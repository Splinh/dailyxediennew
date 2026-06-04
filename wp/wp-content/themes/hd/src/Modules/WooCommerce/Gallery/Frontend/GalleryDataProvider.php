<?php
/**
 * Gallery Data Provider — centralized data building for gallery rendering.
 *
 * Responsible for:
 * - Collecting product image IDs (with deduplication)
 * - Building image data arrays (src, thumb, full, srcset, sizes, alt, video)
 * - Variation gallery resolution & caching
 * - Default variation detection
 * - Layout normalization
 *
 * Video-specific logic (detection, thumbnails, injection) → VideoHelper.
 *
 * @package HD\Modules\WooCommerce\Gallery\Frontend
 */

namespace HD\Modules\WooCommerce\Gallery\Frontend;

use HD\Core\Helper;
use WC_Product;
use WC_Product_Variable;

defined( 'ABSPATH' ) || exit;

final class GalleryDataProvider {

	public const VARIATION_META_KEY   = '_hd_variation_gallery';
	public const PRODUCT_VIDEO_KEY    = '_hd_product_video_url';
	public const PRODUCT_VIDEO_POSTER = '_hd_product_video_poster';
	public const MEDIA_URL_KEY        = '_hd_media_url';

	private const VALID_LAYOUTS = [ 'below', 'above', 'left', 'right', 'stacked' ];

	// ── Layout ──────────────────────────────────────

	/**
	 * Normalize layout value — backwards compat for horizontal/vertical.
	 */
	public static function normalizeLayout( string $layout ): string {
		return match ( $layout ) {
			'horizontal' => 'below',
			'vertical'   => 'left',
			default      => in_array( $layout, self::VALID_LAYOUTS, true ) ? $layout : 'below',
		};
	}

	// ── Image IDs ───────────────────────────────────

	/**
	 * Collect unique, non-empty attachment IDs for a product gallery.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return int[] Attachment IDs (main image first).
	 */
	public static function collectImageIds( WC_Product $product ): array {
		$mainImageId = $product->get_image_id();
		$galleryIds  = $product->get_gallery_image_ids();

		return array_values(
			array_unique(
				array_filter(
					$mainImageId ? array_merge( [ $mainImageId ], $galleryIds ) : $galleryIds
				)
			)
		);
	}

	// ── Image Data ──────────────────────────────────

	/**
	 * Build image data for a single attachment.
	 *
	 * @param int $attachmentId Attachment post ID.
	 * @param int $productId    Product post ID (used for alt fallback).
	 *
	 * @return array{src: string, width: int, height: int, thumb: string, full: string, srcset: string, sizes: string, alt: string, video?: string, video_type?: string}
	 */
	public static function getImageData( int $attachmentId, int $productId ): array {
		$srcData = wp_get_attachment_image_src( $attachmentId, 'woocommerce_single' );

		$data = [
			'src'    => $srcData ? $srcData[0] : '',
			'width'  => $srcData ? $srcData[1] : 0,
			'height' => $srcData ? $srcData[2] : 0,
			'thumb'  => Helper::attachmentImageSrc( $attachmentId, 'woocommerce_thumbnail' ) ?: '',
			'full'   => Helper::attachmentImageSrc( $attachmentId, 'full' ) ?: '',
			'srcset' => wp_get_attachment_image_srcset( $attachmentId, 'woocommerce_single' ) ?: '',
			'sizes'  => wp_get_attachment_image_sizes( $attachmentId, 'woocommerce_single' ) ?: '',
			'alt'    => get_post_meta( $attachmentId, '_wp_attachment_image_alt', true )
						?: get_the_title( $productId ),
		];

		// F5: Check for attached video URL
		$mediaUrl = get_post_meta( $attachmentId, self::MEDIA_URL_KEY, true );
		if ( $mediaUrl ) {
			$data['video']      = $mediaUrl;
			$data['video_type'] = VideoHelper::detectType( $mediaUrl );
		}

		return $data;
	}

	/**
	 * Build image data array for all product images.
	 *
	 * @param int[] $attachmentIds Attachment IDs.
	 * @param int   $productId    Product post ID.
	 *
	 * @return array[] Array of image data.
	 */
	public static function buildImagesData( array $attachmentIds, int $productId ): array {
		return array_map(
			static fn( int $id ) => self::getImageData( $id, $productId ),
			$attachmentIds
		);
	}

	// ── Variation Galleries ─────────────────────────

	/**
	 * Get variation galleries — ONLY for variations that have custom gallery.
	 * Optimizes JSON payload: 50 variations with only 3 custom galleries → 3 entries.
	 *
	 * @param WC_Product $product The parent product.
	 *
	 * @return array<int, array> Variation ID => array of image data.
	 */
	public static function getVariationGalleries( WC_Product $product ): array {
		if ( ! $product instanceof WC_Product_Variable ) {
			return [];
		}

		$galleries   = [];
		$childrenIds = $product->get_children();

		// Batch preload variation meta — prevents N+1 on get_post_meta inside loop
		if ( $childrenIds ) {
			update_meta_cache( 'post', $childrenIds );
		}

		foreach ( $childrenIds as $variationId ) {
			$galleryIds = get_post_meta( $variationId, self::VARIATION_META_KEY, true );

			if ( empty( $galleryIds ) ) {
				continue;
			}

			$galleryIds = array_filter( array_map( 'absint', (array) $galleryIds ) );
			if ( empty( $galleryIds ) ) {
				continue;
			}

			// Prepend variation image if exists
			$variationImageId = get_post_thumbnail_id( $variationId );
			if ( $variationImageId && ! in_array( $variationImageId, $galleryIds, true ) ) {
				array_unshift( $galleryIds, absint( $variationImageId ) );
			}

			$galleries[ $variationId ] = self::buildImagesData( $galleryIds, $product->get_id() );
		}

		return $galleries;
	}

	// ── Default Variation Resolution ────────────────

	/**
	 * Resolve default variation ID from request or product defaults.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return int|null Variation ID or null.
	 */
	public static function resolveDefaultVariation( WC_Product $product ): ?int {
		if ( ! $product instanceof WC_Product_Variable ) {
			return null;
		}

		$dataStore = \WC_Data_Store::load( 'product' );

		// 1. Check URL param (e.g. ?variation_id=123)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requestedId = absint( $_REQUEST['variation_id'] ?? 0 );
		if (
			$requestedId
			&& 'product_variation' === get_post_type( $requestedId )
			&& $product->get_id() === (int) wp_get_post_parent_id( $requestedId )
		) {
			return $requestedId;
		}

		// 2. Check attribute_* URL params (from Linkable URL feature).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$urlAttrs = [];
		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( str_starts_with( $key, 'attribute_' ) && '' !== $value ) {
				$urlAttrs[ sanitize_title( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}

		if ( ! empty( $urlAttrs ) ) {
			$variationId = $dataStore->find_matching_product_variation( $product, $urlAttrs );
			if ( $variationId ) {
				return $variationId;
			}
		}

		// 3. Fallback: product default attributes.
		$defaults = $product->get_default_attributes();
		if ( empty( $defaults ) ) {
			return null;
		}

		$attrs = [];
		foreach ( $defaults as $key => $value ) {
			if ( '' !== $value ) {
				$attrs[ "attribute_{$key}" ] = $value;
			}
		}

		if ( empty( $attrs ) ) {
			return null;
		}

		$variationId = $dataStore->find_matching_product_variation( $product, $attrs );

		return $variationId ?: null;
	}
}
