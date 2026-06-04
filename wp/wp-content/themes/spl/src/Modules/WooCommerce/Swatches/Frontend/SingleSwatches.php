<?php
/**
 * Single Product Swatches — replace WC dropdown with visual swatches.
 *
 * Render strategy (3 branches):
 *   1. Taxonomy with swatch meta → renderTaxonomySwatches() [color/image/label per-term]
 *   2. Taxonomy without swatch meta → renderButtonSwatches() [default-to-button]
 *   3. Custom (non-taxonomy) attribute → renderCustomAttributeButtons()
 *
 * Config read from SwatchConfig (backed by WCSettings admin UI).
 * UI delegated to SwatchRenderer.
 *
 * @package SPL\Modules\WooCommerce\Swatches\Frontend
 */

namespace SPL\Modules\WooCommerce\Swatches\Frontend;

use SPL\Core\Helper;
use SPL\Modules\WooCommerce\Swatches\Admin\ProductSwatchesTab;
use SPL\Modules\WooCommerce\Swatches\SwatchConfig;
use SPL\Modules\WooCommerce\Swatches\SwatchMeta;
use SPL\Modules\WooCommerce\Swatches\SwatchRenderer;
use WC_Product;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

final class SingleSwatches {

	/**
	 * Register single product hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_dropdown_variation_attribute_options_html', [ $this, 'render' ], 10, 2 );

		// Out-of-stock variation visual — WC JS adds .disabled class
		add_filter( 'woocommerce_variation_is_active', [ self::class, 'filterVariationActive' ], 10, 2 );
	}

	/**
	 * Deactivate out-of-stock variations so WC JS applies .disabled class.
	 *
	 * @param bool                 $active    Whether variation is active.
	 * @param WC_Product_Variation $variation Variation object.
	 *
	 * @return bool
	 */
	public static function filterVariationActive( bool $active, WC_Product_Variation $variation ): bool {
		return $variation->is_in_stock() ? $active : false;
	}

	/**
	 * Replace WC dropdown with visual swatches for an attribute.
	 *
	 * @param string $html Original dropdown HTML.
	 * @param array  $args Arguments passed by WC.
	 *
	 * @return string
	 */
	public function render( string $html, array $args ): string {
		$attribute = $args['attribute'] ?? '';
		$product   = $args['product'] ?? null;

		if ( ! $product instanceof WC_Product ) {
			return $html;
		}

		$selected = $args['selected'] ?? '';
		$options  = $args['options'] ?? [];

		// ── Branch 1 & 2: Taxonomy attribute ──
		if ( taxonomy_exists( $attribute ) ) {
			$terms = wc_get_product_terms( $product->get_id(), $attribute, [ 'fields' => 'all' ] );
			if ( empty( $terms ) ) {
				return $html;
			}

			// Check if ANY term has swatch data
			$hasSwatch = false;
			foreach ( $terms as $term ) {
				if ( SwatchMeta::hasSwatch( $term->term_id ) ) {
					$hasSwatch = true;
					break;
				}
			}

			if ( $hasSwatch ) {
				return $this->renderTaxonomySwatches( $html, $args, $terms, $product );
			}

			// Fallback: default-to-button
			if ( SwatchConfig::defaultToButton() ) {
				return $this->renderButtonSwatches( $html, $args, $terms, $product );
			}

			return $html;
		}

		// ── Branch 3: Custom attribute (non-taxonomy) ──
		if ( SwatchConfig::defaultToButton() && ! empty( $options ) ) {
			return $this->renderCustomAttributeButtons( $html, $args );
		}

		return $html;
	}

	/* ──────────────────────────────────────────────── */
	/* Branch 1: Taxonomy with swatch meta             */
	/* ──────────────────────────────────────────────── */

	/**
	 * Render taxonomy terms with configured swatch types (color/image/label).
	 *
	 * @param string      $html    Original dropdown HTML.
	 * @param array       $args    WC args.
	 * @param \WP_Term[]  $terms   Product terms.
	 * @param WC_Product  $product Current product.
	 *
	 * @return string
	 */
	private function renderTaxonomySwatches( string $html, array $args, array $terms, WC_Product $product ): string {
		$attribute = $args['attribute'];
		$selected  = $args['selected'] ?? '';
		$options   = $args['options'] ?? [];

		// D1: Load product-level overrides
		$productSettings = ProductSwatchesTab::getSettings( $product->get_id() );
		$termOverrides   = $productSettings['overrides'][ $attribute ] ?? [];

		// Detect if any term uses radio type (for container class)
		$hasRadio = false;
		foreach ( $terms as $term ) {
			$data = SwatchMeta::getData( $term->term_id );
			if ( isset( $termOverrides[ $term->slug ] ) ) {
				$data = array_merge( $data, $termOverrides[ $term->slug ] );
			}
			if ( 'radio' === ( $data['type'] ?: 'label' ) ) {
				$hasRadio = true;
				break;
			}
		}

		// Stock info map (slug => label string)
		$stockMap = SwatchConfig::showStockInfo()
			? $this->getStockMap( $product, $attribute, SwatchConfig::stockThreshold() )
			: [];

		// Pre-fetch variation data for radio type
		$variationDataMap = $hasRadio
			? $this->getVariationDataMap( $product, $attribute )
			: [];

		$extraClasses = $hasRadio ? [ 'hd-swatches--radio' ] : [];
		$swatches     = '<div ' . $this->buildContainerAttrs( $attribute, $extraClasses ) . '>';

		foreach ( $terms as $term ) {
			if ( ! empty( $options ) && ! in_array( $term->slug, $options, true ) ) {
				continue;
			}

			$data = SwatchMeta::getData( $term->term_id );

			// D4: Apply product-level override
			if ( isset( $termOverrides[ $term->slug ] ) ) {
				$data = array_merge( $data, $termOverrides[ $term->slug ] );
			}

			$type     = $data['type'] ?: 'label';
			$isActive = $selected === $term->slug;

			// D2: Radio type — render radio markup
			if ( 'radio' === $type ) {
				$swatches .= SwatchRenderer::renderRadio(
					$term->slug,
					$term->name,
					$attribute,
					$isActive,
					$variationDataMap[ $term->slug ] ?? null
				);
				continue;
			}

			$classes = [ 'hd-swatch', 'hd-swatch--' . $type ];
			if ( $isActive ) {
				$classes[] = 'is-selected';
			}

			$tooltipAttrs = SwatchConfig::tooltipEnabled()
				? SwatchRenderer::buildTooltipAttrs( $term, $data )
				: '';

			$swatches .= $this->buildButton( $term->slug, $term->name, $classes, $isActive, $tooltipAttrs );
			$swatches .= SwatchRenderer::renderInner( $data, $term->name );

			// Stock info label
			if ( isset( $stockMap[ $term->slug ] ) ) {
				$stockLabel = $stockMap[ $term->slug ];
				$lowClass   = $stockLabel['is_low'] ? ' hd-swatch__stock--low' : '';
				$swatches  .= '<span class="hd-swatch__stock' . $lowClass . '">' . esc_html( $stockLabel['text'] ) . '</span>';
			}

			$swatches .= '</button>';
		}

		$swatches .= '</div>';

		return $swatches . self::hiddenSelect( $html );
	}

	/* ──────────────────────────────────────────────── */
	/* Branch 2: Taxonomy without swatch meta          */
	/* ──────────────────────────────────────────────── */

	/**
	 * Render taxonomy terms as plain button swatches (no color/image configured).
	 *
	 * If default-to-image is enabled and variations have unique images,
	 * renders image swatches instead of text buttons.
	 *
	 * @param string        $html    Original dropdown HTML.
	 * @param array         $args    WC args.
	 * @param \WP_Term[]    $terms   Product terms.
	 * @param WC_Product    $product Current product.
	 *
	 * @return string
	 */
	private function renderButtonSwatches( string $html, array $args, array $terms, WC_Product $product ): string {
		$attribute = $args['attribute'];
		$selected  = $args['selected'] ?? '';
		$options   = $args['options'] ?? [];

		// Try default-to-image: use variation images as swatch
		$imageMap = [];
		if ( SwatchConfig::defaultToImage() ) {
			$imageMap = $this->getVariationImages( $product, $attribute );
		}

		$swatches = '<div ' . $this->buildContainerAttrs( $attribute ) . '>';

		foreach ( $terms as $term ) {
			if ( ! empty( $options ) && ! in_array( $term->slug, $options, true ) ) {
				continue;
			}

			$isActive = $selected === $term->slug;
			$hasImage = ! empty( $imageMap[ $term->slug ] );
			$type     = $hasImage ? 'image' : 'label';
			$classes  = [ 'hd-swatch', 'hd-swatch--' . $type ];

			if ( $isActive ) {
				$classes[] = 'is-selected';
			}

			$tooltipAttrs = SwatchConfig::tooltipEnabled()
				? ' data-wvstooltip="' . esc_attr( $term->name ) . '"'
				: '';

			$swatches .= $this->buildButton( $term->slug, $term->name, $classes, $isActive, $tooltipAttrs );

			if ( $hasImage ) {
				$swatches .= Helper::attachmentImageHTML(
					$imageMap[ $term->slug ],
					'woocommerce_gallery_thumbnail',
					[ 'class' => 'hd-swatch__image' ]
				);
			} else {
				$swatches .= '<span class="hd-swatch__label">' . esc_html( $term->name ) . '</span>';
			}

			$swatches .= '</button>';
		}

		$swatches .= '</div>';

		return $swatches . self::hiddenSelect( $html );
	}

	/* ──────────────────────────────────────────────── */
	/* Branch 3: Custom attribute (non-taxonomy)       */
	/* ──────────────────────────────────────────────── */

	/**
	 * Render custom (non-taxonomy) attribute values as button swatches.
	 *
	 * @param string $html Original dropdown HTML.
	 * @param array  $args WC args.
	 *
	 * @return string
	 */
	private function renderCustomAttributeButtons( string $html, array $args ): string {
		$attribute = $args['attribute'];
		$selected  = $args['selected'] ?? '';
		$options   = $args['options'] ?? [];

		$swatches = '<div ' . $this->buildContainerAttrs( $attribute ) . '>';

		foreach ( $options as $option ) {
			$isActive = sanitize_title( $selected ) === sanitize_title( $option );
			$classes  = [ 'hd-swatch', 'hd-swatch--label' ];
			if ( $isActive ) {
				$classes[] = 'is-selected';
			}

			$tooltipAttrs = SwatchConfig::tooltipEnabled()
				? ' data-wvstooltip="' . esc_attr( $option ) . '"'
				: '';

			$swatches .= $this->buildButton( $option, $option, $classes, $isActive, $tooltipAttrs );
			$swatches .= '<span class="hd-swatch__label">' . esc_html( $option ) . '</span>';
			$swatches .= '</button>';
		}

		$swatches .= '</div>';

		return $swatches . self::hiddenSelect( $html );
	}

	/* ──────────────────────────────────────────────── */
	/* Shared helpers                                  */
	/* ──────────────────────────────────────────────── */

	/**
	 * Build opening <button> tag for a swatch (without closing tag).
	 *
	 * @param string   $value        Attribute value (slug or option string).
	 * @param string   $name         Human-readable name.
	 * @param string[] $classes      CSS classes.
	 * @param bool     $isActive     Whether this swatch is currently selected.
	 * @param string   $tooltipAttrs Pre-built tooltip HTML attributes.
	 *
	 * @return string Opening <button> tag.
	 */
	private function buildButton( string $value, string $name, array $classes, bool $isActive, string $tooltipAttrs ): string {
		return '<button type="button"'
			. ' class="' . esc_attr( implode( ' ', $classes ) ) . '"'
			. ' data-value="' . esc_attr( $value ) . '"'
			. ' title="' . esc_attr( $name ) . '"'
			. ' aria-label="' . esc_attr( $name ) . '"'
			. ' role="radio"'
			. ' aria-checked="' . ( $isActive ? 'true' : 'false' ) . '"'
			. ' tabindex="0"'
			. $tooltipAttrs . '>';
	}

	/**
	 * Wrap original <select> in a visually-hidden container.
	 *
	 * WC JS depends on the hidden select for variation matching.
	 *
	 * @param string $html Original dropdown HTML.
	 *
	 * @return string Hidden select wrapper.
	 */
	private static function hiddenSelect( string $html ): string {
		return '<div class="hd-swatches-select-wrap" style="position:absolute;overflow:hidden;clip:rect(0,0,0,0);width:1px;height:1px">'
			. $html . '</div>';
	}

	/**
	 * Build container HTML attributes from SwatchConfig.
	 *
	 * Single source for all config-driven container attributes.
	 * JS reads `data-*` attributes; CSS reads BEM modifier classes.
	 *
	 * @param string $attribute WC attribute taxonomy name.
	 *
	 * @return string HTML attributes string.
	 */
	private function buildContainerAttrs( string $attribute, array $extraClasses = [] ): string {
		$classes = [
			'hd-swatches',
			'hd-swatches--' . SwatchConfig::shapeStyle(),
			'hd-swatches--disabled-' . SwatchConfig::disabledStyle(),
			...$extraClasses,
		];

		$attrs = 'class="' . esc_attr( implode( ' ', $classes ) ) . '"'
			. ' role="radiogroup"'
			. ' aria-label="' . esc_attr( wc_attribute_label( $attribute ) ) . '"'
			. ' data-wc-swatches'
			. ' data-attribute="' . esc_attr( sanitize_title( $attribute ) ) . '"';

		if ( SwatchConfig::clearOnReselect() ) {
			$attrs .= ' data-clear-reselect';
		}

		if ( SwatchConfig::showSelectedLabel() ) {
			$attrs .= ' data-show-label'
				. ' data-label-separator="' . esc_attr( SwatchConfig::labelSeparator() ) . '"';
		}

		$limit = SwatchConfig::displayLimit();
		if ( $limit > 0 ) {
			$attrs .= ' data-display-limit="' . $limit . '"';
		}

		if ( SwatchConfig::linkableUrl() ) {
			$attrs .= ' data-linkable-url';
		}

		if ( SwatchConfig::imagePreview() ) {
			$attrs .= ' data-preview-attribute';
		}

		return $attrs;
	}

	/**
	 * Get variation image IDs mapped by attribute value.
	 *
	 * Used by default-to-image fallback: when no swatch meta exists but
	 * variations have unique images, automatically render image swatches.
	 *
	 * Performance: loops through children — OK for single product page
	 * (variation data is already cached by WC). Do NOT use on archive.
	 *
	 * @param WC_Product $product   Current product.
	 * @param string     $attribute Attribute taxonomy name.
	 *
	 * @return array<string, int> slug => attachment_id
	 */
	private function getVariationImages( WC_Product $product, string $attribute ): array {
		$map     = [];
		$attrKey = 'attribute_' . sanitize_title( $attribute );

		foreach ( $product->get_children() as $variationId ) {
			$variation = wc_get_product( $variationId );
			if ( ! $variation instanceof WC_Product_Variation || ! $variation->get_image_id() ) {
				continue;
			}

			$attrs = $variation->get_variation_attributes();
			$value = $attrs[ $attrKey ] ?? '';

			// Only store first match per term (avoid duplicates)
			if ( $value && ! isset( $map[ $value ] ) ) {
				$map[ $value ] = $variation->get_image_id();
			}
		}

		return $map;
	}

	/**
	 * Get variation stock info mapped by attribute value.
	 *
	 * Returns stock labels for swatches (e.g. "3 left", "In stock", "Out of stock").
	 * Only includes entry when stock quantity ≤ threshold (or threshold = 0 for always).
	 *
	 * @param WC_Product $product   Current product.
	 * @param string     $attribute Attribute taxonomy name.
	 * @param int        $threshold Show count only when stock ≤ this (0 = always).
	 *
	 * @return array<string, array{text: string, is_low: bool}>
	 */
	private function getStockMap( WC_Product $product, string $attribute, int $threshold ): array {
		$map     = [];
		$attrKey = 'attribute_' . sanitize_title( $attribute );

		foreach ( $product->get_children() as $variationId ) {
			$variation = wc_get_product( $variationId );
			if ( ! $variation instanceof WC_Product_Variation ) {
				continue;
			}

			$attrs = $variation->get_variation_attributes();
			$value = $attrs[ $attrKey ] ?? '';

			if ( ! $value || isset( $map[ $value ] ) ) {
				continue;
			}

			if ( ! $variation->is_in_stock() ) {
				$map[ $value ] = [
					'text'   => __( 'Out of stock', 'SPL' ),
					'is_low' => true,
				];
				continue;
			}

			if ( ! $variation->managing_stock() ) {
				continue; // No stock management — skip (always "In stock")
			}

			$qty = $variation->get_stock_quantity();
			if ( null === $qty ) {
				continue;
			}

			// Show only if at or below threshold (or threshold = 0 means always)
			if ( 0 === $threshold || $qty <= $threshold ) {
				$map[ $value ] = [
					/* translators: %d: stock quantity */
					'text'   => sprintf( _n( '%d left', '%d left', $qty, 'SPL' ), $qty ),
					'is_low' => $qty <= $threshold,
				];
			}
		}

		return $map;
	}

	/**
	 * Get variation data (image_id, price_html) mapped by attribute value.
	 *
	 * Used by radio swatch type to display variation image and price.
	 *
	 * @param WC_Product $product   Current product.
	 * @param string     $attribute Attribute taxonomy name.
	 *
	 * @return array<string, array{image_id: int, price_html: string}>
	 */
	private function getVariationDataMap( WC_Product $product, string $attribute ): array {
		$map     = [];
		$attrKey = 'attribute_' . sanitize_title( $attribute );

		foreach ( $product->get_children() as $variationId ) {
			$variation = wc_get_product( $variationId );
			if ( ! $variation instanceof WC_Product_Variation ) {
				continue;
			}

			$attrs = $variation->get_variation_attributes();
			$value = $attrs[ $attrKey ] ?? '';

			if ( ! $value || isset( $map[ $value ] ) ) {
				continue;
			}

			$map[ $value ] = [
				'image_id'   => $variation->get_image_id(),
				'price_html' => $variation->get_price_html(),
			];
		}

		return $map;
	}
}
