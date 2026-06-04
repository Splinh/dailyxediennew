<?php
/**
 * @package HDAT\Domain\Routing
 */

declare(strict_types=1);

namespace HDAT\Domain\Routing;

use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Gateway\GatewayRequest;

defined( 'ABSPATH' ) || exit;

interface RouterPolicy {

	/**
	 * Resolve ordered route candidates for a gateway request.
	 *
	 * @return RouteCandidate[] Ordered best-first.
	 */
	public function resolve( GatewayRequest $req, ConsumerToken $consumer ): array;
}
