<?php
/**
 * DailyXeDien UI scripts — enqueued via Vite build pipeline.
 *
 * dxd.js: header drawer, category dropdown, back-to-top, cart modal,
 *         mobile bottom nav, category panel, add-to-cart AJAX, buy now.
 * home.js: homepage hero slider, tabs, store locator, lightbox, toast.
 *
 * CSS: dxd-ui styles are now bundled via SCSS partial (_dxd-ui.scss)
 * and included in the main CSS build — no separate enqueue needed.
 *
 * @package SPL
 */

use SPL\Core\Asset;

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'spl_enqueue_dxd_ui', 99 );

/**
 * Enqueue DailyXeDien UI interactions via Vite-built assets.
 */
function spl_enqueue_dxd_ui(): void {
	// Global DXD UI (drawer, cart modal, back-to-top, category dropdown, etc.)
	Asset::enqueueJS( 'dxd.js', [], null, true, [ 'defer' ] );

	// Homepage-only interactions (hero slider, store locator, lightbox, etc.)
	if ( is_front_page() || is_page_template( 'templates/template-page-home.php' ) ) {
		Asset::enqueueJS( 'home.js', [ Asset::handle( 'dxd.js' ) ], null, true, [ 'defer' ] );
	}
}
