<?php
/**
 * @package HDAT\Domain\Credential
 */

declare(strict_types=1);

namespace HDAT\Domain\Credential;

defined( 'ABSPATH' ) || exit;

final class CredentialId {

	public function __construct(
		public readonly int $value,
	) {}

	public static function new(): self {
		return new self( 0 );
	}

	public function isNew(): bool {
		return 0 === $this->value;
	}
}
