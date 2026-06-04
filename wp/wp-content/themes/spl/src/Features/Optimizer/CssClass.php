<?php
/**
 * CSS Class Optimizer
 *
 * Handles body_class, post_class, and nav_menu class modifications.
 *
 * @package SPL\Features\Optimizer
 * @author  HD
 */

namespace SPL\Features\Optimizer;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class CssClass {

	/**
	 * Register CSS class filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'body_class', [ self::class, 'bodyClass' ], 12 );
		add_filter( 'post_class', [ self::class, 'postClass' ], 12 );
		add_filter( 'nav_menu_css_class', [ self::class, 'navMenuCssClass' ], 12, 4 );
		add_filter( 'nav_menu_link_attributes', [ self::class, 'navMenuLinkAttributes' ], 12, 4 );
		add_filter( 'nav_menu_submenu_css_class', [ self::class, 'navMenuSubmenuCssClass' ], 12, 2 );
	}

	/** ---------------------------------------- */

	/**
	 * Filter body classes - remove unwanted classes, add custom ones.
	 *
	 * @param array $classes Body classes.
	 *
	 * @return array
	 */
	public static function bodyClass( array $classes ): array {
		// Check whether we're in the customizer preview
		if ( is_customize_preview() ) {
			$classes[] = 'customizer-preview';
		}

		// Remove unwanted classes
		$unwantedPatterns = [
			'wp-custom-logo',
			'page-template-templates',
			'page-id-',
			'postid-',
			'single-format-standard',
			'no-customize-support',
		];

		$classes = array_filter(
			$classes,
			static fn( $cssClass ) => ! self::matchesAnyPattern( $cssClass, $unwantedPatterns )
		);

		// Add WooCommerce class if active
		if ( Helper::isWoocommerceActive() ) {
			$classes[] = 'woocommerce';
		}

		return $classes;
	}

	/** ---------------------------------------- */

	/**
	 * Filter post classes - rename sticky, remove tag/category classes.
	 *
	 * @param array $classes Post classes.
	 *
	 * @return array
	 */
	public static function postClass( array $classes ): array {
		// Rename sticky class to avoid CSS conflicts
		if ( in_array( 'sticky', $classes, true ) ) {
			$classes   = array_diff( $classes, [ 'sticky' ] );
			$classes[] = 'wp-sticky';
		}

		// Remove 'tag-', 'category-' classes
		return array_filter(
			$classes,
			static fn( $cssClass ) => ! str_contains( $cssClass, 'tag-' ) && ! str_contains( $cssClass, 'category-' )
		);
	}

	/** ---------------------------------------- */

	/**
	 * Filter nav menu item classes.
	 *
	 * @param array    $classes  Menu item classes.
	 * @param \WP_Post $menuItem Menu item object.
	 * @param object   $args     Menu arguments.
	 * @param int      $depth    Menu depth.
	 *
	 * @return array
	 */
	public static function navMenuCssClass( mixed $classes, \WP_Post $menuItem, object $args, int $depth ): array {
		$classes = (array) $classes;

		// Remove WordPress default menu classes (prefix-match only, preserves 3rd-party classes)
		$unwantedPrefixes = [
			'menu-item-type-',
			'menu-item-object-',
		];

		$classes = array_filter(
			$classes,
			static fn( $cssClass ) => $cssClass !== 'menu-item'
				&& ! ( str_starts_with( $cssClass, 'menu-item-' ) && ctype_digit( substr( $cssClass, 10 ) ) )
				&& ! self::matchesAnyPrefix( $cssClass, $unwantedPrefixes )
		);

		// Add active class
		if ( $menuItem->current || $menuItem->current_item_ancestor || $menuItem->current_item_parent ) {
			$classes[] = 'active';
		}

		// Add custom li_class based on depth
		if ( $depth === 0 && ! empty( $args->li_class ) ) {
			$classes[] = $args->li_class;
		} elseif ( $depth > 0 && ! empty( $args->li_depth_class ) ) {
			$classes[] = $args->li_depth_class;
		}

		return $classes;
	}

	/** ---------------------------------------- */

	/**
	 * Filter nav menu link attributes.
	 *
	 * @param array    $atts     Link attributes.
	 * @param \WP_Post $menuItem Menu item object.
	 * @param object   $args     Menu arguments.
	 * @param int      $depth    Menu depth.
	 *
	 * @return array
	 */
	public static function navMenuLinkAttributes( array $atts, \WP_Post $menuItem, object $args, int $depth ): array {
		$classProperty = match ( true ) {
			$depth === 0 && property_exists( $args, 'link_class' )     => $args->link_class,
			$depth > 0 && property_exists( $args, 'link_depth_class' ) => $args->link_depth_class,
			default                                                    => null,
		};

		if ( $classProperty ) {
			$atts['class'] = esc_attr( $classProperty );
		}

		return $atts;
	}

	/** ---------------------------------------- */

	/**
	 * Filter nav menu submenu classes.
	 *
	 * Appends custom classes from `$args->submenu_class` when provided.
	 *
	 * @param array  $classes Menu item classes.
	 * @param object $args    Menu arguments.
	 *
	 * @return array
	 */
	public static function navMenuSubmenuCssClass( array $classes, object $args ): array {
		if ( ! empty( $args->submenu_class ) ) {
			$extra   = array_filter( explode( ' ', (string) $args->submenu_class ) );
			$classes = array_merge( $classes, $extra );
		}

		return $classes;
	}

	/** ---------------------------------------- */

	/**
	 * Check if a class matches any pattern.
	 *
	 * @param string $cssClass
	 * @param array  $patterns
	 *
	 * @return bool
	 */
	private static function matchesAnyPattern( string $cssClass, array $patterns ): bool {
		foreach ( $patterns as $pattern ) {
			if ( str_contains( $cssClass, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a class starts with any of the given prefixes.
	 *
	 * @param string $cssClass
	 * @param array  $prefixes
	 *
	 * @return bool
	 */
	private static function matchesAnyPrefix( string $cssClass, array $prefixes ): bool {
		foreach ( $prefixes as $prefix ) {
			if ( str_starts_with( $cssClass, $prefix ) ) {
				return true;
			}
		}

		return false;
	}
}
