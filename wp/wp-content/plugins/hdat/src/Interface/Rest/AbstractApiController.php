<?php
/**
 * @package HDAT\Interface\Rest
 */

declare(strict_types=1);

namespace HDAT\Interface\Rest;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for all REST API controllers in HDAT.
 *
 * Consolidates routing helper and common request parameter parsing/sanitation logic.
 */
abstract class AbstractApiController {

	/**
	 * Register routes with WordPress.
	 */
	abstract public function register(): void;

	/**
	 * Register a REST route helper.
	 */
	protected function route( string $ns, string $path, string $method, string $callback, array $perm ): void {
		register_rest_route(
			$ns,
			$path,
			[
				'methods'             => $method,
				'callback'            => [ $this, $callback ],
				'permission_callback' => $perm,
			]
		);
	}

	/**
	 * Parse parameter to optional integer.
	 */
	protected function nullableInt( \WP_REST_Request $r, string $key ): ?int {
		$val = $r->get_param( $key );

		return null !== $val && '' !== $val ? (int) $val : null;
	}

	/**
	 * Parse parameter to optional DateTimeImmutable.
	 */
	protected function nullableDate( \WP_REST_Request $r, string $key ): ?\DateTimeImmutable {
		$val = $r->get_param( $key );
		if ( null === $val || '' === $val ) {
			return null;
		}

		try {
			return new \DateTimeImmutable( (string) $val );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Sanitize and filter input array to an array of strings.
	 *
	 * @param mixed $input
	 * @return string[]
	 */
	protected function sanitizeStringArray( mixed $input ): array {
		if ( ! is_array( $input ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map( 'sanitize_text_field', array_map( 'strval', $input ) ),
				static fn( string $s ) => '' !== $s
			)
		);
	}
}
