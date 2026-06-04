<?php
/**
 * Starter: Feature Cards — 4-column icon grid below hero slider.
 *
 * Tailwind 4 syntax. Static HTML — replace with ACF repeater when ready.
 *
 * @package HD
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="py-16 bg-background dark:bg-gray-900" aria-labelledby="feature-cards-heading">
	<div class="container mx-auto px-6 xl:px-12">

		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

			<!-- Card 1 -->
			<div class="group relative flex flex-col gap-4 p-8 rounded-2xl border border-black/5 dark:border-white/5 bg-white dark:bg-gray-800/80 shadow-sm backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 dark:hover:border-teal-400/30 hover:shadow-[0_12px_40px_-12px_rgba(0,0,0,0.12)] dark:hover:shadow-[0_12px_40px_-12px_rgba(0,0,0,0.5)] overflow-hidden">
				<div class="absolute -top-24 -right-24 size-48 bg-primary/5 dark:bg-teal-400/10 blur-3xl rounded-full pointer-events-none transition-opacity duration-500 opacity-0 group-hover:opacity-100" aria-hidden="true"></div>

				<div class="relative flex items-center justify-center size-13 rounded-xl bg-primary/4 dark:bg-teal-400/10 ring-1 ring-inset ring-primary/10 dark:ring-teal-400/20 text-primary dark:text-teal-400 shrink-0 group-hover:bg-primary group-hover:text-white group-hover:ring-primary dark:group-hover:bg-teal-400 dark:group-hover:text-gray-900 dark:group-hover:ring-teal-400 transition-all duration-300">
					<svg width="24" height="24" viewBox="0 0 28 28" fill="none" aria-hidden="true">
						<rect x="2" y="2" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/>
						<rect x="16" y="2" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/>
						<rect x="2" y="16" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/>
						<rect x="16" y="16" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/>
					</svg>
				</div>
				<div class="relative flex flex-col grow gap-2">
					<h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight m-0">Thiết kế hiện đại</h3>
					<p class="text-base text-gray-500 dark:text-gray-400 leading-relaxed m-0 grow">
						Giao diện thẩm mỹ cao, tương thích mọi thiết bị, tối ưu trải nghiệm người dùng từ mobile đến desktop.
					</p>
				</div>
				<div class="relative mt-2">
					<a href="#" class="inline-flex items-center gap-1.5 text-base font-semibold text-primary dark:text-teal-400 no-underline transition-all duration-200 group-hover:gap-2.5">
						Tìm hiểu thêm
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<path d="M3.5 8H12.5M12.5 8L9 4.5M12.5 8L9 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</a>
				</div>
			</div>

			<!-- Card 2 -->
			<div class="group relative flex flex-col gap-4 p-8 rounded-2xl border border-black/5 dark:border-white/5 bg-white dark:bg-gray-800/80 shadow-sm backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 dark:hover:border-teal-400/30 hover:shadow-[0_12px_40px_-12px_rgba(0,0,0,0.12)] dark:hover:shadow-[0_12px_40px_-12px_rgba(0,0,0,0.5)] overflow-hidden">
				<div class="absolute -top-24 -right-24 size-48 bg-primary/5 dark:bg-teal-400/10 blur-3xl rounded-full pointer-events-none transition-opacity duration-500 opacity-0 group-hover:opacity-100" aria-hidden="true"></div>

				<div class="relative flex items-center justify-center size-13 rounded-xl bg-primary/4 dark:bg-teal-400/10 ring-1 ring-inset ring-primary/10 dark:ring-teal-400/20 text-primary dark:text-teal-400 shrink-0 group-hover:bg-primary group-hover:text-white group-hover:ring-primary dark:group-hover:bg-teal-400 dark:group-hover:text-gray-900 dark:group-hover:ring-teal-400 transition-all duration-300">
					<svg width="24" height="24" viewBox="0 0 28 28" fill="none" aria-hidden="true">
						<path d="M14 3L25 8.5V14C25 19.5 20 24 14 26C8 24 3 19.5 3 14V8.5L14 3Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
						<path d="M9 14L12.5 17.5L19 11" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<div class="relative flex flex-col grow gap-2">
					<h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight m-0">Bảo mật tuyệt đối</h3>
					<p class="text-base text-gray-500 dark:text-gray-400 leading-relaxed m-0 grow">
						Hệ thống bảo mật đa lớp, mã hóa SSL, cập nhật thường xuyên để bảo vệ dữ liệu quý giá của bạn.
					</p>
				</div>
				<div class="relative mt-2">
					<a href="#" class="inline-flex items-center gap-1.5 text-base font-semibold text-primary dark:text-teal-400 no-underline transition-all duration-200 group-hover:gap-2.5">
						Tìm hiểu thêm
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<path d="M3.5 8H12.5M12.5 8L9 4.5M12.5 8L9 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</a>
				</div>
			</div>

			<!-- Card 3 -->
			<div class="group relative flex flex-col gap-4 p-8 rounded-2xl border border-black/5 dark:border-white/5 bg-white dark:bg-gray-800/80 shadow-sm backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 dark:hover:border-teal-400/30 hover:shadow-[0_12px_40px_-12px_rgba(0,0,0,0.12)] dark:hover:shadow-[0_12px_40px_-12px_rgba(0,0,0,0.5)] overflow-hidden">
				<div class="absolute -top-24 -right-24 size-48 bg-primary/5 dark:bg-teal-400/10 blur-3xl rounded-full pointer-events-none transition-opacity duration-500 opacity-0 group-hover:opacity-100" aria-hidden="true"></div>

				<div class="relative flex items-center justify-center size-13 rounded-xl bg-primary/4 dark:bg-teal-400/10 ring-1 ring-inset ring-primary/10 dark:ring-teal-400/20 text-primary dark:text-teal-400 shrink-0 group-hover:bg-primary group-hover:text-white group-hover:ring-primary dark:group-hover:bg-teal-400 dark:group-hover:text-gray-900 dark:group-hover:ring-teal-400 transition-all duration-300">
					<svg width="24" height="24" viewBox="0 0 28 28" fill="none" aria-hidden="true">
						<circle cx="14" cy="14" r="11" stroke="currentColor" stroke-width="1.75"/>
						<path d="M14 8V14L18 17" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<div class="relative flex flex-col grow gap-2">
					<h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight m-0">Tốc độ vượt trội</h3>
					<p class="text-base text-gray-500 dark:text-gray-400 leading-relaxed m-0 grow">
						Tối ưu Core Web Vitals, CDN toàn cầu, thời gian tải trang dưới 1 giây — đảm bảo SEO và tỷ lệ chuyển đổi cao nhất.
					</p>
				</div>
				<div class="relative mt-2">
					<a href="#" class="inline-flex items-center gap-1.5 text-base font-semibold text-primary dark:text-teal-400 no-underline transition-all duration-200 group-hover:gap-2.5">
						Tìm hiểu thêm
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<path d="M3.5 8H12.5M12.5 8L9 4.5M12.5 8L9 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</a>
				</div>
			</div>

			<!-- Card 4 -->
			<div class="group relative flex flex-col gap-4 p-8 rounded-2xl border border-black/5 dark:border-white/5 bg-white dark:bg-gray-800/80 shadow-sm backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 dark:hover:border-teal-400/30 hover:shadow-[0_12px_40px_-12px_rgba(0,0,0,0.12)] dark:hover:shadow-[0_12px_40px_-12px_rgba(0,0,0,0.5)] overflow-hidden">
				<div class="absolute -top-24 -right-24 size-48 bg-primary/5 dark:bg-teal-400/10 blur-3xl rounded-full pointer-events-none transition-opacity duration-500 opacity-0 group-hover:opacity-100" aria-hidden="true"></div>

				<div class="relative flex items-center justify-center size-13 rounded-xl bg-primary/4 dark:bg-teal-400/10 ring-1 ring-inset ring-primary/10 dark:ring-teal-400/20 text-primary dark:text-teal-400 shrink-0 group-hover:bg-primary group-hover:text-white group-hover:ring-primary dark:group-hover:bg-teal-400 dark:group-hover:text-gray-900 dark:group-hover:ring-teal-400 transition-all duration-300">
					<svg width="24" height="24" viewBox="0 0 28 28" fill="none" aria-hidden="true">
						<path d="M5 8h18M5 14h12M5 20h8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
						<circle cx="22" cy="19" r="4" fill="none" stroke="currentColor" stroke-width="1.75"/>
						<path d="M25 22L27 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
					</svg>
				</div>
				<div class="relative flex flex-col grow gap-2">
					<h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight m-0">Hỗ trợ 24/7</h3>
					<p class="text-base text-gray-500 dark:text-gray-400 leading-relaxed m-0 grow">
						Đội ngũ kỹ thuật sẵn sàng hỗ trợ mọi lúc — qua live chat, email hoặc điện thoại, không bao giờ để bạn chờ.
					</p>
				</div>
				<div class="relative mt-2">
					<a href="#" class="inline-flex items-center gap-1.5 text-base font-semibold text-primary dark:text-teal-400 no-underline transition-all duration-200 group-hover:gap-2.5">
						Tìm hiểu thêm
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<path d="M3.5 8H12.5M12.5 8L9 4.5M12.5 8L9 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</a>
				</div>
			</div>

		</div>

	</div>
</section>
