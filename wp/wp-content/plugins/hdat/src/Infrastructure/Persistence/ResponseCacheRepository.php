<?php
/**
 * @package HDAT\Infrastructure\Persistence
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Persistence;

use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Domain\Gateway\GatewayUsage;
use HDAT\Infrastructure\DB\DB;
use HDAT\Infrastructure\DB\Schema;
use HDAT\Kernel\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Deterministic-input response cache.
 *
 * Cache key is sha256 of (messages, model, temperature, max_tokens, extra).
 * Streaming responses are never cached (chunks are too short-lived; cache
 * skip happens at the GatewayService layer, not here).
 */
final class ResponseCacheRepository {

	public function get( GatewayRequest $req ): ?GatewayResponse {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = DB::db()->get_row(
			DB::db()->prepare(
				'SELECT * FROM %i WHERE hash_key = %s AND expires_at > NOW()',
				$table,
				$this->hash( $req )
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$payload = json_decode( (string) $row['response_json'], true );
		if ( ! is_array( $payload ) ) {
			return null;
		}

		$usage = $payload['usage'] ?? [];

		return new GatewayResponse(
			content:  (string) ( $payload['content'] ?? '' ),
			model:    (string) $row['model'],
			provider: (string) $row['provider'],
			usage:    new GatewayUsage(
				promptTokens:     (int) ( $usage['prompt_tokens'] ?? 0 ),
				completionTokens: (int) ( $usage['completion_tokens'] ?? 0 ),
				totalTokens:      (int) ( $usage['total_tokens'] ?? 0 ),
			),
			cached:   true,
		);
	}

	public function store( GatewayRequest $req, GatewayResponse $resp ): void {
		if ( $resp->cached ) {
			return;
		}

		$ttl = (int) Settings::get( 'cache_ttl', 0 );
		if ( $ttl <= 0 ) {
			return;
		}

		$payload = [
			'content' => $resp->content,
			'usage'   => [
				'prompt_tokens'     => $resp->usage->promptTokens,
				'completion_tokens' => $resp->usage->completionTokens,
				'total_tokens'      => $resp->usage->totalTokens,
			],
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->replace(
			$this->table(),
			[
				'hash_key'      => $this->hash( $req ),
				'provider'      => $resp->provider,
				'model'         => $resp->model,
				'response_json' => wp_json_encode( $payload ),
				'tokens_used'   => $resp->usage->totalTokens,
				'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + $ttl ),
			]
		);
	}

	public function pruneExpired(): int {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) DB::db()->query( DB::db()->prepare( 'DELETE FROM %i WHERE expires_at < NOW()', $table ) );
	}

	private function hash( GatewayRequest $req ): string {
		return hash(
			'sha256',
			(string) wp_json_encode(
				[
					'provider'    => $req->provider,
					'messages'    => $req->messages,
					'model'       => $req->model,
					'temperature' => $req->temperature,
					'max_tokens'  => $req->maxTokens,
					'extra'       => $req->extra,
				]
			)
		);
	}

	private function table(): string {
		return Schema::table( Schema::RESPONSE_CACHE );
	}
}
