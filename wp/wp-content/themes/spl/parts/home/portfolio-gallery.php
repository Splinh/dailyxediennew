<?php
/**
 * Home page — Portfolio Gallery with Tabs + Slider + Lightbox.
 *
 * Pulls from dxd_gallery CPT grouped by dxd_gallery_cat taxonomy.
 * Uses data-fx-tabs for tab switching, data-fx-slider (Swiper) for >4 items,
 * and data-fx-lightbox for image zoom/browse.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

use SPL\Features\Optimizer\PortfolioGallery;

$data     = $args ?? [];
$title    = $data['title'] ?? __( 'Hình ảnh sự kiện', 'spl' );
$subtitle = $data['subtitle'] ?? __( 'Hoạt động tại cửa hàng', 'spl' );
$tabs     = $data['tabs'] ?? [];
$per_tab  = (int) ( $data['per_tab'] ?? 12 );

if ( empty( $tabs ) ) {
	// Fallback: auto-detect all dxd_gallery_cat terms.
	$all_terms = get_terms( [
		'taxonomy'   => PortfolioGallery::TAXONOMY,
		'hide_empty' => true,
		'orderby'    => 'term_id',
		'order'      => 'ASC',
	] );
	if ( ! is_wp_error( $all_terms ) && $all_terms ) {
		foreach ( $all_terms as $term ) {
			$tabs[] = [
				'tab_label'    => $term->name,
				'tab_category' => $term->term_id,
			];
		}
	}
}

if ( empty( $tabs ) ) {
	return;
}

// Pre-query posts for each tab.
$tab_data = [];
foreach ( $tabs as $tab ) {
	$term_id = (int) ( $tab['tab_category'] ?? 0 );
	$label   = $tab['tab_label'] ?? '';

	if ( ! $term_id ) {
		continue;
	}

	if ( ! $label ) {
		$term  = get_term( $term_id, PortfolioGallery::TAXONOMY );
		$label = ( $term && ! is_wp_error( $term ) ) ? $term->name : __( 'Tab', 'spl' );
	}

	$posts = get_posts( [
		'post_type'      => PortfolioGallery::POST_TYPE,
		'posts_per_page' => $per_tab,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
		'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			[
				'taxonomy' => PortfolioGallery::TAXONOMY,
				'terms'    => $term_id,
			],
		],
	] );

	if ( $posts ) {
		$tab_data[] = [
			'label' => $label,
			'posts' => $posts,
		];
	}
}

if ( empty( $tab_data ) ) {
	return;
}

$tabs_id        = 'portfolio-gallery-tabs';
$slider_options = wp_json_encode( [
	'slidesPerView'    => 2,
	'spaceBetween'     => 12,
	'navigation'       => true,
	'watchSlidesProgress' => true,
	'observer'         => true,
	'observeParents'   => true,
	'breakpoints'      => [
		640  => [ 'slidesPerView' => 3, 'spaceBetween' => 16 ],
		1024 => [ 'slidesPerView' => 4, 'spaceBetween' => 20 ],
	],
], JSON_UNESCAPED_SLASHES );
?>
<section class="max-w-7xl mx-auto px-4 mb-8 md:mb-16">
	<!-- Section header -->
	<div class="flex items-center justify-between mb-8">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-primary rounded-full"></span>
			<h2 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<span class="text-sm font-semibold text-slate-400"><?php echo esc_html( $subtitle ); ?></span>
	</div>

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

	<!-- Tab panels -->
	<div class="tabs-content" data-fx-tabs-content="<?php echo esc_attr( $tabs_id ); ?>">
		<?php foreach ( $tab_data as $i => $td ) :
			// Filter posts with thumbnails and fetch their dimensions.
			$valid_posts = [];
			foreach ( $td['posts'] as $post ) {
				$thumb_id = get_post_thumbnail_id( $post->ID );
				if ( ! $thumb_id ) {
					continue;
				}
				$img_src  = wp_get_attachment_image_src( $thumb_id, 'large' );
				$valid_posts[] = [
					'id'        => $post->ID,
					'thumb_url' => wp_get_attachment_image_url( $thumb_id, 'medium_large' ),
					'full_url'  => $img_src ? $img_src[0] : wp_get_attachment_image_url( $thumb_id, 'large' ),
					'width'     => $img_src ? $img_src[1] : 1024,
					'height'    => $img_src ? $img_src[2] : 768,
					'alt'       => get_the_title( $post ),
				];
			}

			$use_slider = count( $valid_posts ) > 4;
			?>
			<div class="tabs-panel<?php echo 0 === $i ? ' is-active' : ''; ?>">
				<?php if ( $use_slider ) : ?>
					<!-- Slider layout (>4 items) with lightbox container grouping -->
					<div class="relative closest-swiper">
						<div class="swiper" data-fx-slider data-fx-lightbox>
							<div class="swiper-wrapper" data-swiper-options='<?php echo $slider_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'>
								<?php foreach ( $valid_posts as $p ) : ?>
									<div class="swiper-slide h-auto!">
										<a href="<?php echo esc_url( $p['full_url'] ?: $p['thumb_url'] ); ?>"
										   data-pswp-width="<?php echo esc_attr( $p['width'] ); ?>"
										   data-pswp-height="<?php echo esc_attr( $p['height'] ); ?>"
										   class="group flex flex-col h-full bg-white border border-slate-100 p-2 rounded-2xl shadow-premium hover:shadow-hover-card transition-all duration-300">
											<div class="rounded-xl overflow-hidden aspect-[4/3] relative">
												<img loading="lazy"
													 src="<?php echo esc_url( $p['thumb_url'] ); ?>"
													 alt="<?php echo esc_attr( $p['alt'] ); ?>"
													 width="400" height="300"
													 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
												<div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
													<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
												</div>
											</div>
											<p class="text-[11px] md:text-xs font-bold text-slate-700 mt-2 text-center line-clamp-2 px-1 flex-grow flex items-center justify-center min-h-[32px] md:min-h-[40px]"><?php echo esc_html( $p['alt'] ); ?></p>
										</a>
									</div>
								<?php endforeach; ?>
							</div>
						</div>

						<!-- Navigation controls -->
						<div class="swiper-controls">
							<button class="swiper-button swiper-button-prev absolute -left-3 top-1/2 -translate-y-1/2 z-20 w-7 h-7 md:w-8 md:h-8 rounded-full text-white shadow-md flex items-center justify-center transition-all focus:outline-none disabled:opacity-0 disabled:pointer-events-none" style="background-color: #1e73be !important; color: #ffffff !important; border: none !important;">
								<svg class="w-3.5 h-3.5 text-white stroke-[2.5]" style="fill: none !important; stroke: #ffffff !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
							</button>
							<button class="swiper-button swiper-button-next absolute -right-3 top-1/2 -translate-y-1/2 z-20 w-7 h-7 md:w-8 md:h-8 rounded-full text-white shadow-md flex items-center justify-center transition-all focus:outline-none disabled:opacity-0 disabled:pointer-events-none" style="background-color: #1e73be !important; color: #ffffff !important; border: none !important;">
								<svg class="w-3.5 h-3.5 text-white stroke-[2.5]" style="fill: none !important; stroke: #ffffff !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg>
							</button>
						</div>
					</div>
				<?php else : ?>
					<!-- Grid layout (≤4 items) with lightbox container grouping -->
					<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-6" data-fx-lightbox>
						<?php foreach ( $valid_posts as $p ) : ?>
							<a href="<?php echo esc_url( $p['full_url'] ?: $p['thumb_url'] ); ?>"
							   data-pswp-width="<?php echo esc_attr( $p['width'] ); ?>"
							   data-pswp-height="<?php echo esc_attr( $p['height'] ); ?>"
							   class="group flex flex-col h-full bg-white border border-slate-100 p-2 rounded-2xl shadow-premium hover:shadow-hover-card transition-all duration-300">
								<div class="rounded-xl overflow-hidden aspect-[4/3] relative">
									<img loading="lazy"
										 src="<?php echo esc_url( $p['thumb_url'] ); ?>"
										 alt="<?php echo esc_attr( $p['alt'] ); ?>"
										 width="400" height="300"
										 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
									<div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
										<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
									</div>
								</div>
								<p class="text-[11px] md:text-xs font-bold text-slate-700 mt-2 text-center line-clamp-2 px-1 flex-grow flex items-center justify-center min-h-[32px] md:min-h-[40px]"><?php echo esc_html( $p['alt'] ); ?></p>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<?php // Re-scan FX modules (slider, lightbox) when switching tabs so hidden panels initialize. ?>
<script>
(function() {
	var tabs = document.getElementById('<?php echo esc_js( $tabs_id ); ?>');
	if (!tabs) return;

	function debugHeights() {
		var section = document.querySelector('.max-w-7xl');
		var content = document.querySelector('.tabs-content');
		var panels = document.querySelectorAll('.tabs-panel');
		console.warn('--- Debug Heights ---');
		if (section) console.warn('Section height: ' + section.offsetHeight + 'px');
		if (content) console.warn('Tabs Content height: ' + content.offsetHeight + 'px');
		panels.forEach(function(p, idx) {
			var style = window.getComputedStyle(p);
			console.warn('Panel ' + (idx + 1) + ' (' + (p.classList.contains('is-active') ? 'active' : 'inactive') + ') height: ' + p.offsetHeight + 'px, display: ' + style.display + ', position: ' + style.position);
		});
		var activeSwiper = document.querySelector('.tabs-panel.is-active .swiper');
		if (activeSwiper) {
			console.warn('Active Swiper height: ' + activeSwiper.offsetHeight + 'px');
			var wrapper = activeSwiper.querySelector('.swiper-wrapper');
			if (wrapper) console.warn('Active Swiper Wrapper height: ' + wrapper.offsetHeight + 'px');
		}
	}

	// Register click listener immediately since elements are already parsed.
	tabs.addEventListener('click', function(e) {
		// Defer to next frame after browser processes the tab panel toggle.
		requestAnimationFrame(function() {
			var activePanel = document.querySelector(
				'[data-fx-tabs-content="<?php echo esc_js( $tabs_id ); ?>"] > .tabs-panel.is-active'
			);
			if (activePanel) {
				// Rescan the active panel to initialize lazy-loaded Swiper sliders and PhotoSwipe lightboxes.
				document.dispatchEvent(new CustomEvent('core:scan', { detail: { root: activePanel } }));
			}
			setTimeout(debugHeights, 300);
		});
	});

	window.addEventListener('load', function() {
		setTimeout(debugHeights, 1000);
	});
})();
</script>
