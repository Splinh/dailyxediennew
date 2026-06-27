<?php
/**
 * Lightweight product list caching for homepage product sections.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_update_product', 'spl_clear_product_transients', 10, 0 );
add_action( 'woocommerce_new_product', 'spl_clear_product_transients', 10, 0 );
add_action( 'woocommerce_delete_product', 'spl_clear_product_transients', 10, 0 );
add_action( 'woocommerce_trash_product', 'spl_clear_product_transients', 10, 0 );
add_action( 'save_post_product', 'spl_clear_product_transients', 10, 0 );
add_action( 'set_object_terms', 'spl_clear_product_transients_on_terms', 10, 4 );

function spl_product_cache_context(): string {
	$language = get_locale();
	if ( function_exists( 'pll_current_language' ) ) {
		$pll_language = pll_current_language( 'slug' );
		if ( $pll_language ) {
			$language = $pll_language;
		}
	}

	return (string) $language;
}

function spl_product_ids_cache_key( string $prefix, array $parts ): string {
	$parts['context'] = spl_product_cache_context();
	$parts['version'] = spl_product_cache_version();

	return $prefix . '_' . md5( (string) wp_json_encode( $parts ) );
}

function spl_product_cache_version(): int {
	return (int) get_option( 'spl_product_cache_version', 1 );
}

/**
 * @return array<int>|false
 */
function spl_get_cached_product_ids( string $cache_key ): array|false {
	$product_ids = get_transient( $cache_key );
	if ( ! is_array( $product_ids ) ) {
		return false;
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
}

/**
 * @param array<int> $product_ids
 */
function spl_set_cached_product_ids( string $cache_key, array $product_ids, int $ttl ): void {
	set_transient(
		$cache_key,
		array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) ),
		$ttl
	);
}

/**
 * @param array<int> $product_ids
 */
function spl_prime_product_card_caches( array $product_ids ): void {
	$product_ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
	if ( empty( $product_ids ) ) {
		return;
	}

	if ( function_exists( '_prime_post_caches' ) ) {
		_prime_post_caches( $product_ids, false, true );
	} else {
		update_meta_cache( 'post', $product_ids );
	}

	update_object_term_cache( $product_ids, 'product' );
}

/**
 * Get top-level product categories — single query, multiple consumers.
 *
 * Called from header.php (desktop + mobile) and categories.php.
 * Uses wp_cache to avoid redundant get_terms within the same request.
 *
 * @param int $limit Max categories to return. 0 = no limit.
 * @return \WP_Term[]
 */
function spl_get_product_categories( int $limit = 0 ): array {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return [];
	}

	$cache_key = 'spl_product_cats_top';
	$cached    = wp_cache_get( $cache_key, 'spl' );

	if ( false === $cached ) {
		$cached = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'parent'     => 0,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
			'number'     => 20,
		] );

		if ( is_wp_error( $cached ) ) {
			$cached = [];
		}

		wp_cache_set( $cache_key, $cached, 'spl' );
	}

	if ( $limit > 0 && count( $cached ) > $limit ) {
		return array_slice( $cached, 0, $limit );
	}

	return $cached;
}

function spl_clear_product_transients(): void {
	global $wpdb;

	update_option( 'spl_product_cache_version', (string) time(), false );

	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '_transient_spl_products_%'
		    OR option_name LIKE '_transient_timeout_spl_products_%'
		    OR option_name LIKE '_transient_spl_flash_sale_%'
		    OR option_name LIKE '_transient_timeout_spl_flash_sale_%'"
	);
}

function spl_clear_product_transients_on_terms( int $object_id, mixed $terms, mixed $tt_ids, string $taxonomy ): void {
	unset( $terms, $tt_ids );

	if ( 'product_cat' !== $taxonomy && 'product' !== get_post_type( $object_id ) ) {
		return;
	}

	spl_clear_product_transients();
}
