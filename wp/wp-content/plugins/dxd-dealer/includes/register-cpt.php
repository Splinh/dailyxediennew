<?php
/**
 * Register CPT local_store + taxonomies store_type, local_store_state.
 *
 * Uses exact same slugs as devvn-local-stores-pro for data compatibility.
 *
 * @package DXD_Dealer
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'dxd_dealer_register_cpt' );
add_action( 'init', 'dxd_dealer_register_taxonomies' );
add_action( 'init', 'dxd_dealer_create_default_terms', 20 );

/**
 * Register the local_store CPT.
 */
function dxd_dealer_register_cpt(): void {
	$labels = [
		'name'               => __( 'Cửa hàng', 'dxd-dealer' ),
		'singular_name'      => __( 'Cửa hàng', 'dxd-dealer' ),
		'menu_name'          => __( 'Đại lý', 'dxd-dealer' ),
		'add_new'            => __( 'Thêm cửa hàng', 'dxd-dealer' ),
		'add_new_item'       => __( 'Thêm cửa hàng mới', 'dxd-dealer' ),
		'edit_item'          => __( 'Sửa cửa hàng', 'dxd-dealer' ),
		'new_item'           => __( 'Cửa hàng mới', 'dxd-dealer' ),
		'view_item'          => __( 'Xem cửa hàng', 'dxd-dealer' ),
		'search_items'       => __( 'Tìm cửa hàng', 'dxd-dealer' ),
		'not_found'          => __( 'Không tìm thấy cửa hàng', 'dxd-dealer' ),
		'not_found_in_trash' => __( 'Không có cửa hàng trong thùng rác', 'dxd-dealer' ),
		'all_items'          => __( 'Tất cả cửa hàng', 'dxd-dealer' ),
	];

	register_post_type( 'local_store', [
		'labels'              => $labels,
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'show_in_rest'        => true,
		'menu_position'       => 26,
		'menu_icon'           => 'dashicons-store',
		'capability_type'     => 'post',
		'has_archive'         => false,
		'hierarchical'        => false,
		'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
		'rewrite'             => [ 'slug' => 'cua-hang', 'with_front' => false ],
	] );
}

/**
 * Register store_type + local_store_state taxonomies.
 */
function dxd_dealer_register_taxonomies(): void {
	// ── store_type ──
	register_taxonomy( 'store_type', [ 'local_store' ], [
		'hierarchical'      => true,
		'labels'            => [
			'name'          => __( 'Loại Cửa Hàng', 'dxd-dealer' ),
			'singular_name' => __( 'Loại Cửa Hàng', 'dxd-dealer' ),
			'search_items'  => __( 'Tìm loại cửa hàng', 'dxd-dealer' ),
			'all_items'     => __( 'Tất cả loại', 'dxd-dealer' ),
			'edit_item'     => __( 'Sửa loại', 'dxd-dealer' ),
			'add_new_item'  => __( 'Thêm loại mới', 'dxd-dealer' ),
			'menu_name'     => __( 'Loại Cửa Hàng', 'dxd-dealer' ),
		],
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'query_var'         => true,
		'rewrite'           => [ 'slug' => 'loai-cua-hang' ],
	] );

	// ── local_store_state (province) ──
	register_taxonomy( 'local_store_state', [ 'local_store' ], [
		'hierarchical'      => true,
		'labels'            => [
			'name'          => __( 'Tỉnh/Thành phố', 'dxd-dealer' ),
			'singular_name' => __( 'Tỉnh/Thành phố', 'dxd-dealer' ),
			'search_items'  => __( 'Tìm tỉnh thành', 'dxd-dealer' ),
			'all_items'     => __( 'Tất cả tỉnh thành', 'dxd-dealer' ),
			'edit_item'     => __( 'Sửa tỉnh thành', 'dxd-dealer' ),
			'add_new_item'  => __( 'Thêm tỉnh thành', 'dxd-dealer' ),
			'menu_name'     => __( 'Tỉnh/Thành', 'dxd-dealer' ),
		],
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'query_var'         => true,
		'rewrite'           => [ 'slug' => 'tinh-thanh' ],
	] );
}

/**
 * Create default store_type terms.
 */
function dxd_dealer_create_default_terms(): void {
	if ( ! taxonomy_exists( 'store_type' ) ) {
		return;
	}

	$defaults = [
		[ 'name' => 'Đại Lý Uỷ Quyền',   'slug' => 'dai-ly-uy-quyen' ],
		[ 'name' => 'Cửa Hàng Uỷ Quyền', 'slug' => 'cua-hang-uy-quyen' ],
	];

	foreach ( $defaults as $term ) {
		if ( ! term_exists( $term['slug'], 'store_type' ) ) {
			wp_insert_term( $term['name'], 'store_type', [ 'slug' => $term['slug'] ] );
		}
	}
}
