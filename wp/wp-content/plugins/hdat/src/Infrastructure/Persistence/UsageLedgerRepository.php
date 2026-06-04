<?php
/**
 * @package HDAT\Infrastructure\Persistence
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Persistence;

use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Domain\Gateway\RouteContext;
use HDAT\Infrastructure\DB\DB;
use HDAT\Infrastructure\DB\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Append-only request-level usage ledger.
 *
 * Each completed request writes one row (success or failure). Aggregations
 * are computed at read time via getStats(). Old rows are pruned via cron.
 */
final class UsageLedgerRepository {

	public function record(
		GatewayResponse $resp,
		?RouteContext $ctx,
		ConsumerToken $consumer,
		string $status = 'success',
		?string $errorCode = null,
		?int $latencyMs = null,
	): void {
		// When ctx is null (cache hit), derive from response + use placeholder credential.
		$provider     = $ctx?->provider ?? $resp->provider;
		$model        = $ctx?->model ?? $resp->model;
		$credentialId = $ctx?->credentialId->value ?? 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->insert(
			$this->table(),
			[
				'consumer_token_id' => $consumer->id->value ?: null,
				'credential_id'     => $credentialId ?: null,
				'provider'          => $provider,
				'model'             => $model,
				'route_hash'        => $this->routeHash( $provider, $model, $credentialId ),
				'status'            => $status,
				'prompt_tokens'     => $resp->usage->promptTokens,
				'completion_tokens' => $resp->usage->completionTokens,
				'total_tokens'      => $resp->usage->totalTokens,
				'latency_ms'        => $latencyMs,
				'error_code'        => $errorCode,
			]
		);
	}

	/**
	 * Aggregated stats: summary totals + per-provider breakdown.
	 *
	 * @param array{provider?: string, from?: string, to?: string} $filters
	 * @return array{
	 *     summary: array{requests: int, tokens: int, prompt_tokens: int, completion_tokens: int},
	 *     by_provider: array<int, array<string, mixed>>
	 * }
	 */
	public function getStats( array $filters = [] ): array {
		$table = $this->table();

		$where  = [];
		$params = [ $table ];

		if ( ! empty( $filters['provider'] ) ) {
			$where[]  = 'provider = %s';
			$params[] = $filters['provider'];
		}
		if ( ! empty( $filters['from'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = $filters['from'];
		}
		if ( ! empty( $filters['to'] ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = $filters['to'];
		}

		$whereSql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$breakdownSql = "SELECT provider, model,
				COUNT(*)                                            AS requests,
				SUM(prompt_tokens)                                  AS prompt_tokens,
				SUM(completion_tokens)                              AS completion_tokens,
				SUM(total_tokens)                                   AS total_tokens,
				SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successes,
				SUM(CASE WHEN status != 'success' THEN 1 ELSE 0 END) AS errors,
				AVG(latency_ms)                                     AS avg_latency_ms
			FROM %i {$whereSql}
			GROUP BY provider, model
			ORDER BY MAX(created_at) DESC";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = DB::db()->get_results( DB::db()->prepare( $breakdownSql, ...$params ), ARRAY_A ) ?: [];

		$byProvider = array_map(
			static fn( array $r ) => [
				'provider'          => (string) ( $r['provider'] ?? '' ),
				'model'             => (string) ( $r['model'] ?? '' ),
				'requests'          => (int) ( $r['requests'] ?? 0 ),
				'prompt_tokens'     => (int) ( $r['prompt_tokens'] ?? 0 ),
				'completion_tokens' => (int) ( $r['completion_tokens'] ?? 0 ),
				'total_tokens'      => (int) ( $r['total_tokens'] ?? 0 ),
				'successes'         => (int) ( $r['successes'] ?? 0 ),
				'errors'            => (int) ( $r['errors'] ?? 0 ),
				'avg_latency_ms'    => (int) round( (float) ( $r['avg_latency_ms'] ?? 0 ) ),
			],
			$rows
		);

		$summary = [
			'requests'          => 0,
			'tokens'            => 0,
			'prompt_tokens'     => 0,
			'completion_tokens' => 0,
			'successes'         => 0,
			'errors'            => 0,
		];
		foreach ( $byProvider as $row ) {
			$summary['requests']          += $row['requests'];
			$summary['tokens']            += $row['total_tokens'];
			$summary['prompt_tokens']     += $row['prompt_tokens'];
			$summary['completion_tokens'] += $row['completion_tokens'];
			$summary['successes']         += $row['successes'];
			$summary['errors']            += $row['errors'];
		}

		// Per-error-code breakdown (only rows with errors).
		$errorWhere    = $where;
		$errorWhere[]  = "error_code IS NOT NULL AND error_code != ''";
		$errorWhereSql = 'WHERE ' . implode( ' AND ', $errorWhere );

		$errorSql = "SELECT error_code, COUNT(*) AS count
			FROM %i {$errorWhereSql}
			GROUP BY error_code
			ORDER BY count DESC";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$errorRows = DB::db()->get_results( DB::db()->prepare( $errorSql, ...$params ), ARRAY_A ) ?: [];

		$byError = array_map(
			static fn( array $r ) => [
				'error_code' => (string) ( $r['error_code'] ?? '' ),
				'count'      => (int) ( $r['count'] ?? 0 ),
			],
			$errorRows
		);

		return [
			'summary'     => $summary,
			'by_provider' => $byProvider,
			'by_error'    => $byError,
		];
	}

	public function totals( ?\DateTimeImmutable $since = null ): array {
		$table = $this->table();

		if ( null !== $since ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$row = DB::db()->get_row(
				DB::db()->prepare(
					'SELECT COUNT(*) AS requests, SUM(total_tokens) AS tokens FROM %i WHERE created_at >= %s',
					$table,
					$since->format( 'Y-m-d H:i:s' )
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$row = DB::db()->get_row(
				DB::db()->prepare(
					'SELECT COUNT(*) AS requests, SUM(total_tokens) AS tokens FROM %i',
					$table
				),
				ARRAY_A
			);
		}

		return [
			'requests' => (int) ( $row['requests'] ?? 0 ),
			'tokens'   => (int) ( $row['tokens'] ?? 0 ),
		];
	}

	public function pruneOld( int $days = 90 ): int {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) DB::db()->query(
			DB::db()->prepare(
				'DELETE FROM %i WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)',
				$table,
				$days
			)
		);
	}

	private function routeHash( string $provider, string $model, int $credentialId ): string {
		return substr( hash( 'sha256', "{$provider}|{$model}|{$credentialId}" ), 0, 64 );
	}

	private function table(): string {
		return Schema::table( Schema::USAGE_LEDGER );
	}
}
