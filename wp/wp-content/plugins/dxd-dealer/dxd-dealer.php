<?php
/**
 * Plugin Name: DXD Dealer System
 * Plugin URI: https://dailyxedien.vn
 * Description: Hệ thống quản lý đại lý/cửa hàng — Custom Post Type + Taxonomy + CSV Import/Export. Thay thế devvn-local-stores-pro + devvn-store-type.
 * Version: 1.0.0
 * Author: SPL
 * Text Domain: dxd-dealer
 * Domain Path: /languages
 * Requires PHP: 8.1
 *
 * Registers:
 *   CPT:  local_store
 *   Tax:  store_type, local_store_state
 *   Meta: localstore_address, localstore_phone, localstore_hotline,
 *         localstore_email, localstore_website, localstore_open_hours,
 *         localstore_maps_lat, localstore_maps_lng, localstore_code,
 *         localstore_gallery
 *
 * CSV format matches cua-hang-2026-06-06.csv:
 *   ma_cua_hang, ten_cua_hang, noi_dung, dia_chi, dien_thoai, hotline,
 *   email, gio_mo_cua, website, lat, lng, tinh_thanh, quan_huyen,
 *   loai_cua_hang, featured_image_url, gallery_urls
 */

defined( 'ABSPATH' ) || exit;

define( 'DXD_DEALER_VERSION', '1.0.0' );
define( 'DXD_DEALER_DIR', plugin_dir_path( __FILE__ ) );
define( 'DXD_DEALER_URL', plugin_dir_url( __FILE__ ) );

// ════════════════════════════════════════════════════════════════
//  AUTOLOAD MODULES
// ════════════════════════════════════════════════════════════════

require_once DXD_DEALER_DIR . 'includes/register-cpt.php';
require_once DXD_DEALER_DIR . 'includes/register-meta.php';
require_once DXD_DEALER_DIR . 'includes/csv-handler.php';
require_once DXD_DEALER_DIR . 'includes/admin-page.php';

// ════════════════════════════════════════════════════════════════
//  ACTIVATION / DEACTIVATION
// ════════════════════════════════════════════════════════════════

register_activation_hook( __FILE__, 'dxd_dealer_activate' );
function dxd_dealer_activate(): void {
	dxd_dealer_register_cpt();
	dxd_dealer_register_taxonomies();
	dxd_dealer_create_default_terms();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'dxd_dealer_deactivate' );
function dxd_dealer_deactivate(): void {
	flush_rewrite_rules();
}

// ════════════════════════════════════════════════════════════════
//  PUBLIC API — dxd_dealer_get_stores()
//  Centralized query, cached 1 hour, auto-invalidated on CPT save.
//  Used by both home store-locator.php and template-page-daily.php.
// ════════════════════════════════════════════════════════════════

/**
 * Get all published stores as a compact array.
 *
 * Each element: [
 *   'id', 't' (title), 'u' (url), 'img' (thumbnail),
 *   'ty' (type slug), 'tn' (type name),
 *   'p' (province name), 'ps' (province slug),
 *   'a' (address), 'ph' (phone), 'hl' (hotline),
 *   'em' (email), 'la' (lat), 'lo' (lng),
 * ]
 *
 * @return list<array<string,mixed>>
 */
function dxd_dealer_get_stores(): array {
	$cache_key = 'dxd_dealer_stores_v1';
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$posts = get_posts( [
		'post_type'      => 'local_store',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	$stores = [];
	foreach ( $posts as $s ) {
		$sid = $s->ID;

		$state = get_the_terms( $sid, 'local_store_state' );
		$types = get_the_terms( $sid, 'store_type' );

		$phone   = get_post_meta( $sid, 'localstore_phone', true );
		$hotline = get_post_meta( $sid, 'localstore_hotline', true );

		$stores[] = [
			'id'  => $sid,
			't'   => $s->post_title,
			'u'   => get_permalink( $sid ),
			'img' => get_the_post_thumbnail_url( $sid, 'medium' ) ?: '',
			'ty'  => ( $types && ! is_wp_error( $types ) ) ? $types[0]->slug : '',
			'tn'  => ( $types && ! is_wp_error( $types ) ) ? $types[0]->name : '',
			'p'   => ( $state && ! is_wp_error( $state ) ) ? $state[0]->name : '',
			'ps'  => ( $state && ! is_wp_error( $state ) ) ? $state[0]->slug : '',
			'a'   => get_post_meta( $sid, 'localstore_address', true ) ?: '',
			'ph'  => $phone ?: '',
			'hl'  => $hotline ?: '',
			'em'  => get_post_meta( $sid, 'localstore_email', true ) ?: '',
			'la'  => (float) get_post_meta( $sid, 'localstore_maps_lat', true ),
			'lo'  => (float) get_post_meta( $sid, 'localstore_maps_lng', true ),
		];
	}

	set_transient( $cache_key, $stores, HOUR_IN_SECONDS );
	return $stores;
}

/**
 * Flush store cache on any local_store save/delete.
 */
add_action( 'save_post_local_store', 'dxd_dealer_flush_cache' );
add_action( 'delete_post', 'dxd_dealer_flush_cache' );
function dxd_dealer_flush_cache(): void {
	delete_transient( 'dxd_dealer_stores_v1' );
}
