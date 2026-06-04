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
 * @package HD\Modules\WooCommerce\Swatches
 */

namespace HD\Modules\WooCommerce\Swatches;

use HD\Modules\WooCommerce\Contracts\HasSettings;

defined( 'ABSPATH' ) || exit;

final class SwatchSettings implements HasSettings {

	/* ---------- HasSettings ---------------------------------------- */

	public static function settingsFields(): array {
		return [

			// ── General ──────────────────────────────────
			'swatch_shape_style'         => [
				'type'    => 'select',
				'options' => [
					'squared' => __( 'Squared', 'hd' ),
					'rounded' => __( 'Rounded (circle)', 'hd' ),
				],
			],
			'swatch_disabled_style'      => [
				'type'    => 'select',
				'options' => [
					'blur'          => __( 'Blur with cross', 'hd' ),
					'blur-no-cross' => __( 'Blur without cross', 'hd' ),
					'hide'          => __( 'Hide', 'hd' ),
				],
			],
			'swatch_default_to_button'   => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Auto-convert unconfigured dropdown attributes to button swatches', 'hd' ),
			],
			'swatch_default_to_image'    => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Auto-convert to image swatch if variation has an image', 'hd' ),
			],
			'swatch_clear_on_reselect'   => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Click a selected swatch again to deselect', 'hd' ),
			],

			// ── Single Product ───────────────────────────
			'swatch_show_selected_label' => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Show selected value next to attribute label (e.g. Color: Red)', 'hd' ),
			],
			'swatch_label_separator'     => [
				'type'    => 'text',
				'default' => ':',
				'help'    => __( 'Separator between label and selected value', 'hd' ),
			],
			'swatch_display_limit'       => [
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'max'     => 50,
				'help'    => __( 'Max swatches to show on single product (0 = no limit)', 'hd' ),
			],
			'swatch_show_stock_info'     => [
				'type'    => 'toggle',
				'default' => false,
				'help'    => __( 'Show stock status below swatch (e.g. "3 left")', 'hd' ),
			],
			'swatch_stock_threshold'     => [
				'type'    => 'number',
				'default' => 5,
				'min'     => 0,
				'max'     => 100,
				'help'    => __( 'Show count only when stock ≤ this number (0 = always show)', 'hd' ),
			],
			'swatch_linkable_url'        => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Update URL when variation is selected (shareable link)', 'hd' ),
			],
			'swatch_image_preview'       => [
				'type'    => 'toggle',
				'default' => false,
				'help'    => __( 'Swap gallery image on swatch hover (before all attributes are selected)', 'hd' ),
			],

			// ── Tooltip ──────────────────────────────────
			'swatch_tooltip'             => [
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Show tooltip on hover', 'hd' ),
			],

			// ── Archive / Shop ───────────────────────────
			'swatch_archive_position'    => [
				'type'    => 'select',
				'options' => [
					'after'  => __( 'After add-to-cart button', 'hd' ),
					'before' => __( 'Before add-to-cart button', 'hd' ),
				],
			],
			'swatch_archive_limit'       => [
				'type'    => 'number',
				'default' => 5,
				'min'     => 0,
				'max'     => 20,
				'help'    => __( 'Max swatches on archive cards (0 = no limit)', 'hd' ),
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
