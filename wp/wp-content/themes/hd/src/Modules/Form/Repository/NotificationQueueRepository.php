<?php
/**
 * Channel-aware notification queue repository.
 *
 * @package HD\Modules\Form\Repository
 */

namespace HD\Modules\Form\Repository;

use HD\Core\DB;

defined( 'ABSPATH' ) || exit;

class NotificationQueueRepository extends MailQueueRepository {
	private const TABLE = 'hd_mail_queue';

	/**
	 * Enqueue a non-email notification channel.
	 *
	 * @param array<string, mixed> $payload Channel payload.
	 */
	public function enqueueChannel( string $channel, int $entryId, array $payload = [], int $maxAttempts = 3 ): int|\WP_Error {
		$channel = sanitize_key( $channel );
		if ( '' === $channel || 'email' === $channel ) {
			return new \WP_Error( 'invalid_channel', 'Invalid notification channel.' );
		}

		$data = [
			'entry_id'     => $entryId,
			'channel'      => $channel,
			'to_email'     => '',
			'subject'      => '',
			'body'         => '',
			'headers'      => wp_json_encode( [] ),
			'attachments'  => wp_json_encode( [] ),
			'payload'      => wp_json_encode( $payload ),
			'status'       => 'pending',
			'max_attempts' => $maxAttempts,
			'created_at'   => current_time( 'mysql' ),
			'scheduled_at' => current_time( 'mysql' ),
		];

		return DB::insertOneRow( self::TABLE, $data );
	}
}
