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
			'meta_key'   => 'order',
			'orderby'    => 'meta_value_num',
			'order'      => 'ASC',
			'number'     => 25,
		] );

		if ( is_wp_error( $cached ) ) {
			$cached = [];
		}

		// Filter out accessories/spare parts/battery categories from top primary menu
		$exclude_keywords = [ 'ac-quy', 'phu-tung', 'pin', 'battery', 'gio-xe', 'phu-kien', 'linh-kien' ];
		$cached = array_values( array_filter( (array) $cached, function( $term ) use ( $exclude_keywords ) {
			if ( ! isset( $term->slug ) ) return true;
			foreach ( $exclude_keywords as $kw ) {
				if ( str_contains( strtolower( $term->slug ), $kw ) ) {
					return false;
				}
			}
			return true;
		} ) );

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

/**
 * Get child sub-categories for a parent product category.
 *
 * @param int $parent_id Parent term ID.
 * @return \WP_Term[]
 */
function spl_get_product_sub_categories( int $parent_id ): array {
	if ( ! function_exists( 'is_woocommerce' ) || $parent_id <= 0 ) {
		return [];
	}

	$cache_key = 'spl_product_subcats_' . $parent_id;
	$cached    = wp_cache_get( $cache_key, 'spl' );

	if ( false === $cached ) {
		$cached = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'parent'     => $parent_id,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
			'number'     => 10,
		] );

		if ( is_wp_error( $cached ) ) {
			$cached = [];
		}

		wp_cache_set( $cache_key, $cached, 'spl' );
	}

	return $cached;
}

/**
 * Get featured products for Mega Menu dropdown.
 *
 * @param int $count Number of products to fetch.
 * @return array Array of product data arrays.
 */
function spl_get_mega_menu_products( int $count = 3 ): array {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return [];
	}

	$cache_key = 'spl_mega_products_' . $count;
	$cached    = wp_cache_get( $cache_key, 'spl' );

	if ( false === $cached ) {
		$products = wc_get_products( [
			'limit'        => $count,
			'status'       => 'publish',
			'stock_status' => 'instock',
			'orderby'      => 'menu_order date',
			'order'        => 'ASC',
		] );

		$cached = [];
		foreach ( $products as $product ) {
			$image_id  = $product->get_image_id();
			$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src() : '' );
			
			$regular_price = (float) $product->get_regular_price();
			$price         = (float) $product->get_price();
			
			$discount = '';
			if ( $regular_price > $price && $regular_price > 0 ) {
				$discount = '-' . round( ( ( $regular_price - $price ) / $regular_price ) * 100 ) . '%';
			}

			$cached[] = [
				'id'            => $product->get_id(),
				'name'          => $product->get_name(),
				'url'           => $product->get_permalink(),
				'image'         => $image_url,
				'price_html'    => $product->get_price_html(),
				'price'         => $price,
				'regular_price' => $regular_price,
				'discount'      => $discount,
			];
		}

		wp_cache_set( $cache_key, $cached, 'spl' );
	}

	return $cached;
}

/**
 * Get featured posts for News Mega Menu dropdown.
 *
 * @param int $count Number of posts to fetch.
 * @return array Array of post data arrays.
 */
function spl_get_mega_menu_posts( int $count = 3 ): array {
	$cache_key = 'spl_mega_posts_' . $count;
	$cached    = wp_cache_get( $cache_key, 'spl' );

	if ( false === $cached ) {
		$posts = get_posts( [
			'post_type'      => 'post',
			'posts_per_page' => $count,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$cached = [];
		foreach ( $posts as $post ) {
			$cats      = get_the_category( $post->ID );
			$cat_name  = ! empty( $cats ) ? $cats[0]->name : __( 'Tin tức', 'spl' );
			$thumb_url = get_the_post_thumbnail_url( $post->ID, 'medium' );

			$cached[] = [
				'id'       => $post->ID,
				'title'    => get_the_title( $post ),
				'url'      => get_permalink( $post ),
				'image'    => $thumb_url,
				'date'     => get_the_date( 'd/m/Y', $post ),
				'category' => $cat_name,
			];
		}

		wp_cache_set( $cache_key, $cached, 'spl' );
	}

	return $cached;
}

/**
 * Get products for a specific category for Mega Menu hover panels.
 *
 * @param int $cat_id Product category term ID.
 * @param int $count Number of products to fetch.
 * @return array Array of product data arrays.
 */
function spl_get_mega_menu_products_by_cat( int $cat_id, int $count = 3 ): array {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return [];
	}

	$cache_key = 'spl_mega_cat_prods_v3_' . $cat_id . '_' . $count;
	$cached    = wp_cache_get( $cache_key, 'spl' );

	if ( false === $cached ) {
		$tax_query = [
			[
				'taxonomy' => 'product_visibility',
				'field'    => 'slug',
				'terms'    => [ 'outofstock' ],
				'operator' => 'NOT IN',
			],
		];
		if ( $cat_id > 0 ) {
			$cat_term_ids = [ $cat_id ];
			$child_ids    = get_term_children( $cat_id, 'product_cat' );
			if ( ! is_wp_error( $child_ids ) && ! empty( $child_ids ) ) {
				$cat_term_ids = array_merge( $cat_term_ids, $child_ids );
			}
			$tax_query[] = [
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => $cat_term_ids,
				'include_children' => true,
			];
		}

		$query_args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				],
			],
			'tax_query'      => $tax_query,
		];

		$prod_query = new \WP_Query( $query_args );

		// Fallback to in-stock products if category query is empty
		if ( ! $prod_query->have_posts() && $cat_id > 0 ) {
			$prod_query = new \WP_Query( [
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $count,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_query'     => [
					[
						'key'     => '_stock_status',
						'value'   => 'instock',
						'compare' => '=',
					],
				],
				'tax_query'      => [
					[
						'taxonomy' => 'product_visibility',
						'field'    => 'slug',
						'terms'    => [ 'outofstock' ],
						'operator' => 'NOT IN',
					],
				],
			] );
		}

		$cached = [];
		if ( $prod_query->have_posts() ) {
			while ( $prod_query->have_posts() ) {
				$prod_query->the_post();
				$product = wc_get_product( get_the_ID() );
				if ( ! $product ) {
					continue;
				}
				$image_id      = $product->get_image_id();
				$image_url     = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src() : '' );
				$regular_price = (float) $product->get_regular_price();
				$price         = (float) $product->get_price();

				$discount = '';
				if ( $regular_price > $price && $regular_price > 0 ) {
					$discount = '-' . round( ( ( $regular_price - $price ) / $regular_price ) * 100 ) . '%';
				}

				$cached[] = [
					'id'            => $product->get_id(),
					'name'          => $product->get_name(),
					'url'           => $product->get_permalink(),
					'image'         => $image_url,
					'price_html'    => $product->get_price_html(),
					'price'         => $price,
					'regular_price' => $regular_price,
					'discount'      => $discount,
				];
			}
			wp_reset_postdata();
		}

		wp_cache_set( $cache_key, $cached, 'spl', 3600 );
	}

	return $cached;
}
