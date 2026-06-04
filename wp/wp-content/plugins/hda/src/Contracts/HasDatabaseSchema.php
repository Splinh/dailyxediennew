<?php
/**
 * HasDatabaseSchema — optional contract for modules needing custom tables.
 *
 * Migration auto-collects schemas from all discovered modules
 * implementing this interface. Tables are created for ALL modules
 * (including disabled) so enabling is instant.
 *
 * @package HDAddons\Contracts
 */

namespace HDAddons\Contracts;

defined( 'ABSPATH' ) || exit;

interface HasDatabaseSchema {
	/**
	 * Return table schemas owned by this module.
	 *
	 * Key = table name (without prefix), Value = column SQL (without CREATE TABLE wrapper).
	 *
	 * @return array<string, string> ['table_name' => 'SQL columns/keys']
	 */
	public static function databaseSchemas(): array;
}
