<?php
/**
 * XML-RPC disabling functionality.
 *
 * Disables WordPress XML-RPC and related features for security.
 *
 * @author HD
 */

namespace HDAddons\Modules\Security;

\defined( 'ABSPATH' ) || exit;

final class Xmlrpc {

	// --------------------------------------------------

	/**
	 * Disable XML-RPC functionality.
	 *
	 * @return void
	 */
	public function disable(): void {
		// Disable XMLRPC authentication and related functions
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'pre_update_option_enable_xmlrpc', '__return_false' );
		add_filter( 'pre_option_enable_xmlrpc', '__return_zero' );

		// Unset XML-RPC headers
		add_filter(
			'wp_headers',
			static function ( array $headers ): array {
				unset( $headers['X-Pingback'] );

				return $headers;
			}
		);

		// Unset XMLRPC methods for ping-backs
		add_filter(
			'xmlrpc_methods',
			static function ( array $methods ): array {
				unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

				return $methods;
			}
		);

		// Block direct access to xmlrpc.php
		add_action(
			'init',
			static function () {
				$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

				if ( str_contains( $request_uri, 'xmlrpc.php' ) ) {
					status_header( 403 );
					exit;
				}
			}
		);
	}
}
