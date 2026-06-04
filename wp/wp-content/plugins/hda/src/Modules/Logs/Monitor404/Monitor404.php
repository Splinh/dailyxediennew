<?php
/**
 * 404 Monitor — Data utility class for 404 logging.
 *
 * Constants, DB methods, logging, cleanup.
 * Module concerns (boot, settings) live in Monitor404Module.
 *
 * @package HDAddons\Modules\Logs\Monitor404
 */

namespace HDAddons\Modules\Logs\Monitor404;

use HDAddons\DB;
use HDAddons\Helper;
use HDAddons\Modules\Logs\LogsModule;

\defined( 'ABSPATH' ) || exit;

final class Monitor404 {

	/**
	 * Database table name (without prefix).
	 */
	public const TABLE_NAME = 'hda_404_log';

	// ─── Option Keys (single source of truth) ───────────
	public const SUB_KEY            = 'monitor_404';
	public const KEY_ENABLED        = 'm404_enabled';
	public const KEY_RETENTION_DAYS = 'm404_retention_days';

	/**
	 * Default log retention in days.
	 */
	private const DEFAULT_RETENTION_DAYS = 90;

	/**
	 * Maximum logs to keep.
	 */
	private const MAX_LOGS = 50000;

	/**
	 * Static assets extensions to skip.
	 */
	private const SKIP_EXTENSIONS = [
		'css',
		'js',
		'jpg',
		'jpeg',
		'png',
		'gif',
		'svg',
		'webp',
		'avif',
		'woff',
		'woff2',
		'ttf',
		'eot',
		'otf',
		'ico',
		'map',
		'xml',
		'txt',
		'json',
	];

	/**
	 * Cached options (avoids re-reading from DB in same request).
	 *
	 * @var array|null
	 */
	private ?array $cachedOptions = null;

	/**
	 * Cached table existence flag per request.
	 *
	 * @var bool|null
	 */
	private static ?bool $tableExistsCache = null;

	// ------------------------------------------------------
	// OPTIONS
	// ------------------------------------------------------

	/**
	 * Get module options (static, always from DB).
	 *
	 * @return array
	 */
	public static function getOptions(): array {
		return LogsModule::getSubOptions( self::SUB_KEY );
	}

	/**
	 * Get cached options for the current request.
	 *
	 * @return array
	 */
	private function getCachedOptions(): array {
		return $this->cachedOptions ??= self::getOptions();
	}

	public static function defaults(): array {
		return [
			self::KEY_ENABLED        => false,
			self::KEY_RETENTION_DAYS => self::DEFAULT_RETENTION_DAYS,
		];
	}

	public static function saveSettings( array $data ): void {
		$options = [
			self::KEY_ENABLED        => ! empty( $data[ self::KEY_ENABLED ] ),
			self::KEY_RETENTION_DAYS => isset( $data[ self::KEY_RETENTION_DAYS ] )
				? max( 7, min( 365, absint( $data[ self::KEY_RETENTION_DAYS ] ) ) )
				: self::DEFAULT_RETENTION_DAYS,
		];

		LogsModule::setSubOptions( self::SUB_KEY, $options );
	}

	// ------------------------------------------------------
	// DATABASE METHODS
	// ------------------------------------------------------

	/**
	 * Check if the table exists (cached per request).
	 *
	 * @return bool
	 */
	public static function tableExists(): bool {
		return self::$tableExistsCache ??= DB::tableExists( self::TABLE_NAME );
	}

	// ------------------------------------------------------
	// LOGGING
	// ------------------------------------------------------

	/**
	 * Log a 404 error on the frontend.
	 *
	 * @return void
	 */
	public function log404(): void {
		if ( ! is_404() ) {
			return;
		}

		$requestUri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

		if ( empty( $requestUri ) ) {
			return;
		}

		// Skip static assets
		$path      = wp_parse_url( $requestUri, PHP_URL_PATH ) ?: '';
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		if ( in_array( $extension, self::SKIP_EXTENSIONS, true ) ) {
			return;
		}

		$ip = Helper::ipAddress();

		$options = $this->getCachedOptions();
		if ( ! empty( $options[ self::KEY_ENABLED ] ) ) {
			$this->insertOrUpdate( $requestUri );
		}

		// ── Fire action for TrafficMonitor/Security to handle flood ──────────
		if ( '' !== $ip && '127.0.0.1' !== $ip && '::1' !== $ip ) {
			do_action( 'hda_404_error_event', $ip, $requestUri );
		}
	}

	/**
	 * Insert a new 404 record or increment hit_count if URL already exists.
	 *
	 * @param string $url The 404 URL.
	 *
	 * @return void
	 */
	private function insertOrUpdate( string $url ): void {
		if ( ! self::tableExists() ) {
			return;
		}

		$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ) );

		$ip = Helper::ipAddress();

		$userAgent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 )
			: '';

		$now      = gmdate( 'Y-m-d H:i:s' );
		$url_hash = md5( $url );

		DB::upsert(
			self::TABLE_NAME,
			[
				'url_hash'   => $url_hash,
				'url'        => $url,
				'referer'    => $referer,
				'ip_address' => $ip,
				'user_agent' => $userAgent,
				'hit_count'  => 1,
				'created_at' => $now,
				'updated_at' => $now,
			],
			[
				'hit_count'  => 'hit_count + 1',
				'referer'    => 'VALUES(referer)',
				'ip_address' => 'VALUES(ip_address)',
				'user_agent' => 'VALUES(user_agent)',
				'updated_at' => 'VALUES(updated_at)',
			]
		);
	}

	// ------------------------------------------------------
	// QUERY METHODS
	// ------------------------------------------------------

	/**
	 * Get logs with pagination and search.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array{items: array, total: int}
	 */
	public static function getLogs( array $args = [] ): array {
		$defaults = [
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'updated_at',
			'order'    => 'DESC',
			'search'   => '',
		];

		$args  = wp_parse_args( $args, $defaults );
		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		// Build WHERE clause
		$where  = [];
		$values = [];

		if ( ! empty( $args['search'] ) ) {
			$where[]     = '(url LIKE %s OR referer LIKE %s)';
			$search_term = '%' . $db->esc_like( $args['search'] ) . '%';
			$values[]    = $search_term;
			$values[]    = $search_term;
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// Validate orderby
		$allowed_orderby = [ 'id', 'url', 'hit_count', 'ip_address', 'created_at', 'updated_at' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'updated_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Get total count
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		if ( $values ) {
			$total = (int) $db->get_var( $db->prepare( $count_sql, ...$values ) );
		} else {
			$total = (int) $db->get_var( $count_sql );
		}

		// Get items
		$offset    = ( (int) $args['page'] - 1 ) * (int) $args['per_page'];
		$limit     = (int) $args['per_page'];
		$items_sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$prepared_values = array_merge( $values, [ $limit, $offset ] );
		$items           = $db->get_results( $db->prepare( $items_sql, ...$prepared_values ), ARRAY_A );

		return [
			'items' => $items ?: [],
			'total' => $total,
		];
	}

	// ------------------------------------------------------
	// CLEANUP METHODS
	// ------------------------------------------------------

	/**
	 * Cleanup old log entries.
	 *
	 * @return int Number of deleted rows.
	 */
	public static function cleanup(): int {
		if ( ! self::tableExists() ) {
			return 0;
		}

		$options       = self::getOptions();
		$retentionDays = ! empty( $options[ self::KEY_RETENTION_DAYS ] ) ? (int) $options[ self::KEY_RETENTION_DAYS ] : self::DEFAULT_RETENTION_DAYS;

		$db     = DB::db();
		$table  = DB::tableNameFull( self::TABLE_NAME );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $retentionDays . ' days' ) );

		// Delete old entries
		$deleted = (int) $db->query(
			$db->prepare( "DELETE FROM {$table} WHERE updated_at < %s", $cutoff )
		);

		// Limit total entries
		$total = (int) $db->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( $total > self::MAX_LOGS ) {
			$excess = $total - self::MAX_LOGS;

			$db->query(
				$db->prepare( "DELETE FROM {$table} ORDER BY updated_at ASC LIMIT %d", $excess )
			);
		}

		return $deleted;
	}

	/**
	 * Clear all logs.
	 *
	 * @return bool
	 */
	public static function clearAll(): bool {
		if ( ! self::tableExists() ) {
			return false;
		}

		return DB::truncateTable( self::TABLE_NAME );
	}

	/**
	 * Delete logs by IDs.
	 *
	 * @param array<int> $ids Log IDs to delete.
	 *
	 * @return int Number of deleted rows.
	 */
	public static function deleteByIds( array $ids ): int {
		if ( empty( $ids ) || ! self::tableExists() ) {
			return 0;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );
		$ids   = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		return (int) $db->query( $db->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", ...$ids ) );
	}
}
