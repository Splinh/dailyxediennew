<?php
/**
 * Starter: Hero Slider
 *
 * Full-width 2:1 slider using fx-slider (Swiper-based).
 * Tailwind 4 syntax. Swiper slide-active animation triggers live in page-home.scss.
 * Static HTML — replace with ACF/WP fields when ready.
 *
 * @package HD
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="hero-slider relative" aria-label="Slider giới thiệu">

	<div class="swiper swiper-image w-full aspect-2/1" data-fx-slider>
		<div class="swiper-wrapper"
			data-swiper-options='{"autoplay":true,"delay":5500,"loop":true,"effect":"fade","speed":900,"pagination":{"type":"bullets","clickable":true}}'>

			<!-- Slide 1 -->
			<div class="swiper-slide relative h-full overflow-hidden">
				<picture class="block w-full h-full pointer-events-none">
					<source srcset="https://images.unsplash.com/photo-1600607686527-6fb886090705?w=1920&q=80" media="(min-width: 768px)"/>
					<img src="https://images.unsplash.com/photo-1600607686527-6fb886090705?w=960&q=75"
						alt="Không gian làm việc hiện đại"
						class="w-full h-full object-cover object-center block"
						loading="eager" decoding="async"/>
				</picture>
				<div class="absolute inset-0 bg-linear-to-r from-black/60 via-black/30 to-black/5 z-1" aria-hidden="true"></div>
				<div class="absolute inset-0 z-2 flex items-center">
					<div class="container mx-auto px-6 xl:px-12">
						<div class="animate-hero opacity-0 mb-6">
							<span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-md text-xs font-semibold tracking-wide text-white shadow-sm">
								<span class="flex size-1.5 rounded-full bg-teal-400 animate-pulse"></span>
								Giải pháp CNTT Toàn diện
							</span>
						</div>
						<h2 class="animate-hero animate-hero-d1 opacity-0 p-clamp-[28,52] font-extrabold leading-tight tracking-tight text-white mb-4">
							Thiết kế vượt thời gian<br>cho doanh nghiệp của bạn
						</h2>
						<p class="animate-hero animate-hero-d2 opacity-0 text-white/80 text-base max-w-xl leading-relaxed mb-8">
							Giải pháp toàn diện — từ thương hiệu đến trải nghiệm số — giúp bạn dẫn đầu xu hướng.
						</p>
						<div class="animate-hero animate-hero-d3 opacity-0 flex flex-wrap gap-4 mt-2">
							<a href="#" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl bg-white text-gray-900 text-[0.9375rem] font-bold no-underline hover:bg-gray-50 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-200">
								Khám phá giải pháp
							</a>
							<a href="#" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl bg-white/10 border border-white/20 backdrop-blur-md text-white text-[0.9375rem] font-semibold no-underline hover:bg-white/20 hover:border-white/30 transition-all duration-200">
								Xem dự án tiêu biểu
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Slide 2 -->
			<div class="swiper-slide relative h-full overflow-hidden">
				<picture class="block w-full h-full pointer-events-none">
					<source srcset="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=1920&q=80" media="(min-width: 768px)"/>
					<img src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=960&q=75"
						alt="Đội ngũ chuyên nghiệp"
						class="w-full h-full object-cover object-center block"
						loading="lazy" decoding="async"/>
				</picture>
				<div class="absolute inset-0 bg-linear-to-r from-black/60 via-black/30 to-black/5 z-1" aria-hidden="true"></div>
				<div class="absolute inset-0 z-2 flex items-center">
					<div class="container mx-auto px-6 xl:px-12">
						<div class="animate-hero opacity-0 mb-6">
							<span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-md text-xs font-semibold tracking-wide text-white shadow-sm">
								<span class="flex size-1.5 rounded-full bg-teal-400 animate-pulse"></span>
								Đội ngũ Chuyên gia
							</span>
						</div>
						<h2 class="animate-hero animate-hero-d1 opacity-0 p-clamp-[28,52] font-extrabold leading-tight tracking-tight text-white mb-4">
							Chuyên gia tận tâm<br>vì mỗi dự án của bạn
						</h2>
						<p class="animate-hero animate-hero-d2 opacity-0 text-white/80 text-base max-w-xl leading-relaxed mb-8">
							Hơn 10 năm kinh nghiệm — luôn đồng hành cùng bạn từ ý tưởng đến sản phẩm hoàn chỉnh.
						</p>
						<div class="animate-hero animate-hero-d3 opacity-0 flex flex-wrap gap-4 mt-2">
							<a href="#" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl bg-white text-gray-900 text-[0.9375rem] font-bold no-underline hover:bg-gray-50 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-200">
								Gặp gỡ đội ngũ
							</a>
							<a href="#" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl bg-white/10 border border-white/20 backdrop-blur-md text-white text-[0.9375rem] font-semibold no-underline hover:bg-white/20 hover:border-white/30 transition-all duration-200">
								Liên hệ tư vấn
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Slide 3 -->
			<div class="swiper-slide relative h-full overflow-hidden">
				<picture class="block w-full h-full pointer-events-none">
					<source srcset="https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1920&q=80" media="(min-width: 768px)"/>
					<img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=960&q=75"
						alt="Công nghệ tiên tiến"
						class="w-full h-full object-cover object-center block"
						loading="lazy" decoding="async"/>
				</picture>
				<div class="absolute inset-0 bg-linear-to-r from-black/60 via-black/30 to-black/5 z-1" aria-hidden="true"></div>
				<div class="absolute inset-0 z-2 flex items-center">
					<div class="container mx-auto px-6 xl:px-12">
						<div class="animate-hero opacity-0 mb-6">
							<span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-md text-xs font-semibold tracking-wide text-white shadow-sm">
								<span class="flex size-1.5 rounded-full bg-teal-400 animate-pulse"></span>
								Công nghệ Tiên tiến
							</span>
						</div>
						<h2 class="animate-hero animate-hero-d1 opacity-0 p-clamp-[28,52] font-extrabold leading-tight tracking-tight text-white mb-4">
							Hệ sinh thái số<br>cho tương lai
						</h2>
						<p class="animate-hero animate-hero-d2 opacity-0 text-white/80 text-base max-w-xl leading-relaxed mb-8">
							Chúng tôi xây dựng nền tảng kỹ thuật số vững chắc, giúp doanh nghiệp bứt phá trong kỷ nguyên AI.
						</p>
						<div class="animate-hero animate-hero-d3 opacity-0 flex flex-wrap gap-4 mt-2">
							<a href="#" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl bg-white text-gray-900 text-[0.9375rem] font-bold no-underline hover:bg-gray-50 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-200">
								Tìm hiểu hệ sinh thái
							</a>
						</div>
					</div>
				</div>
			</div>

		</div><!-- /.swiper-wrapper -->
	</div><!-- /.swiper -->

</section>
