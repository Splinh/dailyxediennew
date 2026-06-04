<?php
/**
 * Gallery Renderer — frontend product gallery output.
 *
 * Orchestrates gallery HTML rendering using data from GalleryDataProvider.
 * Renders: Swiper slider, Stacked grid, Thumbnails, Mini (Quick View).
 * Shared partials: renderSlide(), renderVideoOverlay() — zero duplication.
 *
 * @package HD\Modules\WooCommerce\Gallery\Frontend
 */

namespace HD\Modules\WooCommerce\Gallery\Frontend;

use HD\Core\Helper;
use HD\Modules\WooCommerce\WooCommerceModule;
use WC_Product;

defined( 'ABSPATH' ) || exit;

final class GalleryRenderer {

	/**
	 * Register frontend hooks.
	 */
	public function register(): void {
		// Replace default WC gallery output
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
		add_action( 'woocommerce_before_single_product_summary', [ self::class, 'render' ], 20 );
	}

	// ── Main Render ────────────────────────────────

	/**
	 * Render the full product gallery (main slider + thumbs + zoom).
	 */
	public static function render(): void {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$settings      = WooCommerceModule::getCachedOptions();
		$layout        = GalleryDataProvider::normalizeLayout( $settings['gallery_layout'] ?? 'below' );
		$zoomOn        = ! empty( $settings['gallery_zoom'] );
		$variationMode = $settings['gallery_variation_mode'] ?? 'replace';
		$showNav       = ! empty( $settings['gallery_nav_arrows'] );

		// Collect and build image data
		$allIds = GalleryDataProvider::collectImageIds( $product );

		// F6: Per-product video — check early so video-only products don't get placeholder
		$productVideoUrl = get_post_meta( $product->get_id(), GalleryDataProvider::PRODUCT_VIDEO_KEY, true );
		$videoPosition   = $settings['gallery_product_video_pos'] ?? 'first_slide';

		if ( empty( $allIds ) && ! $productVideoUrl ) {
			echo wp_kses_post( wc_placeholder_img( 'woocommerce_single' ) );

			return;
		}

		update_meta_cache( 'post', $allIds );
		$images             = GalleryDataProvider::buildImagesData( $allIds, $product->get_id() );
		$variationGalleries = GalleryDataProvider::getVariationGalleries( $product );

		// Preserve original parent gallery for data-default-images (JS reset_data).
		// $images will be mutated below for initial render, but defaultImages stays pristine.
		$defaultImages = $images;

		// F2: Resolve default variation → show its gallery on page load
		$defaultVarId = GalleryDataProvider::resolveDefaultVariation( $product );
		if ( $defaultVarId ) {
			if ( isset( $variationGalleries[ $defaultVarId ] ) ) {
				// Variation has custom gallery — use it based on mode.
				$varImages = $variationGalleries[ $defaultVarId ];
				$images    = 'prepend' === $variationMode
					? self::mergeVariationImages( $varImages, $images )
					: $varImages;
			} else {
				// No custom gallery — swap first image with variation featured (WC default).
				$varThumbId = (int) get_post_thumbnail_id( $defaultVarId );
				if ( $varThumbId && ! empty( $images ) ) {
					$varImageData = GalleryDataProvider::buildImagesData( [ $varThumbId ], $product->get_id() );
					if ( ! empty( $varImageData ) ) {
						$images[0] = $varImageData[0];
					}
				}
			}
		}

		// F6: Per-product video (URL + position already resolved above)
		if ( $productVideoUrl ) {
			VideoHelper::injectVideo( $images, $productVideoUrl, $videoPosition, $product->get_id() );
		}

		$layoutClass  = 'hd-gallery--' . esc_attr( $layout );
		$thumbsBefore = in_array( $layout, [ 'left', 'above' ], true );

		// Zoom attributes from settings
		$zoomAttrs = '';
		if ( $zoomOn ) {
			$zoomScale = (float) ( $settings['gallery_zoom_scale'] ?? 2 );
			$lensSize  = absint( $settings['gallery_lens_size'] ?? 150 );
			$lensMode  = $settings['gallery_lens_mode'] ?? 'circle';

			$zoomAttrs = sprintf(
				' data-zoom-scale="%s" data-lens-size="%d" data-lens-mode="%s"',
				esc_attr( $zoomScale ),
				$lensSize,
				esc_attr( $lensMode )
			);
		}

		// Aspect ratio + object-fit
		$aspectRatio = Helper::getAspectRatio( 'product', '', 'as-1-1' );
		$objectFit   = $settings['gallery_object_fit'] ?? 'contain';

		// F9: Stacked layout — all images visible, no slider
		if ( 'stacked' === $layout ) {
			self::renderStacked( $images, $defaultImages, $layoutClass, $variationGalleries, $variationMode, $productVideoUrl, $videoPosition, $aspectRatio->class, $objectFit );

			return;
		}

		?>
		<div class="hd-gallery hd-gallery--skeleton <?php echo esc_attr( $layoutClass ); ?>" data-wc-gallery
			style="--gallery-object-fit:<?php echo esc_attr( $objectFit ); ?>"
			<?php self::renderGalleryDataAttrs( $variationGalleries, $defaultImages, $variationMode, $settings ); ?>
		>
			<?php if ( $thumbsBefore ) : ?>
				<?php self::renderThumbs( $images, $aspectRatio->class, $showNav ); ?>
			<?php endif; ?>

			<div class="hd-gallery__main">
				<div class="swiper hd-gallery__slider">
					<div class="swiper-wrapper">
						<?php foreach ( $images as $img ) : ?>
							<div class="swiper-slide">
								<?php self::renderSlide( $img, $zoomAttrs, $zoomOn, $aspectRatio->class ); ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php if ( $showNav && count( $images ) > 1 ) : ?>
						<?php self::renderNavArrows( 'hd-gallery__nav' ); ?>
					<?php endif; ?>
				</div>

				<?php self::renderVideoOverlay( $productVideoUrl, $videoPosition ); ?>
			</div>

			<?php if ( ! $thumbsBefore ) : ?>
				<?php self::renderThumbs( $images, $aspectRatio->class, $showNav ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	// ── Stacked Layout ─────────────────────────────

	/**
	 * Render stacked layout — all images visible, no slider.
	 */
	private static function renderStacked(
		array $images,
		array $defaultImages,
		string $layoutClass,
		array $variationGalleries,
		string $variationMode,
		string $productVideoUrl,
		string $videoPosition,
		string $aspectClass = 'as-1-1',
		string $objectFit = 'contain'
	): void {
		?>
		<div class="hd-gallery <?php echo esc_attr( $layoutClass ); ?>" data-wc-gallery
			style="--gallery-object-fit:<?php echo esc_attr( $objectFit ); ?>"
			<?php if ( ! empty( $variationGalleries ) ) : ?>
				data-variation-galleries="<?php echo esc_attr( wp_json_encode( $variationGalleries ) ); ?>"
			<?php endif; ?>
			data-default-images="<?php echo esc_attr( wp_json_encode( $defaultImages ) ); ?>"
			data-variation-mode="<?php echo esc_attr( $variationMode ); ?>"
		>
			<div class="hd-gallery__stacked">
				<?php foreach ( $images as $img ) : ?>
					<div class="hd-gallery__stacked-item">
						<?php self::renderSlide( $img, '', false, $aspectClass ); ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php self::renderVideoOverlay( $productVideoUrl, $videoPosition ); ?>
		</div>
		<?php
	}

	// ── Shared Partials ────────────────────────────

	/**
	 * Render a single slide — video or image with zoom.
	 * Used by both slider and stacked layouts (DRY).
	 *
	 * @param array  $img         Image data.
	 * @param string $zoomAttrs   Zoom data attributes string.
	 * @param bool   $zoomOn      Whether zoom lens is enabled.
	 * @param string $aspectClass Aspect ratio CSS class.
	 */
	private static function renderSlide( array $img, string $zoomAttrs, bool $zoomOn, string $aspectClass = 'as-1-1' ): void {
		$imgWidth  = (int) ( $img['width'] ?? 0 );
		$imgHeight = (int) ( $img['height'] ?? 0 );

		if ( ! empty( $img['video'] ) ) {
			$videoType    = $img['video_type'] ?? 'iframe';
			$isProductVid = ! empty( $img['is_product_video'] );

			if ( $isProductVid ) {
				// Product video — inline playback via JS (no lightbox)
				$fxType = $videoType;

				?>
				<div class="hd-gallery-video <?php echo esc_attr( $aspectClass ); ?>"
					data-fx-video
					data-fx-video-url="<?php echo esc_url( $img['video'] ); ?>"
					data-fx-video-type="<?php echo esc_attr( $fxType ); ?>">
					<img class="hd-gallery-video__poster"
						src="<?php echo esc_url( $img['src'] ); ?>"
						alt="<?php echo esc_attr( $img['alt'] ); ?>"
						<?php if ( $imgWidth && $imgHeight ) : ?>
							width="<?php echo esc_attr( $imgWidth ); ?>"
							height="<?php echo esc_attr( $imgHeight ); ?>"
						<?php endif; ?>
						loading="eager" />
					<span class="hd-gallery-video__play" aria-label="<?php esc_attr_e( 'Play video', 'hd' ); ?>">
						<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
					</span>
				</div>
				<?php
			} else {
				// Attachment video — opens via lightbox (grouped with images)
				// Only set data-video-type for mp4; YouTube/Vimeo auto-detected
				$videoTypeAttr = 'mp4' === $videoType ? 'mp4' : '';

				?>
				<div class="hd-gallery-video <?php echo esc_attr( $aspectClass ); ?>">
					<a href="<?php echo esc_url( $img['video'] ); ?>"
						data-lightbox="hd-gallery"
						<?php if ( $videoTypeAttr ) : ?>
							data-video-type="<?php echo esc_attr( $videoTypeAttr ); ?>"
						<?php endif; ?>
						data-caption="<?php echo esc_attr( $img['alt'] ); ?>">
						<img class="hd-gallery-video__poster"
							src="<?php echo esc_url( $img['src'] ); ?>"
							alt="<?php echo esc_attr( $img['alt'] ); ?>"
							<?php if ( $imgWidth && $imgHeight ) : ?>
								width="<?php echo esc_attr( $imgWidth ); ?>"
								height="<?php echo esc_attr( $imgHeight ); ?>"
							<?php endif; ?>
							loading="eager" />
						<span class="hd-gallery-video__play" aria-label="<?php esc_attr_e( 'Play video', 'hd' ); ?>">
							<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
						</span>
					</a>
				</div>
				<?php
			}

			return;
		}

		?>
		<div class="hd-gallery-zoom <?php echo esc_attr( $aspectClass ); ?>"<?php echo $zoomAttrs; // Pre-escaped via sprintf + esc_attr ?>>
			<a href="<?php echo esc_url( $img['full'] ); ?>"
				data-lightbox="hd-gallery"
				data-caption="<?php echo esc_attr( $img['alt'] ); ?>">
				<img class="hd-gallery-zoom__img"
					src="<?php echo esc_url( $img['src'] ); ?>"
					<?php if ( $img['srcset'] ) : ?>
						srcset="<?php echo esc_attr( $img['srcset'] ); ?>"
						sizes="<?php echo esc_attr( $img['sizes'] ); ?>"
					<?php endif; ?>
					data-zoom-src="<?php echo esc_url( $img['full'] ); ?>"
					alt="<?php echo esc_attr( $img['alt'] ); ?>"
					<?php if ( $imgWidth && $imgHeight ) : ?>
						width="<?php echo esc_attr( $imgWidth ); ?>"
						height="<?php echo esc_attr( $imgHeight ); ?>"
						data-large_image="<?php echo esc_url( $img['full'] ); ?>"
						data-large_image_width="<?php echo esc_attr( $imgWidth ); ?>"
						data-large_image_height="<?php echo esc_attr( $imgHeight ); ?>"
					<?php endif; ?>
					loading="eager" />
			</a>
			<?php if ( $zoomOn ) : ?>
				<div class="hd-gallery-zoom__lens" aria-hidden="true"></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render floating video overlay button (F6 overlay mode).
	 *
	 * @param string $videoUrl  Per-product video URL.
	 * @param string $position  Video position setting.
	 */
	private static function renderVideoOverlay( string $videoUrl, string $position ): void {
		if ( ! $videoUrl || 'overlay' !== $position ) {
			return;
		}

		$videoType = VideoHelper::detectType( $videoUrl );

		// Only set data-video-type for mp4; YouTube/Vimeo auto-detected
		$videoTypeAttr = 'mp4' === $videoType ? 'mp4' : '';

		?>
		<a href="<?php echo esc_url( $videoUrl ); ?>"
			class="hd-gallery__video-overlay"
			data-lightbox="hd-gallery-video"
			<?php if ( $videoTypeAttr ) : ?>
				data-video-type="<?php echo esc_attr( $videoTypeAttr ); ?>"
			<?php endif; ?>
			aria-label="<?php esc_attr_e( 'Play product video', 'hd' ); ?>">
			<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
		</a>
		<?php
	}

	/**
	 * Render gallery data attributes for the container div.
	 *
	 * @param array  $variationGalleries Variation galleries data.
	 * @param array  $images             Default images data.
	 * @param string $variationMode      Variation swap mode.
	 * @param array  $settings           Gallery settings.
	 */
	private static function renderGalleryDataAttrs(
		array $variationGalleries,
		array $images,
		string $variationMode,
		array $settings
	): void {
		if ( ! empty( $variationGalleries ) ) {
			echo 'data-variation-galleries="' . esc_attr( wp_json_encode( $variationGalleries ) ) . '" ';
		}

		printf(
			'data-default-images="%s" data-variation-mode="%s" data-thumbs-mobile="%d" data-thumbs-tablet="%d" data-thumbs-desktop="%d"',
			esc_attr( wp_json_encode( $images ) ),
			esc_attr( $variationMode ),
			(int) ( $settings['gallery_thumbs_mobile'] ?? 3 ),
			(int) ( $settings['gallery_thumbs_tablet'] ?? 4 ),
			(int) ( $settings['gallery_thumbs_desktop'] ?? 5 )
		);
	}

	// ── Thumbnails ──────────────────────────────────

	/**
	 * Render thumbnail strip for gallery.
	 *
	 * @param array  $images      Image data array.
	 * @param string $aspectClass Aspect ratio CSS class.
	 * @param bool   $showNav     Whether to show nav arrows.
	 */
	private static function renderThumbs( array $images, string $aspectClass = 'as-1-1', bool $showNav = true ): void {
		if ( count( $images ) < 2 ) {
			return;
		}

		?>
		<div class="hd-gallery__thumbs">
			<div class="swiper hd-gallery__thumbs-slider">
				<div class="swiper-wrapper">
					<?php foreach ( $images as $img ) : ?>
						<div class="swiper-slide<?php echo ! empty( $img['video'] ) ? ' hd-gallery__thumb--video' : ''; ?>">
							<span class="hd-gallery__thumb-frame <?php echo esc_attr( $aspectClass ); ?>">
								<img src="<?php echo esc_url( $img['thumb'] ); ?>"
									alt="<?php echo esc_attr( $img['alt'] ); ?>"
									loading="lazy" />
							</span>
							<?php if ( ! empty( $img['video'] ) ) : ?>
								<span class="hd-gallery__thumb-play" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
								</span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php if ( $showNav ) : ?>
					<?php self::renderNavArrows( 'hd-gallery__thumbs-nav' ); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	// ── Navigation Arrows ───────────────────────────

	/**
	 * Render SVG prev/next navigation arrows for Swiper.
	 *
	 * @param string $cssPrefix BEM prefix for the nav buttons.
	 */
	private static function renderNavArrows( string $cssPrefix ): void {
		?>
		<button type="button" class="<?php echo esc_attr( $cssPrefix ); ?> <?php echo esc_attr( $cssPrefix ); ?>--prev" aria-label="<?php esc_attr_e( 'Previous', 'hd' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
		</button>
		<button type="button" class="<?php echo esc_attr( $cssPrefix ); ?> <?php echo esc_attr( $cssPrefix ); ?>--next" aria-label="<?php esc_attr_e( 'Next', 'hd' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
		</button>
		<?php
	}

	/**
	 * Merge variation images with default images (prepend mode).
	 *
	 * Variation images come first, then remaining defaults (deduped by src).
	 *
	 * @param array $varImages     Variation gallery images.
	 * @param array $defaultImages Product default gallery images.
	 *
	 * @return array Merged image list.
	 */
	private static function mergeVariationImages( array $varImages, array $defaultImages ): array {
		$seen = [];
		foreach ( $varImages as $img ) {
			$seen[ $img['src'] ] = true;
		}

		$remaining = array_filter(
			$defaultImages,
			static fn( $img ) => ! isset( $seen[ $img['src'] ] )
		);

		return array_merge( $varImages, array_values( $remaining ) );
	}
}
