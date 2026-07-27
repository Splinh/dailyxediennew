<?php
/**
 * CLI Script: Update & Sync Site Logo across WordPress Customizer & ACF options.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

echo "=== START UPDATE SITE LOGO CLI ===" . PHP_EOL;

// 1. Search for primary brand logo attachment in media library
$logo_attachment_id = 0;

// Search for 'Logo-tong-hop' or 'dailyxedien' or latest logo image
$logos = get_posts( [
	'post_type'      => 'attachment',
	'post_status'    => 'inherit',
	'posts_per_page' => 10,
	'orderby'        => 'ID',
	'order'          => 'DESC',
	's'              => 'Logo-tong-hop',
] );

if ( empty( $logos ) ) {
	$logos = get_posts( [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 10,
		'orderby'        => 'ID',
		'order'          => 'DESC',
		's'              => 'logo',
	] );
}

if ( ! empty( $logos ) ) {
	// Prefer Logo-tong-hop if present
	foreach ( $logos as $l ) {
		if ( false !== stripos( $l->post_title, 'tong-hop' ) || false !== stripos( $l->post_name, 'tong-hop' ) ) {
			$logo_attachment_id = $l->ID;
			break;
		}
	}
	if ( ! $logo_attachment_id ) {
		$logo_attachment_id = $logos[0]->ID;
	}
	echo "✓ Found logo attachment ID: {$logo_attachment_id}" . PHP_EOL;
} else {
	$logo_attachment_id = get_theme_mod( 'custom_logo' );
}

if ( $logo_attachment_id ) {
	// Set customizer theme mod
	set_theme_mod( 'custom_logo', (int) $logo_attachment_id );
	echo "✓ Set theme_mod 'custom_logo' => $logo_attachment_id" . PHP_EOL;

	// Set ACF option fields
	if ( function_exists( 'update_field' ) ) {
		update_field( 'site_logo', (int) $logo_attachment_id, 'option' );
		update_field( 'logo_header', (int) $logo_attachment_id, 'option' );
		update_field( 'logo_footer', (int) $logo_attachment_id, 'option' );
		echo "✓ Set ACF option fields ('site_logo', 'logo_header', 'logo_footer') => $logo_attachment_id" . PHP_EOL;
	}

	$url = wp_get_attachment_url( $logo_attachment_id );
	echo "✓ Logo Image URL: $url" . PHP_EOL;
} else {
	echo "⚠ No logo attachment found." . PHP_EOL;
}

flush_rewrite_rules();
echo "=== FINISHED UPDATE SITE LOGO CLI ===" . PHP_EOL;
