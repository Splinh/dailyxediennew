<?php
/**
 * Auto Converter — Automatically converts images on media upload.
 *
 * Hooks into wp_generate_attachment_metadata to convert
 * uploaded images + all generated sizes to WebP/AVIF.
 *
 * @package HDAddons\Modules\ImageConverter
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class AutoConverter {

	/**
	 * Pending conversion items — collected during upload, processed at shutdown.
	 *
	 * @var array<int, array{baseDir: string, outputDir: string, file: string, format: string, qualityJpg: int, qualityPng: int}>
	 */
	private static array $pendingConversions = [];

	/**
	 * Initialize auto-conversion hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Hook AFTER WordPress generates all image sizes (priority 99)
		add_filter( 'wp_generate_attachment_metadata', self::onAttachmentGenerated( ... ), 99, 2 );

		// Also handle image deletion
		add_action( 'delete_attachment', self::onAttachmentDeleted( ... ) );
	}

	/**
	 * Collect image paths for deferred conversion.
	 *
	 * Instead of converting inline (which blocks the upload response),
	 * paths are collected and processed at shutdown for a faster UX.
	 *
	 * @param array $metadata      Attachment metadata.
	 * @param int   $attachmentId  Attachment post ID.
	 *
	 * @return array Unmodified metadata.
	 */
	public static function onAttachmentGenerated( array $metadata, int $attachmentId ): array {
		// Get module settings
		$options = ImageConverter::getOptions();
		$format  = $options[ ImageConverter::KEY_FORMAT ] ?? Converter::FORMAT_AVIF;

		// Get quality settings
		$qualityJpg = (int) ( $options[ ImageConverter::KEY_QUALITY_JPG ] ?? 0 );
		$qualityPng = (int) ( $options[ ImageConverter::KEY_QUALITY_PNG ] ?? 0 );

		// Check engine availability
		if ( Converter::detectEngine( $format ) === false ) {
			return $metadata;
		}

		// Get upload directory info
		$uploadDir = wp_upload_dir();
		$baseDir   = $uploadDir['basedir']; // e.g., /path/to/wp-content/uploads
		$outputDir = BatchProcessor::getOutputDir( $baseDir, $format );

		// Get the original file path
		$file = $metadata['file'] ?? '';
		if ( empty( $file ) ) {
			return $metadata;
		}

		// Collect original file for deferred conversion
		self::$pendingConversions[] = [
			'baseDir'    => $baseDir,
			'outputDir'  => $outputDir,
			'file'       => $file,
			'format'     => $format,
			'qualityJpg' => $qualityJpg,
			'qualityPng' => $qualityPng,
		];

		// Collect all generated sizes
		if ( ! empty( $metadata['sizes'] ) ) {
			$fileDir = \dirname( $file ); // e.g., 2026/03

			foreach ( $metadata['sizes'] as $sizeName => $sizeData ) {
				if ( empty( $sizeData['file'] ) ) {
					continue;
				}

				self::$pendingConversions[] = [
					'baseDir'    => $baseDir,
					'outputDir'  => $outputDir,
					'file'       => $fileDir . '/' . $sizeData['file'],
					'format'     => $format,
					'qualityJpg' => $qualityJpg,
					'qualityPng' => $qualityPng,
				];
			}
		}

		// Schedule deferred processing (idempotent — won't double-register)
		if ( \count( self::$pendingConversions ) >= 1 && ! has_action( 'shutdown', self::processDeferred( ... ) ) ) {
			add_action( 'shutdown', self::processDeferred( ... ), 0 );
		}

		return $metadata;
	}

	/**
	 * Delete converted files when attachment is deleted.
	 *
	 * @param int $attachmentId Attachment post ID.
	 *
	 * @return void
	 */
	public static function onAttachmentDeleted( int $attachmentId ): void {
		$metadata = wp_get_attachment_metadata( $attachmentId );

		if ( empty( $metadata['file'] ) ) {
			return;
		}

		$uploadDir = wp_upload_dir();
		$baseDir   = $uploadDir['basedir'];

		// Try both formats
		foreach ( [ Converter::FORMAT_WEBP, Converter::FORMAT_AVIF ] as $format ) {
			$outputDir = BatchProcessor::getOutputDir( $baseDir, $format );

			// Delete original converted file
			$convertedPath = $outputDir . '/' . $metadata['file'] . '.' . $format;
			if ( is_file( $convertedPath ) ) {
				@unlink( $convertedPath );
			}

			// Delete all sizes
			if ( ! empty( $metadata['sizes'] ) ) {
				$fileDir = \dirname( $metadata['file'] );

				foreach ( $metadata['sizes'] as $sizeData ) {
					if ( empty( $sizeData['file'] ) ) {
						continue;
					}

					$sizeConvertedPath = $outputDir . '/' . $fileDir . '/' . $sizeData['file'] . '.' . $format;
					if ( is_file( $sizeConvertedPath ) ) {
						@unlink( $sizeConvertedPath );
					}
				}
			}

			// Clean up empty directories
			self::cleanupEmptyDirs( $outputDir . '/' . \dirname( $metadata['file'] ) );
		}
	}

	// ─── Private Helpers ────────────────────────────────

	/**
	 * Process all pending conversions at shutdown.
	 *
	 * Runs after WordPress has sent the response to the browser,
	 * so the upload feels fast even for large images.
	 *
	 * @return void
	 */
	private static function processDeferred(): void {
		// Collect file paths for Cloudflare purge
		$filePaths = array_column( self::$pendingConversions, 'file' );

		foreach ( self::$pendingConversions as $item ) {
			self::convertSingleFile(
				$item['baseDir'],
				$item['outputDir'],
				$item['file'],
				$item['format'],
				$item['qualityJpg'],
				$item['qualityPng']
			);
		}

		self::$pendingConversions = [];

		// Purge converted image URLs from Cloudflare cache
		CloudflareIntegration::onAutoConvertComplete( $filePaths );
	}

	/**
	 * Convert a single file from uploads to the sibling directory.
	 *
	 * @param string $baseDir    Uploads base directory.
	 * @param string $outputDir  Output base directory (sibling).
	 * @param string $file       Relative file path (e.g., 2026/03/photo.jpg).
	 * @param string $format     Target format.
	 * @param int    $qualityJpg Quality for JPG sources (0 = use default).
	 * @param int    $qualityPng Quality for PNG sources (0 = use default).
	 *
	 * @return void
	 */
	private static function convertSingleFile(
		string $baseDir,
		string $outputDir,
		string $file,
		string $format,
		int $qualityJpg,
		int $qualityPng,
	): void {
		$sourcePath = $baseDir . '/' . $file;

		// Skip non-image files
		if ( ! Converter::isSupportedFile( $sourcePath ) ) {
			return;
		}

		$outputPath = $outputDir . '/' . $file . '.' . $format;

		// Skip if already converted
		if ( is_file( $outputPath ) ) {
			return;
		}

		// Determine quality
		$ext     = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		$quality = Converter::getQuality(
			$format,
			$ext,
			\in_array( $ext, [ 'jpg', 'jpeg' ], true ) ? ( $qualityJpg ?: null ) : ( $qualityPng ?: null )
		);

		$result = Converter::convert( $sourcePath, $outputPath, $format, $quality );

		if ( ! $result['success'] && ! $result['skipped'] ) {
			Helper::errorLog(
				sprintf(
					'[HDA ImageConverter] Auto-convert failed for %s: %s',
					$file,
					$result['error'] ?? 'Unknown error'
				)
			);
		}
	}

	/**
	 * Remove empty directories up the tree.
	 *
	 * @param string $dir Directory to check.
	 *
	 * @return void
	 */
	private static function cleanupEmptyDirs( string $dir ): void {
		while ( is_dir( $dir ) && \count( (array) @scandir( $dir ) ) <= 2 ) {
			@rmdir( $dir );
			$dir = \dirname( $dir );
		}
	}
}
