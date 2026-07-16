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
 * Register self-hosted Be Vietnam Pro @font-face via inline CSS.
 *
 * Using inline style avoids an extra HTTP request while keeping
 * font declarations out of the Vite build (no URL resolution issues).
 */
function spl_enqueue_fonts(): void {
	$font_url = get_template_directory_uri() . '/assets/fonts';

	$weights = [
		300 => [ 'vi' => 'BeVietnamPro-300-vi.woff2', 'la' => 'BeVietnamPro-300-la.woff2' ],
		400 => [ 'vi' => 'BeVietnamPro-400-vi.woff2', 'la' => 'BeVietnamPro-400-la.woff2' ],
		500 => [ 'vi' => 'BeVietnamPro-500-vi.woff2', 'la' => 'BeVietnamPro-500-la.woff2' ],
		600 => [ 'vi' => 'BeVietnamPro-600-vi.woff2', 'la' => 'BeVietnamPro-600-la.woff2' ],
		700 => [ 'vi' => 'BeVietnamPro-700-vi.woff2', 'la' => 'BeVietnamPro-700-la.woff2' ],
	];

	$vi_range = 'U+0102-0103,U+0110-0111,U+0128-0129,U+0168-0169,U+01A0-01A1,U+01AF-01B0,U+0300-0301,U+0303-0304,U+0308-0309,U+0323,U+0329,U+1EA0-1EF9,U+20AB';
	$la_range = 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD';

	$css = '';

	foreach ( $weights as $weight => $files ) {
		// Vietnamese subset.
		$css .= "@font-face{font-family:'Be Vietnam Pro';font-style:normal;font-weight:{$weight};font-display:swap;src:url('{$font_url}/{$files['vi']}') format('woff2');unicode-range:{$vi_range}}";

		// Latin subset.
		$css .= "@font-face{font-family:'Be Vietnam Pro';font-style:normal;font-weight:{$weight};font-display:swap;src:url('{$font_url}/{$files['la']}') format('woff2');unicode-range:{$la_range}}";
	}

	// Register a dummy handle for inline CSS.
	wp_register_style( 'spl-fonts', false, [], null );
	wp_enqueue_style( 'spl-fonts' );
	wp_add_inline_style( 'spl-fonts', $css );
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
	$font_url = get_template_directory_uri() . '/assets/fonts';

	// 1. Preconnect to external trackers/analytics domains.
	$preconnect_domains = [
		'https://www.googletagmanager.com',
		'https://www.google-analytics.com',
		'https://connect.facebook.net',
	];
	foreach ( $preconnect_domains as $domain ) {
		echo '<link rel="preconnect" href="' . esc_url( $domain ) . '">' . "\n";
		echo '<link rel="dns-prefetch" href="' . esc_url( $domain ) . '">' . "\n";
	}

	// 2. Preload critical fonts for LCP / layout.
	$critical_fonts = [
		'BeVietnamPro-400-vi.woff2',
		'BeVietnamPro-400-la.woff2',
		'BeVietnamPro-600-vi.woff2',
		'BeVietnamPro-600-la.woff2',
	];
	foreach ( $critical_fonts as $font ) {
		echo '<link rel="preload" href="' . esc_url( "{$font_url}/{$font}" ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}
