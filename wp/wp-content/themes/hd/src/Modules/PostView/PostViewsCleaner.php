<?php
/**
 * Cron handler — cleans orphaned post_views records.
 *
 * Runs weekly to delete rows whose post_id no longer exists in wp_posts.
 *
 * @package HD\Modules\PostView
 * @author  HD
 */

namespace HD\Modules\PostView;

use HD\Core\DB;
use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class PostViewsCleaner {

	private const HOOK       = '_clean_post_views_handler';
	private const INTERVAL   = 'weekly';
	private const BATCH_SIZE = 500;

	// -----------------------------------------

	/**
	 * Register the cron handler and schedule if not already scheduled.
	 *
	 * @return void
	 */
	public function schedule(): void {
		add_action( self::HOOK, $this->handle( ... ) );

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), self::INTERVAL, self::HOOK );
		}
	}

	// -----------------------------------------

	/**
	 * Delete post_views rows whose post_id no longer exists.
	 *
	 * @return void
	 */
	public function handle(): void {
		$table = PostViewModule::TABLE;

		if ( ! DB::tableExists( $table ) ) {
			return;
		}

		$totalDeleted = 0;

		do {
			$orphanedPostIds = $this->orphanedPostIds( $table, self::BATCH_SIZE );
			$orphanedCount   = count( $orphanedPostIds );
			if ( 0 === $orphanedCount ) {
				break;
			}

			$deleted = $this->deleteOrphanedRows( $table, $orphanedPostIds );
			if ( false === $deleted ) {
				Helper::errorLog( '[PostViewsCleaner] Failed: ' . DB::db()->last_error );
				return;
			}

			if ( $deleted > 0 ) {
				foreach ( $orphanedPostIds as $postId ) {
					PostViewModule::invalidateCache( $postId );
				}
			}

			$totalDeleted += $deleted;
		} while ( $deleted > 0 && $orphanedCount >= self::BATCH_SIZE );

		if ( $totalDeleted > 0 ) {
			Helper::errorLog( "[PostViewsCleaner] Deleted {$totalDeleted} orphaned rows." );
		}
	}

	/**
	 * @return int[]
	 */
	private function orphanedPostIds( string $table, int $limit ): array {
		$sql = 'SELECT DISTINCT pv.post_id FROM ' . DB::backtickedTable( $table ) . ' AS pv
			LEFT JOIN ' . DB::db()->posts . ' AS p ON pv.post_id = p.ID
			WHERE p.ID IS NULL
			LIMIT %d';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input; table names sanitized via DB::backtickedTable().
		$postIds = DB::db()->get_col( DB::db()->prepare( $sql, max( 1, $limit ) ) );
		if ( ! is_array( $postIds ) ) {
			return [];
		}

		return array_values(
			array_unique(
				array_filter(
					array_map( static fn( mixed $postId ): int => (int) $postId, $postIds )
				)
			)
		);
	}

	/**
	 * @param int[] $postIds Orphaned post IDs selected by orphanedPostIds().
	 */
	private function deleteOrphanedRows( string $table, array $postIds ): int|false {
		$postIds = array_values( array_unique( array_filter( array_map( 'absint', $postIds ) ) ) );
		if ( empty( $postIds ) ) {
			return 0;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $postIds ), '%d' ) );
		$sql          = 'DELETE pv FROM ' . DB::backtickedTable( $table ) . ' AS pv
			LEFT JOIN ' . DB::db()->posts . " AS p ON pv.post_id = p.ID
			WHERE p.ID IS NULL AND pv.post_id IN ({$placeholders})";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic placeholders are prepared with integer post IDs.
		return DB::db()->query( DB::db()->prepare( $sql, ...$postIds ) );
	}
}
