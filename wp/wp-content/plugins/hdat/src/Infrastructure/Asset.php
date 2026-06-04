<?php
/**
 * @package HDAT\Infrastructure
 */

declare(strict_types=1);

namespace HDAT\Infrastructure;

defined( 'ABSPATH' ) || exit;

/**
 * Vite manifest-based asset enqueue for HD AI Toolkit.
 *
 * Managed asset resolution and automatic CSS extraction enqueue.
 */
final class Asset {

	/** @var array<string, array> */
	private static array $manifestCache = [];

	/** @var array<string, array> */
	private static array $resolveCache = [];

	/**
	 * Get resolved handle for a manifest entry.
	 */
	public static function handle( ?string $entry = null ): ?string {
		if ( empty( $entry ) ) {
			return null;
		}

		$resolve = self::manifestResolve( $entry );

		return $resolve['handle'] ?? null;
	}

	/**
	 * Enqueue JS from Vite manifest.
	 *
	 * @param string|null     $entry     Entry file path.
	 * @param array           $deps      Dependencies.
	 * @param string|bool|null $ver      Version.
	 * @param bool            $in_footer Load in footer.
	 * @param array           $extra     Extra attributes (e.g., ['module', 'defer']).
	 */
	public static function enqueueJS(
		?string $entry = null,
		array $deps = [],
		string|bool|null $ver = null,
		bool $in_footer = true,
		array $extra = []
	): void {
		$resolve = self::manifestResolve( $entry );
		if ( empty( $resolve['handle'] ) || empty( $resolve['src'] ) ) {
			return;
		}

		if ( ! wp_script_is( $resolve['handle'], 'registered' ) ) {
			wp_register_script( $resolve['handle'], $resolve['src'], $deps, $ver ?? self::version(), $in_footer );
		}

		wp_enqueue_script( $resolve['handle'] );

		if ( $extra ) {
			wp_script_add_data( $resolve['handle'], 'hdat', $extra );
		}

		// Auto-enqueue extracted CSS.
		if ( ! empty( $resolve['css'] ) && is_array( $resolve['css'] ) ) {
			foreach ( $resolve['css'] as $idx => $cssFile ) {
				$cssHandle = $resolve['handle'] . ( 0 === $idx ? '-css' : '-' . $idx . '-css' );
				if ( ! wp_style_is( $cssHandle, 'registered' ) ) {
					wp_register_style( $cssHandle, self::assetUrl( $cssFile ), [], $ver ?? self::version() );
				}
				wp_enqueue_style( $cssHandle );
			}
		}
	}

	/**
	 * Localize a script with data.
	 */
	public static function localize( string $handle, string $objectName, array $l10n ): void {
		if ( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle ) ) {
			wp_localize_script( $handle, $objectName, $l10n );
		}
	}

	/**
	 * Resolve entry from Vite manifest.
	 *
	 * @return array{handle?: string, src?: string, css?: string[], file?: string}
	 */
	public static function manifestResolve( ?string $entry = null ): array {
		if ( ! $entry || ! trim( $entry ) ) {
			return [];
		}

		if ( isset( self::$resolveCache[ $entry ] ) ) {
			return self::$resolveCache[ $entry ];
		}

		$manifest = self::manifest();
		if ( ! $manifest ) {
			self::$resolveCache[ $entry ] = [];

			return [];
		}

		$entry     = trim( wp_normalize_path( $entry ) );
		$ext       = strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) );
		$pathNoExt = preg_replace( '/\.' . preg_quote( $ext, '/' ) . '$/i', '', $entry );

		// Find matching manifest entry.
		$found = null;
		foreach ( $manifest as $key => $item ) {
			if ( ! is_array( $item ) || empty( $item['isEntry'] ) ) {
				continue;
			}

			$src = wp_normalize_path( $item['src'] ?? '' );
			if ( str_ends_with( $src, '/' . $entry ) || $src === $entry ) {
				$found = $item;
				break;
			}

			// Match without extension.
			$srcNoExt = preg_replace( '/\.[^.]+$/', '', $src );
			if ( str_ends_with( $srcNoExt, '/' . $pathNoExt ) || $srcNoExt === $pathNoExt ) {
				$found = $item;
				break;
			}
		}

		if ( ! $found || empty( $found['file'] ) ) {
			self::$resolveCache[ $entry ] = [];

			return [];
		}

		$slug   = strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $pathNoExt ) );
		$slug   = trim( $slug, '-' ) ?: 'entry';
		$handle = 'hdat-' . $slug . '-js';

		$result = [
			'handle' => $handle,
			'src'    => self::assetUrl( $found['file'] ),
			'css'    => $found['css'] ?? [],
			'file'   => $found['src'] ?? '',
		];

		self::$resolveCache[ $entry ] = $result;

		return $result;
	}

	/**
	 * Get Vite manifest data.
	 */
	private static function manifest(): array {
		if ( isset( self::$manifestCache['hdat'] ) ) {
			return self::$manifestCache['hdat'];
		}

		$manifestPath = rtrim( HDAT_DIR, '/\\' ) . '/assets/.vite/manifest.json';

		if ( ! is_file( $manifestPath ) || ! is_readable( $manifestPath ) ) {
			self::$manifestCache['hdat'] = [];

			return [];
		}

		$data = wp_json_file_decode(
			$manifestPath,
			[
				'associative' => true,
				'depth'       => 512,
			]
		);

		if ( is_wp_error( $data ) || ! is_array( $data ) ) {
			self::$manifestCache['hdat'] = [];

			return [];
		}

		self::$manifestCache['hdat'] = $data;

		return $data;
	}

	/**
	 * Build full asset URL.
	 */
	public static function assetUrl( string $file ): string {
		return HDAT_URL . 'assets/' . ltrim( $file, '/' );
	}

	/**
	 * Asset version based on manifest file hash.
	 */
	public static function version(): string|false {
		static $hash;

		if ( isset( $hash ) ) {
			return $hash ?: false;
		}

		$path = rtrim( HDAT_DIR, '/\\' ) . '/assets/.vite/manifest.json';
		$hash = is_file( $path ) ? substr( (string) md5_file( $path ), 0, 8 ) : '';

		return $hash ?: false;
	}

	/**
	 * Inject extra attributes (defer, module) to hdat script tags.
	 */
	public static function scriptLoaderTag( string $tag, string $handle ): string {
		$scripts = wp_scripts();
		$reg     = $scripts->registered[ $handle ] ?? null;

		if ( ! $reg || empty( $reg->extra['hdat'] ) ) {
			return $tag;
		}

		$extras = is_array( $reg->extra['hdat'] )
			? $reg->extra['hdat']
			: explode( ' ', (string) $reg->extra['hdat'] );

		foreach ( $extras as $attr ) {
			$attr = trim( $attr );
			if ( empty( $attr ) ) {
				continue;
			}

			if ( 'module' === $attr ) {
				if ( ! str_contains( $tag, 'type=' ) ) {
					$tag = str_replace( ' src=', ' type="module" src=', $tag );
				}
			} elseif ( ! preg_match( "#\\s{$attr}(=|>|\\s|$)#", $tag ) ) {
				$tag = str_replace( ' src=', " {$attr} src=", $tag );
			}
		}

		return $tag;
	}
}
