<?php
/**
 * @package HDAT\Infrastructure\Http
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Http;

use HDAT\Domain\Routing\FailureCategory;

defined( 'ABSPATH' ) || exit;

/**
 * Provider call failure.
 *
 * Carries a FailureCategory so the circuit breaker / router can decide
 * whether to retry, cool down, or stop.
 */
final class ProviderException extends \RuntimeException {

	public function __construct(
		public readonly int $status,
		public readonly FailureCategory $category,
		string $message = '',
		?\Throwable $previous = null,
	) {
		parent::__construct( '' === $message ? "Provider call failed (status={$status}, category={$category->value})" : $message, 0, $previous );
	}
}
