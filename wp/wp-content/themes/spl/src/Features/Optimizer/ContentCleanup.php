<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

/**
 * Content Cleanup — 301 redirects for discontinued products.
 *
 * When a visitor hits a single product page whose status is `private`,
 * `draft`, or `trash`, this redirects them to the product's primary
 * category archive (or the shop page) with a 301.
 *
 * This prevents 404 errors for legacy URLs after hiding discontinued
 * products during the content cleanup phase.
 *
 * @package SPL
 */
final class ContentCleanup {

	public static function register(): void {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		add_action( 'template_redirect', [ self::class, 'redirectDiscontinued' ], 5 );
	}

	/**
	 * Redirect discontinued product pages to their category archive.
	 */
	public static function redirectDiscontinued(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! is_404() ) {
			return;
		}

		// Check if the 404 URL matches a product slug.
		$slug = get_query_var( 'product' );
		if ( ! $slug ) {
			return;
		}

		// Look for hidden/trashed/drafted products by slug.
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( ! $post ) {
			return;
		}

		// Only redirect non-public statuses.
		if ( in_array( $post->post_status, [ 'publish' ], true ) ) {
			return;
		}

		$redirect_url = self::getCategoryUrl( $post->ID );

		wp_safe_redirect( esc_url_raw( $redirect_url ), 301 );
		exit;
	}

	/**
	 * Get the primary product category URL, falling back to shop page.
	 */
	private static function getCategoryUrl( int $product_id ): string {
		$terms = get_the_terms( $product_id, 'product_cat' );

		if ( $terms && ! is_wp_error( $terms ) ) {
			// Prefer the first non-"uncategorised" term.
			foreach ( $terms as $term ) {
				if ( 'uncategorised' !== $term->slug && 'uncategorised-vi' !== $term->slug ) {
					return get_term_link( $term );
				}
			}
		}

		// Fall back to shop page.
		$shop_id = wc_get_page_id( 'shop' );
		if ( $shop_id > 0 ) {
			return get_permalink( $shop_id );
		}

		return home_url( '/' );
	}
}
