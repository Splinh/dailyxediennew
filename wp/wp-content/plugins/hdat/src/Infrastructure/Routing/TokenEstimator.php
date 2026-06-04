<?php
/**
 * @package HDAT\Infrastructure\Routing
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Rough token estimator for pre-flight quota checks.
 *
 * We don't have a real tokenizer server-side and don't want one — the cost of
 * a perfectly accurate count isn't worth it for an admission check. ~4 chars
 * per token is the standard rule of thumb for English/code; good enough to
 * gate against limits. Real usage is reconciled from the provider's response.
 */
final class TokenEstimator {

	private const CHARS_PER_TOKEN = 4;

	/**
	 * @param array<int, array<string, mixed>> $messages
	 */
	public function estimateMessages( array $messages ): int {
		$chars = 0;
		foreach ( $messages as $msg ) {
			$content = $msg['content'] ?? '';
			if ( is_string( $content ) ) {
				$chars += strlen( $content );
			} elseif ( is_array( $content ) ) {
				$chars += strlen( (string) wp_json_encode( $content ) );
			}
		}

		return (int) ceil( $chars / self::CHARS_PER_TOKEN ) + 4; // +4 per-message overhead.
	}

	public function estimateText( string $text ): int {
		return (int) ceil( strlen( $text ) / self::CHARS_PER_TOKEN );
	}

	/**
	 * Reserve the completion (output) budget for admission.
	 *
	 * TPM/TPD pools are consumed by prompt + completion combined, so admitting
	 * on the prompt alone under-counts and lets a request that will blow the
	 * window slip through — costing a wasted provider round-trip and a 429.
	 * We reserve the caller's max_tokens, falling back to a sane default when
	 * it is unset or non-positive. Real usage is reconciled from the response.
	 */
	public function estimateCompletion( int $maxTokens, int $fallback = 1024 ): int {
		return $maxTokens > 0 ? $maxTokens : $fallback;
	}
}
