<?php
/**
 * @package HDAT\Domain\Provider
 */

declare(strict_types=1);

namespace HDAT\Domain\Provider;

use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Domain\Routing\FailureCategory;

defined( 'ABSPATH' ) || exit;

interface ProviderCapsule {

	/**
	 * Static metadata. Cheap — no instantiation needed for discovery.
	 */
	public static function meta(): ProviderMeta;

	/**
	 * Build provider-specific HTTP payload.
	 *
	 * @return array{0: string, 1: array<string, string>, 2: array<string, mixed>} [url, headers, body]
	 */
	public function buildRequest( GatewayRequest $req, Credential $cred ): array;

	/**
	 * Parse non-streaming HTTP response.
	 *
	 * @param array{status: int, headers: array<string, string>, body: array<string, mixed>} $http
	 */
	public function parseResponse( array $http ): GatewayResponse;

	/**
	 * Parse one streaming SSE data line. Return delta content or null to skip.
	 */
	public function parseStreamChunk( string $line ): ?string;

	/**
	 * Classify HTTP error for circuit breaker / retry policy.
	 *
	 * @param array<string, mixed> $body
	 */
	public function classifyError( int $status, array $body ): FailureCategory;

	/**
	 * Build HTTP request for fetching the provider's model list.
	 *
	 * @return array{0: string, 1: array<string, string>}|null [url, headers] or null if not supported.
	 */
	public function buildModelsRequest( string $apiKey, ?string $baseUrl = null ): ?array;

	/**
	 * Parse the raw models API response into a normalized list.
	 *
	 * @param array<string, mixed> $body Decoded JSON body.
	 * @return array<int, array{id: string, name: string}>
	 */
	public function parseModelsResponse( array $body ): array;
}
