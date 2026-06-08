<?php
/**
 * Seed demo WooCommerce products (xe điện) with real images so the Home
 * "Best sellers" tabs render like the mockup. Run once via:
 *   wp eval-file wp/wp-content/themes/spl/populate-demo-products.php
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Product_Simple' ) ) {
	echo "WooCommerce not active\n";
	return;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/** Sideload an image URL → attachment ID (cached per URL). */
function dxd_sideload_img( $url ) {
	static $cache = [];
	if ( isset( $cache[ $url ] ) ) {
		return $cache[ $url ];
	}
	$tmp = download_url( $url );
	if ( is_wp_error( $tmp ) ) {
		return $cache[ $url ] = 0;
	}
	$file = [ 'name' => 'demo-xe-' . substr( md5( $url ), 0, 8 ) . '.jpg', 'tmp_name' => $tmp ];
	$id   = media_handle_sideload( $file, 0 );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		return $cache[ $url ] = 0;
	}
	return $cache[ $url ] = (int) $id;
}

// Product category map (slug => display name).
$cat_defs = [
	'xe-dien'     => 'Xe Điện',
	'xe-50cc'     => 'Xe 50cc',
	'xe-may-dien' => 'Xe Máy Điện',
	'xe-3-banh'   => 'Xe 3 Bánh',
];
$cat_id = [];
foreach ( $cat_defs as $slug => $name ) {
	$term = term_exists( $slug, 'product_cat' );
	if ( ! $term ) {
		$term = wp_insert_term( $name, 'product_cat', [ 'slug' => $slug ] );
	}
	if ( ! is_wp_error( $term ) ) {
		$cat_id[ $slug ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	}
}

// A small pool of bike/scooter photos (reused across products).
$imgs = [
	'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=600&q=80',
	'https://images.unsplash.com/photo-1595054179361-b0e66d9bb7a3?auto=format&fit=crop&w=600&q=80',
	'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=600&q=80',
	'https://images.unsplash.com/photo-1571068316344-75bc76f77890?auto=format&fit=crop&w=600&q=80',
];

// name, regular, sale (0=none), cat slug, brand, sales, img index.
$products = [
	[ 'Xe điện Vespa Roma S', 22000000, 19900000, 'xe-dien', 'Vespa', 1200, 1 ],
	[ 'Xe điện thể thao Xmen One', 20500000, 18500000, 'xe-dien', 'Xmen', 980, 0 ],
	[ 'Xe điện thông minh Vinfast Feliz S', 27000000, 24900000, 'xe-dien', 'Vinfast', 1500, 2 ],
	[ 'Xe điện Dibao Pansy S cao cấp', 21000000, 18900000, 'xe-dien', 'Dibao', 640, 1 ],
	[ 'Xe đạp điện Giant M133S chính hãng', 14000000, 12500000, 'xe-dien', 'Giant', 1100, 0 ],

	[ 'Xe máy 50cc Wave Alpha bản nâng cấp', 19000000, 17800000, 'xe-50cc', 'Honda', 820, 0 ],
	[ 'Xe 50cc Cub Halim cổ điển', 14500000, 13900000, 'xe-50cc', 'Halim', 410, 3 ],
	[ 'Xe 50cc Crea Nilux thời trang', 15500000, 0, 'xe-50cc', 'Crea', 260, 1 ],

	[ 'Xe máy điện thông minh Yadea Orla', 22000000, 20500000, 'xe-may-dien', 'Yadea', 2100, 2 ],
	[ 'Xe máy điện Vinfast Klara S', 26900000, 0, 'xe-may-dien', 'Vinfast', 1700, 1 ],
	[ 'Xe máy điện Pega NewTech', 17000000, 15900000, 'xe-may-dien', 'Pega', 930, 3 ],
	[ 'Xe máy điện Dibao Pansy S4', 23000000, 21500000, 'xe-may-dien', 'Dibao', 820, 0 ],
];

$created = 0;
foreach ( $products as $p ) {
	list( $name, $regular, $sale, $slug, $brand, $sales, $imgi ) = $p;

	// Skip if a product with the same name already exists.
	$existing = get_page_by_title( $name, OBJECT, 'product' );
	if ( $existing ) {
		continue;
	}

	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( (string) $regular );
	if ( $sale > 0 ) {
		$product->set_sale_price( (string) $sale );
	}
	$product->set_short_description( sprintf( 'Sản phẩm %s chính hãng, bảo hành toàn quốc, hỗ trợ trả góp 0%%.', $brand ) );
	if ( isset( $cat_id[ $slug ] ) ) {
		$product->set_category_ids( [ $cat_id[ $slug ] ] );
	}
	$img_id = dxd_sideload_img( $imgs[ $imgi ] );
	if ( $img_id ) {
		$product->set_image_id( $img_id );
	}
	$pid = $product->save();
	if ( $pid ) {
		update_post_meta( $pid, 'total_sales', $sales );
		update_post_meta( $pid, '_dxd_brand', $brand );
		$created++;
	}
}
echo "✓ Created {$created} demo products\n";

// Wire the Home best_sellers tabs to the new categories (section index 3).
$home_id = (int) get_option( 'page_on_front' );
if ( $home_id ) {
	$tab_map = [ 0 => 'xe-dien', 1 => 'xe-50cc', 2 => 'xe-may-dien', 3 => 'xe-3-banh' ];
	$tab_count = (int) get_post_meta( $home_id, 'home_sections_3_tabs', true );
	for ( $i = 0; $i < max( $tab_count, 4 ); $i++ ) {
		if ( isset( $tab_map[ $i ], $cat_id[ $tab_map[ $i ] ] ) ) {
			update_post_meta( $home_id, "home_sections_3_tabs_{$i}_category", $cat_id[ $tab_map[ $i ] ] );
		}
	}
	echo "✓ Linked best_sellers tabs to categories (tabs: {$tab_count})\n";
}

echo "=== DONE ===\n";
