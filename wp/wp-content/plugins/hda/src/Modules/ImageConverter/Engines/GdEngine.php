<?php
/**
 * GD Engine — Uses PHP GD extension.
 *
 * Acceptable quality, highest memory usage. Available on virtually
 * all servers as GD is bundled with PHP by default.
 *
 * Priority: 3 (least preferred, but most universally available)
 *
 * @package HDAddons\Modules\ImageConverter\Engines
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter\Engines;

use HDAddons\Helper;
use HDAddons\Modules\ImageConverter\Contracts\EngineInterface;

\defined( 'ABSPATH' ) || exit;

final class GdEngine implements EngineInterface {

	// ─── EngineInterface ────────────────────────────────

	public function name(): string {
		return 'gd';
	}

	public function label(): string {
		return 'GD';
	}

	public function priority(): int {
		return 30;
	}

	public function isAvailable(): bool {
		return \extension_loaded( 'gd' );
	}

	public function supportsFormat( string $format ): bool {
		if ( ! $this->isAvailable() ) {
			return false;
		}

		$info = gd_info();

		return match ( $format ) {
			'webp'  => ! empty( $info['WebP Support'] ),
			'avif'  => ! empty( $info['AVIF Support'] ) && $this->avifRuntimeTest(),
			default => false,
		};
	}

	/**
	 * Runtime test: actually try to encode a 1×1 pixel as AVIF.
	 *
	 * GD on Windows may report AVIF support but imageavif() hangs
	 * indefinitely due to missing or broken libavif codecs.
	 * This test catches that by enforcing a time limit.
	 *
	 * Result is cached in a transient for 1 hour.
	 *
	 * @return bool True if AVIF encoding actually works.
	 */
	private function avifRuntimeTest(): bool {
		$cached = get_transient( 'hda_gd_avif_test' );

		if ( $cached !== false ) {
			return $cached === 'ok';
		}

		// Check if set_time_limit is available.
		$disabled        = ini_get( 'disable_functions' ) ?: '';
		$canSetTimeLimit = ! str_contains( $disabled, 'set_time_limit' );

		// Set a short time limit for this test only.
		$prevLimit = (int) ini_get( 'max_execution_time' );
		if ( $canSetTimeLimit ) {
			@set_time_limit( 5 );
		}

		$img    = imagecreatetruecolor( 1, 1 );
		$result = false;

		if ( $img ) {
			// Try to encode to a temp file.
			$tmp = wp_tempnam( 'avif_test' );

			try {
				$result = @imageavif( $img, $tmp, 50 );
			} catch ( \Throwable ) {
				$result = false;
			}

			imagedestroy( $img );

			// Clean up.
			if ( is_file( $tmp ) ) {
				@unlink( $tmp );
			}
		}

		// Restore original time limit.
		if ( $canSetTimeLimit ) {
			@set_time_limit( $prevLimit );
		}

		set_transient( 'hda_gd_avif_test', $result ? 'ok' : 'fail', HOUR_IN_SECONDS );

		return $result;
	}

	public function convert( string $sourcePath, string $outputPath, string $format, int $quality ): bool {
		// ── Resolution check (prevent OOM on huge images) ──
		$imageSize = @getimagesize( $sourcePath );
		if ( $imageSize !== false && ( $imageSize[0] > 8192 || $imageSize[1] > 8192 ) ) {
			Helper::errorLog(
				sprintf(
					'[HDA ImageConverter] GD: skipped oversized image (%dx%d): %s',
					$imageSize[0],
					$imageSize[1],
					basename( $sourcePath )
				)
			);

			return false;
		}

		$ext = strtolower( pathinfo( $sourcePath, PATHINFO_EXTENSION ) );

		// Create GD image from source
		$image = match ( $ext ) {
			'jpg', 'jpeg' => @imagecreatefromjpeg( $sourcePath ),
			'png'         => @imagecreatefrompng( $sourcePath ),
			'gif'         => @imagecreatefromgif( $sourcePath ),
			default       => false,
		};

		if ( ! $image ) {
			Helper::errorLog( '[HDA ImageConverter] GD: failed to create image from source.' );

			return false;
		}

		// ── EXIF orientation (phone photos) ────────────
		if ( function_exists( 'exif_read_data' ) && in_array( $ext, [ 'jpg', 'jpeg' ], true ) ) {
			$exif = @exif_read_data( $sourcePath );
			if ( $exif && isset( $exif['Orientation'] ) ) {
				$image = match ( (int) $exif['Orientation'] ) {
					3       => imagerotate( $image, 180, 0 ),
					6       => imagerotate( $image, -90, 0 ),
					8       => imagerotate( $image, 90, 0 ),
					default => $image,
				};
			}
		}

		// Preserve transparency for PNG/GIF
		if ( \in_array( $ext, [ 'png', 'gif' ], true ) ) {
			imagepalettetotruecolor( $image );
			imagealphablending( $image, true );
			imagesavealpha( $image, true );
		}

		// Convert to target format
		// AVIF speed: 0 (slowest, best compression) to 10 (fastest, worst).
		// Speed 4 gives significantly better quality-per-byte than default (~6).
		$success = match ( $format ) {
			'webp'  => imagewebp( $image, $outputPath, $quality ),
			'avif'  => imageavif( $image, $outputPath, $quality, (int) apply_filters( 'hda_imgconv_avif_speed', 4 ) ),
			default => false,
		};

		imagedestroy( $image );

		return $success;
	}

	public function statusNote(): string {
		if ( ! $this->isAvailable() ) {
			return 'GD extension not loaded';
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
