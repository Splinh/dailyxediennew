<?php
/**
 * Home page — Latest News section with category tabs.
 *
 * Mirrors the portfolio-gallery tab pattern:
 * ACF repeater `tabs` → tab_label + tab_category (WP category taxonomy).
 * Uses data-fx-tabs for tab switching. Falls back to auto-detecting
 * all non-empty categories when the repeater is empty.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data       = $args ?? [];
$title      = $data['title'] ?? __( 'Tin tức nổi bật', 'spl' );
$subtitle   = $data['subtitle'] ?? __( 'Cập nhật tin tức và mẹo vặt sử dụng xe hữu ích', 'spl' );
$tabs       = $data['tabs'] ?? [];
$per_tab    = isset( $data['count'] ) ? absint( $data['count'] ) : 6;
$ratio_css  = \SPL\Core\Helper::aspectRatioClass( 'post' );

// ── Fallback: auto-detect all non-empty post categories. ──────────────
if ( empty( $tabs ) ) {
	$all_cats = get_categories( [
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
		'number'     => 6,
	] );
	if ( $all_cats && ! is_wp_error( $all_cats ) ) {
		foreach ( $all_cats as $cat ) {
			$tabs[] = [
				'tab_label'    => $cat->name,
				'tab_category' => $cat->term_id,
			];
		}
	}
}

if ( empty( $tabs ) ) {
	return;
}

// ── Pre-query posts for each tab. ─────────────────────────────────────
$tab_data = [];
foreach ( $tabs as $tab ) {
	$cat_id = (int) ( $tab['tab_category'] ?? 0 );
	$label  = $tab['tab_label'] ?? '';

	if ( ! $cat_id ) {
		continue;
	}

	if ( ! $label ) {
		$cat   = get_category( $cat_id );
		$label = ( $cat && ! is_wp_error( $cat ) ) ? $cat->name : __( 'Tab', 'spl' );
	}

	$posts = get_posts( [
		'post_type'      => 'post',
		'posts_per_page' => $per_tab,
		'post_status'    => 'publish',
		'category'       => $cat_id,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	] );

	if ( $posts ) {
		$cat_link   = get_category_link( $cat_id );
		$tab_data[] = [
			'label'    => $label,
			'posts'    => $posts,
			'cat_link' => $cat_link,
		];
	}
}

if ( empty( $tab_data ) ) {
	return;
}

$tabs_id        = 'news-tabs';
$slider_options = wp_json_encode( [
	'slidesPerView'    => 1,
	'spaceBetween'     => 12,
	'navigation'       => true,
	'watchSlidesProgress' => true,
	'observer'         => true,
	'observeParents'   => true,
	'breakpoints'      => [
		640  => [ 'slidesPerView' => 2, 'spaceBetween' => 16 ],
		1024 => [ 'slidesPerView' => 3, 'spaceBetween' => 24 ],
	],
], JSON_UNESCAPED_SLASHES );
?>
<section class="max-w-7xl mx-auto px-4 mb-8 md:mb-16 scroll-mt-24" id="news-section">
	<!-- Section header -->
	<div class="flex items-center justify-between mb-8">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-primary rounded-full"></span>
			<h2 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<span class="text-sm font-semibold text-slate-400"><?php echo esc_html( $subtitle ); ?></span>
	</div>

	<?php if ( count( $tab_data ) > 1 ) : ?>
		<!-- Tab navigation -->
		<ul id="<?php echo esc_attr( $tabs_id ); ?>" class="tabs-list flex gap-2 mb-6" data-fx-tabs>
			<?php foreach ( $tab_data as $i => $td ) : ?>
				<li class="tabs-title<?php echo 0 === $i ? ' is-active' : ''; ?>">
					<button type="button" class="portfolio-tab-btn px-4 py-2 rounded-full text-sm font-bold transition-all">
						<?php echo esc_html( $td['label'] ); ?>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<!-- Tab panels -->
	<div class="tabs-content" <?php echo count( $tab_data ) > 1 ? 'data-fx-tabs-content="' . esc_attr( $tabs_id ) . '"' : ''; ?>>
		<?php foreach ( $tab_data as $i => $td ) :
			$use_slider = count( $td['posts'] ) > 3;
			?>
			<div class="tabs-panel<?php echo 0 === $i ? ' is-active' : ''; ?>">
				<?php if ( $use_slider ) : ?>
					<div class="relative closest-swiper">
						<div class="swiper" data-fx-slider>
							<div class="swiper-wrapper" data-swiper-options='<?php echo $slider_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'>
								<?php foreach ( $td['posts'] as $post ) :
									$img_url = get_the_post_thumbnail_url( $post->ID, 'medium_large' );
									if ( ! $img_url ) {
										$img_url = function_exists( 'wc_placeholder_img_src' )
											? wc_placeholder_img_src()
											: \SPL\Core\Helper::placeholderSrc( '', false );
									}
									?>
									<div class="swiper-slide h-auto!">
										<article class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-premium hover:shadow-hover-card transition-all duration-300 group flex flex-col justify-between h-full">
											<div>
												<div class="relative overflow-hidden <?php echo esc_attr( $ratio_css ); ?>">
													<img loading="lazy"
														 src="<?php echo esc_url( $img_url ); ?>"
														 alt="<?php echo esc_attr( get_the_title( $post ) ); ?>"
														 width="640" height="400"
														 class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300">
												</div>
												<div class="p-5 space-y-2">
													<span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block"><?php echo esc_html( get_the_date( '', $post ) ); ?></span>
													<h3 class="font-bold text-slate-800 text-sm md:text-base line-clamp-2 group-hover:text-primary transition-colors leading-snug">
														<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
													</h3>
													<p class="text-xs text-slate-500 line-clamp-3 leading-relaxed"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $post ) ) ); ?></p>
												</div>
											</div>
											<div class="p-5 pt-0">
												<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="text-xs font-black text-primary hover:text-primary-hover transition-colors inline-flex items-center gap-1">
													<?php esc_html_e( 'Đọc tiếp', 'spl' ); ?>
													<?php echo spl_icon( 'chevron-right', 'w-3 h-3' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												</a>
											</div>
										</article>
									</div>
								<?php endforeach; ?>
							</div>
						</div>

						<!-- Navigation controls -->
						<div class="swiper-controls">
							<button class="swiper-button swiper-button-prev absolute -left-4 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white border border-slate-200 shadow-md hover:bg-slate-50 text-slate-600 flex items-center justify-center transition-all focus:outline-none disabled:opacity-40 disabled:pointer-events-none">
								<?php echo spl_icon( 'chevron-down', 'w-4 h-4 rotate-90' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</button>
							<button class="swiper-button swiper-button-next absolute -right-4 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white border border-slate-200 shadow-md hover:bg-slate-50 text-slate-600 flex items-center justify-center transition-all focus:outline-none disabled:opacity-40 disabled:pointer-events-none">
								<?php echo spl_icon( 'chevron-down', 'w-4 h-4 -rotate-90' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</button>
						</div>
					</div>
				<?php else : ?>
					<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
						<?php foreach ( $td['posts'] as $post ) :
							$img_url = get_the_post_thumbnail_url( $post->ID, 'medium_large' );
							if ( ! $img_url ) {
								$img_url = function_exists( 'wc_placeholder_img_src' )
									? wc_placeholder_img_src()
									: \SPL\Core\Helper::placeholderSrc( '', false );
							}
							?>
							<article class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-premium hover:shadow-hover-card transition-all duration-300 group flex flex-col justify-between">
								<div>
									<div class="relative overflow-hidden <?php echo esc_attr( $ratio_css ); ?>">
										<img loading="lazy"
											 src="<?php echo esc_url( $img_url ); ?>"
											 alt="<?php echo esc_attr( get_the_title( $post ) ); ?>"
											 width="640" height="400"
											 class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300">
									</div>
									<div class="p-5 space-y-2">
										<span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block"><?php echo esc_html( get_the_date( '', $post ) ); ?></span>
										<h3 class="font-bold text-slate-800 text-sm md:text-base line-clamp-2 group-hover:text-primary transition-colors leading-snug">
											<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
										</h3>
										<p class="text-xs text-slate-500 line-clamp-3 leading-relaxed"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $post ) ) ); ?></p>
									</div>
								</div>
								<div class="p-5 pt-0">
									<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="text-xs font-black text-primary hover:text-primary-hover transition-colors inline-flex items-center gap-1">
										<?php esc_html_e( 'Đọc tiếp', 'spl' ); ?>
										<?php echo spl_icon( 'chevron-right', 'w-3 h-3' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $td['cat_link'] ) ) : ?>
					<div class="mt-6 text-center">
						<a href="<?php echo esc_url( $td['cat_link'] ); ?>" class="inline-flex items-center gap-1.5 text-sm font-bold text-primary hover:text-primary-hover transition-colors">
							<?php esc_html_e( 'Xem tất cả', 'spl' ); ?>
							<?php echo spl_icon( 'chevron-right', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<?php if ( count( $tab_data ) > 1 ) : ?>
<script>
(function() {
	var tabs = document.getElementById('<?php echo esc_js( $tabs_id ); ?>');
	if (!tabs) return;

	tabs.addEventListener('click', function() {
		requestAnimationFrame(function() {
			var activePanel = document.querySelector(
				'[data-fx-tabs-content="<?php echo esc_js( $tabs_id ); ?>"] > .tabs-panel.is-active'
			);
			if (activePanel) {
				document.dispatchEvent(new CustomEvent('core:scan', { detail: { root: activePanel } }));
			}
		});
	});
})();
</script>
<?php endif; ?>
