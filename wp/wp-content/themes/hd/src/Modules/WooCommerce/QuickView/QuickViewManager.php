<?php
/**
 * Quick View Manager — sub-feature entry point.
 *
 * Handles Quick View popup loading and AJAX add-to-cart for the popup context.
 * Implements HasAPI for REST endpoint registration.
 *
 * @package HD\Modules\WooCommerce\QuickView
 */

namespace HD\Modules\WooCommerce\QuickView;

use HD\Modules\WooCommerce\Contracts\HasAPI;
use HD\Modules\WooCommerce\Contracts\WooFeatureInterface;

defined( 'ABSPATH' ) || exit;

final class QuickViewManager implements WooFeatureInterface, HasAPI {

	public static function slug(): string {
		return 'quick_view';
	}

	public function register(): void {

		// Add Quick View button to product loop
		// (also lazily enqueues variation form JS on first render — see renderLoopButton)
		add_action( 'woocommerce_before_shop_loop_item_title', [ self::class, 'renderLoopButton' ], 8 );

		// AJAX add-to-cart handler for popup (supports simple, variable, grouped)
		add_action(
			'wc_ajax_hd_quickview_add_cart',
			[ self::class, 'handleAjaxAddToCart' ]
		);

		// Cache invalidation on product update
		add_action( 'clean_post_cache', [ self::class, 'invalidateCache' ], 10, 2 );

		// WC stock updates (e.g. order processing) might not trigger clean_post_cache
		add_action( 'woocommerce_product_set_stock', [ self::class, 'handleStockChange' ] );
	}

	/**
	 * Invalidate QuickView cache on stock change.
	 * Hooked to `woocommerce_product_set_stock`.
	 *
	 * @param \WC_Product $product Product with stock change.
	 */
	public static function handleStockChange( \WC_Product $product ): void {
		$productId = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		self::deleteProductCache( $productId );
	}

	public static function apiClasses(): array {
		return [ API\QuickViewAPI::class ];
	}

	// ── AJAX Add-to-Cart ───────────────────────────

	/**
	 * Handle AJAX add-to-cart from QuickView popup.
	 *
	 * Supports simple, variable, and grouped product types.
	 * Simple products only have `name="add-to-cart"` on the submit button
	 * (no `product_id` input), so we fall back to that value.
	 */
	public static function handleAjaxAddToCart(): void {

		// Verify nonce from X-WP-Nonce header (sent by quickview.js).
		$nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			wp_send_json_error( [ 'error' => __( 'Security check failed.', 'hd' ) ], 403 );
		}

		// Product ID: variable form has hidden input `product_id`,
		// simple form only has submit button `name="add-to-cart" value="$id"`.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via header.
		$productId = absint( $_POST['product_id'] ?? $_POST['add-to-cart'] ?? 0 );
		$productId = apply_filters( 'woocommerce_add_to_cart_product_id', $productId );

		if ( ! $productId ) {
			wp_send_json_error( [ 'error' => __( 'Invalid product.', 'hd' ) ] );
		}

		$product = wc_get_product( $productId );
		if ( ! $product ) {
			wp_send_json_error( [ 'error' => __( 'Product not found.', 'hd' ) ] );
		}

		// Grouped products: quantity[child_id] array — add each child separately
		if ( $product->is_type( 'grouped' ) ) {
			self::handleGroupedAddToCart( $product );
			return;
		}

		// Simple & Variable products
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified at method entry.
		$quantity = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$variationId = absint( $_POST['variation_id'] ?? 0 );

		$variation = [];
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		foreach ( $_POST as $key => $value ) {
			if ( is_string( $key ) && str_starts_with( $key, 'attribute_' ) ) {
				$variation[ sanitize_text_field( $key ) ] = wc_clean( wp_unslash( $value ) );
			}
		}

		$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $productId, $quantity, $variationId, $variation );
		if ( ! $passed ) {
			$errors = wc_get_notices( 'error' );
			wc_clear_notices();
			wp_send_json_error( [ 'error' => $errors ] );
		}

		$cartItemKey = WC()->cart->add_to_cart( $productId, $quantity, $variationId, $variation );
		if ( ! $cartItemKey ) {
			$errors = wc_get_notices( 'error' );
			wc_clear_notices();
			wp_send_json_error( [ 'error' => $errors ] );
		}

		\WC_AJAX::get_refreshed_fragments();
	}

	/**
	 * Handle grouped product add-to-cart.
	 *
	 * Grouped form sends `quantity[child_id] = qty` for each child product.
	 * Mirrors WC_Form_Handler::add_to_cart_action() grouped logic.
	 *
	 * @param \WC_Product $parentProduct The grouped parent product.
	 */
	private static function handleGroupedAddToCart( \WC_Product $parentProduct ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handleAjaxAddToCart.
		$quantities = isset( $_POST['quantity'] ) && is_array( $_POST['quantity'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			? array_map( 'intval', $_POST['quantity'] )
			: [];

		$added = false;
		foreach ( $quantities as $childId => $qty ) {
			if ( $qty <= 0 ) {
				continue;
			}

			$childId = absint( $childId );
			$passed  = apply_filters( 'woocommerce_add_to_cart_validation', true, $childId, $qty );
			if ( ! $passed ) {
				continue;
			}

			$result = WC()->cart->add_to_cart( $childId, $qty );
			if ( false !== $result ) {
				$added = true;
			}
		}

		if ( ! $added ) {
			$errors = wc_get_notices( 'error' );
			wc_clear_notices();
			wp_send_json_error( [ 'error' => $errors ?: [ __( 'Please choose a quantity.', 'hd' ) ] ] );
		}

		\WC_AJAX::get_refreshed_fragments();
	}


	// ── Loop Button ─────────────────────────────────

	/**
	 * Render Quick View button in product loop (inside thumbnail wrapper).
	 * Also lazily enqueues variation form JS on first render — WC only loads
	 * it on single product, but QuickView needs it wherever products appear.
	 */
	public static function renderLoopButton(): void {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		// Enqueue variation JS once per request (footer scripts still accept enqueues during template rendering)
		static $variationEnqueued = false;
		if ( ! $variationEnqueued ) {
			wp_enqueue_script( 'wc-add-to-cart-variation' );
			$variationEnqueued = true;
		}

		printf(
			'<button type="button" class="hd-quick-view-btn" data-wc-quickview data-product-id="%d" aria-label="%s">%s</button>',
			absint( $product->get_id() ),
			esc_attr__( 'Quick View', 'hd' ),
			esc_html__( 'Quick View', 'hd' )
		);
	}

	// ── Cache Invalidation ──────────────────────────

	/**
	 * Invalidate Quick View transient when product is updated.
	 * `clean_post_cache` fires on ALL update paths (save, trash, REST, stock, bulk).
	 *
	 * @param int      $postId Post ID.
	 * @param \WP_Post $post   Post object.
	 */
	public static function invalidateCache( int $postId, \WP_Post $post ): void {
		if ( 'product' !== $post->post_type && 'product_variation' !== $post->post_type ) {
			return;
		}

		$productId = ( 'product_variation' === $post->post_type ) ? $post->post_parent : $postId;
		self::deleteProductCache( $productId );
	}

	private static function deleteProductCache( int $productId ): void {
		delete_transient( API\QuickViewAPI::legacyCacheKey( $productId ) );

		$indexKey = API\QuickViewAPI::cacheIndexKey( $productId );
		$keys     = get_transient( $indexKey );
		if ( is_array( $keys ) ) {
			foreach ( $keys as $key ) {
				if ( is_string( $key ) && '' !== $key ) {
					delete_transient( $key );
				}
			}
		}

		delete_transient( $indexKey );
	}
}
