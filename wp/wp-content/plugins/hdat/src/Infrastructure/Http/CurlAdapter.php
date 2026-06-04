<?php
/**
 * @package HDAT\Infrastructure\Http
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Http;

use HDAT\Domain\Routing\FailureCategory;
use HDAT\Kernel\Settings;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
// phpcs:disable Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition

/**
 * Thin cURL wrapper for provider HTTP.
 *
 * Direct cURL — not wp_remote_post — because:
 *   - WP HTTP buffers the entire response, which breaks streaming.
 *   - We need raw response headers (e.g. OpenRouter x-ratelimit-*) without
 *     WP's normalisation.
 *
 * Two entry points:
 *   - post()       non-streaming: returns { status, headers, body }
 *   - streamPost() streaming: invokes $onChunk(string $data) per SSE data line
 *
 * Errors throw ProviderException with a FailureCategory the router can act on.
 */
final class CurlAdapter {

	public function post( string $url, array $headers, array $body ): array {
		if ( ! function_exists( 'curl_init' ) ) {
			throw new \RuntimeException( 'cURL extension required.' );
		}

		$ch = curl_init( $url );
		if ( false === $ch ) {
			throw new ProviderException( 0, FailureCategory::Unknown, 'curl_init failed' );
		}

		curl_setopt_array(
			$ch,
			[
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => wp_json_encode( $body ),
				CURLOPT_HTTPHEADER     => $this->formatHeaders( $headers ),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => true,
				CURLOPT_TIMEOUT        => (int) Settings::get( 'request_timeout', 30 ),
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS      => 3,
			]
		);

		$raw = curl_exec( $ch );
		if ( false === $raw ) {
			$err      = curl_error( $ch );
			$errno    = curl_errno( $ch );
			$category = $this->categorizeCurlError( $errno );
			curl_close( $ch );

			throw new ProviderException( 0, $category, "cURL error #{$errno}: {$err}" );
		}

		$status     = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$headerSize = (int) curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		curl_close( $ch );

		$rawHeaders = substr( $raw, 0, $headerSize );
		$rawBody    = (string) substr( $raw, $headerSize );

		$decoded = $rawBody === '' ? [] : json_decode( $rawBody, true );

		return [
			'status'  => $status,
			'headers' => $this->parseHeaders( $rawHeaders ),
			'body'    => is_array( $decoded ) ? $decoded : [ 'raw' => $rawBody ],
		];
	}

	/**
	 * Simple GET request.
	 *
	 * @param array<string, string> $headers
	 * @return array{status: int, headers: array<string, string>, body: array<string, mixed>}
	 */
	public function get( string $url, array $headers = [] ): array {
		if ( ! function_exists( 'curl_init' ) ) {
			throw new \RuntimeException( 'cURL extension required.' );
		}

		$ch = curl_init( $url );
		if ( false === $ch ) {
			throw new ProviderException( 0, FailureCategory::Unknown, 'curl_init failed' );
		}

		curl_setopt_array(
			$ch,
			[
				CURLOPT_HTTPGET        => true,
				CURLOPT_HTTPHEADER     => $this->formatHeaders( $headers ),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => true,
				CURLOPT_TIMEOUT        => 15,
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS      => 3,
			]
		);

		$raw = curl_exec( $ch );
		if ( false === $raw ) {
			$err      = curl_error( $ch );
			$errno    = curl_errno( $ch );
			$category = $this->categorizeCurlError( $errno );
			curl_close( $ch );

			throw new ProviderException( 0, $category, "cURL error #{$errno}: {$err}" );
		}

		$status     = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$headerSize = (int) curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		curl_close( $ch );

		$rawHeaders = substr( $raw, 0, $headerSize );
		$rawBody    = (string) substr( $raw, $headerSize );

		$decoded = $rawBody === '' ? [] : json_decode( $rawBody, true );

		return [
			'status'  => $status,
			'headers' => $this->parseHeaders( $rawHeaders ),
			'body'    => is_array( $decoded ) ? $decoded : [ 'raw' => $rawBody ],
		];
	}

	/**
	 * Stream POST. $onChunk is called with each SSE `data:` payload.
	 *
	 * @param callable(string): void $onChunk
	 */
	public function streamPost( string $url, array $headers, array $body, callable $onChunk ): void {
		if ( ! function_exists( 'curl_init' ) ) {
			throw new \RuntimeException( 'cURL extension required.' );
		}

		$ch = curl_init( $url );
		if ( false === $ch ) {
			throw new ProviderException( 0, FailureCategory::Unknown, 'curl_init failed' );
		}

		$buffer       = '';
		$lastStatus   = 0;
		$writeFailure = null;
		$errorBody    = '';
		$headersDone  = false;

		curl_setopt_array(
			$ch,
			[
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => wp_json_encode( $body ),
				CURLOPT_HTTPHEADER     => $this->formatHeaders( $headers ),
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_TIMEOUT        => 0, // streams can run long; rely on provider timeout
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS      => 3,
				CURLOPT_HEADERFUNCTION => function ( $ch, string $header ) use ( &$lastStatus, &$headersDone ): int {
					if ( ! $headersDone && str_starts_with( $header, 'HTTP/' ) ) {
						$lastStatus = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
					}
					if ( "\r\n" === $header ) {
						$headersDone = true;
					}

					return strlen( $header );
				},
				CURLOPT_WRITEFUNCTION  => function ( $ch, string $chunk ) use ( &$buffer, &$lastStatus, &$errorBody, $onChunk, &$writeFailure ): int {
					// If HTTP error, collect body for error extraction instead of parsing SSE.
					if ( 0 === $lastStatus ) {
						$lastStatus = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
					}
					if ( $lastStatus >= 400 ) {
						$errorBody .= $chunk;

						return strlen( $chunk );
					}

					$buffer .= $chunk;
					while ( false !== ( $pos = strpos( $buffer, "\n" ) ) ) {
						$line   = substr( $buffer, 0, $pos );
						$buffer = (string) substr( $buffer, $pos + 1 );

						$line = rtrim( $line, "\r" );
						if ( '' === $line || ':' === $line[0] ) {
							continue; // SSE comment / heartbeat
						}

						if ( str_starts_with( $line, 'data: ' ) ) {
							try {
								$onChunk( substr( $line, 6 ) );
							} catch ( \Throwable $e ) {
								$writeFailure = $e;
								return -1;
							}
						}
					}

					return strlen( $chunk );
				},
			]
		);

		$ok = curl_exec( $ch );

		if ( null !== $writeFailure ) {
			curl_close( $ch );
			throw new ProviderException( 0, FailureCategory::Unknown, 'Stream handler threw: ' . $writeFailure->getMessage(), $writeFailure );
		}

		if ( 0 === $lastStatus ) {
			$lastStatus = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		}
		$errno  = curl_errno( $ch );
		$errMsg = curl_error( $ch );
		curl_close( $ch );

		if ( false === $ok && 0 !== $errno ) {
			throw new ProviderException( $lastStatus, $this->categorizeCurlError( $errno ), "cURL stream error #{$errno}: {$errMsg}" );
		}

		if ( $lastStatus >= 400 ) {
			$detail = "Stream HTTP {$lastStatus}";
			if ( '' !== $errorBody ) {
				$decoded = json_decode( $errorBody, true );
				$msg     = $decoded['error']['message']
					?? $decoded['error']
					?? $decoded['message']
					?? null;

				if ( is_string( $msg ) && '' !== $msg ) {
					$detail .= ': ' . substr( $msg, 0, 200 );
				}
			}
			throw new ProviderException( $lastStatus, $this->categorizeHttpStatus( $lastStatus ), $detail );
		}
	}

	/**
	 * @param array<string, string> $headers
	 * @return string[]
	 */
	private function formatHeaders( array $headers ): array {
		$out = [];
		foreach ( $headers as $k => $v ) {
			$out[] = $k . ': ' . $v;
		}

		return $out;
	}

	/**
	 * @return array<string, string>
	 */
	private function parseHeaders( string $raw ): array {
		$out = [];
		foreach ( preg_split( '/\r?\n/', $raw ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || ! str_contains( $line, ':' ) ) {
				continue;
			}
			[ $name, $value ]                   = explode( ':', $line, 2 );
			$out[ strtolower( trim( $name ) ) ] = trim( $value );
		}

		return $out;
	}

	private function categorizeCurlError( int $errno ): FailureCategory {
		return match ( $errno ) {
			CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED => FailureCategory::Timeout,
			CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST   => FailureCategory::Server,
			default                                             => FailureCategory::Unknown,
		};
	}

	private function categorizeHttpStatus( int $status ): FailureCategory {
		return match ( true ) {
			429 === $status                          => FailureCategory::RateLimit,
			in_array( $status, [ 401, 403 ], true )  => FailureCategory::Auth,
			408 === $status                          => FailureCategory::Timeout,
			$status >= 500                           => FailureCategory::Server,
			default                                  => FailureCategory::Unknown,
		};
	}
}
