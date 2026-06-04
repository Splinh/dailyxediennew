<?php
/**
 * Block: Language Switcher
 *
 * Renders Polylang language switcher dropdown.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'pll_the_languages' ) ) {
	return;
}

$langs = pll_the_languages( [ 'raw' => 1 ] );
if ( empty( $langs ) ) {
	return;
}

$current_lang = null;
foreach ( $langs as $lang ) {
	if ( ! empty( $lang['current_lang'] ) ) {
		$current_lang = $lang;
		break;
	}
}
if ( ! $current_lang ) {
	$current_lang = reset( $langs );
}

?>
<!-- Language Switcher -->
<div class="relative inline-block text-left">
	<button type="button" data-fx-dropdown-toggle aria-label="<?php esc_attr_e( 'Chọn ngôn ngữ', 'spl' ); ?>" class="inline-flex items-center gap-1.5 p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/7 transition-colors cursor-pointer border-none bg-transparent" aria-expanded="false" aria-haspopup="true">
		<?php if ( ! empty( $current_lang['flag'] ) ) : ?>
		<span class="inline-block size-4 overflow-hidden rounded-sm shrink-0">
			<img src="<?php echo esc_url( $current_lang['flag'] ); ?>" alt="<?php echo esc_attr( $current_lang['name'] ); ?>" class="object-cover size-full block" />
		</span>
		<?php endif; ?>
		<span class="text-sm font-semibold uppercase"><?php echo esc_html( $current_lang['slug'] ); ?></span>
		<svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" class="transition-transform duration-200">
			<path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
	</button>
	<div class="dropdown-pane absolute right-0 mt-1 sm:w-50! origin-top-right rounded-xl bg-white dark:bg-gray-800 border border-black/8 dark:border-white/8 shadow-xl dark:shadow-[0_8px_32px_rgba(0,0,0,.5)] focus:outline-none p-1 z-50" data-fx-dropdown data-hover="true" role="menu" aria-orientation="vertical">
		<?php foreach ( $langs as $lang ) : ?>
		<a href="<?php echo esc_url( $lang['url'] ); ?>" class="flex items-center gap-2 px-3 py-1.5 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-black/5 dark:hover:bg-white/7 hover:text-gray-900 dark:hover:text-white no-underline transition-colors <?php echo ! empty( $lang['current_lang'] ) ? 'font-semibold bg-black/3 dark:bg-white/4' : ''; ?>" role="menuitem">
			<?php if ( ! empty( $lang['flag'] ) ) : ?>
			<span class="inline-block size-4 overflow-hidden rounded-sm shrink-0">
				<img src="<?php echo esc_url( $lang['flag'] ); ?>" alt="<?php echo esc_attr( $lang['name'] ); ?>" class="object-cover size-full block" />
			</span>
			<?php endif; ?>
			<span><?php echo esc_html( $lang['name'] ); ?></span>
		</a>
		<?php endforeach; ?>
	</div>
</div>
