<?php
/**
 * Form Log Repository
 *
 * @package HD\Modules\Form\Repository
 */

namespace HD\Modules\Form\Repository;

use HD\Core\DB;

defined( 'ABSPATH' ) || exit;

class FormLogRepository {
	use JsonDecodeTrait;

	private const TABLE = 'hd_form_logs';

	/**
	 * Log a form event.
	 *
	 * @param int    $entryId   Entry ID.
	 * @param string $event     Event type.
	 * @param string $message   Log message.
	 * @param array  $context   Extra context.
	 * @param string $actor     Actor identifier.
	 * @param string $ipAddress Client IP.
	 *
	 * @return int|\WP_Error Insert ID on success.
	 */
	public function log( int $entryId, string $event, string $message, array $context = [], string $actor = 'system', string $ipAddress = '' ): int|\WP_Error {
		$data = [
			'entry_id'   => $entryId,
			'event'      => $event,
			'message'    => $message,
			'context'    => wp_json_encode( $context ),
			'actor'      => $actor,
			'ip_address' => $ipAddress,
			'created_at' => current_time( 'mysql' ),
		];

		return DB::insertOneRow( self::TABLE, $data );
	}

	/**
	 * Get logs for a specific entry.
	 *
	 * @param int $entryId Entry ID.
	 *
	 * @return array
	 */
	public function getByEntry( int $entryId ): array {
		$results = DB::getRows( self::TABLE, [ 'entry_id' => $entryId ], 1, 100, 'id', 'DESC' );

		if ( is_array( $results ) ) {
			foreach ( $results as &$result ) {
				$result['context'] = self::decodeJsonArray( $result['context'] ?? '', 'log.context.' . ( $result['id'] ?? 'unknown' ) );
			}
			unset( $result );
		}

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Get logs with pagination and filters.
	 *
	 * @param array $filters Filters.
	 * @param int   $page    Current page.
	 * @param int   $perPage Items per page.
	 *
	 * @return array
	 */
	public function findAll( array $filters = [], int $page = 1, int $perPage = 20 ): array {
		$where = [];

		if ( ! empty( $filters['event'] ) ) {
			$where['event'] = $filters['event'];
		}
		if ( ! empty( $filters['actor'] ) ) {
			$where['actor'] = $filters['actor'];
		}

		$results = DB::getRows( self::TABLE, $where, $page, $perPage, 'id', 'DESC' );

		if ( is_array( $results ) ) {
			foreach ( $results as &$result ) {
				$result['context'] = self::decodeJsonArray( $result['context'] ?? '', 'log.context.' . ( $result['id'] ?? 'unknown' ) );
			}
			unset( $result );
		}

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Count logs based on filters.
	 *
	 * @param array $filters Filters.
	 *
	 * @return int
	 */
	public function countAll( array $filters = [] ): int {
		$where = [];
		$args  = [];
		if ( ! empty( $filters['event'] ) ) {
			$where[] = '`event` = %s';
			$args[]  = $filters['event'];
		}
		if ( ! empty( $filters['actor'] ) ) {
			$where[] = '`actor` = %s';
			$args[]  = $filters['actor'];
		}

		$table    = DB::tableNameFull( self::TABLE );
		$whereSql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
		$sql      = "SELECT COUNT(*) FROM {$table} {$whereSql}";

		if ( $args ) {
			$sql = DB::db()->prepare( $sql, ...$args );
		}

		return (int) DB::db()->get_var( $sql );
	}
}
