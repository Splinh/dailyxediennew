<?php
/**
 * Database Optimizer - Clean up database bloat and optimize tables.
 *
 * Removes revisions, auto-drafts, trashed posts/comments, expired transients,
 * orphaned metadata, and optimizes database tables.
 *
 * @package HDAddons\Modules\Optimize
 */

namespace HDAddons\Modules\Optimize;

use HDAddons\Asset;
use HDAddons\Contracts\HasSettings;
use HDAddons\DB;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class DatabaseOptimizer implements HasSettings {


	// ─── Option Keys ─────────────────────────────────
	public const SUB_KEY             = 'db_optimizer';
	public const KEY_SCHEDULE        = 'schedule';        // '' | 'daily' | 'weekly' | 'monthly'
	public const KEY_CLEANUP_ENABLED = 'cleanup_enabled';  // bool

	// Internal task keys (used by runCleanup / getCounts).
	private const KEY_REVISIONS       = 'revisions';
	private const KEY_AUTO_DRAFTS     = 'auto_drafts';
	private const KEY_TRASH_POSTS     = 'trash_posts';
	private const KEY_SPAM_COMMENTS   = 'spam_comments';
	private const KEY_TRASH_COMMENTS  = 'trash_comments';
	private const KEY_TRANSIENTS      = 'transients';
	private const KEY_ORPHAN_POSTMETA = 'orphan_postmeta';
	private const KEY_ORPHAN_TERMMETA = 'orphan_termmeta';
	private const KEY_OPTIMIZE_TABLES = 'optimize_tables';

	private const CRON_HOOK = 'hda_db_optimizer_cleanup';

	/**
	 * Cached options.
	 *
	 * @var array|null
	 */
	private static ?array $options = null;

	// ─────────────────────────────────────────────────

	/**
	 * Initialize the module.
	 */
	public function __construct() {
		// Cron handler.
		add_action( self::CRON_HOOK, self::runScheduledCleanup( ... ) );

		// AJAX handler for manual cleanup.
		add_action( 'wp_ajax_hda_db_optimize', self::ajaxOptimize( ... ) );

		// Localize nonce for external JS module.
		add_action(
			'admin_enqueue_scripts',
			static function (): void {
				$handle = Asset::handle( 'settings.js' );
				if ( $handle ) {
					Asset::localize(
						$handle,
						'hdaDbOptimizer',
						[
							'nonce' => wp_create_nonce( 'hda_db_optimize' ),
							'i18n'  => [
								'optimizing' => __( 'Optimizing...', 'hda' ),
							],
						]
					);
				}
			},
			50
		);
	}

	// ─────────────────────────────────────────────────

	/**
	 * Get cached module options.
	 *
	 * @return array
	 */
	public static function getOptions(): array {
		if ( null === self::$options ) {
			self::$options = OptimizeModule::getSubOptions( self::SUB_KEY );
		}

		return self::$options;
	}

	/**
	 * Reset cached options (call after save).
	 *
	 * @return void
	 */
	public static function resetCache(): void {
		self::$options = null;
	}

	// ─────────────────────────────────────────────────

	/**
	 * Sync the cron schedule based on the given recurrence.
	 * Called from SettingsManager on settings save — NOT on every admin_init.
	 *
	 * @param string $schedule '' | 'daily' | 'weekly' | 'monthly'
	 *
	 * @return void
	 */
	public static function syncSchedule( string $schedule ): void {
		$scheduled = wp_next_scheduled( self::CRON_HOOK );

		if ( empty( $schedule ) ) {
			if ( $scheduled ) {
				wp_unschedule_event( $scheduled, self::CRON_HOOK );
			}

			return;
		}

		// Re-schedule if recurrence changed.
		if ( $scheduled ) {
			$existing = wp_get_schedule( self::CRON_HOOK );
			if ( $existing !== $schedule ) {
				wp_unschedule_event( $scheduled, self::CRON_HOOK );
				$scheduled = false;
			}
		}

		if ( ! $scheduled ) {
			wp_schedule_event( time(), $schedule, self::CRON_HOOK );
		}

		self::resetCache();
	}

	// ─────────────────────────────────────────────────

	/**
	 * Run scheduled cleanup — only if cleanup is enabled.
	 *
	 * @return void
	 */
	public static function runScheduledCleanup(): void {
		$options = self::getOptions();

		if ( ! empty( $options[ self::KEY_CLEANUP_ENABLED ] ) ) {
			self::runCleanup( self::allTaskFlags() );
		}
	}

	// ─────────────────────────────────────────────────

	/**
	 * AJAX handler for manual optimization (runs all tasks).
	 *
	 * @return void
	 */
	public static function ajaxOptimize(): void {
		check_ajax_referer( 'hda_db_optimize', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
		}

		$results = self::runCleanup( self::allTaskFlags() );

		wp_send_json_success(
			[
				'message' => __( 'Optimization complete.', 'hda' ),
				'results' => $results,
			]
		);
	}

	// ─────────────────────────────────────────────────

	/**
	 * Get all task flags enabled.
	 *
	 * @return array<string, true>
	 */
	private static function allTaskFlags(): array {
		return [
			self::KEY_REVISIONS       => true,
			self::KEY_AUTO_DRAFTS     => true,
			self::KEY_TRASH_POSTS     => true,
			self::KEY_SPAM_COMMENTS   => true,
			self::KEY_TRASH_COMMENTS  => true,
			self::KEY_TRANSIENTS      => true,
			self::KEY_ORPHAN_POSTMETA => true,
			self::KEY_ORPHAN_TERMMETA => true,
			self::KEY_OPTIMIZE_TABLES => true,
		];
	}

	// ─────────────────────────────────────────────────

	/**
	 * Run cleanup tasks based on provided options.
	 *
	 * @param array $options Task flags.
	 *
	 * @return array<string, int> Results with counts.
	 */
	public static function runCleanup( array $options ): array {
		$wpdb = DB::db();

		$results = [];
		$counts  = self::getCounts();

		// ── Post Revisions ───────────────────────────
		if ( ! empty( $options[ self::KEY_REVISIONS ] ) ) {
			$wpdb->query(
				"DELETE p, pm FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_type = 'revision'"
			);
			$results['revisions'] = $counts['revisions'];
		}

		// ── Auto-Drafts ──────────────────────────────
		if ( ! empty( $options[ self::KEY_AUTO_DRAFTS ] ) ) {
			$wpdb->query(
				"DELETE p, pm FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_status = 'auto-draft'"
			);
			$results['auto_drafts'] = $counts['auto_drafts'];
		}

		// ── Trashed Posts ────────────────────────────
		if ( ! empty( $options[ self::KEY_TRASH_POSTS ] ) ) {
			$wpdb->query(
				"DELETE p, pm FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_status = 'trash'"
			);
			$results['trash_posts'] = $counts['trash_posts'];
		}

		// ── Spam Comments ────────────────────────────
		if ( ! empty( $options[ self::KEY_SPAM_COMMENTS ] ) ) {
			$wpdb->query(
				"DELETE c, cm FROM {$wpdb->comments} c
				LEFT JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID
				WHERE c.comment_approved = 'spam'"
			);
			$results['spam_comments'] = $counts['spam_comments'];
		}

		// ── Trashed Comments ─────────────────────────
		if ( ! empty( $options[ self::KEY_TRASH_COMMENTS ] ) ) {
			$wpdb->query(
				"DELETE c, cm FROM {$wpdb->comments} c
				LEFT JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID
				WHERE c.comment_approved = 'trash'"
			);
			$results['trash_comments'] = $counts['trash_comments'];
		}

		// ── Expired Transients ───────────────────────
		if ( ! empty( $options[ self::KEY_TRANSIENTS ] ) ) {
			$time = time();
			$wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->options} a
					LEFT JOIN {$wpdb->options} b ON b.option_name = REPLACE(a.option_name, '_timeout_', '_')
					WHERE a.option_name LIKE '\_transient\_timeout\_%'
					AND a.option_value < %d",
					$time
				)
			);
			$results['transients'] = $counts['transients'];
		}

		// ── Orphaned Postmeta ────────────────────────
		if ( ! empty( $options[ self::KEY_ORPHAN_POSTMETA ] ) ) {
			$wpdb->query(
				"DELETE pm FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.ID IS NULL"
			);
			$results['orphan_postmeta'] = $counts['orphan_postmeta'];
		}

		// ── Orphaned Termmeta ────────────────────────
		if ( ! empty( $options[ self::KEY_ORPHAN_TERMMETA ] ) ) {
			$wpdb->query(
				"DELETE tm FROM {$wpdb->termmeta} tm
				LEFT JOIN {$wpdb->terms} t ON t.term_id = tm.term_id
				WHERE t.term_id IS NULL"
			);
			$results['orphan_termmeta'] = $counts['orphan_termmeta'];
		}

		// ── Optimize Tables ──────────────────────────
		if ( ! empty( $options[ self::KEY_OPTIMIZE_TABLES ] ) ) {
			$tables = $wpdb->get_col(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$wpdb->esc_like( $wpdb->prefix ) . '%'
				)
			);
			$count  = 0;

			foreach ( $tables as $table ) {
				// Validate table name — only allow word characters and underscores.
				if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $table ) ) {
					continue;
				}

				$wpdb->query( "OPTIMIZE TABLE `{$table}`" );
				++$count;
			}

			$results['optimize_tables'] = $count;
		}

		return $results;
	}

	// ─────────────────────────────────────────────────

	/**
	 * Get counts of items that can be cleaned.
	 *
	 * @return array<string, int>
	 */
	public static function getCounts(): array {
		$wpdb = DB::db();

		// Batch 1: Posts table — 3 counts in 1 query via conditional aggregation.
		$postCounts = $wpdb->get_row(
			"SELECT
				SUM(post_type = 'revision') AS revisions,
				SUM(post_status = 'auto-draft') AS auto_drafts,
				SUM(post_status = 'trash') AS trash_posts
			FROM {$wpdb->posts}"
		);

		// Batch 2: Comments table — 2 counts in 1 query.
		$commentCounts = $wpdb->get_row(
			"SELECT
				SUM(comment_approved = 'spam') AS spam_comments,
				SUM(comment_approved = 'trash') AS trash_comments
			FROM {$wpdb->comments}"
		);

		return [
			'revisions'       => (int) ( $postCounts->revisions ?? 0 ),
			'auto_drafts'     => (int) ( $postCounts->auto_drafts ?? 0 ),
			'trash_posts'     => (int) ( $postCounts->trash_posts ?? 0 ),
			'spam_comments'   => (int) ( $commentCounts->spam_comments ?? 0 ),
			'trash_comments'  => (int) ( $commentCounts->trash_comments ?? 0 ),
			'transients'      => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\_%' AND option_value < %d",
					time()
				)
			),
			'orphan_postmeta' => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL"
			),
			'orphan_termmeta' => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->terms} t ON t.term_id = tm.term_id WHERE t.term_id IS NULL"
			),
		];
	}

	// ── HasSettings ──────────────────────────────────────


	public static function saveSettings( array $data ): void {
		$input   = $data[ self::SUB_KEY ] ?? [];
		$options = [];

		// Schedule ('' | 'daily' | 'weekly' | 'monthly').
		$schedule = $input[ self::KEY_SCHEDULE ] ?? '';
		if ( in_array( $schedule, [ 'daily', 'weekly', 'monthly' ], true ) ) {
			$options[ self::KEY_SCHEDULE ] = $schedule;
		}

		// Cleanup enabled toggle.
		if ( ! empty( $input[ self::KEY_CLEANUP_ENABLED ] ) ) {
			$options[ self::KEY_CLEANUP_ENABLED ] = true;
		}

		OptimizeModule::setSubOptions( self::SUB_KEY, $options );

		// Manage cron schedule immediately after save.
		self::syncSchedule( $options[ self::KEY_SCHEDULE ] ?? '' );
	}
}
