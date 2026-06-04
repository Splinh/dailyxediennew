<?php
/**
 * Async form follow-up processor.
 *
 * @package SPL\Modules\Form\Cron
 */

namespace SPL\Modules\Form\Cron;

use SPL\Modules\Form\DTO\FormEntry;
use SPL\Modules\Form\FormConfig;
use SPL\Modules\Form\Notification\NotificationDispatcher;
use SPL\Modules\Form\Notification\NotificationMessage;
use SPL\Modules\Form\Repository\FormEntryRepository;
use SPL\Modules\Form\Repository\FormLogRepository;
use SPL\Modules\Form\Repository\NotificationQueueRepository;
use SPL\Modules\Form\Security\SpamChecker;

defined( 'ABSPATH' ) || exit;

final class AsyncFormProcessor {
	public const AKISMET_HOOK      = 'hd_form_async_akismet_check';
	public const NOTIFICATION_HOOK = 'hd_form_async_notifications';

	public static function init(): void {
		add_action( self::AKISMET_HOOK, [ self::class, 'processAkismet' ] );
		add_action( self::NOTIFICATION_HOOK, [ self::class, 'processNotifications' ] );
	}

	public static function enqueueAkismet( int $entryId ): bool {
		return self::schedule( self::AKISMET_HOOK, $entryId );
	}

	public static function enqueueNotifications( int $entryId, ?FormEntry $entry = null ): bool {
		$entry ??= self::entryById( $entryId );
		if ( null === $entry ) {
			return false;
		}

		$channels = NotificationDispatcher::enabledChannels( $entry->formType );
		unset( $channels['email'] );

		if ( ! $channels ) {
			return true;
		}

		$repo        = new NotificationQueueRepository();
		$queuedCount = 0;

		foreach ( array_keys( $channels ) as $channel ) {
			$result = $repo->enqueueChannel( $channel, $entryId, [ 'entry_id' => $entryId ] );
			if ( is_int( $result ) && $result > 0 ) {
				++$queuedCount;
			}
		}

		return $queuedCount === count( $channels );
	}

	public static function processAkismet( int $entryId ): void {
		$entry = self::entryById( $entryId );
		if ( null === $entry ) {
			return;
		}

		$reasons = SpamChecker::checkAkismet( $entry );
		if ( ! $reasons ) {
			return;
		}

		$entryRepo = new FormEntryRepository();
		$entryRepo->bulkUpdateStatus( [ $entryId ], 'spam' );

		( new FormLogRepository() )->log(
			$entryId,
			'spam_detected_async',
			'Async spam check classified the entry as spam.',
			[ 'spam_reasons' => $reasons ],
			'cron',
			$entry->ipAddress
		);
	}

	public static function processNotifications( int $entryId ): void {
		$entry = self::entryById( $entryId );
		if ( null === $entry ) {
			return;
		}

		$formTypeConfig = FormConfig::getFormType( $entry->formType );
		$message        = new NotificationMessage(
			entryId:       $entryId,
			formTypeLabel: $formTypeConfig['label'] ?? ucfirst( $entry->formType ),
			createdAt:     current_time( 'mysql' ),
			entry:         $entry,
		);
		$results        = NotificationDispatcher::dispatchNonEmail( $message );
		$logRepo        = new FormLogRepository();

		foreach ( $results as $channel => $success ) {
			$logRepo->log(
				$entryId,
				$success ? $channel . '_sent' : $channel . '_failed',
				sprintf( 'Async notification via %s: %s', $channel, $success ? 'Sent' : 'Failed' ),
				[ 'channel' => $channel ],
				'cron',
				$entry->ipAddress
			);
		}
	}

	public static function dispatchNotificationChannel( int $entryId, string $channel ): bool {
		$entry = self::entryById( $entryId );
		if ( null === $entry ) {
			return false;
		}

		$formTypeConfig = FormConfig::getFormType( $entry->formType );
		$message        = new NotificationMessage(
			entryId:       $entryId,
			formTypeLabel: $formTypeConfig['label'] ?? ucfirst( $entry->formType ),
			createdAt:     current_time( 'mysql' ),
			entry:         $entry,
		);
		$results        = NotificationDispatcher::dispatch( $message, [ $channel ] );

		return ! empty( $results[ $channel ] );
	}

	private static function schedule( string $hook, int $entryId ): bool {
		if ( ! function_exists( 'wp_schedule_single_event' ) ) {
			return false;
		}

		if ( function_exists( 'wp_next_scheduled' ) && wp_next_scheduled( $hook, [ $entryId ] ) ) {
			return true;
		}

		return (bool) wp_schedule_single_event( time(), $hook, [ $entryId ] );
	}

	private static function entryById( int $entryId ): ?FormEntry {
		$row = ( new FormEntryRepository() )->findById( $entryId );

		return null !== $row ? FormEntry::fromRow( $row ) : null;
	}
}
