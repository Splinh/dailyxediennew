<?php
/**
 * @package HDAT\Domain\Provider
 */

declare(strict_types=1);

namespace HDAT\Domain\Provider;

use HDAT\Domain\Credential\CredentialTier;

defined( 'ABSPATH' ) || exit;

final class ProviderMeta {

	/**
	 * @param Capability[] $capabilities
	 * @param ModelMeta[]  $staticModels Fallback list when live fetch is disabled or fails.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly string $apiFormat,
		public readonly CredentialTier $tier,
		public readonly string $baseUrl,
		public readonly array $capabilities = [ Capability::Chat ],
		public readonly bool $supportsLiveModels = false,
		public readonly bool $allowBaseUrlOverride = true,
		public readonly string $category = 'official',
		public readonly string $rateInfo = '',
		public readonly ?string $regUrl = null,
		public readonly ?string $modelsUrl = null,
		public readonly array $staticModels = [],
	) {}
}
