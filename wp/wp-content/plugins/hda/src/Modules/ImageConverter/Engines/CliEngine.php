<?php
/**
 * CLI Engine — Uses native cwebp/avifenc binaries.
 *
 * Highest quality, lowest memory usage (runs as separate OS process).
 * Ideal for VPS/dedicated servers. Unavailable on most shared hosts
 * where exec() is disabled.
 *
 * Priority: 1 (most preferred)
 *
 * @package HDAddons\Modules\ImageConverter\Engines
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter\Engines;

use HDAddons\Helper;
use HDAddons\Modules\ImageConverter\Contracts\EngineInterface;

\defined( 'ABSPATH' ) || exit;

final class CliEngine implements EngineInterface {

	/**
	 * Binary map: format → CLI binary name.
	 */
	private const BINARY_MAP = [
		'webp' => 'cwebp',
		'avif' => 'avifenc',
	];

	/**
	 * Cached binary availability results.
	 *
	 * @var array<string, bool>
	 */
	private static array $binaryCache = [];

	// ─── EngineInterface ────────────────────────────────

	public function name(): string {
		return 'cli';
	}

	public function label(): string {
		return 'CLI (cwebp / avifenc)';
	}

	public function priority(): int {
		return 10;
	}

	public function isAvailable(): bool {
		if ( ! \function_exists( 'exec' ) || self::isExecDisabled() ) {
			return false;
		}

		// Available if at least one binary is present
		foreach ( self::BINARY_MAP as $binary ) {
			if ( self::hasBinary( $binary ) ) {
				return true;
			}
		}

		return false;
	}

	public function supportsFormat( string $format ): bool {
		$binary = self::BINARY_MAP[ $format ] ?? null;

		return $binary !== null && self::hasBinary( $binary );
	}

	public function convert( string $sourcePath, string $outputPath, string $format, int $quality ): bool {
		$source = escapeshellarg( $sourcePath );
		$output = escapeshellarg( $outputPath );

		$cmd = match ( $format ) {
			'webp'  => sprintf( 'cwebp -q %d -m 6 -mt %s -o %s 2>&1', $quality, $source, $output ),
			'avif'  => sprintf( 'avifenc --min %d --max %d -s 4 -j all %s %s 2>&1', max( 0, $quality - 15 ), $quality, $source, $output ),
			default => null,
		};

		if ( $cmd === null ) {
			return false;
		}

		$exitCode = 0;
		exec( $cmd, $cmdOutput, $exitCode );

		if ( $exitCode !== 0 ) {
			Helper::errorLog(
				sprintf(
					'[HDA ImageConverter] CLI (%s) failed (exit %d): %s',
					$format,
					$exitCode,
					implode( "\n", $cmdOutput )
				)
			);

			return false;
		}

		return is_file( $outputPath );
	}

	public function statusNote(): string {
		if ( ! \function_exists( 'exec' ) || self::isExecDisabled() ) {
			return 'exec() disabled by hosting';
		}

		$available = [];

		foreach ( self::BINARY_MAP as $format => $binary ) {
			if ( self::hasBinary( $binary ) ) {
				$available[] = $binary;
			}
		}

		return $available
			? 'Available: ' . implode( ', ', $available )
			: 'No binaries found (cwebp, avifenc)';
	}

	// ─── Extended Info (for admin UI) ───────────────────

	/**
	 * Get per-binary availability info.
	 *
	 * @return array<string, array{available: bool, note: string}>
	 */
	public function getBinaryInfo(): array {
		$info = [];

		foreach ( self::BINARY_MAP as $format => $binary ) {
			$available = self::hasBinary( $binary );

			$info[ $binary ] = [
				'format'    => $format,
				'available' => $available,
				'note'      => $available
					? "{$binary} available"
					: ( ! \function_exists( 'exec' ) || self::isExecDisabled()
						? 'exec() disabled by hosting'
						: "{$binary} not installed" ),
			];
		}

		return $info;
	}

	// ─── Private Helpers ────────────────────────────────

	/**
	 * Check if a CLI binary is reachable.
	 */
	private static function hasBinary( string $binary ): bool {
		if ( isset( self::$binaryCache[ $binary ] ) ) {
			return self::$binaryCache[ $binary ];
		}

		if ( ! \function_exists( 'exec' ) || self::isExecDisabled() ) {
			return self::$binaryCache[ $binary ] = false;
		}

		$escaped = escapeshellarg( $binary );

		if ( PHP_OS_FAMILY === 'Windows' ) {
			exec( "where {$escaped} 2>NUL", $output, $exitCode );
		} else {
			exec( "command -v {$escaped} 2>/dev/null", $output, $exitCode );
		}

		return self::$binaryCache[ $binary ] = ( $exitCode === 0 && ! empty( $output ) );
	}

	/**
	 * Check if exec() is disabled in php.ini.
	 */
	private static function isExecDisabled(): bool {
		$disabled = ini_get( 'disable_functions' );

		if ( ! $disabled ) {
			return false;
		}

		return \in_array( 'exec', array_map( 'trim', explode( ',', strtolower( $disabled ) ) ), true );
	}
}
