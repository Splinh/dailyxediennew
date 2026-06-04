<?php
/**
 * @package HDAT\Domain\Consumer
 */

declare(strict_types=1);

namespace HDAT\Domain\Consumer;

defined( 'ABSPATH' ) || exit;

final class ConsumerToken {

	/**
	 * @param string[] $allowedProviders Empty = all providers allowed.
	 * @param string[] $allowedModels    Empty = all models allowed.
	 */
	public function __construct(
		public readonly ConsumerTokenId $id,
		public string $name,
		public string $tokenHash,
		public string $tokenPrefix,
		public ?int $rpmLimit = null,
		public ?int $rpdLimit = null,
		public ?int $tpmLimit = null,
		public ?int $tpdLimit = null,
		public array $allowedProviders = [],
		public array $allowedModels = [],
		public bool $internalOnly = false,
		public ?\DateTimeImmutable $expiresAt = null,
		public ?\DateTimeImmutable $revokedAt = null,
	) {}

	public function isValid(): bool {
		if ( null !== $this->revokedAt ) {
			return false;
		}

		if ( null !== $this->expiresAt && $this->expiresAt <= new \DateTimeImmutable() ) {
			return false;
		}

		return true;
	}

	public function allowsProvider( string $provider ): bool {
		if ( empty( $this->allowedProviders ) || in_array( $provider, $this->allowedProviders, true ) ) {
			return true;
		}

		return str_starts_with( $provider, 'custom:' ) && in_array( 'custom', $this->allowedProviders, true );
	}

	public function allowsModel( string $model ): bool {
		return empty( $this->allowedModels ) || in_array( $model, $this->allowedModels, true );
	}

	/**
	 * Internal pseudo-token for in-process calls (cron, WP-CLI).
	 */
	public static function internal(): self {
		return new self(
			id:           new ConsumerTokenId( 0 ),
			name:         'internal',
			tokenHash:    '',
			tokenPrefix:  'int_',
			internalOnly: true,
		);
	}
}
