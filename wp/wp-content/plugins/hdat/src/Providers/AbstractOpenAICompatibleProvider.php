<?php
/**
 * @package HDAT\Providers
 */

declare(strict_types=1);

namespace HDAT\Providers;

use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Domain\Provider\ProviderCapsule;
use HDAT\Domain\Routing\FailureCategory;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for the 19 OpenAI-compatible providers.
 *
 * Subclass override is usually `meta()` only. Providers needing extra
 * headers (e.g. OpenRouter site attribution) override `headers()`.
 *
 * The `$extra` array on GatewayRequest is merged into the body so callers
 * can pass `tools`, `tool_choice`, `response_format`, `seed`, etc. Providers
 * that don't recognize a field will simply return an error — that's the
 * caller's responsibility, not ours.
 */
abstract class AbstractOpenAICompatibleProvider implements ProviderCapsule {

	public function buildRequest( GatewayRequest $req, Credential $cred ): array {
		$base = rtrim( $cred->baseUrl ?: static::meta()->baseUrl, '/' );

		return OpenAICompatibleFormat::buildRequest( $req, $base, $this->headers( $cred ) );
	}

	public function parseResponse( array $http ): GatewayResponse {
		return OpenAICompatibleFormat::parseResponse( $http, static::meta()->id );
	}

	public function parseStreamChunk( string $line ): ?string {
		return OpenAICompatibleFormat::parseStreamChunk( $line );
	}

	public function classifyError( int $status, array $body ): FailureCategory {
		return OpenAICompatibleFormat::classifyError( $status, $body );
	}

	/**
	 * @return array<string, string>
	 */
	protected function headers( Credential $cred ): array {
		return [
			'Authorization' => 'Bearer ' . $cred->apiKey,
			'Content-Type'  => 'application/json',
		];
	}

	public function buildModelsRequest( string $apiKey, ?string $baseUrl = null ): ?array {
		$meta = static::meta();

		return OpenAICompatibleFormat::buildModelsRequest(
			$baseUrl,
			$meta->modelsUrl,
			[ 'Authorization' => 'Bearer ' . $apiKey ]
		);
	}

	public function parseModelsResponse( array $body ): array {
		return OpenAICompatibleFormat::parseModelsResponse( $body );
	}
}
