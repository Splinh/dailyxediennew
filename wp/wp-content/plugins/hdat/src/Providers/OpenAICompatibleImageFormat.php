<?php
/**
 * @package HDAT\Providers
 */

declare(strict_types=1);

namespace HDAT\Providers;

use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Provider\ProviderMeta;

defined( 'ABSPATH' ) || exit;

final class OpenAICompatibleImageFormat {

	/**
	 * @param array<string, mixed> $body
	 * @return array{0: string, 1: array<string, string>, 2: array<string, mixed>}
	 */
	public static function buildGenerationRequest( Credential $cred, ProviderMeta $meta, array $body, string $prompt ): array {
		return [
			rtrim( $cred->baseUrl ?: $meta->baseUrl, '/' ) . '/images/generations',
			[
				'Authorization' => 'Bearer ' . $cred->apiKey,
				'Content-Type'  => 'application/json',
			],
			self::forwardPayload( $body, $prompt ),
		];
	}

	/**
	 * @param array{status: int, headers?: array<string, string>, body?: mixed} $http
	 */
	public static function errorMessage( array $http ): string {
		$body = $http['body'] ?? null;
		if ( is_array( $body ) ) {
			$message = $body['error']['message'] ?? $body['message'] ?? null;
			if ( is_scalar( $message ) ) {
				return (string) $message;
			}
		}

		return 'HTTP ' . (int) ( $http['status'] ?? 0 );
	}

	/**
	 * Whitelist fields forwarded to the provider's /images/generations endpoint.
	 *
	 * @param array<string, mixed> $body
	 * @return array<string, mixed>
	 */
	private static function forwardPayload( array $body, string $prompt ): array {
		$out = [ 'prompt' => $prompt ];

		foreach ( [ 'model', 'n', 'size', 'quality', 'response_format', 'style', 'user' ] as $key ) {
			if ( array_key_exists( $key, $body ) ) {
				$out[ $key ] = $body[ $key ];
			}
		}

		return $out;
	}
}
