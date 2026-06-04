<?php
/**
 * Image Converter — Context / Factory.
 *
 * Strategy Pattern context: resolves the best available engine
 * from the registered engine pool and delegates conversion.
 *
 * Adding a new engine:
 * 1. Create a class implementing EngineInterface
 * 2. Add it to getEnginePool() below
 * That's it — the engine will be auto-detected and prioritized.
 *
 * @package HDAddons\Modules\ImageConverter
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter;

use HDAddons\Modules\ImageConverter\Contracts\EngineInterface;
use HDAddons\Modules\ImageConverter\Engines\CliEngine;
use HDAddons\Modules\ImageConverter\Engines\GdEngine;
use HDAddons\Modules\ImageConverter\Engines\ImagickEngine;

\defined( 'ABSPATH' ) || exit;

final class Converter {

	// ─── Supported Formats ──────────────────────────────

	public const FORMAT_NONE = 'none';
	public const FORMAT_WEBP = 'webp';
	public const FORMAT_AVIF = 'avif';

	/** Source formats that can be converted. */
	private const SUPPORTED_EXTENSIONS = [ 'jpg', 'jpeg', 'png', 'gif' ];

	/** Default quality settings per format and source type. */
	public const DEFAULT_QUALITY = [
		'webp' => [
			'jpg' => 85,
			'png' => 85,
		],
		'avif' => [
			'jpg' => 75,
			'png' => 80,
		],
	];

	/**
	 * Cached engine instances (singleton pool per request).
	 *
	 * @var EngineInterface[]|null
	 */
	private static ?array $engines = null;

	/**
	 * Cached resolved engine per format.
	 *
	 * @var array<string, EngineInterface|false>
	 */
	private static array $resolvedCache = [];

	// ─── Engine Pool (Registry) ─────────────────────────

	/**
	 * Get all registered engine instances, sorted by priority.
	 *
	 * Adding a new engine? → Add one line here.
	 *
	 * @return EngineInterface[]
	 */
	public static function getEnginePool(): array {
		if ( self::$engines !== null ) {
			return self::$engines;
		}

		$pool = [
			new CliEngine(),
			new ImagickEngine(),
			new GdEngine(),
		];

		// Sort by priority (lower = preferred)
		usort( $pool, static fn( EngineInterface $a, EngineInterface $b ) => $a->priority() <=> $b->priority() );

		return self::$engines = $pool;
	}

	// ─── Engine Resolution ──────────────────────────────

	/**
	 * Resolve the best available engine for a given format.
	 *
	 * Walks the engine pool in priority order.
	 * Result is cached per format per request.
	 *
	 * @param string $format 'webp' or 'avif'.
	 *
	 * @return EngineInterface|null Resolved engine or null if none available.
	 */
	public static function resolveEngine( string $format ): ?EngineInterface {
		if ( isset( self::$resolvedCache[ $format ] ) ) {
			$cached = self::$resolvedCache[ $format ];

			return $cached instanceof EngineInterface ? $cached : null;
		}

		foreach ( self::getEnginePool() as $engine ) {
			if ( $engine->isAvailable() && $engine->supportsFormat( $format ) ) {
				self::$resolvedCache[ $format ] = $engine;

				return $engine;
			}
		}

		self::$resolvedCache[ $format ] = false;

		return null;
	}

	/**
	 * Legacy alias for resolveEngine (returns engine name or false).
	 *
	 * @param string $format 'webp' or 'avif'.
	 *
	 * @return string|false Engine name or false.
	 */
	public static function detectEngine( string $format ): string|false {
		$engine = self::resolveEngine( $format );

		return $engine?->name() ?? false;
	}

	/**
	 * Get detailed info about all engines and format support.
	 * Used by the admin UI to display engine status table.
	 *
	 * @return array{
	 *     active_engine_webp: string|false,
	 *     active_engine_avif: string|false,
	 *     engines: array<int, array{name: string, label: string, priority: int, available: bool, note: string, webp: bool, avif: bool}>,
	 *     formats: array<string, bool>
	 * }
	 */
	public static function getEngineInfo(): array {
		$engines = [];

		foreach ( self::getEnginePool() as $engine ) {
			$engineData = [
				'name'      => $engine->name(),
				'label'     => $engine->label(),
				'priority'  => $engine->priority(),
				'available' => $engine->isAvailable(),
				'note'      => $engine->statusNote(),
				'webp'      => $engine->supportsFormat( self::FORMAT_WEBP ),
				'avif'      => $engine->supportsFormat( self::FORMAT_AVIF ),
			];

			// CLI engine has per-binary detail
			if ( $engine instanceof CliEngine ) {
				$engineData['binaries'] = $engine->getBinaryInfo();
			}

			$engines[] = $engineData;
		}

		return [
			'active_engine_webp' => self::detectEngine( self::FORMAT_WEBP ),
			'active_engine_avif' => self::detectEngine( self::FORMAT_AVIF ),
			'engines'            => $engines,
			'formats'            => [
				'webp' => self::detectEngine( self::FORMAT_WEBP ) !== false,
				'avif' => self::detectEngine( self::FORMAT_AVIF ) !== false,
			],
		];
	}

	// ─── Conversion (Delegate to Engine) ────────────────

	/**
	 * Convert a single image to the specified format.
	 *
	 * Resolves the best engine, converts to a temp file, and
	 * auto-skips if output is larger than or equal to source.
	 *
	 * @param string $sourcePath Absolute path to source image.
	 * @param string $outputPath Absolute path for output file.
	 * @param string $format     'webp' or 'avif'.
	 * @param int    $quality    1-100.
	 *
	 * @return array{
	 *     success: bool,
	 *     source_size: int,
	 *     output_size: int,
	 *     saved_bytes: int,
	 *     saved_percent: float,
	 *     skipped: bool,
	 *     skip_reason: string|null,
	 *     engine: string|null,
	 *     error: string|null
	 * }
	 */
	public static function convert(
		string $sourcePath,
		string $outputPath,
		string $format = self::FORMAT_AVIF,
		int $quality = 65,
	): array {
		$result = self::emptyResult();

		// ── Validate source ─────────────────────────────
		if ( ! is_file( $sourcePath ) || ! is_readable( $sourcePath ) ) {
			$result['error'] = 'Source file not found or not readable.';

			return $result;
		}

		$sourceSize            = (int) filesize( $sourcePath );
		$result['source_size'] = $sourceSize;

		if ( $sourceSize === 0 ) {
			$result['error'] = 'Source file is empty.';

			return $result;
		}

		// ── Validate extension ──────────────────────────
		$ext = strtolower( pathinfo( $sourcePath, PATHINFO_EXTENSION ) );
		if ( ! \in_array( $ext, self::SUPPORTED_EXTENSIONS, true ) ) {
			$result['error']       = "Unsupported source format: {$ext}";
			$result['skipped']     = true;
			$result['skip_reason'] = 'unsupported_format';

			return $result;
		}

		// ── Skip animated GIFs (would lose animation) ──
		if ( $ext === 'gif' && self::isAnimatedGif( $sourcePath ) ) {
			$result['success']     = true;
			$result['skipped']     = true;
			$result['skip_reason'] = 'animated_gif';

			return $result;
		}

		// ── Skip images matching exclusion keywords ─────
		$excludeKeywords = ImageConverter::getExcludeKeywords();

		if ( ! empty( $excludeKeywords ) ) {
			$basename = basename( $sourcePath );

			foreach ( $excludeKeywords as $keyword ) {
				if ( stripos( $basename, $keyword ) !== false ) {
					$result['success']     = true;
					$result['skipped']     = true;
					$result['skip_reason'] = 'excluded_keyword';

					return $result;
				}
			}
		}

		// ── Resolve engine ──────────────────────────────
		$engine           = self::resolveEngine( $format );
		$result['engine'] = $engine?->name();

		if ( $engine === null ) {
			$result['error'] = "No engine available for {$format} conversion.";

			return $result;
		}

		// ── Bump server resources for large images ─────
		self::ensureResources();

		// ── Ensure output directory ─────────────────────
		$outputDir = \dirname( $outputPath );
		if ( ! is_dir( $outputDir ) && ! wp_mkdir_p( $outputDir ) ) {
			$result['error'] = "Failed to create output directory: {$outputDir}";

			return $result;
		}

		// ── Convert to temp file (atomicity) ────────────
		$tempOutput = $outputPath . '.tmp';

		try {
			$success = $engine->convert( $sourcePath, $tempOutput, $format, $quality );

			if ( ! $success || ! is_file( $tempOutput ) ) {
				self::cleanupTemp( $tempOutput );
				$result['error'] = "Conversion failed with engine: {$engine->name()}";

				return $result;
			}

			$outputSize = (int) filesize( $tempOutput );

			// Auto-skip: output ≥ source → not worth keeping
			if ( $outputSize >= $sourceSize ) {
				self::cleanupTemp( $tempOutput );

				$result['success']     = true;
				$result['skipped']     = true;
				$result['skip_reason'] = 'output_larger';
				$result['output_size'] = $outputSize;

				return $result;
			}

			// Output is smaller → keep it
			if ( ! rename( $tempOutput, $outputPath ) ) {
				self::cleanupTemp( $tempOutput );
				$result['error'] = 'Failed to move converted file to output path.';

				return $result;
			}

			$savedBytes   = $sourceSize - $outputSize;
			$savedPercent = $sourceSize > 0 ? round( ( $savedBytes / $sourceSize ) * 100, 1 ) : 0.0;

			$result['success']       = true;
			$result['output_size']   = $outputSize;
			$result['saved_bytes']   = $savedBytes;
			$result['saved_percent'] = $savedPercent;

			return $result;
		} catch ( \Throwable $e ) {
			self::cleanupTemp( $tempOutput );
			$result['error'] = $e->getMessage();

			return $result;
		}
	}

	// ─── Utility Methods ────────────────────────────────

	/**
	 * Check if a source file extension is supported.
	 *
	 * @param string $filePath File path or extension.
	 *
	 * @return bool
	 */
	public static function isSupportedFile( string $filePath ): bool {
		$ext = strtolower( pathinfo( $filePath, PATHINFO_EXTENSION ) );

		return \in_array( $ext, self::SUPPORTED_EXTENSIONS, true );
	}

	/**
	 * Get quality for a given format and source extension.
	 *
	 * @param string   $format        'webp' or 'avif'.
	 * @param string   $sourceExt     Source file extension.
	 * @param int|null $customQuality Custom quality override.
	 *
	 * @return int
	 */
	public static function getQuality( string $format, string $sourceExt, ?int $customQuality = null ): int {
		if ( $customQuality !== null ) {
			return max( 1, min( 100, $customQuality ) );
		}

		$sourceExt = strtolower( $sourceExt );

		// Normalize jpeg → jpg, gif → png
		$sourceExt = match ( $sourceExt ) {
			'jpeg'  => 'jpg',
			'gif'   => 'png',
			default => $sourceExt,
		};

		return self::DEFAULT_QUALITY[ $format ][ $sourceExt ] ?? 65;
	}

	// ─── Private Helpers ────────────────────────────────

	/**
	 * Create an empty result array.
	 *
	 * @return array
	 */
	private static function emptyResult(): array {
		return [
			'success'       => false,
			'source_size'   => 0,
			'output_size'   => 0,
			'saved_bytes'   => 0,
			'saved_percent' => 0.0,
			'skipped'       => false,
			'skip_reason'   => null,
			'engine'        => null,
			'error'         => null,
		];
	}

	/**
	 * Clean up temp file if it exists.
	 *
	 * @param string $tempPath Temp file path.
	 */
	private static function cleanupTemp( string $tempPath ): void {
		if ( is_file( $tempPath ) ) {
			@unlink( $tempPath );
		}
	}

	/**
	 * Detect if a GIF file is animated (has multiple frames).
	 *
	 * Reads the file in 100KB chunks scanning for GIF frame markers.
	 * Stops as soon as 2 frames are found (minimum for animation).
	 *
	 * @param string $filePath Absolute path to GIF file.
	 *
	 * @return bool
	 */
	private static function isAnimatedGif( string $filePath ): bool {
		$fh = @fopen( $filePath, 'rb' );
		if ( ! $fh ) {
			return false;
		}

		$count = 0;
		while ( ! feof( $fh ) && $count < 2 ) {
			$chunk  = fread( $fh, 1024 * 100 );
			$count += preg_match_all(
				'#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s',
				$chunk ?: '',
				$matches
			);
		}

		fclose( $fh );

		return $count > 1;
	}

	/**
	 * Ensure adequate memory and time for image processing.
	 *
	 * Memory: bumps to 512M if current is lower.
	 * Time: extends to 120s if set_time_limit() is available.
	 *
	 * @return void
	 */
	private static function ensureResources(): void {
		// Memory bump (only increase, never decrease)
		$currentLimit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$targetLimit  = 512 * 1024 * 1024; // 512M

		if ( $currentLimit > 0 && $currentLimit < $targetLimit ) {
			@ini_set( 'memory_limit', '512M' );
		}

		// Extend execution time (if not disabled)
		$disabled = ini_get( 'disable_functions' ) ?: '';
		if ( ! str_contains( $disabled, 'set_time_limit' ) ) {
			@set_time_limit( 120 );
		}
	}
}
