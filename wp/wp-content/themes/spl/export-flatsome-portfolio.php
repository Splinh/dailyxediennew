<?php
/**
 * Export Flatsome Portfolio (featured_item) → JSON.
 *
 * NOTE: Prefer using WordPress Admin → Tools → Export → Featured Items
 * to export as WXR XML instead. The import script supports both formats.
 *
 * Usage (alternative via WP-CLI on OLD Flatsome site):
 *   wp eval-file export-flatsome-portfolio.php
 *
 * Output: wp-content/exports/flatsome-portfolio.json
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

// ── Config ──────────────────────────────────────────

$source_cpt      = 'featured_item';
$source_taxonomy  = 'featured_item_category';
$output_dir       = WP_CONTENT_DIR . '/exports';
$output_file      = $output_dir . '/flatsome-portfolio.json';

// ── Guard: verify CPT exists ────────────────────────

if ( ! post_type_exists( $source_cpt ) ) {
	WP_CLI::error( "CPT '{$source_cpt}' not found. Are you running this on the Flatsome site?" );
}

// ── Export categories ───────────────────────────────

$terms      = get_terms( [
	'taxonomy'   => $source_taxonomy,
	'hide_empty' => false,
	'orderby'    => 'term_id',
	'order'      => 'ASC',
] );
$categories = [];

if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term ) {
		$parent_slug = '';
		if ( $term->parent ) {
			$parent_term = get_term( $term->parent, $source_taxonomy );
			$parent_slug = ( $parent_term && ! is_wp_error( $parent_term ) ) ? $parent_term->slug : '';
		}

		$categories[] = [
			'term_id'     => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'parent'      => $term->parent,
			'parent_slug' => $parent_slug,
			'count'       => $term->count,
		];
	}
}

WP_CLI::log( sprintf( 'Found %d categories.', count( $categories ) ) );

// ── Export posts ────────────────────────────────────

$posts = get_posts( [
	'post_type'      => $source_cpt,
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'orderby'        => 'menu_order date',
	'order'          => 'ASC',
] );

$items = [];

foreach ( $posts as $post ) {
	$thumb_id  = get_post_thumbnail_id( $post->ID );
	$thumb_url = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';

	// Get assigned category slugs.
	$post_terms  = wp_get_object_terms( $post->ID, $source_taxonomy, [ 'fields' => 'slugs' ] );
	$cat_slugs   = is_wp_error( $post_terms ) ? [] : $post_terms;

	$items[] = [
		'id'            => $post->ID,
		'title'         => $post->post_title,
		'slug'          => $post->post_name,
		'thumbnail_url' => $thumb_url,
		'thumbnail_id'  => (int) $thumb_id,
		'content'       => wp_strip_all_tags( $post->post_content ),
		'categories'    => $cat_slugs,
		'menu_order'    => $post->menu_order,
		'date'          => $post->post_date,
	];
}

WP_CLI::log( sprintf( 'Found %d items.', count( $items ) ) );

// ── Write JSON ──────────────────────────────────────

$data = [
	'exported_at' => wp_date( 'c' ),
	'source_site' => home_url(),
	'categories'  => $categories,
	'items'       => $items,
];

if ( ! is_dir( $output_dir ) ) {
	wp_mkdir_p( $output_dir );
}

$json    = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
$written = file_put_contents( $output_file, $json ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

if ( false === $written ) {
	WP_CLI::error( "Failed to write JSON to {$output_file}" );
}

WP_CLI::success( sprintf(
	'Exported %d categories + %d items → %s (%s)',
	count( $categories ),
	count( $items ),
	$output_file,
	size_format( $written )
) );
