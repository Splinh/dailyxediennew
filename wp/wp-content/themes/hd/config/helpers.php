<?php
/**
 * Theme Helper Functions
 *
 * Contains reusable utility functions used across templates and core files.
 * Merged from: helpers.php, template-tags.php, translations.php
 *
 * @package HD
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

// --------------------------------------------------
// Translation Functions
// --------------------------------------------------

/**
 * Get JavaScript localization strings.
 *
 * @return array Translation strings for JS.
 */
function hd_get_js_translations(): array {
	return [
		// General
		'view_more'     => __( 'Xem thêm', 'hd' ),
		'loading'       => __( 'Đang tải...', 'hd' ),
		'error'         => __( 'Có lỗi xảy ra', 'hd' ),
		'success'       => __( 'Thành công', 'hd' ),
		'confirm'       => __( 'Xác nhận', 'hd' ),
		'cancel'        => __( 'Hủy', 'hd' ),
		'close'         => __( 'Đóng', 'hd' ),
		'search'        => __( 'Tìm kiếm', 'hd' ),
		'no_results'    => __( 'Không tìm thấy kết quả', 'hd' ),

		// Forms
		'required'      => __( 'Trường này là bắt buộc', 'hd' ),
		'invalid_email' => __( 'Email không hợp lệ', 'hd' ),
		'invalid_phone' => __( 'Số điện thoại không hợp lệ', 'hd' ),

		// Share
		'share'         => __( 'Chia sẻ', 'hd' ),
		'copy_link'     => __( 'Sao chép liên kết', 'hd' ),
		'link_copied'   => __( 'Đã sao chép liên kết', 'hd' ),
	];
}

// --------------------------------------------------

/**
 * Get WooCommerce localization strings.
 *
 * @return array WooCommerce translation strings for JS.
 */
function hd_get_wc_translations(): array {
	return [
		'added_to_cart' => __( 'Đã thêm vào giỏ hàng', 'hd' ),
		'view_cart'     => __( 'Xem giỏ hàng', 'hd' ),
		'checkout'      => __( 'Thanh toán', 'hd' ),
		'cart_empty'    => __( 'Giỏ hàng trống', 'hd' ),
		'remove_item'   => __( 'Xóa sản phẩm', 'hd' ),
		'update_cart'   => __( 'Cập nhật giỏ hàng', 'hd' ),
		'cart_updated'  => __( 'Giỏ hàng đã được cập nhật', 'hd' ),
		'out_of_stock'  => __( 'Hết hàng', 'hd' ),
		'add_to_cart'   => __( 'Thêm vào giỏ', 'hd' ),
		'quantity'      => __( 'Số lượng', 'hd' ),
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
