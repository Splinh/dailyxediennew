<?php
/**
 * Starter Footer Bar
 *
 * @package HD
 */

use HD\Core\Helper;

\defined( 'ABSPATH' ) || exit;

// Menu classes for desktop & mobile
$menu_item_classes = 'group inline-flex items-center gap-2 text-base font-medium text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-teal-400 transition-all duration-200 no-underline py-2.5 md:py-1.5 hover:translate-x-1';
?>
<div class="relative w-full bg-white dark:bg-gray-900 border-t border-black/5 dark:border-white/5 pt-16 pb-8 overflow-hidden z-10">
	<!-- Decorative Background Glow -->
	<div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-4xl h-[500px] bg-primary/4 dark:bg-teal-400/5 blur-[120px] rounded-full pointer-events-none" aria-hidden="true"></div>

	<div class="container mx-auto px-6 xl:px-12 relative z-10">

		<!-- Footer Top CTA Card (Elevated Premium UI) -->
		<div class="relative overflow-hidden bg-gray-50 dark:bg-gray-800/60 rounded-4xl p-8 md:p-12 lg:p-16 border border-black/5 dark:border-white/5 mb-16 shadow-sm backdrop-blur-md flex flex-col md:flex-row items-center justify-between gap-8 text-center md:text-left">
			<div class="absolute -right-32 -top-32 size-96 bg-primary/10 dark:bg-teal-400/10 blur-[100px] rounded-full pointer-events-none" aria-hidden="true"></div>
			<div class="absolute -left-32 -bottom-32 size-80 bg-blue-500/10 blur-[100px] rounded-full pointer-events-none" aria-hidden="true"></div>

			<div class="relative z-10 max-w-2xl">
				<h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-gray-900 dark:text-white tracking-tight mb-4">Sẵn sàng bứt phá?</h2>
				<p class="text-lg text-gray-600 dark:text-gray-300 m-0 leading-relaxed">
					Bắt đầu dự án số hóa của bạn cùng đội ngũ chuyên gia. Chúng tôi biến ý tưởng phức tạp thành trải nghiệm tuyệt vời.
				</p>
			</div>
			<div class="relative z-10 shrink-0">
				<a href="/contact" class="inline-flex items-center justify-center px-8 py-4 rounded-xl text-[0.9375rem] font-bold text-white bg-primary dark:bg-teal-400 dark:text-gray-900 hover:bg-primary-hover dark:hover:bg-teal-300 transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1 ring-1 ring-inset ring-black/10 dark:ring-white/10 no-underline gap-2 group">
					Khởi tạo dự án
					<svg width="18" height="18" viewBox="0 0 16 16" fill="none" class="transition-transform duration-300 group-hover:translate-x-1">
						<path d="M3.5 8H12.5M12.5 8L9 4.5M12.5 8L9 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</a>
			</div>
		</div>

		<!-- Footer Main Grid with Mobile Accordion -->
		<div class="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-16 pb-16">

			<!-- Brand Column -->
			<div class="md:col-span-5 lg:col-span-4 flex flex-col gap-6">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-block no-underline shrink-0 mb-2">
					<span class="text-3xl font-black tracking-tighter text-gray-900 dark:text-white">2026<span class="text-primary dark:text-teal-400">.</span></span>
				</a>
				<p class="text-base text-gray-500 dark:text-gray-400 leading-relaxed m-0 pr-4">
					Đại lý công nghệ tiên phong, cung cấp giải pháp toàn diện về thiết kế trải nghiệm, phát triển phần mềm và tối ưu hóa hệ thống.
				</p>

				<!-- Contact Info -->
				<div class="flex flex-col gap-3 mt-2">
					<a href="mailto:hello@2026.com" class="inline-flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-white hover:text-primary dark:hover:text-teal-400 transition-colors no-underline">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="text-gray-400"><path d="M3 8L10.8906 13.2604C11.5624 13.7083 12.4376 13.7083 13.1094 13.2604L21 8M5 19H19C20.1046 19 21 18.1046 21 17V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V17C3 18.1046 3.89543 19 5 19Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
						hello@2026.com
					</a>
				</div>

				<!-- Socials -->
				<div class="flex items-center gap-3 mt-4">
					<a href="#" aria-label="Facebook" class="flex items-center justify-center size-10 rounded-full bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-primary hover:text-white dark:hover:bg-teal-400 dark:hover:text-gray-900 transition-all duration-300 ring-1 ring-inset ring-black/5 dark:ring-white/10 hover:ring-primary dark:hover:ring-teal-400 hover:-translate-y-1 hover:shadow-md no-underline">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M14 13.5H16.5L17.5 9.5H14V7.5C14 6.47 14 5.5 16 5.5H17.5V2.14C17.174 2.097 15.943 2 14.643 2C11.928 2 10 3.657 10 6.7V9.5H7V13.5H10V22H14V13.5Z"/></svg>
					</a>
					<a href="#" aria-label="Twitter" class="flex items-center justify-center size-10 rounded-full bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-primary hover:text-white dark:hover:bg-teal-400 dark:hover:text-gray-900 transition-all duration-300 ring-1 ring-inset ring-black/5 dark:ring-white/10 hover:ring-primary dark:hover:ring-teal-400 hover:-translate-y-1 hover:shadow-md no-underline">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M23.643 4.937C22.784 5.33 21.854 5.59 20.875 5.71C21.876 5.093 22.64 4.12 23.003 2.965C22.078 3.528 21.054 3.937 19.962 4.16C19.09 3.208 17.844 2.615 16.467 2.615C13.823 2.615 11.677 4.815 11.677 7.525C11.677 7.91 11.722 8.283 11.808 8.643C7.828 8.442 4.316 6.5 1.956 3.56C1.54 4.29 1.302 5.14 1.302 6.035C1.302 7.74 2.148 9.245 3.435 10.12C2.65 10.095 1.91 9.877 1.265 9.51V9.57C1.265 11.95 2.923 13.935 5.115 14.385C4.703 14.5 4.27 14.558 3.82 14.558C3.504 14.558 3.197 14.526 2.898 14.47C3.506 16.425 5.285 17.847 7.387 17.887C5.74 19.21 3.655 20 1.398 20C0.993 20 0.596 19.977 0.205 19.93C2.333 21.328 4.864 22.145 7.584 22.145C16.44 22.145 21.282 14.6 21.282 8.062C21.282 7.85 21.277 7.64 21.266 7.433C22.208 6.735 23.024 5.885 23.643 4.937Z"/></svg>
					</a>
					<a href="#" aria-label="LinkedIn" class="flex items-center justify-center size-10 rounded-full bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-primary hover:text-white dark:hover:bg-teal-400 dark:hover:text-gray-900 transition-all duration-300 ring-1 ring-inset ring-black/5 dark:ring-white/10 hover:ring-primary dark:hover:ring-teal-400 hover:-translate-y-1 hover:shadow-md no-underline">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452H16.907V14.88C16.907 13.55 16.883 11.84 15.06 11.84C13.212 11.84 12.928 13.28 12.928 14.783V20.452H9.39V9H12.784V10.562H12.833C13.305 9.664 14.464 8.73 16.173 8.73C19.747 8.73 20.447 11.08 20.447 14.12V20.452ZM5.337 7.433C4.195 7.433 3.273 6.51 3.273 5.37C3.273 4.228 4.195 3.305 5.337 3.305C6.475 3.305 7.397 4.228 7.397 5.37C7.397 6.51 6.475 7.433 5.337 7.433ZM7.108 20.452H3.563V9H7.108V20.452Z"/></svg>
					</a>
				</div>
			</div>

			<!-- Links Accordion Group -->
			<div class="md:col-span-7 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-y-4 md:gap-x-8" data-fx-accordion="true" data-allow-all-closed="true" data-multi-expand="true">

				<!-- Dịch vụ -->
				<div class="flex flex-col border-b border-black/5 dark:border-white/5 md:border-none pb-4 md:pb-0" data-fx-accordion-item>
					<button type="button" class="flex md:hidden items-center justify-between w-full py-2 text-left text-base font-bold text-gray-900 dark:text-white group" data-fx-accordion-title aria-expanded="false">
						Dịch vụ
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="text-gray-400 transition-transform duration-300 group-[.is-active]:rotate-180"><path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
					<h3 class="hidden md:block text-base font-bold text-gray-900 dark:text-white tracking-widest uppercase mb-6 m-0">Dịch vụ</h3>

					<div class="h-0 md:h-auto overflow-hidden opacity-0 md:opacity-100 transition-all duration-300 md:max-h-none md:block" data-fx-accordion-content aria-hidden="true">
						<ul class="flex flex-col gap-1.5 m-0 p-0 list-none mt-2 md:mt-0">
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Phát triển Web App</a></li>
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Thiết kế UI/UX</a></li>
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Chuyển đổi số</a></li>
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Tối ưu SEO & Tốc độ</a></li>
						</ul>
					</div>
				</div>

				<!-- Công ty -->
				<div class="flex flex-col border-b border-black/5 dark:border-white/5 md:border-none pb-4 md:pb-0" data-fx-accordion-item>
					<button type="button" class="flex md:hidden items-center justify-between w-full py-2 text-left text-base font-bold text-gray-900 dark:text-white group" data-fx-accordion-title aria-expanded="false">
						Công ty
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="text-gray-400 transition-transform duration-300 group-[.is-active]:rotate-180"><path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
					<h3 class="hidden md:block text-base font-bold text-gray-900 dark:text-white tracking-widest uppercase mb-6 m-0">Công ty</h3>

					<div class="h-0 md:h-auto overflow-hidden opacity-0 md:opacity-100 transition-all duration-300 md:max-h-none md:block" data-fx-accordion-content aria-hidden="true">
						<ul class="flex flex-col gap-1.5 m-0 p-0 list-none mt-2 md:mt-0">
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Về chúng tôi</a></li>
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Đội ngũ chuyên gia</a></li>
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Dự án tiêu biểu</a></li>
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Tuyển dụng</a></li>
						</ul>
					</div>
				</div>

				<!-- Hỗ trợ -->
				<div class="flex flex-col border-b border-black/5 dark:border-white/5 md:border-none pb-4 md:pb-0" data-fx-accordion-item>
					<button type="button" class="flex md:hidden items-center justify-between w-full py-2 text-left text-base font-bold text-gray-900 dark:text-white group" data-fx-accordion-title aria-expanded="false">
						Hỗ trợ
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="text-gray-400 transition-transform duration-300 group-[.is-active]:rotate-180"><path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
					<h3 class="hidden md:block text-base font-bold text-gray-900 dark:text-white tracking-widest uppercase mb-6 m-0">Hỗ trợ</h3>

					<div class="h-0 md:h-auto overflow-hidden opacity-0 md:opacity-100 transition-all duration-300 md:max-h-none md:block" data-fx-accordion-content aria-hidden="true">
						<ul class="flex flex-col gap-1.5 m-0 p-0 list-none mt-2 md:mt-0">
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Trung tâm trợ giúp</a></li>
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Tài liệu kỹ thuật</a></li>
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Liên hệ</a></li>
							<li><a href="#" class="<?php echo esc_attr( $menu_item_classes ); ?>">Gửi yêu cầu</a></li>
						</ul>
					</div>
				</div>

			</div>
		</div>

		<!-- Footer Bottom -->
		<div class="flex flex-col-reverse md:flex-row items-center justify-between gap-6 pt-8 border-t border-black/5 dark:border-white/10">
			<!-- Copyright & Badge -->
			<div class="flex flex-col md:flex-row items-center gap-4 text-base text-gray-500 dark:text-gray-400">
				<p class="m-0 text-center md:text-left">
					&copy; 2020 - <?php echo esc_html( gmdate( 'Y' ) ); ?> HD Agency. All rights reserved.
				</p>
				<!-- System Status Badge -->
				<a href="#" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-black/5 dark:border-white/5 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors no-underline">
					<span class="flex size-2 rounded-full bg-teal-400 shadow-[0_0_8px_rgba(45,212,191,0.6)] animate-pulse"></span>
					<span class="text-xs font-semibold text-gray-600 dark:text-gray-300">All systems operational</span>
				</a>
			</div>

			<!-- Legal Links -->
			<div class="flex items-center gap-6 text-base font-medium text-gray-500 dark:text-gray-400">
				<a href="#" class="no-underline text-inherit hover:text-gray-900 dark:hover:text-white transition-colors">Điều khoản</a>
				<a href="#" class="no-underline text-inherit hover:text-gray-900 dark:hover:text-white transition-colors">Bảo mật</a>
				<a href="#" class="no-underline text-inherit hover:text-gray-900 dark:hover:text-white transition-colors">Cookies</a>
			</div>
		</div>

	</div>
</div>
