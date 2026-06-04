<?php
/**
 * WooCommerce Theme Support — core setup, always active.
 *
 * Handles: add_theme_support, theme.json font override,
 * admin WC header cleanup, and misc output filters.
 *
 * @package SPL\Modules\WooCommerce\Core
 */

namespace SPL\Modules\WooCommerce\Core;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class ThemeSupport {
	private const ADMIN_OVERRIDES_HANDLE = 'hd-wc-admin-overrides';

	/**
	 * Register all theme support hooks.
	 */
	public function register(): void {
		add_action( 'after_setup_theme', [ $this, 'afterSetupTheme' ], 33 );
		add_action( 'after_switch_theme', [ self::class, 'configureImageDefaults' ] );

		add_filter( 'wp_theme_json_data_theme', [ $this, 'jsonDataTheme' ] );
		// Disable WC default stylesheets — theme handles all styling
			add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

		// Remove WC admin header via inline style (scoped to admin)
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueueAdminOverrides' ] );

		add_filter( 'woocommerce_defer_transactional_emails', '__return_true' );
		add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
		add_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );
		add_filter( 'woocommerce_product_brands_output', '__return_empty_string' );
		add_filter( 'woocommerce_product_get_rating_html', [ $this, 'getRatingHtml' ], 10, 3 );
	}

	// ── Hooks ───────────────────────────────────────

	/**
	 * @return void
	 */
	public function afterSetupTheme(): void {
		add_theme_support( 'woocommerce' );
	}

	/**
	 * Enqueue inline admin CSS overrides.
	 * Hooked to `admin_enqueue_scripts` - named method for removability.
	 */
	public static function enqueueAdminOverrides(): void {
		wp_register_style( self::ADMIN_OVERRIDES_HANDLE, false, [], null );
		wp_enqueue_style( self::ADMIN_OVERRIDES_HANDLE );
		wp_add_inline_style(
			self::ADMIN_OVERRIDES_HANDLE,
			'#wpadminbar ~ #wpbody { margin-top: 0 !important; } .woocommerce-layout__header { display: none !important; }'
		);
	}

	/**
	 * Configure product image defaults (one-time, on theme activation).
	 *
	 * Main image: 1024px, Thumbnail: 480px, Cropping: uncropped.
	 */
	public static function configureImageDefaults(): void {
		if ( Helper::getOption( 'hd_wc_images_configured' ) ) {
			return;
		}

		Helper::updateOption( 'hd_wc_images_configured', true );
		Helper::updateOption( 'woocommerce_single_image_width', 1024 );
		Helper::updateOption( 'woocommerce_thumbnail_image_width', 480 );
		Helper::updateOption( 'woocommerce_thumbnail_cropping', 'uncropped' );
	}

	/**
	 * Override theme.json font families to empty (use theme fonts only).
	 *
	 * @param mixed $themeJson
	 *
	 * @return mixed
	 */
	public function jsonDataTheme( mixed $themeJson ): mixed {
		$themeJson->update_with(
			[
				'version'  => 1,
				'settings' => [
					'typography' => [
						'fontFamilies' => [ 'theme' => [] ],
					],
				],
			]
		);

		return $themeJson;
	}

	/**
	 * Custom star rating HTML.
	 *
	 * @param string $html
	 * @param float  $rating
	 * @param int    $count
	 *
	 * @return string
	 */
	public function getRatingHtml( string $html, float $rating, int $count ): string {
		if ( $rating <= 0 ) {
			return '';
		}

		$label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );

		return '<div class="loop-stars-rating" role="img" aria-label="' . esc_attr( $label ) . '">'
				. \wc_get_star_rating_html( $rating, $count )
				. '</div>';
	}
}
