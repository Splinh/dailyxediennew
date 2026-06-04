<?php
/**
 * @package HDAT\Providers\Custom
 */

declare(strict_types=1);

namespace HDAT\Providers\Custom;

use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderCapsule;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Domain\Routing\FailureCategory;
use HDAT\Providers\AnthropicMessagesFormat;
use HDAT\Providers\OpenAICompatibleFormat;

defined( 'ABSPATH' ) || exit;

/**
 * Dynamic provider that adapts behavior based on credential metadata.
 *
 * Supports two API formats:
 *   - openai_compatible: /chat/completions endpoint
 *   - anthropic_messages: /v1/messages endpoint
 *
 * Configuration is stored in the credential's customProviderMeta property.
 */
final class CustomProvider implements ProviderCapsule {

	public function __construct(
		private readonly CustomProviderMeta $meta,
	) {}

	public static function meta(): ProviderMeta {
		// Static meta for registry discovery. Actual behavior is instance-specific.
		return new ProviderMeta(
			id:                 'custom',
			label:              'Custom Provider',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Paid,
			baseUrl:            '',
			capabilities:       [ Capability::Chat ],
			supportsLiveModels: false,
			category:           'custom',
			rateInfo:           'Varies by provider',
			allowBaseUrlOverride: true,
		);
	}

	/**
	 * Get instance-specific metadata.
	 */
	public function instanceMeta(): ProviderMeta {
		return new ProviderMeta(
			id:                 $this->providerId(),
			label:              $this->meta->customLabel,
			apiFormat:          $this->meta->apiFormat,
			tier:               CredentialTier::Free,
			baseUrl:            '',
			capabilities:       $this->meta->capabilities,
			supportsLiveModels: $this->meta->supportsLiveModels,
			category:           'custom',
			rateInfo:           'Custom provider',
			modelsUrl:          $this->meta->modelsUrl,
			allowBaseUrlOverride: true,
		);
	}

	public function buildRequest( GatewayRequest $req, Credential $cred ): array {
		if ( 'anthropic_messages' === $this->meta->apiFormat ) {
			return AnthropicMessagesFormat::buildRequest(
				$req,
				$cred->baseUrl ?: '',
				$this->authHeaders( $cred->apiKey )
			);
		}

		return OpenAICompatibleFormat::buildRequest(
			$req,
			$cred->baseUrl ?: '',
			$this->authHeaders( $cred->apiKey )
		);
	}

	public function parseResponse( array $http ): GatewayResponse {
		if ( 'anthropic_messages' === $this->meta->apiFormat ) {
			return AnthropicMessagesFormat::parseResponse( $http, $this->providerId() );
		}

		return OpenAICompatibleFormat::parseResponse( $http, $this->providerId() );
	}

	public function parseStreamChunk( string $line ): ?string {
		if ( 'anthropic_messages' === $this->meta->apiFormat ) {
			return AnthropicMessagesFormat::parseStreamChunk( $line );
		}

		return OpenAICompatibleFormat::parseStreamChunk( $line );
	}

	public function classifyError( int $status, array $body ): FailureCategory {
		if ( 'anthropic_messages' === $this->meta->apiFormat ) {
			return AnthropicMessagesFormat::classifyError( $status, $body );
		}

		return OpenAICompatibleFormat::classifyError( $status, $body );
	}

	public function buildModelsRequest( string $apiKey, ?string $baseUrl = null ): ?array {
		if ( ! $this->meta->supportsLiveModels || ! $this->meta->modelsUrl ) {
			return null;
		}

		if ( 'anthropic_messages' === $this->meta->apiFormat ) {
			return AnthropicMessagesFormat::buildModelsRequest(
				null,
				$this->meta->modelsUrl,
				$this->authHeaders( $apiKey )
			);
		}

		return OpenAICompatibleFormat::buildModelsRequest(
			null,
			$this->meta->modelsUrl,
			$this->authHeaders( $apiKey )
		);
	}

	public function parseModelsResponse( array $body ): array {
		if ( 'anthropic_messages' === $this->meta->apiFormat ) {
			return AnthropicMessagesFormat::parseModelsResponse( $body );
		}

		return OpenAICompatibleFormat::parseModelsResponse( $body );
	}

	/**
	 * @return array<string, string>
	 */
	private function authHeaders( string $apiKey ): array {
		$prefix = trim( $this->meta->authHeaderPrefix );
		$value  = '' === $prefix ? $apiKey : $prefix . ' ' . $apiKey;

		return [ $this->meta->authHeaderName => $value ];
	}

	private function providerId(): string {
		$slug = sanitize_title( $this->meta->customLabel );

		return 'custom:' . ( '' !== $slug ? $slug : 'custom-provider' );
	}
}
