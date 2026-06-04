<?php
/**
 * Batch Processor — Client-driven image conversion.
 *
 * Processing is driven entirely by the browser via AJAX:
 * JS calls the "process" endpoint in a sequential loop;
 * each call converts one chunk and returns updated stats.
 *
 * @package HDAddons\Modules\ImageConverter
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter;

use HDAddons\DB;
use HDAddons\Helper;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class BatchProcessor {

	/**
	 * Number of images to process per AJAX request.
	 */
	private const CHUNK_SIZE = 10;

	/**
	 * Option key to store the current batch state.
	 */
	public const BATCH_OPTION = 'hda_imgconv_active_batch';

	// ─── Init ───────────────────────────────────────────

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_ajax_hda_imgconv_start', self::ajaxStartBatch( ... ) );
		add_action( 'wp_ajax_hda_imgconv_process', self::ajaxProcessChunk( ... ) );
		add_action( 'wp_ajax_hda_imgconv_cancel', self::ajaxCancelBatch( ... ) );
		add_action( 'wp_ajax_hda_imgconv_cleanup', self::ajaxCleanup( ... ) );
	}

	// ─── AJAX: Start Batch ──────────────────────────────

	/**
	 * AJAX: Start a new batch conversion.
	 *
	 * @return void
	 */
	public static function ajaxStartBatch(): void {
		check_ajax_referer( 'hda_imgconv_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'hda' ) ] );
		}

		// Parse input
		$sourceDir    = sanitize_text_field( wp_unslash( $_POST['source_dir'] ?? '' ) );
		$format       = sanitize_text_field( wp_unslash( $_POST['format'] ?? 'avif' ) );
		$forceConvert = ! empty( $_POST['force'] );

		if ( empty( $sourceDir ) ) {
			wp_send_json_error( [ 'message' => __( 'Source directory is required.', 'hda' ) ] );
		}

		// Validate format
		if ( ! \in_array( $format, [ Converter::FORMAT_WEBP, Converter::FORMAT_AVIF ], true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid format.', 'hda' ) ] );
		}

		// Check engine availability
		$engine = Converter::detectEngine( $format );
		if ( $engine === false ) {
			wp_send_json_error(
				[
					'message' => sprintf(
						__( 'No conversion engine available for %s.', 'hda' ),
						strtoupper( $format )
					),
				]
			);
		}

		// Resolve paths
		$absSourceDir = self::resolveAbsolutePath( $sourceDir );
		if ( ! is_dir( $absSourceDir ) ) {
			wp_send_json_error( [ 'message' => __( 'Source directory does not exist.', 'hda' ) ] );
		}

		$outputDir = self::getOutputDir( $absSourceDir, $format );

		// Generate batch ID
		$batchId = wp_generate_uuid4();

		// Scan and enqueue (paginated, memory-safe)
		$skipExisting = ! $forceConvert;
		$total        = Queue::enqueueDirectory( $absSourceDir, $batchId, $format, $outputDir, $skipExisting );

		if ( $total === 0 ) {
			wp_send_json_error( [ 'message' => __( 'No convertible images found.', 'hda' ) ] );
		}

		// Save batch state
		$batchState = [
			'batch_id'   => $batchId,
			'source_dir' => $absSourceDir,
			'output_dir' => $outputDir,
			'format'     => $format,
			'total'      => $total,
			'engine'     => $engine,
			'started_at' => time(),
		];

		Helper::updateOption( self::BATCH_OPTION, $batchState, 0, false );

		wp_send_json_success(
			[
				'batch_id' => $batchId,
				'total'    => $total,
				'engine'   => $engine,
				'format'   => $format,
				'message'  => sprintf(
					__( 'Found %d images. Conversion started.', 'hda' ),
					$total
				),
			]
		);
	}

	// ─── AJAX: Process Chunk ────────────────────────────

	/**
	 * AJAX: Process the next chunk of images.
	 *
	 * Called by JS in a sequential loop.
	 *
	 * @return void
	 */
	public static function ajaxProcessChunk(): void {
		check_ajax_referer( 'hda_imgconv_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'hda' ) ] );
		}

		$batchState = Helper::getOption( self::BATCH_OPTION, [] );

		if ( empty( $batchState['batch_id'] ) ) {
			wp_send_json_success(
				[
					'active'  => false,
					'message' => __( 'No active batch.', 'hda' ),
				]
			);
		}

		$batchId   = $batchState['batch_id'];
		$sourceDir = $batchState['source_dir'];
		$outputDir = $batchState['output_dir'];
		$format    = $batchState['format'];

		// Check cancel flag
		if ( get_transient( "hda_imgconv_cancel_{$batchId}" ) ) {
			Helper::removeOption( self::BATCH_OPTION );
			delete_transient( "hda_imgconv_cancel_{$batchId}" );

			wp_send_json_success(
				[
					'active'  => false,
					'message' => __( 'Conversion cancelled.', 'hda' ),
				]
			);
		}

		// Reset any stuck 'processing' items
		Queue::resetStuckItems( $batchId );

		// Fetch next chunk
		$items = Queue::fetchPending( $batchId, self::CHUNK_SIZE );

		if ( empty( $items ) ) {
			// All done — clean up
			Helper::removeOption( self::BATCH_OPTION );
			CloudflareIntegration::onBatchComplete();
			wp_cache_flush();

			$stats = Queue::getStats( $batchId );

			wp_send_json_success( self::buildResponse( $batchState, $stats, false ) );
		}

		// Get quality settings
		$options    = ImageConverter::getOptions();
		$qualityJpg = (int) ( $options[ ImageConverter::KEY_QUALITY_JPG ] ?? 0 );
		$qualityPng = (int) ( $options[ ImageConverter::KEY_QUALITY_PNG ] ?? 0 );

		// Process each image in this chunk
		foreach ( $items as $item ) {
			if ( get_transient( "hda_imgconv_cancel_{$batchId}" ) ) {
				break;
			}

			$sourcePath = $sourceDir . '/' . $item->file_path;
			$outputPath = $outputDir . '/' . $item->file_path . '.' . $format;

			$ext     = strtolower( pathinfo( $item->file_path, PATHINFO_EXTENSION ) );
			$quality = Converter::getQuality(
				$format,
				$ext,
				\in_array( $ext, [ 'jpg', 'jpeg' ], true ) ? ( $qualityJpg ?: null ) : ( $qualityPng ?: null )
			);

			$result = Converter::convert( $sourcePath, $outputPath, $format, $quality );

			if ( $result['success'] && $result['skipped'] ) {
				Queue::markSkipped( (int) $item->id, $result['source_size'], $result['skip_reason'] ?? 'output_larger' );
			} elseif ( $result['success'] ) {
				Queue::markConverted( (int) $item->id, $result['source_size'], $result['output_size'] );
			} else {
				Queue::markError( (int) $item->id, $result['error'] ?? 'Unknown error' );
			}
		}

		// Get updated stats
		$stats  = Queue::getStats( $batchId );
		$active = ! $stats['done'];

		if ( ! $active ) {
			Helper::removeOption( self::BATCH_OPTION );
			CloudflareIntegration::onBatchComplete();
			wp_cache_flush();
		}

		wp_send_json_success( self::buildResponse( $batchState, $stats, $active ) );
	}

	// ─── AJAX: Cancel Batch ─────────────────────────────

	/**
	 * AJAX: Cancel the running batch.
	 *
	 * @return void
	 */
	public static function ajaxCancelBatch(): void {
		check_ajax_referer( 'hda_imgconv_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'hda' ) ] );
		}

		$batchState = Helper::getOption( self::BATCH_OPTION, [] );

		if ( empty( $batchState['batch_id'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No active batch to cancel.', 'hda' ) ] );
		}

		$batchId = $batchState['batch_id'];

		set_transient( "hda_imgconv_cancel_{$batchId}", true, HOUR_IN_SECONDS );

		$stats = Queue::getStats( $batchId );

		Queue::clearBatch( $batchId );
		Helper::removeOption( self::BATCH_OPTION );
		delete_transient( "hda_imgconv_cancel_{$batchId}" );

		wp_send_json_success(
			[
				'message' => sprintf(
					__( 'Batch cancelled. %1$d converted, %2$d skipped before cancellation.', 'hda' ),
					$stats['converted'],
					$stats['skipped']
				),
				'stats'   => $stats,
			]
		);
	}

	// ─── Response Builder ───────────────────────────────

	/**
	 * Build a standardized progress/complete response.
	 *
	 * @param array $batchState Batch state from options.
	 * @param array $stats      Queue stats.
	 * @param bool  $active     Whether the batch is still active.
	 *
	 * @return array Response data.
	 */
	private static function buildResponse( array $batchState, array $stats, bool $active ): array {
		$elapsed   = time() - ( $batchState['started_at'] ?? time() );
		$processed = $stats['converted'] + $stats['skipped'] + $stats['error'];
		$remaining = $stats['pending'] + $stats['processing'];

		$eta = 0;
		if ( $processed > 0 && $remaining > 0 ) {
			$avgTime = $elapsed / $processed;
			$eta     = (int) ceil( $avgTime * $remaining );
		}

		$response = [
			'active'     => $active,
			'batch_id'   => $batchState['batch_id'],
			'format'     => $batchState['format'] ?? '',
			'engine'     => $batchState['engine'] ?? '',
			'source_dir' => self::getRelativePath( $batchState['source_dir'] ?? '' ),
			'output_dir' => self::getRelativePath( $batchState['output_dir'] ?? '' ),
			'stats'      => $stats,
			'elapsed'    => $elapsed,
			'eta'        => $eta,
			'processed'  => $processed,
		];

		if ( ! $active ) {
			$response['message'] = sprintf(
				__( 'Conversion complete! %1$d converted, %2$d skipped, %3$d errors.', 'hda' ),
				$stats['converted'],
				$stats['skipped'],
				$stats['error']
			);
		}

		return $response;
	}

	// ─── Source Directories ─────────────────────────────

	/**
	 * Get available source directories with image counts.
	 *
	 * Returns uploads dir + optional 'images' dir at project root.
	 * Always shows total + already-converted counts so the UI is visible after conversion.
	 *
	 * @param string $format Target format.
	 *
	 * @return array[] Array of [ 'absolute', 'relative', 'total', 'converted', 'remaining' ].
	 */
	public static function getSourceDirectories( string $format ): array {
		$dirs = [];

		// 1. WordPress uploads directory (always available)
		$uploadDir   = wp_upload_dir();
		$uploadsPath = $uploadDir['basedir'];

		if ( is_dir( $uploadsPath ) ) {
			$dirs[] = self::buildDirInfo( $uploadsPath, $format );
		}

		// 2. 'images' directory at project root (same level as wp/)
		// Structure: project_root/wp/ and project_root/images/
		$projectRoot = dirname( ABSPATH );
		$imagesPath  = $projectRoot . '/images';

		if ( is_dir( $imagesPath ) ) {
			$dirs[] = self::buildDirInfo( $imagesPath, $format );
		}

		return $dirs;
	}

	/**
	 * Build directory info with image counts (recursive).
	 *
	 * @param string $absPath Absolute directory path.
	 * @param string $format  Target format.
	 *
	 * @return array Directory info.
	 */
	private static function buildDirInfo( string $absPath, string $format ): array {
		$outputDir = self::getOutputDir( $absPath, $format );
		$total     = 0;
		$converted = 0;

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator(
					$absPath,
					\FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
				),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			/** @var \SplFileInfo $fileInfo */
			foreach ( $iterator as $fileInfo ) {
				if ( $fileInfo->isDir() ) {
					continue;
				}

				if ( ! Converter::isSupportedFile( $fileInfo->getFilename() ) ) {
					continue;
				}

				if ( str_starts_with( $fileInfo->getFilename(), '.' ) ) {
					continue;
				}

				++$total;

				// Check if already converted
				$relativePath = ltrim(
					str_replace( $absPath, '', $fileInfo->getPathname() ),
					DIRECTORY_SEPARATOR . '/'
				);
				$relativePath = str_replace( '\\', '/', $relativePath );
				$outputPath   = $outputDir . '/' . $relativePath . '.' . $format;

				if ( is_file( $outputPath ) ) {
					++$converted;
				}
			}
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA ImgConv] buildDirInfo error: ' . $e->getMessage() );
		}

		return [
			'absolute'  => $absPath,
			'relative'  => self::getRelativePath( $absPath ),
			'total'     => $total,
			'converted' => $converted,
			'remaining' => $total - $converted,
		];
	}

	// ─── Helpers ────────────────────────────────────────

	/**
	 * Determine output directory path from source directory.
	 *
	 * @param string $sourceDir Absolute source directory path.
	 * @param string $format    Target format.
	 *
	 * @return string Absolute output directory path.
	 */
	public static function getOutputDir( string $sourceDir, string $format ): string {
		$sourceDir = rtrim( $sourceDir, '/\\' );

		return $sourceDir . '_' . $format;
	}

	/**
	 * Resolve a relative path (from wp-content) to absolute.
	 *
	 * @param string $relativePath Relative path (e.g., 'wp-content/uploads').
	 *
	 * @return string Absolute path.
	 */
	public static function resolveAbsolutePath( string $relativePath ): string {
		// If already absolute, return as-is
		if ( str_starts_with( $relativePath, ABSPATH ) ) {
			return rtrim( $relativePath, '/\\' );
		}

		// Check if path is relative to project root (for 'images' folder)
		$projectRoot = dirname( ABSPATH );
		$fromProject = $projectRoot . '/' . ltrim( $relativePath, '/\\' );
		if ( is_dir( $fromProject ) ) {
			return rtrim( $fromProject, '/\\' );
		}

		return rtrim( ABSPATH . ltrim( $relativePath, '/\\' ), '/\\' );
	}

	/**
	 * Get relative path from ABSPATH.
	 *
	 * @param string $absolutePath Absolute path.
	 *
	 * @return string Relative path.
	 */
	public static function getRelativePath( string $absolutePath ): string {
		// Try relative to project root first (for 'images' folder)
		$projectRoot = wp_normalize_path( dirname( ABSPATH ) );
		$normalized  = wp_normalize_path( $absolutePath );

		if ( str_starts_with( $normalized, $projectRoot ) ) {
			return ltrim( str_replace( $projectRoot, '', $normalized ), '/' );
		}

		return ltrim(
			str_replace(
				wp_normalize_path( ABSPATH ),
				'',
				$normalized
			),
			'/'
		);
	}

	// ─── AJAX: Cleanup ───────────────────────────────────

	/**
	 * AJAX: Delete all converted output directories and reset state.
	 *
	 * Removes: uploads_avif, uploads_webp, images_avif, images_webp,
	 * queue table, batch state, and server rewrite rules.
	 *
	 * @return void
	 */
	public static function ajaxCleanup(): void {
		check_ajax_referer( 'hda_imgconv_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'hda' ) ] );
		}

		$deleted    = [];
		$totalBytes = 0;

		// Find and delete all output directories
		$outputDirs = self::findOutputDirectories();

		foreach ( $outputDirs as $dir ) {
			if ( ! is_dir( $dir['path'] ) ) {
				continue;
			}

			$size = self::getDirectorySize( $dir['path'] );
			self::deleteDirectory( $dir['path'] );

			if ( ! is_dir( $dir['path'] ) ) {
				$deleted[]   = $dir['name'];
				$totalBytes += $size;
			}
		}

		// Drop the queue table
		if ( Queue::tableExists() ) {
			DB::dropTable( Queue::TABLE_NAME );
		}

		// Remove batch state
		Helper::removeOption( self::BATCH_OPTION );

		// Remove server rewrite rules
		ServerRules::apply( false );

		if ( empty( $deleted ) ) {
			wp_send_json_success(
				[
					'message' => __( 'Nothing to clean up — no output directories found.', 'hda' ),
					'deleted' => [],
				]
			);
		} else {
			wp_send_json_success(
				[
					'message' => sprintf(
						__( 'Cleaned up %1$d directories, freed %2$s.', 'hda' ),
						\count( $deleted ),
						size_format( $totalBytes )
					),
					'deleted' => $deleted,
					'freed'   => size_format( $totalBytes ),
				]
			);
		}
	}

	/**
	 * Find all output directories that exist.
	 *
	 * @return array[] Array of [ 'path' => string, 'name' => string ]
	 */
	private static function findOutputDirectories(): array {
		$dirs = [];

		// Uploads directories
		$uploadDir   = wp_upload_dir();
		$uploadsPath = $uploadDir['basedir'];

		foreach ( [ 'avif', 'webp' ] as $format ) {
			$outputPath = $uploadsPath . '_' . $format;
			if ( is_dir( $outputPath ) ) {
				$dirs[] = [
					'path' => $outputPath,
					'name' => basename( $outputPath ),
				];
			}
		}

		// Images directories (project root)
		$projectRoot = dirname( ABSPATH );
		foreach ( [ 'avif', 'webp' ] as $format ) {
			$outputPath = $projectRoot . '/images_' . $format;
			if ( is_dir( $outputPath ) ) {
				$dirs[] = [
					'path' => $outputPath,
					'name' => basename( $outputPath ),
				];
			}
		}

		return $dirs;
	}

	/**
	 * Get total size of a directory (recursive).
	 *
	 * @param string $dir Absolute directory path.
	 *
	 * @return int Total size in bytes.
	 */
	private static function getDirectorySize( string $dir ): int {
		$size = 0;

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator(
					$dir,
					\FilesystemIterator::SKIP_DOTS
				)
			);

			foreach ( $iterator as $file ) {
				if ( $file->isFile() ) {
					$size += $file->getSize();
				}
			}
		} catch ( \Throwable ) {
			// Ignore errors
		}

		return $size;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $dir Absolute directory path.
	 *
	 * @return void
	 */
	private static function deleteDirectory( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator(
					$dir,
					\FilesystemIterator::SKIP_DOTS
				),
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ( $iterator as $item ) {
				if ( $item->isDir() ) {
					@rmdir( $item->getPathname() );
				} else {
					@unlink( $item->getPathname() );
				}
			}

			@rmdir( $dir );
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA ImgConv] deleteDirectory error: ' . $e->getMessage() );
		}
	}
}
