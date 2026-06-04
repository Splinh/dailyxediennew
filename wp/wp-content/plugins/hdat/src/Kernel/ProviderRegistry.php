<?php
/**
 * @package HDAT\Kernel
 */

declare(strict_types=1);

namespace HDAT\Kernel;

use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Provider\ProviderCapsule;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\Custom\CustomProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Static service locator for provider capsules.
 *
 * Each provider class is registered once at boot. Capsules are stateless,
 * so we instantiate fresh on every `get()` call — cheap, avoids cross-request
 * state leaks.
 */
final class ProviderRegistry {

	/** @var array<string, class-string<ProviderCapsule>> */
	private static array $map = [];

	/**
	 * @param class-string<ProviderCapsule> $providerClass
	 */
	public static function register( string $providerClass ): void {
		$id = $providerClass::meta()->id;

		self::$map[ $id ] = $providerClass;
	}

	public static function get( string $id ): ProviderCapsule {
		if ( ! isset( self::$map[ $id ] ) ) {
			throw new \InvalidArgumentException( "Unknown provider: {$id}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$providerClass = self::$map[ $id ];

		return new $providerClass();
	}

	public static function getForCredential( Credential $credential ): ProviderCapsule {
		if ( self::isCustomId( $credential->provider ) ) {
			if ( null === $credential->customProviderMeta ) {
				throw new \InvalidArgumentException( "Custom provider metadata missing for credential {$credential->id->value}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			return new CustomProvider( $credential->customProviderMeta );
		}

		return self::get( $credential->provider );
	}

	public static function metaForCredential( Credential $credential ): ProviderMeta {
		$provider = self::getForCredential( $credential );

		return $provider instanceof CustomProvider
			? $provider->instanceMeta()
			: $provider::meta();
	}

	public static function has( string $id ): bool {
		return isset( self::$map[ $id ] );
	}

	public static function hasForCredential( Credential $credential ): bool {
		if ( self::isCustomId( $credential->provider ) ) {
			return null !== $credential->customProviderMeta;
		}

		return self::has( $credential->provider );
	}

	public static function isCustomId( string $id ): bool {
		return 'custom' === $id || str_starts_with( $id, 'custom:' );
	}

	/**
	 * @return array<string, ProviderMeta>
	 */
	public static function all(): array {
		return array_map( static fn( string $providerClass ) => $providerClass::meta(), self::$map );
	}

	/**
	 * @return string[]
	 */
	public static function ids(): array {
		return array_keys( self::$map );
	}

	/**
	 * Reset registry (testing only).
	 */
	public static function reset(): void {
		self::$map = [];
	}
}
