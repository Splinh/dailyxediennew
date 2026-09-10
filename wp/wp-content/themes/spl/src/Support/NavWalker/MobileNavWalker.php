<?php
/**
 * Mobile Nav Walker
 *
 * Custom Walker class for mobile drawer navigation menu.
 *
 * @package SPL
 */

namespace SPL\Support\NavWalker;

defined( 'ABSPATH' ) || exit;

/**
 * Walker for mobile drawer navigation.
 */
class MobileNavWalker extends \Walker_Nav_Menu {

	/**
	 * Starts the list before the elements are added.
	 *
	 * @param string    $output Used to append additional content (passed by reference).
	 * @param int       $depth  Depth of menu item. Used for padding.
	 * @param \stdClass $args   An object of wp_nav_menu() arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		$indent  = str_repeat( "\t", $depth );
		$output .= "\n{$indent}<ul class=\"sub-menu pl-4 mt-1 space-y-1 border-l-2 border-slate-100\">\n";
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @param string    $output Used to append additional content (passed by reference).
	 * @param int       $depth  Depth of menu item. Used for padding.
	 * @param \stdClass $args   An object of wp_nav_menu() arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ): void {
		$indent  = str_repeat( "\t", $depth );
		$output .= "{$indent}</ul>\n";
	}

	/**
	 * Starts the element output.
	 *
	 * @param string    $output            Used to append additional content (passed by reference).
	 * @param \WP_Post  $data_object       Menu item data object.
	 * @param int       $depth             Depth of menu item. Used for padding.
	 * @param \stdClass $args              An object of wp_nav_menu() arguments.
	 * @param int       $current_object_id Optional. ID of the current menu item. Default 0.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ): void {
		$item       = $data_object;
		$classes    = empty( $item->classes ) ? [] : (array) $item->classes;
		$classes[]  = 'menu-item-' . $item->ID;
		$is_current = in_array( 'current-menu-item', $classes, true ) || in_array( 'current_page_item', $classes, true );

		$link_classes = $depth === 0
			? 'flex items-center justify-between px-3.5 py-3 rounded-xl transition-colors ' . ( $is_current ? 'bg-primary-50 text-primary font-bold' : 'text-slate-800 hover:bg-slate-100 hover:text-primary font-bold text-sm' )
			: 'flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition-colors ' . ( $is_current ? 'text-primary font-bold bg-primary-50/60' : 'text-slate-600 hover:text-primary hover:bg-slate-50' );

		$chevron = function_exists( 'spl_icon' )
			? spl_icon( 'chevron-right', 'w-4 h-4 text-slate-400 shrink-0' )
			: '<svg class="w-4 h-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';

		$output .= '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$output .= '<a href="' . esc_url( $item->url ) . '" class="' . esc_attr( $link_classes ) . '">';
		$output .= '<span>' . esc_html( $item->title ) . '</span>';
		$output .= $chevron;
		$output .= '</a>';
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @param string    $output      Used to append additional content (passed by reference).
	 * @param \WP_Post  $data_object Menu item data object.
	 * @param int       $depth       Depth of menu item. Used for padding.
	 * @param \stdClass $args        An object of wp_nav_menu() arguments.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ): void {
		$output .= "</li>\n";
	}
}
