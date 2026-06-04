<?php
/**
 * @package HDAT\Infrastructure\DB
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Centralised table-name constants.
 *
 * Tables are reused from hd-ai-toolkit (no rename) so existing data flows
 * through the new plugin without migration.
 */
final class Schema {

	public const AI_KEYS         = 'hdat_ai_keys';
	public const CONSUMER_TOKENS = 'hdat_consumer_tokens';
	public const USAGE_LEDGER    = 'hdat_usage_ledger';
	public const RESPONSE_CACHE  = 'hdat_response_cache';
	public const ROUTE_STATE     = 'hdat_route_state';
	public const QUOTA_WINDOWS   = 'hdat_quota_windows';
	public const STICKY_ROUTES   = 'hdat_sticky_routes';

	public static function table( string $name ): string {
		return DB::db()->prefix . $name;
	}

	/**
	 * @return string[]
	 */
	public static function all(): array {
		return [
			self::AI_KEYS,
			self::CONSUMER_TOKENS,
			self::USAGE_LEDGER,
			self::RESPONSE_CACHE,
			self::ROUTE_STATE,
			self::QUOTA_WINDOWS,
			self::STICKY_ROUTES,
		];
	}
}
