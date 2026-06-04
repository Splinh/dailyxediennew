<?php
/**
 * @package HDAT\Providers\Anthropic
 */

declare(strict_types=1);

namespace HDAT\Providers\Anthropic;

use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderCapsule;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Domain\Routing\FailureCategory;
use HDAT\Providers\AnthropicMessagesFormat;

defined( 'ABSPATH' ) || exit;

/**
 * Anthropic uses /v1/messages, not OpenAI-compat /chat/completions.
 *
 * Notable differences:
 *   - System prompt extracted out of `messages` into top-level `system`.
 *   - Stream events come as named SSE events (`event: content_block_delta`).
 *   - Auth via `x-api-key` header, not Bearer.
 */
final class AnthropicProvider implements ProviderCapsule {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'anthropic',
			label:              'Anthropic',
			apiFormat:          'anthropic_messages',
			tier:               CredentialTier::Paid,
			baseUrl:            'https://api.anthropic.com',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'official',
			rateInfo:           'Pay-per-use',
			regUrl:             'https://console.anthropic.com/settings/keys',
			modelsUrl:          'https://api.anthropic.com/v1/models',
		);
	}

	public function buildRequest( GatewayRequest $req, Credential $cred ): array {
		return AnthropicMessagesFormat::buildRequest(
			$req,
			$cred->baseUrl ?: self::meta()->baseUrl,
			[ 'x-api-key' => $cred->apiKey ]
		);
	}

	public function parseResponse( array $http ): GatewayResponse {
		return AnthropicMessagesFormat::parseResponse( $http, self::meta()->id );
	}

	public function parseStreamChunk( string $line ): ?string {
		return AnthropicMessagesFormat::parseStreamChunk( $line );
	}

	public function classifyError( int $status, array $body ): FailureCategory {
		return AnthropicMessagesFormat::classifyError( $status, $body );
	}

	public function buildModelsRequest( string $apiKey, ?string $baseUrl = null ): ?array {
		$meta = self::meta();

		return AnthropicMessagesFormat::buildModelsRequest(
			$baseUrl,
			$meta->modelsUrl,
			[ 'x-api-key' => $apiKey ]
		);
	}

	public function parseModelsResponse( array $body ): array {
		return AnthropicMessagesFormat::parseModelsResponse( $body );
	}
}
