<?php
/**
 * @package HDAT\Infrastructure\Persistence
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Persistence;

use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Consumer\ConsumerTokenId;
use HDAT\Infrastructure\DB\DB;
use HDAT\Infrastructure\DB\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Read/write ConsumerToken entities.
 *
 * Tokens are stored as sha256 hashes; the raw token is shown to the user
 * exactly once at creation time and never persisted.
 */
final class ConsumerTokenRepository {

	public function findById( ConsumerTokenId $id ): ConsumerToken {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = DB::db()->get_row(
			DB::db()->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id->value ),
			ARRAY_A
		);

		if ( ! $row ) {
			throw new \RuntimeException( "Consumer token not found: id={$id->value}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return $this->hydrate( $row );
	}

	public function findByRawToken( string $rawToken ): ?ConsumerToken {
		$table = $this->table();
		$hash  = hash( 'sha256', $rawToken );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = DB::db()->get_row(
			DB::db()->prepare( 'SELECT * FROM %i WHERE token_hash = %s', $table, $hash ),
			ARRAY_A
		);

		return $row ? $this->hydrate( $row ) : null;
	}

	/**
	 * @return ConsumerToken[]
	 */
	public function findAll(): array {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = DB::db()->get_results(
			DB::db()->prepare( 'SELECT * FROM %i ORDER BY id DESC', $table ),
			ARRAY_A
		);

		return array_map( [ $this, 'hydrate' ], $rows ?: [] );
	}

	/**
	 * Create a new consumer token. Returns the entity with its assigned ID
	 * and the raw token (returned separately — never persisted).
	 *
	 * @return array{token: ConsumerToken, raw: string}
	 */
	public function create(
		string $name,
		?int $rpmLimit = null,
		?int $rpdLimit = null,
		?int $tpmLimit = null,
		?int $tpdLimit = null,
		array $allowedProviders = [],
		array $allowedModels = [],
		bool $internalOnly = false,
		?\DateTimeImmutable $expiresAt = null,
	): array {
		$rawToken = 'hdat_' . bin2hex( random_bytes( 24 ) );
		$hash     = hash( 'sha256', $rawToken );
		$prefix   = substr( $rawToken, 0, 12 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->insert(
			$this->table(),
			[
				'name'                   => $name,
				'token_hash'             => $hash,
				'token_prefix'           => $prefix,
				'allowed_providers_json' => wp_json_encode( $allowedProviders ),
				'allowed_models_json'    => wp_json_encode( $allowedModels ),
				'internal_only'          => $internalOnly ? 1 : 0,
				'rpm_limit'              => $rpmLimit,
				'rpd_limit'              => $rpdLimit,
				'tpm_limit'              => $tpmLimit,
				'tpd_limit'              => $tpdLimit,
				'expires_at'             => $expiresAt?->format( 'Y-m-d H:i:s' ),
			]
		);

		$id    = new ConsumerTokenId( (int) DB::db()->insert_id );
		$token = new ConsumerToken(
			id:               $id,
			name:             $name,
			tokenHash:        $hash,
			tokenPrefix:      $prefix,
			rpmLimit:         $rpmLimit,
			rpdLimit:         $rpdLimit,
			tpmLimit:         $tpmLimit,
			tpdLimit:         $tpdLimit,
			allowedProviders: $allowedProviders,
			allowedModels:    $allowedModels,
			internalOnly:     $internalOnly,
			expiresAt:        $expiresAt,
		);

		return [
			'token' => $token,
			'raw'   => $rawToken,
		];
	}

	public function update(
		ConsumerTokenId $id,
		string $name,
		?int $rpmLimit = null,
		?int $rpdLimit = null,
		?int $tpmLimit = null,
		?int $tpdLimit = null,
		array $allowedProviders = [],
		array $allowedModels = [],
		bool $internalOnly = false,
		?\DateTimeImmutable $expiresAt = null,
	): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->update(
			$this->table(),
			[
				'name'                   => $name,
				'allowed_providers_json' => wp_json_encode( $allowedProviders ),
				'allowed_models_json'    => wp_json_encode( $allowedModels ),
				'internal_only'          => $internalOnly ? 1 : 0,
				'rpm_limit'              => $rpmLimit,
				'rpd_limit'              => $rpdLimit,
				'tpm_limit'              => $tpmLimit,
				'tpd_limit'              => $tpdLimit,
				'expires_at'             => $expiresAt?->format( 'Y-m-d H:i:s' ),
			],
			[ 'id' => $id->value ]
		);
	}

	public function revoke( ConsumerTokenId $id ): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->update(
			$this->table(),
			[ 'revoked_at' => current_time( 'mysql' ) ],
			[ 'id' => $id->value ]
		);
	}

	public function delete( ConsumerTokenId $id ): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->delete( $this->table(), [ 'id' => $id->value ] );
	}

	public function touch( ConsumerTokenId $id ): void {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->query(
			DB::db()->prepare( 'UPDATE %i SET last_used_at = NOW() WHERE id = %d', $table, $id->value )
		);
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function hydrate( array $row ): ConsumerToken {
		$providers = json_decode( (string) ( $row['allowed_providers_json'] ?? '[]' ), true ) ?: [];
		$models    = json_decode( (string) ( $row['allowed_models_json'] ?? '[]' ), true ) ?: [];

		return new ConsumerToken(
			id:               new ConsumerTokenId( (int) $row['id'] ),
			name:             (string) $row['name'],
			tokenHash:        (string) $row['token_hash'],
			tokenPrefix:      (string) $row['token_prefix'],
			rpmLimit:         null !== $row['rpm_limit'] ? (int) $row['rpm_limit'] : null,
			rpdLimit:         null !== $row['rpd_limit'] ? (int) $row['rpd_limit'] : null,
			tpmLimit:         null !== $row['tpm_limit'] ? (int) $row['tpm_limit'] : null,
			tpdLimit:         null !== $row['tpd_limit'] ? (int) $row['tpd_limit'] : null,
			allowedProviders: is_array( $providers ) ? $providers : [],
			allowedModels:    is_array( $models ) ? $models : [],
			internalOnly:     (bool) $row['internal_only'],
			expiresAt: ! empty( $row['expires_at'] ) ? new \DateTimeImmutable( (string) $row['expires_at'] ) : null,
			revokedAt: ! empty( $row['revoked_at'] ) ? new \DateTimeImmutable( (string) $row['revoked_at'] ) : null,
		);
	}

	private function table(): string {
		return Schema::table( Schema::CONSUMER_TOKENS );
	}
}
