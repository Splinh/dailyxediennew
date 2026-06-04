<?php
/**
 * @package HDAT\Kernel
 */

declare(strict_types=1);

namespace HDAT\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for plugin settings.
 *
 * Consumers MUST use Settings::get() instead of raw get_option().
 */
final class Settings {

	public const OPTION_KEY = 'hdat_settings';

	public const DEFAULTS = [
		'router_strategy'              => 'auto', // auto|free_only|paid_only
		'max_route_attempts'           => 6,
		'request_timeout'              => 30,
		'cache_ttl'                    => 86400,
		'circuit_threshold'            => 5,
		'circuit_ttl'                  => 300,
		'cooldown_429'                 => 60,
		'sticky_route_ttl'             => 1800,
		'request_shaper'               => 'off', // off|safe
		'route_headers'                => true,
		'clean_uninstall'              => false,
		'force_provider_credential_id' => null, // null = disabled, int = credential ID
	];

	public static function get( ?string $key = null, mixed $fallback = null ): mixed {
		$opts = array_merge( self::DEFAULTS, (array) get_option( self::OPTION_KEY, [] ) );

		if ( null !== $key ) {
			return $opts[ $key ] ?? $fallback;
		}

		return $opts;
	}

	public static function set( string $key, mixed $value ): void {
		$opts         = (array) get_option( self::OPTION_KEY, [] );
		$opts[ $key ] = $value;
		update_option( self::OPTION_KEY, $opts );
	}
}
