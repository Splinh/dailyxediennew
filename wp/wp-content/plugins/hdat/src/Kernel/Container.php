<?php
/**
 * @package HDAT\Kernel
 */

declare(strict_types=1);

namespace HDAT\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal lazy service locator.
 *
 * Not a full DI container — no reflection, no autowiring. Just a
 * factory map with one-shot caching.
 */
final class Container {

	/** @var array<string, callable> */
	private array $factories = [];

	/** @var array<string, mixed> */
	private array $instances = [];

	public function bind( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
		unset( $this->instances[ $id ] );
	}

	public function make( string $id ): mixed {
		if ( ! array_key_exists( $id, $this->instances ) ) {
			if ( ! isset( $this->factories[ $id ] ) ) {
				throw new \RuntimeException( "Container has no binding for: {$id}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
			$this->instances[ $id ] = ( $this->factories[ $id ] )( $this );
		}

		return $this->instances[ $id ];
	}

	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] );
	}
}
