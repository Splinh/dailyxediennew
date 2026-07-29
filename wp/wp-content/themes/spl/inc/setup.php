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
		'gioi-thieu'     => __( 'Giới Thiệu', 'spl' ),
		'co-hoi-hop-tac' => __( 'Hợp Tác', 'spl' ),
		'tin-tuc'        => __( 'Tin Tức', 'spl' ),
		'lien-he'        => __( 'Liên Hệ', 'spl' ),
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

add_action( 'acf/init', 'spl_register_bottom_nav_acf_fields' );
function spl_register_bottom_nav_acf_fields(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( [
		'key'    => 'group_mobile_bottom_nav_options',
		'title'  => __( 'Cấu hình Bottom Nav Mobile', 'spl' ),
		'fields' => [
			[
				'key'           => 'field_bottom_nav_categories',
				'label'         => __( 'Danh mục sản phẩm hiển thị', 'spl' ),
				'name'          => 'bottom_nav_categories',
				'type'          => 'taxonomy',
				'taxonomy'      => 'product_cat',
				'field_type'    => 'multi_select',
				'allow_null'    => 1,
				'add_term'      => 0,
				'save_terms'    => 0,
				'load_terms'    => 0,
				'return_format' => 'object',
				'instructions'  => __( 'Chọn các danh mục sản phẩm cha muốn hiển thị trong slide panel di động. Bỏ trống để hiển thị tất cả.', 'spl' ),
			],
			[
				'key'           => 'field_bottom_nav_news_categories',
				'label'         => __( 'Danh mục tin tức hiển thị', 'spl' ),
				'name'          => 'bottom_nav_news_categories',
				'type'          => 'taxonomy',
				'taxonomy'      => 'category',
				'field_type'    => 'multi_select',
				'allow_null'    => 1,
				'add_term'      => 0,
				'save_terms'    => 0,
				'load_terms'    => 0,
				'return_format' => 'object',
				'instructions'  => __( 'Chọn các danh mục tin tức muốn hiển thị trong slide panel di động. Bỏ trống để hiển thị tất cả.', 'spl' ),
			],
		],
		'location' => [
			[
				[
					'param'    => 'options_page',
					'operator' => '==',
					'value'    => 'acf-options',
				],
			],
		],
	] );
}

// --------------------------------------------------
// Clean Unicode dotted uppercase "İ" (U+0130) in Vietnamese titles
// --------------------------------------------------

add_filter( 'the_title', 'spl_clean_vietnamese_dotted_i', 20 );
add_filter( 'single_post_title', 'spl_clean_vietnamese_dotted_i', 20 );
add_filter( 'wp_title', 'spl_clean_vietnamese_dotted_i', 20 );
add_filter( 'document_title_parts', function( $parts ) {
	if ( is_array( $parts ) ) {
		foreach ( $parts as $k => $v ) {
			if ( is_string( $v ) ) {
				$parts[ $k ] = spl_clean_vietnamese_dotted_i( $v );
			}
		}
	}
	return $parts;
}, 20 );

function spl_clean_vietnamese_dotted_i( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return $text;
	}
	// Replace U+0130 (İ -> I) and remove U+0307 combining dot accent.
	return str_replace( [ "\u{0130}", "\u{0307}" ], [ 'I', '' ], $text );
}

