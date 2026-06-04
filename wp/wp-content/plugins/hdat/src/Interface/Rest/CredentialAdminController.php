<?php
/**
 * @package HDAT\Interface\Rest
 */

declare(strict_types=1);

namespace HDAT\Interface\Rest;

use HDAT\Auth\WpNonceAuthenticator;
use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Credential\CredentialId;
use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderCapsule;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Infrastructure\Http\CurlAdapter;
use HDAT\Infrastructure\Persistence\CredentialRepository;
use HDAT\Kernel\ProviderRegistry;
use HDAT\Providers\OpenRouter\OpenRouterPool;
use HDAT\Providers\Custom\CustomProviderMeta;
use HDAT\Providers\Custom\CustomProvider;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for credentials management & probing.
 */
final class CredentialAdminController extends AbstractApiController {

	public function __construct(
		private readonly WpNonceAuthenticator $auth,
		private readonly CredentialRepository $credentials,
		private readonly CurlAdapter $http,
	) {}

	public function register(): void {
		$perm = [ $this->auth, 'check' ];
		$ns   = 'hdat/v1/admin';

		$this->route( $ns, '/credentials', 'GET', 'listCredentials', $perm );
		$this->route( $ns, '/credentials', 'POST', 'createCredential', $perm );
		$this->route( $ns, '/credentials/(?P<id>\d+)', 'PUT', 'updateCredential', $perm );
		$this->route( $ns, '/credentials/(?P<id>\d+)', 'DELETE', 'deleteCredential', $perm );
		$this->route( $ns, '/credentials/(?P<id>\d+)/test', 'POST', 'testCredential', $perm );
		$this->route( $ns, '/credentials/(?P<id>\d+)/health', 'POST', 'healthCredential', $perm );
		$this->route( $ns, '/providers/(?P<provider_id>[a-z0-9_-]+)/models', 'POST', 'fetchProviderModels', $perm );
	}

	public function listCredentials( \WP_REST_Request $r ): \WP_REST_Response {
		$page     = max( 1, (int) $r->get_param( 'page' ) ?: 1 );
		$perPage  = min( 100, max( 1, (int) $r->get_param( 'per_page' ) ?: 20 ) );
		$provider = $r->get_param( 'provider' ) ? sanitize_text_field( (string) $r->get_param( 'provider' ) ) : null;

		$result = $this->credentials->paginate( $page, $perPage, $provider );

		return new \WP_REST_Response(
			[
				'items' => array_map( [ $this, 'serializeCred' ], $result->items ),
				'total' => $result->total,
				'pages' => $result->pages,
			],
			200
		);
	}

	public function createCredential( \WP_REST_Request $r ): \WP_REST_Response {
		try {
			$cred = $this->hydrateCredential( $r, CredentialId::new() );
			$id   = $this->credentials->save( $cred );

			return new \WP_REST_Response(
				$this->serializeCred( $this->credentials->findById( $id ) ),
				201
			);
		} catch ( \InvalidArgumentException $e ) {
			return new \WP_REST_Response(
				[
					'code'    => 'hdat_invalid_credential',
					'message' => $e->getMessage(),
				],
				400
			);
		} catch ( \Throwable $e ) {
			return new \WP_REST_Response(
				[
					'code'    => 'hdat_credential_error',
					'message' => $e->getMessage(),
				],
				500
			);
		}
	}

	public function updateCredential( \WP_REST_Request $r ): \WP_REST_Response {
		try {
			$id       = new CredentialId( (int) $r->get_param( 'id' ) );
			$existing = $this->credentials->findById( $id );
			$updated  = $this->hydrateCredential( $r, $id, $existing );

			$this->credentials->save( $updated );

			delete_transient( 'hdat_models_' . $id->value );

			return new \WP_REST_Response( $this->serializeCred( $this->credentials->findById( $id ) ), 200 );
		} catch ( \InvalidArgumentException $e ) {
			return new \WP_REST_Response(
				[
					'code'    => 'hdat_invalid_credential',
					'message' => $e->getMessage(),
				],
				400
			);
		} catch ( \Throwable $e ) {
			return new \WP_REST_Response(
				[
					'code'    => 'hdat_credential_error',
					'message' => $e->getMessage(),
				],
				500
			);
		}
	}

	public function deleteCredential( \WP_REST_Request $r ): \WP_REST_Response {
		$id = (int) $r->get_param( 'id' );
		$this->credentials->delete( new CredentialId( $id ) );

		delete_transient( 'hdat_models_' . $id );

		return new \WP_REST_Response( [ 'deleted' => true ], 200 );
	}

	public function testCredential( \WP_REST_Request $r ): \WP_REST_Response {
		return $this->probeCredential( (int) $r->get_param( 'id' ), 'test' );
	}

	public function healthCredential( \WP_REST_Request $r ): \WP_REST_Response {
		return $this->probeCredential( (int) $r->get_param( 'id' ), 'health' );
	}

	public function fetchProviderModels( \WP_REST_Request $r ): \WP_REST_Response {
		$providerId = sanitize_text_field( (string) $r->get_param( 'provider_id' ) );

		// Custom providers aren't in the static registry — fetch via their
		// configured models_url (CustomProviderMeta), using the stored key
		// (credential_id) or a raw api_key from the add/edit form.
		if ( 'custom' === $providerId ) {
			return $this->fetchCustomProviderModels( $r );
		}

		if ( ! ProviderRegistry::has( $providerId ) ) {
			return new \WP_REST_Response( [ 'message' => 'Unknown provider' ], 404 );
		}

		$provider = ProviderRegistry::get( $providerId );
		$meta     = $provider::meta();

		$credId  = $this->nullableInt( $r, 'credential_id' );
		$baseUrl = null;

		if ( null !== $credId ) {
			try {
				$cred    = $this->credentials->findById( new CredentialId( $credId ) );
				$apiKey  = $cred->apiKey;
				$baseUrl = $cred->baseUrl ?: null;
			} catch ( \Throwable $e ) {
				return new \WP_REST_Response( [ 'message' => 'Credential not found' ], 404 );
			}
		} else {
			$apiKey = sanitize_text_field( (string) ( $r->get_param( 'api_key' ) ?? '' ) );
			if ( '' === $apiKey ) {
				return new \WP_REST_Response( [ 'message' => 'api_key or credential_id required' ], 400 );
			}
			$rawBase = sanitize_text_field( (string) ( $r->get_param( 'base_url' ) ?? '' ) );
			$baseUrl = '' !== $rawBase ? $rawBase : null;
		}

		try {
			$models = $this->fetchModelsViaProvider( $provider, $apiKey, $baseUrl );
		} catch ( \Throwable $e ) {
			return new \WP_REST_Response(
				[
					'message' => $e->getMessage(),
					'models'  => [],
				],
				200
			);
		}

		if ( $meta->staticModels ) {
			$static  = array_map(
				static fn( $m ) => [
					'id'   => $m->id,
					'name' => $m->name,
				],
				$meta->staticModels
			);
			$liveIds = array_column( $models, 'id' );
			$unique  = array_filter( $static, static fn( $s ) => ! in_array( $s['id'], $liveIds, true ) );
			$models  = array_merge( $unique, $models );
		}

		return new \WP_REST_Response( [ 'models' => $models ], 200 );
	}

	/**
	 * Fetch live models for a custom provider via its configured models_url.
	 *
	 * Reads config from the request (current form state) and resolves the API
	 * key from a stored credential (credential_id) or a raw api_key. Always
	 * returns HTTP 200 — failures surface as an empty list + message so the UI
	 * can fall back to manual model entry.
	 */
	private function fetchCustomProviderModels( \WP_REST_Request $r ): \WP_REST_Response {
		$modelsUrl = sanitize_text_field( (string) ( $r->get_param( 'models_url' ) ?? '' ) );
		if ( '' === $modelsUrl ) {
			return new \WP_REST_Response(
				[
					'models'  => [],
					'message' => 'Provider chưa cấu hình Models Endpoint',
				],
				200
			);
		}

		// Resolve API key: raw key takes precedence, else fall back to the stored
		// credential's key (edit flow, key shown as "unchanged").
		$apiKey = sanitize_text_field( (string) ( $r->get_param( 'api_key' ) ?? '' ) );
		$credId = $this->nullableInt( $r, 'credential_id' );
		if ( '' === $apiKey && null !== $credId ) {
			try {
				$apiKey = $this->credentials->findById( new CredentialId( $credId ) )->apiKey;
			} catch ( \Throwable $e ) {
				return new \WP_REST_Response(
					[
						'models'  => [],
						'message' => 'Credential not found',
					],
					200
				);
			}
		}

		$meta = CustomProviderMeta::fromArray(
			[
				'api_format'           => sanitize_text_field( (string) ( $r->get_param( 'api_format' ) ?? 'openai_compatible' ) ),
				'custom_label'         => 'Custom',
				'models_url'           => $modelsUrl,
				'auth_header_name'     => sanitize_text_field( (string) ( $r->get_param( 'auth_header_name' ) ?? 'Authorization' ) ),
				'auth_header_prefix'   => sanitize_text_field( (string) ( $r->get_param( 'auth_header_prefix' ) ?? 'Bearer' ) ),
				'supports_live_models' => true,
			]
		);

		try {
			$models = $this->fetchModelsViaProvider( new CustomProvider( $meta ), $apiKey );
		} catch ( \Throwable $e ) {
			return new \WP_REST_Response(
				[
					'models'  => [],
					'message' => $e->getMessage(),
				],
				200
			);
		}

		return new \WP_REST_Response( [ 'models' => $models ], 200 );
	}

	private function hydrateCredential( \WP_REST_Request $r, CredentialId $id, ?Credential $existing = null ): Credential {
		$capsRaw = $r->get_param( 'capabilities' );
		$caps    = [];
		if ( is_array( $capsRaw ) ) {
			foreach ( $capsRaw as $raw ) {
				$enum = Capability::tryFrom( (string) $raw );
				if ( null !== $enum ) {
					$caps[] = $enum;
				}
			}
		} elseif ( null !== $existing ) {
			$caps = $existing->capabilities;
		}

		// Handle custom provider metadata.
		// The provider id is `custom:<slug>` (frontend) — match the prefix, not an
		// exact 'custom', otherwise the metadata block is silently dropped on save.
		$customProviderMeta = null;
		$metaData           = null;
		$provider           = sanitize_text_field( (string) ( $r->get_param( 'provider' ) ?? $existing?->provider ?? '' ) );
		$isCustom           = 'custom' === $provider || str_starts_with( $provider, 'custom:' );

		if ( $isCustom && $r->has_param( 'custom_provider_meta' ) ) {
			$metaRaw = $r->get_param( 'custom_provider_meta' );
			if ( is_string( $metaRaw ) ) {
				$decoded = json_decode( $metaRaw, true );
				if ( is_array( $decoded ) ) {
					$metaData           = $decoded;
					$customProviderMeta = CustomProviderMeta::fromArray( $decoded );
				}
			} elseif ( is_array( $metaRaw ) ) {
				$metaData           = $metaRaw;
				$customProviderMeta = CustomProviderMeta::fromArray( $metaRaw );
			}
		} elseif ( null !== $existing ) {
			$customProviderMeta = $existing->customProviderMeta;
		}

		if ( $isCustom && null === $customProviderMeta ) {
			throw new \InvalidArgumentException( 'custom_provider_meta is required for custom providers' );
		}

		if ( null !== $customProviderMeta ) {
			$validationErrors = $customProviderMeta->validate();
			if ( ! empty( $validationErrors ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \InvalidArgumentException( 'Invalid custom provider metadata: ' . implode( ', ', $validationErrors ) );
			}
		}

		$baseUrl = $r->has_param( 'base_url' ) ? ( (string) $r->get_param( 'base_url' ) ?: null ) : $existing?->baseUrl;
		if ( $isCustom ) {
			if ( null === $baseUrl && is_array( $metaData ) ) {
				$legacyBaseUrl = $metaData['base_url'] ?? $metaData['baseUrl'] ?? null;
				if ( is_string( $legacyBaseUrl ) && '' !== trim( $legacyBaseUrl ) ) {
					$baseUrl = trim( $legacyBaseUrl );
				}
			}

			$baseUrl = null !== $baseUrl ? trim( $baseUrl ) : null;
			if ( null === $baseUrl || '' === $baseUrl ) {
				throw new \InvalidArgumentException( 'base_url is required for custom providers' );
			}
			if ( ! filter_var( $baseUrl, FILTER_VALIDATE_URL ) ) {
				throw new \InvalidArgumentException( 'base_url must be a valid URL for custom providers' );
			}

			if ( null !== $customProviderMeta ) {
				$slug     = sanitize_title( $customProviderMeta->customLabel );
				$provider = 'custom:' . ( '' !== $slug ? $slug : 'custom-provider' );
			}
		}

		return new Credential(
			id:                $id,
			provider:          $provider,
			label:             sanitize_text_field( (string) ( $r->get_param( 'label' ) ?? $existing?->label ?? '' ) ),
			apiKey:            sanitize_text_field( (string) ( $r->get_param( 'api_key' ) ?? $existing?->apiKey ?? '' ) ),
			baseUrl:           $baseUrl,
			tier:              CredentialTier::tryFrom( (string) ( $r->get_param( 'tier' ) ?? $existing?->tier->value ?? 'free' ) ) ?? CredentialTier::Free,
			priority:          (int) ( $r->get_param( 'priority' ) ?? $existing?->priority ?? 5 ),
			isActive:          $r->has_param( 'is_active' ) ? (bool) $r->get_param( 'is_active' ) : ( $existing?->isActive ?? true ),
			capabilities:      $caps,
			rpmLimit:          $this->nullableInt( $r, 'rpm_limit' ) ?? $existing?->rpmLimit,
			rpdLimit:          $this->nullableInt( $r, 'rpd_limit' ) ?? $existing?->rpdLimit,
			tpmLimit:          $this->nullableInt( $r, 'tpm_limit' ) ?? $existing?->tpmLimit,
			tpdLimit:          $this->nullableInt( $r, 'tpd_limit' ) ?? $existing?->tpdLimit,
			dailyTokenLimit:   $this->nullableInt( $r, 'daily_token_limit' ) ?? $existing?->dailyTokenLimit,
			monthlyTokenLimit: $this->nullableInt( $r, 'monthly_token_limit' ) ?? $existing?->monthlyTokenLimit,
			cooldownUntil:     $existing?->cooldownUntil,
			preferredModel:    $r->has_param( 'preferred_model' )
				? ( (string) $r->get_param( 'preferred_model' ) ?: null )
				: $existing?->preferredModel,
			customProviderMeta: $customProviderMeta,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function serializeCred( Credential $c ): array {
		$masked = strlen( $c->apiKey ) > 4
			? '••••' . substr( $c->apiKey, -4 )
			: '••••';

		$data = [
			'id'                  => $c->id->value,
			'provider'            => $c->provider,
			'label'               => $c->label,
			'api_key_masked'      => $masked,
			'base_url'            => $c->baseUrl,
			'tier'                => $c->tier->value,
			'priority'            => $c->priority,
			'is_active'           => $c->isActive,
			'capabilities'        => array_map( static fn( Capability $cap ) => $cap->value, $c->capabilities ),
			'rpm_limit'           => $c->rpmLimit,
			'rpd_limit'           => $c->rpdLimit,
			'tpm_limit'           => $c->tpmLimit,
			'tpd_limit'           => $c->tpdLimit,
			'daily_token_limit'   => $c->dailyTokenLimit,
			'monthly_token_limit' => $c->monthlyTokenLimit,
			'cooldown_until'      => $c->cooldownUntil?->format( 'c' ),
			'preferred_model'     => $c->preferredModel,
		];

		// Include custom provider metadata if present
		if ( null !== $c->customProviderMeta ) {
			$data['custom_provider_meta'] = $c->customProviderMeta->toArray();
		}

		return $data;
	}

	private function probeCredential( int $credentialId, string $mode ): \WP_REST_Response {
		try {
			$cred = $this->credentials->findById( new CredentialId( $credentialId ) );

			$provider = ProviderRegistry::getForCredential( $cred );
			$meta     = ProviderRegistry::metaForCredential( $cred );

			$models = $this->resolveProbeModels( $cred, $meta );

			if ( empty( $models ) ) {
				return $this->probeResult( $mode, false, 'No model available for testing. Set a preferred model on this credential.' );
			}

			$lastError = '';
			$attempts  = min( count( $models ), 10 );

			for ( $i = 0; $i < $attempts; $i++ ) {
				$model = $models[ $i ];

				$req = new GatewayRequest(
					messages:  [
						[
							'role'    => 'user',
							'content' => 'Reply with "ok"',
						],
					],
					model:     $model,
					maxTokens: 5,
				);

				$t0                       = microtime( true );
				[ $url, $headers, $body ] = $provider->buildRequest( $req, $cred );
				$http                     = $this->http->post( $url, $headers, $body );
				$ms                       = (int) ( ( microtime( true ) - $t0 ) * 1000 );

				$error = $http['body']['error'] ?? null;
				if ( $http['status'] >= 400 || ! empty( $error ) ) {
					$errMsg = $error['message'] ?? $error['metadata']['raw'] ?? ( 'HTTP ' . $http['status'] );

					if ( $this->isBillingError( $http['status'], (string) $errMsg ) ) {
						$lastError = "[$model] $errMsg";
						continue;
					}

					return $this->probeResult( $mode, false, "[$model] $errMsg", $ms );
				}

				$resp = $provider->parseResponse( $http );

				return $this->probeResult( $mode, true, null, $ms, $resp->model );
			}

			return $this->probeResult( $mode, false, $lastError );
		} catch ( \Throwable $e ) {
			return $this->probeResult( $mode, false, $e->getMessage() );
		}
	}

	private function isBillingError( int $status, string $message ): bool {
		if ( 402 === $status ) {
			return true;
		}

		return (bool) preg_match( '/balance|quota|billing|subscription|payment|recharge|insufficient/i', $message );
	}

	/**
	 * @return string[]
	 */
	private function resolveProbeModels( Credential $cred, ProviderMeta $meta ): array {
		if ( $cred->preferredModel ) {
			return [ $cred->preferredModel ];
		}

		if ( 'openrouter' === $cred->provider ) {
			$poolModel = OpenRouterPool::getEnabledModelIds()[0] ?? null;
			if ( $poolModel ) {
				return [ $poolModel ];
			}
		}

		$staticIds = array_map( static fn( $m ) => $m->id, $meta->staticModels );

		$provider = ProviderRegistry::getForCredential( $cred );
		$baseUrl  = $cred->baseUrl ?: null;

		try {
			$fetched = $this->fetchModelsViaProvider( $provider, $cred->apiKey, $baseUrl );
		} catch ( \Throwable $e ) {
			return $staticIds;
		}

		$chatModels = array_filter(
			$fetched,
			static fn( array $m ) => ! preg_match(
				'/embed|whisper|tts|audio|speech|orpheus|dall-e|image|stable-diffusion|flux|sdxl|moderation|rerank/i',
				$m['id']
			)
		);

		$liveIds = array_map( static fn( array $m ) => $m['id'], array_values( $chatModels ?: $fetched ) );

		$seen   = [];
		$result = [];
		foreach ( array_merge( $staticIds, $liveIds ) as $id ) {
			if ( ! isset( $seen[ $id ] ) ) {
				$seen[ $id ] = true;
				$result[]    = $id;
			}
		}

		return $result;
	}

	private function probeResult( string $mode, bool $ok, ?string $error = null, int $ms = 0, ?string $model = null ): \WP_REST_Response {
		if ( 'health' === $mode ) {
			$data = [
				'status'     => $ok ? 'ok' : 'error',
				'latency_ms' => $ms,
			];
			if ( $ok ) {
				$data['model'] = $model;
			} else {
				$data['error'] = $error;
			}
		} else {
			$data = [ 'ok' => $ok ];
			if ( $ok ) {
				$data['model']      = $model;
				$data['latency_ms'] = $ms;
			} else {
				$data['error'] = $error;
			}
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * @return array<int, array{id: string, name: string}>
	 */
	private function fetchModelsViaProvider( ProviderCapsule $provider, string $apiKey, ?string $baseUrl = null ): array {
		$req = $provider->buildModelsRequest( $apiKey, $baseUrl );

		if ( ! $req ) {
			return [];
		}

		[ $url, $headers ] = $req;
		$response          = $this->http->get( $url, $headers );

		if ( $response['status'] >= 400 ) {
			$msg = $response['body']['error']['message']
				?? $response['body']['error']['msg']
				?? ( 'HTTP ' . $response['status'] );
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \RuntimeException( (string) $msg );
		}

		return $provider->parseModelsResponse( $response['body'] );
	}
}
