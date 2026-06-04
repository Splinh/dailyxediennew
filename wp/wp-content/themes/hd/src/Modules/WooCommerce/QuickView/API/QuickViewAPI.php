<?php
/**
 * Quick View API — REST endpoint for product Quick View popup.
 *
 * GET /hd/v1/wc-quickview/{id}
 *
 * Returns server-rendered product HTML for FxModal popup.
 * Uses transient cache with invalidation via clean_post_cache.
 *
 * @package HD\Modules\WooCommerce\QuickView\API
 */

namespace HD\Modules\WooCommerce\QuickView\API;

use HD\API\AbstractAPI;
use WC_Product;

defined( 'ABSPATH' ) || exit;

final class QuickViewAPI extends AbstractAPI {
	public const CACHE_PREFIX = 'hd_quickview_';

	private const CACHE_INDEX_MAX_KEYS = 50;

	/**
	 * Register REST routes.
	 */
	protected function registerRoutes(): void {
		register_rest_route(
			REST_NAMESPACE,
			'/wc-quickview/(?P<id>\d+)',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => $this->getQuickView( ... ),
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'validate_callback' => static fn( $val ) => is_numeric( $val ) && absint( $val ) > 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Handle Quick View request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function getQuickView( \WP_REST_Request $request ): \WP_REST_Response {

		// Rate limit: 30 requests per minute
		$rateLimitCheck = $this->rateLimit( 'wc_quickview', 30, 60 );
		if ( $rateLimitCheck instanceof \WP_REST_Response ) {
			return $rateLimitCheck;
		}

		$productId = absint( $request['id'] );
		$product   = wc_get_product( $productId );

		// Security: validate product exists, published, not password-protected
		if ( ! $product
			|| 'publish' !== get_post_status( $product->get_id() )
			|| post_password_required( $product->get_id() )
		) {
			return $this->sendResponse( [ 'message' => 'Product not found.' ], 404 );
		}

		$cacheVariance = $this->cacheVariance( $productId );
		$cacheKey      = $this->cacheKey( $productId, $cacheVariance );
		$cacheTtl      = $this->cacheTtl( $productId, $cacheVariance );
		$html          = $cacheTtl > 0 ? get_transient( $cacheKey ) : false;

		if ( false === $html ) {
			$html = $this->renderProductHtml( $product );
			if ( $cacheTtl > 0 ) {
				set_transient( $cacheKey, $html, $cacheTtl );
				$this->trackCacheKey( $productId, $cacheKey, $cacheTtl );
			}
		}

		return $this->sendResponse(
			[],
			200,
			[
				'html'         => $html,
				'product_type' => $product->get_type(),
			]
		);
	}

	public static function legacyCacheKey( int $productId ): string {
		return self::CACHE_PREFIX . $productId;
	}

	public static function cacheIndexKey( int $productId ): string {
		return self::CACHE_PREFIX . $productId . '_keys';
	}

	/**
	 * Render Quick View product HTML with proper WC global context.
	 *
	 * Full render isolation: saves/restores globals, removes unwanted
	 * summary callbacks (sharing, structured data) during render.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return string Rendered HTML.
	 */
	private function renderProductHtml( WC_Product $product ): string {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for WC template functions
		global $post;

		$prevPost    = $post;
		$hadProduct  = array_key_exists( 'product', $GLOBALS );
		$prevProduct = $GLOBALS['product'] ?? null;
		$bufferLevel = ob_get_level();

		$post               = get_post( $product->get_id() );
		$GLOBALS['product'] = $product;
		setup_postdata( $post );

		try {
			// Prevent form redirect in popup context
			add_filter( 'woocommerce_add_to_cart_form_action', '__return_empty_string' );

			// Remove unwanted summary callbacks (verified via Phase 0d audit)
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
			remove_action( 'woocommerce_single_product_summary', [ WC()->structured_data, 'generate_product_data' ], 60 );

			ob_start();
			$this->renderQuickViewContent( $product );
			$html = ob_get_clean();

			return $html;
		} finally {
			while ( ob_get_level() > $bufferLevel ) {
				ob_end_clean();
			}

			// Restore hooks
			remove_filter( 'woocommerce_add_to_cart_form_action', '__return_empty_string' );
			add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
			add_action( 'woocommerce_single_product_summary', [ WC()->structured_data, 'generate_product_data' ], 60 );

			// Restore globals
			$post = $prevPost;
			if ( $hadProduct ) {
				$GLOBALS['product'] = $prevProduct;
			} else {
				unset( $GLOBALS['product'] );
			}

			if ( $prevPost ) {
				setup_postdata( $prevPost );
			} else {
				wp_reset_postdata();
			}
		}
	}

	/**
	 * Build context that can change rendered QuickView markup.
	 *
	 * @return array<string, mixed>
	 */
	private function cacheVariance( int $productId ): array {
		$language = '';
		if ( function_exists( 'pll_current_language' ) ) {
			$language = (string) pll_current_language( 'slug' );
		}
		if ( '' === $language && function_exists( 'get_locale' ) ) {
			$language = (string) get_locale();
		}

		$variance = [
			'currency'    => function_exists( 'get_woocommerce_currency' ) ? (string) get_woocommerce_currency() : '',
			'language'    => $language,
			'roles'       => $this->currentUserRoles(),
			'tax_display' => function_exists( 'get_option' ) ? (string) get_option( 'woocommerce_tax_display_shop', 'excl' ) : 'excl',
		];

		$variance = apply_filters( 'hd_quickview_cache_variance', $variance, $productId );

		return is_array( $variance ) ? $this->normalizeCacheVariance( $variance ) : [];
	}

	/**
	 * @param array<string, mixed> $variance
	 */
	private function cacheKey( int $productId, array $variance ): string {
		$payload = wp_json_encode( $variance );

		if ( ! is_string( $payload ) ) {
			$payload = '';
		}

		$defaultKey = self::legacyCacheKey( $productId ) . '_' . md5( $payload );

		return (string) apply_filters( 'hd_quickview_cache_key', $defaultKey, $productId, $variance );
	}

	/**
	 * @param array<string, mixed> $variance
	 */
	private function cacheTtl( int $productId, array $variance ): int {
		$defaultTtl = defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600;

		return max( 0, (int) apply_filters( 'hd_quickview_cache_ttl', $defaultTtl, $productId, $variance ) );
	}

	private function currentUserRoles(): array {
		$roles = [ 'guest' ];

		if ( function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
			if ( is_object( $user ) && ! empty( $user->roles ) && is_array( $user->roles ) ) {
				$roles = array_values( array_map( 'strval', $user->roles ) );
			}
		}

		sort( $roles );

		return $roles;
	}

	/**
	 * @param array<string|int, mixed> $variance
	 *
	 * @return array<string|int, mixed>
	 */
	private function normalizeCacheVariance( array $variance ): array {
		foreach ( $variance as $key => $value ) {
			if ( is_array( $value ) ) {
				$variance[ $key ] = $this->normalizeCacheVariance( $value );
				continue;
			}

			if ( ! is_scalar( $value ) && null !== $value ) {
				$variance[ $key ] = method_exists( $value, '__toString' ) ? (string) $value : get_debug_type( $value );
			}
		}

		ksort( $variance );

		return $variance;
	}

	private function trackCacheKey( int $productId, string $cacheKey, int $cacheTtl ): void {
		$indexKey = self::cacheIndexKey( $productId );
		$keys     = get_transient( $indexKey );
		$keys     = is_array( $keys ) ? array_values( array_map( 'strval', $keys ) ) : [];
		$keys[]   = $cacheKey;
		$keys     = array_values( array_unique( $keys ) );

		if ( count( $keys ) > self::CACHE_INDEX_MAX_KEYS ) {
			$keys = array_slice( $keys, -self::CACHE_INDEX_MAX_KEYS );
		}

		$indexTtl = max( $cacheTtl, defined( 'WEEK_IN_SECONDS' ) ? WEEK_IN_SECONDS : 604800 );
		set_transient( $indexKey, $keys, $indexTtl );
	}

	/**
	 * Render the Quick View popup content.
	 *
	 * Uses standard WC hooks so Gallery and Swatches modules auto-hook
	 * via woocommerce_before_single_product_summary and
	 * woocommerce_single_product_summary — zero cross-module calls.
	 *
	 * @param WC_Product $product The product.
	 */
	private function renderQuickViewContent( WC_Product $product ): void {
		?>
		<div class="hd-quickview product" data-woocommerce data-product-type="<?php echo esc_attr( $product->get_type() ); ?>">
			<div class="hd-quickview__gallery">
				<?php
				/**
				 * Standard WC hook — Gallery module hooks at priority 20,
				 * sale flash at priority 10. Both render automatically.
				 */
				do_action( 'woocommerce_before_single_product_summary' );
				?>
			</div>
			<div class="hd-quickview__info summary entry-summary">
				<?php
				/**
				 * Standard WC hook — title, rating, price, excerpt,
				 * add-to-cart (with swatches), meta all render automatically.
				 * Sharing + structured_data removed in renderProductHtml().
				 */
				do_action( 'woocommerce_single_product_summary' );
				?>
				<a href="<?php echo esc_url( get_permalink() ); ?>" class="hd-quickview__detail-link">
					<?php esc_html_e( 'Xem chi tiết →', 'hd' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
}
