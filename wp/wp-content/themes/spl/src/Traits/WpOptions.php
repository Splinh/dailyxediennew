<?php
/**
 * Options and ThemeMod helper methods.
 *
 * Thin wrappers around WP's option API with consistent interface.
 * Does NOT add extra caching — WordPress already caches options
 * via wp_object_cache / alloptions.
 *
 * @author HD
 */

namespace SPL\Traits;

defined( 'ABSPATH' ) || exit;

trait WpOptions {
	// -------------------------------------------------------------

	/**
	 * Get option value.
	 *
	 * WordPress core `get_option()` already caches values in
	 * `wp_object_cache` (and autoloaded options in `alloptions`).
	 * No additional caching layer is needed.
	 *
	 * @param string $option       Option name.
	 * @param mixed  $defaultValue Default value if option doesn't exist.
	 * @param bool   $network      Whether to get site (network) option.
	 *
	 * @return mixed
	 */
	public static function getOption( string $option, mixed $defaultValue = false, bool $network = false ): mixed {
		$option = trim( $option );
		if ( ! $option ) {
			return $defaultValue;
		}

		return $network
			? get_site_option( $option, $defaultValue )
			: get_option( $option, $defaultValue );
	}

	// -------------------------------------------------------------

	/**
	 * Update option value.
	 *
	 * @param string    $option   Option name.
	 * @param mixed     $newValue New value.
	 * @param bool      $network  Whether to update site (network) option.
	 * @param bool|null $autoload Whether to autoload option.
	 *
	 * @return bool
	 */
	public static function updateOption( string $option, mixed $newValue, bool $network = false, ?bool $autoload = false ): bool {
		$option = trim( $option );
		if ( ! $option ) {
			return false;
		}

		return $network
			? update_site_option( $option, $newValue )
			: update_option( $option, $newValue, $autoload );
	}

	// --------------------------------------------------

	/**
	 * Remove option.
	 *
	 * @param string $option  Option name.
	 * @param bool   $network Whether to delete site (network) option.
	 *
	 * @return bool
	 */
	public static function removeOption( string $option, bool $network = false ): bool {
		$option = trim( $option );
		if ( ! $option ) {
			return false;
		}

		return $network
			? delete_site_option( $option )
			: delete_option( $option );
	}

	// -------------------------------------------------------------

	/**
	 * Get theme mod value.
	 *
	 * WordPress `get_theme_mod()` is already cached via `theme_mods_{slug}`
	 * option (autoloaded). No additional caching layer needed.
	 *
	 * @param string|null $modName      Mod name.
	 * @param mixed       $defaultValue Default value.
	 *
	 * @return mixed
	 */
	public static function getThemeMod( ?string $modName, mixed $defaultValue = false ): mixed {
		if ( ! $modName ) {
			return $defaultValue;
		}

		$mod = get_theme_mod( $modName, $defaultValue );

		// Force HTTPS on URLs when site uses SSL.
		if ( is_ssl() && is_string( $mod ) && str_contains( $mod, 'http://' ) ) {
			return str_replace( 'http://', 'https://', $mod );
		}

		return $mod;
	}

	// -------------------------------------------------------------

	/**
	 * Set theme mod value.
	 *
	 * @param string $modName Mod name.
	 * @param mixed  $value   Value.
	 *
	 * @return void
	 */
	public static function setThemeMod( string $modName, mixed $value ): void {
		if ( ! $modName ) {
			return;
		}

		set_theme_mod( $modName, $value );
	}
}
