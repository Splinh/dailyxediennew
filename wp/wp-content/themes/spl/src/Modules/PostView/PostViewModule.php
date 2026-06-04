<?php
/**
 * Post Views Module.
 *
 * Tracks and retrieves post view counts with IP-based cooldown.
 * Self-registers its own cron job for cleaning orphaned records.
 *
 * @package SPL\Modules\PostView
 * @author  HD
 */

namespace SPL\Modules\PostView;

use SPL\Contracts\HasDatabaseSchema;
use SPL\Modules\AbstractModule;
use SPL\Core\DB;

defined( 'ABSPATH' ) || exit;

final class PostViewModule extends AbstractModule implements HasDatabaseSchema {

	public const TABLE          = 'post_views';
	private const CACHE_GROUP   = 'post_views';
	private const CACHE_PREFIX  = 'views_';
	private const VIEW_COOLDOWN = 240; // 4 minutes

	/* ---------- ModuleInterface --------------------------------- */

	public static function slug(): string {
		return 'post-view';
	}

	/** PostView module is always active — no external plugin dependency. */
	public static function isActive(): bool {
		return true;
	}

	public static function apiClasses(): array {
		return [ PostViewAPI::class ];
	}

	/** @inheritDoc */
	public static function databaseSchemas(): array {
		return [
			// inet_pton() stores packed IPv4 (4 bytes) and IPv6 (16 bytes), not text.
			self::TABLE => <<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint unsigned NOT NULL,
			ip varbinary(16) NOT NULL,
			last_view int unsigned NOT NULL,
			view_count int unsigned DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_view (post_id, ip),
			KEY post_id_idx (post_id)
			SQL,
		];
	}

	/* ---------- Boot -------------------------------------------- */

	public function boot(): void {
		$cleaner = new PostViewsCleaner();
		$cleaner->schedule();

		add_action( 'before_delete_post', self::invalidateCache( ... ), 10, 1 );
		add_action( 'trashed_post', self::invalidateCache( ... ), 10, 1 );
		add_action( 'transition_post_status', self::invalidateCacheOnStatusTransition( ... ), 10, 3 );
	}

	/* ---------- PUBLIC ------------------------------------------- */

	/**
	 * @param int    $postId
	 * @param string $ip
	 *
	 * @return int|\WP_Error
	 */
	public static function recordView( int $postId, string $ip ): int|\WP_Error {
		$now      = time();
		$packedIp = inet_pton( $ip );

		if ( false === $packedIp ) {
			return new \WP_Error( 'invalid_client_ip', 'Invalid client IP address.', [ 'status' => 400 ] );
		}

		$cooldown = self::viewCooldown();

		// Atomic upsert: INSERT on first visit, UPDATE on revisit.
		// If cooldown has NOT expired: only refresh last_view (no count increment).
		// If cooldown HAS expired: increment view_count and refresh last_view.
		$result = DB::upsert(
			self::TABLE,
			[
				'post_id'    => $postId,
				'ip'         => $packedIp,
				'last_view'  => $now,
				'view_count' => 1,
			],
			[
				'view_count' => "`view_count` + IF(({$now} - `last_view`) >= {$cooldown}, 1, 0)",
				'last_view'  => (string) $now,
			]
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		self::invalidateCache( $postId );

		return $result;
	}

	/**
	 * @param int $postId
	 *
	 * @return int
	 */
	public static function getTotalViews( int $postId ): int {
		$cacheKey = self::cacheKey( $postId );

		$cached = wp_cache_get( $cacheKey, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$tableName = DB::backtickedTable( self::TABLE );
		$total     = (int) ( DB::db()->get_var(
			DB::db()->prepare( "SELECT SUM(view_count) FROM {$tableName} WHERE `post_id` = %d", $postId )
		) ?: 0 );

		wp_cache_set( $cacheKey, $total, self::CACHE_GROUP, 15 * MINUTE_IN_SECONDS );

		return $total;
	}

	public static function invalidateCache( int $postId ): void {
		if ( $postId <= 0 ) {
			return;
		}

		wp_cache_delete( self::cacheKey( $postId ), self::CACHE_GROUP );
	}

	public static function invalidateCacheOnStatusTransition( string $newStatus, string $oldStatus, mixed $post ): void {
		if ( $newStatus === $oldStatus || ! is_object( $post ) ) {
			return;
		}

		self::invalidateCache( (int) ( $post->ID ?? 0 ) );
	}

	private static function cacheKey( int $postId ): string {
		return self::CACHE_PREFIX . $postId;
	}

	private static function viewCooldown(): int {
		/*
		 * Lower cooldown values count repeat visits sooner, which makes analytics
		 * more sensitive to refreshes and increases write amplification.
		 */
		return max( 0, (int) apply_filters( 'hd_post_view_cooldown', self::VIEW_COOLDOWN ) );
	}
}
