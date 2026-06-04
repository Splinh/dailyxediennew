<?php
/**
 * Inline critical CSS — loaded directly when Vite build is not available.
 *
 * This provides basic styling so the demo is viewable before the full
 * Vite pipeline is operational.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'spl_inline_critical_css', 5 );

/**
 * Output inline CSS if Vite assets are not built yet.
 */
function spl_inline_critical_css(): void {
	echo '<style id="spl-critical-css">';
	readfile( __DIR__ . '/critical.css' );
	echo '</style>';

	// Sub-page styles (about, contact, news, single).
	if ( ! is_front_page() ) {
		echo '<style id="spl-pages-css">';
		readfile( __DIR__ . '/pages.css' );
		echo '</style>';
	}
}
