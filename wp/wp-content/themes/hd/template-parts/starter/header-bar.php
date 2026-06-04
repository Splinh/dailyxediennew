<?php
/**
 * Starter: Site Header Bar
 *
 * 3-column layout: Logo | Main Nav | Actions
 * Tailwind 4 syntax — CSS variable shorthand via (), open scale for z/spacing.
 * Static HTML — replace with dynamic WP functions when ready.
 *
 * @package HD
 */

defined( 'ABSPATH' ) || exit;
?>
<header
	id="site-header"
	data-site-header
	data-fx-sticky
	class="sticky top-0 inset-x-0 z-999 h-16 border-b border-black/7 dark:border-white/7 bg-white/85 dark:bg-gray-900/90 backdrop-blur-md backdrop-saturate-180 transition-[background,box-shadow] duration-300"
>
	<div class="flex items-center gap-8 h-full container mx-auto px-6 xl:px-12">

		<!-- ── Logo ─────────────────────────────────────── -->
		<a href="/" aria-label="2026 — Home" class="flex items-center gap-2 shrink-0 no-underline group">
			<span aria-hidden="true" class="flex text-primary dark:text-teal-400 transition-transform duration-300 group-hover:scale-110">
				<svg width="32" height="32" viewBox="0 0 32 32" fill="none">
					<rect width="32" height="32" rx="8" fill="currentColor"/>
					<path d="M8 22L13 10L16 18L19 13L24 22H8Z" fill="white" opacity="0.9"/>
				</svg>
			</span>
			<span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">2026</span>
		</a>

		<!-- ── Main Navigation ───────────────────────────── -->
		<nav id="main-nav" aria-label="Main navigation" class="hidden lg:block flex-1">
			<?php
			echo \HD_Helper::doShortcode(
				'horizontal_menu',
				[
					'location'         => 'main-nav',
					'class'            => 'dropdown menu flex items-center gap-1 list-none m-0 p-0',
					'link_class'       => 'inline-flex items-center px-3 py-1.5 rounded-md text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/7 transition-colors no-underline',
					'link_depth_class' => 'block px-3 py-1.5 rounded-md text-base text-gray-600 dark:text-gray-300 hover:bg-black/5 dark:hover:bg-white/7 hover:text-gray-900 dark:hover:text-white no-underline transition-colors',
					'submenu_class'    => 'bg-white dark:bg-gray-800 border border-black/8 dark:border-white/8 rounded-xl shadow-xl dark:shadow-[0_8px_32px_rgba(0,0,0,.5)] list-none m-0 p-1.5 z-50',
					'data_autohide'    => true,
					'data_hover'       => true,
				]
			);
			?>
		</nav>

		<!-- ── Right Actions ─────────────────────────────── -->
		<div class="flex items-center gap-1 shrink-0 ml-auto">

			<!-- Search -->
			<?php
			echo \HD_Helper::doShortcode(
				'dropdown_search',
				[
					'class'         => 'header-search',
					'show_title'    => false,
					'trigger_class' => 'relative inline-flex items-center justify-center p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/7 transition-colors cursor-pointer border-none bg-transparent',
					'icon_class'    => 'size-[18px]',
					'pane_class'    => 'absolute right-0 mt-2 origin-top-right rounded-xl bg-white dark:bg-gray-800 border border-black/8 dark:border-white/8 shadow-xl dark:shadow-[0_8px_32px_rgba(0,0,0,.5)] focus:outline-none p-4 z-50 w-80 sm:w-100',
				]
			);
			?>

			<!-- Cart -->
			<a href="/cart" aria-label="Giỏ hàng (0 sản phẩm)"
				class="relative inline-flex items-center p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/7 transition-colors no-underline">
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
					<path d="M2 2H3.5L5.5 12H13.5L15.5 5H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					<circle cx="7" cy="15" r="1" fill="currentColor"/>
					<circle cx="13" cy="15" r="1" fill="currentColor"/>
				</svg>
				<span aria-hidden="true"
					class="absolute top-0.5 right-0.5 min-w-4 h-4 bg-red-500 text-white text-[0.625rem] font-bold rounded-full flex items-center justify-center px-1 pointer-events-none leading-none">0</span>
			</a>

			<!-- User -->
			<a href="/my-account" aria-label="Tài khoản"
				class="inline-flex items-center p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/7 transition-colors no-underline">
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
					<circle cx="9" cy="6" r="3.5" stroke="currentColor" stroke-width="1.5"/>
					<path d="M2.5 16C2.5 13 5.5 11 9 11C12.5 11 15.5 13 15.5 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				</svg>
			</a>

			<?php get_template_part( 'template-parts/blocks/language-switcher' ); ?>
			<?php get_template_part( 'template-parts/blocks/dark-mode-toggle' ); ?>

			<!-- CTA Button — hidden below xl -->
			<a href="/contact" aria-label="Bắt đầu dự án"
				class="hidden xl:inline-flex items-center justify-center px-4 py-2 rounded-lg text-base font-semibold text-white bg-primary dark:bg-teal-400 dark:text-gray-900 hover:bg-primary-hover dark:hover:bg-teal-300 transition-colors shadow-sm ring-1 ring-inset ring-black/10 dark:ring-white/10 no-underline ml-2">
				Bắt đầu ngay
			</a>

			<!-- Mobile hamburger — hidden on lg+ -->
			<button type="button" id="mobile-menu-toggle" aria-label="Mở menu" aria-expanded="false" aria-controls="main-nav"
				class="lg:hidden inline-flex items-center p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/7 transition-colors cursor-pointer border-none bg-transparent">
				<span class="flex flex-col gap-1 w-[18px]" aria-hidden="true">
					<span class="block h-px bg-current rounded transition-transform duration-200"></span>
					<span class="block h-px bg-current rounded w-3.5 transition-opacity duration-150"></span>
					<span class="block h-px bg-current rounded transition-transform duration-200"></span>
				</span>
			</button>

		</div>

	</div>
</header>
