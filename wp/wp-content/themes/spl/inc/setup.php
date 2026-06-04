<?php
/**
 * Theme setup and initialization.
 *
 * Handles menu registration, ACF options, widget areas.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

// --------------------------------------------------
// Menu locations
// --------------------------------------------------

add_action( 'after_setup_theme', 'spl_register_nav_menus', 11 );
function spl_register_nav_menus(): void {
	register_nav_menus( [
		'main-nav'   => __( 'Primary Menu', 'spl' ),
		'mobile-nav' => __( 'Mobile Menu', 'spl' ),
		'about-nav'  => __( 'Footer About Menu', 'spl' ),
		'policy-nav' => __( 'Footer Support Menu', 'spl' ),
	] );
}

// --------------------------------------------------
// Main nav fallback (when no menu assigned to main-nav)
// --------------------------------------------------

/**
 * Render a basic navigation when the "main-nav" location has no menu.
 *
 * Outputs <li><a> items (matches wp_nav_menu items_wrap '%3$s') linking to
 * the key site pages, so the header is never empty.
 *
 * @return void
 */
function spl_main_nav_fallback(): void {
	$items = [
		[ home_url( '/' ), __( 'Trang Chủ', 'spl' ) ],
	];

	$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
	if ( $shop_id > 0 ) {
		$items[] = [ get_permalink( $shop_id ), __( 'Cửa Hàng', 'spl' ) ];
	}

	$pages = [
		'gioi-thieu' => __( 'Giới Thiệu', 'spl' ),
		'tin-tuc'    => __( 'Tin Tức', 'spl' ),
		'lien-he'    => __( 'Liên Hệ', 'spl' ),
	];
	foreach ( $pages as $slug => $label ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$items[] = [ get_permalink( $page ), $label ];
		}
	}

	foreach ( $items as [ $url, $label ] ) {
		printf(
			'<li class="menu-item"><a href="%s">%s</a></li>',
			esc_url( $url ),
			esc_html( $label )
		);
	}
}

// --------------------------------------------------
// ACF Options Page
// --------------------------------------------------

add_action( 'acf/init', 'spl_register_acf_options_page' );
function spl_register_acf_options_page(): void {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page( [
		'page_title' => __( 'Tùy Chọn Theme', 'spl' ),
		'menu_title' => __( 'Tùy Chọn', 'spl' ),
		'menu_slug'  => 'acf-options',
		'capability' => 'edit_posts',
		'redirect'   => false,
		'icon_url'   => 'dashicons-admin-generic',
		'position'   => 2,
	] );
}
