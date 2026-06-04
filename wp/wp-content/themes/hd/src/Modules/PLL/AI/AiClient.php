<?php
/**
 * HDAT chat-completions adapter for PLL AI translation.
 *
 * @package HD\Modules\PLL\AI
 */

namespace HD\Modules\PLL\AI;

use HD\Modules\PLL\PLLModule;

defined( 'ABSPATH' ) || exit;

final class AiClient {

	private const ROUTE = '/hdat/v1/chat/completions';

	/**
	 * Check route availability from the registered REST route table.
	 */
	public static function isAvailable(): bool {
		if ( ! function_exists( 'rest_get_server' ) ) {
			return false;
		}

		$routes = rest_get_server()->get_routes();

		return isset( $routes[ self::ROUTE ] );
	}

	/**
	 * Send an OpenAI-compatible chat-completions request.
	 *
	 * @param array<string, mixed> $payload OpenAI-compatible payload.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	public function chat( array $payload ): array|\WP_Error {
		$request = apply_filters( 'hd_pll_ai_client_request', $payload );
		if ( ! is_array( $request ) ) {
			return new \WP_Error( 'hd_pll_ai_invalid_request', __( 'AI request must be an array.', 'hd' ) );
		}

		$transport = apply_filters( 'hd_pll_ai_client_transport', 'rest', $request );

		if ( is_callable( $transport ) ) {
			return $this->normalizeResponse( $transport( $request ) );
		}

		return 'http' === $transport
			? $this->httpRequest( $request )
			: $this->restRequest( $request );
	}

	/**
	 * Extract the first assistant message content.
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
	 * @param array<string, mixed> $payload Request payload.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	private function restRequest( array $payload ): array|\WP_Error {
		if ( ! self::isAvailable() ) {
			return new \WP_Error( 'hd_pll_ai_hdat_unavailable', __( 'HDAT chat completions route is unavailable.', 'hd' ) );
		}

		$headers = $this->headers();
		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		foreach ( $headers as $name => $value ) {
			$request->set_header( $name, $value );
		}
		$request->set_body( wp_json_encode( $payload ) ?: '{}' );

		$dispatch = class_exists( \HDAT\Auth\InternalRequestContext::class )
			? static fn() => \HDAT\Auth\InternalRequestContext::run( static fn() => rest_do_request( $request ) )
			: static fn() => rest_do_request( $request );

		return $this->normalizeResponse( $dispatch() );
	}

	/**
	 * @param array<string, mixed> $payload Request payload.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	private function httpRequest( array $payload ): array|\WP_Error {
		$response = wp_remote_post(
			rest_url( ltrim( self::ROUTE, '/' ) ),
			[
				'timeout' => (int) apply_filters( 'hd_pll_ai_client_timeout', 45 ),
				'headers' => [
					'content-type' => 'application/json',
					...$this->headers(),
				],
				'body'    => wp_json_encode( $payload ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			return new \WP_Error( 'hd_pll_ai_http_error', $body['error']['message'] ?? __( 'AI request failed.', 'hd' ), [ 'status' => $code ] );
		}

		return is_array( $body ) ? $body : new \WP_Error( 'hd_pll_ai_invalid_response', __( 'AI response is not valid JSON.', 'hd' ) );
	}

	/**
	 * @return array<string, string>
	 */
	private function headers(): array {
		$settings = PLLModule::getCachedOptions();
		$token    = (string) apply_filters( 'hd_pll_ai_client_bearer_token', $settings['ai_consumer_token'] ?? '' );

		return '' !== $token ? [ 'authorization' => 'Bearer ' . $token ] : [];
	}

	/**
	 * @param mixed $response Raw transport response.
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
				return new \WP_Error( 'hd_pll_ai_rest_error', $data['error']['message'] ?? __( 'AI request failed.', 'hd' ), [ 'status' => $response->get_status() ] );
			}

			return is_array( $data ) ? $data : [];
		}

		return is_array( $response )
			? $response
			: new \WP_Error( 'hd_pll_ai_invalid_response', __( 'AI response must be an array.', 'hd' ) );
	}
}
