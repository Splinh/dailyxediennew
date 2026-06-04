<?php
/**
 * @package HDAT\Infrastructure\Persistence
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Persistence;

use HDAT\Domain\Consumer\ConsumerTokenId;
use HDAT\Domain\Credential\CredentialId;
use HDAT\Infrastructure\DB\DB;
use HDAT\Infrastructure\DB\Schema;
use HDAT\Kernel\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Sticky-route affinity table.
 *
 * When a consumer hits a working route, we remember it for a short window
 * (default 30 min, configurable via sticky_route_ttl) so subsequent identical
 * requests reuse the same (provider, model, credential). This dampens the
 * thundering herd when many requests arrive simultaneously and a route
 * becomes briefly hot.
 *
 * Phase 2 supplies the CRUD; integration with AiRouter follows in Phase 3.
 */
final class StickyRouteRepository {

	public function find( string $stickyKey ): ?array {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = DB::db()->get_row(
			DB::db()->prepare(
				'SELECT * FROM %i WHERE sticky_key = %s AND expires_at > NOW()',
				$table,
				$stickyKey
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function remember(
		string $stickyKey,
		?ConsumerTokenId $consumerId,
		string $routeHash,
		CredentialId $credentialId,
		string $provider,
		string $model,
	): void {
		$ttl = (int) Settings::get( 'sticky_route_ttl', 1800 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->replace(
			$this->table(),
			[
				'sticky_key'        => $stickyKey,
				'consumer_token_id' => $consumerId?->value ?: null,
				'route_hash'        => $routeHash,
				'credential_id'     => $credentialId->value,
				'provider'          => $provider,
				'model'             => $model,
				'last_used_at'      => current_time( 'mysql' ),
				'expires_at'        => gmdate( 'Y-m-d H:i:s', time() + $ttl ),
			]
		);
	}

	public function forget( string $stickyKey ): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->delete( $this->table(), [ 'sticky_key' => $stickyKey ] );
	}

	public function pruneExpired(): int {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) DB::db()->query( DB::db()->prepare( 'DELETE FROM %i WHERE expires_at < NOW()', $table ) );
	}

	private function table(): string {
		return Schema::table( Schema::STICKY_ROUTES );
	}
}
