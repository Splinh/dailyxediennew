<?php
/**
 * @package HDAT\Providers\Cloudflare
 */

declare(strict_types=1);

namespace HDAT\Providers\Cloudflare;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Cloudflare Workers AI.
 *
 * Base URL is account-specific:
 *   https://api.cloudflare.com/client/v4/accounts/{account_id}/ai/v1
 *
 * Users MUST set `base_url` per credential. The provider's default base URL
 * is intentionally empty so requests fail loudly until configured.
 */
final class CloudflareProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'cloudflare',
			label:              'Cloudflare Workers AI',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            '',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::Image, Capability::Embedding ],
			supportsLiveModels: false,
			category:           'inference',
			rateInfo:           'Account-specific base URL required: https://api.cloudflare.com/client/v4/accounts/{account_id}/ai/v1',
			regUrl:             'https://dash.cloudflare.com/?to=/:account/workers-ai',
			// modelsUrl intentionally null — base URL is account-specific, derived from cred->baseUrl.
		);
	}
}
