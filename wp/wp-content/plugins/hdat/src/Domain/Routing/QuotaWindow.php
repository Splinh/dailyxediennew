<?php
/**
 * @package HDAT\Domain\Routing
 */

declare(strict_types=1);

namespace HDAT\Domain\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Snapshot of usage in the current minute + day for one subject (consumer or credential).
 *
 * Subjects use this against their rpm/rpd/tpm/tpd limits to decide whether
 * to admit or refuse a request before it's dispatched.
 */
final class QuotaWindow {

	public function __construct(
		public readonly int $requestsThisMinute = 0,
		public readonly int $requestsToday = 0,
		public readonly int $tokensThisMinute = 0,
		public readonly int $tokensToday = 0,
	) {}
}
