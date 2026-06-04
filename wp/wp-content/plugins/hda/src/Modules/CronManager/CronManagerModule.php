<?php
/**
 * CronManager Module — View, manage, and debug WP-Cron events.
 *
 * Lists all scheduled cron events, allows deleting, running immediately,
 * and detecting stuck/overdue cron jobs.
 *
 * @package HDAddons\Modules\CronManager
 */

namespace HDAddons\Modules\CronManager;

use HDAddons\Asset;
use HDAddons\Modules\AbstractModule;
use HDAddons\Plugin;

defined( 'ABSPATH' ) || exit;

final class CronManagerModule extends AbstractModule {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'cron_manager';
	}

	public static function title(): string {
		return 'Cron Manager';
	}

	public static function description(): string {
		return 'View, run, and manage WP-Cron events.';
	}

	public static function group(): string {
		return 'tools';
	}

	// ── Option Keys ─────────────────────────────────


	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		// AJAX handlers (admin only).
		add_action( 'wp_ajax_hda_cron_run', self::ajaxRunEvent( ... ) );
		add_action( 'wp_ajax_hda_cron_delete', self::ajaxDeleteEvent( ... ) );
		add_action( 'wp_ajax_hda_cron_delete_all', self::ajaxDeleteAllForHook( ... ) );

		// Localize nonce for external JS module.
		add_action(
			'admin_enqueue_scripts',
			static function (): void {
				$handle = Asset::handle( 'settings.js' );
				if ( $handle ) {
					Asset::localize(
						$handle,
						'hdaCronManager',
						[
							'nonce' => wp_create_nonce( 'hda_cron_manage' ),
						]
					);
				}
			},
			50
		);
	}

	// ── Public API ──────────────────────────────────

	/**
	 * Get all cron events, sorted by next run time.
	 *
	 * @return array<int, array{
	 *     hook: string,
	 *     timestamp: int,
	 *     schedule: string,
	 *     interval: int|null,
	 *     args: array,
	 *     overdue: bool
	 * }>
	 */
	public static function getEvents(): array {
		$cronArray = _get_cron_array();
		$events    = [];
		$now       = time();

		if ( empty( $cronArray ) || ! is_array( $cronArray ) ) {
			return [];
		}

		foreach ( $cronArray as $timestamp => $hooks ) {
			if ( ! is_array( $hooks ) ) {
				continue;
			}

			foreach ( $hooks as $hook => $schedules ) {
				if ( ! is_array( $schedules ) ) {
					continue;
				}

				foreach ( $schedules as $key => $event ) {
					$events[] = [
						'hook'      => $hook,
						'timestamp' => (int) $timestamp,
						'schedule'  => $event['schedule'] ?? '',
						'interval'  => $event['interval'] ?? null,
						'args'      => $event['args'] ?? [],
						'args_key'  => $key,
						'overdue'   => ( (int) $timestamp < $now - 60 ),
					];
				}
			}
		}

		// Sort by timestamp ascending.
		usort( $events, static fn( $a, $b ) => $a['timestamp'] <=> $b['timestamp'] );

		return $events;
	}

	/**
	 * Get summary statistics about cron events.
	 *
	 * @param array|null $events Pre-computed events (to avoid double call). Null = fetch internally.
	 *
	 * @return array{total: int, overdue: int, recurring: int, one_time: int}
	 */
	public static function getStats( ?array $events = null ): array {
		if ( null === $events ) {
			$events = self::getEvents();
		}

		return [
			'total'     => count( $events ),
			'overdue'   => count( array_filter( $events, static fn( $e ) => $e['overdue'] ) ),
			'recurring' => count( array_filter( $events, static fn( $e ) => ! empty( $e['schedule'] ) ) ),
			'one_time'  => count( array_filter( $events, static fn( $e ) => empty( $e['schedule'] ) ) ),
		];
	}

	/**
	 * Get available cron schedules with labels.
	 *
	 * @return array<string, array{display: string, interval: int}>
	 */
	public static function getSchedules(): array {
		return wp_get_schedules();
	}

	/**
	 * Check if WP-Cron is effectively running.
	 *
	 * @return array{enabled: bool, disabled_constant: bool, alternate_cron: bool}
	 */
	public static function getCronStatus(): array {
		return [
			'enabled'           => ! ( defined( 'DISABLE_WP_CRON' ) && \DISABLE_WP_CRON ),
			'disabled_constant' => defined( 'DISABLE_WP_CRON' ) && \DISABLE_WP_CRON,
			'alternate_cron'    => defined( 'ALTERNATE_WP_CRON' ) && \ALTERNATE_WP_CRON,
		];
	}

	// ── AJAX Handlers ───────────────────────────────

	/**
	 * Validate AJAX request and find cron event.
	 *
	 * @param bool $requireTimestamp Whether timestamp is required.
	 *
	 * @return array{hook: string, timestamp: int, args: array, event: array}|null Null if validation failed (response already sent).
	 */
	private static function validateAndFindEvent( bool $requireTimestamp = true ): ?array {
		if ( ! check_ajax_referer( 'hda_cron_manage', '_nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid or expired nonce. Please reload the page.' ], 403 );
		}

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
		}

		$hook = sanitize_text_field( wp_unslash( $_POST['hook'] ?? '' ) );

		if ( empty( $hook ) ) {
			wp_send_json_error( [ 'message' => 'Missing hook.' ] );
		}

		if ( ! $requireTimestamp ) {
			return [
				'hook'      => $hook,
				'timestamp' => 0,
				'args'      => [],
				'event'     => [],
			];
		}

		$timestamp = absint( $_POST['timestamp'] ?? 0 );
		$sig       = sanitize_text_field( wp_unslash( $_POST['sig'] ?? '' ) );

		if ( empty( $timestamp ) ) {
			wp_send_json_error( [ 'message' => 'Missing timestamp.' ] );
		}

		// Find the specific event using sig (args hash) for disambiguation.
		$cronArray = _get_cron_array();
		$args      = [];
		$event     = [];

		if ( isset( $cronArray[ $timestamp ][ $hook ] ) ) {
			$schedules = $cronArray[ $timestamp ][ $hook ];

			if ( $sig && isset( $schedules[ $sig ] ) ) {
				// Exact match via args hash.
				$event = $schedules[ $sig ];
			} elseif ( ! empty( $schedules ) ) {
				// Fallback: first entry.
				$event = reset( $schedules );
			}

			$args = $event['args'] ?? [];
		}

		return [
			'hook'      => $hook,
			'timestamp' => $timestamp,
			'args'      => $args,
			'event'     => $event,
		];
	}

	/**
	 * Run a cron event immediately.
	 */
	public static function ajaxRunEvent(): void {
		$found = self::validateAndFindEvent();
		if ( null === $found ) {
			return;
		}

		try {
			// One-time events: unschedule before run to prevent double execution if it throws.
			$removed = false;
			if ( ! empty( $found['event'] ) && empty( $found['event']['schedule'] ) ) {
				wp_unschedule_event( $found['timestamp'], $found['hook'], $found['args'] );
				$removed = true;
			}

			do_action_ref_array( $found['hook'], $found['args'] );

			wp_send_json_success(
				[
					'message' => sprintf(
						/* translators: %s: hook name */
						__( 'Executed: %s', 'hda' ),
						$found['hook']
					),
					'removed' => $removed,
				]
			);
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * Delete a specific cron event.
	 */
	public static function ajaxDeleteEvent(): void {
		$found = self::validateAndFindEvent();
		if ( null === $found ) {
			return;
		}

		$result = wp_unschedule_event( $found['timestamp'], $found['hook'], $found['args'] );

		if ( false !== $result ) {
			wp_send_json_success( [ 'message' => __( 'Event deleted.', 'hda' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to delete event.', 'hda' ) ] );
		}
	}

	/**
	 * Delete all events for a specific hook.
	 */
	public static function ajaxDeleteAllForHook(): void {
		$found = self::validateAndFindEvent( false );
		if ( null === $found ) {
			return;
		}

		wp_unschedule_hook( $found['hook'] );

		wp_send_json_success(
			[
				'message' => sprintf(
					/* translators: %s: hook name */
					__( 'All events for "%s" have been removed.', 'hda' ),
					$found['hook']
				),
			]
		);
	}
}
