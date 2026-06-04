<?php
/**
 * @package HDAT\Providers
 */

declare(strict_types=1);

namespace HDAT\Providers;

use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Domain\Gateway\GatewayUsage;
use HDAT\Domain\Routing\FailureCategory;

defined( 'ABSPATH' ) || exit;

final class OpenAICompatibleFormat {

	/**
	 * @param array<string, string> $headers
	 * @return array{0: string, 1: array<string, string>, 2: array<string, mixed>}
	 */
	public static function buildRequest( GatewayRequest $req, string $baseUrl, array $headers ): array {
		$base = rtrim( $baseUrl, '/' );
		$url  = $base . '/chat/completions';

		return [ $url, $headers + [ 'Content-Type' => 'application/json' ], self::body( $req ) ];
	}

	public static function parseResponse( array $http, string $providerId ): GatewayResponse {
		$data    = $http['body'] ?? [];
		$choice  = $data['choices'][0] ?? [];
		$usage   = $data['usage'] ?? [];
		$content = $choice['message']['content'] ?? '';

		return new GatewayResponse(
			content:      is_string( $content ) ? $content : (string) wp_json_encode( $content ),
			model:        $data['model'] ?? $providerId,
			provider:     $providerId,
			usage:        new GatewayUsage(
				promptTokens:     (int) ( $usage['prompt_tokens'] ?? 0 ),
				completionTokens: (int) ( $usage['completion_tokens'] ?? 0 ),
				totalTokens:      (int) ( $usage['total_tokens'] ?? 0 ),
			),
			finishReason: $choice['finish_reason'] ?? null,
		);
	}

	public static function parseStreamChunk( string $line ): ?string {
		$line = trim( $line );

		if ( '' === $line || '[DONE]' === $line ) {
			return null;
		}

		$data = json_decode( $line, true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		return $data['choices'][0]['delta']['content'] ?? null;
	}

	public static function classifyError( int $status, array $body ): FailureCategory {
		unset( $body );

		return match ( true ) {
			429 === $status                       => FailureCategory::RateLimit,
			in_array( $status, [ 401, 403 ], true ) => FailureCategory::Auth,
			408 === $status                       => FailureCategory::Timeout,
			$status >= 500                        => FailureCategory::Server,
			default                               => FailureCategory::Unknown,
		};
	}

	/**
	 * @param array<string, string> $headers
	 * @return array{0: string, 1: array<string, string>}|null
	 */
	public static function buildModelsRequest( ?string $baseUrl, ?string $modelsUrl, array $headers ): ?array {
		$url = $baseUrl
			? rtrim( $baseUrl, '/' ) . '/models'
			: $modelsUrl;

		if ( ! $url ) {
			return null;
		}

		return [ $url, $headers ];
	}

	public static function parseModelsResponse( array $body ): array {
		$raw = $body['data'] ?? [];

		return array_values(
			array_map(
				static fn( array $m ) => [
					'id'             => (string) ( $m['id'] ?? '' ),
					'name'           => (string) ( $m['display_name'] ?? $m['id'] ?? '' ),
					'context_window' => (int) ( $m['context_length'] ?? 0 ),
				],
				is_array( $raw ) ? $raw : []
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function body( GatewayRequest $req ): array {
		$body = [
			'model'       => $req->model,
			'messages'    => $req->messages,
			'temperature' => $req->temperature,
			'max_tokens'  => $req->maxTokens,
		];

		if ( $req->stream ) {
			$body['stream'] = true;
		}

		foreach ( $req->extra as $key => $value ) {
			$body[ $key ] = $value;
		}

		return array_filter( $body, static fn( $value ) => null !== $value );
	}
}
