<?php
/**
 * REST API for plugin settings.
 *
 * Routes: GET/POST hd-ai-classic/v1/settings
 * Auth: WordPress nonce (logged-in admin with manage_options capability).
 *
 * @package HDAC\API
 */

namespace HDAC\API;

defined( 'ABSPATH' ) || exit;

use HDAC\Settings;
use HDAT\Auth\InternalRequestContext;

final class SettingsAPI {

	private const NAMESPACE = 'hd-ai-classic/v1';

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'getSettings' ],
					'permission_callback' => [ $this, 'permission' ],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'updateSettings' ],
					'permission_callback' => [ $this, 'permission' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings/test-connection',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'testConnection' ],
					'permission_callback' => [ $this, 'permission' ],
				],
			]
		);
	}

	/**
	 * Permission check: must be admin with manage_options capability.
	 */
	public function permission(): bool|\WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'hdac_forbidden',
				__( 'You do not have permission to manage settings.', 'hd-ai-classic' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get settings (masking the consumer token).
	 */
	public function getSettings(): \WP_REST_Response {
		$settings = Settings::all();

		// Mask consumer token if it exists.
		if ( ! empty( $settings['consumer_token'] ) ) {
			$settings['consumer_token'] = '****************';
		}

		return new \WP_REST_Response(
			[
				'success'  => true,
				'settings' => $settings,
			]
		);
	}

	/**
	 * Update settings.
	 */
	public function updateSettings( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = [];
		}

		Settings::save( $params );

		return new \WP_REST_Response(
			[
				'success'  => true,
				'settings' => $this->getSettings()->get_data()['settings'] ?? [],
			]
		);
	}

	/**
	 * Test connection to HDAT using token.
	 */
	public function testConnection( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();
		$token  = isset( $params['consumer_token'] ) ? sanitize_text_field( $params['consumer_token'] ) : '';

		if ( Settings::isMasked( $token ) || '' === $token ) {
			$token = Settings::consumerToken();
		}

		if ( '' === $token ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Consumer token is empty.', 'hd-ai-classic' ),
				],
				400
			);
		}

		$hdatRequest = new \WP_REST_Request( 'GET', '/hdat/v1/models' );
		$hdatRequest->set_header( 'Authorization', 'Bearer ' . $token );

		$response = class_exists( InternalRequestContext::class )
			? InternalRequestContext::run( static fn() => rest_do_request( $hdatRequest ) )
			: rest_do_request( $hdatRequest );

		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => $response->get_error_message(),
				],
				500
			);
		}

		if ( $response->is_error() || $response->get_status() >= 400 ) {
			$data = $response->get_data();
			$msg  = $data['error']['message'] ?? $data['message'] ?? __( 'Connection failed.', 'hd-ai-classic' );
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => $msg,
				],
				$response->get_status()
			);
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Connection successful!', 'hd-ai-classic' ),
			]
		);
	}
}
