<?php
/**
 * @package HDAT\Infrastructure\Routing
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Routing;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Routing\RouteCandidate;
use HDAT\Infrastructure\Persistence\RouteStateRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Scores a route candidate 0–105. Higher is better.
 *
 * Weights:
 *   priority   0–20  operator's manual preference (credential.priority 0–10 → ×2)
 *   health     0–40  consecutive failures erode this; 0 failures = full marks
 *   latency    0–20  faster average response = higher; unknown = neutral 0.5
 *   free bonus 0–20  free tier preferred for pool-token-burning goal
 *   jitter     0–5   random noise so equal-score candidates rotate naturally
 *
 * Health is the dominant term: a flaky-but-fast route should lose to a
 * slower healthy one. Jitter is small enough to never override meaningful
 * score differences, but large enough to shuffle otherwise-equal candidates.
 */
final class RouteScorer {

	public function __construct(
		private readonly RouteStateRepository $routeState,
	) {}

	public function score( RouteCandidate $c ): float {
		$state = $this->routeState->get( $c->provider, $c->model, $c->credentialId );

		$priorityScore = min( 1.0, $c->priority / 10 ) * 20;
		$healthScore   = $this->health( $state->consecutiveFailures ) * 40;
		$latencyScore  = $this->latency( $state->avgLatencyMs ) * 20;
		$tierScore     = ( CredentialTier::Free === $c->tier ? 1.0 : 0.0 ) * 20;
		$jitter        = wp_rand( 0, 500 ) / 100; // 0.00–5.00

		return $priorityScore + $healthScore + $latencyScore + $tierScore + $jitter;
	}

	private function health( int $failures ): float {
		return max( 0.0, 1.0 - $failures * 0.2 );
	}

	private function latency( int $ms ): float {
		if ( 0 === $ms ) {
			return 0.5; // unknown → neutral
		}

		return max( 0.0, 1.0 - $ms / 10000 ); // 10s = 0
	}
}
