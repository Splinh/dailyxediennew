<?php
/**
 * @package HDAT\Infrastructure\Persistence
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Persistence;

use HDAT\Domain\Credential\CredentialId;
use HDAT\Domain\Routing\RouteState;
use HDAT\Infrastructure\DB\DB;
use HDAT\Infrastructure\DB\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Per-route health state used by CircuitBreaker + RouteScorer.
 *
 * Route is keyed by (provider, model, credential_id). The hash is stored so
 * the unique index can enforce one row per route across (route_hash, scope).
 * We use a single 'model' scope today; the column is kept open for future
 * per-consumer scoping.
 */
final class RouteStateRepository {

	/**
	 * Get or create the route state row for this triple.
	 */
	public function get( string $provider, string $model, CredentialId $credentialId ): RouteState {
		$hash  = $this->hash( $provider, $model, $credentialId->value );
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = DB::db()->get_row(
			DB::db()->prepare( 'SELECT * FROM %i WHERE route_hash = %s AND scope = %s', $table, $hash, 'model' ),
			ARRAY_A
		);

		if ( ! $row ) {
			return new RouteState(
				routeHash:    $hash,
				provider:     $provider,
				model:        $model,
				credentialId: $credentialId,
			);
		}

		return new RouteState(
			id:                  (int) $row['id'],
			routeHash:           (string) $row['route_hash'],
			provider:            (string) $row['provider'],
			model:               (string) $row['model'],
			credentialId:        new CredentialId( (int) ( $row['credential_id'] ?? 0 ) ),
			consecutiveFailures: (int) $row['consecutive_failures'],
			avgLatencyMs:        (int) ( $row['avg_latency_ms'] ?? 0 ),
			lastSuccessAt: ! empty( $row['last_success_at'] ) ? new \DateTimeImmutable( (string) $row['last_success_at'] ) : null,
			lastFailureAt: ! empty( $row['last_failure_at'] ) ? new \DateTimeImmutable( (string) $row['last_failure_at'] ) : null,
			lastFailureCategory: (string) ( $row['last_failure_category'] ?? '' ),
			circuitOpenUntil: ! empty( $row['circuit_open_until'] ) ? new \DateTimeImmutable( (string) $row['circuit_open_until'] ) : null,
		);
	}

	public function save( RouteState $state ): void {
		$data = [
			'route_hash'            => $state->routeHash,
			'scope'                 => 'model',
			'provider'              => $state->provider,
			'model'                 => $state->model,
			'credential_id'         => $state->credentialId?->value ?: null,
			'consecutive_failures'  => $state->consecutiveFailures,
			'avg_latency_ms'        => $state->avgLatencyMs,
			'last_success_at'       => $state->lastSuccessAt?->format( 'Y-m-d H:i:s' ),
			'last_failure_at'       => $state->lastFailureAt?->format( 'Y-m-d H:i:s' ),
			'last_failure_category' => $state->lastFailureCategory,
			'circuit_open_until'    => $state->circuitOpenUntil?->format( 'Y-m-d H:i:s' ),
		];

		if ( $state->isNew() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			DB::db()->insert( $this->table(), $data );
			$state->id = (int) DB::db()->insert_id;
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->update( $this->table(), $data, [ 'id' => $state->id ] );
	}

	public function resetFailures( string $provider, string $model, CredentialId $credentialId ): void {
		$state = $this->get( $provider, $model, $credentialId );

		$state->consecutiveFailures = 0;
		$state->circuitOpenUntil    = null;
		$state->lastSuccessAt       = new \DateTimeImmutable();

		$this->save( $state );
	}

	public function recordLatency( string $provider, string $model, CredentialId $credentialId, int $latencyMs ): void {
		$state = $this->get( $provider, $model, $credentialId );

		// EMA with alpha=0.3; first sample seeds the average.
		$state->avgLatencyMs = 0 === $state->avgLatencyMs
			? $latencyMs
			: (int) round( 0.3 * $latencyMs + 0.7 * $state->avgLatencyMs );

		$this->save( $state );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function listAll(): array {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return DB::db()->get_results(
			DB::db()->prepare( 'SELECT * FROM %i ORDER BY updated_at DESC', $table ),
			ARRAY_A
		) ?: [];
	}

	public function reset( string $routeHash ): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->delete( $this->table(), [ 'route_hash' => $routeHash ] );
	}

	/**
	 * Reset ALL route state rows. Used for bulk recovery after provider outage.
	 */
	public function resetAll(): int {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) DB::db()->query( DB::db()->prepare( 'DELETE FROM %i', $table ) );
	}

	public function pruneExpired( int $days = 30 ): int {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) DB::db()->query(
			DB::db()->prepare(
				'DELETE FROM %i WHERE updated_at < DATE_SUB(NOW(), INTERVAL %d DAY)',
				$table,
				$days
			)
		);
	}

	private function hash( string $provider, string $model, int $credentialId ): string {
		return hash( 'sha256', "{$provider}|{$model}|{$credentialId}" );
	}

	private function table(): string {
		return Schema::table( Schema::ROUTE_STATE );
	}
}
