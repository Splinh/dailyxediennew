<?php
/**
 * @package HDAT\Providers\Mistral
 */

declare(strict_types=1);

namespace HDAT\Providers\Mistral;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class MistralProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'mistral',
			label:              'Mistral AI',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://api.mistral.ai/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'official',
			rateInfo:           'Experiment/free plan for evaluation; limits vary by workspace',
			regUrl:             'https://console.mistral.ai/api-keys/',
			modelsUrl:          'https://api.mistral.ai/v1/models',
		);
	}
}
