<?php
/**
 * Vite manifest utility trait.
 *
 * Ported improvements from HD\Core\Asset:
 * - entryIndex() for O(1) lookup instead of O(n) manifest scan
 * - makeSlugFromPath() for proper slug normalization
 * - assetUrl() helper for DRY
 * - Transient caching retained from original HDA implementation
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Vite {

	/** @var array Static cache for manifest data */
	private static array $manifestCache = [];

	/** @var array Static cache for resolved entries */
	private static array $resolveCache = [];

	// --------------------------------------------------

	/**
	 * Get Vite manifest data with versioned caching.
	 */
	public static function manifest(): array {
		$cacheKey = 'manifest:hda';

		if ( isset( self::$manifestCache[ $cacheKey ] ) ) {
			return self::$manifestCache[ $cacheKey ];
		}

		$manifestPath = rtrim( HDA_PATH, '/\\' ) . '/assets/.vite/manifest.json';

		if ( ! is_file( $manifestPath ) || ! is_readable( $manifestPath ) ) {
			self::$manifestCache[ $cacheKey ] = [];

			return self::$manifestCache[ $cacheKey ];
		}

		$transientKey = 'hda_manifest';
		$fileMtime    = filemtime( $manifestPath ) ?: 0;
		$cached       = get_transient( $transientKey );

		if ( is_array( $cached ) && ( $cached['mtime'] ?? 0 ) === $fileMtime ) {
			self::$manifestCache[ $cacheKey ] = $cached['data'] ?? [];

			return self::$manifestCache[ $cacheKey ];
		}

		$data = wp_json_file_decode(
			$manifestPath,
			[
				'associative' => true,
				'depth'       => 512,
			]
		);

		if ( is_wp_error( $data ) ) {
			self::$manifestCache[ $cacheKey ] = [];

			return self::$manifestCache[ $cacheKey ];
		}

		if ( ! is_array( $data ) ) {
			self::$manifestCache[ $cacheKey ] = [];

			return self::$manifestCache[ $cacheKey ];
		}

		$filtered = self::filterManifestEntries( $data );
		set_transient(
			$transientKey,
			[
				'mtime' => $fileMtime,
				'data'  => $filtered,
			],
			DAY_IN_SECONDS
		);

		self::$manifestCache[ $cacheKey ] = $filtered;

		return self::$manifestCache[ $cacheKey ];
	}

	// --------------------------------------------------

	/**
	 * Filter manifest entries — keep only vendor chunks + isEntry entries.
	 */
	private static function filterManifestEntries( array $data ): array {
		$filtered   = [];
		$keepFields = [ 'file', 'name', 'src', 'css', 'isEntry', 'imports' ];

		foreach ( $data as $entryKey => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$isVendor = preg_match( '/^_?vendor\..+\.(js|css)$/', (string) $entryKey ) === 1;
			$isEntry  = ! empty( $entry['isEntry'] );

			if ( $isVendor || $isEntry ) {
				$filtered[ $entryKey ] = array_intersect_key( $entry, array_flip( $keepFields ) );
			}
		}

		return $filtered;
	}

	// --------------------------------------------------

	/**
	 * Resolve entry from Vite manifest.
	 */
	public static function manifestResolve( ?string $entry = null, string $handlePrefix = 'hda-' ): array {
		if ( ! $entry || ! trim( $entry ) ) {
			return [];
		}

		$cacheKey = $entry . '|' . $handlePrefix;
		if ( isset( self::$resolveCache[ $cacheKey ] ) ) {
			return self::$resolveCache[ $cacheKey ];
		}

		$manifest = self::manifest();
		if ( ! $manifest ) {
			self::$resolveCache[ $cacheKey ] = [];

			return self::$resolveCache[ $cacheKey ];
		}

		$entry = trim( wp_normalize_path( $entry ) );

		// Check vendor entries.
		if ( preg_match( '/^_?vendor(\..+)?\.(js|css)$/', $entry, $m ) ) {
			self::$resolveCache[ $cacheKey ] = self::resolveVendor( $manifest, $m[2], $handlePrefix );

			return self::$resolveCache[ $cacheKey ];
		}

		// Regular entries via O(1) index lookup.
		self::$resolveCache[ $cacheKey ] = self::resolveRegularEntry( $entry, $handlePrefix );

		return self::$resolveCache[ $cacheKey ];
	}

	// --------------------------------------------------

	private static function resolveVendor( array $manifest, string $ext, string $prefix ): array {
		$pattern = '/^_?vendor\..+\.' . $ext . '$/';

		foreach ( $manifest as $k => $v ) {
			if ( is_array( $v ) && preg_match( $pattern, $k ) && ! empty( $v['file'] ) ) {
				return [
					'handle' => $prefix . 'vendor-' . $ext,
					'src'    => self::assetUrl( $v['file'] ),
					'file'   => $v['src'] ?? '',
				];
			}
		}

		// Fallback for CSS from JS vendor.
		if ( 'css' === $ext ) {
			foreach ( $manifest as $k => $v ) {
				if ( ! empty( $v['css'][0] ) && preg_match( '/^_?vendor\..+\.js$/', $k ) ) {
					return [
						'handle' => $prefix . 'vendor-css',
						'src'    => self::assetUrl( $v['css'][0] ),
					];
				}
			}
		}

		return [];
	}

	// --------------------------------------------------

	/**
	 * Resolve regular entry using O(1) index lookup.
	 */
	private static function resolveRegularEntry( string $entry, string $prefix ): array {
		$ext       = strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) );
		$pathNoExt = preg_replace( '/\.' . preg_quote( $ext, '/' ) . '$/i', '', $entry );
		$isCss     = in_array( $ext, [ 'css', 'scss' ], true );
		$isJs      = 'js' === $ext;

		if ( ! $isCss && ! $isJs ) {
			return [];
		}

		$srcCandidates = $isCss
			? [ $pathNoExt . '.scss', $pathNoExt . '.css' ]
			: [ $pathNoExt . '.js' ];

		$found = null;
		$index = self::entryIndex();

		foreach ( $srcCandidates as $tail ) {
			$normalizedTail = wp_normalize_path( $tail );
			if ( isset( $index[ $normalizedTail ] ) ) {
				$found = $index[ $normalizedTail ];
				break;
			}
		}

		if ( ! $found ) {
			return [];
		}

		$baseSlug = self::makeSlugFromPath( $pathNoExt );
		$handle   = $prefix . $baseSlug . '-' . ( $isJs ? 'js' : 'css' );

		if ( $isJs && ! empty( $found['file'] ) ) {
			return [
				'handle'  => $handle,
				'src'     => self::assetUrl( $found['file'] ),
				'file'    => $found['src'] ?? '',
				'imports' => $found['imports'] ?? [],
				'css'     => $found['css'] ?? [],
			];
		}

		if ( $isCss ) {
			$file = $found['css'][0] ?? $found['file'] ?? null;
			if ( $file ) {
				return [
					'handle' => $handle,
					'src'    => self::assetUrl( $file ),
					'file'   => $found['src'] ?? '',
				];
			}
		}

		return [];
	}

	// --------------------------------------------------

	/**
	 * Build a lookup index from short entry names to manifest data.
	 * Enables O(1) lookup instead of O(n) manifest loop.
	 *
	 * @return array<string, array>
	 */
	private static function entryIndex(): array {
		static $index = null;

		if ( null !== $index ) {
			return $index;
		}

		$index    = [];
		$manifest = self::manifest();

		foreach ( $manifest as $key => $item ) {
			if ( ! is_array( $item ) || empty( $item['isEntry'] ) || empty( $item['src'] ) || ! is_string( $item['src'] ) ) {
				continue;
			}

			// Skip vendor entries (resolved via dedicated method).
			if ( preg_match( '/^_?vendor\..+\.(js|css)$/', (string) $key ) ) {
				continue;
			}

			$src   = wp_normalize_path( $item['src'] );
			$parts = explode( '/', $src );
			$count = count( $parts );

			// Index by progressive suffixes: 'index.js', 'scripts/index.js', etc.
			for ( $i = $count - 1; $i >= 1; $i-- ) {
				$suffix             = implode( '/', array_slice( $parts, $i ) );
				$index[ $suffix ] ??= $item;
			}

			// For SCSS: also index .css variant.
			if ( str_ends_with( $src, '.scss' ) ) {
				$cssSrc   = substr( $src, 0, -5 ) . '.css';
				$cssParts = explode( '/', $cssSrc );

				for ( $i = count( $cssParts ) - 1; $i >= 1; $i-- ) {
					$suffix             = implode( '/', array_slice( $cssParts, $i ) );
					$index[ $suffix ] ??= $item;
				}
			}

			// Full src path.
			$index[ $src ] ??= $item;
		}

		return $index;
	}

	// --------------------------------------------------

	/**
	 * Build full asset URL from a build output filename.
	 */
	public static function assetUrl( string $file ): string {
		return HDA_URL . 'assets/' . ltrim( $file, '/' );
	}

	// --------------------------------------------------

	/**
	 * Convert a file path (without extension) into a URL-safe slug.
	 */
	private static function makeSlugFromPath( string $pathNoExt ): string {
		$pathNoExt = str_replace( '\\', '/', $pathNoExt );
		$pathNoExt = preg_replace( '#/+#', '/', $pathNoExt );
		$pathNoExt = trim( $pathNoExt, '/' );
		$slug      = strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $pathNoExt ) );

		return trim( $slug, '-' ) ?: 'entry';
	}
}
