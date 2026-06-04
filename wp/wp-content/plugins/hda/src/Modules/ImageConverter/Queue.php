<?php
/**
 * Image Converter Queue — DB-backed job queue for batch processing.
 *
 * Designed to handle millions of images without memory issues.
 * Uses paginated directory scanning and batch INSERT for enqueuing.
 *
 * @package HDAddons\Modules\ImageConverter
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter;

use HDAddons\DB;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class Queue {

	/**
	 * Database table name (without prefix).
	 */
	public const TABLE_NAME = 'hda_imgconv_queue';

	/**
	 * Number of files to INSERT per batch during directory scan.
	 */
	private const SCAN_BATCH_SIZE = 500;

	/**
	 * Table schema SQL.
	 */
	private const TABLE_SCHEMA = <<<'SQL'
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		batch_id VARCHAR(36) NOT NULL DEFAULT '',
		file_path VARCHAR(500) NOT NULL DEFAULT '',
		status VARCHAR(20) NOT NULL DEFAULT 'pending',
		source_size INT UNSIGNED DEFAULT NULL,
		output_size INT UNSIGNED DEFAULT NULL,
		error_msg VARCHAR(255) DEFAULT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT NULL,
		PRIMARY KEY (id),
		KEY idx_batch_status (batch_id, status),
		KEY idx_status (status)
	SQL;

	/**
	 * Cached table existence flag.
	 *
	 * @var bool|null
	 */
	private static ?bool $tableExistsCache = null;

	// ─── Table Management ───────────────────────────────

	/**
	 * Check if the table exists (cached per request).
	 *
	 * @return bool
	 */
	public static function tableExists(): bool {
		return self::$tableExistsCache ??= DB::tableExists( self::TABLE_NAME );
	}

	// ─── Enqueue ────────────────────────────────────────

	/**
	 * Scan a directory recursively and enqueue all convertible images.
	 *
	 * Uses RecursiveDirectoryIterator for O(1) memory per file.
	 * Files are batch-inserted in chunks to avoid memory spikes.
	 *
	 * @param string $sourceDir    Absolute path to source directory.
	 * @param string $batchId      Unique batch identifier.
	 * @param string $format       Target format ('webp' or 'avif').
	 * @param string $outputDir    Absolute path to output directory.
	 * @param bool   $skipExisting Skip files that already have output (default true).
	 *
	 * @return int Total number of files enqueued.
	 */
	public static function enqueueDirectory(
		string $sourceDir,
		string $batchId,
		string $format,
		string $outputDir,
		bool $skipExisting = true,
	): int {
		if ( ! self::tableExists() || ! is_dir( $sourceDir ) ) {
			return 0;
		}

		$totalEnqueued = 0;
		$buffer        = [];

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator(
					$sourceDir,
					\FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
				),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			/** @var \SplFileInfo $fileInfo */
			foreach ( $iterator as $fileInfo ) {
				// Skip directories
				if ( $fileInfo->isDir() ) {
					continue;
				}

				// Skip non-image files
				if ( ! Converter::isSupportedFile( $fileInfo->getFilename() ) ) {
					continue;
				}

				// Skip hidden files
				if ( str_starts_with( $fileInfo->getFilename(), '.' ) ) {
					continue;
				}

				// Relative path from source directory
				$relativePath = ltrim(
					str_replace( $sourceDir, '', $fileInfo->getPathname() ),
					DIRECTORY_SEPARATOR . '/'
				);

				// Normalize to forward slashes
				$relativePath = str_replace( '\\', '/', $relativePath );

				// Skip if output already exists (unless force mode)
				if ( $skipExisting ) {
					$outputPath = $outputDir . '/' . $relativePath . '.' . $format;
					if ( is_file( $outputPath ) ) {
						continue;
					}
				}

				$buffer[] = [
					'batch_id'  => $batchId,
					'file_path' => $relativePath,
					'status'    => 'pending',
				];

				// Flush buffer when it reaches batch size
				if ( \count( $buffer ) >= self::SCAN_BATCH_SIZE ) {
					$inserted = DB::bulkInsertRows( self::TABLE_NAME, $buffer );

					if ( ! is_wp_error( $inserted ) ) {
						$totalEnqueued += $inserted;
					}

					$buffer = [];
				}
			}

			// Flush remaining buffer
			if ( ! empty( $buffer ) ) {
				$inserted = DB::bulkInsertRows( self::TABLE_NAME, $buffer );

				if ( ! is_wp_error( $inserted ) ) {
					$totalEnqueued += $inserted;
				}
			}
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA ImageConverter] Scan error: ' . $e->getMessage() );
		}

		return $totalEnqueued;
	}

	// ─── Fetch & Update ─────────────────────────────────

	/**
	 * Fetch the next N pending items for processing.
	 *
	 * Uses SELECT ... FOR UPDATE pattern for concurrency safety.
	 *
	 * @param string $batchId Batch identifier.
	 * @param int    $limit   Number of items to fetch.
	 *
	 * @return array<object{id: int, file_path: string}>
	 */
	public static function fetchPending( string $batchId, int $limit = 10 ): array {
		if ( ! self::tableExists() ) {
			return [];
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		// Mark items as 'processing' to prevent double-processing
		$items = $db->get_results(
			$db->prepare(
				"SELECT id, file_path FROM {$table}
			 WHERE batch_id = %s AND status = 'pending'
			 ORDER BY id ASC
			 LIMIT %d",
				$batchId,
				$limit
			)
		);

		if ( empty( $items ) ) {
			return [];
		}

		// Mark as processing
		$ids          = array_map( static fn( $item ) => (int) $item->id, $items );
		$placeholders = implode( ',', array_fill( 0, \count( $ids ), '%d' ) );

		$db->query(
			$db->prepare(
				"UPDATE {$table} SET status = 'processing', updated_at = %s WHERE id IN ({$placeholders})",
				gmdate( 'Y-m-d H:i:s' ),
				...$ids,
			)
		);

		return $items;
	}

	/**
	 * Mark an item as successfully converted.
	 *
	 * @param int $id         Queue item ID.
	 * @param int $sourceSize Source file size in bytes.
	 * @param int $outputSize Output file size in bytes.
	 *
	 * @return void
	 */
	public static function markConverted( int $id, int $sourceSize, int $outputSize ): void {
		self::updateStatus( $id, 'converted', $sourceSize, $outputSize );
	}

	/**
	 * Mark an item as skipped (output ≥ source or unsupported).
	 *
	 * @param int    $id         Queue item ID.
	 * @param int    $sourceSize Source file size in bytes.
	 * @param string $reason     Skip reason.
	 *
	 * @return void
	 */
	public static function markSkipped( int $id, int $sourceSize = 0, string $reason = '' ): void {
		self::updateStatus( $id, 'skipped', $sourceSize, errorMsg: $reason );
	}

	/**
	 * Mark an item as failed.
	 *
	 * @param int    $id    Queue item ID.
	 * @param string $error Error message.
	 *
	 * @return void
	 */
	public static function markError( int $id, string $error ): void {
		self::updateStatus( $id, 'error', errorMsg: substr( $error, 0, 255 ) );
	}

	// ─── Progress Tracking ──────────────────────────────

	/**
	 * Get batch progress stats.
	 *
	 * Single query with GROUP BY — fast O(1) with index.
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return array{
	 *     total: int,
	 *     pending: int,
	 *     processing: int,
	 *     converted: int,
	 *     skipped: int,
	 *     error: int,
	 *     total_source_bytes: int,
	 *     total_output_bytes: int,
	 *     saved_bytes: int,
	 *     done: bool
	 * }
	 */
	public static function getStats( string $batchId ): array {
		$stats = [
			'total'              => 0,
			'pending'            => 0,
			'processing'         => 0,
			'converted'          => 0,
			'skipped'            => 0,
			'error'              => 0,
			'total_source_bytes' => 0,
			'total_output_bytes' => 0,
			'saved_bytes'        => 0,
			'done'               => false,
		];

		if ( ! self::tableExists() ) {
			return $stats;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$rows = $db->get_results(
			$db->prepare(
				"SELECT status,
			        COUNT(*) AS cnt,
			        COALESCE(SUM(source_size), 0) AS total_source,
			        COALESCE(SUM(output_size), 0) AS total_output
			 FROM {$table}
			 WHERE batch_id = %s
			 GROUP BY status",
				$batchId,
			)
		);

		if ( empty( $rows ) ) {
			return $stats;
		}

		foreach ( $rows as $row ) {
			$count  = (int) $row->cnt;
			$status = $row->status;

			$stats['total'] += $count;

			if ( isset( $stats[ $status ] ) ) {
				$stats[ $status ] = $count;
			}

			$stats['total_source_bytes'] += (int) $row->total_source;
			$stats['total_output_bytes'] += (int) $row->total_output;
		}

		$stats['saved_bytes'] = $stats['total_source_bytes'] - $stats['total_output_bytes'];
		$stats['done']        = ( $stats['pending'] + $stats['processing'] ) === 0 && $stats['total'] > 0;

		return $stats;
	}

	/**
	 * Check if a batch has an active (running or pending) job.
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return bool
	 */
	public static function isActive( string $batchId ): bool {
		if ( ! self::tableExists() ) {
			return false;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$count = (int) $db->get_var(
			$db->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE batch_id = %s AND status IN ('pending', 'processing')",
				$batchId,
			)
		);

		return $count > 0;
	}

	// ─── Batch Management ───────────────────────────────

	/**
	 * Clear all records for a specific batch.
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return int Number of deleted rows.
	 */
	public static function clearBatch( string $batchId ): int {
		if ( ! self::tableExists() ) {
			return 0;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		return (int) $db->query(
			$db->prepare(
				"DELETE FROM {$table} WHERE batch_id = %s",
				$batchId,
			)
		);
	}

	/**
	 * Get the latest batch ID (most recent).
	 *
	 * @return string|null
	 */
	public static function getLatestBatchId(): ?string {
		if ( ! self::tableExists() ) {
			return null;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		return $db->get_var(
			"SELECT batch_id FROM {$table} ORDER BY id DESC LIMIT 1"
		);
	}

	/**
	 * Reset 'processing' items back to 'pending' (recovery from crashes).
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return int Number of reset rows.
	 */
	public static function resetStuckItems( string $batchId ): int {
		if ( ! self::tableExists() ) {
			return 0;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		return (int) $db->query(
			$db->prepare(
				"UPDATE {$table} SET status = 'pending', updated_at = %s
			 WHERE batch_id = %s AND status = 'processing'",
				gmdate( 'Y-m-d H:i:s' ),
				$batchId,
			)
		);
	}

	/**
	 * Clear all queue data (all batches).
	 *
	 * @return bool
	 */
	public static function clearAll(): bool {
		if ( ! self::tableExists() ) {
			return false;
		}

		return DB::truncateTable( self::TABLE_NAME );
	}

	// ─── Private Helpers ────────────────────────────────

	/**
	 * Update item status with optional sizes and error message.
	 *
	 * @param int         $id         Queue item ID.
	 * @param string      $status     New status.
	 * @param int|null    $sourceSize Source file size.
	 * @param int|null    $outputSize Output file size.
	 * @param string|null $errorMsg   Error or skip reason.
	 *
	 * @return void
	 */
	private static function updateStatus(
		int $id,
		string $status,
		?int $sourceSize = null,
		?int $outputSize = null,
		?string $errorMsg = null,
	): void {
		if ( ! self::tableExists() ) {
			return;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$data   = [
			'status'     => $status,
			'updated_at' => gmdate( 'Y-m-d H:i:s' ),
		];
		$format = [ '%s', '%s' ];

		if ( $sourceSize !== null ) {
			$data['source_size'] = $sourceSize;
			$format[]            = '%d';
		}

		if ( $outputSize !== null ) {
			$data['output_size'] = $outputSize;
			$format[]            = '%d';
		}

		if ( $errorMsg !== null ) {
			$data['error_msg'] = $errorMsg;
			$format[]          = '%s';
		}

		$db->update( $table, $data, [ 'id' => $id ], $format, [ '%d' ] );
	}
}
