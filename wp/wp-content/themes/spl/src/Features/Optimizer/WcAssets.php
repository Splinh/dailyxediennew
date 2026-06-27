<?php
/**
 * WooCommerce Asset Optimizer — dequeue unused WC scripts/styles on non-shop pages.
 *
 * WooCommerce loads ~7 extra JS/CSS files on EVERY page even when not needed.
 * This dequeues them on non-WC pages (home, about, contact, blog) while keeping
 * them on shop, product, cart, checkout, and account pages.
 *
 * @package SPL\Features\Optimizer
 */

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

final class WcAssets {

	/**
	 * Register the dequeue hook.
	 */
	public static function register(): void {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ self::class, 'dequeueUnusedAssets' ], 999 );
	}

	/**
	 * Dequeue WC assets on pages that don't need them.
	 */
	public static function dequeueUnusedAssets(): void {
		// Keep WC assets on WC pages (shop, product, cart, checkout, account).
		if (
			is_woocommerce()
			|| is_cart()
			|| is_checkout()
			|| is_account_page()
		) {
			return;
		}

		// Scripts to dequeue on non-WC pages.
		$scripts = [
			'wc-add-to-cart',
			'wc-cart-fragments',
			'woocommerce',
			'wc-order-attribution',
			'sourcebuster-js',
			'js-cookie',
			'jquery-blockui',
			'wp-block-library',
		];

		// Styles to dequeue on non-WC pages.
		$styles = [
			'wc-blocks-style',
			'wc-blocks-vendors-style',
			'wc-blocks-style-active-filters',
			'wc-blocks-style-price-filter',
			'wc-blocks-style-attribute-filter',
			'wc-blocks-style-stock-filter',
			'woocommerce-general',
			'woocommerce-layout',
			'woocommerce-smallscreen',
			'wp-block-library',
		];

		foreach ( $scripts as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}

		foreach ( $styles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}
}
