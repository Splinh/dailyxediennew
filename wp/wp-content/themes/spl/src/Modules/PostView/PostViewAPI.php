<?php
/**
 * PostView REST API Endpoint.
 *
 * Handles the `track_views` REST route for recording and retrieving post view counts.
 * This endpoint is module-owned: registered by PostViewModule during boot.
 *
 * @package SPL\Modules\PostView
 * @author  HD
 */

namespace SPL\Modules\PostView;

use SPL\API\AbstractAPI;
use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class PostViewAPI extends AbstractAPI {

	public function __construct() {
		$this->namespace = REST_NAMESPACE;
		$this->rest_base = 'post-view';
	}

	/**
	 * Register custom REST routes.
	 *
	 * @return void
	 */
	protected function registerRoutes(): void {
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/track",
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => $this->trackViewsCallback( ... ),
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => static fn( $v ): bool => $v > 0,
					],
				],
			]
		);
	}

	/**
	 * Track post views endpoint callback.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function trackViewsCallback( \WP_REST_Request $request ): \WP_Error|\WP_REST_Response {
		$nonceCheck = $this->verifyNonce( $request );
		if ( $nonceCheck instanceof \WP_REST_Response ) {
			return $nonceCheck;
		}

		// Rate limit: 60 requests per minute per IP.
		$rateLimitCheck = $this->rateLimit( 'track_views', 60 );
		if ( $rateLimitCheck instanceof \WP_REST_Response ) {
			return $rateLimitCheck;
		}

		$id = absint( $request['id'] ?? 0 );
		if ( ! $this->isTrackablePost( $id ) ) {
			return $this->sendResponse(
				[
					'success' => false,
					'message' => 'Invalid post ID.',
				],
				400
			);
		}

		$ip            = Helper::ipAddress();
		$viewTimestamp = time();

		try {
			$writeResult = PostViewModule::recordView( $id, $ip );
			if ( is_wp_error( $writeResult ) ) {
				$errorData = $writeResult->get_error_data();
				$status    = is_array( $errorData ) && isset( $errorData['status'] )
					? (int) $errorData['status']
					: 500;

				return $this->sendResponse(
					[
						'success' => false,
						'message' => 'Unable to record view.',
					],
					$status
				);
			}

			$totalViews = PostViewModule::getTotalViews( $id );
		} catch ( \Throwable $e ) {
			$message = WP_DEBUG
				? 'Failed to record view: ' . $e->getMessage()
				: 'An error occurred while recording view.';

			return $this->sendResponse(
				[
					'success' => false,
					'message' => $message,
				],
				500
			);
		}

		return $this->sendResponse(
			[
				'success' => true,
				'post_id' => $id,
				'views'   => $totalViews,
				'time'    => $viewTimestamp,
				'date'    => $this->formatViewDate( $viewTimestamp ),
				'message' => 'View recorded successfully.',
			]
		);
	}

	private function isTrackablePost( int $id ): bool {
		return $id > 0 && 'publish' === get_post_status( $id );
	}

	private function formatViewDate( int $timestamp ): string {
		return wp_date( DATE_ATOM, $timestamp );
	}
}
