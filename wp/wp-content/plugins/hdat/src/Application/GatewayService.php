<?php
/**
 * @package HDAT\Application
 */

declare(strict_types=1);

namespace HDAT\Application;

use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Domain\Gateway\GatewayUsage;
use HDAT\Domain\Gateway\RouteContext;
use HDAT\Domain\Routing\FailureCategory;
use HDAT\Domain\Routing\RouteCandidate;
use HDAT\Domain\Routing\RouterPolicy;
use HDAT\Domain\Routing\StickyKey;
use HDAT\Infrastructure\Http\CurlAdapter;
use HDAT\Infrastructure\Http\ProviderException;
use HDAT\Infrastructure\Http\SseEmitter;
use HDAT\Infrastructure\Persistence\ConsumerTokenRepository;
use HDAT\Infrastructure\Persistence\CredentialRepository;
use HDAT\Infrastructure\Persistence\QuotaWindowRepository;
use HDAT\Infrastructure\Persistence\ResponseCacheRepository;
use HDAT\Infrastructure\Persistence\StickyRouteRepository;
use HDAT\Infrastructure\Persistence\UsageLedgerRepository;
use HDAT\Infrastructure\Routing\CircuitBreaker;
use HDAT\Infrastructure\Routing\QuotaPolicy;
use HDAT\Infrastructure\Routing\RequestShaper;
use HDAT\Infrastructure\Routing\TokenEstimator;
use HDAT\Kernel\ProviderRegistry;
use HDAT\Kernel\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Single use case for "send messages, get a reply".
 *
 * dispatch() = non-streaming, with response cache.
 * stream()   = streaming via SSE; cache always skipped.
 *
 * Try-loop strategy:
 *   - Pull ranked candidates from RouterPolicy.
 *   - For each candidate: build request, POST, parse, on success record
 *     usage + reset breaker, return.
 *   - On ProviderException: record breaker failure with the category, move to
 *     the next candidate.
 *   - If every candidate fails, throw NoRouteAvailableException with the last
 *     failure category so the API layer can map status correctly.
 */
final class GatewayService {

	public function __construct(
		private readonly RouterPolicy $router,
		private readonly CredentialRepository $credentials,
		private readonly ConsumerTokenRepository $tokens,
		private readonly CurlAdapter $http,
		private readonly CircuitBreaker $circuit,
		private readonly QuotaPolicy $quota,
		private readonly QuotaWindowRepository $quotaWindows,
		private readonly UsageLedgerRepository $ledger,
		private readonly ResponseCacheRepository $cache,
		private readonly TokenEstimator $estimator,
		private readonly RequestShaper $shaper,
		private readonly ?StickyRouteRepository $sticky = null,
	) {}

	/**
	 * Non-streaming path.
	 *
	 * @return array{0: GatewayResponse, 1: ?RouteContext}
	 */
	public function dispatch( GatewayRequest $req ): array {
		$req      = $this->shaper->shape( $req );
		$consumer = $this->resolveConsumer( $req );

		$estimated = $this->estimator->estimateMessages( $req->messages )
			+ $this->estimator->estimateCompletion( $req->maxTokens );
		$this->quota->checkConsumerOrFail( $consumer, $estimated );

		$cached = $this->cache->get( $req );
		if ( null !== $cached ) {
			// M2: Record usage even on cache hits so quota windows and token touch are accurate.
			if ( $consumer->id->value > 0 ) {
				$this->quotaWindows->recordConsumerUsage( $consumer->id, $cached->usage->totalTokens );
				$this->tokens->touch( $consumer->id );
			}

			// M2: Record cache hit in ledger for analytics visibility (ctx=null signals cache).
			$this->ledger->record( $cached, null, $consumer );

			return [ $cached, null ];
		}

		[ $response, $ctx ] = $this->tryRoute( $req, $consumer );

		$this->ledger->record( $response, $ctx, $consumer );
		$this->cache->store( $req, $response );

		if ( $consumer->id->value > 0 ) {
			$this->quotaWindows->recordConsumerUsage( $consumer->id, $response->usage->totalTokens );
			$this->tokens->touch( $consumer->id );
		}

		$this->quotaWindows->recordCredentialUsage( $ctx->credentialId, $response->usage->totalTokens );
		$this->credentials->recordUsage( $ctx->credentialId );

		$this->rememberSticky( $req, $consumer, $ctx );

		return [ $response, $ctx ];
	}

	/**
	 * Streaming path. Writes SSE chunks via $emitter; caller owns sendDone().
	 *
	 * @throws NoRouteAvailableException if every candidate fails.
	 */
	public function stream( GatewayRequest $req, SseEmitter $emitter ): RouteContext {
		$req      = $this->shaper->shape( $req );
		$consumer = $this->resolveConsumer( $req );

		$estimated = $this->estimator->estimateMessages( $req->messages )
			+ $this->estimator->estimateCompletion( $req->maxTokens );
		$this->quota->checkConsumerOrFail( $consumer, $estimated );

		$streamReq  = $req->withStream( true );
		$candidates = $this->router->resolve( $streamReq, $consumer );

		if ( empty( $candidates ) ) {
			throw new NoRouteAvailableException( 'no_route_available' );
		}

		$attempt   = 0;
		$lastError = null;
		/** @var string[] $errors */
		$errors = [];

		foreach ( $candidates as $candidate ) {
			++$attempt;
			$start = microtime( true );

			try {
				$this->streamCandidate( $streamReq, $candidate, $emitter );

				$latencyMs = (int) ( ( microtime( true ) - $start ) * 1000 );
				$this->circuit->recordSuccess( $candidate, $latencyMs );

				$ctx = new RouteContext(
					provider:     $candidate->provider,
					model:        $candidate->model,
					credentialId: $candidate->credentialId,
					attempts:     $attempt,
				);

				// Record estimated usage for streaming (no real usage data available).
				$promptTokens     = $this->estimator->estimateMessages( $req->messages );
				$completionTokens = (int) ceil( $emitter->getStreamedBytes() / 4 );
				$usage            = new GatewayUsage( $promptTokens, $completionTokens, $promptTokens + $completionTokens );

				$syntheticResp = new GatewayResponse(
					content:  '[streamed]',
					model:    $candidate->model,
					provider: $candidate->provider,
					usage:    $usage,
				);
				$this->ledger->record( $syntheticResp, $ctx, $consumer, 'success', null, $latencyMs );

				if ( $consumer->id->value > 0 ) {
					$this->quotaWindows->recordConsumerUsage( $consumer->id, $usage->totalTokens );
					$this->tokens->touch( $consumer->id );
				}
				$this->quotaWindows->recordCredentialUsage( $candidate->credentialId, $usage->totalTokens );
				$this->credentials->recordUsage( $candidate->credentialId );

				$this->rememberSticky( $req, $consumer, $ctx );

				return $ctx;
			} catch ( ProviderException $e ) {
				// M1: If bytes already streamed to client, retry would corrupt the response.
				if ( $emitter->getStreamedBytes() > 0 ) {
					$emitter->send( "\n[Error: stream interrupted — {$e->getMessage()}]" );
					$this->circuit->recordFailure( $candidate, $e->category );
					$this->applyCooldown( $candidate, $e->category );

					return new RouteContext(
						provider:     $candidate->provider,
						model:        $candidate->model,
						credentialId: $candidate->credentialId,
						attempts:     $attempt,
					);
				}

				$this->circuit->recordFailure( $candidate, $e->category );
				$this->applyCooldown( $candidate, $e->category );
				$lastError = $e;
				$errors[]  = "[{$candidate->provider}/{$candidate->model}] {$e->category->value}: {$e->getMessage()}";
			}
		}

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		$summary = implode( ' | ', $errors );
		$detail  = "all_candidates_failed ({$attempt} tried): {$summary}";

		// DC2: Forget sticky route on total failure — it's proven dead.
		$this->forgetSticky( $req, $consumer );

		throw new NoRouteAvailableException(
			$detail,
			$attempt,
			$lastError ? $lastError->category->value : '',
			$lastError,
		);
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	/**
	 * @return array{0: GatewayResponse, 1: RouteContext}
	 *
	 * @throws NoRouteAvailableException
	 */
	private function tryRoute( GatewayRequest $req, ConsumerToken $consumer ): array {
		$candidates = $this->router->resolve( $req, $consumer );

		if ( empty( $candidates ) ) {
			throw new NoRouteAvailableException( 'no_route_available' );
		}

		$attempt   = 0;
		$lastError = null;
		/** @var string[] $errors */
		$errors = [];

		foreach ( $candidates as $candidate ) {
			++$attempt;

			try {
				$cred     = $this->credentials->findById( $candidate->credentialId );
				$provider = $this->resolveProvider( $cred );

				// Pin the chosen model on the request so the provider sees it.
				$pinned = null === $req->model || $req->model !== $candidate->model
					? new GatewayRequest(
						messages:    $req->messages,
						model:       $candidate->model,
						provider:    $req->provider,
						temperature: $req->temperature,
						maxTokens:   $req->maxTokens,
						stream:      $req->stream,
						consumerId:  $req->consumerId,
						extra:       $req->extra,
					)
					: $req;

				[ $url, $headers, $body ] = $provider->buildRequest( $pinned, $cred );

				$start = microtime( true );
				$http  = $this->http->post( $url, $headers, $body );
				$ms    = (int) ( ( microtime( true ) - $start ) * 1000 );

				if ( $http['status'] >= 400 ) {
					$category = $provider->classifyError( $http['status'], $http['body'] );
					throw new ProviderException( $http['status'], $category, $this->extractError( $http['body'] ) );
				}

				$response = $provider->parseResponse( $http );

				$this->circuit->recordSuccess( $candidate, $ms );
				$ctx = new RouteContext(
					provider:     $candidate->provider,
					model:        $response->model ?: $candidate->model,
					credentialId: $candidate->credentialId,
					attempts:     $attempt,
				);

				return [ $response, $ctx ];
			} catch ( ProviderException $e ) {
				$this->circuit->recordFailure( $candidate, $e->category );
				$this->applyCooldown( $candidate, $e->category );
				$lastError = $e;
				$errors[]  = "[{$candidate->provider}/{$candidate->model}] {$e->category->value}: {$e->getMessage()}";
			} catch ( \Throwable $t ) {
				$this->circuit->recordFailure( $candidate, FailureCategory::Unknown );
				$lastError = new ProviderException( 0, FailureCategory::Unknown, $t->getMessage(), $t );
				$errors[]  = "[{$candidate->provider}/{$candidate->model}] unknown: {$t->getMessage()}";
			}
		}

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		$summary = implode( ' | ', $errors );
		$detail  = "all_candidates_failed ({$attempt} tried): {$summary}";

		// DC2: Forget sticky route on total failure — it's proven dead.
		$this->forgetSticky( $req, $consumer );

		throw new NoRouteAvailableException(
			$detail,
			$attempt,
			$lastError ? $lastError->category->value : '',
			$lastError,
		);
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	private function streamCandidate( GatewayRequest $req, RouteCandidate $candidate, SseEmitter $emitter ): void {
		$cred     = $this->credentials->findById( $candidate->credentialId );
		$provider = $this->resolveProvider( $cred );

		$pinned = new GatewayRequest(
			messages:    $req->messages,
			model:       $candidate->model,
			provider:    $req->provider,
			temperature: $req->temperature,
			maxTokens:   $req->maxTokens,
			stream:      true,
			consumerId:  $req->consumerId,
			extra:       $req->extra,
		);

		[ $url, $headers, $body ] = $provider->buildRequest( $pinned, $cred );

		$this->http->streamPost(
			$url,
			$headers,
			$body,
			static function ( string $line ) use ( $provider, $emitter ): void {
				$delta = $provider->parseStreamChunk( $line );
				if ( null !== $delta && '' !== $delta ) {
					$emitter->send( $delta );
				}
			}
		);
	}

	/**
	 * Resolve provider capsule, handling custom providers with credential metadata.
	 */
	private function resolveProvider( \HDAT\Domain\Credential\Credential $cred ): \HDAT\Domain\Provider\ProviderCapsule {
		return ProviderRegistry::getForCredential( $cred );
	}

	private function resolveConsumer( GatewayRequest $req ): ConsumerToken {
		if ( null === $req->consumerId || 0 === $req->consumerId->value ) {
			return ConsumerToken::internal();
		}

		return $this->tokens->findById( $req->consumerId );
	}

	/**
	 * @param array<string, mixed> $body
	 */
	private function extractError( array $body ): string {
		if ( isset( $body['error']['message'] ) && is_string( $body['error']['message'] ) ) {
			return $body['error']['message'];
		}
		if ( isset( $body['error'] ) && is_string( $body['error'] ) ) {
			return $body['error'];
		}

		return 'provider_error';
	}

	/**
	 * Pin this conversation to the route that just succeeded.
	 *
	 * No-op unless a StickyRouteRepository is wired AND the request carried a
	 * conversation/thread/session id (see StickyKey). The next turn with the
	 * same id will have this exact (provider, model, credential) promoted to
	 * the front of the candidate list by AiRouter::promoteSticky().
	 */
	private function rememberSticky( GatewayRequest $req, ConsumerToken $consumer, RouteContext $ctx ): void {
		if ( null === $this->sticky ) {
			return;
		}

		$key = StickyKey::derive( $req, $consumer->id );
		if ( null === $key ) {
			return;
		}

		$routeHash = hash( 'sha256', "{$ctx->provider}|{$ctx->model}|{$ctx->credentialId->value}" );

		$this->sticky->remember(
			$key,
			$consumer->id->value > 0 ? $consumer->id : null,
			$routeHash,
			$ctx->credentialId,
			$ctx->provider,
			$ctx->model,
		);
	}

	/**
	 * Cool a credential down after a rate-limit so the router stops re-offering
	 * its *other* models too.
	 *
	 * The per-route circuit breaker only trips the exact (provider, model,
	 * credential) triple; a 429 means the whole KEY is exhausted, so without
	 * this every other model on that credential would still be tried and 429
	 * again. findActive() already filters on cooldown_until, so a future
	 * resolve() simply skips the key until the window passes.
	 */
	private function applyCooldown( RouteCandidate $candidate, FailureCategory $category ): void {
		if ( FailureCategory::RateLimit !== $category ) {
			return;
		}

		$seconds = (int) Settings::get( 'cooldown_429', 60 );
		if ( $seconds <= 0 ) {
			return;
		}

		$this->credentials->setCooldown(
			$candidate->credentialId,
			( new \DateTimeImmutable() )->modify( "+{$seconds} seconds" )
		);
	}

	/**
	 * Forget a sticky route when routing fails completely.
	 *
	 * The remembered route is proven dead (all candidates exhausted), so clear
	 * it proactively instead of waiting for TTL expiry. On the next success,
	 * rememberSticky() will establish a new affinity.
	 */
	private function forgetSticky( GatewayRequest $req, ConsumerToken $consumer ): void {
		if ( null === $this->sticky ) {
			return;
		}

		$key = StickyKey::derive( $req, $consumer->id );
		if ( null !== $key ) {
			$this->sticky->forget( $key );
		}
	}
}
