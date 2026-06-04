<?php
/**
 * @package HDAT\Providers\Cohere
 */

declare(strict_types=1);

namespace HDAT\Providers\Cohere;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class CohereProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'cohere',
			label:              'Cohere',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://api.cohere.ai/compatibility/v1',
			capabilities:       [ Capability::Chat, Capability::FunctionCall, Capability::ToolUse, Capability::Embedding ],
			supportsLiveModels: false,
			category:           'official',
			rateInfo:           '1,000 API calls/month free (non-commercial)',
			regUrl:             'https://dashboard.cohere.com/api-keys',
			modelsUrl:          'https://api.cohere.ai/compatibility/v1/models',
		);
	}
}
