<?php
/**
 * Form Entry Repository
 *
 * @package HD\Modules\Form\Repository
 */

namespace HD\Modules\Form\Repository;

use HD\Core\DB;
use HD\Modules\Form\DTO\FormEntry;
use HD\Modules\Form\Enum\FormEntryStatus;

defined( 'ABSPATH' ) || exit;

class FormEntryRepository {
	use JsonDecodeTrait;

	private const TABLE = 'hd_form_entries';

	/**
	 * Insert a new form entry.
	 *
	 * @param FormEntry $entry The form entry DTO.
	 *
	 * @return int|\WP_Error Insert ID on success.
	 */
	public function insert( FormEntry $entry ): int|\WP_Error {
		$data = [
			'form_type'      => $entry->formType,
			'form_id'        => $entry->formId,
			'status'         => FormEntryStatus::New->value,
			'name'           => $entry->name,
			'email'          => $entry->email,
			'phone'          => $entry->phone,
			'phone_country'  => $entry->phoneCountry,
			'phone_national' => $entry->phoneNational,
			'ip_address'     => $entry->ipAddress,
			'user_agent'     => $entry->userAgent,
			'referer_url'    => $entry->refererUrl,
			'page_url'       => $entry->pageUrl,
			'utm_source'     => $entry->utmSource,
			'utm_medium'     => $entry->utmMedium,
			'utm_campaign'   => $entry->utmCampaign,
			'utm_term'       => $entry->utmTerm,
			'utm_content'    => $entry->utmContent,
			'data'           => wp_json_encode( $entry->data ),
			'user_id'        => $entry->userId,
			'created_at'     => current_time( 'mysql' ),
		];

		if ( '' !== $entry->submissionHash ) {
			$data['submission_hash'] = $entry->submissionHash;
		}

		$result = DB::insertOneRow( self::TABLE, $data );
		if ( is_wp_error( $result ) && $this->isDuplicateSubmissionError( $result ) ) {
			return new \WP_Error( 'duplicate_submission', __( 'Duplicate submission.', 'hd' ) );
		}

		return $result;
	}

	/**
	 * Find an entry by ID.
	 *
	 * @param int $id Entry ID.
	 *
	 * @return array|null
	 */
	public function findById( int $id ): ?array {
		$result = DB::getOneWhere( self::TABLE, [ 'id' => $id ] );
		if ( is_wp_error( $result ) ) {
			return null;
		}

		if ( $result && isset( $result['data'] ) ) {
			$result['data'] = self::decodeJsonArray( $result['data'], 'entry.data.' . $id );
		}

		return $result;
	}

	/**
	 * Get entries with pagination and filters.
	 *
	 * @param array  $filters Available: 'status', 'form_type'.
	 * @param int    $page    Current page.
	 * @param int    $perPage Items per page.
	 * @param string $orderBy Column to order by.
	 * @param string $order   ASC or DESC.
	 *
	 * @return array|\WP_Error
	 */
	public function findAll( array $filters = [], int $page = 1, int $perPage = 20, string $orderBy = 'id', string $order = 'DESC' ): array|\WP_Error {
		$table               = DB::tableNameFull( self::TABLE );
		$page                = max( 1, $page );
		$perPage             = max( 1, min( 500, $perPage ) );
		[ $whereSql, $args ] = self::buildListWhere( $filters );

		// Whitelist sortable columns.
		$allowed = [ 'id', 'name', 'email', 'created_at', 'form_type', 'status' ];
		$orderBy = in_array( $orderBy, $allowed, true ) ? $orderBy : 'id';
		$order   = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';

		$offset = ( $page - 1 ) * $perPage;

		$sql    = "SELECT * FROM {$table} {$whereSql} ORDER BY `{$orderBy}` {$order} LIMIT %d OFFSET %d";
		$args[] = $perPage;
		$args[] = $offset;

		$prepared = DB::db()->prepare( $sql, ...$args );
		$results  = DB::db()->get_results( $prepared, ARRAY_A );

		if ( is_array( $results ) ) {
			foreach ( $results as &$result ) {
				if ( isset( $result['data'] ) ) {
					$result['data'] = self::decodeJsonArray( $result['data'], 'entry.data.' . ( $result['id'] ?? 'unknown' ) );
				}
			}
			unset( $result );
		}

		return $results ?: [];
	}

	/**
	 * Bulk update status for multiple entries.
	 *
	 * @param array  $ids    Entry IDs.
	 * @param string $status New status.
	 *
	 * @return int Number of affected rows.
	 */
	public function bulkUpdateStatus( array $ids, string $status ): int {
		if ( empty( $ids ) ) {
			return 0;
		}

		$status = FormEntryStatus::fromRaw( $status );
		if ( null === $status ) {
			return 0;
		}

		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table        = DB::tableNameFull( self::TABLE );

		// Keep is_spam column in sync with spam status.
		$isSpam   = FormEntryStatus::Spam === $status ? 1 : 0;
		$sql      = "UPDATE {$table} SET `status` = %s, `is_spam` = %d WHERE `id` IN ($placeholders)";
		$prepared = DB::db()->prepare( $sql, $status->value, $isSpam, ...$ids );

		$result = DB::db()->query( $prepared );

		return false !== $result ? (int) $result : 0;
	}

	/**
	 * Update status for a specific entry.
	 *
	 * @param int    $id     Entry ID.
	 * @param string $status New status.
	 *
	 * @return bool
	 */
	public function updateStatus( int $id, string $status ): bool {
		$status = FormEntryStatus::fromRaw( $status );
		if ( null === $status ) {
			return false;
		}

		$result = DB::updateOneRow( self::TABLE, $id, [ 'status' => $status->value ] );

		return (bool) $result;
	}

	/**
	 * Update admin notes for a specific entry.
	 *
	 * @param int    $id    Entry ID.
	 * @param string $notes Notes content.
	 *
	 * @return bool
	 */
	public function updateNotes( int $id, string $notes ): bool {
		$result = DB::updateOneRow( self::TABLE, $id, [ 'notes' => $notes ] );

		return (bool) $result;
	}

	/**
	 * Delete a specific entry.
	 *
	 * @param int $id Entry ID.
	 *
	 * @return bool
	 */
	public function delete( int $id ): bool {
		return $this->bulkDelete( [ $id ] ) > 0;
	}

	/**
	 * Bulk delete entries.
	 *
	 * @param array $ids Entry IDs.
	 *
	 * @return int Number of deleted rows.
	 */
	public function bulkDelete( array $ids ): int {
		$ids = self::normalizeIds( $ids );
		if ( empty( $ids ) ) {
			return 0;
		}

		$result = DB::transaction(
			static function () use ( $ids ): int {
				self::deleteRowsByEntryIds( 'hd_form_logs', $ids );
				self::deleteRowsByEntryIds( 'hd_mail_queue', $ids );

				return self::deleteEntryRows( $ids );
			}
		);

		return is_int( $result ) ? $result : 0;
	}

	/**
	 * Count entries based on filters.
	 *
	 * @param array $filters Filters.
	 *
	 * @return int
	 */
	public function countAll( array $filters = [] ): int {
		$table               = DB::tableNameFull( self::TABLE );
		[ $whereSql, $args ] = self::buildListWhere( $filters );
		$sql                 = "SELECT COUNT(*) FROM {$table} {$whereSql}";

		if ( $args ) {
			$sql = DB::db()->prepare( $sql, ...$args );
		}

		return (int) DB::db()->get_var( $sql );
	}

	/**
	 * Get grouped count by status to avoid N+1 queries.
	 *
	 * @return array<string, int>
	 */
	public function countByStatus(): array {
		$table = DB::tableNameFull( self::TABLE );
		$sql   = "SELECT `status`, COUNT(*) as count FROM {$table} GROUP BY `status`";

		$results = DB::db()->get_results( $sql, ARRAY_A );

		$counts = [];
		if ( $results ) {
			foreach ( $results as $row ) {
				$status = FormEntryStatus::fromRaw( $row['status'] ?? '' );
				if ( null !== $status ) {
					$counts[ $status->value ] = (int) $row['count'];
				}
			}
		}

		return $counts;
	}

	/**
	 * @return array<int, string>
	 */
	public static function allowedStatuses(): array {
		return FormEntryStatus::values();
	}

	/**
	 * @return array{0: string, 1: array<int, mixed>}
	 */
	private static function buildListWhere( array $filters ): array {
		$where = [];
		$args  = [];

		if ( array_key_exists( 'status', $filters ) && '' !== (string) $filters['status'] ) {
			$status = FormEntryStatus::fromRaw( $filters['status'] );
			if ( null === $status ) {
				$where[] = '1 = 0';
			} else {
				$where[] = '`status` = %s';
				$args[]  = $status->value;
			}
		}

		if ( ! empty( $filters['form_type'] ) ) {
			$where[] = '`form_type` = %s';
			$args[]  = sanitize_key( (string) $filters['form_type'] );
		}

		if ( ! empty( $filters['search'] ) ) {
			$like    = '%' . DB::db()->esc_like( (string) $filters['search'] ) . '%';
			$where[] = '(`name` LIKE %s OR `email` LIKE %s OR `phone` LIKE %s OR `ip_address` LIKE %s)';
			array_push( $args, $like, $like, $like, $like );
		}

		return [
			$where ? 'WHERE ' . implode( ' AND ', $where ) : '',
			$args,
		];
	}



	/**
	 * @return array<int, int>
	 */
	private static function normalizeIds( array $ids ): array {
		return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	}

	/**
	 * @param array<int, int> $ids
	 */
	private static function deleteRowsByEntryIds( string $tableName, array $ids ): int {
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table        = DB::tableNameFull( $tableName );
		$sql          = DB::db()->prepare( "DELETE FROM {$table} WHERE `entry_id` IN ($placeholders)", ...$ids );
		$result       = DB::db()->query( $sql );

		if ( false === $result ) {
			throw new \RuntimeException( esc_html( DB::db()->last_error ?: 'Failed to delete form child rows.' ) );
		}

		return (int) $result;
	}

	/**
	 * @param array<int, int> $ids
	 */
	private static function deleteEntryRows( array $ids ): int {
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table        = DB::tableNameFull( self::TABLE );
		$sql          = DB::db()->prepare( "DELETE FROM {$table} WHERE `id` IN ($placeholders)", ...$ids );
		$result       = DB::db()->query( $sql );

		if ( false === $result ) {
			throw new \RuntimeException( esc_html( DB::db()->last_error ?: 'Failed to delete form entries.' ) );
		}

		return (int) $result;
	}

	/**
	 * Detect DB-level duplicate submission failures.
	 */
	private function isDuplicateSubmissionError( \WP_Error $error ): bool {
		$message = strtolower( $error->get_error_message() );

		return str_contains( $message, 'duplicate' )
			&& str_contains( $message, 'uniq_form_submission' );
	}
}
