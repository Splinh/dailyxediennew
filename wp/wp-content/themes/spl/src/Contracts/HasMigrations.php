<?php
/**
 * HasMigrations — optional contract for modules needing destructive schema changes.
 *
 * For changes that dbDelta cannot handle (ALTER COLUMN, DROP COLUMN, data migrations),
 * modules implement this interface to declare versioned migration callbacks.
 *
 * Each migration runs exactly once, tracked per-module in wp_options.
 * Migrations are executed in version order; if one fails, subsequent ones are skipped.
 *
 * @package SPL\Contracts
 */

namespace SPL\Contracts;

defined( 'ABSPATH' ) || exit;

interface HasMigrations {
	/**
	 * Return versioned migration callbacks.
	 *
	 * Key   = semver string (e.g. '1.1.0').
	 * Value = callable that performs the migration.
	 *
	 * @return array<string, callable(): void>
	 */
	public static function migrations(): array;
}
