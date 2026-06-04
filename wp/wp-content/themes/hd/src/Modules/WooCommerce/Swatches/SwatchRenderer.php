<?php
/**
 * Swatch Renderer — shared HTML output for swatch elements.
 *
 * Centralizes all swatch HTML generation (color, dual-color, image, label, tooltip)
 * so that SingleSwatches, ArchiveSwatches, and future Widget/API can reuse
 * the same rendering logic without duplication.
 *
 * @package HD\Modules\WooCommerce\Swatches
 */

namespace HD\Modules\WooCommerce\Swatches;

use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class SwatchRenderer {

	/**
	 * Render swatch inner content (color / dual-color / image / label).
	 *
	 * @param array  $data      Swatch meta from SwatchMeta::getData().
	 * @param string $name      Term name (for label fallback and alt text).
	 * @param string $cssPrefix CSS class prefix — 'hd-swatch' or 'hd-archive-swatch'.
	 * @param string $imageSize WP image size — 'woocommerce_gallery_thumbnail' or [24,24].
	 *
	 * @return string HTML output.
	 */
	public static function renderInner( array $data, string $name, string $cssPrefix = 'hd-swatch', string|array $imageSize = 'woocommerce_gallery_thumbnail' ): string {
		$type = $data['type'] ?: 'label';

		if ( 'color' === $type ) {
			return self::renderColor( $data, $cssPrefix );
		}

		return match ( $type ) {
			'image' => Helper::attachmentImageHTML( $data['image'], $imageSize, [ 'class' => "{$cssPrefix}__image" ] ),
			default => '<span class="' . esc_attr( "{$cssPrefix}__label" ) . '">' . esc_html( $name ) . '</span>',
		};
	}

	/**
	 * Build tooltip HTML attributes for a swatch button.
	 *
	 * @param \WP_Term $term Term object.
	 * @param array    $data Swatch meta from SwatchMeta::getData().
	 *
	 * @return string Space-prefixed HTML attributes string.
	 */
	public static function buildTooltipAttrs( \WP_Term $term, array $data ): string {
		if ( 'no' === $data['tooltip_type'] ) {
			return '';
		}

		$attrs = '';

		if ( 'image' === $data['tooltip_type'] && $data['tooltip_image'] ) {
			$imgSrc = Helper::attachmentImageSrc( $data['tooltip_image'], 'woocommerce_gallery_thumbnail' );
			if ( $imgSrc ) {
				$attrs .= ' data-tooltip-image="' . esc_url( $imgSrc ) . '"';
				$attrs .= ' style="--tooltip-image:url(\'' . esc_url( $imgSrc ) . '\')"';
			}
		}

		$tooltip = $data['tooltip_text'] ?: $term->name;
		$attrs  .= ' data-wvstooltip="' . esc_attr( $tooltip ) . '"';

		return $attrs;
	}

	/**
	 * Render color swatch (solid or dual-color gradient).
	 */
	private static function renderColor( array $data, string $cssPrefix ): string {
		$primary = esc_attr( sanitize_hex_color( $data['color'] ) );

		if ( $data['is_dual'] ) {
			$secondary = esc_attr( sanitize_hex_color( $data['secondary_color'] ) );

			return '<span class="' . esc_attr( "{$cssPrefix}__color {$cssPrefix}__color--dual" ) . '" style="background:linear-gradient(135deg,' . $secondary . ' 50%,' . $primary . ' 50%)"></span>';
		}

		return '<span class="' . esc_attr( "{$cssPrefix}__color" ) . '" style="background:' . $primary . '"></span>';
	}

	/**
	 * Render radio-style swatch with optional variation image and price.
	 *
	 * @param string      $slug          Term slug (attribute value).
	 * @param string      $name          Term name (display label).
	 * @param string      $attribute     Attribute taxonomy name.
	 * @param bool        $isSelected    Whether this radio is currently selected.
	 * @param array|null  $variationData Optional variation data with 'image_id' and 'price_html'.
	 *
	 * @return string HTML output.
	 */
	public static function renderRadio(
		string $slug,
		string $name,
		string $attribute,
		bool $isSelected,
		?array $variationData = null
	): string {
		$html  = '<label class="hd-swatch hd-swatch--radio">';
		$html .= '<input type="radio" name="hd_radio_' . esc_attr( $attribute ) . '"'
			. ' value="' . esc_attr( $slug ) . '"'
			. ' data-attribute_name="attribute_' . esc_attr( $attribute ) . '"'
			. ' data-value="' . esc_attr( $slug ) . '"'
			. ( $isSelected ? ' checked' : '' ) . '>';

		$html .= '<span class="hd-swatch__radio-content">';

		if ( $variationData && ! empty( $variationData['image_id'] ) ) {
			$html .= wp_get_attachment_image(
				$variationData['image_id'],
				'woocommerce_gallery_thumbnail',
				false,
				[
					'class' => 'hd-swatch__radio-image',
				]
			);
		}

		$html .= '<span class="hd-swatch__radio-label">' . esc_html( $name ) . '</span>';

		if ( $variationData && ! empty( $variationData['price_html'] ) ) {
			$html .= '<span class="hd-swatch__radio-price">' . wp_kses_post( $variationData['price_html'] ) . '</span>';
		}

		$html .= '</span></label>';

		return $html;
	}
}
