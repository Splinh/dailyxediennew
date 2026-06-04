<?php
/**
 * Gallery Manager — sub-feature entry point.
 *
 * Manages product gallery with Swiper slider, thumbnail strip,
 * magnifying glass zoom, and per-variation gallery swap.
 * Implements HasSettings for gallery_layout and gallery_zoom options.
 *
 * @package SPL\Modules\WooCommerce\Gallery
 */

namespace SPL\Modules\WooCommerce\Gallery;

use SPL\Modules\WooCommerce\Contracts\HasSettings;
use SPL\Modules\WooCommerce\Contracts\WooFeatureInterface;
use WC_Product;

defined( 'ABSPATH' ) || exit;

final class GalleryManager implements WooFeatureInterface, HasSettings {

	public static function slug(): string {
		return 'gallery_thumbs';
	}

	public function register(): void {

		// Disable WC built-in gallery features (we use Swiper + custom zoom)
		add_action( 'wp', [ self::class, 'disableBuiltinGallery' ], 99 );

		if ( is_admin() ) {
			( new Admin\GalleryAdmin() )->register();
			( new Admin\GalleryMediaFields() )->register();
		}

		( new Frontend\GalleryRenderer() )->register();
		( new API\GalleryAPI() )->register();

		// N7: Preload first gallery image for LCP optimization
		add_action( 'wp_head', [ self::class, 'preloadLcpImage' ], 1 );

		// Register product meta keys for Polylang content duplication sync.
		// Standard PLL filter — if Polylang is inactive, this filter never fires.
		add_filter( 'pll_copy_post_metas', [ self::class, 'addPllMetas' ] );
	}

	/**
	 * Disable WC built-in gallery features on single product.
	 * Hooked to `wp` at priority 99.
	 */
	public static function disableBuiltinGallery(): void {
		if ( ! is_product() ) {
			return;
		}

		remove_theme_support( 'wc-product-gallery-zoom' );
		remove_theme_support( 'wc-product-gallery-slider' );
		remove_theme_support( 'wc-product-gallery-lightbox' );
	}

	/**
	 * Register gallery meta keys for Polylang content duplication sync.
	 *
	 * @param array<string> $metas Meta keys to copy.
	 *
	 * @return array<string>
	 */
	public static function addPllMetas( array $metas ): array {
		$metas[] = Frontend\GalleryDataProvider::PRODUCT_VIDEO_KEY;
		$metas[] = Frontend\GalleryDataProvider::PRODUCT_VIDEO_POSTER;

		return array_unique( $metas );
	}

	/**
	 * N7: Output preload hint for main gallery image on single product.
	 *
	 * Note: global $product is NOT available at wp_head time (WC sets it during the_post).
	 * Uses wc_get_product( get_queried_object_id() ) instead.
	 */
	public static function preloadLcpImage(): void {
		if ( ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$imageId = $product->get_image_id();

		// Account for default variation — preload the image actually displayed
		$defaultVarId = Frontend\GalleryDataProvider::resolveDefaultVariation( $product );
		if ( $defaultVarId ) {
			$varThumbId = get_post_thumbnail_id( $defaultVarId );
			if ( $varThumbId ) {
				$imageId = $varThumbId;
			}
		}

		if ( ! $imageId ) {
			return;
		}

		$src    = wp_get_attachment_image_url( $imageId, 'woocommerce_single' );
		$srcset = wp_get_attachment_image_srcset( $imageId, 'woocommerce_single' );
		$sizes  = wp_get_attachment_image_sizes( $imageId, 'woocommerce_single' );

		if ( $src ) {
			printf(
				'<link rel="preload" as="image" href="%s"%s%s>' . "\n",
				esc_url( $src ),
				$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : '',
				$sizes ? ' imagesizes="' . esc_attr( $sizes ) . '"' : ''
			);
		}
	}

	public static function settingsFields(): array {
		return [
			'gallery_layout'            => [
				'type'    => 'select',
				'options' => [
					'below'   => __( 'Slider — Thumbs Below', 'SPL' ),
					'above'   => __( 'Slider — Thumbs Above', 'SPL' ),
					'left'    => __( 'Slider — Thumbs Left', 'SPL' ),
					'right'   => __( 'Slider — Thumbs Right', 'SPL' ),
					'stacked' => __( 'Stacked (Grid, no slider)', 'SPL' ),
				],
			],
			'gallery_zoom'              => [
				'type'    => 'toggle',
				'default' => true,
			],
			'gallery_zoom_scale'        => [
				'type'    => 'number',
				'default' => 2,
				'min'     => 1.5,
				'max'     => 5,
				'step'    => 0.5,
				'help'    => __( 'Zoom magnification level', 'SPL' ),
			],
			'gallery_lens_size'         => [
				'type'    => 'number',
				'default' => 150,
				'min'     => 80,
				'max'     => 400,
				'step'    => 10,
				'help'    => __( 'Lens diameter in px (circle mode only)', 'SPL' ),
			],
			'gallery_lens_mode'         => [
				'type'    => 'select',
				'options' => [
					'circle' => __( 'Circle (magnifying glass)', 'SPL' ),
					'full'   => __( 'Full (lens fills entire image)', 'SPL' ),
				],
			],
			'gallery_variation_mode'    => [
				'type'    => 'select',
				'options' => [
					'replace' => __( 'Replace — show only variation images', 'SPL' ),
					'prepend' => __( 'Prepend — variation images first, then product gallery', 'SPL' ),
				],
			],

			'gallery_product_video_pos' => [
				'type'    => 'select',
				'options' => [
					'first_slide' => __( 'First Slide', 'SPL' ),
					'last_slide'  => __( 'Last Slide', 'SPL' ),
					'overlay'     => __( 'Floating Overlay Button', 'SPL' ),
				],
			],
			'gallery_object_fit'        => [
				'type'    => 'select',
				'options' => [
					'contain' => __( 'Contain (keep full image, may show background)', 'SPL' ),
					'cover'   => __( 'Cover (fill frame, may crop edges)', 'SPL' ),
				],
			],
			'gallery_thumbs_mobile'     => [
				'type'    => 'number',
				'default' => 3,
				'min'     => 0,
				'max'     => 6,
				'help'    => __( '0 = auto (CSS-based sizing)', 'SPL' ),
			],
			'gallery_thumbs_tablet'     => [
				'type'    => 'number',
				'default' => 4,
				'min'     => 0,
				'max'     => 8,
				'help'    => __( '0 = auto (CSS-based sizing)', 'SPL' ),
			],
			'gallery_thumbs_desktop'    => [
				'type'    => 'number',
				'default' => 5,
				'min'     => 0,
				'max'     => 10,
				'help'    => __( '0 = auto (CSS-based sizing)', 'SPL' ),
			],
			'gallery_nav_arrows'        => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Show prev/next navigation arrows on slider', 'SPL' ),
			],
		];
	}

	public static function defaults(): array {
		$defaults = [];
		foreach ( self::settingsFields() as $key => $field ) {
			if ( isset( $field['default'] ) ) {
				$defaults[ $key ] = $field['default'];
			} elseif ( 'select' === $field['type'] && ! empty( $field['options'] ) ) {
				$defaults[ $key ] = array_key_first( $field['options'] );
			}
		}

		return $defaults;
	}
}
