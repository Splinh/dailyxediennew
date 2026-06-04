<?php
/**
 * @package HDAT\Providers\DeepInfra
 */

declare(strict_types=1);

namespace HDAT\Providers\DeepInfra;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class DeepInfraProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'deepinfra',
			label:              'DeepInfra',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Paid,
			baseUrl:            'https://api.deepinfra.com/v1/openai',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse, Capability::Image, Capability::Embedding ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           'OpenAI-compatible inference for OSS models; pay-as-you-go',
			regUrl:             'https://deepinfra.com/dash/api_keys',
			modelsUrl:          'https://api.deepinfra.com/v1/openai/models',
		);
	}
}
