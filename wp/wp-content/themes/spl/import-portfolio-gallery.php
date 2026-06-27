<?php
/**
 * Import Portfolio Gallery from WordPress WXR XML → dxd_gallery CPT.
 *
 * Usage: Run on the NEW site via WP-CLI:
 *   wp eval-file import-portfolio-gallery.php
 *   wp eval-file import-portfolio-gallery.php --skip-images
 *   wp eval-file import-portfolio-gallery.php /path/to/custom-export.xml
 *
 * Default input: wp-content/exports/flatsome-portfolio.xml
 *
 * This script:
 * 1. Parses WordPress WXR XML export of `featured_item` CPT
 * 2. Creates `dxd_gallery_cat` terms (VI only, deduplicating en slugs)
 * 3. Creates `dxd_gallery` posts with title, slug, menu_order, date
 * 4. Sideloads thumbnail images from the old site URL
 * 5. Assigns taxonomy terms to posts
 *
 * Idempotent: skips posts/terms that already exist (matched by slug).
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

// ── Config ──────────────────────────────────────────

$target_cpt     = 'dxd_gallery';
$target_taxonomy = 'dxd_gallery_cat';
$default_xml     = WP_CONTENT_DIR . '/exports/flatsome-portfolio.xml';
$skip_images     = in_array( '--skip-images', $args ?? [], true );

// Allow custom path as first positional arg.
$xml_path = $default_xml;
foreach ( $args ?? [] as $arg ) {
	if ( strpos( $arg, '--' ) !== 0 && file_exists( $arg ) ) {
		$xml_path = $arg;
		break;
	}
}

if ( ! file_exists( $xml_path ) ) {
	WP_CLI::error( "XML file not found: {$xml_path}" );
}

// ── Parse WXR XML ───────────────────────────────────

libxml_use_internal_errors( true );
$xml = simplexml_load_file( $xml_path );

if ( ! $xml ) {
	WP_CLI::error( 'Failed to parse XML.' );
}

$channel    = $xml->channel;
$namespaces = $xml->getNamespaces( true );
$wp_ns      = $namespaces['wp'] ?? 'http://wordpress.org/export/1.2/';
$dc_ns      = $namespaces['dc'] ?? 'http://purl.org/dc/elements/1.1/';

WP_CLI::log( 'Source: ' . (string) $channel->link );

// ── Ensure media functions ──────────────────────────

if ( ! function_exists( 'media_sideload_image' ) ) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
}

// ── Pass 1: Collect categories & attachment URLs ────

// Map: old_post_id => attachment_url (for thumbnails).
$attachment_map = [];
// Map: old_post_id => post data.
$featured_items = [];

foreach ( $channel->item as $item ) {
	$wp = $item->children( $wp_ns );
	$post_type = (string) $wp->post_type;

	if ( 'attachment' === $post_type ) {
		$old_id = (int) $wp->post_id;
		$att_url = (string) $wp->attachment_url;
		if ( $old_id && $att_url ) {
			$attachment_map[ $old_id ] = $att_url;
		}
		continue;
	}

	if ( 'featured_item' !== $post_type ) {
		continue;
	}

	// Only import Vietnamese posts (skip EN translations).
	$lang = '';
	foreach ( $item->category as $cat ) {
		if ( (string) $cat['domain'] === 'language' ) {
			$lang = (string) $cat['nicename'];
			break;
		}
	}
	if ( $lang && 'vi' !== $lang ) {
		continue;
	}

	// Extract category slugs.
	$cat_slugs = [];
	foreach ( $item->category as $cat ) {
		$domain = (string) $cat['domain'];
		if ( 'featured_item_category' === $domain ) {
			$cat_slugs[] = [
				'slug' => (string) $cat['nicename'],
				'name' => (string) $cat,
			];
		}
	}

	// Extract thumbnail ID from postmeta.
	$thumb_id = 0;
	foreach ( $wp->postmeta as $meta ) {
		if ( '_thumbnail_id' === (string) $meta->meta_key ) {
			$thumb_id = (int) $meta->meta_value;
			break;
		}
	}

	$featured_items[] = [
		'old_id'     => (int) $wp->post_id,
		'title'      => (string) $item->title,
		'slug'       => (string) $wp->post_name,
		'date'       => (string) $wp->post_date,
		'status'     => (string) $wp->status,
		'menu_order' => (int) $wp->menu_order,
		'thumb_id'   => $thumb_id,
		'categories' => $cat_slugs,
	];
}

WP_CLI::log( sprintf(
	'Parsed: %d featured_items (VI), %d attachments',
	count( $featured_items ),
	count( $attachment_map )
) );

// ── Pass 2: Create taxonomy terms ───────────────────

$term_map = []; // slug => new_term_id

// Collect unique categories from items.
$unique_cats = [];
foreach ( $featured_items as $fi ) {
	foreach ( $fi['categories'] as $cat ) {
		$unique_cats[ $cat['slug'] ] = $cat['name'];
	}
}

foreach ( $unique_cats as $slug => $name ) {
	$existing = get_term_by( 'slug', $slug, $target_taxonomy );
	if ( $existing ) {
		$term_map[ $slug ] = $existing->term_id;
		WP_CLI::log( "  ⏭ Term exists: {$name} ({$slug})" );
		continue;
	}

	$result = wp_insert_term( $name, $target_taxonomy, [ 'slug' => $slug ] );
	if ( is_wp_error( $result ) ) {
		WP_CLI::warning( "  ✗ Term '{$name}': " . $result->get_error_message() );
		continue;
	}

	$term_map[ $slug ] = $result['term_id'];
	WP_CLI::log( "  ✓ Created term: {$name} (ID: {$result['term_id']})" );
}

WP_CLI::success( sprintf( 'Categories: %d mapped.', count( $term_map ) ) );

// ── Pass 3: Create posts ────────────────────────────

$created  = 0;
$skipped  = 0;
$img_ok   = 0;
$img_fail = 0;
$total    = count( $featured_items );

foreach ( $featured_items as $i => $fi ) {
	$slug  = sanitize_title( $fi['slug'] );
	$title = sanitize_text_field( $fi['title'] );
	$num   = $i + 1;

	// Skip non-publish.
	if ( 'publish' !== $fi['status'] ) {
		WP_CLI::log( "  [{$num}/{$total}] ⏭ Not published: {$title}" );
		$skipped++;
		continue;
	}

	// Check existing by slug.
	$existing = get_page_by_path( $slug, OBJECT, $target_cpt );
	if ( $existing ) {
		WP_CLI::log( "  [{$num}/{$total}] ⏭ Exists: {$title}" );
		$skipped++;
		continue;
	}

	$post_id = wp_insert_post( [
		'post_type'   => $target_cpt,
		'post_title'  => $title,
		'post_name'   => $slug,
		'post_status' => 'publish',
		'menu_order'  => $fi['menu_order'],
		'post_date'   => $fi['date'],
	], true );

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "  [{$num}/{$total}] ✗ Failed: {$title}" );
		continue;
	}

	// Assign taxonomy terms.
	$term_ids = [];
	foreach ( $fi['categories'] as $cat ) {
		if ( isset( $term_map[ $cat['slug'] ] ) ) {
			$term_ids[] = $term_map[ $cat['slug'] ];
		}
	}
	if ( $term_ids ) {
		wp_set_object_terms( $post_id, $term_ids, $target_taxonomy );
	}

	// Sideload thumbnail.
	if ( ! $skip_images && $fi['thumb_id'] && isset( $attachment_map[ $fi['thumb_id'] ] ) ) {
		$thumb_url = $attachment_map[ $fi['thumb_id'] ];
		$attach_id = media_sideload_image( $thumb_url, $post_id, $title, 'id' );
		if ( is_wp_error( $attach_id ) ) {
			WP_CLI::warning( "    ⚠ Image failed: " . $attach_id->get_error_message() );
			$img_fail++;
		} else {
			set_post_thumbnail( $post_id, $attach_id );
			$img_ok++;
		}
	}

	WP_CLI::log( "  [{$num}/{$total}] ✓ Created: {$title} (ID: {$post_id})" );
	$created++;
}

// ── Summary ─────────────────────────────────────────

WP_CLI::success( sprintf(
	'Import complete: %d created, %d skipped, %d images OK, %d images failed (of %d total).',
	$created,
	$skipped,
	$img_ok,
	$img_fail,
	$total
) );
