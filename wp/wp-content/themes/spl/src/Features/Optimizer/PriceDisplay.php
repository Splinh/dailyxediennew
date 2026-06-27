<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

/**
 * Customize WooCommerce price display for Vietnamese e-commerce.
 *
 * - Show "Liên hệ" when a product has no price set.
 * - Show only the lowest variant price (not "From X – To Y" range)
 *   for variable products.
 *
 * @package SPL
 */
final class PriceDisplay {

	public static function register(): void {
		add_filter( 'woocommerce_empty_price_html', [ self::class, 'contactPrice' ], 10, 2 );
		add_filter( 'woocommerce_variable_price_html', [ self::class, 'firstVariantPrice' ], 10, 2 );
		add_filter( 'woocommerce_get_price_html', [ self::class, 'fallbackContactPrice' ], 20, 2 );
		add_filter( 'woocommerce_currency_symbol', [ self::class, 'currencySymbol' ], 10, 2 );
	}

	/**
	 * Show "Liên hệ" for products with empty price.
	 *
	 * @param string      $html    Empty price HTML (usually '').
	 * @param \WC_Product $product Product instance.
	 * @return string
	 */
	public static function contactPrice( string $html, \WC_Product $product ): string {
		return '<span class="price-contact text-primary font-bold">'
			. esc_html__( 'Liên hệ', 'spl' )
			. '</span>';
	}

	/**
	 * Show only the lowest variant price instead of "From X – To Y".
	 *
	 * @param string              $html    Variable price HTML.
	 * @param \WC_Product_Variable $product Variable product.
	 * @return string
	 */
	public static function firstVariantPrice( string $html, \WC_Product $product ): string {
		$prices = $product->get_variation_prices( true );

		if ( empty( $prices['price'] ) ) {
			return self::contactPrice( '', $product );
		}

		$min_id      = array_key_first( $prices['price'] );
		$min_price   = (float) $prices['price'][ $min_id ];
		$min_regular = (float) ( $prices['regular_price'][ $min_id ] ?? $min_price );

		if ( $min_price <= 0 ) {
			return self::contactPrice( '', $product );
		}

		$price_html = wc_price( $min_price );

		if ( $min_regular > $min_price ) {
			$price_html = '<del>' . wc_price( $min_regular ) . '</del> <ins>' . $price_html . '</ins>';
		}

		return $price_html . $product->get_price_suffix();
	}

	/**
	 * Fallback: show "Liên hệ" for simple products with price = 0 or empty.
	 *
	 * This catches cases where WC builds price HTML but the product
	 * effectively has no meaningful price (e.g. price = '0' or '').
	 *
	 * @param string      $html    Price HTML.
	 * @param \WC_Product $product Product instance.
	 * @return string
	 */
	public static function fallbackContactPrice( string $html, \WC_Product $product ): string {
		// Skip variable/grouped — handled by dedicated filters above.
		if ( $product->is_type( [ 'variable', 'grouped' ] ) ) {
			return $html;
		}

		$price = $product->get_price();

		// Empty string = no price set; '0' = free but might be intentional.
		if ( '' === $price ) {
			return self::contactPrice( '', $product );
		}

		return $html;
	}

	/**
	 * Normalize VND currency symbol to ₫.
	 *
	 * @param string $symbol   Currency symbol.
	 * @param string $currency Currency code.
	 * @return string
	 */
	public static function currencySymbol( string $symbol, string $currency ): string {
		if ( 'VND' === $currency ) {
			return '₫';
		}

		return $symbol;
	}
}
