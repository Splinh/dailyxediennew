<?php
/**
 * Archive Swatches — compact swatch display on product listing pages.
 *
 * Features:
 * - Compact swatch display (color/image/label) with overflow indicator
 * - Click-to-swap: image groups swap product card image on click
 * - Per-product attribute picker via ProductSwatchesTab settings
 * - Batch term meta preload (N queries → 1 query)
 *
 * Config read from SwatchConfig (archive_limit, archive_position).
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
use WC_Product_Variable;

defined( 'ABSPATH' ) || exit;

final class ArchiveSwatches {

	/**
	 * Register archive hooks.
	 */
	public function register(): void {
		$position = SwatchConfig::archivePosition();
		$priority = 'before' === $position ? 7 : 30;

		add_action( 'woocommerce_after_shop_loop_item', [ self::class, 'render' ], $priority );
		add_action( 'woocommerce_before_shop_loop', [ self::class, 'preloadTermMeta' ], 5 );
	}

	/**
	 * Render compact swatches on archive pages.
	 */
	public static function render(): void {
		global $product;

		if ( ! $product instanceof WC_Product_Variable ) {
			return;
		}

		$attributes = $product->get_variation_attributes();
		if ( empty( $attributes ) ) {
			return;
		}

		$maxSwatches = SwatchConfig::archiveLimit();

		// ── Attribute Picker: which attributes to show? ──
		$productSettings   = ProductSwatchesTab::getSettings( $product->get_id() );
		$archiveConfigured = ! empty( $productSettings['archive_configured'] );
		$archiveAttrs      = $productSettings['archive_attributes'] ?? [];

		if ( $archiveConfigured ) {
			// User explicitly configured: empty list = show nothing.
			if ( empty( $archiveAttrs ) ) {
				return;
			}
			$attributes = array_intersect_key( $attributes, array_flip( $archiveAttrs ) );
		}

		if ( empty( $attributes ) ) {
			return;
		}

		// P2b: Load product-level overrides for archive term rendering.
		$termOverrides = $productSettings['overrides'] ?? [];

		echo '<div class="hd-archive-swatches" data-wc-swatches data-product-id="' . esc_attr( $product->get_id() ) . '">';

		foreach ( $attributes as $taxonomy => $values ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$allTerms = wc_get_product_terms( $product->get_id(), $taxonomy, [ 'fields' => 'all' ] );
			if ( empty( $allTerms ) ) {
				continue;
			}

			// P2a: Filter terms to only those present in actual variation values.
			$validSlugs = array_map( 'strval', $values );
			$terms      = array_values(
				array_filter(
					$allTerms,
					static fn( $t ) => empty( $validSlugs ) || in_array( $t->slug, $validSlugs, true )
				)
			);
			if ( empty( $terms ) ) {
				continue;
			}

			// P2b: Per-taxonomy overrides for this product.
			$taxOverrides = $termOverrides[ $taxonomy ] ?? [];

			// Determine group type from first term that has swatch config.
			$groupType = 'label';
			$hasSwatch = false;
			foreach ( $terms as $t ) {
				$tData = SwatchMeta::getData( $t->term_id );
				if ( isset( $taxOverrides[ $t->slug ] ) ) {
					$tData = array_merge( $tData, $taxOverrides[ $t->slug ] );
				}
				if ( $tData['type'] ) {
					$hasSwatch = true;
					$groupType = $tData['type'];
					break;
				}
			}

			// Default-to-image fallback: use variation images when no swatch meta exists.
			$defaultImageMap = [];
			if ( ! $hasSwatch && SwatchConfig::defaultToImage() ) {
				$defaultImageMap = self::getVariationImageMap( $product, $taxonomy );
				if ( ! empty( $defaultImageMap ) ) {
					$groupType = 'image';
				}
			}

			// In auto-detect mode (no explicit archive selection), skip attributes without swatch config.
			if ( ! $hasSwatch && ! $archiveConfigured && empty( $defaultImageMap ) ) {
				continue;
			}

			$isImageGroup = ( 'image' === $groupType );

			// Build image map only for image-type groups
			$imageMap = $isImageGroup && empty( $defaultImageMap )
				? self::getVariationImageMap( $product, $taxonomy )
				: [];

			$total    = count( $terms );
			$showMax  = $maxSwatches > 0 ? min( $total, $maxSwatches ) : $total;
			$overflow = $total - $showMax;

			echo '<div class="hd-archive-swatches__group"'
				. ' data-attribute="' . esc_attr( $taxonomy ) . '"'
				. ( $isImageGroup ? ' data-image-swap' : '' ) . '>';

			for ( $i = 0; $i < $showMax; $i++ ) {
				$term = $terms[ $i ];
				$data = SwatchMeta::getData( $term->term_id );

				// P2b: Apply product-level override.
				if ( isset( $taxOverrides[ $term->slug ] ) ) {
					$data = array_merge( $data, $taxOverrides[ $term->slug ] );
				}

				$type = $data['type'] ?: 'label';

				// Default-to-image override: use variation image when no swatch meta.
				$defaultImg = $defaultImageMap[ $term->slug ] ?? null;
				if ( ! $data['type'] && $defaultImg ) {
					$type = 'image';
				}

				// Archive uses truncated label (3 chars max)
				$label = 'label' === $type ? mb_substr( $term->name, 0, 3 ) : $term->name;

				// Image swap data attributes (image-type groups only)
				$imageAttrs = '';
				$imgSource  = $imageMap[ $term->slug ] ?? $defaultImg;
				if ( $isImageGroup && $imgSource ) {
					$imageAttrs .= ' data-image-src="' . esc_url( $imgSource['src'] ) . '"';
					if ( $imgSource['srcset'] ) {
						$imageAttrs .= ' data-image-srcset="' . esc_attr( $imgSource['srcset'] ) . '"';
					}
					if ( $imgSource['sizes'] ) {
						$imageAttrs .= ' data-image-sizes="' . esc_attr( $imgSource['sizes'] ) . '"';
					}
				}

				echo '<button type="button"'
					. ' class="hd-archive-swatch hd-archive-swatch--' . esc_attr( $type ) . '"'
					. ' title="' . esc_attr( $term->name ) . '"'
					. ' data-value="' . esc_attr( $term->slug ) . '"'
					. $imageAttrs . '>';

				if ( $defaultImg && ! $data['type'] ) {
					// Render variation image as swatch.
					echo Helper::attachmentImageHTML( $defaultImg['image_id'], [ 24, 24 ], [ 'class' => 'hd-archive-swatch__image' ] );
				} else {
					echo SwatchRenderer::renderInner( $data, $label, 'hd-archive-swatch', [ 24, 24 ] );
				}

				echo '</button>';
			}

			if ( $overflow > 0 ) {
				echo '<a href="' . esc_url( $product->get_permalink() ) . '"'
					. ' class="hd-archive-swatch hd-archive-swatch--more">+'
					. esc_html( $overflow ) . '</a>';
			}

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Pre-compute variation images mapped by attribute value.
	 *
	 * Used for archive image swap on hover — stores src/srcset/sizes
	 * in data attributes for instant JS-driven image replacement.
	 *
	 * Only processes first attribute (typically color/visual) to avoid
	 * N×M data explosion on multi-attribute products.
	 *
	 * @param WC_Product_Variable $product        Variable product.
	 * @param string              $firstAttribute First attribute taxonomy.
	 *
	 * @return array<string, array{image_id: int, src: string, srcset: string, sizes: string}>
	 */
	private static function getVariationImageMap( WC_Product_Variable $product, string $firstAttribute ): array {
		$map          = [];
		$attrKey      = 'attribute_' . sanitize_title( $firstAttribute );
		$variationIds = array_values( array_filter( array_map( 'absint', $product->get_children() ) ) );

		if ( empty( $variationIds ) ) {
			return [];
		}

		update_meta_cache( 'post', $variationIds );

		$imageSize = apply_filters( 'woocommerce_thumbnail_size', 'woocommerce_thumbnail' );
		foreach ( $variationIds as $variationId ) {
			$imageId = absint( get_post_meta( $variationId, '_thumbnail_id', true ) );
			if ( ! $imageId ) {
				continue;
			}

			$value = (string) get_post_meta( $variationId, $attrKey, true );
			if ( ! $value || isset( $map[ $value ] ) ) {
				continue;
			}

			$imgSrc = wp_get_attachment_image_src( $imageId, $imageSize );

			if ( $imgSrc ) {
				$map[ $value ] = [
					'image_id' => $imageId,
					'src'      => $imgSrc[0],
					'srcset'   => wp_get_attachment_image_srcset( $imageId, $imageSize ) ?: '',
					'sizes'    => wp_get_attachment_image_sizes( $imageId, $imageSize ) ?: '',
				];
			}
		}

		return $map;
	}

	/**
	 * Batch preload term meta for all products on archive page.
	 *
	 * Runs BEFORE the product loop starts — populates WP object cache.
	 * Result: ~180 queries (12 products × 3 attrs × 5 terms) → 1 query.
	 */
	public static function preloadTermMeta(): void {
		global $wp_query;

		if ( empty( $wp_query->posts ) ) {
			return;
		}

		$termIds = [];
		foreach ( $wp_query->posts as $post ) {
			$product = wc_get_product( $post );
			if ( ! $product instanceof WC_Product_Variable ) {
				continue;
			}

			foreach ( $product->get_variation_attributes() as $taxonomy => $values ) {
				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				$terms   = wc_get_product_terms( $product->get_id(), $taxonomy, [ 'fields' => 'ids' ] );
				$termIds = array_merge( $termIds, $terms );
			}
		}

		if ( $termIds ) {
			update_meta_cache( 'term', array_unique( $termIds ) );
		}
	}
}
