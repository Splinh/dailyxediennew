<?php
/**
 * Script/Style Loader
 *
 * Handles script and style tag modifications, lazy loading, and async/defer attributes.
 *
 * @package HD\Features\Optimizer
 * @author  HD
 */

namespace HD\Features\Optimizer;

use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class ScriptLoader {

	/**
	 * Lazy-loaded styles queue.
	 *
	 * @var array
	 */
	private static array $lazyStyles = [];

	/** ---------------------------------------- */

	/**
	 * Register hooks for script/style loading.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'script_loader_tag', [ self::class, 'scriptLoaderTag' ], 12, 3 );
		add_filter( 'style_loader_tag', [ self::class, 'styleLoaderTag' ], 12, 2 );
		add_action( 'wp_body_open', [ self::class, 'printLazyStyles' ], 1 );

		// Strip versions in production.
		if ( ! Helper::development() ) {
			add_filter( 'style_loader_src', [ self::class, 'removeAssetVersion' ], 9999 );
			add_filter( 'script_loader_src', [ self::class, 'removeAssetVersion' ], 9999 );
		}
	}

	/** ---------------------------------------- */

	/**
	 * Remove version query string from assets in production.
	 *
	 * @param string $src
	 *
	 * @return string
	 */
	public static function removeAssetVersion( string $src ): string {
		if ( str_contains( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}

		return $src;
	}

	/** ---------------------------------------- */

	/**
	 * Modify script tags to add module, async, defer attributes.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 *
	 * @return string
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by script_loader_tag filter signature.
	public static function scriptLoaderTag( string $tag, string $handle, string $src ): string {
		global $pagenow;

		$registered = wp_scripts()->registered[ $handle ] ?? null;
		if ( ! $registered ) {
			return $tag;
		}

		$attributes = $registered->extra ?? [];

		// Process combined attributes (e.g., `module defer`) from `extra`
		// This runs for all pages including admin, so admin.js gets module/defer
		if ( ! empty( $attributes['extra'] ) ) {
			$extraAttrs = is_array( $attributes['extra'] ) ? $attributes['extra'] : explode( ' ', $attributes['extra'] );

			foreach ( $extraAttrs as $attr ) {
				$tag = self::addScriptAttribute( $tag, $attr );
			}
		}

		// Skip lazy/defer logic for admin pages and login page
		if ( $pagenow === 'wp-login.php' || is_admin() ) {
			return $tag;
		}

		// Add `type="module"` attributes if the script is marked as a module
		if ( ! empty( $attributes['module'] ) ) {
			$tag = self::addScriptAttribute( $tag, 'module' );
		}

		// Handle `async` and `defer` attributes
		foreach ( [ 'async', 'defer' ] as $attr ) {
			if ( ! empty( $attributes[ $attr ] ) ) {
				$tag = self::addScriptAttribute( $tag, $attr );
			}
		}

		// Add script handles to the array
		static $strParsed = null;
		$strParsed      ??= Helper::filterSettingOptions( 'defer_script' );

		return Helper::lazyScriptTag( $strParsed, $tag, $handle );
	}

	/** ---------------------------------------- */

	/**
	 * Modify style tags for lazy loading.
	 *
	 * @param string $html   Style tag HTML.
	 * @param string $handle Style handle.
	 *
	 * @return string
	 */
	public static function styleLoaderTag( string $html, string $handle ): string {
		global $pagenow;

		// Skip for admin pages and login page
		if ( $pagenow === 'wp-login.php' || ! $handle || is_admin() ) {
			return $html;
		}

		static $styles = null;
		$styles      ??= Helper::filterSettingOptions( 'defer_style' );

		$lazyHtml = Helper::lazyStyleTag( $styles, $html, $handle );

		if ( $lazyHtml === $html ) {
			return $html;
		}

		$safeHandle         = htmlspecialchars( $handle, ENT_QUOTES, 'UTF-8' );
		self::$lazyStyles[] = str_replace(
			"onload=\"this.rel='stylesheet'\"",
			"data-handle='{$safeHandle}' onload=\"this.rel='stylesheet'\"",
			$lazyHtml
		);

		return '';
	}

	/** ---------------------------------------- */

	/**
	 * Print lazy-loaded styles at wp_body_open.
	 *
	 * @return void
	 */
	public static function printLazyStyles(): void {
		if ( ! self::$lazyStyles || is_admin() ) {
			return;
		}

		$allowedTags = [
			'link' => [
				'rel'         => [],
				'id'          => [],
				'href'        => [],
				'type'        => [],
				'media'       => [],
				'as'          => [],
				'data-handle' => [],
				'onload'      => [],
				'crossorigin' => [],
			],
		];

		foreach ( self::$lazyStyles as $link ) {
			echo wp_kses( $link, $allowedTags );
		}
	}

	/** ---------------------------------------- */

	/**
	 * Add attribute to script tag if not already present.
	 *
	 * @param string $tag  Script tag HTML.
	 * @param string $attr Attribute name (module, async, defer, etc.).
	 *
	 * @return string Modified script tag.
	 */
	private static function addScriptAttribute( string $tag, string $attr ): string {
		// Handle module attribute specially
		if ( $attr === 'module' ) {
			// Already has type="module"?
			if ( preg_match( '#\stype=(["\'])module\1#', $tag ) ) {
				return $tag;
			}

			$result = preg_replace( '#(?=></script>)#', ' type="module"', $tag, 1 );

			return is_string( $result ) ? $result : $tag;
		}

		// Handle other attributes (async, defer, etc.)
		if ( preg_match( "#\s{$attr}(=|>|\s)#", $tag ) ) {
			return $tag;
		}

		$result = preg_replace( '#(?=></script>)#', " {$attr}", $tag, 1 );

		return is_string( $result ) ? $result : $tag;
	}
}
