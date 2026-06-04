<?php
/**
 * @package HDAT\Infrastructure\Http
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Http;

defined( 'ABSPATH' ) || exit;

/**
 * Server-Sent Events emitter.
 *
 * The constructor takes over the response: kills WP buffering, sets headers,
 * and disables Nginx proxy buffering (`X-Accel-Buffering: no`). After that the
 * caller writes deltas via send() and closes with sendDone(). The REST handler
 * must `exit` after sendDone() to keep WP from appending its own JSON.
 *
 * Event format follows the OpenAI streaming wire format so existing client
 * SDKs (openai-node, langchain) just work.
 */
final class SseEmitter {

	private string $id;
	private int $streamedBytes = 0;

	public function __construct() {
		$this->id = 'chatcmpl-' . uniqid();

		// Drop any output WP/PHP have buffered before we hijack the stream.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		if ( ! headers_sent() ) {
			header( 'Content-Type: text/event-stream; charset=utf-8' );
			header( 'Cache-Control: no-cache, no-store, must-revalidate' );
			header( 'X-Accel-Buffering: no' );
			header( 'Connection: keep-alive' );
		}

		// Nginx + FastCGI can buffer until 4 KiB; pad once so the first
		// chunk reaches the client immediately.
		echo str_repeat( ' ', 2048 ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		@flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	public function send( string $delta ): void {
		$this->streamedBytes += strlen( $delta );
		$this->emit(
			[
				'id'      => $this->id,
				'object'  => 'chat.completion.chunk',
				'choices' => [
					[
						'index' => 0,
						'delta' => [ 'content' => $delta ],
					],
				],
			]
		);
	}

	public function getStreamedBytes(): int {
		return $this->streamedBytes;
	}

	public function sendDone(): void {
		echo "data: [DONE]\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		@flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	public function sendError( string $message ): void {
		$this->emit(
			[
				'id'     => $this->id,
				'object' => 'chat.completion.chunk',
				'error'  => [ 'message' => $message ],
			]
		);
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function emit( array $payload ): void {
		echo 'data: ' . wp_json_encode( $payload ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		@flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}
