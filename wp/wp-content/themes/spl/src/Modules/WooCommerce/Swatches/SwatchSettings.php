<?php
/**
 * Swatch Settings — field definitions for the WCSettings admin tab.
 *
 * Implements HasSettings to provide auto-rendered settings UI
 * under WooCommerce → HD WooCommerce → Variation Swatches tab.
 *
 * Separated from SwatchesManager to follow SRP:
 * - SwatchesManager = orchestration (register hooks)
 * - SwatchSettings  = configuration (field definitions + defaults)
 *
 * @package SPL\Modules\WooCommerce\Swatches
 */

namespace SPL\Modules\WooCommerce\Swatches;

use SPL\Modules\WooCommerce\Contracts\HasSettings;

defined( 'ABSPATH' ) || exit;

final class SwatchSettings implements HasSettings {

	/* ---------- HasSettings ---------------------------------------- */

	public static function settingsFields(): array {
		return [

			// ── General ──────────────────────────────────
			'swatch_shape_style'         => [
				'type'    => 'select',
				'options' => [
					'squared' => __( 'Squared', 'SPL' ),
					'rounded' => __( 'Rounded (circle)', 'SPL' ),
				],
			],
			'swatch_disabled_style'      => [
				'type'    => 'select',
				'options' => [
					'blur'          => __( 'Blur with cross', 'SPL' ),
					'blur-no-cross' => __( 'Blur without cross', 'SPL' ),
					'hide'          => __( 'Hide', 'SPL' ),
				],
			],
			'swatch_default_to_button'   => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Auto-convert unconfigured dropdown attributes to button swatches', 'SPL' ),
			],
			'swatch_default_to_image'    => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Auto-convert to image swatch if variation has an image', 'SPL' ),
			],
			'swatch_clear_on_reselect'   => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Click a selected swatch again to deselect', 'SPL' ),
			],

			// ── Single Product ───────────────────────────
			'swatch_show_selected_label' => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Show selected value next to attribute label (e.g. Color: Red)', 'SPL' ),
			],
			'swatch_label_separator'     => [
				'type'    => 'text',
				'default' => ':',
				'help'    => __( 'Separator between label and selected value', 'SPL' ),
			],
			'swatch_display_limit'       => [
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'max'     => 50,
				'help'    => __( 'Max swatches to show on single product (0 = no limit)', 'SPL' ),
			],
			'swatch_show_stock_info'     => [
				'type'    => 'toggle',
				'default' => false,
				'help'    => __( 'Show stock status below swatch (e.g. "3 left")', 'SPL' ),
			],
			'swatch_stock_threshold'     => [
				'type'    => 'number',
				'default' => 5,
				'min'     => 0,
				'max'     => 100,
				'help'    => __( 'Show count only when stock ≤ this number (0 = always show)', 'SPL' ),
			],
			'swatch_linkable_url'        => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Update URL when variation is selected (shareable link)', 'SPL' ),
			],
			'swatch_image_preview'       => [
				'type'    => 'toggle',
				'default' => false,
				'help'    => __( 'Swap gallery image on swatch hover (before all attributes are selected)', 'SPL' ),
			],

			// ── Tooltip ──────────────────────────────────
			'swatch_tooltip'             => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Show tooltip on hover', 'SPL' ),
			],

			// ── Archive / Shop ───────────────────────────
			'swatch_archive_position'    => [
				'type'    => 'select',
				'options' => [
					'after'  => __( 'After add-to-cart button', 'SPL' ),
					'before' => __( 'Before add-to-cart button', 'SPL' ),
				],
			],
			'swatch_archive_limit'       => [
				'type'    => 'number',
				'default' => 5,
				'min'     => 0,
				'max'     => 20,
				'help'    => __( 'Max swatches on archive cards (0 = no limit)', 'SPL' ),
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
