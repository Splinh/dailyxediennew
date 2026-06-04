<?php
/**
 * File Module — Upload limits, SVG support, and File Integrity scanning.
 *
 * Coordinates sub-modules: SVG, FileIntegrity, FileIntegrityAdmin.
 *
 * @package HDAddons\Modules\File
 */

namespace HDAddons\Modules\File;

use HDAddons\Contracts\HasSettings;
use HDAddons\Modules\AbstractModule;
use HDAddons\Modules\File\FileIntegrity\FileIntegrity;
use HDAddons\Modules\File\FileIntegrity\FileIntegrityAdmin;

defined( 'ABSPATH' ) || exit;

final class FileModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'file';
	}

	public static function title(): string {
		return 'File';
	}

	public static function description(): string {
		return 'Upload limits, SVG, integrity, and malware scan.';
	}

	public static function group(): string {
		return 'performance';
	}

	// ── Option Keys ─────────────────────────────────

	public const KEY_UPLOAD_SIZE_LIMIT = 'upload_size_limit';
	public const KEY_SVGS              = 'svgs';



	public static function cronHooks(): array {
		return [ 'hda_file_integrity_scan' ];
	}

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		$options = self::getCachedOptions();

		// Initialize SVG support module (only when not disabled).
		if ( 'disable' !== ( $options[ self::KEY_SVGS ] ?? 'disable' ) ) {
			new SVG();
		}

		add_action( 'init', $this->initFilters( ... ), 99 );

		// Sub-module: File Integrity Scanner.
		// Admin page (manual scans) is always available.
		if ( is_admin() ) {
			new FileIntegrityAdmin();
		}

		// Automated cron scanning only when enabled.
		$integrityOptions = self::getSubOptions( FileIntegrity::SUB_KEY );
		if ( ! empty( $integrityOptions[ FileIntegrity::KEY_ENABLED ] ) ) {
			new FileIntegrity();
		}
	}

	// ── Filters ─────────────────────────────────────

	/**
	 * Register file-related filters.
	 */
	public function initFilters(): void {
		add_filter( 'upload_size_limit', $this->customUploadSizeLimit( ... ) );
	}

	/**
	 * Override the maximum upload size limit.
	 *
	 * @param int $size Current upload size limit in bytes.
	 *
	 * @return int Modified upload size limit.
	 */
	public function customUploadSizeLimit( int $size ): int {
		$options         = self::getCachedOptions();
		$uploadSizeLimit = (int) ( $options[ self::KEY_UPLOAD_SIZE_LIMIT ] ?? 0 );

		if ( $uploadSizeLimit > 0 ) {
			return $uploadSizeLimit * 1024 * 1024; // Convert MB to bytes
		}

		return $size;
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		$fields  = [ self::KEY_UPLOAD_SIZE_LIMIT, self::KEY_SVGS ];
		$options = self::extractFields( $data, $fields, true );
		self::saveOrRemove( self::optionKey(), $options );

		// Delegate to FileIntegrity sub-module.
		if ( isset( $data[ FileIntegrity::SUB_KEY ] ) ) {
			FileIntegrity::saveSettings( (array) $data[ FileIntegrity::SUB_KEY ] );
		}
	}
}
