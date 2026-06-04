<?php
/**
 * Centralized DB Migration for HDA plugin.
 *
 * Core tables (rate_limits) are defined inline.
 * Module tables are auto-collected via HasDatabaseSchema interface.
 * Version-gated to HDA_VERSION — runs once per plugin update.
 *
 * @package HDAddons
 */

namespace HDAddons;

use HDAddons\Core\ModuleRegistry;
use HDAddons\Core\RateLimitStorage;
use HDAddons\Modules\LoginSecurity\Totp\TotpHandler;

\defined( 'ABSPATH' ) || exit;

final class Migration {
	private const DB_VERSION_OPTION = 'hda_db_version';

	public static function init(): void {
		add_action( 'admin_init', self::run( ... ) );
	}

	public static function run(): void {
		$installedVersion = Helper::getOption( self::DB_VERSION_OPTION, '0.0.0' );

		// Early return.
		if ( $installedVersion === HDA_VERSION ) {
			return;
		}

		if ( version_compare( $installedVersion, HDA_VERSION, '<' ) ) {
			self::createTables();
			self::runDataMigrations( $installedVersion );
			Helper::updateOption( self::DB_VERSION_OPTION, HDA_VERSION );
		}
	}

	/**
	 * Create/update ALL plugin tables via dbDelta.
	 *
	 * Core tables are defined inline. Module tables are auto-collected
	 * from modules implementing HasDatabaseSchema.
	 */
	public static function createTables(): void {

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
		$registry = ModuleRegistry::getInstance();
		if ( empty( $registry->all() ) ) {
			$registry->discover();
		}

		foreach ( $registry->collectSchemas() as $table => $sql ) {
			DB::createTable( $table, $sql );
		}

		DB::clearSchemaCache();
	}

	/**
	 * Run version-gated data migrations.
	 */
	private static function runDataMigrations( string $installedVersion ): void {
		if ( version_compare( $installedVersion, '2.3.9', '<' ) ) {
			self::ensureTotpSecretColumn();
			TotpHandler::migratePlaintextSecrets();
		}
	}

	/**
	 * Ensure encrypted TOTP secrets cannot be truncated on upgraded installs.
	 */
	private static function ensureTotpSecretColumn(): void {
		if ( ! DB::tableExists( TotpHandler::TABLE_NAME ) ) {
			return;
		}

		$db     = DB::db();
		$table  = DB::tableNameFull( TotpHandler::TABLE_NAME );
		$column = $db->get_row( $db->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'secret' ), ARRAY_A );

		if ( ! $column ) {
			return;
		}

		$type = strtolower( (string) ( $column['Type'] ?? '' ) );
		if ( 'text' === $type || str_ends_with( $type, 'text' ) ) {
			return;
		}

		$db->query( "ALTER TABLE {$table} MODIFY secret text NOT NULL" );
		DB::clearSchemaCache( TotpHandler::TABLE_NAME );
	}

	/**
	 * Drop ALL plugin tables.
	 * Called during clean uninstall.
	 */
	public static function dropTables(): void {
		DB::dropTable( RateLimitStorage::TABLE_NAME );

		$registry = ModuleRegistry::getInstance();
		if ( empty( $registry->all() ) ) {
			$registry->discover();
		}

		foreach ( array_keys( $registry->collectSchemas() ) as $table ) {
			DB::dropTable( $table );
		}
	}
}
