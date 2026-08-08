<?php
/**
 * About — Timeline section ("Từng mốc dấu ấn").
 *
 * Official verified milestones for DailyXeDien.vn / Bluera Việt Nhật / AIEBike (2013 - 2026):
 * - Starts at 2013 (Founding of Bluera Việt Nhật & DailyXeDien)
 * - Ends at 2026 (Showroom 3S standardization)
 * - Strict 4:3 photo container ratio (aspect-ratio: 4 / 3 !important)
 * - Outer slide height set to auto (!h-auto) preventing vertical whitespace stretching
 * - SVG icon width 55% inside 40px circle button
 * - Light background (#f8fafc)
 * - Dynamic Stepper bar with active dot sync
 * - Full-bleed peeking slides
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$title = $data['title'] ?? 'TỪNG MỐC DẤU ẤN';
$items = ! empty( $data['items'] ) ? $data['items'] : [
	[
		'year'  => '2013',
		'desc'  => 'Khởi đầu. Thành lập Công ty TNHH Xe Điện Bluera Việt Nhật & khai trương showroom DailyXeDien đầu tiên (23/09/2013).',
		'image' => get_template_directory_uri() . '/resources/img/timeline/timeline_2013_founding_1786175168990.png',
	],
	[
		'year'  => '2015',
		'desc'  => 'Nhà máy Bluera. Khai trương nhà máy sản xuất & lắp ráp xe điện công nghệ hiện đại đầu tiên đạt tiêu chuẩn TCVN, ISO.',
		'image' => get_template_directory_uri() . '/resources/img/timeline/timeline_2015_factory_1786175183412.png',
	],
	[
		'year'  => '2018',
		'desc'  => 'Mở rộng quy mô. Nâng cấp dây chuyền công nghệ hiện đại, liên kết linh kiện cao cấp và nhân rộng hệ thống đại lý.',
		'image' => get_template_directory_uri() . '/resources/img/timeline/timeline_2018_scale_1786175198872.png',
	],
	[
		'year'  => '2021',
		'desc'  => 'Chuyển đổi số. Triển khai hệ thống bảo hành điện tử 24/7 và nâng cấp trải nghiệm mua sắm số trên DailyXeDien.vn.',
		'image' => get_template_directory_uri() . '/resources/img/timeline/timeline_2021_digital_1786175211739.png',
	],
	[
		'year'  => '2023',
		'desc'  => 'Cột mốc 10 năm. Thành lập dự án AI Ebike (AIE) nghiên cứu và phát triển dòng sản phẩm xe điện thông minh thế hệ mới.',
		'image' => get_template_directory_uri() . '/resources/img/timeline/timeline_2023_ai_ebike_1786175227144.png',
	],
	[
		'year'  => '2024',
		'desc'  => 'Mạng lưới toàn quốc. Phát triển mạng lưới phân phối đạt 500+ đại lý ủy quyền và hợp tác đối tác chiến lược.',
		'image' => get_template_directory_uri() . '/resources/img/timeline/timeline_2024_network_1786175240864.png',
	],
	[
		'year'  => '2026',
		'desc'  => 'Kỷ nguyên mới. Chuẩn hóa hệ thống showroom 3S & trung tâm kỹ thuật bảo hành ủy quyền chính hãng trên toàn quốc.',
		'image' => get_template_directory_uri() . '/resources/img/timeline/timeline_2026_showroom3s_1786175252670.png',
	],
];

$fallback_image = 'https://dailyxedien.vn/wp-content/uploads/2026/02/khai-truong-dai-ly-xe-dien-bluera-viet-nhat-ron-bike-pro-tai-can-tho-dlxd.jpg';

$slider_config = wp_json_encode( [
	'slidesPerView'       => 'auto',
	'centered'            => true,
	'loop'                => true,
	'spaceBetween'        => 24,
	'navigation'          => true,
	'observeParents'      => true,
	'observer'            => true,
	'watchSlidesProgress' => true,
], JSON_UNESCAPED_SLASHES );
?>

<style>
/* Cancel global height: 100% !important on swiper slide direct children for timeline section */
#about-timeline-section .swiper-slide > *,
#about-timeline-section .swiper-slide:not(.bg-white) > * {
	height: auto !important;
	flex-grow: 0 !important;
}

/* Strict 4:3 ratio for photo card wrapper only */
#about-timeline-section .timeline-photo-card {
	aspect-ratio: 4 / 3 !important;
	width: 100% !important;
	height: auto !important;
}
#about-timeline-section .timeline-photo-card img {
	width: 100% !important;
	height: 100% !important;
	object-fit: cover !important;
	object-position: center !important;
}

/* Swiper peeking slides effect matching Unila light design */
#about-timeline-section .swiper-slide {
	opacity: 0.35 !important;
	transform: scale(0.88) !important;
	transition: opacity 0.4s ease, transform 0.4s ease;
	filter: none !important;
	height: auto !important;
}
#about-timeline-section .swiper-slide-active {
	opacity: 1 !important;
	transform: scale(1) !important;
	filter: none !important;
}
/* Force kill any Swiper icon font glyphs / solid black triangle pseudo elements */
#about-timeline-section .swiper-button-prev::before,
#about-timeline-section .swiper-button-prev::after,
#about-timeline-section .swiper-button-next::before,
#about-timeline-section .swiper-button-next::after,
#about-timeline-section .timeline-nav-btn::before,
#about-timeline-section .timeline-nav-btn::after {
	display: none !important;
	content: none !important;
	content: "" !important;
	font-size: 0 !important;
	width: 0 !important;
	height: 0 !important;
	opacity: 0 !important;
	visibility: hidden !important;
}

/* Styling for compact & subtle navigation circle buttons */
#about-timeline-section .timeline-nav-btn {
	position: absolute;
	top: 90px;
	z-index: 30;
	width: 38px;
	height: 38px;
	border-radius: 9999px;
	background-color: rgba(255, 255, 255, 0.95) !important;
	border: 1px solid rgba(226, 232, 240, 0.9) !important;
	color: #334155 !important;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.2s ease;
	cursor: pointer;
	padding: 0 !important;
	font-size: 0 !important;
	line-height: 0 !important;
	overflow: hidden !important;
}
@media (min-width: 640px) {
	#about-timeline-section .timeline-nav-btn {
		top: 115px;
		width: 42px;
		height: 42px;
	}
}
@media (min-width: 768px) {
	#about-timeline-section .timeline-nav-btn {
		top: 135px;
		width: 44px;
		height: 44px;
	}
}
#about-timeline-section .timeline-nav-btn:hover:not(:disabled) {
	background-color: #1e73be !important;
	border-color: #1e73be !important;
	color: #ffffff !important;
	transform: translateY(-2px) scale(1.05);
	box-shadow: 0 10px 15px -3px rgba(30, 115, 190, 0.3);
}
#about-timeline-section .timeline-nav-btn:active:not(:disabled) {
	transform: translateY(0) scale(0.95);
}
#about-timeline-section .timeline-nav-btn:disabled {
	opacity: 0.35;
	cursor: not-allowed;
	box-shadow: none;
}
/* SVG width set precisely to 55% of button size */
#about-timeline-section .timeline-nav-btn svg {
	width: 55% !important;
	height: 55% !important;
	fill: none !important;
	stroke: currentColor !important;
	display: block !important;
}
</style>

<section class="about-timeline-section py-10 md:py-16 bg-[#f8fafc] border-b border-slate-200/80 overflow-hidden relative" id="about-timeline-section">
	
	<!-- Header & Stepper Line (Centered inside max-w-4xl container) -->
	<div class="max-w-4xl mx-auto px-4 text-center">
		
		<!-- Section Header (Centered Uppercase Title) -->
		<h2 class="text-2xl md:text-3xl font-black text-slate-900 uppercase tracking-tight mb-6 md:mb-8">
			<?php echo esc_html( $title ); ?>
		</h2>

		<!-- Swiper Timeline Thumbs Stepper Bar -->
		<div class="max-w-3xl mx-auto relative mb-8 md:mb-10 overflow-x-auto no-scrollbar py-2" id="timeline-stepper-wrap">
			<!-- Connected Horizontal Line centered exactly behind dots -->
			<div class="absolute left-10 right-10 top-[20px] -translate-y-1/2 h-0.5 bg-slate-300 pointer-events-none rounded-full"></div>

			<div class="flex items-center justify-between gap-4 md:gap-8 min-w-max px-6 relative z-10">
				<?php foreach ( $items as $index => $item ) : ?>
					<button type="button"
						class="year-item group flex flex-col items-center cursor-pointer transition-all duration-300 focus:outline-none"
						data-slide-index="<?php echo esc_attr( $index ); ?>">
						
						<!-- Dot Indicator -->
						<span class="dot-wrap w-6 h-6 flex items-center justify-center relative">
							<span class="dot transition-all duration-300 <?php echo $index === 0 ? 'w-5 h-5 rounded-full border-2 border-primary bg-white ring-4 ring-primary/20 shadow-md flex items-center justify-center' : 'w-2.5 h-2.5 bg-slate-400 rounded-full group-hover:bg-primary group-hover:scale-125'; ?>">
								<span class="dot-inner w-2 h-2 rounded-full bg-primary transition-opacity <?php echo $index === 0 ? 'opacity-100' : 'opacity-0'; ?>"></span>
							</span>
						</span>

						<!-- Year Label -->
						<span class="year text-xs md:text-sm font-semibold tracking-wider transition-all duration-300 mt-2 <?php echo $index === 0 ? 'text-slate-900 font-extrabold text-sm md:text-base' : 'text-slate-500 group-hover:text-primary'; ?>">
							<?php echo esc_html( $item['year'] ); ?>
						</span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>

	</div>

	<!-- FULL-WIDTH Swiper Carousel Showcase -->
	<div class="w-full relative px-0 closest-swiper">
		<div class="swiper data-fx-slider group !overflow-visible" id="timeline-swiper-main" data-fx-slider>
			
			<div class="swiper-wrapper" data-swiper-options='<?php echo esc_attr( $slider_config ); ?>'>
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$img_val = $item['image'] ?? '';
					$img_url = '';

					if ( is_array( $img_val ) ) {
						$img_url = $img_val['url'] ?? ( isset( $img_val['id'] ) ? wp_get_attachment_image_url( $img_val['id'], 'large' ) : '' );
					} elseif ( is_numeric( $img_val ) && (int) $img_val > 0 ) {
						$img_url = wp_get_attachment_image_url( (int) $img_val, 'large' );
					} elseif ( is_string( $img_val ) && ! empty( $img_val ) ) {
						$img_url = $img_val;
					}

					// If empty or if it's not a custom timeline_20 image, override with custom per-year milestone image
					if ( empty( $img_url ) || ! str_contains( (string) $img_url, 'timeline_20' ) ) {
						$default_milestone_images = [
							get_template_directory_uri() . '/resources/img/timeline/timeline_2013_founding_1786175168990.png',
							get_template_directory_uri() . '/resources/img/timeline/timeline_2015_factory_1786175183412.png',
							get_template_directory_uri() . '/resources/img/timeline/timeline_2018_scale_1786175198872.png',
							get_template_directory_uri() . '/resources/img/timeline/timeline_2021_digital_1786175211739.png',
							get_template_directory_uri() . '/resources/img/timeline/timeline_2023_ai_ebike_1786175227144.png',
							get_template_directory_uri() . '/resources/img/timeline/timeline_2024_network_1786175240864.png',
							get_template_directory_uri() . '/resources/img/timeline/timeline_2026_showroom3s_1786175252670.png',
						];
						$img_url = $default_milestone_images[ $index % count( $default_milestone_images ) ];
					}
					?>
					<div class="swiper-slide !w-[75vw] sm:!w-[52vw] md:!w-[40vw] lg:!w-[34vw] max-w-xl shrink-0 !h-auto">
						<!-- Photo Container ONLY has 4:3 aspect ratio -->
						<div class="timeline-photo-card relative rounded-2xl overflow-hidden shadow-lg border border-slate-200 bg-slate-200 w-full" style="aspect-ratio: 4 / 3;">
							<img src="<?php echo esc_url( $img_url ); ?>" 
								 alt="<?php echo esc_attr( $item['year'] ); ?>" 
								 class="w-full h-full object-cover object-center" 
								 loading="lazy">
						</div>

						<!-- Below Image Caption: sits right below the 4:3 photo box with zero artificial gaps -->
						<div class="text-center mt-3 max-w-lg mx-auto px-2 space-y-1">
							<div class="text-xl md:text-2xl font-black text-primary tracking-tight">
								<?php echo esc_html( $item['year'] ); ?>
							</div>
							<div class="text-xs md:text-sm text-slate-800 font-bold leading-relaxed">
								<p><?php echo esc_html( $item['desc'] ); ?></p>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Sleek Compact Chevron Navigation Buttons inside swiper-controls -->
			<div class="swiper-controls">
				<button type="button" class="swiper-button swiper-button-prev timeline-nav-btn left-3 sm:left-6 md:left-12" aria-label="Trước">
					<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<path d="M15.75 19.5L8.25 12l7.5-7.5" />
					</svg>
				</button>
				<button type="button" class="swiper-button swiper-button-next timeline-nav-btn right-3 sm:right-6 md:right-12" aria-label="Sau">
					<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<path d="M8.25 4.5l7.5 7.5-7.5 7.5" />
					</svg>
				</button>
			</div>

		</div>
	</div>

</section>

<!-- Dynamic Stepper & Swiper Direct Loader Script -->
<script>
(function() {
	function initTimelineSwiper() {
		const section = document.getElementById('about-timeline-section');
		if (!section) return;

		const stepperBtns = Array.from(section.querySelectorAll('.year-item'));
		const sliderContainer = section.querySelector('#timeline-swiper-main');
		const prevBtn = section.querySelector('.swiper-button-prev');
		const nextBtn = section.querySelector('.swiper-button-next');
		if (!sliderContainer) return;

		const svgLeft = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>';
		const svgRight = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>';

		// Clean inner HTML to purge any FxSlider injected elements or text nodes
		const sanitizeNavButtons = () => {
			if (prevBtn) prevBtn.innerHTML = svgLeft;
			if (nextBtn) nextBtn.innerHTML = svgRight;
		};
		sanitizeNavButtons();

		let swiperInstance = null;

		// Function to update active stepper state
		const updateActiveStepper = (activeIndex) => {
			const activeNum = isNaN(parseInt(activeIndex, 10)) ? 0 : parseInt(activeIndex, 10);

			stepperBtns.forEach((btn, idx) => {
				const dot = btn.querySelector('.dot');
				const dotInner = btn.querySelector('.dot-inner');
				const yearText = btn.querySelector('.year');

				if (idx === activeNum) {
					if (dot) {
						dot.className = 'dot w-5 h-5 rounded-full border-2 border-primary bg-white ring-4 ring-primary/20 shadow-md flex items-center justify-center transition-all duration-300';
					}
					if (dotInner) dotInner.className = 'dot-inner w-2 h-2 rounded-full bg-primary opacity-100 transition-opacity';
					if (yearText) yearText.className = 'year text-xs md:text-sm font-extrabold text-slate-900 text-sm md:text-base transition-colors mt-2';
					
					// Internal scroll container only for mobile screens (never shifts page or title)
					const stepperWrap = section.querySelector('#timeline-stepper-wrap');
					if (stepperWrap && window.innerWidth < 768 && stepperWrap.scrollWidth > stepperWrap.clientWidth) {
						const btnLeft = btn.offsetLeft;
						const btnWidth = btn.offsetWidth;
						const wrapWidth = stepperWrap.clientWidth;
						stepperWrap.scrollTo({
							left: btnLeft - wrapWidth / 2 + btnWidth / 2,
							behavior: 'smooth'
						});
					}
				} else {
					if (dot) {
						dot.className = 'dot w-2.5 h-2.5 bg-slate-400 rounded-full group-hover:bg-primary group-hover:scale-125 transition-all duration-300';
					}
					if (dotInner) dotInner.className = 'dot-inner w-2 h-2 rounded-full bg-primary opacity-0 transition-opacity';
					if (yearText) yearText.className = 'year text-xs md:text-sm font-semibold text-slate-500 group-hover:text-primary transition-colors mt-2';
				}
			});
		};

		// Stepper click handler
		stepperBtns.forEach((btn) => {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				const slideIdx = parseInt(this.getAttribute('data-slide-index'), 10);
				if (swiperInstance) {
					if (typeof swiperInstance.slideToLoop === 'function') {
						swiperInstance.slideToLoop(slideIdx, 400);
					} else {
						swiperInstance.slideTo(slideIdx, 400);
					}
				}
				updateActiveStepper(slideIdx);
			});
		});

		// Check if Swiper is already initialized or initialize it directly
		const setupSwiperEvents = (instance) => {
			if (!instance || swiperInstance === instance) return;
			swiperInstance = instance;
			sanitizeNavButtons();

			const handleIndexChange = () => {
				sanitizeNavButtons();
				const activeIdx = (typeof instance.realIndex !== 'undefined' && instance.realIndex !== null)
					? instance.realIndex
					: (instance.activeIndex || 0);
				updateActiveStepper(activeIdx);
			};

			instance.on('slideChange', handleIndexChange);
			instance.on('slideChangeTransitionStart', handleIndexChange);
			instance.on('activeIndexChange', handleIndexChange);

			const initialIdx = (typeof instance.realIndex !== 'undefined' && instance.realIndex !== null)
				? instance.realIndex
				: (instance.activeIndex || 0);
			updateActiveStepper(initialIdx);
		};

		// Listen to global FxSlider init event
		document.addEventListener('fx:slider:init', (e) => {
			if (e.detail && e.detail.el === sliderContainer) {
				setupSwiperEvents(e.detail.instance);
			}
		});

		// Poll for swiper instance safely
		let attempts = 0;
		const timer = setInterval(() => {
			attempts++;
			sanitizeNavButtons();

			if (sliderContainer.swiper) {
				clearInterval(timer);
				setupSwiperEvents(sliderContainer.swiper);
			} else if (window.FX && typeof window.FX.get === 'function' && window.FX.get(sliderContainer)) {
				const fxObj = window.FX.get(sliderContainer);
				const swInstance = fxObj.swiper || fxObj;
				if (swInstance && typeof swInstance.on === 'function') {
					clearInterval(timer);
					setupSwiperEvents(swInstance);
				}
			} else if (typeof Swiper !== 'undefined' && attempts > 25) {
				// Fallback after 2.5s if fx-slider didn't initialize
				clearInterval(timer);
				if (!sliderContainer.swiper) {
					const directSwiper = new Swiper(sliderContainer, {
						slidesPerView: 'auto',
						centeredSlides: true,
						loop: true,
						spaceBetween: 24,
						initialSlide: 0,
						speed: 500,
						navigation: {
							nextEl: nextBtn,
							prevEl: prevBtn,
						},
					});
					sliderContainer.swiper = directSwiper;
					setupSwiperEvents(directSwiper);
				}
			} else if (attempts > 50) {
				clearInterval(timer);
			}
		}, 100);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initTimelineSwiper);
	} else {
		initTimelineSwiper();
	}
})();
</script>
