<?php
/**
 * Abstract base class for all custom REST API controllers.
 *
 * Provides shared methods and constants for defining REST API namespaces,
 * generating endpoint URLs, and returning standardized REST responses.
 *
 * @author HD
 */

namespace HD\API;

use HD\Core\Helper;
use HD\Core\RateLimitStorage;

defined( 'ABSPATH' ) || exit;

abstract class AbstractAPI extends \WP_REST_Controller {
	/**
	 * Reserved for intentionally public endpoints.
	 *
	 * Leave false by default. Controllers that set this to true must document
	 * their callback-level threat model, rate limiting, and validation because
	 * verifyNonce() will allow anonymous requests through.
	 */
	public const BYPASS_NONCE = false;

	/** ---------------------------------------- */

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->registerRoutes();
	}

	/** ---------------------------------------- */

	abstract protected function registerRoutes(): void;

	/** ---------------------------------------- */

	/**
	 * Generate full REST API URL for a given route.
	 *
	 * @param string $route
	 *
	 * @return string
	 */
	public function restApiUrl( string $route = '' ): string {
		return esc_url_raw( rest_url( REST_NAMESPACE . '/' . ltrim( $route, '/' ) ) );
	}

	/** ---------------------------------------- */

	/**
	 * Verify nonce from request header.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response|null
	 */
	protected function verifyNonce( \WP_REST_Request $request ): ?\WP_REST_Response {
		if ( static::BYPASS_NONCE ) {
			return null;
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );

		return ( $nonce && wp_verify_nonce( $nonce, 'wp_rest' ) )
			? null
			: $this->sendResponse(
				[
					'success' => false,
					'message' => 'Invalid CSRF token.',
				],
				403
			);
	}

	/** ---------------------------------------- */

	/**
	 * Send standardized REST response.
	 *
	 * @param array $result
	 * @param int $status
	 * @param array $data
	 *
	 * @return \WP_REST_Response
	 */
	public function sendResponse( array $result = [], int $status = 200, array $data = [] ): \WP_REST_Response {

		$result = [
			'success'   => $status < 400,
			'status'    => $status,
			'errorCode' => 0,
			...$result,
		];

		if ( $data ) {
			$result['data'] = $data;
		}

		$response = rest_ensure_response( $result );
		$response->set_status( $status );

		return $response;
	}

	/** ---------------------------------------- */

	/**
	 * Rate limiter using hybrid RateLimitStorage.
	 *
	 * Uses Redis/Memcached transients when available, falls back to
	 * a lightweight custom MySQL table to avoid wp_options bloat.
	 *
	 * Returns null if within limit, or a 429 WP_REST_Response if exceeded.
	 * Same pattern as verifyNonce(): null = OK, response = blocked.
	 *
	 * @param string $keyPrefix Action identifier (e.g., 'contact', 'search').
	 * @param int    $limit     Max requests allowed in window.
	 * @param int    $window    Time window in seconds.
	 *
	 * @return \WP_REST_Response|null Null if allowed, 429 response if rate limited.
	 */
	protected function rateLimit( string $keyPrefix, int $limit = 30, int $window = 60 ): ?\WP_REST_Response {
		$ip    = Helper::ipAddress();
		$count = RateLimitStorage::increment( $ip, 'api_' . $keyPrefix, $window );

		return $count <= $limit
			? null
			: $this->sendResponse(
				[
					'success' => false,
					'message' => __( 'Too many requests. Please try again later.', 'hd' ),
				],
				429
			);
	}
}
