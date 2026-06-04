<?php
/**
 * ImageConverter Module — Convert images to WebP/AVIF with batch processing.
 *
 * Coordinates: AutoConverter, BatchProcessor, ServerRules, CloudflareIntegration.
 *
 * @package HDAddons\Modules\ImageConverter
 */

namespace HDAddons\Modules\ImageConverter;

use HDAddons\Contracts\HasDatabaseSchema;
use HDAddons\Contracts\HasSettings;
use HDAddons\Modules\ImageConverter\Converter;
use HDAddons\Modules\ImageConverter\ImageConverter;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class ImageConverterModule extends AbstractModule implements HasSettings, HasDatabaseSchema {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'image_converter';
	}

	public static function title(): string {
		return 'Image Converter';
	}

	public static function description(): string {
		return 'Convert images to WebP/AVIF with batch processing.';
	}

	public static function group(): string {
		return 'performance';
	}


	public static function defaults(): array {
		return [
			ImageConverter::KEY_FORMAT       => Converter::FORMAT_AVIF,
			ImageConverter::KEY_QUALITY_JPG  => 65,
			ImageConverter::KEY_QUALITY_PNG  => 70,
			ImageConverter::KEY_AUTO_CONVERT => false,
			ImageConverter::KEY_SERVER_RULES => false,
		];
	}


	// ── HasDatabaseSchema ──────────────────────────

	/** @inheritDoc */
	public static function databaseSchemas(): array {
		return [
			Queue::TABLE_NAME => <<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			batch_id varchar(36) NOT NULL DEFAULT '',
			file_path varchar(500) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'pending',
			source_size int unsigned DEFAULT NULL,
			output_size int unsigned DEFAULT NULL,
			error_msg varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_batch_status (batch_id, status),
			KEY idx_status (status)
			SQL,
		];
	}

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		// Delegate to existing ImageConverter constructor logic.
		new ImageConverter();
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		ImageConverter::saveSettings( $data );
	}
}
