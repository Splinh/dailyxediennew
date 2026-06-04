<?php
/**
 * Imagick Engine — Uses PHP Imagick extension.
 *
 * Good quality, moderate memory usage. Available on most VPS
 * and many shared hosts with the Imagick extension enabled.
 *
 * Priority: 2
 *
 * @package HDAddons\Modules\ImageConverter\Engines
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter\Engines;

use HDAddons\Helper;
use HDAddons\Modules\ImageConverter\Contracts\EngineInterface;

\defined( 'ABSPATH' ) || exit;

final class ImagickEngine implements EngineInterface {

	/**
	 * Cached format support results.
	 *
	 * @var array<string, bool>
	 */
	private static array $formatCache = [];

	// ─── EngineInterface ────────────────────────────────

	public function name(): string {
		return 'imagick';
	}

	public function label(): string {
		return 'Imagick';
	}

	public function priority(): int {
		return 20;
	}

	public function isAvailable(): bool {
		return \extension_loaded( 'imagick' );
	}

	public function supportsFormat( string $format ): bool {
		if ( ! $this->isAvailable() ) {
			return false;
		}

		if ( isset( self::$formatCache[ $format ] ) ) {
			return self::$formatCache[ $format ];
		}

		try {
			$formats = \Imagick::queryFormats( strtoupper( $format ) );

			return self::$formatCache[ $format ] = ! empty( $formats );
		} catch ( \Throwable ) {
			return self::$formatCache[ $format ] = false;
		}
	}

	public function convert( string $sourcePath, string $outputPath, string $format, int $quality ): bool {
		try {
			$imagick = new \Imagick( $sourcePath );

			// Auto-orient (EXIF rotation) then strip metadata
			$imagick->autoOrientImage();
			$imagick->stripImage();

			// Set output format
			$imagick->setImageFormat( $format );
			$imagick->setImageCompressionQuality( $quality );

			// AVIF-specific: set encoding speed
			if ( $format === 'avif' ) {
				$imagick->setOption( 'heic:speed', '4' );
			}

			$success = $imagick->writeImage( $outputPath );

			$imagick->clear();
			$imagick->destroy();

			return $success;
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA ImageConverter] Imagick error: ' . $e->getMessage() );

			return false;
		}
	}

	public function statusNote(): string {
		if ( ! $this->isAvailable() ) {
			return 'Imagick extension not loaded';
		}

		$supported = [];
		foreach ( [ 'webp', 'avif' ] as $format ) {
			if ( $this->supportsFormat( $format ) ) {
				$supported[] = strtoupper( $format );
			}
		}

		return $supported
			? 'Formats: ' . implode( ', ', $supported )
			: 'Loaded, but no WebP/AVIF support';
	}
}
