<?php
/**
 * @package HDAT\Providers\OpenRouter
 */

declare(strict_types=1);

namespace HDAT\Providers\OpenRouter;

defined( 'ABSPATH' ) || exit;

/**
 * OpenRouter pool + per-model rate-limit state.
 *
 * Free models share a global pool; users can enable/disable individual models
 * and reorder priority via the admin UI. Rate-limit tracking is merged here
 * (instead of a separate class) because the data is the same shape and shares
 * the same lifecycle: refresh on each successful response, expire after TTL.
 *
 * Storage:
 *   - hdat_or_models   : array<int, ModelInfo>  cached free model list (TTL 6h)
 *   - hdat_or_pool     : array{models: array<string, PoolEntry>}  user-controlled pool
 *   - hdat_or_rl       : array<string, RateLimitState>  per-model RL bars
 *
 * Where:
 *   ModelInfo      = { id, name, context_length, pricing, modalities, top_provider }
 *   PoolEntry      = { enabled: bool, priority: int }
 *   RateLimitState = { limit: int, remaining: int, reset: int (unix), updated_at: int }
 */
final class OpenRouterPool {

	public const OPT_MODELS     = 'hdat_or_models';
	public const OPT_ALL_MODELS = 'hdat_or_all_models';
	public const OPT_POOL       = 'hdat_or_pool';
	public const OPT_RL         = 'hdat_or_rl';

	public const MODELS_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * @return array<int, array<string, mixed>>|null
	 */
	public static function getCachedModels(): ?array {
		$cache = get_option( self::OPT_MODELS, null );
		if ( ! is_array( $cache ) ) {
			return null;
		}

		$fetched = (int) ( $cache['fetched_at'] ?? 0 );
		if ( $fetched > 0 && ( time() - $fetched ) > self::MODELS_TTL ) {
			return null;
		}

		return $cache['models'] ?? null;
	}

	/**
	 * Full model list (all tiers). Used by credential modal for paid tier model selection.
	 *
	 * @return array<int, array<string, mixed>>|null
	 */
	public static function getCachedAllModels(): ?array {
		$cache = get_option( self::OPT_ALL_MODELS, null );
		if ( ! is_array( $cache ) ) {
			return null;
		}

		$fetched = (int) ( $cache['fetched_at'] ?? 0 );
		if ( $fetched > 0 && ( time() - $fetched ) > self::MODELS_TTL ) {
			return null;
		}

		return $cache['models'] ?? null;
	}

	/**
	 * @param array<int, array<string, mixed>> $models
	 */
	public static function setCachedModels( array $models ): void {
		update_option(
			self::OPT_MODELS,
			[
				'fetched_at' => time(),
				'models'     => $models,
			],
			false,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $models
	 */
	public static function setCachedAllModels( array $models ): void {
		update_option(
			self::OPT_ALL_MODELS,
			[
				'fetched_at' => time(),
				'models'     => $models,
			],
			false,
		);
	}

	/**
	 * @return array{models: array<string, array{enabled: bool, priority: int}>}
	 */
	public static function getPool(): array {
		$pool = get_option( self::OPT_POOL, null );
		if ( ! is_array( $pool ) || ! isset( $pool['models'] ) ) {
			return [ 'models' => [] ];
		}

		return [ 'models' => $pool['models'] ];
	}

	/**
	 * Persist pool config. Accepts the same shape getPool() returns
	 * (`['models' => array<id, {enabled, priority}>]`) so the admin UI can
	 * round-trip it without reshaping.
	 *
	 * @param array{models?: array<string, array{enabled?: bool, priority?: int}>} $config
	 */
	public static function setPool( array $config ): void {
		$models = $config['models'] ?? [];

		update_option( self::OPT_POOL, [ 'models' => is_array( $models ) ? $models : [] ], false );
	}

	/**
	 * Ordered list of enabled pool model IDs (highest priority first).
	 *
	 * @return string[]
	 */
	public static function getEnabledModelIds(): array {
		$pool = self::getPool()['models'];

		$enabled = array_filter( $pool, static fn( array $entry ) => ! empty( $entry['enabled'] ) );

		uasort( $enabled, static fn( array $a, array $b ) => ( $b['priority'] ?? 0 ) <=> ( $a['priority'] ?? 0 ) );

		return array_column( $enabled, 'id' );
	}

	/**
	 * @return array<string, array{limit: int, remaining: int, reset: int, updated_at: int}>
	 */
	public static function getAllRateLimits(): array {
		$rl = get_transient( self::OPT_RL );

		return is_array( $rl ) ? $rl : [];
	}

	/**
	 * Update rate-limit snapshot for one model from response headers.
	 *
	 * Headers: x-ratelimit-limit-requests, x-ratelimit-remaining-requests, x-ratelimit-reset-requests.
	 *
	 * @param array<string, string> $headers Lower-cased response header map.
	 */
	public static function recordRateLimit( string $modelId, array $headers ): void {
		$limit     = isset( $headers['x-ratelimit-limit-requests'] ) ? (int) $headers['x-ratelimit-limit-requests'] : null;
		$remaining = isset( $headers['x-ratelimit-remaining-requests'] ) ? (int) $headers['x-ratelimit-remaining-requests'] : null;
		$reset     = isset( $headers['x-ratelimit-reset-requests'] ) ? (int) $headers['x-ratelimit-reset-requests'] : null;

		if ( null === $limit && null === $remaining && null === $reset ) {
			return;
		}

		$all = self::getAllRateLimits();

		$all[ $modelId ] = [
			'limit'      => $limit ?? ( $all[ $modelId ]['limit'] ?? 0 ),
			'remaining'  => $remaining ?? ( $all[ $modelId ]['remaining'] ?? 0 ),
			'reset'      => $reset ?? ( $all[ $modelId ]['reset'] ?? 0 ),
			'updated_at' => time(),
		];

		// O4: Transient with 10min TTL — ephemeral RL data avoids autoload, and
		// respects object cache when available.
		set_transient( self::OPT_RL, $all, 10 * MINUTE_IN_SECONDS );
	}
}
