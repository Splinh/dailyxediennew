<?php
/**
 * Engine Interface — Contract for image conversion engines.
 *
 * Strategy Pattern: each engine implements this interface providing
 * a unified API. The Converter (context) auto-selects the best
 * available engine based on priority.
 *
 * @package HDAddons\Modules\ImageConverter\Contracts
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter\Contracts;

\defined( 'ABSPATH' ) || exit;

interface EngineInterface {

	/**
	 * Unique engine identifier.
	 *
	 * @return string e.g., 'cli', 'imagick', 'gd'
	 */
	public function name(): string;

	/**
	 * Display label for admin UI.
	 *
	 * @return string e.g., 'CLI (cwebp/avifenc)', 'Imagick', 'GD'
	 */
	public function label(): string;

	/**
	 * Priority order (lower = preferred).
	 * Used by Converter to rank engines.
	 *
	 * @return int
	 */
	public function priority(): int;

	/**
	 * Whether this engine is available on the current server.
	 *
	 * @return bool
	 */
	public function isAvailable(): bool;

	/**
	 * Whether this engine supports converting to a specific format.
	 *
	 * @param string $format 'webp' or 'avif'.
	 *
	 * @return bool
	 */
	public function supportsFormat( string $format ): bool;

	/**
	 * Convert a single image.
	 *
	 * Implementations MUST:
	 * - Write output to $outputPath (parent dir already exists).
	 * - Strip EXIF metadata for smaller output.
	 * - Return true on success, false on failure.
	 *
	 * @param string $sourcePath Absolute path to source image.
	 * @param string $outputPath Absolute path for output file.
	 * @param string $format     'webp' or 'avif'.
	 * @param int    $quality    1-100.
	 *
	 * @return bool
	 */
	public function convert( string $sourcePath, string $outputPath, string $format, int $quality ): bool;

	/**
	 * Detailed note about engine status (for admin UI).
	 *
	 * @return string e.g., 'exec() disabled by hosting', 'Extension loaded'
	 */
	public function statusNote(): string;
}
