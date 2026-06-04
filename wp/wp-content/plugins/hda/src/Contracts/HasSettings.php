<?php
/**
 * Interface for modules that have admin settings UI.
 *
 * Modules implementing this are automatically detected by
 * ModuleRegistry::processSettingsSave().
 *
 * Form inputs MUST use namespace arrays matching optionKey():
 *   name="hda_{slug}[field_name]"
 *
 * saveSettings() receives ONLY the module's own data array,
 * not the entire form.
 *
 * @package HDAddons\Contracts
 */

namespace HDAddons\Contracts;

defined( 'ABSPATH' ) || exit;

interface HasSettings {

	/**
	 * Sanitize and persist settings from the module's own data.
	 *
	 * Receives only the array under $data[ static::optionKey() ],
	 * already pre-sanitized by SettingsManager.
	 *
	 * @param array $data Module-scoped settings data.
	 */
	public static function saveSettings( array $data ): void;
}
