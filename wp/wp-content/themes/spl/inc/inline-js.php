<?php
/**
 * Core UI JavaScript — enqueued as external cacheable file.
 *
 * Previously inlined (~25KB per page), now served as a cacheable
 * script file. Browser caches after first visit.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'spl_enqueue_core_ui_js', 99 );

/**
 * Enqueue core UI interactions (mobile menu, reveal, tabs, etc.) as external file.
 */
function spl_enqueue_core_ui_js(): void {
	// core-ui.js disabled — old theme selectors (#mobile-menu-btn, .activity-card, .sp-tabs__tab)
	// clash with DailyXeDien Tailwind templates. dxd-ui.js handles drawer/dropdown/backToTop.
	// wp_enqueue_script(
	// 	'spl-core-ui',
	// 	get_template_directory_uri() . '/inc/core-ui.js',
	// 	[],
	// 	function_exists( 'spl_theme_asset_version' ) ? spl_theme_asset_version( 'inc/core-ui.js' ) : (string) THEME_VERSION,
	// 	[ 'strategy' => 'defer', 'in_footer' => true ]
	// );

	// dailyxedien UI: header drawer, category dropdown, back-to-top (plain JS, no build).
	wp_enqueue_script(
		'dxd-ui',
		get_template_directory_uri() . '/inc/dxd-ui.js',
		[],
		function_exists( 'spl_theme_asset_version' ) ? spl_theme_asset_version( 'inc/dxd-ui.js' ) : (string) THEME_VERSION,
		[ 'strategy' => 'defer', 'in_footer' => true ]
	);

	if ( is_front_page() || is_page_template( 'templates/template-page-home.php' ) ) {
		wp_enqueue_script(
			'dxd-home',
			get_template_directory_uri() . '/inc/page-home.js',
			[ 'dxd-ui' ],
			function_exists( 'spl_theme_asset_version' ) ? spl_theme_asset_version( 'inc/page-home.js' ) : (string) THEME_VERSION,
			[ 'strategy' => 'defer', 'in_footer' => true ]
		);
	}
}
