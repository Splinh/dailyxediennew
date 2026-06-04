<?php
/**
 * Core DB Migration for HD Theme.
 *
 * Centralized schema management for all theme tables.
 * Version-gated to avoid redundant DB queries.
 *
 * @package HD\Core
 */

namespace HD\Core;

defined( 'ABSPATH' ) || exit;

final class Migration {
	private const DB_VERSION_OPTION      = 'hd_core_db_version';
	private const CURRENT_VERSION        = '1.1.0';
	private const MODULE_VERSIONS_OPTION = 'hd_module_db_versions';

	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'run' ] );
		// Ensure it also runs when theme is switched/activated.
		add_action( 'after_switch_theme', [ self::class, 'run' ] );
	}

	public static function run(): void {
		$installedVersion = Helper::getOption( self::DB_VERSION_OPTION, '0.0.0' );

		if ( version_compare( $installedVersion, self::CURRENT_VERSION, '<' ) ) {
			self::createTables();

			if ( ! wp_next_scheduled( 'hd_daily_rate_limit_cleanup' ) ) {
				wp_schedule_event( time(), 'daily', 'hd_daily_rate_limit_cleanup' );
			}

			Helper::updateOption( self::DB_VERSION_OPTION, self::CURRENT_VERSION );
		}

		// Module-owned migrations run independently (cheap: option read + version compare).
		self::runModuleMigrations();
	}

	private static function createTables(): void {
		// ── Core: Rate Limit Storage ──
		DB::createTable(
			RateLimitStorage::TABLE_NAME,
			<<<'SQL'
			ip_address varbinary(16) NOT NULL,
			action varchar(32) NOT NULL,
			hits int unsigned NOT NULL DEFAULT 1,
			data varchar(64) NOT NULL DEFAULT '',
			expires_at datetime NOT NULL,
			PRIMARY KEY  (ip_address, action),
			KEY idx_expires (expires_at)
			SQL
		);

		// ── Module-owned tables (auto-collected via HasDatabaseSchema) ──
		foreach ( ModuleRegistry::getInstance()->collectSchemas() as $table => $sql ) {
			DB::createTable( $table, $sql );
		}

		DB::clearSchemaCache();
	}

	/**
	 * Run versioned migrations from modules implementing HasMigrations.
	 *
	 * Each module tracks its own version independently.
	 * Migrations execute in semver order; failure halts that module's chain.
	 */
	private static function runModuleMigrations(): void {
		$allMigrations = ModuleRegistry::getInstance()->collectMigrations();
		if ( ! $allMigrations ) {
			return;
		}

		$storedVersions = (array) Helper::getOption( self::MODULE_VERSIONS_OPTION, [] );
		$updated        = false;

		foreach ( $allMigrations as $slug => $migrations ) {
			$currentVersion = $storedVersions[ $slug ] ?? '0.0.0';

			// Sort by semver ascending.
			uksort( $migrations, 'version_compare' );

			foreach ( $migrations as $version => $callback ) {
				if ( version_compare( $version, $currentVersion, '<=' ) ) {
					continue;
				}

				try {
					$callback();
					$storedVersions[ $slug ] = $version;
					$updated                 = true;
				} catch ( \Throwable $e ) {
					wp_trigger_error(
						__METHOD__,
						"Module '{$slug}' migration {$version} failed: " . $e->getMessage(),
						E_USER_WARNING
					);

					// Stop further migrations for this module — later ones may depend on this.
					break;
				}
			}
		}

		if ( $updated ) {
			Helper::updateOption( self::MODULE_VERSIONS_OPTION, $storedVersions );
		}
	}
}
