<?php
/**
 * HDAT gateway adapter.
 *
 * Reuses the InternalRequestContext pattern from PLL AI module
 * for zero-overhead internal REST dispatch without consumer tokens.
 *
 * @package HDAC
 */

namespace HDAC;

defined( 'ABSPATH' ) || exit;

use HDAC\Settings;
use HDAT\Auth\InternalRequestContext;

final class AiClient {

	private const ROUTE = '/hdat/v1/chat/completions';

	private const IMAGE_ROUTE = '/hdat/v1/images/generations';

	/**
	 * Check if HDAT chat completions route is available.
	 */
	public static function isAvailable(): bool {
		if ( ! function_exists( 'rest_get_server' ) ) {
			return false;
		}

		$routes = rest_get_server()->get_routes();

		return isset( $routes[ self::ROUTE ] );
	}

	/**
	 * Check if HDAT image generation route is available.
	 */
	public static function isImageAvailable(): bool {
		if ( ! function_exists( 'rest_get_server' ) ) {
			return false;
		}

		$routes = rest_get_server()->get_routes();

		return isset( $routes[ self::IMAGE_ROUTE ] );
	}

	/**
	 * Send an OpenAI-compatible chat-completions request via internal REST dispatch.
	 *
	 * @param array<string, mixed> $payload OpenAI-compatible payload (messages, model, etc.).
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	public function chat( array $payload ): array|\WP_Error {
		if ( ! self::isAvailable() ) {
			return new \WP_Error( 'hdac_hdat_unavailable', __( 'HDAT chat completions route is unavailable.', 'hd-ai-classic' ) );
		}

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $payload ) ?: '{}' );

		$token = Settings::consumerToken();

		if ( '' !== $token ) {
			$request->set_header( 'Authorization', 'Bearer ' . $token );
		}

		return $this->normalizeResponse( $this->dispatchInternal( $request ) );
	}

	/**
	 * Extract the first assistant message content from response.
	 *
	 * @param array<string, mixed> $response OpenAI-compatible response.
	 */
	public static function assistantContent( array $response ): string {
		$choice = $response['choices'][0] ?? null;
		if ( ! is_array( $choice ) ) {
			return '';
		}

		$message = $choice['message'] ?? [];

		return is_array( $message ) ? (string) ( $message['content'] ?? '' ) : '';
	}

	/**
	 * Send an OpenAI-compatible image-generation request.
	 *
	 * @param string $prompt Image prompt.
	 * @param string $size   Image size (default: '1024x1024').
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	public function generateImage( string $prompt, string $size = '1024x1024' ): array|\WP_Error {
		if ( ! self::isImageAvailable() ) {
			return new \WP_Error( 'hdac_hdat_image_unavailable', __( 'HDAT image generation route is unavailable.', 'hd-ai-classic' ) );
		}

		$request = new \WP_REST_Request( 'POST', self::IMAGE_ROUTE );
		$request->set_header( 'content-type', 'application/json' );

		$payload = [
			'prompt' => $prompt,
			'n'      => 1,
			'size'   => $size,
		];
		$request->set_body( wp_json_encode( $payload ) ?: '{}' );

		$token = Settings::consumerToken();

		if ( '' !== $token ) {
			$request->set_header( 'Authorization', 'Bearer ' . $token );
		}

		return $this->normalizeResponse( $this->dispatchInternal( $request ) );
	}

	/**
	 * Dispatch an in-process REST request to HDAT with the internal marker active.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed
	 */
	private function dispatchInternal( \WP_REST_Request $request ): mixed {
		if ( class_exists( InternalRequestContext::class ) ) {
			return InternalRequestContext::run(
				static fn() => rest_do_request( $request )
			);
		}

		return rest_do_request( $request );
	}

	/**
	 * Normalize transport response.
	 *
	 * @param mixed $response
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	private function normalizeResponse( mixed $response ): array|\WP_Error {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response instanceof \WP_REST_Response ) {
			$data = $response->get_data();
			if ( $response->get_status() >= 400 ) {
				$message = __( 'AI request failed.', 'hd-ai-classic' );
				if ( is_array( $data ) ) {
					$message = (string) ( $data['error']['message'] ?? $data['message'] ?? $message );
				}

				return new \WP_Error(
					is_array( $data ) ? (string) ( $data['error']['code'] ?? $data['code'] ?? 'hdac_rest_error' ) : 'hdac_rest_error',
					$message,
					[
						'status' => $response->get_status(),
						'data'   => is_array( $data ) ? $data : [],
					]
				);
			}

			return is_array( $data ) ? $data : [];
		}

		return is_array( $response )
			? $response
			: new \WP_Error( 'hdac_invalid_response', __( 'AI response must be an array.', 'hd-ai-classic' ) );
	}
}
