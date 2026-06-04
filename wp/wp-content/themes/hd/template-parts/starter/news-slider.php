<?php
/**
 * Starter: News Slider — Latest posts carousel.
 *
 * Uses fx-slider (Swiper) with slidesPerView: auto + CSS width per breakpoint.
 * Desktop 3 cols, Tablet 2 cols, Mobile 1.3 cols.
 * Tailwind 4 syntax. Queries real WP posts — falls back to static data if empty.
 *
 * @package HD
 */

use HD\Core\Query;

defined( 'ABSPATH' ) || exit;

// ── Query latest posts (cached via Query trait) ──────────────────────────────
$post_ids = Query::queryByLatestPosts(
	[
		'limit'        => 6,
		'return_query' => false,
	]
);

if ( empty( $post_ids ) ) {
	return;
}
?>

<section id="news-slider" class="news-slider relative py-20 lg:py-24 bg-gray-50 dark:bg-gray-900/80 overflow-hidden" aria-labelledby="news-slider-heading">

	<!-- Decorative Glow -->
	<div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary/3 dark:bg-teal-400/5 blur-[150px] rounded-full pointer-events-none" aria-hidden="true"></div>

	<div class="container mx-auto px-6 xl:px-12 relative z-10">
		<!-- Section Header -->
		<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 mb-12">
			<div class="max-w-2xl">
				<span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/8 dark:bg-teal-400/10 text-xs font-bold tracking-wider uppercase text-primary dark:text-teal-400 mb-4">
					<span class="flex size-1.5 rounded-full bg-current animate-pulse"></span>
					Blog & Tin tức
				</span>
				<h2 id="news-slider-heading" class="text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight m-0">
					Bài viết mới nhất
				</h2>
				<p class="text-lg text-gray-500 dark:text-gray-400 mt-3 m-0 leading-relaxed">
					Cập nhật xu hướng công nghệ, thiết kế và chiến lược kinh doanh số mới nhất.
				</p>
			</div>

			<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: '#' ); ?>"
				class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-base font-semibold text-primary dark:text-teal-400 bg-primary/5 dark:bg-teal-400/10 hover:bg-primary/10 dark:hover:bg-teal-400/15 border border-primary/10 dark:border-teal-400/20 transition-all duration-300 no-underline shrink-0 group">
				Xem tất cả
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true">
					<path d="M3.5 8H12.5M12.5 8L9 4.5M12.5 8L9 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
		</div>

		<!-- Slider -->
		<div class="swiper news-swiper closest-swiper" data-fx-slider>
			<div class="swiper-wrapper"
				data-swiper-options='{"slidesPerView":"auto","spaceBetween":16,"grabCursor":true,"navigation":true,"pagination":{"type":"bullets","clickable":true},"autoplay":true,"delay":5000,"speed":600}'>

				<?php foreach ( $post_ids as $post_id ) : ?>
					<div class="swiper-slide news-slide h-auto!">
						<?php
						\HD_Helper::blockTemplate(
							'template-parts/post/loop',
							[
								'id'        => $post_id,
								'title_tag' => 'h3',
								'thumbnail' => 'medium_large',
							]
						);
						?>
					</div>
				<?php endforeach; ?>

			</div><!-- /.swiper-wrapper -->

			<!-- Controls (nav + pagination auto-created by fx-slider) -->
			<div class="swiper-controls"></div>
		</div><!-- /.swiper-container -->
	</div>
</section>
