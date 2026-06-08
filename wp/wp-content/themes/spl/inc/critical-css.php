<?php
/**
 * Theme core CSS — enqueued as external files for browser caching.
 *
 * Previously inlined (~150KB per page load), now served as cacheable
 * stylesheet files. Browser caches them after first visit.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'spl_enqueue_core_css', 1 );
add_filter( 'wp_resource_hints', 'spl_google_font_resource_hints', 10, 2 );

if ( ! function_exists( 'spl_theme_asset_version' ) ) {
	/**
	 * Return a cache-busting version for theme assets.
	 */
	function spl_theme_asset_version( string $relative_path ): string {
		$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );

		return is_file( $path ) ? (string) filemtime( $path ) : (string) THEME_VERSION;
	}
}

/**
 * Add preconnect hints for Google Fonts.
 *
 * @param array<int, string|array<string, string>> $urls          Resource URLs.
 * @param string                                   $relation_type Resource hint type.
 * @return array<int, string|array<string, string>>
 */
function spl_google_font_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' !== $relation_type ) {
		return $urls;
	}

	$urls[] = 'https://fonts.googleapis.com';
	$urls[] = [
		'href'        => 'https://fonts.gstatic.com',
		'crossorigin' => 'anonymous',
	];

	return $urls;
}

/**
 * Enqueue critical + pages CSS as external files (cacheable).
 *
 * Note: critical.css chứa design system cũ (--color-primary: #60b301 green)
 * xung đột với Tailwind-based design của DailyXeDien (blue #1e73be).
 * Đã tạm disable để Tailwind utilities trong header/footer/parts/home hoạt động đúng.
 * Khi cần restore cho site khác, bật lại block wp_enqueue_style('spl-critical') bên dưới.
 */
function spl_enqueue_core_css(): void {
	wp_enqueue_style(
		'spl-fonts',
		'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap',
		[],
		null
	);

	// critical.css disabled — conflicts with DailyXeDien Tailwind design.
	// wp_enqueue_style(
	// 	'spl-critical',
	// 	get_template_directory_uri() . '/inc/critical.css',
	// 	[ 'spl-fonts' ],
	// 	spl_theme_asset_version( 'inc/critical.css' )
	// );

	// Sub-page styles — also old theme design system, disabled.
	// pages.css dùng --color-bg, --color-border từ critical.css (green theme).
	// Tailwind page.scss/share.scss từ Vite build handles inner page styling.
	// if ( ! is_front_page() ) {
	// 	wp_enqueue_style(
	// 		'spl-pages',
	// 		get_template_directory_uri() . '/inc/pages.css',
	// 		[ 'spl-fonts' ],
	// 		spl_theme_asset_version( 'inc/pages.css' )
	// 	);
	// }
}
