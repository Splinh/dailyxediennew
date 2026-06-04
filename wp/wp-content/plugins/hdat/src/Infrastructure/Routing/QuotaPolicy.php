<?php
/**
 * @package HDAT\Infrastructure\Routing
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Routing;

use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Credential\Credential;
use HDAT\Infrastructure\Persistence\QuotaWindowRepository;

defined( 'ABSPATH' ) || exit;

/**
 * 4-dimension quota enforcement (RPM/RPD/TPM/TPD).
 *
 * Two subjects:
 *   - Consumer: checked up front (checkConsumerOrFail) — refuse before any
 *     provider call if the caller is over budget.
 *   - Credential: checked during candidate filtering (credentialHasCapacity)
 *     — a maxed-out key is skipped so the router falls through to the next.
 *
 * A null limit means "unlimited" for that dimension.
 */
final class QuotaPolicy {

	public function __construct(
		private readonly QuotaWindowRepository $windows,
	) {}

	/**
	 * @throws QuotaExceededException
	 */
	public function checkConsumerOrFail( ConsumerToken $token, int $estimatedTokens = 0 ): void {
		if ( $token->internalOnly && 0 === $token->id->value ) {
			return; // in-process pseudo-token bypasses quota
		}

		$w = $this->windows->getConsumerWindow( $token->id );

		$this->assert( $token->rpmLimit, $w->requestsThisMinute + 1, 'rpm' );
		$this->assert( $token->rpdLimit, $w->requestsToday + 1, 'rpd' );
		$this->assert( $token->tpmLimit, $w->tokensThisMinute + $estimatedTokens, 'tpm' );
		$this->assert( $token->tpdLimit, $w->tokensToday + $estimatedTokens, 'tpd' );
	}

	public function credentialHasCapacity( Credential $cred ): bool {
		$w = $this->windows->getCredentialWindow( $cred->id );

		if ( null !== $cred->rpmLimit && $w->requestsThisMinute >= $cred->rpmLimit ) {
			return false;
		}
		if ( null !== $cred->rpdLimit && $w->requestsToday >= $cred->rpdLimit ) {
			return false;
		}
		if ( null !== $cred->tpmLimit && $w->tokensThisMinute >= $cred->tpmLimit ) {
			return false;
		}
		if ( null !== $cred->tpdLimit && $w->tokensToday >= $cred->tpdLimit ) {
			return false;
		}
		if ( null !== $cred->dailyTokenLimit && $w->tokensToday >= $cred->dailyTokenLimit ) {
			return false;
		}

		return true;
	}

	/**
	 * @throws QuotaExceededException
	 */
	private function assert( ?int $limit, int $projected, string $dimension ): void {
		if ( null !== $limit && $limit > 0 && $projected > $limit ) {
			throw new QuotaExceededException( $dimension ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}
}
