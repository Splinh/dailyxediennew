<?php
/**
 * Theme Helper Utilities
 *
 * This file defines the Helper class, a static utility class that provides
 * commonly used helper methods for various theme functionalities.
 * It centralizes reusable logic such as data formatting, template helpers,
 * and other generic utility operations.
 *
 * @author HD
 */

namespace SPL\Core;

use SPL\Traits\DateTime;
use SPL\Traits\Embed;
use SPL\Traits\Encryption;
use SPL\Traits\Minification;
use SPL\Traits\Misc;
use SPL\Traits\Str;
use SPL\Traits\WpAcf;
use SPL\Traits\WpMedia;
use SPL\Traits\WpOptions;
use SPL\Traits\WpTemplate;

defined( 'ABSPATH' ) || exit;

final class Helper {
	// Merged utility traits
	use Str;          // String, Array, Generator, URL, Validation
	use Misc;         // Base, File, WpMisc, WpNavigation

	// Domain-specific traits
	use DateTime;
	use Embed;
	use Encryption;
	use Minification;
	use WpAcf;
	use WpMedia;
	use WpOptions;
	use WpTemplate;

	// -------------------------------------------------------------

	/**
	 * @param string $name
	 * @param array $defaultValue
	 *
	 * @return mixed
	 */
	public static function filterSettingOptions( string $name, array $defaultValue = [] ): mixed {
		$filters = apply_filters( 'spl_settings_filter', [] );

		if ( ! isset( $filters[ $name ] ) ) {
			return $defaultValue;
		}

		return $filters[ $name ] ?: $defaultValue;
	}

	// -------------------------------------------------------------

	/**
	 * @return string
	 */
	public static function currentLanguage(): string {
		// Polylang
		if ( function_exists( 'pll_current_language' ) ) {
			$lang = \pll_current_language( 'slug' );
			if ( ! empty( $lang ) ) {
				return $lang;
			}
		}

		// Weglot
		if ( function_exists( 'weglot_get_current_language' ) ) {
			$lang = \weglot_get_current_language();
			if ( ! empty( $lang ) ) {
				return $lang;
			}
		}

		// WPML
		$currentLanguage = apply_filters( 'wpml_current_language', null );

		return $currentLanguage ?: strtolower( substr( get_bloginfo( 'language' ), 0, 2 ) );
	}

	// --------------------------------------------------

	/**
	 * Clear all caches.
	 *
	 * Theme handles basic WordPress cache (transients + object cache).
	 * Plugin can hook into 'hd_clear_all_cache' for comprehensive clearing (cache plugins).
	 *
	 * @return void
	 */
	public static function clearAllCache(): void {
		self::clearTransients();
		self::flushObjectCache();

		// Allow plugin to handle cache plugin clearing
		do_action( 'hd_clear_all_cache' );
	}

	// -------------------------------------------------------------

	/**
	 * Clear WordPress transients from database.
	 *
	 * When persistent object cache (Redis/Memcached) is active,
	 * transients are stored in the object cache — not wp_options.
	 * flushObjectCache() handles that case, so we skip the SQL.
	 */
	private static function clearTransients(): void {
		if ( wp_using_ext_object_cache() ) {
			return;
		}

		$db    = DB::db();
		$table = $db->options;

		$patterns = [
			$db->esc_like( '_transient_' ) . '%',
			$db->esc_like( '_transient_timeout_' ) . '%',
			$db->esc_like( '_site_transient_' ) . '%',
			$db->esc_like( '_site_transient_timeout_' ) . '%',
		];

		$conditions = [];
		$values     = [];
		foreach ( $patterns as $pattern ) {
			$conditions[] = 'option_name LIKE %s';
			$values[]     = $pattern;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic OR clauses with prepare().
		$db->query( $db->prepare( "DELETE FROM {$table} WHERE " . implode( ' OR ', $conditions ), ...$values ) );
	}

	// -------------------------------------------------------------

	/**
	 * Flush object cache.
	 */
	private static function flushObjectCache(): void {
		wp_cache_flush();
	}
}
