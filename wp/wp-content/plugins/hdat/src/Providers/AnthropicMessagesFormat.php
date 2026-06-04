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

final class AnthropicMessagesFormat {

	/**
	 * @param array<string, string> $headers
	 * @return array{0: string, 1: array<string, string>, 2: array<string, mixed>}
	 */
	public static function buildRequest( GatewayRequest $req, string $baseUrl, array $headers ): array {
		$base = self::apiRoot( $baseUrl );
		$url  = $base . '/v1/messages';

		$system   = '';
		$messages = [];

		foreach ( $req->messages as $message ) {
			if ( ( $message['role'] ?? '' ) === 'system' ) {
				$system .= ( '' === $system ? '' : "\n" ) . ( $message['content'] ?? '' );
				continue;
			}
			$messages[] = $message;
		}

		$body = [
			'model'       => $req->model,
			'messages'    => $messages,
			'max_tokens'  => $req->maxTokens,
			'temperature' => $req->temperature,
		];

		if ( '' !== $system ) {
			$body['system'] = $system;
		}

		if ( $req->stream ) {
			$body['stream'] = true;
		}

		foreach ( $req->extra as $key => $value ) {
			$body[ $key ] = $value;
		}

		return [
			$url,
			$headers + [
				'anthropic-version' => '2023-06-01',
				'Content-Type'      => 'application/json',
			],
			array_filter( $body, static fn( $value ) => null !== $value ),
		];
	}

	public static function parseResponse( array $http, string $providerId ): GatewayResponse {
		$data    = $http['body'] ?? [];
		$content = '';
		foreach ( (array) ( $data['content'] ?? [] ) as $block ) {
			if ( ( $block['type'] ?? '' ) === 'text' ) {
				$content .= $block['text'] ?? '';
			}
		}

		$usage = $data['usage'] ?? [];
		$pt    = (int) ( $usage['input_tokens'] ?? 0 );
		$ct    = (int) ( $usage['output_tokens'] ?? 0 );

		return new GatewayResponse(
			content:      $content,
			model:        $data['model'] ?? $providerId,
			provider:     $providerId,
			usage:        new GatewayUsage( $pt, $ct, $pt + $ct ),
			finishReason: $data['stop_reason'] ?? null,
		);
	}

	public static function parseStreamChunk( string $line ): ?string {
		$line = trim( $line );
		if ( '' === $line ) {
			return null;
		}

		$data = json_decode( $line, true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		if ( ( $data['type'] ?? '' ) === 'content_block_delta' ) {
			return $data['delta']['text'] ?? null;
		}

		return null;
	}

	public static function classifyError( int $status, array $body ): FailureCategory {
		$type = $body['error']['type'] ?? '';

		return match ( true ) {
			'overloaded_error' === $type           => FailureCategory::RateLimit,
			429 === $status                        => FailureCategory::RateLimit,
			in_array( $status, [ 401, 403 ], true ) => FailureCategory::Auth,
			408 === $status                        => FailureCategory::Timeout,
			$status >= 500                         => FailureCategory::Server,
			default                                => FailureCategory::Unknown,
		};
	}

	/**
	 * @param array<string, string> $headers
	 * @return array{0: string, 1: array<string, string>}|null
	 */
	public static function buildModelsRequest( ?string $baseUrl, ?string $modelsUrl, array $headers ): ?array {
		$url = $baseUrl
			? self::apiRoot( $baseUrl ) . '/v1/models'
			: $modelsUrl;

		if ( ! $url ) {
			return null;
		}

		return [ $url, $headers + [ 'anthropic-version' => '2023-06-01' ] ];
	}

	public static function parseModelsResponse( array $body ): array {
		$raw = $body['data'] ?? [];

		return array_values(
			array_map(
				static fn( array $m ) => [
					'id'             => (string) ( $m['id'] ?? '' ),
					'name'           => (string) ( $m['display_name'] ?? $m['id'] ?? '' ),
					'context_window' => (int) ( $m['context_window'] ?? 0 ),
				],
				is_array( $raw ) ? $raw : []
			)
		);
	}

	private static function apiRoot( string $baseUrl ): string {
		$base = rtrim( $baseUrl, '/' );

		return (string) preg_replace( '#/v1$#', '', $base );
	}
}
