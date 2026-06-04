<?php
/**
 * @package HDAT\Providers\Together
 */

declare(strict_types=1);

namespace HDAT\Providers\Together;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class TogetherProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'together',
			label:              'Together AI',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Paid,
			baseUrl:            'https://api.together.ai/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse, Capability::Image, Capability::Embedding ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           'OpenAI-compatible inference for OSS models; free signup may include credits',
			regUrl:             'https://api.together.ai/settings/api-keys',
			modelsUrl:          'https://api.together.ai/v1/models',
		);
	}
}
