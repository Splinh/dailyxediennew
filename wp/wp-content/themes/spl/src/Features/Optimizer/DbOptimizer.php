<?php
/**
 * Database Optimizer module.
 *
 * Cleans up expired transients, deletes post revisions, and optimizes
 * core WordPress tables on cache purge.
 *
 * @package SPL\Features\Optimizer
 */

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

final class DbOptimizer {

	/**
	 * Register hook.
	 */
	public static function register(): void {
		add_action( 'hd_clear_all_cache', [ self::class, 'optimize' ] );
	}

	/**
	 * Optimize database.
	 */
	public static function optimize(): void {
		global $wpdb;

		// 1. Delete all post revisions (including meta and relationships).
		$wpdb->query(
			"DELETE a, b, c FROM {$wpdb->posts} a
			 LEFT JOIN {$wpdb->postmeta} b ON a.ID = b.post_id
			 LEFT JOIN {$wpdb->term_relationships} c ON a.ID = c.object_id
			 WHERE a.post_type = 'revision'"
		);

		// 2. Delete expired transients.
		$time = time();
		$wpdb->query( $wpdb->prepare(
			"DELETE a, b FROM {$wpdb->options} a
			 JOIN {$wpdb->options} b ON b.option_name = REPLACE(a.option_name, '_transient_timeout_', '_transient_')
			 WHERE a.option_name LIKE %s AND a.option_value < %d",
			'_transient_timeout_%',
			$time
		) );

		$wpdb->query( $wpdb->prepare(
			"DELETE a, b FROM {$wpdb->options} a
			 JOIN {$wpdb->options} b ON b.option_name = REPLACE(a.option_name, '_site_transient_timeout_', '_site_transient_')
			 WHERE a.option_name LIKE %s AND a.option_value < %d",
			'_site_transient_timeout_%',
			$time
		) );

		// 3. Optimize core tables to reclaim unused space (CLI/cron only, skip on web requests to avoid locking tables).
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$tables = [
				$wpdb->posts,
				$wpdb->postmeta,
				$wpdb->options,
				$wpdb->term_relationships,
			];

			foreach ( $tables as $table ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( "OPTIMIZE TABLE {$table}" );
			}
		}
	}
}
