<?php
/**
 * Home page — Hero Slider section.
 *
 * Banner images are pre-designed at a fixed ratio — display at natural size,
 * no cropping (object-cover would cut the logo/text in the banner).
 *
 * Supports separate mobile banner (880×660) via ACF `bg_image_mobile` field.
 * Uses <picture> + <source media> to serve the correct image per breakpoint.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data   = $args ?? [];
$slides = $data['slides'] ?? [];

// Fallback slides if empty.
if ( empty( $slides ) ) {
	$slides = [
		[
			'bg_image'        => get_theme_file_uri( 'resources/img/banner-he-sang-chanh.jpg' ),
			'bg_image_mobile' => '',
			'link'            => [ 'url' => '#', 'title' => 'Xem ngay' ],
			'title'           => 'Banner Hè Sang Chảnh',
		],
	];
}
?>
<section class="relative w-full overflow-hidden bg-white"
	aria-label="<?php esc_attr_e( 'Banners nổi bật', 'spl' ); ?>">

	<!-- Slide Track -->
	<div id="hero-slider" class="relative w-full">
		<?php foreach ( $slides as $index => $slide ) :
			$img_raw    = $slide['bg_image'] ?? 0;
			$mobile_raw = $slide['bg_image_mobile'] ?? 0;

			// Desktop image: support both attachment ID and direct URL.
			if ( is_numeric( $img_raw ) && (int) $img_raw > 0 ) {
				$img_url = wp_get_attachment_image_url( (int) $img_raw, 'full' );
			} elseif ( ! empty( $img_raw ) && is_string( $img_raw ) ) {
				$img_url = $img_raw;
			} else {
				$img_url = get_theme_file_uri( 'resources/img/banner-he-sang-chanh.jpg' );
			}

			// Mobile image: optional, falls back to desktop.
			$mobile_url = '';
			if ( is_numeric( $mobile_raw ) && (int) $mobile_raw > 0 ) {
				$mobile_url = wp_get_attachment_image_url( (int) $mobile_raw, 'large' );
			} elseif ( ! empty( $mobile_raw ) && is_string( $mobile_raw ) ) {
				$mobile_url = $mobile_raw;
			}

			$link       = $slide['link'] ?? null;
			$title      = $slide['title'] ?? '';
			$is_active  = $index === 0;

			// First slide flows in document (sets container height), rest are absolute overlay.
			if ( $is_active ) :
			?>
			<div class="hero-slide relative w-full opacity-100 z-10"
				aria-hidden="false">
				<picture>
					<?php if ( $mobile_url ) : ?>
						<source media="(max-width: 767px)"
							srcset="<?php echo esc_url( $mobile_url ); ?>"
							width="880" height="660">
					<?php endif; ?>
					<img src="<?php echo esc_url( $img_url ); ?>"
						alt="<?php echo esc_attr( $title ); ?>"
						width="1920" height="750"
						loading="eager"
						fetchpriority="high"
						decoding="async"
						class="w-full h-auto block select-none"
					/>
				</picture>
				<?php if ( $link && ! empty( $link['url'] ) ) : ?>
					<a href="<?php echo esc_url( $link['url'] ); ?>"
						class="absolute inset-0 block z-[1]"
						target="<?php echo esc_attr( $link['target'] ?? '' ); ?>">
						<span class="sr-only"><?php echo esc_html( $link['title'] ?: $title ); ?></span>
					</a>
				<?php endif; ?>
			</div>
			<?php else : ?>
			<div class="hero-slide absolute inset-0 w-full h-full opacity-0 z-0 pointer-events-none"
				aria-hidden="true">
				<picture>
					<?php if ( $mobile_url ) : ?>
						<source media="(max-width: 767px)"
							srcset="<?php echo esc_url( $mobile_url ); ?>"
							width="880" height="660">
					<?php endif; ?>
					<img src="<?php echo esc_url( $img_url ); ?>"
						alt="<?php echo esc_attr( $title ); ?>"
						width="1920" height="750"
						loading="lazy"
						fetchpriority="low"
						decoding="async"
						class="w-full h-full object-contain object-top select-none"
					/>
				</picture>
				<?php if ( $link && ! empty( $link['url'] ) ) : ?>
					<a href="<?php echo esc_url( $link['url'] ); ?>"
						class="absolute inset-0 block z-[1]"
						target="<?php echo esc_attr( $link['target'] ?? '' ); ?>">
						<span class="sr-only"><?php echo esc_html( $link['title'] ?: $title ); ?></span>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>

	<?php if ( count( $slides ) > 1 ) : ?>
		<!-- Prev / Next arrows -->
		<button onclick="moveHeroSlide(-1)"
			aria-label="<?php esc_attr_e( 'Banner trước', 'spl' ); ?>"
			class="absolute left-3 sm:left-5 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white w-10 h-10 rounded-full flex items-center justify-center transition-all z-20 focus:outline-none">
			<?php echo spl_icon( 'chevron-left', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<button onclick="moveHeroSlide(1)"
			aria-label="<?php esc_attr_e( 'Banner kế tiếp', 'spl' ); ?>"
			class="absolute right-3 sm:right-5 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white w-10 h-10 rounded-full flex items-center justify-center transition-all z-20 focus:outline-none">
			<?php echo spl_icon( 'chevron-right', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>

		<!-- Dot indicators -->
		<div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20" role="tablist" aria-label="<?php esc_attr_e( 'Chọn banner', 'spl' ); ?>">
			<?php foreach ( $slides as $index => $slide ) : ?>
				<button onclick="setHeroSlide(<?php echo (int) $index; ?>)"
					role="tab"
					data-active="<?php echo $index === 0 ? 'true' : 'false'; ?>"
					aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( sprintf( __( 'Xem slide %d', 'spl' ), $index + 1 ) ); ?>"
					class="hero-dot cursor-pointer focus:outline-none">
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
