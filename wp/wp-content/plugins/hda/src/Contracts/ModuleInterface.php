<?php
/**
 * Core module interface.
 *
 * Every HDA module MUST implement this interface to be auto-discovered
 * by ModuleRegistry. Static methods allow metadata access without instantiation.
 *
 * boot() is the ONLY entry point — if a module is disabled, boot() is never
 * called, resulting in zero footprint (no hooks, no assets, no queries).
 *
 * @package HDAddons\Contracts
 */

namespace HDAddons\Contracts;

defined( 'ABSPATH' ) || exit;

interface ModuleInterface {

	// ── Self-Description ──────────────────────────────

	/** Unique slug used as config key and checkbox ID. */
	public static function slug(): string;

	/** Human-readable title for settings UI. */
	public static function title(): string;

	/** Short description for module cards. */
	public static function description(): string;

	/** Group key matching config/groups.php for sidebar organization. */
	public static function group(): string;

	// ── Data Declaration ──────────────────────────────

	/**
	 * Primary wp_options key for this module.
	 *
	 * Convention: `hda_{slug}`. AbstractModule auto-generates this.
	 */
	public static function optionKey(): string;

	/**
	 * All option keys owned by this module.
	 *
	 * Used for uninstall data deletion.
	 *
	 * @return string[]
	 */
	public static function optionKeys(): array;

	/**
	 * Default option values.
	 *
	 * Merged with saved options via getOptions() — ensures
	 * options array is always complete (FlyingPress pattern).
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array;

	/**
	 * Cron hook names registered by this module.
	 *
	 * Used on deactivation to unschedule all hooks in one pass.
	 *
	 * @return string[]
	 */
	public static function cronHooks(): array;

	/**
	 * Cron schedules: hook name → WP recurrence string.
	 *
	 * Replaces the fragile string-matching heuristic in Activator.
	 * Example: `['hda_threat_intel_sync' => 'daily']`.
	 *
	 * Default implementation in AbstractModule derives from cronHooks()
	 * for backward compatibility.
	 *
	 * @return array<string, string>
	 */
	public static function cronSchedules(): array;

	/**
	 * Whether this module cannot be toggled off.
	 *
	 * alwaysActive modules (e.g., GlobalSetting) are booted
	 * regardless of checkbox state and hidden from toggle list.
	 */
	public static function alwaysActive(): bool;

	// ── Self-Hooking ────────────────────────────────

	/**
	 * Register ALL WordPress hooks for this module.
	 *
	 * This is the ONLY entry point. Module OFF → boot() NOT called → zero footprint.
	 * Replaces the old constructor-based hook registration pattern.
	 */
	public function boot(): void;
}
