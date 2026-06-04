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
use HDAT\Infrastructure\Http\CurlAdapter;
use HDAT\Infrastructure\Persistence\CredentialRepository;
use HDAT\Providers\Custom\CustomProvider;
use HDAT\Providers\Custom\CustomProviderMeta;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for custom provider validation and management.
 */
final class CustomProviderAdminController extends AbstractApiController {

	public function __construct(
		private readonly WpNonceAuthenticator $auth,
		private readonly CurlAdapter $http,
		private readonly CredentialRepository $credentials,
	) {}

	public function register(): void {
		$perm = [ $this->auth, 'check' ];
		$ns   = 'hdat/v1/admin';

		$this->route( $ns, '/custom-providers/validate', 'POST', 'validateCustomProvider', $perm );
	}

	/**
	 * Validate a custom provider configuration by testing API format compatibility.
	 *
	 * POST /wp-json/hdat/v1/admin/custom-providers/validate
	 *
	 * Body:
	 *   - api_format: 'openai_compatible' | 'anthropic_messages' (required)
	 *   - base_url: string (required)
	 *   - api_key: string (required unless credential_id is supplied)
	 *   - credential_id: int (optional; on edit, reuse the stored key/base_url)
	 *   - custom_label: string (optional, defaults to 'Custom Provider')
	 *   - models_url: string (optional)
	 *   - auth_header_name: string (optional, defaults to 'Authorization')
	 *   - auth_header_prefix: string (optional, defaults to 'Bearer')
	 *
	 * Response:
	 *   - valid: bool
	 *   - detected_format: string (if valid)
	 *   - sample_models: array (if models_url provided and valid)
	 *   - error: string (if not valid)
	 *   - validation_errors: array (if metadata validation fails)
	 */
	public function validateCustomProvider( \WP_REST_Request $r ): \WP_REST_Response {
		// Extract and sanitize parameters
		$apiFormat        = sanitize_text_field( (string) ( $r->get_param( 'api_format' ) ?? '' ) );
		$baseUrl          = sanitize_text_field( (string) ( $r->get_param( 'base_url' ) ?? '' ) );
		$apiKey           = sanitize_text_field( (string) ( $r->get_param( 'api_key' ) ?? '' ) );
		$customLabel      = sanitize_text_field( (string) ( $r->get_param( 'custom_label' ) ?? 'Custom Provider' ) );
		$modelsUrl        = $r->get_param( 'models_url' ) ? sanitize_text_field( (string) $r->get_param( 'models_url' ) ) : null;
		$authHeaderName   = sanitize_text_field( (string) ( $r->get_param( 'auth_header_name' ) ?? 'Authorization' ) );
		$authHeaderPrefix = sanitize_text_field( (string) ( $r->get_param( 'auth_header_prefix' ) ?? 'Bearer' ) );
		$credentialId     = $this->nullableInt( $r, 'credential_id' );

		// On edit, an empty api_key means "reuse stored key". Hydrate key/base_url
		// from the persisted credential so the user need not re-enter the secret.
		if ( '' === $apiKey && null !== $credentialId ) {
			try {
				$cred   = $this->credentials->findById( new CredentialId( $credentialId ) );
				$apiKey = $cred->apiKey;
				if ( '' === $baseUrl && null !== $cred->baseUrl ) {
					$baseUrl = $cred->baseUrl;
				}
			} catch ( \Throwable $e ) {
				return new \WP_REST_Response(
					[
						'valid' => false,
						'error' => 'Credential not found for validation',
					],
					404
				);
			}
		}

		// Validate required fields
		if ( '' === $apiFormat ) {
			return new \WP_REST_Response(
				[
					'valid' => false,
					'error' => 'api_format is required',
				],
				400
			);
		}

		if ( '' === $baseUrl ) {
			return new \WP_REST_Response(
				[
					'valid' => false,
					'error' => 'base_url is required',
				],
				400
			);
		}

		if ( '' === $apiKey ) {
			return new \WP_REST_Response(
				[
					'valid' => false,
					'error' => 'api_key is required',
				],
				400
			);
		}

		// Validate base_url format
		if ( ! filter_var( $baseUrl, FILTER_VALIDATE_URL ) ) {
			return new \WP_REST_Response(
				[
					'valid' => false,
					'error' => 'base_url must be a valid URL',
				],
				400
			);
		}

		// Create CustomProviderMeta for validation
		$meta = CustomProviderMeta::fromArray(
			[
				'api_format'           => $apiFormat,
				'custom_label'         => $customLabel,
				'models_url'           => $modelsUrl,
				'auth_header_name'     => $authHeaderName,
				'auth_header_prefix'   => $authHeaderPrefix,
				'capabilities'         => [ 'chat' ],
				'supports_live_models' => null !== $modelsUrl && '' !== $modelsUrl,
			]
		);

		// Validate metadata
		$validationErrors = $meta->validate();
		if ( ! empty( $validationErrors ) ) {
			return new \WP_REST_Response(
				[
					'valid'             => false,
					'error'             => 'Validation failed',
					'validation_errors' => $validationErrors,
				],
				400
			);
		}

		// Test API format compatibility
		$testResult = $this->testApiFormat( $meta, $baseUrl, $apiKey );

		if ( ! $testResult['valid'] ) {
			return new \WP_REST_Response( $testResult, 200 );
		}

		// If models_url provided, try to fetch models
		$sampleModels = [];
		if ( null !== $modelsUrl && '' !== $modelsUrl ) {
			$modelsResult = $this->fetchModels( $meta, $apiKey );
			if ( $modelsResult['success'] ) {
				$sampleModels = $modelsResult['models'];
			}
		}

		return new \WP_REST_Response(
			[
				'valid'           => true,
				'detected_format' => $apiFormat,
				'sample_models'   => $sampleModels,
			],
			200
		);
	}

	/**
	 * Test API format compatibility by sending a minimal request.
	 *
	 * @return array{valid: bool, detected_format?: string, error?: string}
	 */
	private function testApiFormat( CustomProviderMeta $meta, string $baseUrl, string $apiKey ): array {
		try {
			$provider = new CustomProvider( $meta );

			// Create a minimal test request
			$testRequest = new GatewayRequest(
				messages:  [
					[
						'role'    => 'user',
						'content' => 'Reply with "ok"',
					],
				],
				model:     'test-model',
				maxTokens: 5,
			);

			// Build the request using the provider
			[ $url, $headers, $body ] = $provider->buildRequest(
				$testRequest,
				new Credential(
					id:           new CredentialId( 0 ),
					provider:     'custom',
					label:        'Test',
					apiKey:       $apiKey,
					baseUrl:      $baseUrl,
					tier:         CredentialTier::Free,
					priority:     5,
					isActive:     true,
					capabilities: [ Capability::Chat ],
				)
			);

			// Send the request with a short timeout
			$response = $this->http->post( $url, $headers, $body );

			// Check response status
			if ( $response['status'] >= 400 ) {
				$errorMsg = $this->extractErrorMessage( $response['body'] );

				// Check if it's an auth error (expected for test)
				if ( in_array( $response['status'], [ 401, 403 ], true ) ) {
					// Auth error means the endpoint exists and format is likely correct
					return [
						'valid'           => true,
						'detected_format' => $meta->apiFormat,
					];
				}

				// Check if it's a model-not-found error (also indicates correct format)
				if ( 404 === $response['status'] && $this->isModelNotFoundError( $errorMsg ) ) {
					return [
						'valid'           => true,
						'detected_format' => $meta->apiFormat,
					];
				}

				// Check if it's a validation error (indicates correct format but invalid request)
				if ( 400 === $response['status'] ) {
					return [
						'valid'           => true,
						'detected_format' => $meta->apiFormat,
					];
				}

				return [
					'valid' => false,
					'error' => "API returned HTTP {$response['status']}: {$errorMsg}",
				];
			}

			// Success response - format is valid
			return [
				'valid'           => true,
				'detected_format' => $meta->apiFormat,
			];

		} catch ( \Throwable $e ) {
			return [
				'valid' => false,
				'error' => 'Connection failed: ' . $e->getMessage(),
			];
		}
	}

	/**
	 * Fetch models from the custom provider's models endpoint.
	 *
	 * @return array{success: bool, models: array, error?: string}
	 */
	private function fetchModels( CustomProviderMeta $meta, string $apiKey ): array {
		if ( null === $meta->modelsUrl ) {
			return [
				'success' => false,
				'models'  => [],
				'error'   => 'No models URL configured',
			];
		}

		try {
			$provider = new CustomProvider( $meta );
			$req      = $provider->buildModelsRequest( $apiKey );
			if ( ! $req ) {
				return [
					'success' => false,
					'models'  => [],
					'error'   => 'No models URL configured',
				];
			}

			[ $url, $headers ] = $req;
			$response          = $this->http->get( $url, $headers );

			if ( $response['status'] >= 400 ) {
				$errorMsg = $this->extractErrorMessage( $response['body'] );

				return [
					'success' => false,
					'models'  => [],
					'error'   => "Models endpoint returned HTTP {$response['status']}: {$errorMsg}",
				];
			}

			// Parse models response
			$models = $provider->parseModelsResponse( $response['body'] );

			// Limit to first 10 models for sample
			$sampleModels = array_slice( $models, 0, 10 );

			return [
				'success' => true,
				'models'  => $sampleModels,
			];

		} catch ( \Throwable $e ) {
			return [
				'success' => false,
				'models'  => [],
				'error'   => 'Failed to fetch models: ' . $e->getMessage(),
			];
		}
	}

	/**
	 * Extract error message from response body.
	 *
	 * @param array<string, mixed> $body
	 */
	private function extractErrorMessage( array $body ): string {
		// Try various error message locations
		$msg = $body['error']['message']
			?? $body['error']['msg']
			?? $body['error']
			?? $body['message']
			?? $body['msg']
			?? null;

		if ( is_string( $msg ) && '' !== $msg ) {
			return substr( $msg, 0, 200 );
		}

		if ( is_array( $msg ) ) {
			return substr( (string) wp_json_encode( $msg ), 0, 200 );
		}

		return 'Unknown error';
	}

	/**
	 * Check if error message indicates model not found.
	 */
	private function isModelNotFoundError( string $errorMsg ): bool {
		return (bool) preg_match( '/model.*not.*found|invalid.*model|unknown.*model/i', $errorMsg );
	}
}
