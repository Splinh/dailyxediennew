<?php
/**
 * Base class for all theme Modules.
 *
 * Provides sensible defaults for ModuleInterface and common option helpers.
 * Subclasses MUST implement: slug(), boot().
 * Override isActive() to check external plugin dependency.
 *
 * @package SPL\Modules
 * @author  HD
 */

namespace SPL\Modules;

use SPL\Contracts\ModuleInterface;
use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

abstract class AbstractModule implements ModuleInterface {

	/**
	 * In-memory option cache, keyed by FQCN.
	 *
	 * @var array<class-string, array>
	 */
	private static array $optionCache = [];

	// ── Defaults (override as needed) ────────────────

	/**
	 * Default: module is always active.
	 * Override for plugin integrations: return Helper::isAcfActive();
	 */
	public static function isActive(): bool {
		return true;
	}

	/**
	 * Auto-generate wp_options key from slug.
	 * Convention: `hd_{slug}` (hyphens → underscores).
	 */
	public static function optionKey(): string {
		return 'hd_' . str_replace( '-', '_', static::slug() );
	}

	/**
	 * Default option values. Override per module.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return [];
	}

	// ── Option Helpers ───────────────────────────────

	/**
	 * Get merged options: defaults + saved values.
	 *
	 * @return array<string, mixed>
	 */
	public static function getOptions(): array {
		return array_merge( static::defaults(), Helper::getOption( static::optionKey(), [] ) );
	}

	/**
	 * Get cached module options (lazy-loaded, per-request).
	 *
	 * @return array<string, mixed>
	 */
	public static function getCachedOptions(): array {
		$class = static::class;

		if ( ! isset( self::$optionCache[ $class ] ) ) {
			self::$optionCache[ $class ] = static::getOptions();
		}

		return self::$optionCache[ $class ];
	}

	/**
	 * Reset in-memory cache (call after save/update).
	 */
	public static function resetCache(): void {
		unset( self::$optionCache[ static::class ] );
	}

	// ── Module-Owned API ─────────────────────────────

	/**
	 * REST API controllers owned by this module.
	 * ModuleRegistry auto-registers routes on rest_api_init.
	 *
	 * Override to return controller class names.
	 *
	 * @return list<class-string<\WP_REST_Controller>>
	 */
	public static function apiClasses(): array {
		return [];
	}
}
