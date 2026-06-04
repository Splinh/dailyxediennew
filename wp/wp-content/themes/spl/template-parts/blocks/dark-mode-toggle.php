<?php
/**
 * Block: Dark Mode Toggle
 *
 * Renders a button to toggle dark/light mode.
 * JS logic handled by preflight.js → utils/dark.js.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

?>
<!-- Dark Mode Toggle -->
<button type="button" id="theme-toggle" aria-label="<?php esc_attr_e( 'Chuyển chế độ tối/sáng', 'spl' ); ?>"
	class="dark-mode inline-flex items-center p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/7 transition-colors cursor-pointer border-none bg-transparent">
	<!-- Sun — visible in dark mode -->
	<svg class="hidden dark:block size-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5V3m0 18v-2M7.05 7.05L5.636 5.636m12.728 12.728L16.95 16.95M5 12H3m18 0h-2M7.05 16.95l-1.414 1.414M18.364 5.636L16.95 7.05M16 12a4 4 0 1 1-8 0a4 4 0 0 1 8 0"/>
	</svg>
	<!-- Moon — visible in light mode -->
	<svg class="block dark:hidden size-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 0 1-.5-17.986V3c-.354.966-.5 1.911-.5 3a9 9 0 0 0 9 9c.239 0 .254.018.488 0A9 9 0 0 1 12 21"/>
	</svg>
</button>
