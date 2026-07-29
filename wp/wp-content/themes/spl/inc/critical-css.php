<?php
/**
 * Theme fonts & core CSS setup.
 *
 * Self-hosts Be Vietnam Pro woff2 (Vietnamese + Latin subsets).
 * Font files: static/fonts/ → assets/fonts/ (via Vite publicDir).
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'spl_enqueue_fonts', 5 );

/**
 * Enqueue Google Fonts Inter & Be Vietnam Pro for flawless Vietnamese typography.
 *
 * Inter font provides superior kerning and precise diacritic mark positioning for
 * Vietnamese character combinations like iệ, iế, iện without mark collision.
 */
function spl_enqueue_fonts(): void {
	wp_enqueue_style(
		'google-font-inter-vietnam',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap',
		[],
		null
	);
}

if ( ! function_exists( 'spl_theme_asset_version' ) ) {
	/**
	 * Return a cache-busting version for theme assets.
	 */
	function spl_theme_asset_version( string $relative_path ): string {
		$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );

		return is_file( $path ) ? (string) filemtime( $path ) : (string) THEME_VERSION;
	}
}

add_action( 'wp_head', 'spl_preload_assets', 1 );

/**
 * Output preload and preconnect resource hints in wp_head.
 */
function spl_preload_assets(): void {
	// 1. Preconnect to font and tracking domains.
	$preconnect_domains = [
		'https://fonts.googleapis.com',
		'https://fonts.gstatic.com',
		'https://www.googletagmanager.com',
		'https://www.google-analytics.com',
		'https://connect.facebook.net',
	];
	foreach ( $preconnect_domains as $domain ) {
		echo '<link rel="preconnect" href="' . esc_url( $domain ) . '" crossorigin>' . "\n";
		echo '<link rel="dns-prefetch" href="' . esc_url( $domain ) . '">' . "\n";
	}
}
