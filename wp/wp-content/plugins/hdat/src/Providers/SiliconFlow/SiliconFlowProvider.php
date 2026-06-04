<?php
/**
 * @package HDAT\Providers\SiliconFlow
 */

declare(strict_types=1);

namespace HDAT\Providers\SiliconFlow;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class SiliconFlowProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'siliconflow',
			label:              'SiliconFlow',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://api.siliconflow.com/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse, Capability::Image, Capability::Embedding ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           'Free signup credits + permanently free models (e.g. Qwen2.5-7B). Pro/ prefix = paid.',
			regUrl:             'https://cloud.siliconflow.com/account/ak',
			modelsUrl:          'https://api.siliconflow.com/v1/models',
		);
	}
}
