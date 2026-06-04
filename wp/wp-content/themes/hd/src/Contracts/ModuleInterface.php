<?php
/**
 * ModuleInterface — auto-discovered, conditionally loaded.
 *
 * Modules are PLUGIN INTEGRATIONS or PROJECT-SPECIFIC features:
 * - Plugin integrations: isActive() checks plugin availability.
 * - Project features: isActive() returns true (always-on).
 * - Theme works correctly without any Module loaded.
 *
 * Auto-discovered by ModuleRegistry via Composer classmap under HD\Modules\.
 * Modules may expose REST controllers through apiClasses(); core features must
 * register their own hooks explicitly instead.
 *
 * @see \HD\Modules\AbstractModule for base implementation with option helpers.
 * @see \HD\Contracts\Feature for core theme infrastructure.
 *
 * @package HD\Contracts
 */

namespace HD\Contracts;

defined( 'ABSPATH' ) || exit;

interface ModuleInterface extends Bootable {
	/** Unique slug for identification (e.g. 'acf', 'post-view'). */
	public static function slug(): string;

	/**
	 * Whether this module should be loaded.
	 *
	 * Plugin integrations: check class_exists() / function_exists().
	 * Always-on modules: return true.
	 */
	public static function isActive(): bool;

	/**
	 * REST API controllers owned by this module.
	 *
	 * @return list<class-string<\WP_REST_Controller>>
	 */
	public static function apiClasses(): array;
}
