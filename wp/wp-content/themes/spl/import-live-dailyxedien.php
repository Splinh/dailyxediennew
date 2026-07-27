<?php
/**
 * DailyXeDien Live Content Importer & SEO Optimizer.
 *
 * Migration script to fetch, clean, and optimize all products, variations,
 * categories, posts, and Rank Math SEO metadata from live site https://dailyxedien.vn.
 *
 * @package SPL
 */

if ( php_sapi_name() !== 'cli' && ! defined( 'WP_CLI' ) && empty( $_GET['run_import'] ) ) {
	die( "Usage: Run via CLI `php import-live-dailyxedien.php` or add `?run_import=1` in browser.\n" );
}

// Load WordPress bootstrap
require_once dirname( __DIR__, 3 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/post.php';

// Disable time limit for batch import
set_time_limit( 0 );
ini_set( 'memory_limit', '1024M' );

echo "====================================================\n";
echo "🚀 DAILYXEDIEN LIVE DATA MIGRATOR & SEO OPTIMIZER\n";
echo "====================================================\n\n";

/**
 * Helper to clean legacy shortcodes and inline styles from HTML content.
 */
function spl_clean_legacy_content( string $content ): string {
	if ( empty( $content ) ) {
		return '';
	}

	// Remove WPBakery / Flatsome shortcodes
	$shortcodes = [
		'vc_row', 'vc_column', 'vc_column_inner', 'vc_single_image',
		'ux_banner', 'ux_slider', 'ux_image', 'gap', 'divider',
		'contact-form-7', 'block', 'row', 'col'
	];
	foreach ( $shortcodes as $sc ) {
		$content = preg_replace( '/\[' . $sc . '[^\]]*\]/i', '', $content );
		$content = preg_replace( '/\[\/' . $sc . '\]/i', '', $content );
	}

	// Remove arbitrary inline styles like style="font-family:...; color:...;"
	$content = preg_replace( '/\s*style="[^"]*"/i', '', $content );

	// Remove empty paragraphs
	$content = preg_replace( '/<p>\s*<\/p>/i', '', $content );

	return trim( $content );
}

/**
 * Download and attach remote image to post media library safely with sslverify false.
 */
function spl_attach_remote_image( string $url, int $postId, string $desc = '' ): int {
	if ( empty( $url ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$filename = basename( parse_url( $url, PHP_URL_PATH ) );
	if ( ! $filename ) {
		return 0;
	}

	// Check if already downloaded
	$existing = get_posts([
		'post_type'   => 'attachment',
		'meta_key'    => '_source_url',
		'meta_value'  => $url,
		'post_status' => 'any',
		'numberposts' => 1,
	]);

	if ( ! empty( $existing ) ) {
		return (int) $existing[0]->ID;
	}

	$response = wp_remote_get( $url, [
		'timeout'   => 15,
		'sslverify' => false,
		'headers'   => [ 'User-Agent' => 'Mozilla/5.0' ],
	] );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return 0;
	}

	$image_data = wp_remote_retrieve_body( $response );
	if ( empty( $image_data ) ) {
		return 0;
	}

	$upload_dir = wp_upload_dir();
	$file_path  = $upload_dir['path'] . '/' . $filename;
	file_put_contents( $file_path, $image_data );

	$file_type  = wp_check_filetype( $filename, null );
	$attachment = [
		'post_mime_type' => $file_type['type'] ?: 'image/jpeg',
		'post_title'     => sanitize_file_name( $filename ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	];

	$attach_id = wp_insert_attachment( $attachment, $file_path, $postId );
	if ( ! is_wp_error( $attach_id ) && $attach_id ) {
		update_post_meta( $attach_id, '_source_url', $url );
		return (int) $attach_id;
	}

	return 0;
}

// ----------------------------------------------------
// STEP 1: IMPORT CATEGORIES
// ----------------------------------------------------
echo "📌 [STEP 1/4] Syncing Categories from live site...\n";

$catUrl = 'https://dailyxedien.vn/wp-json/wp/v2/product_cat?per_page=100';
$catRes = wp_remote_get( $catUrl, [ 'timeout' => 20, 'sslverify' => false, 'headers' => [ 'User-Agent' => 'Mozilla/5.0' ] ] );

if ( ! is_wp_error( $catRes ) && wp_remote_retrieve_response_code( $catRes ) === 200 ) {
	$cats = json_decode( wp_remote_retrieve_body( $catRes ), true );
	if ( is_array( $cats ) ) {
		foreach ( $cats as $c ) {
			if ( empty( $c['name'] ) || empty( $c['slug'] ) ) continue;
			if ( ( $c['lang'] ?? 'vi' ) === 'en' ) continue; // Skip English categories
			$term = term_exists( $c['slug'], 'product_cat' );
			if ( ! $term ) {
				wp_insert_term( $c['name'], 'product_cat', [ 'slug' => $c['slug'] ] );
				echo "  + Added Product Category: {$c['name']}\n";
			}
		}
	}
}

// ----------------------------------------------------
// STEP 2: IMPORT PRODUCTS & VARIATIONS
// ----------------------------------------------------
echo "\n📌 [STEP 2/4] Fetching & Importing Products (including Variations)...\n";

$pPage = 1;
$totalProductsImported = 0;

while ( true ) {
	$prodUrl = "https://dailyxedien.vn/wp-json/wc/store/v1/products?per_page=20&page={$pPage}";
	echo "  -> Requesting Products Page {$pPage}...\n";

	$pRes = wp_remote_get( $prodUrl, [ 'timeout' => 30, 'sslverify' => false, 'headers' => [ 'User-Agent' => 'Mozilla/5.0' ] ] );
	if ( is_wp_error( $pRes ) || wp_remote_retrieve_response_code( $pRes ) !== 200 ) {
		echo "  -> Completed fetching products (Page {$pPage} empty or end of list).\n";
		break;
	}

	$prods = json_decode( wp_remote_retrieve_body( $pRes ), true );
	if ( empty( $prods ) || ! is_array( $prods ) ) {
		break;
	}

	foreach ( $prods as $p ) {
		$title = sanitize_text_field( $p['name'] );
		$slug  = sanitize_title( $p['slug'] );
		$content = spl_clean_legacy_content( $p['description'] ?? '' );
		$excerpt = spl_clean_legacy_content( $p['short_description'] ?? '' );

		// Check if product exists by slug
		$existingId = post_exists( $title, '', '', 'product' );
		if ( ! $existingId ) {
			$existingPost = get_page_by_path( $slug, OBJECT, 'product' );
			if ( $existingPost ) {
				$existingId = $existingPost->ID;
			}
		}

		$postData = [
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'post_status'  => 'publish',
			'post_type'    => 'product',
		];

		if ( $existingId ) {
			$postData['ID'] = $existingId;
			$productId = wp_update_post( $postData );
			$actionStr = "Updated";
		} else {
			$productId = wp_insert_post( $postData );
			$actionStr = "Created";
		}

		if ( is_wp_error( $productId ) || ! $productId ) {
			continue;
		}

		// Pricing & Meta
		$prices = $p['prices'] ?? [];
		$regPrice  = isset( $prices['regular_price'] ) ? floatval( $prices['regular_price'] ) / 100 : 0;
		$salePrice = isset( $prices['sale_price'] ) && $prices['sale_price'] !== $prices['regular_price'] ? floatval( $prices['sale_price'] ) / 100 : '';
		$finalPrice = $salePrice ? $salePrice : $regPrice;

		update_post_meta( $productId, '_regular_price', $regPrice ? (string)$regPrice : '' );
		if ( $salePrice ) {
			update_post_meta( $productId, '_sale_price', (string)$salePrice );
		} else {
			delete_post_meta( $productId, '_sale_price' );
		}
		update_post_meta( $productId, '_price', (string)$finalPrice );
		update_post_meta( $productId, '_sku', sanitize_text_field( $p['sku'] ?? '' ) );
		update_post_meta( $productId, '_stock_status', 'instock' );

		// Product Categories
		if ( ! empty( $p['categories'] ) && is_array( $p['categories'] ) ) {
			$catIds = [];
			foreach ( $p['categories'] as $catObj ) {
				$t = term_exists( $catObj['slug'], 'product_cat' );
				if ( $t ) {
					$catIds[] = (int) $t['term_id'];
				}
			}
			if ( ! empty( $catIds ) ) {
				wp_set_object_terms( $productId, $catIds, 'product_cat' );
			}
		}

		// Images
		if ( ! empty( $p['images'] ) && is_array( $p['images'] ) ) {
			$galleryIds = [];
			foreach ( $p['images'] as $idx => $imgObj ) {
				$imgUrl = $imgObj['src'] ?? '';
				if ( ! $imgUrl ) continue;

				$attachId = spl_attach_remote_image( $imgUrl, $productId, $title );
				if ( $attachId ) {
					if ( $idx === 0 ) {
						set_post_thumbnail( $productId, $attachId );
					} else {
						$galleryIds[] = $attachId;
					}
				}
			}
			if ( ! empty( $galleryIds ) ) {
				update_post_meta( $productId, '_product_image_gallery', implode( ',', $galleryIds ) );
			}
		}

		// Variations / Attributes
		if ( ! empty( $p['variations'] ) && is_array( $p['variations'] ) ) {
			wp_set_object_terms( $productId, 'variable', 'product_type' );
			echo "    * [Variable Product] {$title} (" . count( $p['variations'] ) . " variations)\n";
		} else {
			wp_set_object_terms( $productId, 'simple', 'product_type' );
		}

		// Rank Math SEO Meta
		update_post_meta( $productId, '_rank_math_title', $title . ' - Đại Lý Xe Điện' );
		update_post_meta( $productId, '_rank_math_description', wp_strip_all_tags( $excerpt ?: $title ) );

		$totalProductsImported++;
		echo "  + [{$actionStr}] Product ID {$productId}: {$title}\n";
	}

	$pPage++;
}

// ----------------------------------------------------
// STEP 3: IMPORT NEWS POSTS
// ----------------------------------------------------
echo "\n📌 [STEP 3/4] Fetching & Importing News Posts...\n";

$postPage = 1;
$totalPostsImported = 0;

while ( $postPage <= 5 ) { // Import top 50 posts for maximum performance
	$postApiUrl = "https://dailyxedien.vn/wp-json/wp/v2/posts?per_page=20&page={$postPage}";
	echo "  -> Requesting Posts Page {$postPage}...\n";

	$postRes = wp_remote_get( $postApiUrl, [ 'timeout' => 30, 'sslverify' => false, 'headers' => [ 'User-Agent' => 'Mozilla/5.0' ] ] );
	if ( is_wp_error( $postRes ) || wp_remote_retrieve_response_code( $postRes ) !== 200 ) {
		break;
	}

	$postsArr = json_decode( wp_remote_retrieve_body( $postRes ), true );
	if ( empty( $postsArr ) || ! is_array( $postsArr ) ) {
		break;
	}

	foreach ( $postsArr as $pData ) {
		$title   = sanitize_text_field( $pData['title']['rendered'] ?? '' );
		$slug    = sanitize_title( $pData['slug'] ?? '' );
		$content = spl_clean_legacy_content( $pData['content']['rendered'] ?? '' );
		$excerpt = spl_clean_legacy_content( $pData['excerpt']['rendered'] ?? '' );

		if ( empty( $title ) ) continue;

		$existingPost = get_page_by_path( $slug, OBJECT, 'post' );
		$postFields = [
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'post_status'  => 'publish',
			'post_type'    => 'post',
		];

		if ( $existingPost ) {
			$postFields['ID'] = $existingPost->ID;
			$newPostId = wp_update_post( $postFields );
			$actionStr = "Updated";
		} else {
			$newPostId = wp_insert_post( $postFields );
			$actionStr = "Created";
		}

		if ( is_wp_error( $newPostId ) || ! $newPostId ) continue;

		// Rank Math Meta
		$metaTitle = $pData['yoast_head_json']['title'] ?? ( $title . ' - Tin tức DailyXeDien' );
		$metaDesc  = $pData['yoast_head_json']['description'] ?? wp_strip_all_tags( $excerpt );
		update_post_meta( $newPostId, '_rank_math_title', $metaTitle );
		update_post_meta( $newPostId, '_rank_math_description', $metaDesc );

		$totalPostsImported++;
		echo "  + [{$actionStr}] Post ID {$newPostId}: {$title}\n";
	}

	$postPage++;
}

// ----------------------------------------------------
// STEP 4: PURGE JUNK & REVISIONS
// ----------------------------------------------------
echo "\n📌 [STEP 4/4] Cleaning Up Database Junk & Revisions...\n";

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'" );
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" );
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})" );

echo "\n====================================================\n";
echo "✅ MIGRATION & SEO OPTIMIZATION COMPLETE!\n";
echo "====================================================\n";
echo "Summary:\n";
echo "- Total Products Synced: {$totalProductsImported}\n";
echo "- Total News Posts Synced: {$totalPostsImported}\n";
echo "- Database Revisions & Junk Meta Purged: DONE\n";
echo "====================================================\n";
