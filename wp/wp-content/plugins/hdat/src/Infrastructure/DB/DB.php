<?php
/**
 * @package HDAT\Infrastructure\DB
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Static DB utility — table-name normalization, schema introspection,
 * and `dbDelta`-based table creation.
 *
 * Mirrors the API of `HDAT\DB` from the legacy hd-ai-toolkit plugin so a
 * future merge with `themes/hd` and `plugins/hda` can swap implementations
 * without touching call sites. Repositories register their short table name
 * via a `TABLE` class constant (e.g. `'hdat_ai_keys'`); this helper turns
 * that into a fully prefixed, backticked identifier on demand.
 */
final class DB {

	/** @var array<string, string[]> column-name cache keyed by full table name. */
	private static array $schemaCache = [];

	public static function db(): \wpdb {
		global $wpdb;
		return $wpdb;
	}

	public static function sanitizeIdentifier( string $identifier ): string {
		return (string) preg_replace( '/\W/', '', $identifier );
	}

	/**
	 * Full table name with WordPress prefix (no backticks).
	 */
	public static function tableNameFull( string $table ): string {
		return self::db()->prefix . self::sanitizeIdentifier( $table );
	}

	public static function backtickedTable( string $table ): string {
		return '`' . self::tableNameFull( $table ) . '`';
	}

	public static function backtickedColumn( string $column ): string {
		return '`' . self::sanitizeIdentifier( $column ) . '`';
	}

	public static function getCharsetCollate(): string {
		return self::db()->get_charset_collate();
	}

	public static function tableExists( string $tableName ): bool {
		$full   = self::tableNameFull( $tableName );
		$result = self::db()->get_var( self::db()->prepare( 'SHOW TABLES LIKE %s', $full ) );
		return $result === $full;
	}

	/**
	 * @return string[] Column names; empty array if the table doesn't exist
	 *                  or describe failed.
	 */
	public static function getTableColumns( string $tableName ): array {
		$cacheKey = self::tableNameFull( $tableName );
		if ( isset( self::$schemaCache[ $cacheKey ] ) ) {
			return self::$schemaCache[ $cacheKey ];
		}

		$table = self::backtickedTable( $tableName );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$rows = self::db()->get_results( "SHOW COLUMNS FROM {$table}", ARRAY_A );

		$cols = is_array( $rows ) ? array_map( static fn( $r ) => (string) ( $r['Field'] ?? '' ), $rows ) : [];

		self::$schemaCache[ $cacheKey ] = $cols;

		return $cols;
	}

	public static function tableHasColumn( string $tableName, string $column ): bool {
		return in_array(
			self::sanitizeIdentifier( $column ),
			self::getTableColumns( $tableName ),
			true
		);
	}

	/**
	 * Run dbDelta against a single table.
	 *
	 * `$schema` is the inner column/key block — no surrounding `CREATE TABLE`,
	 * no parentheses, no engine/collation suffix. We add those here so every
	 * call site uses the same layout.
	 */
	public static function createTable( string $table, string $schema ): void {
		if ( '' === $table || '' === $schema ) {
			return;
		}

		$collate         = self::getCharsetCollate();
		$backtickedTable = self::backtickedTable( $table );
		$sql             = sprintf( 'CREATE TABLE %s ( %s ) ENGINE=InnoDB %s;', $backtickedTable, $schema, $collate );

		$upgradeFile = ABSPATH . 'wp-admin/includes/upgrade.php';
		if ( is_file( $upgradeFile ) ) {
			require_once $upgradeFile;
		}

		dbDelta( $sql );

		self::clearSchemaCache( $table );
	}

	public static function clearSchemaCache( ?string $tableName = null ): void {
		if ( null === $tableName ) {
			self::$schemaCache = [];
			return;
		}
		unset( self::$schemaCache[ self::tableNameFull( $tableName ) ] );
	}
}
