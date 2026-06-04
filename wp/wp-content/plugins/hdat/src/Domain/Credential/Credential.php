<?php
/**
 * @package HDAT\Domain\Credential
 */

declare(strict_types=1);

namespace HDAT\Domain\Credential;

use HDAT\Domain\Provider\Capability;
use HDAT\Providers\Custom\CustomProviderMeta;

defined( 'ABSPATH' ) || exit;

final class Credential {

	/**
	 * @param Capability[] $capabilities
	 */
	public function __construct(
		public readonly CredentialId $id,
		public string $provider,
		public string $label,
		public string $apiKey,
		public ?string $baseUrl = null,
		public CredentialTier $tier = CredentialTier::Free,
		public int $priority = 5,
		public bool $isActive = true,
		public array $capabilities = [],
		public ?int $rpmLimit = null,
		public ?int $rpdLimit = null,
		public ?int $tpmLimit = null,
		public ?int $tpdLimit = null,
		public ?int $dailyTokenLimit = null,
		public ?int $monthlyTokenLimit = null,
		public ?\DateTimeImmutable $cooldownUntil = null,
		public ?string $preferredModel = null,
		public ?CustomProviderMeta $customProviderMeta = null,
	) {}
}
