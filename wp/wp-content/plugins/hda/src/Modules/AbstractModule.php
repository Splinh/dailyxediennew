<?php
/**
 * Base class for all HDA modules.
 *
 * Provides sensible defaults for ModuleInterface methods and common
 * settings utilities.
 *
 * Subclasses only need to implement: slug(), title(), description(),
 * group(), and boot(). Override defaults(), optionKeys(), cronHooks(),
 * alwaysActive() as needed.
 *
 * @package HDAddons\Modules
 */

namespace HDAddons\Modules;

use HDAddons\Contracts\ModuleInterface;
use HDAddons\Helper;

defined( 'ABSPATH' ) || exit;

abstract class AbstractModule implements ModuleInterface {

	/**
	 * In-memory option cache, keyed by FQCN.
	 *
	 * Prevents multiple get_option() calls within the same request.
	 * Each subclass gets its own slot via static:: (late static binding).
	 *
	 * @var array<class-string, array>
	 */
	private static array $optionCache = [];

	// ── Defaults (override as needed) ────────────────

	/**
	 * Auto-generate the wp_options key from the module slug.
	 *
	 * Convention: `hda_{slug}`. Override only for edge cases.
	 */
	public static function optionKey(): string {
		return 'hda_' . static::slug();
	}

	public static function defaults(): array {
		return [];
	}

	public static function optionKeys(): array {
		return [ static::optionKey() ];
	}

	public static function cronHooks(): array {
		return [];
	}

	public static function cronSchedules(): array {
		return [];
	}

	public static function alwaysActive(): bool {
		return false;
	}

	// ── Options Helper ───────────────────────────────

	/**
	 * Get merged options: defaults + saved values.
	 *
	 * FlyingPress pattern — ensures the options array is always complete.
	 * Uses the auto-generated optionKey() as the primary option name.
	 *
	 * @return array<string, mixed>
	 */
	public static function getOptions(): array {
		return array_merge( static::defaults(), Helper::getOption( static::optionKey(), [] ) );
	}

	/**
	 * Get cached module options (lazy-loaded, no defaults merge).
	 *
	 * Reads from DB once per request, then serves from memory.
	 * Uses auto-generated optionKey().
	 *
	 * @return array<string, mixed>
	 */
	public static function getCachedOptions(): array {
		$class = static::class;

		if ( ! isset( self::$optionCache[ $class ] ) ) {
			self::$optionCache[ $class ] = Helper::getOption( static::optionKey(), [] );
		}

		return self::$optionCache[ $class ];
	}

	/**
	 * Reset cached options (call after save to bust the in-memory cache).
	 */
	public static function resetCache(): void {
		unset( self::$optionCache[ static::class ] );
	}

	// ── Sub-module Option Accessors ─────────────────

	/**
	 * Get a sub-module's options stored as a sub-array of this module's option.
	 *
	 * Convention: `hda_security['firewall']` for sub-key 'firewall'.
	 *
	 * @param string $subKey Sub-key name (e.g., 'firewall').
	 *
	 * @return array<string, mixed>
	 */
	public static function getSubOptions( string $subKey ): array {
		$all = static::getCachedOptions();

		return (array) ( $all[ $subKey ] ?? [] );
	}

	/**
	 * Save a sub-module's options into this module's option array.
	 *
	 * Merges sub-key data into the existing parent option, preserving
	 * other sub-modules' data and the parent's own top-level keys.
	 *
	 * @param string    $subKey   Sub-key name (e.g., 'firewall').
	 * @param array     $options  Sub-module options to save.
	 * @param bool|null $autoload Whether to autoload option.
	 */
	public static function setSubOptions( string $subKey, array $options, ?bool $autoload = true ): void {
		$all = Helper::getOption( static::optionKey(), [] );

		if ( ! empty( $options ) ) {
			$all[ $subKey ] = $options;
		} else {
			unset( $all[ $subKey ] );
		}

		if ( ! empty( $all ) ) {
			Helper::updateOption( static::optionKey(), $all, 12, $autoload );
		} else {
			Helper::removeOption( static::optionKey() );
		}

		// Bust cache after write.
		static::resetCache();
	}

	// ── Settings Helpers ────────────────────────────

	/**
	 * Extract and sanitize specific fields from form data.
	 *
	 * @param array $data         Form data.
	 * @param array $fields       Field keys to extract.
	 * @param bool  $requireValue Only include non-empty values.
	 *
	 * @return array
	 */
	protected static function extractFields( array $data, array $fields, bool $requireValue = false ): array {
		$options = [];

		foreach ( $fields as $field ) {
			if ( $requireValue ) {
				if ( ! empty( $data[ $field ] ) ) {
					$options[ $field ] = self::sanitizeValue( $data[ $field ] );
				}
			} elseif ( isset( $data[ $field ] ) ) {
				$options[ $field ] = self::sanitizeValue( $data[ $field ] );
			}
		}

		return $options;
	}

	/**
	 * Save options or remove if empty.
	 *
	 * @param string    $optionName Option name.
	 * @param array     $options    Options to save.
	 * @param bool|null $autoload   Whether to autoload option.
	 */
	protected static function saveOrRemove( string $optionName, array $options, ?bool $autoload = true ): void {
		if ( ! empty( $options ) ) {
			Helper::updateOption( $optionName, $options, 12, $autoload );
		} else {
			Helper::removeOption( $optionName );
		}
	}

	/**
	 * Sanitize a single value based on its type.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return mixed
	 */
	protected static function sanitizeValue( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( static::sanitizeValue( ... ), $value );
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		return $value;
	}
}
