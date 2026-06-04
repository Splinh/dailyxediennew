<?php
/**
 * @package HDAT\Modules
 */

declare(strict_types=1);

namespace HDAT\Modules;

use HDAT\Auth\BearerTokenAuthenticator;
use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Provider\Capability;
use HDAT\Infrastructure\Http\CurlAdapter;
use HDAT\Infrastructure\Http\ProviderException;
use HDAT\Infrastructure\Persistence\CredentialRepository;
use HDAT\Kernel\Plugin;
use HDAT\Kernel\ProviderRegistry;
use HDAT\Providers\OpenAICompatibleImageFormat;

defined( 'ABSPATH' ) || exit;

/**
 * Image generation module.
 *
 * Exposes `POST hdat/v1/images/generations` (OpenAI-compatible payload) and
 * routes through the first active, OpenAI-format credential whose provider
 * declares `Capability::Image` and is allowed by the bearer-token consumer.
 *
 * Streaming and token-aware routing are intentionally out of scope — image
 * generation returns a single JSON body and providers don't report tokens.
 * The module forwards request/response shapes verbatim.
 */
final class ImageGenModule implements ModuleInterface {

	public static function slug(): string {
		return 'image_gen';
	}

	public static function title(): string {
		return 'Image Generation';
	}

	public static function description(): string {
		return 'Enables /hdat/v1/images/generations endpoint.';
	}

	public static function alwaysActive(): bool {
		return false;
	}

	public function boot(): void {
		add_action(
			'rest_api_init',
			function (): void {
				$auth = Plugin::instance()->container()->make( BearerTokenAuthenticator::class );

				register_rest_route(
					'hdat/v1',
					'/images/generations',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'generate' ],
						'permission_callback' => [ $auth, 'authenticate' ],
					]
				);
			}
		);
	}

	public function generate( \WP_REST_Request $request ): \WP_REST_Response {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return $this->error( 'invalid_body', 'Request body must be JSON.', 400 );
		}

		$prompt = isset( $body['prompt'] ) ? trim( (string) $body['prompt'] ) : '';
		if ( '' === $prompt ) {
			return $this->error( 'prompt_required', 'prompt is required.', 400 );
		}

		$consumer = $this->resolveConsumer( $request );

		$candidate = $this->pickCredential( $consumer );
		if ( null === $candidate ) {
			return $this->error( 'no_route_available', 'No image-capable credential available.', 502 );
		}

		[ $cred, $meta ] = $candidate;

		[ $url, $headers, $payload ] = OpenAICompatibleImageFormat::buildGenerationRequest( $cred, $meta, $body, $prompt );

		try {
			$http = $this->http()->post( $url, $headers, $payload );
		} catch ( ProviderException $e ) {
			return $this->error( 'provider_error', $e->getMessage(), 502 );
		} catch ( \Throwable $t ) {
			return $this->error( 'gateway_error', $t->getMessage(), 500 );
		}

		if ( $http['status'] >= 400 ) {
			return $this->error( 'provider_error', OpenAICompatibleImageFormat::errorMessage( $http ), $http['status'] );
		}

		Plugin::instance()->container()
			->make( CredentialRepository::class )
			->recordUsage( $cred->id );

		return new \WP_REST_Response( $http['body'], 200 );
	}

	/**
	 * Pick the first active, image-capable credential allowed by the consumer.
	 *
	 * @return array{0: Credential, 1: \HDAT\Domain\Provider\ProviderMeta}|null
	 */
	private function pickCredential( ConsumerToken $consumer ): ?array {
		$repo  = Plugin::instance()->container()->make( CredentialRepository::class );
		$creds = $repo->findActive();

		foreach ( $creds as $cred ) {
			if ( ! $consumer->allowsProvider( $cred->provider ) ) {
				continue;
			}

			if ( ! ProviderRegistry::hasForCredential( $cred ) ) {
				continue;
			}

			$meta = ProviderRegistry::metaForCredential( $cred );
			if ( 'openai_compatible' !== $meta->apiFormat ) {
				continue;
			}
			if ( ! in_array( Capability::Image, $meta->capabilities, true ) ) {
				continue;
			}

			return [ $cred, $meta ];
		}

		return null;
	}

	private function resolveConsumer( \WP_REST_Request $request ): ConsumerToken {
		$attrs = $request->get_attributes();
		$token = $attrs['consumer_token'] ?? null;

		return $token instanceof ConsumerToken ? $token : ConsumerToken::internal();
	}

	private function http(): CurlAdapter {
		return Plugin::instance()->container()->make( CurlAdapter::class );
	}

	private function error( string $code, string $message, int $status ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'error' => [
					'code'    => $code,
					'message' => $message,
				],
			],
			$status
		);
	}
}
