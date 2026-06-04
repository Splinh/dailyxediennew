<?php
/**
 * Horizontal Nav Walker
 *
 * Custom Walker class for horizontal dropdown WordPress navigation menus.
 *
 * @author HD
 */

namespace HD\Support\NavWalker;

defined( 'ABSPATH' ) || exit;

/**
 * Walker for horizontal dropdown menus
 */
class HorizontalNavWalker extends \Walker_Nav_Menu {
	/**
	 * @param string $output
	 * @param int $depth
	 * @param \stdClass $args An object of wp_nav_menu() arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		$discard = ( $args->item_spacing ?? '' ) === 'discard';
		$t       = $discard ? '' : "\t";
		$n       = $discard ? '' : "\n";
		$indent  = str_repeat( $t, $depth );

		$classes    = [ 'submenu', 'vertical', 'menu' ];
		$classNames = implode( ' ', apply_filters( 'nav_menu_submenu_css_class', $classes, $args, $depth ) );
		$classAttr  = $classNames ? ' class="' . esc_attr( $classNames ) . '"' : '';

		$output .= "{$n}{$indent}<ul{$classAttr}>{$n}";
	}
}
