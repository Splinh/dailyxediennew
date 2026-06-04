<?php
/**
 * @package HDAT\Domain\Routing
 */

declare(strict_types=1);

namespace HDAT\Domain\Routing;

use HDAT\Domain\Credential\CredentialId;
use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;

defined( 'ABSPATH' ) || exit;

final class RouteCandidate {

	/**
	 * @param Capability[] $capabilities
	 */
	public function __construct(
		public readonly CredentialId $credentialId,
		public readonly string $provider,
		public readonly string $model,
		public readonly CredentialTier $tier,
		public readonly float $priority,
		public readonly array $capabilities = [],
	) {}

	/**
	 * Stable identity of this route as (provider, model, credential).
	 *
	 * Must match RouteStateRepository::hash() so circuit state, route state,
	 * and sticky affinity all key the same triple.
	 */
	public function routeHash(): string {
		return hash( 'sha256', "{$this->provider}|{$this->model}|{$this->credentialId->value}" );
	}
}
