<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Display "Thông Số Kỹ Thuật" (TSKT) specifications tab on WooCommerce
 * single product pages.
 *
 * Data source: ACF repeater field `tskt_specs` on each product,
 * registered via `acf-json/group_daily_tskt.json`.
 *
 * Tab renders a striped specification table matching the
 * htmlmau/chi-tiet-san-pham.html design.
 *
 * @package SPL
 */
final class TSKTDisplay {

	public static function register(): void {
		add_filter( 'woocommerce_product_tabs', [ self::class, 'addSpecsTab' ], 50 );
		add_filter( 'woocommerce_product_tabs', [ self::class, 'addVideoTab' ], 60 );
	}

	/**
	 * Add the TSKT tab if the product has specification rows, image, or custom content.
	 *
	 * @param array<string, array<string, mixed>> $tabs Existing WC tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public static function addSpecsTab( array $tabs ): array {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return $tabs;
		}

		$pid   = $product->get_id();
		$specs = Helper::getField( 'tskt_specs', $pid )
			?: Helper::getField( 'tskt_rows', $pid )
			?: Helper::getField( 'thong_so_ky_thuat', $pid )
			?: Helper::getField( 'bang_tskt', $pid );

		$tskt_image = Helper::getField( 'tskt_image', $pid )
			?: Helper::getField( 'anh_tskt', $pid )
			?: Helper::getField( 'bang_tskt_image', $pid )
			?: get_post_meta( $pid, 'tskt_image', true );

		$tskt_content = Helper::getField( 'tskt_content', $pid )
			?: Helper::getField( 'noi_dung_tskt', $pid );

		$has_tskt = ( ! empty( $specs ) && is_array( $specs ) ) || ! empty( $tskt_image ) || ! empty( trim( (string) $tskt_content ) );

		if ( ! $has_tskt ) {
			return $tabs;
		}

		$tabs['tskt_specs'] = [
			'title'    => __( 'Thông số kỹ thuật', 'spl' ),
			'priority' => 15,
			'callback' => [ self::class, 'renderSpecsTab' ],
		];

		return $tabs;
	}

	/**
	 * Add Video Tab if video URL is present.
	 *
	 * @param array<string, array<string, mixed>> $tabs Existing WC tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public static function addVideoTab( array $tabs ): array {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return $tabs;
		}

		$pid           = $product->get_id();
		$video_url_raw = Helper::getField( 'product_video', $pid )
			?: Helper::getField( 'video_youtube', $pid )
			?: Helper::getField( 'video_tiktok', $pid )
			?: Helper::getField( 'url_video', $pid )
			?: Helper::getField( 'video_url', $pid )
			?: get_post_meta( $pid, '_product_video_url', true )
			?: get_post_meta( $pid, 'video_url', true );

		if ( empty( $video_url_raw ) ) {
			return $tabs;
		}

		$tabs['product_video_tab'] = [
			'title'    => __( 'Video thực tế', 'spl' ),
			'priority' => 25,
			'callback' => [ self::class, 'renderVideoTab' ],
		];

		return $tabs;
	}

	/**
	 * Render the specification table inside the tab panel.
	 *
	 * @return void
	 */
	public static function renderSpecsTab(): void {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$pid   = $product->get_id();
		$specs = Helper::getField( 'tskt_specs', $pid )
			?: Helper::getField( 'tskt_rows', $pid )
			?: Helper::getField( 'thong_so_ky_thuat', $pid )
			?: Helper::getField( 'bang_tskt', $pid );

		$tskt_image = Helper::getField( 'tskt_image', $pid )
			?: Helper::getField( 'anh_tskt', $pid )
			?: Helper::getField( 'bang_tskt_image', $pid )
			?: get_post_meta( $pid, 'tskt_image', true );

		$tskt_image_url = '';
		if ( is_array( $tskt_image ) ) {
			$tskt_image_url = $tskt_image['url'] ?? ( $tskt_image['sizes']['large'] ?? '' );
		} elseif ( is_numeric( $tskt_image ) && (int) $tskt_image > 0 ) {
			$tskt_image_url = wp_get_attachment_image_url( (int) $tskt_image, 'full' ) ?: '';
		} elseif ( is_string( $tskt_image ) && '' !== trim( $tskt_image ) ) {
			$tskt_image_url = trim( $tskt_image );
		}

		$tskt_content = Helper::getField( 'tskt_content', $pid )
			?: Helper::getField( 'noi_dung_tskt', $pid );

		$product_name = $product->get_name();
		?>
		<h2 class="text-lg font-black text-slate-900 mb-5">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: product name */
					__( 'Thông số kỹ thuật %s', 'spl' ),
					$product_name,
				)
			);
			?>
		</h2>

		<?php if ( ! empty( $specs ) && is_array( $specs ) ) : ?>
			<div class="overflow-x-auto mb-6">
				<table class="w-full text-sm">
					<tbody>
						<?php
						$i = 0;
						foreach ( $specs as $row ) :
							$label = trim( (string) ( $row['tskt_label'] ?? $row['label'] ?? '' ) );
							$value = trim( (string) ( $row['tskt_value'] ?? $row['value'] ?? '' ) );

							if ( '' === $label && '' === $value ) {
								continue;
							}

							$stripe = ( 0 === $i % 2 ) ? '' : ' bg-slate-50/50';
							++$i;
							?>
							<tr class="border-b border-slate-100<?php echo esc_attr( $stripe ); ?>">
								<td class="py-3 pr-4 font-semibold text-slate-700 w-1/3">
									<?php echo esc_html( $label ); ?>
								</td>
								<td class="py-3 text-slate-600">
									<?php echo esc_html( $value ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $tskt_image_url ) ) : ?>
			<div class="my-6 text-center">
				<img loading="lazy" decoding="async" src="<?php echo esc_url( $tskt_image_url ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Bảng thông số kỹ thuật %s', 'spl' ), $product_name ) ); ?>" class="max-w-full h-auto mx-auto rounded-xl shadow-sm border border-slate-100" />
			</div>
		<?php endif; ?>

		<?php if ( ! empty( trim( (string) $tskt_content ) ) ) : ?>
			<div class="my-6">
				<?php echo wp_kses_post( apply_filters( 'the_content', $tskt_content ) ); ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Video tab content.
	 *
	 * @return void
	 */
	public static function renderVideoTab(): void {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$pid           = $product->get_id();
		$video_url_raw = Helper::getField( 'product_video', $pid )
			?: Helper::getField( 'video_youtube', $pid )
			?: Helper::getField( 'video_tiktok', $pid )
			?: Helper::getField( 'url_video', $pid )
			?: Helper::getField( 'video_url', $pid )
			?: get_post_meta( $pid, '_product_video_url', true )
			?: get_post_meta( $pid, 'video_url', true );

		if ( empty( $video_url_raw ) || ! is_string( $video_url_raw ) ) {
			return;
		}

		$vurl            = trim( $video_url_raw );
		$video_type      = '';
		$video_id        = '';
		$video_embed_url = '';

		if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $vurl, $v_matches ) ) {
			$video_type      = 'youtube';
			$video_id        = $v_matches[1];
			$video_embed_url = 'https://www.youtube.com/embed/' . $video_id;
		} elseif ( preg_match( '/tiktok\.com\/(?:@[^\/]+\/video\/|v\/)(\d+)/i', $vurl, $v_matches ) ) {
			$video_type      = 'tiktok';
			$video_id        = $v_matches[1];
			$video_embed_url = 'https://www.tiktok.com/embed/v2/' . $video_id;
		} elseif ( preg_match( '/\.(mp4|webm)($|\?)/i', $vurl ) ) {
			$video_type      = 'video';
			$video_embed_url = $vurl;
		} else {
			$video_type      = 'iframe';
			$video_embed_url = $vurl;
		}
		?>
		<div class="sp-video-container max-w-4xl mx-auto rounded-2xl overflow-hidden shadow-lg border border-slate-100 bg-slate-950 aspect-video flex items-center justify-center relative min-h-[300px] md:min-h-[480px]">
			<?php if ( 'youtube' === $video_type && ! empty( $video_id ) ) : ?>
				<div class="w-full h-full relative" data-fx-video data-fx-video-url="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $video_id ); ?>" data-fx-video-type="youtube">
					<img src="<?php echo esc_url( 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg' ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" class="w-full h-full object-cover" loading="lazy" />
					<span class="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/40 transition-colors cursor-pointer">
						<svg class="w-16 h-16 text-white drop-shadow-md transition-transform hover:scale-110" fill="currentColor" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21"/></svg>
					</span>
				</div>
			<?php elseif ( 'tiktok' === $video_type && ! empty( $video_id ) ) : ?>
				<iframe src="<?php echo esc_url( 'https://www.tiktok.com/embed/v2/' . $video_id ); ?>" class="w-full h-full border-0 min-h-[500px]" allowfullscreen allow="encrypted-media"></iframe>
			<?php elseif ( 'video' === $video_type ) : ?>
				<video src="<?php echo esc_url( $video_embed_url ); ?>" controls playsinline class="w-full h-full object-contain"></video>
			<?php else : ?>
				<iframe src="<?php echo esc_url( $video_embed_url ); ?>" class="w-full h-full border-0 min-h-[350px]" allowfullscreen></iframe>
			<?php endif; ?>
		</div>
		<?php
	}
}
