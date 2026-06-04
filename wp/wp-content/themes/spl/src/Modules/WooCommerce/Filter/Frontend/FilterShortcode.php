<?php
/**
 * Filter Shortcodes — [hd_filter] and [hd_filter_chips].
 *
 * [hd_filter id="123" layout="horizontal" class="my-class"]
 * [hd_filter_chips preset_id="123"]
 *
 * @package SPL\Modules\WooCommerce\Filter\Frontend
 */

namespace SPL\Modules\WooCommerce\Filter\Frontend;

use SPL\Modules\WooCommerce\Filter\FilterManager;

defined( 'ABSPATH' ) || exit;

final class FilterShortcode {

	/**
	 * Register shortcodes. Called from FilterManager::register().
	 */
	public static function init(): void {
		add_shortcode( 'hd_filter', [ self::class, 'renderFilter' ] );
		add_shortcode( 'hd_filter_chips', [ self::class, 'renderChips' ] );

		// Action hook for theme template integration
		add_action( 'hd_wc_render_filter', [ FilterRenderer::class, 'render' ], 10, 3 );
	}

	/**
	 * [hd_filter id="123" layout="horizontal" class="my-class"]
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 *
	 * @return string Rendered HTML.
	 */
	public static function renderFilter( array|string $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'id'     => 0,
				'layout' => '',
				'class'  => '',
			],
			$atts,
			'hd_filter'
		);

		$presetId = absint( $atts['id'] );
		if ( 0 === $presetId ) {
			$presetId = FilterManager::resolvePresetId();
		}
		if ( null === $presetId || 0 === $presetId ) {
			return '';
		}

		ob_start();
		FilterRenderer::render( $presetId, sanitize_key( $atts['layout'] ), sanitize_html_class( $atts['class'] ) );

		return ob_get_clean() ?: '';
	}

	/**
	 * [hd_filter_chips preset_id="123"]
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 *
	 * @return string Rendered HTML.
	 */
	public static function renderChips( array|string $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'preset_id' => 0,
			],
			$atts,
			'hd_filter_chips'
		);

		$presetId = absint( $atts['preset_id'] );
		if ( 0 === $presetId ) {
			$presetId = FilterManager::resolvePresetId();
		}
		if ( null === $presetId || 0 === $presetId ) {
			return '';
		}

		return FilterRenderer::renderChips( $presetId );
	}
}
