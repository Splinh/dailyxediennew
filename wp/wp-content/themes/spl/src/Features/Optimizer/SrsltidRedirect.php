<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

/**
 * Strip Google `srsltid` parameter to prevent duplicate URLs.
 *
 * Google Ads appends `?srsltid=...` to landing pages, which causes
 * WooCommerce/WordPress to sometimes treat these as separate pages
 * or trigger unnecessary redirects. This strips the parameter early
 * and performs a 301 redirect to the clean canonical URL.
 *
 * @package SPL
 */
final class SrsltidRedirect {

	public static function register(): void {
		add_action( 'template_redirect', [ self::class, 'redirect' ], 1 );
	}

	/**
	 * Redirect requests with srsltid parameter to clean URL.
	 */
	public static function redirect(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['srsltid'] ) ) {
			return;
		}

		$clean_url = remove_query_arg( 'srsltid' );

		if ( $clean_url ) {
			wp_safe_redirect( esc_url_raw( $clean_url ), 301 );
			exit;
		}
	}
}
