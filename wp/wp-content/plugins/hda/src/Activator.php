<?php
/**
 * Plugin activation, deactivation, and uninstall handlers.
 *
 * Uses ModuleRegistry for auto-collecting tables, cron hooks, and option keys.
 * Adding a new module? → Just implement the interfaces, run `composer dump-autoload`.
 *
 * @author HD
 */

namespace HDAddons;

use HDAddons\Core\ModuleRegistry;
use HDAddons\Modules\GlobalSetting\GlobalSetting;
use HDAddons\Modules\Security\ServerConfig\ServerConfig;

\defined( 'ABSPATH' ) || exit;

final class Activator {

	// ─── Lifecycle Handlers ─────────────────────────────

	/**
	 * Run on plugin activation.
	 */
	public static function activation(): void {
		ModuleRegistry::invalidateCache();
		Plugin::addCapability();

		// Create/update all DB tables via centralized Migration.
		Migration::createTables();

		// Core daily cleanup.
		if ( ! wp_next_scheduled( 'hda_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'hda_daily_cleanup' );
		}

		// Schedule all module-declared cron hooks.
		self::scheduleModuleCrons();

		flush_rewrite_rules();
	}

	/**
	 * Schedule cron hooks declared by all modules.
	 *
	 * Uses explicit cronSchedules() when available.
	 * Falls back to string heuristic for un-migrated modules.
	 */
	private static function scheduleModuleCrons(): void {
		$registry = ModuleRegistry::getInstance();
		$registry->discover();

		// Explicit schedules take priority.
		$explicitSchedules = $registry->getAllCronSchedules();

		foreach ( $registry->getAllCronHooks() as $hook ) {
			if ( wp_next_scheduled( $hook ) ) {
				continue;
			}

			// Prefer explicit schedule, fall back to string heuristic.
			$recurrence = $explicitSchedules[ $hook ] ?? match ( true ) {
				str_contains( $hook, '_daily' ), str_contains( $hook, '_sync' ) => 'daily',
				str_contains( $hook, '_weekly' )                                => 'weekly',
				default                                                         => 'monthly',
			};

			wp_schedule_event( time(), $recurrence, $hook );
		}
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivation(): void {
		$registry = ModuleRegistry::getInstance();
		$registry->discover();

		// Unschedule all module cron hooks + core daily cleanup.
		foreach ( $registry->getAllCronHooks() as $hook ) {
			wp_unschedule_hook( $hook );
		}
		wp_unschedule_hook( 'hda_daily_cleanup' );

		// Restore server config files.
		self::restoreServerConfig();

		flush_rewrite_rules();
	}

	/**
	 * Run on plugin uninstall.
	 *
	 * Behavior controlled by the "clean_uninstall" option in GlobalSetting.
	 * If disabled, all data is preserved for future reinstall.
	 */
	public static function uninstall(): void {
		$hdaConfig = Helper::getOption( GlobalSetting::OPTION_NAME, [] );

		if ( empty( $hdaConfig[ GlobalSetting::KEY_CLEAN_UNINSTALL ] ) ) {
			return;
		}

		$registry = ModuleRegistry::getInstance();
		$registry->discover();

		self::dropAllTables();
		self::deleteAllOptions( $registry );
		self::deleteStoragePosts();
		self::deleteTransients();

		Plugin::removeCapability();
	}

	// ─── Private Helpers ────────────────────────────────

	/**
	 * Unlock files and remove server config blocks (.htaccess / nginx).
	 */
	private static function restoreServerConfig(): void {
		if ( ! class_exists( ServerConfig::class ) ) {
			return;
		}

		try {
			ServerConfig::unlockFiles();
			ServerConfig::removeBlock( ServerConfig::MARKER );
			ServerConfig::removeBlock( ServerConfig::XMLRPC_MARKER );
			ServerConfig::removeBlock( ServerConfig::OPML_MARKER );
		} catch ( \Exception $e ) {
			Helper::errorLog( '[HDA] Deactivation: ' . $e->getMessage() );
		}
	}

	private static function dropAllTables(): void {
		Migration::dropTables();
	}

	/**
	 * Delete all module options + global config keys.
	 */
	private static function deleteAllOptions( ModuleRegistry $registry ): void {
		$optionMap = $registry->getOptionMap();

		foreach ( $optionMap as $slug => $optionNames ) {
			$isStoredOption = in_array( $slug, GlobalSetting::STORED_OPTION_MODULES, true );

			foreach ( $optionNames as $optionName ) {
				if ( $isStoredOption ) {
					self::deleteStoredOption( $optionName );
				} else {
					Helper::removeOption( $optionName );
				}
			}
		}

		Helper::removeOption( GlobalSetting::OPTION_NAME );
		Helper::removeOption( Plugin::KEY_CAP_VERSION );

		// Delete all hda_so_id_* lookup options.
		$db = DB::db();
		$db->query(
			$db->prepare(
				"DELETE FROM {$db->options} WHERE option_name LIKE %s",
				$db->esc_like( 'hda_so_id_' ) . '%'
			)
		);
	}

	/**
	 * Delete all hda_storage posts.
	 */
	private static function deleteStoragePosts(): void {
		$db      = DB::db();
		$postIds = $db->get_col(
			$db->prepare(
				"SELECT ID FROM {$db->posts} WHERE post_type = %s",
				'hda_storage'
			)
		);

		foreach ( $postIds as $postId ) {
			wp_delete_post( (int) $postId, true );
		}
	}

	/**
	 * Delete all HDA transients.
	 */
	private static function deleteTransients(): void {
		$db = DB::db();
		$db->query(
			$db->prepare(
				"DELETE FROM {$db->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$db->esc_like( '_transient_hda_' ) . '%',
				$db->esc_like( '_transient_timeout_hda_' ) . '%'
			)
		);
	}

	/**
	 * Delete a stored option post by its lookup key.
	 */
	private static function deleteStoredOption( string $optionKey ): void {
		$postId = Helper::getOption( "hda_so_id_{$optionKey}", 0 );

		if ( $postId > 0 ) {
			wp_delete_post( (int) $postId, true );
			Helper::removeOption( "hda_so_id_{$optionKey}" );
		}
	}
}
