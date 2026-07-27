<?php
/**
 * Theme Helper Functions
 *
 * Contains reusable utility functions used across templates and core files.
 * Merged from: helpers.php, template-tags.php, translations.php
 *
 * @package SPL
 * @author  HD
 */

\defined( 'ABSPATH' ) || die;

// --------------------------------------------------
// SVG Functions
// --------------------------------------------------

/**
 * @param string|null $name
 * @param string $cssClass
 *
 * @return string
 */
function hd_svg( ?string $name, string $cssClass = '' ): string {
	if ( ! $name ) {
		return '';
	}

	if ( empty( $cssClass ) ) {
		$cssClass = 'fill-current';
	}

	// Lazy-load SVG definitions from config file (only when first called)
	static $icons = null;
	$icons      ??= (array) require __DIR__ . '/svg-icons.php';

	if ( empty( $icons[ $name ] ) ) {
		return '';
	}

	// Inject CSS class into the SVG element
	return str_replace( '<svg ', '<svg class="' . esc_attr( $cssClass ) . '" ', $icons[ $name ] );
}

if ( ! function_exists( 'spl_icon' ) ) {
	/**
	 * Render an inline SVG icon (Lucide-style + custom SVG dictionary).
	 *
	 * @param string|null $name  Icon key.
	 * @param string $cssClass   CSS classes for the <svg>.
	 * @return string SVG markup.
	 */
	function spl_icon( ?string $name, string $cssClass = 'w-5 h-5' ): string {
		if ( ! $name ) {
			return '';
		}

		static $lucide_icons = [
			'menu'           => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
			'search'         => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
			'cart'           => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
			'shopping-cart'  => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
			'user'           => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'phone'          => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
			'close'          => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
			'x'              => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
			'chevron-right'  => '<polyline points="9 18 15 12 9 6"/>',
			'chevron-left'   => '<polyline points="15 18 9 12 15 6"/>',
			'chevron-down'   => '<polyline points="6 9 12 15 18 9"/>',
			'bolt'           => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
			'bicycle'        => '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>',
			'motorcycle'     => '<circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M5.5 17.5h7l3.5-6H20"/><path d="M9 11.5h6"/><path d="M14 8h3l1.5 3.5"/>',
			'truck'          => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
			'map-pin'        => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
			'mail'           => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
			'trash-2'        => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>',
			'tag'            => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
			'arrow-left'     => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
			'check-circle'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
			'shield'         => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
			'refresh-cw'     => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
			'headphones'     => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
			'file-text'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
			'message-circle' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
			'store'          => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M7 14h10"/><path d="M9 18h6"/>',
			'clock'          => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
			'share'          => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>',
			'award'          => '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
			'star'           => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
			'heart'          => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
			'lock'           => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
			'zap'            => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
			'users'          => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
			'building'       => '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/>',
			'target'         => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
			'gift'           => '<polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>',
			'smile'          => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>',
			'check'          => '<polyline points="20 6 9 17 4 12"/>',
		];

		if ( isset( $lucide_icons[ $name ] ) ) {
			return sprintf(
				'<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
				esc_attr( $cssClass ?: 'w-5 h-5' ),
				$lucide_icons[ $name ]
			);
		}

		return hd_svg( $name, $cssClass );
	}
}

// --------------------------------------------------
// Translation Functions
// --------------------------------------------------

/**
 * Get JavaScript localization strings.
 *
 * @return array Translation strings for JS.
 */
function spl_get_js_translations(): array {
	return [
		// General
		'view_more'     => __( 'Xem thêm', 'spl' ),
		'loading'       => __( 'Đang tải...', 'spl' ),
		'error'         => __( 'Có lỗi xảy ra', 'spl' ),
		'success'       => __( 'Thành công', 'spl' ),
		'confirm'       => __( 'Xác nhận', 'spl' ),
		'cancel'        => __( 'Hủy', 'spl' ),
		'close'         => __( 'Đóng', 'spl' ),
		'search'        => __( 'Tìm kiếm', 'spl' ),
		'no_results'    => __( 'Không tìm thấy kết quả', 'spl' ),

		// Forms
		'required'      => __( 'Trường này là bắt buộc', 'spl' ),
		'invalid_email' => __( 'Email không hợp lệ', 'spl' ),
		'invalid_phone' => __( 'Số điện thoại không hợp lệ', 'spl' ),

		// Share
		'share'         => __( 'Chia sẻ', 'spl' ),
		'copy_link'     => __( 'Sao chép liên kết', 'spl' ),
		'link_copied'   => __( 'Đã sao chép liên kết', 'spl' ),
	];
}

// --------------------------------------------------

/**
 * Get WooCommerce localization strings.
 *
 * @return array WooCommerce translation strings for JS.
 */
function spl_get_wc_translations(): array {
	return [
		'added_to_cart' => __( 'Đã thêm vào giỏ hàng', 'spl' ),
		'view_cart'     => __( 'Xem giỏ hàng', 'spl' ),
		'checkout'      => __( 'Thanh toán', 'spl' ),
		'cart_empty'    => __( 'Giỏ hàng trống', 'spl' ),
		'remove_item'   => __( 'Xóa sản phẩm', 'spl' ),
		'update_cart'   => __( 'Cập nhật giỏ hàng', 'spl' ),
		'cart_updated'  => __( 'Giỏ hàng đã được cập nhật', 'spl' ),
		'out_of_stock'  => __( 'Hết hàng', 'spl' ),
		'add_to_cart'   => __( 'Thêm vào giỏ', 'spl' ),
		'quantity'      => __( 'Số lượng', 'spl' ),
	];
}

// --------------------------------------------------
// Post Type / Taxonomy Auto-Detection
// --------------------------------------------------

/**
 * Detect the primary hierarchical taxonomy for a post type.
 *
 * Priority: conventional {cpt}_cat name → first hierarchical + public taxonomy.
 * E.g., for 'product', finds 'product_cat' before 'product_brand'.
 *
 * @param string $postType Post type slug.
 *
 * @return string|null Taxonomy name, or null if none found.
 */
function _hd_detect_primary_taxonomy( string $postType ): ?string {
	$taxonomies = get_object_taxonomies( $postType, 'objects' );

	// Convention: {cpt}_cat (WooCommerce pattern)
	$conventional = $postType . '_cat';
	if ( isset( $taxonomies[ $conventional ] ) && $taxonomies[ $conventional ]->hierarchical && $taxonomies[ $conventional ]->public ) {
		return $conventional;
	}

	// Fallback: first hierarchical + public taxonomy
	foreach ( $taxonomies as $tax ) {
		if ( $tax->hierarchical && $tax->public ) {
			return $tax->name;
		}
	}

	return null;
}

/**
 * Build post_type => primary_taxonomy map.
 *
 * Auto-detects custom post types that have at least one hierarchical
 * (category-like) taxonomy. Built-in 'post' is always included.
 * If called before 'init', returns base defaults only.
 *
 * @return array<string, string>
 */
function _hd_build_post_type_terms(): array {
	$map = [ 'post' => 'category' ];

	if ( ! did_action( 'init' ) ) {
		return $map;
	}

	$cpts = get_post_types(
		[
			'public'   => true,
			'_builtin' => false,
		],
		'names'
	);
	foreach ( $cpts as $cpt ) {
		$primary = _hd_detect_primary_taxonomy( $cpt );
		if ( $primary ) {
			$map[ $cpt ] = $primary;
		}
	}

	return $map;
}

/**
 * Build list of post types/taxonomies for aspect ratio settings.
 *
 * Includes post types that support 'thumbnail' (featured image)
 * and their primary hierarchical taxonomy (if any).
 * Built-in 'post' is always included.
 *
 * @return string[]
 */
function _hd_build_aspect_ratio_post_types(): array {
	$types = [ 'post' ];

	if ( ! did_action( 'init' ) ) {
		return $types;
	}

	$cpts = get_post_types(
		[
			'public'   => true,
			'_builtin' => false,
		],
		'names'
	);
	foreach ( $cpts as $cpt ) {
		if ( ! post_type_supports( $cpt, 'thumbnail' ) ) {
			continue;
		}

		$types[] = $cpt;

		$primary = _hd_detect_primary_taxonomy( $cpt );
		if ( $primary ) {
			$types[] = $primary;
		}
	}

	return array_unique( $types );
}
