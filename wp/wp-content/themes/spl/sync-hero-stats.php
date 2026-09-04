<?php
/**
 * Sync Hero section data for Giới Thiệu (About) page.
 *
 * Restores original title, description, and hero stats.
 *
 * Usage:
 *   php sync-hero-stats.php
 *   or
 *   /www/server/php/84/bin/php sync-hero-stats.php
 */

define( 'WP_USE_THEMES', false );

$wp_load_path = __DIR__ . '/../../../../wp/wp-load.php';
if ( ! file_exists( $wp_load_path ) ) {
	$wp_load_path = __DIR__ . '/../../../wp-load.php';
}

if ( ! file_exists( $wp_load_path ) ) {
	echo "Cannot find wp-load.php.\n";
	exit( 1 );
}

require_once $wp_load_path;

$page = get_page_by_path( 'gioi-thieu' );
$page_id = $page ? $page->ID : 936;

$sections = get_post_meta( $page_id, 'about_sections', true );
if ( ! is_array( $sections ) ) {
	$sections = [];
}

$found_hero = false;
foreach ( $sections as &$sec ) {
	if ( ( $sec['acf_fc_layout'] ?? '' ) === 'about_hero' ) {
		$sec['title']       = 'Về <span class="text-emerald-400">dailyxedien.vn</span>';
		$sec['description'] = 'Hệ thống phân phối xe điện, xe máy điện, xe 50cc chính hãng — tư vấn rõ ràng, giá minh bạch, hậu mãi dễ theo dõi.';
		$sec['stats']       = [
			[ 'number' => '20+', 'label' => 'Cửa hàng' ],
			[ 'number' => '10K+', 'label' => 'Khách hàng' ],
			[ 'number' => '50+', 'label' => 'Thương hiệu' ],
			[ 'number' => '98%', 'label' => 'Hài lòng' ],
		];
		$found_hero = true;
		break;
	}
}
unset( $sec );

if ( ! $found_hero ) {
	// Prepend about_hero if missing
	array_unshift( $sections, [
		'acf_fc_layout' => 'about_hero',
		'disable'       => 0,
		'tag'           => 'Về chúng tôi',
		'title'         => 'Về <span class="text-emerald-400">dailyxedien.vn</span>',
		'description'   => 'Hệ thống phân phối xe điện, xe máy điện, xe 50cc chính hãng — tư vấn rõ ràng, giá minh bạch, hậu mãi dễ theo dõi.',
		'stats'         => [
			[ 'number' => '20+', 'label' => 'Cửa hàng' ],
			[ 'number' => '10K+', 'label' => 'Khách hàng' ],
			[ 'number' => '50+', 'label' => 'Thương hiệu' ],
			[ 'number' => '98%', 'label' => 'Hài lòng' ],
		],
	] );
}

if ( function_exists( 'update_field' ) ) {
	update_field( 'about_sections', $sections, $page_id );
} else {
	update_post_meta( $page_id, 'about_sections', $sections );
}

// Clear caches
if ( class_exists( '\SPL\Features\Optimizer\PageCache' ) ) {
	\SPL\Features\Optimizer\PageCache::purgeAll();
}

echo "✓ Successfully synced Hero section on Page ID: {$page_id} to original dailyxedien.vn values!\n";
