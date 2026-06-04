<?php
/**
 * Theme Bootstrap — Main Bootloader.
 *
 * Manages the boot sequence:
 * 1. Theme frontend (supports, widgets, assets)
 * 2. Native Features (explicit, always loaded)
 * 3. Core services (Cache, Cron, RateLimitStorage)
 * 4. Auto-discovered Modules (plugin integrations + custom)
 * 5. REST API (access control + custom endpoints)
 *
 * @package SPL
 */

namespace SPL;

use SPL\Contracts\Feature;
use SPL\Contracts\HasAdminContext;
use SPL\Core\Cache;
use SPL\Core\ModuleRegistry;
use SPL\Core\RateLimitStorage;
use SPL\Features;
use SPL\API\API;

defined( 'ABSPATH' ) || exit;

final class Bootstrap {

	private static bool $booted = false;

	/**
	 * Native features — explicit, always loaded.
	 * Order matters: dependencies first.
	 *
	 * @var array<class-string<Feature>>
	 */
	private const FEATURES = [
		Features\Optimizer::class,
		Features\Customizer::class,
		Features\Admin::class,
		Features\ShortcodeLoader::class,
		Features\TemplateHooks::class,
	];

	// --------------------------------------------------

	public static function init(): void {
		if ( self::$booted ) {
			return;
		}

		self::$booted = true;

		// Theme frontend (supports, assets, ...).
		new Theme();

		// Boot services on after_setup_theme (priority 11).
		add_action( 'after_setup_theme', self::bootServices( ... ), 11 );

		// Hook core migrations to admin_init and after_switch_theme.
		Core\Migration::init();

		// Action hook after theme is fully loaded.
		add_action( 'after_setup_theme', self::fireThemeLoaded( ... ), 99 );
	}

	// --------------------------------------------------

	/**
	 * Boot all services — called at after_setup_theme priority 11.
	 */
	private static function bootServices(): void {
		self::bootFeatures();
		self::bootCoreServices();

		// Auto-discovered Modules (plugin integrations + custom).
		ModuleRegistry::getInstance()->discover()->boot();

		// REST API services.
		( new API() )->boot();
	}

	/**
	 * Boot native Features.
	 */
	private static function bootFeatures(): void {
		$isAdmin = \is_admin();

		foreach ( self::FEATURES as $class ) {
			/** @var Feature $feature */
			$feature = new $class();
			$feature->boot();

			if ( $isAdmin && $feature instanceof HasAdminContext ) {
				$feature->adminBoot();
			}
		}
	}

	/**
	 * Boot core services: Cache, Cron schedules, RateLimitStorage cleanup.
	 */
	private static function bootCoreServices(): void {
		Cache::boot();

		// Cron hook for RateLimitStorage custom table cleanup.
		// Scheduling is handled centrally by Core\Migration to avoid daily queries on every frontend request.
		add_action( 'spl_daily_rate_limit_cleanup', [ RateLimitStorage::class, 'cleanupDb' ] );

		// Custom cron schedules (before modules boot, so they can use 'weekly'/'monthly').
		// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
		add_filter( 'cron_schedules', [ self::class, 'addCronSchedules' ] );
	}

	/**
	 * Fire the theme loaded action.
	 * Hooked to `after_setup_theme` at priority 99.
	 */
	private static function fireThemeLoaded(): void {
		do_action( 'spl_theme_loaded' );
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array $schedules Existing cron schedules.
	 *
	 * @return array
	 */
	public static function addCronSchedules( array $schedules ): array {
		$schedules['weekly']  ??= [
			'interval' => 7 * DAY_IN_SECONDS,
			'display'  => __( 'Once Weekly', 'SPL' ),
		];
		$schedules['monthly'] ??= [
			'interval' => 30 * DAY_IN_SECONDS,
			'display'  => __( 'Once Monthly', 'SPL' ),
		];

		return $schedules;
	}
}
