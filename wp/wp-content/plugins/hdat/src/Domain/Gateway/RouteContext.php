<?php
/**
 * @package HDAT\Domain\Gateway
 */

declare(strict_types=1);

namespace HDAT\Domain\Gateway;

use HDAT\Domain\Credential\CredentialId;

defined( 'ABSPATH' ) || exit;

final class RouteContext {

	public function __construct(
		public readonly string $provider,
		public readonly string $model,
		public readonly CredentialId $credentialId,
		public readonly int $attempts,
	) {}

	public function toHeader(): string {
		return "{$this->provider}/{$this->model}";
	}
}
