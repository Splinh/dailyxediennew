<?php
/**
 * @package HDAT\Application
 */

declare(strict_types=1);

namespace HDAT\Application;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown when every candidate from AiRouter::resolve() failed.
 *
 * The `$lastCategory` field lets the API layer turn this into the right
 * outward-facing status (502 for Server / Auth, 504 for Timeout,
 * 429 if every route was rate-limited, etc.).
 */
final class NoRouteAvailableException extends \RuntimeException {

	public function __construct(
		string $message = 'no_route_available',
		public readonly int $attempts = 0,
		public readonly string $lastCategory = '',
		?\Throwable $previous = null,
	) {
		parent::__construct( $message, 0, $previous );
	}
}
