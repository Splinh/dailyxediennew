<?php
/**
 * @package HDAT\Interface\Rest
 */

declare(strict_types=1);

namespace HDAT\Interface\Rest;

use HDAT\Auth\WpNonceAuthenticator;
use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Routing\RouterPolicy;
use HDAT\Infrastructure\Persistence\CredentialRepository;
use HDAT\Infrastructure\Persistence\RouteStateRepository;
use HDAT\Infrastructure\Persistence\UsageLedgerRepository;
use HDAT\Infrastructure\Routing\TokenEstimator;
use HDAT\Kernel\ModuleRegistry;
use HDAT\Kernel\ProviderRegistry;
use HDAT\Kernel\Settings;
use HDAT\Updater\GitHubUpdater;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for analytics, settings, modules, dashboard, and updater token.
 */
final class SystemAdminController extends AbstractApiController {

	public function __construct(
		private readonly WpNonceAuthenticator $auth,
		private readonly UsageLedgerRepository $usage,
		private readonly RouteStateRepository $routeState,
		private readonly CredentialRepository $credentials,
		private readonly RouterPolicy $router,
		private readonly TokenEstimator $estimator,
	) {}

	public function register(): void {
		$perm = [ $this->auth, 'check' ];
		$ns   = 'hdat/v1/admin';

		// Analytics.
		$this->route( $ns, '/usage', 'GET', 'getUsage', $perm );
		$this->route( $ns, '/route-state', 'GET', 'listRouteState', $perm );
		$this->route( $ns, '/route-state', 'DELETE', 'resetAllRouteState', $perm );
		$this->route( $ns, '/route-state/(?P<hash>[a-f0-9]+)', 'DELETE', 'resetRouteState', $perm );
		$this->route( $ns, '/route-simulate', 'POST', 'simulateRoute', $perm );

		// Meta & Settings.
		$this->route( $ns, '/providers', 'GET', 'listProviders', $perm );
		$this->route( $ns, '/dashboard', 'GET', 'dashboard', $perm );
		$this->route( $ns, '/modules', 'GET', 'listModules', $perm );
		$this->route( $ns, '/modules', 'PUT', 'saveModules', $perm );
		$this->route( $ns, '/settings', 'GET', 'getSettings', $perm );
		$this->route( $ns, '/settings', 'PUT', 'saveSettings', $perm );

		// Force Provider.
		$this->route( $ns, '/force-provider', 'GET', 'getForcedProvider', $perm );
		$this->route( $ns, '/force-provider', 'PUT', 'setForcedProvider', $perm );
		$this->route( $ns, '/force-provider', 'DELETE', 'clearForcedProvider', $perm );

		// GitHub updater token.
		$this->route( $ns, '/github-token', 'PUT', 'saveGithubToken', $perm );
		$this->route( $ns, '/github-token', 'DELETE', 'deleteGithubToken', $perm );
		$this->route( $ns, '/github-token/status', 'GET', 'githubTokenStatus', $perm );
	}

	public function getUsage( \WP_REST_Request $r ): \WP_REST_Response {
		$filters = [];
		if ( $r->get_param( 'provider' ) ) {
			$filters['provider'] = sanitize_text_field( (string) $r->get_param( 'provider' ) );
		}
		if ( $r->get_param( 'from' ) ) {
			$filters['from'] = sanitize_text_field( (string) $r->get_param( 'from' ) );
		}
		if ( $r->get_param( 'to' ) ) {
			$filters['to'] = sanitize_text_field( (string) $r->get_param( 'to' ) );
		}

		return new \WP_REST_Response( $this->usage->getStats( $filters ), 200 );
	}

	public function listRouteState(): \WP_REST_Response {
		return new \WP_REST_Response( $this->routeState->listAll(), 200 );
	}

	public function resetRouteState( \WP_REST_Request $r ): \WP_REST_Response {
		$this->routeState->reset( sanitize_text_field( (string) $r->get_param( 'hash' ) ) );

		return new \WP_REST_Response( [ 'deleted' => true ], 200 );
	}

	/**
	 * Reset ALL route state + credential cooldowns. One-click recovery after outage.
	 */
	public function resetAllRouteState(): \WP_REST_Response {
		$routes    = $this->routeState->resetAll();
		$cooldowns = $this->credentials->clearAllCooldowns();

		return new \WP_REST_Response(
			[
				'routes_cleared'    => $routes,
				'cooldowns_cleared' => $cooldowns,
			],
			200
		);
	}

	/**
	 * Dry-run routing simulation. Runs the full candidate pipeline without
	 * calling any provider — returns the ranked candidate list, estimated
	 * tokens, and the settings that govern routing.
	 */
	public function simulateRoute( \WP_REST_Request $r ): \WP_REST_Response {
		$body     = $r->get_json_params();
		$messages = is_array( $body['messages'] ?? null ) ? $body['messages'] : [
			[
				'role'    => 'user',
				'content' => 'test',
			],
		];

		$req = new GatewayRequest(
			messages:    $messages,
			model:       isset( $body['model'] ) ? sanitize_text_field( (string) $body['model'] ) : null,
			provider:    isset( $body['provider'] ) ? sanitize_text_field( (string) $body['provider'] ) : null,
			temperature: (float) ( $body['temperature'] ?? 0.7 ),
			maxTokens:   (int) ( $body['max_tokens'] ?? 2048 ),
			extra:       is_array( $body['extra'] ?? null ) ? $body['extra'] : [],
		);

		$consumer   = ConsumerToken::internal();
		$candidates = $this->router->resolve( $req, $consumer );

		$promptTokens     = $this->estimator->estimateMessages( $req->messages );
		$completionTokens = $this->estimator->estimateCompletion( $req->maxTokens );

		$list = [];
		foreach ( $candidates as $i => $c ) {
			$list[] = [
				'rank'          => $i + 1,
				'provider'      => $c->provider,
				'model'         => $c->model,
				'credential_id' => $c->credentialId->value,
				'tier'          => $c->tier->value,
				'priority'      => $c->priority,
				'capabilities'  => array_map( static fn( Capability $cap ) => $cap->value, $c->capabilities ),
				'route_hash'    => $c->routeHash(),
			];
		}

		return new \WP_REST_Response(
			[
				'candidates'       => $list,
				'total_candidates' => count( $list ),
				'estimated_tokens' => [
					'prompt'     => $promptTokens,
					'completion' => $completionTokens,
					'total'      => $promptTokens + $completionTokens,
				],
				'settings'         => [
					'router_strategy'    => Settings::get( 'router_strategy' ),
					'max_route_attempts' => Settings::get( 'max_route_attempts' ),
					'sticky_route_ttl'   => Settings::get( 'sticky_route_ttl' ),
				],
			],
			200
		);
	}

	public function listProviders(): \WP_REST_Response {
		$out = [];
		foreach ( ProviderRegistry::all() as $id => $meta ) {
			$out[] = [
				'id'                   => $meta->id,
				'label'                => $meta->label,
				'api_format'           => $meta->apiFormat,
				'tier'                 => $meta->tier->value,
				'base_url'             => $meta->baseUrl,
				'capabilities'         => array_map( static fn( Capability $c ) => $c->value, $meta->capabilities ),
				'supports_live_models' => $meta->supportsLiveModels,
				'category'             => $meta->category,
				'rate_info'            => $meta->rateInfo,
				'reg_url'              => $meta->regUrl,
				'models_url'           => $meta->modelsUrl,
				'models'               => array_map(
					static fn( $m ) => [
						'id'             => $m->id,
						'name'           => $m->name,
						'context_length' => $m->contextWindow,
					],
					$meta->staticModels
				),
			];
		}

		return new \WP_REST_Response( $out, 200 );
	}

	public function dashboard(): \WP_REST_Response {
		$todayStart  = new \DateTimeImmutable( 'today', wp_timezone() );
		$todayTotals = $this->usage->totals( $todayStart );
		$allTotals   = $this->usage->totals();
		$credResult  = $this->credentials->paginate( 1, 1 );
		$routes      = $this->routeState->listAll();

		$healthy  = 0;
		$degraded = 0;
		foreach ( $routes as $row ) {
			if ( ( (int) ( $row['consecutive_failures'] ?? 0 ) ) >= 3 ) {
				++$degraded;
			} else {
				++$healthy;
			}
		}

		return new \WP_REST_Response(
			[
				'today'       => $todayTotals,
				'all_time'    => $allTotals,
				'credentials' => [
					'total'      => $credResult->total,
					'deprecated' => $this->credentials->countDeprecated(),
				],
				'routes'      => [
					'total'    => count( $routes ),
					'healthy'  => $healthy,
					'degraded' => $degraded,
				],
			],
			200
		);
	}

	public function listModules(): \WP_REST_Response {
		return new \WP_REST_Response( ModuleRegistry::allForAdmin(), 200 );
	}

	public function saveModules( \WP_REST_Request $r ): \WP_REST_Response {
		$slugs = $this->sanitizeStringArray( $r->get_param( 'enabled' ) );
		ModuleRegistry::setEnabled( $slugs );

		return new \WP_REST_Response( ModuleRegistry::allForAdmin(), 200 );
	}

	public function getSettings(): \WP_REST_Response {
		return new \WP_REST_Response( Settings::get(), 200 );
	}

	public function saveSettings( \WP_REST_Request $r ): \WP_REST_Response {
		$body     = $r->get_json_params();
		$defaults = Settings::DEFAULTS;

		if ( is_array( $body ) ) {
			$opts = (array) get_option( Settings::OPTION_KEY, [] );

			foreach ( $body as $key => $value ) {
				if ( ! array_key_exists( $key, $defaults ) ) {
					continue;
				}

				$opts[ $key ] = match ( gettype( $defaults[ $key ] ) ) {
					'integer' => (int) $value,
					'double'  => (float) $value,
					'boolean' => (bool) $value,
					'string'  => sanitize_text_field( (string) $value ),
					default   => $value,
				};
			}

			update_option( Settings::OPTION_KEY, $opts );
		}

		return new \WP_REST_Response( Settings::get(), 200 );
	}

	public function saveGithubToken( \WP_REST_Request $r ): \WP_REST_Response {
		$token = sanitize_text_field( (string) $r->get_param( 'token' ) );
		if ( '' === $token ) {
			return new \WP_REST_Response( [ 'error' => 'token_required' ], 400 );
		}

		$encrypted = GitHubUpdater::encryptToken( $token );
		if ( '' === $encrypted ) {
			return new \WP_REST_Response( [ 'error' => 'encryption_failed' ], 500 );
		}

		update_option( GitHubUpdater::TOKEN_OPTION, $encrypted );

		return new \WP_REST_Response(
			[
				'ok'     => true,
				'source' => GitHubUpdater::tokenSource(),
			],
			200
		);
	}

	public function deleteGithubToken(): \WP_REST_Response {
		delete_option( GitHubUpdater::TOKEN_OPTION );

		return new \WP_REST_Response(
			[
				'ok'     => true,
				'source' => GitHubUpdater::tokenSource(),
			],
			200
		);
	}

	public function githubTokenStatus(): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'has_token' => GitHubUpdater::hasToken(),
				'source'    => GitHubUpdater::tokenSource(),
			],
			200
		);
	}

	public function getForcedProvider(): \WP_REST_Response {
		$credId = Settings::get( 'force_provider_credential_id' );

		if ( null === $credId ) {
			return new \WP_REST_Response(
				[
					'credential_id' => null,
					'credential'    => null,
				],
				200
			);
		}

		// Load credential details.
		try {
			$cred = $this->credentials->findById( new \HDAT\Domain\Credential\CredentialId( (int) $credId ) );

			return new \WP_REST_Response(
				[
					'credential_id' => $cred->id->value,
					'credential'    => [
						'id'        => $cred->id->value,
						'label'     => $cred->label,
						'provider'  => $cred->provider,
						'is_active' => $cred->isActive,
					],
				],
				200
			);
		} catch ( \Throwable $e ) {
			// Credential not found or deleted — return null but keep setting.
			return new \WP_REST_Response(
				[
					'credential_id' => $credId,
					'credential'    => null,
					'error'         => 'credential_not_found',
				],
				200
			);
		}
	}

	public function setForcedProvider( \WP_REST_Request $r ): \WP_REST_Response {
		$credId = $r->get_param( 'credential_id' );

		if ( null === $credId || '' === $credId ) {
			return new \WP_REST_Response( [ 'error' => 'credential_id_required' ], 400 );
		}

		$credId = (int) $credId;

		// Validate credential exists and is active.
		try {
			$cred = $this->credentials->findById( new \HDAT\Domain\Credential\CredentialId( $credId ) );

			if ( ! $cred->isActive ) {
				return new \WP_REST_Response( [ 'error' => 'credential_not_active' ], 400 );
			}
		} catch ( \Throwable $e ) {
			return new \WP_REST_Response( [ 'error' => 'credential_not_found' ], 404 );
		}

		Settings::set( 'force_provider_credential_id', $credId );

		return new \WP_REST_Response(
			[
				'ok'            => true,
				'credential_id' => $credId,
			],
			200
		);
	}

	public function clearForcedProvider(): \WP_REST_Response {
		Settings::set( 'force_provider_credential_id', null );

		return new \WP_REST_Response(
			[
				'ok'            => true,
				'credential_id' => null,
			],
			200
		);
	}
}
