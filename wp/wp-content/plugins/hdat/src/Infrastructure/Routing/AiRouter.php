<?php
/**
 * @package HDAT\Infrastructure\Routing
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Routing;

use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ModelMeta;
use HDAT\Domain\Routing\RouteCandidate;
use HDAT\Domain\Routing\RouterPolicy;
use HDAT\Domain\Routing\StickyKey;
use HDAT\Infrastructure\Persistence\CredentialRepository;
use HDAT\Infrastructure\Persistence\StickyRouteRepository;
use HDAT\Kernel\ProviderRegistry;
use HDAT\Kernel\Settings;
use HDAT\Providers\OpenRouter\OpenRouterPool;

defined( 'ABSPATH' ) || exit;

/**
 * Builds and ranks route candidates.
 *
 * Pipeline:
 *   1. Gather active credentials.
 *   2. Filter by consumer allow-list, request-pinned provider, and router strategy.
 *   3. Expand each credential into (credential × model) candidates.
 *   4. Drop candidates whose circuit is open or whose credential is over quota.
 *   5. Sort by RouteScorer descending.
 *
 * Model expansion rules:
 *   - Request pins a model      → that model only.
 *   - OpenRouter credential     → the enabled pool models (priority-ordered).
 *   - Credential has preferred   → that model only.
 *   - Provider has staticModels  → those models.
 *   - Provider has modelsUrl     → chat models from ModelCache (live-fetched, cached).
 */
final class AiRouter implements RouterPolicy {

	public function __construct(
		private readonly CredentialRepository $credentials,
		private readonly RouteScorer $scorer,
		private readonly CircuitBreaker $circuit,
		private readonly QuotaPolicy $quota,
		private readonly ModelCache $modelCache,
		private readonly ?StickyRouteRepository $sticky = null,
	) {}

	public function resolve( GatewayRequest $req, ConsumerToken $consumer ): array {
		$strategy   = (string) Settings::get( 'router_strategy', 'auto' );
		$required   = $this->requiredCapabilities( $req );
		$candidates = [];

		// Force single provider mode: only use the specified credential.
		$forcedCredId = Settings::get( 'force_provider_credential_id' );
		if ( null !== $forcedCredId && is_int( $forcedCredId ) ) {
			return $this->resolveForcedProvider( $forcedCredId, $req, $consumer, $required );
		}

		foreach ( $this->credentials->findActive() as $cred ) {
			if ( ! $consumer->allowsProvider( $cred->provider ) ) {
				continue;
			}
			if ( ! $this->matchesRequestedProvider( $cred->provider, $req->provider ) ) {
				continue;
			}
			if ( ! $this->matchesStrategy( $cred->tier, $strategy ) ) {
				continue;
			}
			if ( ! $this->supportsCapabilities( $cred->capabilities, $required ) ) {
				continue;
			}
			if ( ! $this->quota->credentialHasCapacity( $cred ) ) {
				continue;
			}

			foreach ( $this->modelsFor( $cred, $req ) as $model ) {
				if ( ! $consumer->allowsModel( $model ) ) {
					continue;
				}

				$candidate = new RouteCandidate(
					credentialId: $cred->id,
					provider:     $cred->provider,
					model:        $model,
					tier:         $cred->tier,
					priority:     (float) $cred->priority,
					capabilities: $cred->capabilities,
				);

				if ( $this->circuit->isOpen( $candidate ) ) {
					continue;
				}

				$candidates[] = $candidate;
			}
		}

		// Pre-compute scores so jitter is stable within a single resolve() call.
		$scores = new \SplObjectStorage();
		foreach ( $candidates as $c ) {
			$scores[ $c ] = $this->scorer->score( $c );
		}

		usort(
			$candidates,
			static fn( RouteCandidate $a, RouteCandidate $b ): int => $scores[ $b ] <=> $scores[ $a ]
		);

		$max = (int) Settings::get( 'max_route_attempts', 6 );

		$ordered = $max > 0 ? $this->interleaveByCredential( $candidates, $max ) : $candidates;

		return $this->promoteSticky( $ordered, $req, $consumer );
	}

	/**
	 * Move a remembered sticky route to the front of the candidate list.
	 *
	 * Keeps a multi-turn conversation on the same backend (behavioural
	 * consistency + provider-side KV-cache locality) without removing the
	 * fallbacks: if the sticky route's circuit has since opened or its
	 * credential dropped out, it was already filtered above and simply won't
	 * be present to promote, so routing falls through normally.
	 *
	 * @param RouteCandidate[] $ordered
	 * @return RouteCandidate[]
	 */
	private function promoteSticky( array $ordered, GatewayRequest $req, ConsumerToken $consumer ): array {
		if ( null === $this->sticky || count( $ordered ) < 2 ) {
			return $ordered;
		}

		$key = StickyKey::derive( $req, $consumer->id );
		if ( null === $key ) {
			return $ordered;
		}

		$row = $this->sticky->find( $key );
		if ( null === $row ) {
			return $ordered;
		}

		$wantedHash = (string) ( $row['route_hash'] ?? '' );
		if ( '' === $wantedHash ) {
			return $ordered;
		}

		foreach ( $ordered as $i => $candidate ) {
			if ( $candidate->routeHash() === $wantedHash ) {
				if ( $i > 0 ) {
					unset( $ordered[ $i ] );
					array_unshift( $ordered, $candidate );
				}
				break;
			}
		}

		return array_values( $ordered );
	}

	/**
	 * Round-robin interleave candidates across credentials.
	 *
	 * Ensures every active credential gets at least one slot before any
	 * credential fills a second, preventing a single multi-model provider
	 * (e.g. OpenRouter pool) from monopolising all route attempts.
	 *
	 * Within each credential group the original score-descending order is
	 * preserved, so the best model per credential is always picked first.
	 *
	 * @param RouteCandidate[] $sorted Score-sorted candidates (best first).
	 * @return RouteCandidate[]
	 */
	private function interleaveByCredential( array $sorted, int $limit ): array {
		// Group by credential, preserving sort order within each group.
		/** @var array<int, RouteCandidate[]> $groups */
		$groups = [];
		foreach ( $sorted as $c ) {
			$groups[ $c->credentialId->value ][] = $c;
		}

		$result      = [];
		$resultCount = 0;
		$round       = 0;
		$hasMore     = true;

		while ( $resultCount < $limit && $hasMore ) {
			$hasMore = false;
			foreach ( $groups as &$group ) {
				if ( ! isset( $group[ $round ] ) ) {
					continue;
				}
				$result[] = $group[ $round ];
				++$resultCount;
				$hasMore = true;
				if ( $resultCount >= $limit ) {
					break;
				}
			}
			unset( $group );
			++$round;
		}

		return $result;
	}

	private function matchesStrategy( CredentialTier $tier, string $strategy ): bool {
		return match ( $strategy ) {
			'free_only' => CredentialTier::Free === $tier,
			'paid_only' => CredentialTier::Paid === $tier,
			default     => true,
		};
	}

	/**
	 * Capabilities a request demands, inferred from its shape.
	 *
	 * - tools / tool_choice in extra  → ToolUse + FunctionCall
	 * - an image part in any message  → Vision
	 *
	 * Chat is implied for every request, so it is never returned here (every
	 * credential is assumed chat-capable unless it declares otherwise and the
	 * request needs something more specific).
	 *
	 * @return Capability[]
	 */
	private function requiredCapabilities( GatewayRequest $req ): array {
		$required = [];

		if ( ! empty( $req->extra['tools'] ) || ! empty( $req->extra['tool_choice'] ) ) {
			$required[] = Capability::ToolUse;
			$required[] = Capability::FunctionCall;
		}

		if ( $this->hasImageInput( $req ) ) {
			$required[] = Capability::Vision;
		}

		return $required;
	}

	/**
	 * A credential is eligible when it declares NO capabilities (treated as
	 * unconstrained) or declares at least one of each required capability.
	 *
	 * For tool use we accept either ToolUse OR FunctionCall, since providers
	 * label the same feature differently.
	 *
	 * @param Capability[] $declared
	 * @param Capability[] $required
	 */
	private function supportsCapabilities( array $declared, array $required ): bool {
		if ( empty( $declared ) || empty( $required ) ) {
			return true;
		}

		$toolEquivalents = [ Capability::ToolUse, Capability::FunctionCall ];

		foreach ( $required as $cap ) {
			if ( in_array( $cap, $toolEquivalents, true ) ) {
				if ( ! array_intersect( $toolEquivalents, $declared ) ) {
					return false;
				}
				continue;
			}

			if ( ! in_array( $cap, $declared, true ) ) {
				return false;
			}
		}

		return true;
	}

	private function hasImageInput( GatewayRequest $req ): bool {
		foreach ( $req->messages as $message ) {
			$content = $message['content'] ?? null;
			if ( ! is_array( $content ) ) {
				continue;
			}

			foreach ( $content as $part ) {
				if ( is_array( $part ) && in_array( (string) ( $part['type'] ?? '' ), [ 'image_url', 'input_image' ], true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return string[]
	 */
	private function modelsFor( Credential $cred, GatewayRequest $req ): array {
		if ( null !== $req->model && '' !== $req->model ) {
			return [ $req->model ];
		}

		if ( 'openrouter' === $cred->provider ) {
			return $this->openRouterModels( $cred );
		}

		if ( null !== $cred->preferredModel && '' !== $cred->preferredModel ) {
			return [ $cred->preferredModel ];
		}

		if ( ! ProviderRegistry::hasForCredential( $cred ) ) {
			return [];
		}

		// Static models declared in provider meta.
		$meta = ProviderRegistry::metaForCredential( $cred );
		if ( $meta->staticModels ) {
			return array_map( static fn( $m ) => $m->id, $meta->staticModels );
		}

		// Live-fetched models (cached with TTL).
		return array_map(
			static fn( ModelMeta $m ) => $m->id,
			$this->modelCache->getModels( $cred )
		);
	}

	/**
	 * OpenRouter: preferred → pool → safety net (always appended).
	 *
	 * @return string[]
	 */
	private function openRouterModels( Credential $cred ): array {
		$models   = [];
		$fallback = CredentialTier::Free === $cred->tier
			? 'openrouter/free'
			: 'openrouter/auto';

		if ( null !== $cred->preferredModel && '' !== $cred->preferredModel ) {
			$models[] = $cred->preferredModel;
		}

		foreach ( OpenRouterPool::getEnabledModelIds() as $id ) {
			if ( ! in_array( $id, $models, true ) ) {
				$models[] = $id;
			}
		}

		if ( ! in_array( $fallback, $models, true ) ) {
			$models[] = $fallback;
		}

		return $models;
	}

	/**
	 * Resolve candidates for forced provider mode.
	 *
	 * When force_provider_credential_id is set, only that credential is used.
	 * No fallback, no interleaving, no sticky promotion. If the credential is
	 * unavailable or has no valid routes, returns empty array (triggers
	 * NoRouteAvailableException).
	 *
	 * @param Capability[] $required
	 * @return RouteCandidate[]
	 */
	private function resolveForcedProvider( int $credId, GatewayRequest $req, ConsumerToken $consumer, array $required ): array {
		$cred = null;

		// Find the forced credential among active credentials.
		foreach ( $this->credentials->findActive() as $c ) {
			if ( $c->id->value === $credId ) {
				$cred = $c;
				break;
			}
		}

		// Credential not found or not active → no routes available.
		if ( null === $cred ) {
			return [];
		}

		// Apply standard filters.
		if ( ! $consumer->allowsProvider( $cred->provider ) ) {
			return [];
		}
		if ( ! $this->matchesRequestedProvider( $cred->provider, $req->provider ) ) {
			return [];
		}
		if ( ! $this->supportsCapabilities( $cred->capabilities, $required ) ) {
			return [];
		}
		if ( ! $this->quota->credentialHasCapacity( $cred ) ) {
			return [];
		}

		$candidates = [];

		foreach ( $this->modelsFor( $cred, $req ) as $model ) {
			if ( ! $consumer->allowsModel( $model ) ) {
				continue;
			}

			$candidate = new RouteCandidate(
				credentialId: $cred->id,
				provider:     $cred->provider,
				model:        $model,
				tier:         $cred->tier,
				priority:     (float) $cred->priority,
				capabilities: $cred->capabilities,
			);

			if ( $this->circuit->isOpen( $candidate ) ) {
				continue;
			}

			$candidates[] = $candidate;
		}

		// Score and sort, but no interleaving (single credential).
		$scores = new \SplObjectStorage();
		foreach ( $candidates as $c ) {
			$scores[ $c ] = $this->scorer->score( $c );
		}

		usort(
			$candidates,
			static fn( RouteCandidate $a, RouteCandidate $b ): int => $scores[ $b ] <=> $scores[ $a ]
		);

		return $candidates;
	}

	private function matchesRequestedProvider( string $credentialProvider, ?string $requestedProvider ): bool {
		if ( null === $requestedProvider || '' === $requestedProvider ) {
			return true;
		}
		if ( $credentialProvider === $requestedProvider ) {
			return true;
		}

		return 'custom' === $requestedProvider && ProviderRegistry::isCustomId( $credentialProvider );
	}
}
