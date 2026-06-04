<?php
/**
 * @package HDAT\Infrastructure\Persistence
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Persistence;

use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Credential\CredentialId;
use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Infrastructure\Crypto\KeyEncryptor;
use HDAT\Infrastructure\DB\DB;
use HDAT\Infrastructure\DB\Schema;
use HDAT\Providers\Custom\CustomProviderMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Read/write Credential entities.
 *
 * Entity field <-> column map:
 *   rpmLimit / rpdLimit / tpmLimit / tpdLimit  ←  default_rpm / default_rpd / default_tpm / default_tpd
 *   apiKey (plaintext in memory)               ←→  api_key_enc (encrypted at rest)
 *
 * `apiKey` is encrypted on save() and decrypted on hydrate(). Plain text never
 * touches the DB.
 */
final class CredentialRepository {

	public function __construct(
		private readonly KeyEncryptor $encryptor,
	) {}

	/**
	 * @return Credential[]
	 */
	public function findActive(): array {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = DB::db()->get_results(
			DB::db()->prepare( 'SELECT * FROM %i WHERE is_active = 1 AND (cooldown_until IS NULL OR cooldown_until < NOW()) ORDER BY priority DESC, id ASC', $table ),
			ARRAY_A
		);

		return array_map( [ $this, 'hydrate' ], $rows ?: [] );
	}

	public function findById( CredentialId $id ): Credential {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$row = DB::db()->get_row(
			DB::db()->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id->value ),
			ARRAY_A
		);

		if ( ! $row ) {
			throw new \RuntimeException( "Credential not found: id={$id->value}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return $this->hydrate( $row );
	}

	/**
	 * @return Credential[]
	 */
	public function findByProvider( string $provider ): array {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = DB::db()->get_results(
			DB::db()->prepare(
				'SELECT * FROM %i WHERE provider = %s ORDER BY priority DESC, id ASC',
				$table,
				$provider
			),
			ARRAY_A
		);

		return array_map( [ $this, 'hydrate' ], $rows ?: [] );
	}

	public function save( Credential $cred ): CredentialId {
		// Encode custom provider metadata with error handling
		$customProviderMetaJson = null;
		if ( null !== $cred->customProviderMeta ) {
			$metaArray              = $cred->customProviderMeta->toArray();
			$customProviderMetaJson = wp_json_encode( $metaArray );
			if ( false === $customProviderMetaJson ) {
				throw new \RuntimeException( 'Failed to encode custom provider metadata to JSON' );
			}
		}

		// Encode capabilities with error handling
		$capabilitiesJson = wp_json_encode( array_map( static fn( Capability $c ) => $c->value, $cred->capabilities ) );
		if ( false === $capabilitiesJson ) {
			throw new \RuntimeException( 'Failed to encode capabilities to JSON' );
		}

		$data = [
			'provider'             => $cred->provider,
			'label'                => $cred->label,
			'api_key_enc'          => $this->encryptor->encrypt( $cred->apiKey ),
			'api_key_hash'         => $this->encryptor->fingerprint( $cred->apiKey ),
			'base_url'             => $cred->baseUrl ?? '',
			'tier'                 => $cred->tier->value,
			'priority'             => $cred->priority,
			'is_active'            => $cred->isActive ? 1 : 0,
			'capabilities_json'    => $capabilitiesJson,
			'default_rpm'          => $cred->rpmLimit,
			'default_rpd'          => $cred->rpdLimit,
			'default_tpm'          => $cred->tpmLimit,
			'default_tpd'          => $cred->tpdLimit,
			'daily_token_limit'    => $cred->dailyTokenLimit,
			'monthly_token_limit'  => $cred->monthlyTokenLimit,
			'cooldown_until'       => $cred->cooldownUntil?->format( 'Y-m-d H:i:s' ),
			'preferred_model'      => $cred->preferredModel,
			'custom_provider_meta' => $customProviderMetaJson,
		];

		if ( $cred->id->isNew() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = DB::db()->insert( $this->table(), $data );
			if ( false === $result ) {
				throw new \RuntimeException( 'Failed to insert credential: ' . DB::db()->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
			return new CredentialId( (int) DB::db()->insert_id );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = DB::db()->update( $this->table(), $data, [ 'id' => $cred->id->value ] );
		if ( false === $result ) {
			throw new \RuntimeException( 'Failed to update credential: ' . DB::db()->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return $cred->id;
	}

	public function delete( CredentialId $id ): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->delete( $this->table(), [ 'id' => $id->value ] );
	}

	public function setCooldown( CredentialId $id, \DateTimeImmutable $until ): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->update(
			$this->table(),
			[ 'cooldown_until' => $until->format( 'Y-m-d H:i:s' ) ],
			[ 'id' => $id->value ]
		);
	}

	/**
	 * Clear ALL credential cooldowns. Used for bulk recovery after provider outage.
	 */
	public function clearAllCooldowns(): int {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) DB::db()->query(
			DB::db()->prepare( 'UPDATE %i SET cooldown_until = NULL WHERE cooldown_until IS NOT NULL', $table )
		);
	}

	public function recordUsage( CredentialId $id ): void {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->query(
			DB::db()->prepare( 'UPDATE %i SET last_used_at = NOW() WHERE id = %d', $table, $id->value )
		);
	}

	/**
	 * Update the model_status flag on a credential.
	 *
	 * @param ?string $status NULL (ok), 'deprecated', 'unknown'.
	 */
	public function setModelStatus( CredentialId $id, ?string $status ): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		DB::db()->update(
			$this->table(),
			[ 'model_status' => $status ],
			[ 'id' => $id->value ]
		);
	}

	/**
	 * Count active credentials with a deprecated preferred model.
	 */
	public function countDeprecated(): int {
		$table = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) DB::db()->get_var(
			DB::db()->prepare(
				"SELECT COUNT(*) FROM %i WHERE is_active = 1 AND model_status = 'deprecated'",
				$table
			)
		);
	}

	/**
	 * @return object{items: Credential[], total: int, pages: int}
	 */
	public function paginate( int $page, int $perPage, ?string $provider = null ): object {
		$table  = $this->table();
		$offset = max( 0, ( $page - 1 ) * $perPage );

		if ( null !== $provider && '' !== $provider ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) DB::db()->get_var(
				DB::db()->prepare( 'SELECT COUNT(*) FROM %i WHERE provider = %s', $table, $provider )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = DB::db()->get_results(
				DB::db()->prepare(
					'SELECT * FROM %i WHERE provider = %s ORDER BY priority DESC, id DESC LIMIT %d OFFSET %d',
					$table,
					$provider,
					$perPage,
					$offset
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) DB::db()->get_var(
				DB::db()->prepare( 'SELECT COUNT(*) FROM %i', $table )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = DB::db()->get_results(
				DB::db()->prepare(
					'SELECT * FROM %i ORDER BY priority DESC, id DESC LIMIT %d OFFSET %d',
					$table,
					$perPage,
					$offset
				),
				ARRAY_A
			);
		}

		return (object) [
			'items' => array_map( [ $this, 'hydrate' ], $rows ?: [] ),
			'total' => $total,
			'pages' => $perPage > 0 ? (int) ceil( $total / $perPage ) : 1,
		];
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function hydrate( array $row ): Credential {
		$capabilities = [];
		$rawCaps      = json_decode( (string) ( $row['capabilities_json'] ?? '[]' ), true );
		if ( is_array( $rawCaps ) ) {
			foreach ( $rawCaps as $cap ) {
				$enum = Capability::tryFrom( (string) $cap );
				if ( null !== $enum ) {
					$capabilities[] = $enum;
				}
			}
		}

		$customProviderMeta = null;
		$metaData           = null;
		if ( ! empty( $row['custom_provider_meta'] ) ) {
			$metaData = json_decode( (string) $row['custom_provider_meta'], true );
			if ( is_array( $metaData ) ) {
				$customProviderMeta = CustomProviderMeta::fromArray( $metaData );
			}
		}

		$baseUrl = '' === (string) $row['base_url'] ? null : (string) $row['base_url'];
		if ( null === $baseUrl && is_array( $metaData ) ) {
			$legacyBaseUrl = $metaData['base_url'] ?? $metaData['baseUrl'] ?? null;
			if ( is_string( $legacyBaseUrl ) && '' !== trim( $legacyBaseUrl ) ) {
				$baseUrl = trim( $legacyBaseUrl );
			}
		}

		return new Credential(
			id:                new CredentialId( (int) $row['id'] ),
			provider:          (string) $row['provider'],
			label:             (string) $row['label'],
			apiKey:            $this->encryptor->decrypt( (string) $row['api_key_enc'] ),
			baseUrl:           $baseUrl,
			tier:              CredentialTier::from( (string) $row['tier'] ),
			priority:          (int) $row['priority'],
			isActive:          (bool) $row['is_active'],
			capabilities:      $capabilities,
			rpmLimit:          null !== $row['default_rpm'] ? (int) $row['default_rpm'] : null,
			rpdLimit:          null !== $row['default_rpd'] ? (int) $row['default_rpd'] : null,
			tpmLimit:          null !== $row['default_tpm'] ? (int) $row['default_tpm'] : null,
			tpdLimit:          null !== $row['default_tpd'] ? (int) $row['default_tpd'] : null,
			dailyTokenLimit:   null !== $row['daily_token_limit'] ? (int) $row['daily_token_limit'] : null,
			monthlyTokenLimit: null !== $row['monthly_token_limit'] ? (int) $row['monthly_token_limit'] : null,
			cooldownUntil: ! empty( $row['cooldown_until'] ) ? new \DateTimeImmutable( (string) $row['cooldown_until'] ) : null,
			preferredModel: ! empty( $row['preferred_model'] ) ? (string) $row['preferred_model'] : null,
			customProviderMeta: $customProviderMeta,
		);
	}

	private function table(): string {
		return Schema::table( Schema::AI_KEYS );
	}
}
