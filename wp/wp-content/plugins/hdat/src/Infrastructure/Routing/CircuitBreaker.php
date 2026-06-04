<?php
/**
 * @package HDAT\Infrastructure\Routing
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Routing;

use HDAT\Domain\Routing\FailureCategory;
use HDAT\Domain\Routing\RouteCandidate;
use HDAT\Infrastructure\Persistence\RouteStateRepository;
use HDAT\Kernel\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Per-route circuit breaker.
 *
 * States (implicit, derived from circuit_open_until):
 *   closed     normal operation
 *   open       circuit_open_until in the future → route skipped by AiRouter
 *   half-open  circuit_open_until passed → route eligible again; one success
 *              resets it fully, one failure re-opens it
 *
 * RateLimit failures use the shorter `cooldown_429` window because they're
 * transient by nature; everything else uses the longer `circuit_ttl`.
 */
final class CircuitBreaker {

	public function __construct(
		private readonly RouteStateRepository $routeState,
	) {}

	public function isOpen( RouteCandidate $c ): bool {
		$state = $this->routeState->get( $c->provider, $c->model, $c->credentialId );

		if ( null === $state->circuitOpenUntil ) {
			return false;
		}

		return $state->circuitOpenUntil->getTimestamp() > time();
	}

	public function recordFailure( RouteCandidate $c, FailureCategory $category ): void {
		$state = $this->routeState->get( $c->provider, $c->model, $c->credentialId );

		++$state->consecutiveFailures;
		$state->lastFailureAt       = new \DateTimeImmutable();
		$state->lastFailureCategory = $category->value;

		$threshold = (int) Settings::get( 'circuit_threshold', 5 );
		if ( $state->consecutiveFailures >= $threshold ) {
			$ttl = FailureCategory::RateLimit === $category
				? (int) Settings::get( 'cooldown_429', 60 )
				: (int) Settings::get( 'circuit_ttl', 300 );

			$state->circuitOpenUntil = ( new \DateTimeImmutable() )->modify( "+{$ttl} seconds" );
		}

		$this->routeState->save( $state );
	}

	public function recordSuccess( RouteCandidate $c, ?int $latencyMs = null ): void {
		$this->routeState->resetFailures( $c->provider, $c->model, $c->credentialId );

		if ( null !== $latencyMs ) {
			$this->routeState->recordLatency( $c->provider, $c->model, $c->credentialId, $latencyMs );
		}
	}
}
