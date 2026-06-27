<?php
/**
 * Tracking Pixels — GA4 (gtag.js) & Facebook Pixel (fbevents.js).
 *
 * Outputs tracking scripts on the frontend, controlled via ACF Options.
 * Includes WooCommerce e-commerce events for both platforms.
 *
 * @package SPL\Features\Optimizer
 */

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class TrackingPixels {

	/** Cached option values (populated once per request). */
	private static string $ga4Id          = '';
	private static string $adsConvId      = '';
	private static string $adsConvLabel   = '';
	private static string $fbPixelId      = '';

	/**
	 * Register tracking hooks if conditions are met.
	 */
	public static function register(): void {
		// Skip admin, login page, REST, AJAX, CLI.
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Defer option reads to 'wp' so ACF and conditional tags are available.
		add_action( 'wp', [ self::class, 'init' ], 5 );
	}

	/**
	 * Read options and attach output hooks.
	 */
	public static function init(): void {
		if ( Helper::isLogin() ) {
			return;
		}

		// Master kill switch.
		$enabled = Helper::getField( 'tracking_enabled', 'option' );
		if ( ! $enabled ) {
			return;
		}

		self::$ga4Id        = trim( (string) Helper::getField( 'ga4_measurement_id', 'option' ) );
		self::$adsConvId    = trim( (string) Helper::getField( 'ga4_ads_conversion_id', 'option' ) );
		self::$adsConvLabel = trim( (string) Helper::getField( 'ga4_ads_conversion_label', 'option' ) );
		self::$fbPixelId    = trim( (string) Helper::getField( 'fb_pixel_id', 'option' ) );

		// Nothing to inject.
		if ( self::$ga4Id === '' && self::$fbPixelId === '' ) {
			return;
		}

		// Head scripts (high priority = early in <head>).
		if ( self::$ga4Id !== '' ) {
			add_action( 'wp_head', [ self::class, 'injectGA4Head' ], 1 );
			add_action( 'wp_footer', [ self::class, 'fireGA4Events' ], 50 );
		}

		if ( self::$fbPixelId !== '' ) {
			add_action( 'wp_head', [ self::class, 'injectFBPixelHead' ], 2 );
			add_action( 'wp_footer', [ self::class, 'fireFBPixelEvents' ], 51 );
		}
	}

	// ──────────────────────────────────────────────
	// GA4
	// ──────────────────────────────────────────────

	/**
	 * Output gtag.js loader + config in <head>.
	 */
	public static function injectGA4Head(): void {
		$id = esc_attr( self::$ga4Id );
		?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= $id ?>"></script>
<script>
window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
gtag('js',new Date());
gtag('config','<?= $id ?>');
<?php if ( self::$adsConvId !== '' ) : ?>
gtag('config','<?= esc_js( self::$adsConvId ) ?>');
<?php endif; ?>
</script>
		<?php
	}

	/**
	 * Fire contextual GA4 e-commerce events in the footer.
	 */
	public static function fireGA4Events(): void {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}

		// Single product → view_item.
		if ( is_product() ) {
			$product = wc_get_product( get_the_ID() );
			if ( ! $product ) {
				return;
			}

			$item = self::ga4Item( $product );
			self::printInlineScript( sprintf(
				"gtag('event','view_item',{currency:'VND',value:%s,items:[%s]});",
				(string) $product->get_price(),
				wp_json_encode( $item )
			) );
			return;
		}

		// Cart page → view_cart.
		if ( is_cart() && ! WC()->cart->is_empty() ) {
			$items = [];
			$total = 0;
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product = $cart_item['data'] ?? null;
				if ( $product instanceof \WC_Product ) {
					$items[] = self::ga4Item( $product, (int) $cart_item['quantity'] );
					$total  += (float) $product->get_price() * (int) $cart_item['quantity'];
				}
			}

			self::printInlineScript( sprintf(
				"gtag('event','view_cart',{currency:'VND',value:%s,items:%s});",
				$total,
				wp_json_encode( $items )
			) );
			return;
		}

		// Checkout page → begin_checkout.
		if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! WC()->cart->is_empty() ) {
			$items = [];
			$total = 0;
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product = $cart_item['data'] ?? null;
				if ( $product instanceof \WC_Product ) {
					$items[] = self::ga4Item( $product, (int) $cart_item['quantity'] );
					$total  += (float) $product->get_price() * (int) $cart_item['quantity'];
				}
			}

			self::printInlineScript( sprintf(
				"gtag('event','begin_checkout',{currency:'VND',value:%s,items:%s});",
				$total,
				wp_json_encode( $items )
			) );
			return;
		}

		// Thank-you page → purchase.
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			self::fireGA4Purchase();
		}
	}

	/**
	 * Fire GA4 purchase event on thank-you page.
	 */
	private static function fireGA4Purchase(): void {
		$order_id = absint( get_query_var( 'order-received' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : null;
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// Prevent duplicate fires via order meta.
		if ( $order->get_meta( '_ga4_tracked' ) === '1' ) {
			return;
		}

		$items = [];
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product ) {
				$items[] = self::ga4Item( $product, $item->get_quantity() );
			}
		}

		$js = sprintf(
			"gtag('event','purchase',{transaction_id:'%s',value:%s,currency:'VND',tax:%s,shipping:%s,items:%s});",
			esc_js( (string) $order->get_id() ),
			$order->get_total(),
			$order->get_total_tax(),
			$order->get_shipping_total(),
			wp_json_encode( $items )
		);

		// Google Ads conversion.
		if ( self::$adsConvId !== '' && self::$adsConvLabel !== '' ) {
			$js .= sprintf(
				"\ngtag('event','conversion',{send_to:'%s/%s',value:%s,currency:'VND',transaction_id:'%s'});",
				esc_js( self::$adsConvId ),
				esc_js( self::$adsConvLabel ),
				$order->get_total(),
				esc_js( (string) $order->get_id() )
			);
		}

		self::printInlineScript( $js );

		$order->update_meta_data( '_ga4_tracked', '1' );
		$order->save();
	}

	/**
	 * Build a GA4 item array from a WC_Product.
	 *
	 * @param \WC_Product $product  Product.
	 * @param int         $quantity Quantity.
	 *
	 * @return array<string, mixed>
	 */
	private static function ga4Item( \WC_Product $product, int $quantity = 1 ): array {
		$categories = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] );
		$category   = is_array( $categories ) && $categories ? $categories[0] : '';

		return [
			'item_id'       => (string) $product->get_id(),
			'item_name'     => $product->get_name(),
			'item_category' => $category,
			'price'         => (float) $product->get_price(),
			'quantity'      => $quantity,
		];
	}

	// ──────────────────────────────────────────────
	// Facebook Pixel
	// ──────────────────────────────────────────────

	/**
	 * Output fbevents.js + init + PageView in <head>.
	 */
	public static function injectFBPixelHead(): void {
		$id = esc_js( self::$fbPixelId );
		?>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init','<?= $id ?>');
fbq('track','PageView');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= esc_attr( self::$fbPixelId ) ?>&ev=PageView&noscript=1"/></noscript>
		<?php
	}

	/**
	 * Fire contextual Facebook Pixel e-commerce events in the footer.
	 */
	public static function fireFBPixelEvents(): void {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}

		// Single product → ViewContent.
		if ( is_product() ) {
			$product = wc_get_product( get_the_ID() );
			if ( ! $product ) {
				return;
			}

			self::printInlineScript( sprintf(
				"fbq('track','ViewContent',{content_ids:['%s'],content_type:'product',content_name:%s,value:%s,currency:'VND'});",
				esc_js( (string) $product->get_id() ),
				wp_json_encode( $product->get_name() ),
				(string) $product->get_price()
			) );
			return;
		}

		// Checkout page → InitiateCheckout.
		if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! WC()->cart->is_empty() ) {
			self::printInlineScript( sprintf(
				"fbq('track','InitiateCheckout',{value:%s,currency:'VND',num_items:%d});",
				WC()->cart->get_total( 'edit' ),
				WC()->cart->get_cart_contents_count()
			) );
			return;
		}

		// Thank-you page → Purchase.
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			self::fireFBPixelPurchase();
		}
	}

	/**
	 * Fire FB Pixel Purchase event on thank-you page.
	 */
	private static function fireFBPixelPurchase(): void {
		$order_id = absint( get_query_var( 'order-received' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : null;
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( $order->get_meta( '_fbp_tracked' ) === '1' ) {
			return;
		}

		$content_ids = [];
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product ) {
				$content_ids[] = (string) $product->get_id();
			}
		}

		self::printInlineScript( sprintf(
			"fbq('track','Purchase',{value:%s,currency:'VND',content_ids:%s,content_type:'product'});",
			$order->get_total(),
			wp_json_encode( $content_ids )
		) );

		$order->update_meta_data( '_fbp_tracked', '1' );
		$order->save();
	}

	// ──────────────────────────────────────────────
	// Add-to-cart JS event (shared GA4 + FB Pixel)
	// ──────────────────────────────────────────────

	/**
	 * Print an inline <script> tag.
	 */
	private static function printInlineScript( string $js ): void {
		wp_print_inline_script_tag( $js );
	}
}
