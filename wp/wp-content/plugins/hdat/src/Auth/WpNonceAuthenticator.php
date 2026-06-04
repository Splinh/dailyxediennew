<?php
/**
 * @package HDAT\Auth
 */

declare(strict_types=1);

namespace HDAT\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * Permission callback for admin REST endpoints.
 *
 * Checks `manage_options` capability. Nonce verification is handled
 * automatically by WP REST cookie authentication when the client sends
 * the `X-WP-Nonce` header (or `_wpnonce` param).
 */
final class WpNonceAuthenticator {

	public function check(): bool|\WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'hdat_forbidden',
				'You do not have permission to access this resource.',
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}
