<?php
/**
 * Rate Limit Storage — Hybrid storage for tracking counters and small transient data.
 *
 * Automatically uses Memcached/Redis via Transients if available (fastest).
 * Falls back to a custom, lightweight VARBINARY(16) MySQL table if object cache
 * is missing, protecting the main 'wp_options' table from getting text-bloated by botnets.
 *
 * @package HDAddons\Core
 * @author  HD
 */

namespace HDAddons\Core;

use HDAddons\DB;

\defined( 'ABSPATH' ) || exit;

final class RateLimitStorage {

	public const TABLE_NAME = 'hda_rate_limits';

	// -------------------------------------------------------------------------

	/**
	 * Increment a counter for an IP and action.
	 *
	 * @param string $ip            Client IP address.
	 * @param string $action        Context/Action (e.g., '404', 'login_limit', 'global_fw').
	 * @param int    $windowSeconds Expiration window in seconds.
	 *
	 * @return int The current hit count (after increment).
	 */
	public static function increment( string $ip, string $action, int $windowSeconds ): int {
		if ( wp_using_ext_object_cache() ) {
			$key   = self::transientKey( $ip, $action );
			$value = get_transient( $key );

			// Handle backward compatibility: old int values or new array structure.
			if ( false === $value ) {
				// First hit: initialize window.
				$data = [
					'count'        => 1,
					'window_start' => time(),
				];
				set_transient( $key, $data, $windowSeconds );

				return 1;
			}

			// Backward compatibility: convert old int format to new array format.
			if ( is_int( $value ) ) {
				$data = [
					'count'        => $value + 1,
					'window_start' => time(),
				];
				set_transient( $key, $data, $windowSeconds );

				return $data['count'];
			}

			// New array format.
			$currentTime = time();
			$windowStart = $value['window_start'] ?? $currentTime;
			$count       = $value['count'] ?? 0;

			// Check if we're still within the original fixed window.
			if ( $currentTime - $windowStart < $windowSeconds ) {
				// Within same window: increment count, preserve window_start.
				++$count;
				$data = [
					'count'        => $count,
					'window_start' => $windowStart,
				];
				// Calculate remaining TTL to avoid resetting the window.
				$remainingTtl = $windowSeconds - ( $currentTime - $windowStart );
				set_transient( $key, $data, max( 1, $remainingTtl ) );

				return $count;
			}

			// Window expired: start new window.
			$data = [
				'count'        => 1,
				'window_start' => $currentTime,
			];
			set_transient( $key, $data, $windowSeconds );

			return 1;
		}

		$ipBin = inet_pton( $ip );

		if ( false === $ipBin ) {
			return 0; // Invalid IP.
		}

		$expires = gmdate( 'Y-m-d H:i:s', time() + $windowSeconds );

		// Single query via upsert + LAST_INSERT_ID trick.
		// DB::raw() marks expressions that should NOT be escaped by prepare().
		DB::upsert(
			self::TABLE_NAME,
			[
				'ip_address' => $ipBin,
				'action'     => $action,
				'hits'       => DB::raw( 'LAST_INSERT_ID(1)' ),
				'data'       => '',
				'expires_at' => $expires,
			],
			[
				'hits'       => 'LAST_INSERT_ID(IF(expires_at <= UTC_TIMESTAMP(), 1, hits + 1))',
				'expires_at' => 'IF(expires_at <= UTC_TIMESTAMP(), VALUES(expires_at), expires_at)',
			]
		);

		return (int) DB::db()->insert_id;
	}

	/**
	 * Get the current counter hits for an IP and action.
	 *
	 * @param string $ip     Client IP address.
	 * @param string $action Context/Action.
	 *
	 * @return int The current hit count (0 if expired).
	 */
	public static function get( string $ip, string $action ): int {
		if ( wp_using_ext_object_cache() ) {
			$value = get_transient( self::transientKey( $ip, $action ) );

			// Backward compatibility: handle both int and array formats.
			if ( is_int( $value ) ) {
				return $value;
			}

			if ( is_array( $value ) && isset( $value['count'] ) ) {
				return (int) $value['count'];
			}

			return 0;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );
		$ipBin = inet_pton( $ip );

		if ( false === $ipBin ) {
			return 0;
		}

		$hits = $db->get_var(
			$db->prepare(
				"SELECT hits FROM {$table} WHERE ip_address = %s AND action = %s AND expires_at > %s",
				$ipBin,
				$action,
				gmdate( 'Y-m-d H:i:s' )
			)
		);

		return (int) $hits;
	}

	/**
	 * Update the expiration window for an existing counter without incrementing.
	 * Useful for escalating bans.
	 *
	 * @param string $ip            Client IP.
	 * @param string $action        Context/Action.
	 * @param int    $windowSeconds New expiration window.
	 */
	public static function extendExpiration( string $ip, string $action, int $windowSeconds ): void {
		if ( wp_using_ext_object_cache() ) {
			$key   = self::transientKey( $ip, $action );
			$value = get_transient( $key );

			if ( false === $value ) {
				return;
			}

			// Backward compatibility: handle both int and array formats.
			if ( is_int( $value ) ) {
				if ( $value > 0 ) {
					set_transient( $key, $value, $windowSeconds );
				}

				return;
			}

			if ( is_array( $value ) && isset( $value['count'] ) ) {
				$count = (int) $value['count'];
				if ( $count > 0 ) {
					// Extend expiration but keep the original window_start.
					$data = [
						'count'        => $count,
						'window_start' => $value['window_start'] ?? time(),
					];
					set_transient( $key, $data, $windowSeconds );
				}
			}

			return;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );
		$ipBin = inet_pton( $ip );

		if ( false === $ipBin ) {
			return; // Invalid IP.
		}

		$expires = gmdate( 'Y-m-d H:i:s', time() + $windowSeconds );

		$db->update(
			$table,
			[ 'expires_at' => $expires ],
			[
				'ip_address' => $ipBin,
				'action'     => $action,
			],
			[ '%s' ],
			[ '%s', '%s' ]
		);
	}

	// -------------------------------------------------------------------------
	// Key-Value String Caching (e.g. Crawler Whitelist lookup caching)
	// -------------------------------------------------------------------------

	/**
	 * Cache a string value for an IP and action.
	 *
	 * @param string $ip            Client IP address.
	 * @param string $action        Context/Action.
	 * @param string $data          Value to cache (max 64 bytes).
	 * @param int    $windowSeconds Expiration window in seconds.
	 */
	public static function setString( string $ip, string $action, string $data, int $windowSeconds ): void {
		if ( wp_using_ext_object_cache() ) {
			set_transient( self::transientKey( $ip, $action ), $data, $windowSeconds );

			return;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );
		$ipBin = inet_pton( $ip );

		if ( false === $ipBin ) {
			return;
		}

		$expires = gmdate( 'Y-m-d H:i:s', time() + $windowSeconds );

		DB::upsert(
			self::TABLE_NAME,
			[
				'ip_address' => $ipBin,
				'action'     => $action,
				'hits'       => 1,
				'data'       => mb_substr( $data, 0, 64 ),
				'expires_at' => $expires,
			],
			[
				'data'       => 'VALUES(data)',
				'expires_at' => 'VALUES(expires_at)',
			]
		);
	}

	/**
	 * Get a cached string value for an IP and action.
	 *
	 * @param string $ip     Client IP address.
	 * @param string $action Context/Action.
	 *
	 * @return string|null Cached string value, or null if expired/not found.
	 */
	public static function getString( string $ip, string $action ): ?string {
		if ( wp_using_ext_object_cache() ) {
			$value = get_transient( self::transientKey( $ip, $action ) );

			return is_string( $value ) ? $value : null;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );
		$ipBin = inet_pton( $ip );

		if ( false === $ipBin ) {
			return null;
		}

		$data = $db->get_var(
			$db->prepare(
				"SELECT data FROM {$table} WHERE ip_address = %s AND action = %s AND expires_at > %s",
				$ipBin,
				$action,
				gmdate( 'Y-m-d H:i:s' )
			)
		);

		return null !== $data ? (string) $data : null;
	}

	// -------------------------------------------------------------------------

	/**
	 * Reset/delete the counter or string tracking for an IP and action.
	 *
	 * @param string $ip     Client IP address.
	 * @param string $action Context/Action.
	 */
	public static function reset( string $ip, string $action ): void {
		if ( wp_using_ext_object_cache() ) {
			delete_transient( self::transientKey( $ip, $action ) );

			return;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );
		$ipBin = inet_pton( $ip );

		if ( false === $ipBin ) {
			return;
		}

		$db->delete(
			$table,
			[
				'ip_address' => $ipBin,
				'action'     => $action,
			],
			[ '%s', '%s' ]
		);
	}

	/**
	 * Cleanup expired custom DB rows.
	 * Called via daily cron setup in Plugin.
	 */
	public static function cleanupDb(): void {
		if ( wp_using_ext_object_cache() ) {
			return;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );
		$db->query(
			$db->prepare(
				"DELETE FROM {$table} WHERE expires_at < %s",
				gmdate( 'Y-m-d H:i:s' )
			)
		);
	}

	// -------------------------------------------------------------------------

	/**
	 * Generate a transient key for external object caches.
	 */
	private static function transientKey( string $ip, string $action ): string {
		return 'hda_rl_' . substr( md5( $action . '_' . $ip ), 0, 16 );
	}
}
