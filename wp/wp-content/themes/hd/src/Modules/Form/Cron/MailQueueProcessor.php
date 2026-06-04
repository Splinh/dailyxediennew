<?php
/**
 * Mail Queue Processor — WP Cron handler.
 *
 * Processes pending/failed emails from the `hd_mail_queue` table.
 * Runs every 5 minutes via WP Cron. Batch size: 10 emails per run.
 *
 * @package HD\Modules\Form\Cron
 */

namespace HD\Modules\Form\Cron;

use HD\Modules\Form\Repository\FormLogRepository;
use HD\Modules\Form\Repository\MailQueueRepository;

defined( 'ABSPATH' ) || exit;

class MailQueueProcessor {
	public const HOOK     = 'hd_process_mail_queue';
	public const INTERVAL = 'every_five_minutes';

	private const BATCH_SIZE = 10;
	private const LOCK_KEY   = 'hd_mail_queue_processor_lock';
	private const LOCK_TTL   = 10 * MINUTE_IN_SECONDS;

	/**
	 * Register cron schedule and hook.
	 */
	public static function init(): void {
		// phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- Mail queue intentionally runs every 5 minutes to keep async email latency low.
		add_filter( 'cron_schedules', [ self::class, 'registerSchedule' ] );
		add_action( self::HOOK, [ self::class, 'process' ] );
		add_action( 'init', [ self::class, 'ensureScheduled' ] );
	}

	/**
	 * Add custom "every_five_minutes" schedule if missing.
	 *
	 * @param array $schedules Existing schedules.
	 *
	 * @return array
	 */
	public static function registerSchedule( array $schedules ): array {
		if ( ! isset( $schedules[ self::INTERVAL ] ) ) {
			$schedules[ self::INTERVAL ] = [
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 5 minutes', 'hd' ),
			];
		}

		return $schedules;
	}

	/**
	 * Ensure the cron event is scheduled.
	 */
	public static function ensureScheduled(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), self::INTERVAL, self::HOOK );
		}
	}

	/**
	 * On theme deactivation / cleanup — unschedule the event.
	 */
	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	/**
	 * Process pending mail queue items.
	 */
	public static function process(): void {
		$workerToken = self::acquireLock();
		if ( null === $workerToken ) {
			return;
		}

		$repo    = new MailQueueRepository();
		$logRepo = new FormLogRepository();

		try {
			$pending = $repo->getPending( self::BATCH_SIZE );

			if ( empty( $pending ) ) {
				return;
			}

			foreach ( $pending as $item ) {
				$id = (int) $item['id'];

				// Mark as processing (also increments attempt counter).
				// Skip if another worker already claimed this item.
				if ( ! $repo->markProcessing( $id, $workerToken ) ) {
					continue;
				}

				$channel = (string) ( $item['channel'] ?? 'email' );
				if ( 'email' !== $channel ) {
					$sent = AsyncFormProcessor::dispatchNotificationChannel( (int) $item['entry_id'], $channel );
					if ( $sent ) {
						$repo->markSent( $id );
						$logRepo->log(
							(int) $item['entry_id'],
							$channel . '_sent',
							sprintf( 'Queued notification via %s sent.', $channel ),
							[
								'queue_id' => $id,
								'channel'  => $channel,
							],
							'cron'
						);
					} else {
						$errorMessage = 'Notification channel failed.';
						$repo->markFailed( $id, $errorMessage );
						$logRepo->log(
							(int) $item['entry_id'],
							$channel . '_failed',
							sprintf( 'Queued notification via %s failed.', $channel ),
							[
								'queue_id' => $id,
								'channel'  => $channel,
								'attempts' => (int) $item['attempts'] + 1,
								'error'    => $errorMessage,
							],
							'cron'
						);
					}

					continue;
				}

				// Capture real error via wp_mail_failed hook.
				$lastMailError = null;

				$errorCapture = function ( \WP_Error $error ) use ( &$lastMailError ): void {
					$lastMailError = $error->get_error_message();
				};

				add_action( 'wp_mail_failed', $errorCapture );

				$sent = wp_mail(
					$item['to_email'],
					$item['subject'],
					$item['body'],
					$item['headers']
				);

				remove_action( 'wp_mail_failed', $errorCapture );

				if ( $sent ) {
					$repo->markSent( $id );

					$logRepo->log(
						(int) $item['entry_id'],
						'email_sent',
						sprintf( 'Email sent to %s', $item['to_email'] ),
						[
							'queue_id' => $id,
							'subject'  => $item['subject'],
						],
						'cron'
					);
				} else {
					$errorMessage = $lastMailError ?: 'Unknown error';

					$repo->markFailed( $id, $errorMessage );

					$logRepo->log(
						(int) $item['entry_id'],
						'email_failed',
						sprintf( 'Email to %s failed: %s', $item['to_email'], $errorMessage ),
						[
							'queue_id' => $id,
							'attempts' => (int) $item['attempts'] + 1,
							'error'    => $errorMessage,
						],
						'cron'
					);
				}
			}
		} finally {
			self::releaseLock( $workerToken );
		}
	}

	private static function acquireLock(): ?string {
		$token = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'hd_mail_queue_worker_', true );
		$lock  = [
			'token'      => $token,
			'created_at' => time(),
		];

		if ( add_option( self::LOCK_KEY, $lock, '', false ) ) {
			return $token;
		}

		$current = get_option( self::LOCK_KEY, [] );
		$created = is_array( $current ) ? absint( $current['created_at'] ?? 0 ) : absint( $current );
		if ( $created > 0 && time() - $created < self::LOCK_TTL ) {
			return null;
		}

		delete_option( self::LOCK_KEY );

		return add_option( self::LOCK_KEY, $lock, '', false ) ? $token : null;
	}

	private static function releaseLock( string $token ): void {
		$current = get_option( self::LOCK_KEY, [] );
		if ( ! is_array( $current ) || ( $current['token'] ?? '' ) !== $token ) {
			return;
		}

		delete_option( self::LOCK_KEY );
	}
}
