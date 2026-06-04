<?php
/**
 * Plugin DB Utilities – ported from HD theme Core\DB.
 *
 * @package HDAddons
 * @author  HD
 */

namespace HDAddons;

defined( 'ABSPATH' ) || exit;

final class DB {
	private static array $schemaCache   = [];
	private static ?\wpdb $wpdbInstance = null;

	/**
	 * Create a raw SQL expression for use in upsert() INSERT values.
	 *
	 * WARNING: The expression is injected as-is into SQL.
	 * Only use with trusted, hardcoded expressions — never user input.
	 *
	 * @param string $expression Raw SQL expression (e.g. 'LAST_INSERT_ID(1)').
	 *
	 * @return RawExpression
	 */
	public static function raw( string $expression ): RawExpression {
		return new RawExpression( $expression );
	}

	// --------------------------------------------------

	/**
	 * Return the wpdb instance (cached or global).
	 *
	 * @return \wpdb
	 */
	public static function db(): \wpdb {
		if ( self::$wpdbInstance instanceof \wpdb ) {
			return self::$wpdbInstance;
		}

		global $wpdb;
		self::$wpdbInstance = $wpdb;

		return $wpdb;
	}

	// --------------------------------------------------

	/**
	 * @param string $identifier
	 *
	 * @return string
	 */
	public static function sanitizeIdentifier( string $identifier ): string {
		return (string) preg_replace( '/\W/', '', $identifier );
	}

	// --------------------------------------------------

	/**
	 * @param string $table
	 *
	 * @return string
	 */
	public static function tableNameFull( string $table ): string {
		return self::db()->prefix . self::sanitizeIdentifier( $table );
	}

	// --------------------------------------------------

	/**
	 * Check if a table exists in the database.
	 *
	 * @param string $tableName Short table name (without prefix)
	 *
	 * @return bool
	 */
	public static function tableExists( string $tableName ): bool {
		$tableFull = self::tableNameFull( $tableName );
		$result    = self::db()->get_var(
			self::db()->prepare( 'SHOW TABLES LIKE %s', $tableFull )
		);

		return $result === $tableFull;
	}

	// --------------------------------------------------

	/**
	 * Helper to get charset/collation string from wpdb.
	 *
	 * @return string
	 */
	public static function getCharsetCollate(): string {
		return self::db()->get_charset_collate();
	}

	// --------------------------------------------------

	/**
	 * @param string $table
	 *
	 * @return string
	 */
	public static function backtickedTable( string $table ): string {
		return '`' . self::tableNameFull( $table ) . '`';
	}

	// --------------------------------------------------

	/**
	 * @param string $column
	 *
	 * @return string
	 */
	public static function backtickedColumn( string $column ): string {
		return '`' . self::sanitizeIdentifier( $column ) . '`';
	}

	// --------------------------------------------------

	/**
	 * Return array of column names for a table or WP_Error on failure.
	 *
	 * @param string $tableName Short table name (without prefix)
	 *
	 * @return array|\WP_Error
	 */
	public static function getTableColumns( string $tableName ): \WP_Error|array {
		$cacheKey = self::tableNameFull( $tableName );
		if ( isset( self::$schemaCache[ $cacheKey ] ) ) {
			return self::$schemaCache[ $cacheKey ];
		}

		$table = self::backtickedTable( $tableName );

		$rows = self::db()->get_results( "SHOW COLUMNS FROM {$table}", ARRAY_A );
		if ( $rows === null ) {
			return new \WP_Error( 'db_describe_failed', self::db()->last_error ?: 'Failed to describe table.' );
		}

		$cols = array_map( static fn( $r ) => $r['Field'] ?? '', $rows );

		self::$schemaCache[ $cacheKey ] = $cols;

		return $cols;
	}

	// --------------------------------------------------

	/**
	 * @param string $table Short table name (without prefix).
	 * @param string $schema SQL column definitions.
	 *
	 * @return string[]|\WP_Error
	 */
	public static function createTable( string $table = '', string $schema = '' ): array|\WP_Error {
		if ( ! $table || ! $schema ) {
			return new \WP_Error( 'invalid_args', 'Table name and schema are required.' );
		}

		$collate         = self::getCharsetCollate();
		$backtickedTable = self::backtickedTable( $table );
		$schema          = sprintf( 'CREATE TABLE %s ( %s ) ENGINE=InnoDB %s;', $backtickedTable, $schema, $collate );

		$upgradeFile = ABSPATH . 'wp-admin/includes/upgrade.php';
		if ( is_file( $upgradeFile ) ) {
			require_once $upgradeFile;
		}

		$results = dbDelta( $schema );

		if ( self::db()->last_error ) {
			return new \WP_Error( 'creating_table_failed', self::db()->last_error );
		}

		return $results;
	}

	// --------------------------------------------------

	/**
	 * Drop a table.
	 *
	 * @param string $tableName Short table name (without prefix)
	 *
	 * @return void
	 */
	public static function dropTable( string $tableName ): void {
		$table = self::backtickedTable( $tableName );

		self::db()->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// --------------------------------------------------

	/**
	 * Truncate a table.
	 *
	 * @param string $tableName Short table name (without prefix)
	 *
	 * @return bool
	 */
	public static function truncateTable( string $tableName ): bool {
		$table = self::backtickedTable( $tableName );

		return false !== self::db()->query( "TRUNCATE TABLE {$table}" );
	}

	// --------------------------------------------------

	/**
	 * Begin a transaction. Returns WP_Error on failure or true.
	 *
	 * @return bool|\WP_Error
	 */
	public static function beginTransaction(): \WP_Error|bool {
		$res = self::db()->query( 'START TRANSACTION' );

		return $res === false
			? new \WP_Error( 'transaction_start_failed', self::db()->last_error ?: 'Failed to start transaction' )
			: true;
	}

	// --------------------------------------------------

	/**
	 * @return bool|\WP_Error
	 */
	public static function commitTransaction(): bool|\WP_Error {
		$res = self::db()->query( 'COMMIT' );

		return $res === false
			? new \WP_Error( 'transaction_commit_failed', self::db()->last_error ?: 'Failed to commit transaction' )
			: true;
	}

	// --------------------------------------------------

	/**
	 * @return bool|\WP_Error
	 */
	public static function rollbackTransaction(): bool|\WP_Error {
		$res = self::db()->query( 'ROLLBACK' );

		return $res === false
			? new \WP_Error( 'transaction_rollback_failed', self::db()->last_error ?: 'Failed to rollback transaction' )
			: true;
	}

	// --------------------------------------------------

	/**
	 * Helper to run a callback inside a transaction.
	 *
	 * @param callable $callback
	 *
	 * @return mixed|\WP_Error
	 */
	public static function transaction( callable $callback ): mixed {
		$begin = self::beginTransaction();
		if ( is_wp_error( $begin ) ) {
			return $begin;
		}

		try {
			$result = $callback();

			if ( is_wp_error( $result ) ) {
				self::rollbackTransaction();

				return $result;
			}

			$commit = self::commitTransaction();
			if ( is_wp_error( $commit ) ) {
				return $commit;
			}

			return $result;
		} catch ( \Throwable $e ) {
			self::rollbackTransaction();

			return new \WP_Error( 'transaction_exception', $e->getMessage(), [ 'exception' => $e ] );
		}
	}

	// --------------------------------------------------

	/**
	 * Insert a single row into a table.
	 *
	 * @param string     $tableName short table name (no prefix)
	 * @param array      $data      associative column => value
	 * @param array|null $format    optional formats for $wpdb->insert
	 *
	 * @return int|\WP_Error Insert ID on success or WP_Error on failure
	 */
	public static function insertOneRow( string $tableName, array $data, ?array $format = null ): \WP_Error|int {
		if ( ! $data ) {
			return new \WP_Error( 'no_data', 'No data provided.' );
		}

		$cols = self::getTableColumns( $tableName );
		if ( is_wp_error( $cols ) ) {
			return $cols;
		}

		$valid = array_intersect_key( $data, array_flip( $cols ) );
		if ( ! $valid ) {
			return new \WP_Error( 'no_valid_columns', 'No valid columns provided for insertion.' );
		}

		$table  = self::tableNameFull( $tableName );
		$result = self::db()->insert( $table, $valid, $format ?? array_fill( 0, count( $valid ), '%s' ) );

		if ( $result === false ) {
			return new \WP_Error( 'insert_failed', self::db()->last_error, [ 'query' => self::db()->last_query ] );
		}

		return self::db()->insert_id;
	}

	// --------------------------------------------------

	/**
	 * Upsert a single row (INSERT ... ON DUPLICATE KEY UPDATE).
	 *
	 * @param string     $tableName  Short table name (no prefix).
	 * @param array      $insertData Associative array of column => value to insert.
	 *                               Values can be:
	 *                               - Scalar (string/int/float) — auto-escaped via prepare().
	 *                               - DB::raw('expr') — injected as raw SQL (e.g. 'LAST_INSERT_ID(1)').
	 * @param array      $updateExpr Associative array of column => raw SQL expression for update.
	 *                               Example: ['hit_count' => 'hit_count + 1', 'referer' => 'VALUES(referer)'].
	 *                               WARNING: $updateExpr values are treated as RAW SQL. Do not pass user input.
	 * @param array|null $format     Optional formats for $insertData (ignored for raw expressions).
	 *
	 * @return int|\WP_Error Number of rows affected (1 for insert, 2 for update) or WP_Error.
	 */
	public static function upsert( string $tableName, array $insertData, array $updateExpr, ?array $format = null ): \WP_Error|int {
		if ( ! $insertData || ! $updateExpr ) {
			return new \WP_Error( 'no_data', 'Missing insert data or update expressions.' );
		}

		$cols = self::getTableColumns( $tableName );
		if ( is_wp_error( $cols ) ) {
			return $cols;
		}

		$validInsert = array_intersect_key( $insertData, array_flip( $cols ) );
		if ( ! $validInsert ) {
			return new \WP_Error( 'no_valid_columns', 'No valid columns provided for insertion.' );
		}

		$tableFullName = self::backtickedTable( $tableName );
		$fields        = [];
		$placeholders  = [];
		$values        = [];

		$format ??= array_fill( 0, count( $validInsert ), '%s' );

		$i = 0;
		foreach ( $validInsert as $col => $val ) {
			$fields[] = self::backtickedColumn( $col );

			if ( $val instanceof RawExpression ) {
				// Inject raw SQL directly — no placeholder/escaping.
				$placeholders[] = $val->expression;
			} else {
				$placeholders[] = $format[ $i ] ?? '%s';
				$values[]       = $val;
			}

			++$i;
		}

		$updateParts = [];
		foreach ( $updateExpr as $col => $expr ) {
			if ( in_array( $col, $cols, true ) ) {
				$updateParts[] = self::backtickedColumn( $col ) . ' = ' . $expr;
			}
		}

		if ( ! $updateParts ) {
			return self::insertOneRow( $tableName, $insertData );
		}

		$sql = "INSERT INTO {$tableFullName} (" . implode( ', ', $fields ) . ') VALUES (' . implode( ', ', $placeholders ) . ') ON DUPLICATE KEY UPDATE ' . implode( ', ', $updateParts );

		$prepared = self::db()->prepare( $sql, $values );
		if ( ! $prepared ) {
			return new \WP_Error( 'prepare_failed', 'Failed to prepare upsert query.' );
		}

		$result = self::db()->query( $prepared );

		if ( $result === false ) {
			return new \WP_Error( 'upsert_failed', self::db()->last_error, [ 'query' => self::db()->last_query ] );
		}

		return (int) $result;
	}

	// --------------------------------------------------

	/**
	 * Bulk insert rows.
	 *
	 * @param string $table
	 * @param array  $rows      array of associative arrays
	 * @param int    $batchSize
	 *
	 * @return int|\WP_Error Number of inserted rows on success or WP_Error on failure
	 */
	public static function bulkInsertRows( string $table, array $rows, int $batchSize = 500 ): \WP_Error|int {
		if ( ! $rows ) {
			return 0;
		}

		$validColumns = self::getTableColumns( $table );
		if ( is_wp_error( $validColumns ) ) {
			return $validColumns;
		}

		$columns = array_keys( (array) array_intersect_key( reset( $rows ), array_flip( $validColumns ) ) );
		if ( ! $columns ) {
			return new \WP_Error( 'no_valid_columns', 'No valid columns detected for bulk insert.' );
		}

		$backtickedTable = self::backtickedTable( $table );
		$columnList      = implode( ', ', array_map( self::backtickedColumn( ... ), $columns ) );
		$totalInserted   = 0;
		$batches         = array_chunk( $rows, $batchSize );

		self::beginTransaction();

		try {
			foreach ( $batches as $batch ) {
				$placeholders = [];
				$values       = [];

				foreach ( $batch as $row ) {
					$rowPlaceholders = [];

					foreach ( $columns as $col ) {
						$v = $row[ $col ] ?? null;
						if ( $v === null ) {
							$rowPlaceholders[] = 'NULL';
						} else {
							$rowPlaceholders[] = '%s';
							$values[]          = (string) $v;
						}
					}

					$placeholders[] = '(' . implode( ', ', $rowPlaceholders ) . ')';
				}

				if ( ! $placeholders ) {
					continue;
				}

				$sql      = "INSERT INTO {$backtickedTable} ({$columnList}) VALUES " . implode( ', ', $placeholders );
				$prepared = $values ? self::db()->prepare( $sql, $values ) : $sql;
				$result   = self::db()->query( $prepared );

				if ( $result === false ) {
					throw new \RuntimeException( self::db()->last_error ?: 'Database insert failed.' );
				}

				$totalInserted += $result;
			}

			self::commitTransaction();

			return $totalInserted;
		} catch ( \Throwable $e ) {
			self::rollbackTransaction();

			return new \WP_Error( 'bulk_insert_failed', $e->getMessage() );
		}
	}

	// --------------------------------------------------

	/**
	 * Update one row by primary key (default 'id')
	 *
	 * @param string     $tableName
	 * @param int|string $id
	 * @param array      $data
	 * @param string     $primaryKey
	 * @param array|null $format
	 *
	 * @return int|\WP_Error Number of rows updated or WP_Error
	 */
	public static function updateOneRow( string $tableName, int|string $id, array $data, string $primaryKey = 'id', ?array $format = null ): \WP_Error|int {
		if ( ! $data ) {
			return new \WP_Error( 'no_data', 'No data provided for update.' );
		}

		$cols = self::getTableColumns( $tableName );
		if ( is_wp_error( $cols ) ) {
			return $cols;
		}

		$valid = array_intersect_key( $data, array_flip( $cols ) );
		if ( ! $valid ) {
			return new \WP_Error( 'no_valid_columns', 'No valid columns provided for update.' );
		}

		$table  = self::tableNameFull( $tableName );
		$result = self::db()->update(
			$table,
			$valid,
			[ $primaryKey => $id ],
			$format ?? array_fill( 0, count( $valid ), '%s' ),
			[ is_int( $id ) ? '%d' : '%s' ]
		);

		if ( $result === false ) {
			return new \WP_Error( 'update_failed', self::db()->last_error, [ 'query' => self::db()->last_query ] );
		}

		return (int) $result;
	}

	// --------------------------------------------------

	/**
	 * Delete one row by primary key (default 'id')
	 *
	 * @param string     $tableName
	 * @param int|string $id
	 * @param string     $primaryKey
	 *
	 * @return int|\WP_Error Number of rows deleted or WP_Error
	 */
	public static function deleteOneRow( string $tableName, int|string $id, string $primaryKey = 'id' ): \WP_Error|int {
		$table  = self::tableNameFull( $tableName );
		$result = self::db()->delete( $table, [ $primaryKey => $id ], [ is_int( $id ) ? '%d' : '%s' ] );

		if ( $result === false ) {
			return new \WP_Error( 'delete_failed', self::db()->last_error, [ 'query' => self::db()->last_query ] );
		}

		return (int) $result;
	}

	// --------------------------------------------------

	/**
	 * @param string $table
	 * @param string $whereSql
	 * @param array  $params
	 *
	 * @return array|null
	 */
	public static function getOne( string $table, string $whereSql, array $params = [] ): ?array {
		$tableFull = self::backtickedTable( $table );
		$sql       = "SELECT * FROM {$tableFull} WHERE {$whereSql} LIMIT 1";

		if ( $params ) {
			$sql = self::db()->prepare( $sql, ...$params );
		}

		return self::db()->get_row( $sql, ARRAY_A );
	}

	// --------------------------------------------------

	/**
	 * Get multiple rows with optional filter array, paging and ordering.
	 *
	 * @param string $tableName
	 * @param array  $where    associative col => value (ANDed)
	 * @param int    $page     1-based page
	 * @param int    $perPage
	 * @param string $orderBy
	 * @param string $order
	 *
	 * @return array|\WP_Error
	 */
	public static function getRows( string $tableName, array $where = [], int $page = 1, int $perPage = 20, string $orderBy = 'id', string $order = 'ASC' ): \WP_Error|array {
		$cols = self::getTableColumns( $tableName );
		if ( is_wp_error( $cols ) ) {
			return $cols;
		}

		// Validate orderBy against actual columns
		$orderBy = self::sanitizeIdentifier( $orderBy );
		if ( ! in_array( $orderBy, $cols, true ) ) {
			$orderBy = 'id';
		}

		$order = strtoupper( $order );
		$order = in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'ASC';

		$table = self::backtickedTable( $tableName );

		// Build WHERE
		$whereClauses = [];
		$values       = [];
		foreach ( $where as $col => $val ) {
			$col = self::sanitizeIdentifier( (string) $col );
			if ( in_array( $col, $cols, true ) ) {
				$whereClauses[] = self::backtickedColumn( $col ) . ' = %s';
				$values[]       = (string) $val;
			}
		}

		$whereSql = $whereClauses ? ' WHERE ' . implode( ' AND ', $whereClauses ) : '';

		$offset      = max( 0, ( $page - 1 ) * $perPage );
		$limitClause = ' LIMIT %d, %d';
		$values[]    = $offset;
		$values[]    = $perPage;

		$sql      = "SELECT * FROM {$table}{$whereSql} ORDER BY " . self::backtickedColumn( $orderBy ) . " {$order}" . $limitClause;
		$prepared = self::db()->prepare( $sql, $values );

		if ( ! $prepared ) {
			return new \WP_Error( 'prepare_failed', 'Failed to prepare select query.' );
		}

		$rows = self::db()->get_results( $prepared, ARRAY_A );

		return $rows ?? new \WP_Error( 'select_failed', self::db()->last_error, [ 'query' => self::db()->last_query ] );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $tableName
	 *
	 * @return void
	 */
	public static function clearSchemaCache( ?string $tableName = null ): void {
		if ( $tableName === null ) {
			self::$schemaCache = [];

			return;
		}

		$key = self::tableNameFull( $tableName );
		unset( self::$schemaCache[ $key ] );
	}
}

/**
 * Raw SQL expression marker for DB::upsert().
 *
 * @internal Only use via DB::raw() with trusted, hardcoded expressions.
 */
final class RawExpression {
	public function __construct(
		public readonly string $expression,
	) {}
}
