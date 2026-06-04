<?php
/**
 * Mail Queue Repository
 *
 * @package HD\Modules\Form\Repository
 */

namespace HD\Modules\Form\Repository;

use HD\Core\DB;

defined( 'ABSPATH' ) || exit;

class MailQueueRepository {
	use JsonDecodeTrait;

	private const TABLE = 'hd_mail_queue';

	/**
	 * Enqueue a new email.
	 *
	 * @param string $to      Recipient.
	 * @param string $subject Email subject.
	 * @param string $body    HTML body.
	 * @param int    $entryId Related entry ID.
	 * @param array  $headers Email headers.
	 *
	 * @return int|\WP_Error Insert ID on success.
	 */
	public function enqueue( string $to, string $subject, string $body, int $entryId = 0, array $headers = [] ): int|\WP_Error {
		$data = [
			'entry_id'     => $entryId,
			'channel'      => 'email',
			'to_email'     => $to,
			'subject'      => $subject,
			'body'         => $body,
			'headers'      => wp_json_encode( $headers ),
			'attachments'  => wp_json_encode( [] ),
			'payload'      => wp_json_encode( [] ),
			'status'       => 'pending',
			'max_attempts' => 3,
			'created_at'   => current_time( 'mysql' ),
			'scheduled_at' => current_time( 'mysql' ),
		];

		return DB::insertOneRow( self::TABLE, $data );
	}

	/**
	 * Get pending emails to process.
	 *
	 * @param int $limit Max emails to fetch.
	 *
	 * @return array
	 */
	public function getPending( int $limit = 10 ): array {
		$table = DB::tableNameFull( self::TABLE );
		$now   = current_time( 'mysql' );

		// Include stale 'processing' items (>15 min) to recover from crashes.
		$sql = "
			SELECT * FROM {$table}
			WHERE (
				(`status` IN ('pending', 'failed') AND `scheduled_at` <= %s)
				OR (`status` = 'processing' AND `scheduled_at` <= DATE_SUB(%s, INTERVAL 15 MINUTE))
			)
			AND `attempts` < `max_attempts`
			ORDER BY `scheduled_at` ASC
			LIMIT %d
		";

		$prepared = DB::db()->prepare( $sql, $now, $now, $limit );
		$results  = DB::db()->get_results( $prepared, ARRAY_A );

		if ( is_array( $results ) ) {
			foreach ( $results as &$result ) {
				$result['headers']     = self::decodeJsonArray( $result['headers'] ?? '', 'mail_queue.headers.' . ( $result['id'] ?? 'unknown' ) );
				$result['attachments'] = self::decodeJsonArray( $result['attachments'] ?? '', 'mail_queue.attachments.' . ( $result['id'] ?? 'unknown' ) );
				$result['payload']     = self::decodeJsonArray( $result['payload'] ?? '', 'mail_queue.payload.' . ( $result['id'] ?? 'unknown' ) );
			}
			unset( $result );
		}

		return $results ?: [];
	}

	/**
	 * Mark an email as processing (atomic increment of attempts).
	 *
	 * @param int $id Queue item ID.
	 *
	 * @return bool
	 */
	public function markProcessing( int $id, string $workerToken ): bool {
		$table = DB::tableNameFull( self::TABLE );
		$now   = current_time( 'mysql' );

		// Atomic claim: only update if pending/failed is due or processing is stale.
		$sql = "UPDATE {$table}
			SET `status` = 'processing',
				`attempts` = `attempts` + 1,
				`worker_token` = %s,
				`scheduled_at` = %s
			WHERE `id` = %d
				AND `attempts` < `max_attempts`
				AND (
					(`status` IN ('pending', 'failed') AND `scheduled_at` <= %s)
					OR (`status` = 'processing' AND `scheduled_at` <= DATE_SUB(%s, INTERVAL 15 MINUTE))
				)";

		$result = DB::db()->query( DB::db()->prepare( $sql, $workerToken, $now, $id, $now, $now ) );

		// Return true only if exactly one row was claimed.
		return false !== $result && DB::db()->rows_affected > 0;
	}

	/**
	 * Mark an email as sent.
	 *
	 * @param int $id Queue item ID.
	 *
	 * @return int
	 */
	public function markSent( int $id ): int {
		$data = [
			'status'       => 'sent',
			'worker_token' => null,
			'sent_at'      => current_time( 'mysql' ),
		];

		$result = DB::updateOneRow( self::TABLE, $id, $data );

		return is_int( $result ) ? $result : 0;
	}

	/**
	 * Mark an email as failed with exponential backoff (atomic).
	 *
	 * Backoff: ≤1 attempt → +5 min, 2 → +30 min, 3+ → +2 hours.
	 *
	 * @param int    $id    Queue item ID.
	 * @param string $error Error message.
	 *
	 * @return bool
	 */
	public function markFailed( int $id, string $error ): bool {
		$table = DB::tableNameFull( self::TABLE );
		$now   = current_time( 'mysql' );

		$sql = "UPDATE {$table} SET
			`status`       = CASE
				WHEN `attempts` >= `max_attempts` THEN 'dead'
				ELSE 'failed'
			END,
			`worker_token` = NULL,
			`last_error`   = %s,
			`scheduled_at` = CASE
				WHEN `attempts` >= `max_attempts` THEN %s
				WHEN `attempts` <= 1 THEN DATE_ADD(%s, INTERVAL 5 MINUTE)
				WHEN `attempts` = 2  THEN DATE_ADD(%s, INTERVAL 30 MINUTE)
				ELSE DATE_ADD(%s, INTERVAL 120 MINUTE)
			END
			WHERE `id` = %d";

		$result = DB::db()->query( DB::db()->prepare( $sql, $error, $now, $now, $now, $now, $id ) );

		return false !== $result;
	}
}
