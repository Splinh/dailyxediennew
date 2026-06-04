<?php
/**
 * @package HDAT\Domain\Routing
 */

declare(strict_types=1);

namespace HDAT\Domain\Routing;

use HDAT\Domain\Credential\CredentialId;

defined( 'ABSPATH' ) || exit;

/**
 * Mutable health/circuit state for a single (provider, model, credential) triple.
 *
 * Mutability is intentional — RouteScorer / CircuitBreaker need to mutate this
 * many times per request and persisting a fresh VO each time is wasteful. The
 * repository handles persistence; this is the working copy.
 */
final class RouteState {

	public function __construct(
		public int $id = 0,
		public string $routeHash = '',
		public string $provider = '',
		public string $model = '',
		public ?CredentialId $credentialId = null,
		public int $consecutiveFailures = 0,
		public int $avgLatencyMs = 0,
		public ?\DateTimeImmutable $lastSuccessAt = null,
		public ?\DateTimeImmutable $lastFailureAt = null,
		public string $lastFailureCategory = '',
		public ?\DateTimeImmutable $circuitOpenUntil = null,
	) {}

	public function isNew(): bool {
		return 0 === $this->id;
	}
}
