<?php
/**
 * Cache utility trait.
 *
 * @author HD
 */

namespace HDAddons\Traits;

use HDAddons\DB;

\defined( 'ABSPATH' ) || exit;

trait Cache {
	// --------------------------------------------------

	/**
	 * Clear HDA-specific caches and notify popular cache plugins.
	 *
	 * Unlike the previous implementation, this does NOT nuke all site transients.
	 * WordPress transients are used by many plugins; blanket deletion causes:
	 * - performance degradation (plugins re-fetch data)
	 * - broken functionality (rate limiters, nonces, sessions)
	 *
	 * @return void
	 */
	public static function clearAllCache(): void {
		// Clear HDA's own transients only.
		self::clearHdaTransients();

		// Flush WP object cache (in-memory only, clean slate for this request).
		wp_cache_flush();

		// Notify popular cache plugins.
		self::clearCachePlugins();
	}

	// --------------------------------------------------

	/**
	 * Clear all HDA-specific transients.
	 *
	 * Only removes transients with the 'hda_' prefix — safe for other plugins.
	 *
	 * @return int Number of transients deleted.
	 */
	public static function clearHdaTransients(): int {
		$db = DB::db();

		$transient         = $db->esc_like( '_transient_hda_' ) . '%';
		$transientTimeout  = $db->esc_like( '_transient_timeout_hda_' ) . '%';
		$siteTransient     = $db->esc_like( '_site_transient_hda_' ) . '%';
		$siteTransientTime = $db->esc_like( '_site_transient_timeout_hda_' ) . '%';

		return (int) $db->query(
			$db->prepare(
				"DELETE FROM {$db->options}
				 WHERE option_name LIKE %s
				    OR option_name LIKE %s
				    OR option_name LIKE %s
				    OR option_name LIKE %s",
				$transient,
				$transientTimeout,
				$siteTransient,
				$siteTransientTime
			)
		);
	}

	// --------------------------------------------------

	/**
	 * Clear popular cache plugins.
	 *
	 * @return void
	 */
	public static function clearCachePlugins(): void {
		// WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// FlyingPress
		if ( class_exists( 'FlyingPress\\Purge' ) && method_exists( 'FlyingPress\\Purge', 'purge_everything' ) ) {
			\FlyingPress\Purge::purge_everything();
		}

		// LiteSpeed Cache
		if ( class_exists( 'LiteSpeed\\Purge' ) && method_exists( 'LiteSpeed\\Purge', 'purge_all' ) ) {
			\LiteSpeed\Purge::purge_all();
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		// Swift Performance
		if ( has_action( 'swift_performance_clear_cache' ) ) {
			do_action( 'swift_performance_clear_cache' );
		}

		// Hummingbird
		if ( has_action( 'wphb_clear_cache' ) ) {
			do_action( 'wphb_clear_cache' );
		}

		// NitroPack
		self::clearNitroPackCache();

		// WP Fastest Cache
		if ( function_exists( 'wpfc_clear_all_cache' ) ) {
			wpfc_clear_all_cache();
		}

		// Autoptimize
		if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
			\autoptimizeCache::clearall();
		}
	}

	// --------------------------------------------------

	/**
	 * Clear NitroPack cache safely.
	 *
	 * @return void
	 */
	private static function clearNitroPackCache(): void {
		if ( ! class_exists( 'NitroPack\\SDK\\NitroPack' ) ) {
			return;
		}

		if ( ! self::checkPluginActive( 'nitropack/main.php' ) ) {
			return;
		}

		try {
			$nitropack = \get_nitropack();
			if ( $nitropack && is_object( $nitropack ) && method_exists( $nitropack, 'purgeCache' ) ) {
				$nitropack->purgeCache();
			}
		} catch ( \Throwable $e ) {
			self::errorLog( '[NitroPack] Cache clear failed: ' . $e->getMessage() );
		}
	}
}
