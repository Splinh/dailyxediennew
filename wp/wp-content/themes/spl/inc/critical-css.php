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
	// Google Fonts — non-render-blocking via media=print + onload swap.
	wp_enqueue_style(
		'google-font-inter-vietnam',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap',
		[],
		null,
		'print' // Load as print media initially; onload converts to 'all' (non-blocking).
	);

	// Convert print → all on load to apply fonts without blocking rendering.
	add_filter( 'style_loader_tag', static function ( string $html, string $handle ) : string {
		if ( $handle !== 'google-font-inter-vietnam' ) {
			return $html;
		}

		return str_replace(
			"media='print'",
			"media='print' onload=\"this.media='all'\"",
			$html
		);
	}, 20, 2 );
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
	// Preconnect only to origins requested immediately during initial render.
	echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<link rel="dns-prefetch" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="dns-prefetch" href="https://fonts.gstatic.com">' . "\n";
}
