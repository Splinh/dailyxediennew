<?php
/**
 * Collect & enqueue CSS/JS.
 *
 * @author HD
 */

namespace HDAddons;

\defined( 'ABSPATH' ) || exit;

final class Asset {

	/**
	 * Track registered fallback handles to prevent duplicate registrations.
	 */
	private static array $registeredFallbacks = [];

	// ----------------------------------------

	/**
	 * Get the resolved handle for a manifest entry.
	 *
	 * @param string|null $entry Entry file path (e.g., 'settings.js', 'admin-core.js').
	 * @param string $handle_prefix Handle prefix (default: 'hda-').
	 *
	 * @return string|null Handle string or null if not found.
	 */
	public static function handle( ?string $entry = null, string $handle_prefix = 'hda-' ): ?string {
		if ( empty( $entry ) ) {
			return null;
		}

		$resolve = Helper::manifestResolve( $entry, $handle_prefix );

		return $resolve['handle'] ?? null;
	}

	// ----------------------------------------

	/**
	 * Enqueue CSS from Vite manifest.
	 *
	 * @param string|null $entry Entry file path.
	 * @param array $deps Dependencies.
	 * @param string|bool|null $ver Version.
	 * @param string $media Media type.
	 */
	public static function enqueueCSS(
		?string $entry = null,
		array $deps = [],
		string|bool|null $ver = null,
		string $media = 'all'
	): void {
		$resolve = Helper::manifestResolve( $entry );
		if ( empty( $resolve ) ) {
			return;
		}

		$resolve['deps']  = $deps;
		$resolve['ver']   = $ver;
		$resolve['media'] = $media;

		self::enqueueStyle( $resolve );
	}

	// ----------------------------------------

	/**
	 * Enqueue JS from Vite manifest.
	 *
	 * @param string|null $entry Entry file path.
	 * @param array $deps Dependencies.
	 * @param string|bool|null $ver Version.
	 * @param bool $in_footer Load in footer.
	 * @param array $extra Extra attributes (e.g., ['module', 'defer']).
	 */
	public static function enqueueJS(
		?string $entry = null,
		array $deps = [],
		string|bool|null $ver = null,
		bool $in_footer = true,
		array $extra = []
	): void {
		$resolve = Helper::manifestResolve( $entry );
		if ( empty( $resolve ) ) {
			return;
		}

		$resolve['deps']      = $deps;
		$resolve['ver']       = $ver;
		$resolve['in_footer'] = $in_footer;
		$resolve['extra']     = $extra;

		self::enqueueScript( $resolve );

		// Auto-enqueue CSS from imports (e.g., vendor.js -> vendor.css)
		self::enqueueImportedCSS( $resolve, $ver );
	}

	// ----------------------------------------

	/**
	 * Enqueue CSS from JS imports (vendor chunks, etc.).
	 *
	 * @param array $resolve Resolved manifest entry.
	 * @param string|bool|null $ver Version.
	 */
	private static function enqueueImportedCSS( array $resolve, string|bool|null $ver = null ): void {
		$imports = $resolve['imports'] ?? [];

		// Check imports for vendor CSS
		foreach ( $imports as $import ) {
			// Resolve vendor.js -> get its CSS
			if ( preg_match( '/^_?vendor\..+\.js$/', $import ) ) {
				$vendorCss = Helper::manifestResolve( 'vendor.css' );
				if ( ! empty( $vendorCss['handle'] ) && ! empty( $vendorCss['src'] ) ) {
					if ( ! wp_style_is( $vendorCss['handle'], 'registered' ) ) {
						wp_register_style( $vendorCss['handle'], $vendorCss['src'], [], $ver, 'all' );
					}
					wp_enqueue_style( $vendorCss['handle'] );
				}
			}
		}

		// Direct CSS dependencies from the entry itself
		if ( empty( $resolve['css'] ) || ! is_array( $resolve['css'] ) ) {
			return;
		}

		foreach ( $resolve['css'] as $key => $cssFile ) {
			// Skip vendor CSS (handled above via imports)
			if ( str_contains( $cssFile, 'vendor.' ) ) {
				continue;
			}

			$suffix = $key === 0 ? '-css' : '-' . $key . '-css';
			$handle = preg_replace( '/-js$/', $suffix, $resolve['handle'] );

			if ( wp_style_is( $handle ) || wp_style_is( $handle, 'registered' ) ) {
				continue;
			}

			$src = Helper::assetUrl( $cssFile );
			self::enqueueStyle(
				[
					'handle' => $handle,
					'src'    => $src,
					'deps'   => [],
					'ver'    => $ver,
					'media'  => 'all',
				]
			);
		}
	}

	// ----------------------------------------

	/**
	 * Enqueue vendor CSS if it exists in manifest.
	 * Call this first to ensure vendor CSS loads before other CSS.
	 *
	 * @return string|null Vendor CSS handle or null if not found.
	 */
	public static function enqueueVendorCSS(): ?string {
		$vendorCss = Helper::manifestResolve( 'vendor.css' );

		if ( empty( $vendorCss['handle'] ) || empty( $vendorCss['src'] ) ) {
			return null;
		}

		if ( ! wp_style_is( $vendorCss['handle'], 'registered' ) ) {
			wp_register_style( $vendorCss['handle'], $vendorCss['src'], [], self::version(), 'all' );
		}

		wp_enqueue_style( $vendorCss['handle'] );

		return $vendorCss['handle'];
	}

	// ----------------------------------------

	/**
	 * Enqueue a stylesheet.
	 *
	 * @param string|array $handle Handle string or config array.
	 * @param string|bool|null $src Source URL.
	 * @param array $deps Dependencies.
	 * @param string|bool|null $ver Version.
	 * @param string $media Media type.
	 */
	public static function enqueueStyle(
		string|array $handle,
		string|bool|null $src = null,
		array $deps = [],
		string|bool|null $ver = null,
		string $media = 'all'
	): void {
		if ( is_array( $handle ) ) {
			$args = wp_parse_args(
				$handle,
				[
					'handle' => '',
					'src'    => null,
					'deps'   => [],
					'ver'    => null,
					'media'  => 'all',
				]
			);
		} else {
			$args = [
				'handle' => $handle,
				'src'    => $src,
				'deps'   => $deps,
				'ver'    => $ver,
				'media'  => $media,
			];
		}

		if ( empty( $args['handle'] ) || empty( $args['src'] ) ) {
			return;
		}

		if ( ! wp_style_is( $args['handle'], 'registered' ) ) {
			wp_register_style( $args['handle'], $args['src'], $args['deps'], $args['ver'], $args['media'] );
		}

		wp_enqueue_style( $args['handle'] );
	}

	// ----------------------------------------

	/**
	 * Enqueue a script.
	 *
	 * @param string|array $handle Handle string or config array with keys:
	 *                                 - handle: (string) Script handle
	 *                                 - src/url: (string) Script URL (url is alias for src)
	 *                                 - deps: (array) Dependencies
	 *                                 - ver: (string|bool|null) Version
	 *                                 - in_footer: (bool) Load in footer
	 *                                 - extra/attr: (array) Extra attributes (attr is alias for extra).
	 * @param string|bool|null $src Source URL.
	 * @param array $deps Dependencies.
	 * @param string|bool|null $ver Version.
	 * @param bool $in_footer Load in footer.
	 * @param array $extra Extra attributes (e.g., ['module', 'defer']).
	 */
	public static function enqueueScript(
		string|array $handle,
		string|bool|null $src = null,
		array $deps = [],
		string|bool|null $ver = null,
		bool $in_footer = true,
		array $extra = []
	): void {
		if ( is_array( $handle ) ) {
			$args = wp_parse_args(
				$handle,
				[
					'handle'    => '',
					'src'       => null,
					'url'       => null,
					'deps'      => [],
					'ver'       => null,
					'in_footer' => true,
					'extra'     => [],
					'attr'      => [],
				]
			);

			// Alias: url -> src
			if ( empty( $args['src'] ) && ! empty( $args['url'] ) ) {
				$args['src'] = $args['url'];
			}

			// Alias: attr -> extra
			if ( ! empty( $args['attr'] ) && empty( $args['extra'] ) ) {
				$args['extra'] = $args['attr'];
			}
		} else {
			$args = [
				'handle'    => $handle,
				'src'       => $src,
				'deps'      => $deps,
				'ver'       => $ver,
				'in_footer' => $in_footer,
				'extra'     => $extra,
			];
		}

		if ( empty( $args['handle'] ) || empty( $args['src'] ) ) {
			return;
		}

		if ( ! wp_script_is( $args['handle'], 'registered' ) ) {
			wp_register_script( $args['handle'], $args['src'], $args['deps'], $args['ver'], (bool) $args['in_footer'] );
		}

		wp_enqueue_script( $args['handle'] );

		if ( ! empty( $args['extra'] ) ) {
			wp_script_add_data( $args['handle'], 'hda', $args['extra'] );
		}
	}

	// ----------------------------------------

	/**
	 * Localize a script with data.
	 *
	 * @param string $handle Script handle.
	 * @param string $object_name JavaScript object name.
	 * @param array|bool|null $l10n Data to pass to script.
	 */
	public static function localize(
		string $handle,
		string $object_name,
		array|bool|null $l10n
	): void {
		if ( empty( $object_name ) || empty( $l10n ) ) {
			return;
		}

		if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle ) ) {
			wp_localize_script( $handle, $object_name, $l10n );
		}
	}

	// ----------------------------------------

	/**
	 * Add inline CSS to a registered/enqueued style.
	 *
	 * @param string $handle Style handle to attach to.
	 * @param string|bool|null $css CSS code to add.
	 */
	public static function inlineStyle( string $handle, string|null|bool $css ): void {
		if ( empty( $css ) || ! is_string( $css ) ) {
			return;
		}

		// Sanitize CSS to prevent injection
		$css = Helper::extractCss( $css );
		if ( empty( $css ) ) {
			return;
		}

		if ( wp_style_is( $handle, 'registered' ) || wp_style_is( $handle, 'enqueued' ) ) {
			wp_add_inline_style( $handle, $css );
		} else {
			$fallback = 'hda-inline-style-' . md5( $handle );

			// Only register once per fallback handle
			if ( ! isset( self::$registeredFallbacks[ $fallback ] ) ) {
				wp_register_style( $fallback, false, [], self::version(), 'all' );
				wp_enqueue_style( $fallback );
				self::$registeredFallbacks[ $fallback ] = true;
			}

			wp_add_inline_style( $fallback, $css );
		}
	}

	// ----------------------------------------

	/**
	 * Asset version based on manifest file hash.
	 * Changes only when Vite rebuilds — enables proper browser caching.
	 *
	 * @return string|false
	 */
	public static function version(): string|false {
		static $hash;

		if ( isset( $hash ) ) {
			return $hash ?: false;
		}

		$path = rtrim( HDA_PATH, '/\\' ) . '/assets/.vite/manifest.json';
		$hash = is_file( $path ) ? substr( (string) md5_file( $path ), 0, 8 ) : '';

		return $hash ?: false;
	}
}
