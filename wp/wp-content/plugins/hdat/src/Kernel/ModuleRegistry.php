<?php
/**
 * @package HDAT\Kernel
 */

declare(strict_types=1);

namespace HDAT\Kernel;

use HDAT\Modules\ModuleInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Static module registry — zero-footprint optional features.
 *
 * Modules are explicitly registered in Plugin::registerModules(). Disabled
 * modules are never instantiated, so they register zero hooks and consume
 * zero memory.
 */
final class ModuleRegistry {

	public const OPTION_KEY = 'hdat_modules';

	/** @var array<string, class-string<ModuleInterface>> */
	private static array $map = [];

	/**
	 * @param class-string<ModuleInterface> $moduleClass
	 */
	public static function register( string $moduleClass ): void {
		self::$map[ $moduleClass::slug() ] = $moduleClass;
	}

	/**
	 * Boot enabled modules (always-active included).
	 */
	public static function boot(): void {
		$enabled = self::enabled();

		foreach ( self::$map as $slug => $moduleClass ) {
			if ( $moduleClass::alwaysActive() || in_array( $slug, $enabled, true ) ) {
				( new $moduleClass() )->boot();
			}
		}
	}

	/**
	 * @return string[] Enabled module slugs.
	 */
	public static function enabled(): array {
		return (array) get_option( self::OPTION_KEY, [] );
	}

	/**
	 * @param string[] $slugs
	 */
	public static function setEnabled( array $slugs ): void {
		$valid = array_intersect( $slugs, array_keys( self::$map ) );

		update_option( self::OPTION_KEY, array_values( array_unique( $valid ) ) );
	}

	/**
	 * @return array<string, array{slug: string, title: string, description: string, active: bool, always_active: bool}>
	 */
	public static function allForAdmin(): array {
		$enabled = self::enabled();
		$out     = [];

		foreach ( self::$map as $slug => $moduleClass ) {
			$out[ $slug ] = [
				'slug'          => $slug,
				'title'         => $moduleClass::title(),
				'description'   => $moduleClass::description(),
				'active'        => $moduleClass::alwaysActive() || in_array( $slug, $enabled, true ),
				'always_active' => $moduleClass::alwaysActive(),
			];
		}

		return $out;
	}

	public static function reset(): void {
		self::$map = [];
	}
}
