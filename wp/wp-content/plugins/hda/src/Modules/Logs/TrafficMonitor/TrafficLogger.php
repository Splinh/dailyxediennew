<?php
/**
 * Traffic Logger — database storage for firewall events and traffic logs.
 *
 * Each row represents a single request event (blocked, challenged, logged, or allowed).
 * Provides CRUD operations, statistics, and cleanup functionality.
 *
 * @package HDAddons\Modules\Logs\TrafficMonitor
 * @author  HD
 */

namespace HDAddons\Modules\Logs\TrafficMonitor;

use HDAddons\DB;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class TrafficLogger {

	/**
	 * Database table name (without prefix).
	 */
	public const TABLE_NAME = 'hda_traffic_logs';

	/**
	 * Maximum logs to keep (hard cap prevents runaway growth).
	 */
	private const MAX_LOGS = 100_000;

	/**
	 * Table schema SQL.
	 */
	private const TABLE_SCHEMA = <<<'SQL'
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		ip VARCHAR(45) NOT NULL DEFAULT '',
		country VARCHAR(2) DEFAULT NULL,
		uri VARCHAR(2048) NOT NULL DEFAULT '',
		method VARCHAR(10) NOT NULL DEFAULT 'GET',
		user_agent VARCHAR(512) DEFAULT NULL,
		action VARCHAR(20) NOT NULL DEFAULT 'allowed',
		attack_type VARCHAR(30) DEFAULT NULL,
		rule_id VARCHAR(50) DEFAULT NULL,
		severity VARCHAR(10) DEFAULT NULL,
		matched TEXT DEFAULT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_ip (ip),
		KEY idx_action (action),
		KEY idx_created (created_at),
		KEY idx_attack_type (attack_type)
	SQL;

	/**
	 * Cached table existence flag.
	 *
	 * @var bool|null
	 */
	private static ?bool $tableExistsCache = null;

	// ══════════════════════════════════════════════════
	// Table management
	// ══════════════════════════════════════════════════

	/**
	 * Check if the table exists (cached per request).
	 *
	 * @return bool
	 */
	public static function tableExists(): bool {
		return self::$tableExistsCache ??= DB::tableExists( self::TABLE_NAME );
	}

	// ══════════════════════════════════════════════════
	// Logging
	// ══════════════════════════════════════════════════

	/**
	 * Log a traffic event.
	 *
	 * @param array{
	 *     ip: string,
	 *     uri: string,
	 *     method?: string,
	 *     user_agent?: string,
	 *     action?: string,
	 *     attack_type?: string,
	 *     rule_id?: string,
	 *     severity?: string,
	 *     matched?: string,
	 *     country?: string,
	 * } $data Event data.
	 *
	 * @return void
	 */
	public function log( array $data ): void {
		if ( ! self::tableExists() ) {
			return;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$row = [
			'ip'          => sanitize_text_field( $data['ip'] ?? '' ),
			'uri'         => sanitize_text_field( substr( $data['uri'] ?? '', 0, 2048 ) ),
			'method'      => sanitize_text_field( strtoupper( $data['method'] ?? 'GET' ) ),
			'user_agent'  => sanitize_text_field( substr( $data['user_agent'] ?? '', 0, 512 ) ),
			'action'      => sanitize_key( $data['action'] ?? 'allowed' ),
			'attack_type' => ! empty( $data['attack_type'] ) ? sanitize_key( $data['attack_type'] ) : null,
			'rule_id'     => ! empty( $data['rule_id'] ) ? sanitize_text_field( substr( $data['rule_id'], 0, 50 ) ) : null,
			'severity'    => ! empty( $data['severity'] ) ? sanitize_key( $data['severity'] ) : null,
			'matched'     => ! empty( $data['matched'] ) ? sanitize_text_field( substr( $data['matched'], 0, 5000 ) ) : null,
			'country'     => ! empty( $data['country'] ) ? sanitize_text_field( substr( $data['country'], 0, 2 ) ) : null,
			'created_at'  => current_time( 'mysql', true ),
		];

		$db->insert( $table, $row );
	}

	// ══════════════════════════════════════════════════
	// Querying
	// ══════════════════════════════════════════════════

	/**
	 * Get logs with pagination, filtering, and search.
	 *
	 * @param array{
	 *     per_page?: int,
	 *     page?: int,
	 *     orderby?: string,
	 *     order?: string,
	 *     search?: string,
	 *     action?: string,
	 *     attack_type?: string,
	 *     ip?: string,
	 * } $args Query arguments.
	 *
	 * @return array{items: array, total: int}
	 */
	public static function getLogs( array $args = [] ): array {
		$defaults = [
			'per_page'    => 30,
			'page'        => 1,
			'orderby'     => 'created_at',
			'order'       => 'DESC',
			'search'      => '',
			'action'      => '',
			'attack_type' => '',
			'ip'          => '',
		];

		$args  = wp_parse_args( $args, $defaults );
		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		// ── Build WHERE clause ──────────────────────
		$where  = [];
		$values = [];

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(uri LIKE %s OR ip LIKE %s OR rule_id LIKE %s)';
			$likeVal  = '%' . $db->esc_like( $args['search'] ) . '%';
			$values[] = $likeVal;
			$values[] = $likeVal;
			$values[] = $likeVal;
		}

		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$values[] = $args['action'];
		}

		if ( ! empty( $args['attack_type'] ) ) {
			$where[]  = 'attack_type = %s';
			$values[] = $args['attack_type'];
		}

		if ( ! empty( $args['ip'] ) ) {
			$where[]  = 'ip = %s';
			$values[] = $args['ip'];
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// ── Validate orderby ────────────────────────
		$allowed = [ 'id', 'ip', 'uri', 'action', 'attack_type', 'severity', 'created_at' ];
		$orderby = in_array( $args['orderby'], $allowed, true ) ? $args['orderby'] : 'created_at';
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// ── Count total ─────────────────────────────
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		$total     = $values
			? (int) $db->get_var( $db->prepare( $count_sql, ...$values ) )
			: (int) $db->get_var( $count_sql );

		// ── Fetch items ─────────────────────────────
		$offset    = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$limit     = (int) $args['per_page'];
		$items_sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$prepared = array_merge( $values, [ $limit, $offset ] );
		$items    = $db->get_results( $db->prepare( $items_sql, ...$prepared ), ARRAY_A );

		return [
			'items' => $items ?: [],
			'total' => $total,
		];
	}

	/**
	 * Get summary statistics for the dashboard.
	 *
	 * @param int $days Number of days to look back.
	 *
	 * @return array{total: int, blocked: int, logged: int, by_type: array, top_ips: array}
	 */
	public static function getStats( int $days = 7 ): array {
		if ( ! self::tableExists() ) {
			return [
				'total'   => 0,
				'blocked' => 0,
				'logged'  => 0,
				'by_type' => [],
				'top_ips' => [],
			];
		}

		$db     = DB::db();
		$table  = DB::tableNameFull( self::TABLE_NAME );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Totals in a single query (conditional COUNT).
		$summary = $db->get_row(
			$db->prepare(
				"SELECT COUNT(*) AS total, SUM(action = 'blocked') AS blocked, SUM(action = 'logged') AS logged FROM {$table} WHERE created_at >= %s",
				$cutoff
			),
			ARRAY_A
		);

		$total   = (int) ( $summary['total'] ?? 0 );
		$blocked = (int) ( $summary['blocked'] ?? 0 );
		$logged  = (int) ( $summary['logged'] ?? 0 );

		// By attack type.
		$byType = $db->get_results(
			$db->prepare(
				"SELECT attack_type, COUNT(*) AS cnt FROM {$table} WHERE attack_type IS NOT NULL AND created_at >= %s GROUP BY attack_type ORDER BY cnt DESC",
				$cutoff
			),
			ARRAY_A
		) ?: [];

		// Top blocked IPs.
		$topIps = $db->get_results(
			$db->prepare(
				"SELECT ip, COUNT(*) AS cnt FROM {$table} WHERE action IN ('blocked','logged') AND created_at >= %s GROUP BY ip ORDER BY cnt DESC LIMIT 10",
				$cutoff
			),
			ARRAY_A
		) ?: [];

		return [
			'total'   => $total,
			'blocked' => $blocked,
			'logged'  => $logged,
			'by_type' => $byType,
			'top_ips' => $topIps,
		];
	}

	// ══════════════════════════════════════════════════
	// Cleanup
	// ══════════════════════════════════════════════════

	/**
	 * Cleanup old log entries.
	 *
	 * @param int $days Delete entries older than this many days.
	 *
	 * @return int Number of deleted rows.
	 */
	public static function cleanup( int $days = 30 ): int {
		if ( ! self::tableExists() ) {
			return 0;
		}

		$db     = DB::db();
		$table  = DB::tableNameFull( self::TABLE_NAME );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Delete old entries.
		$deleted = (int) $db->query(
			$db->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff )
		);

		// Hard cap: trim if total exceeds MAX_LOGS.
		$total = (int) $db->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $total > self::MAX_LOGS ) {
			$excess = $total - self::MAX_LOGS;
			$db->query(
				$db->prepare( "DELETE FROM {$table} ORDER BY created_at ASC LIMIT %d", $excess )
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

		$db           = DB::db();
		$table        = DB::tableNameFull( self::TABLE_NAME );
		$ids          = array_filter( array_map( 'absint', $ids ) );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		return (int) $db->query( $db->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", ...$ids ) );
	}
}
