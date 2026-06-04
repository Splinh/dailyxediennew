<?php
/**
 * @package HDAT\Providers\Nvidia
 */

declare(strict_types=1);

namespace HDAT\Providers\Nvidia;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class NvidiaProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'nvidia',
			label:              'NVIDIA NIM',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://integrate.api.nvidia.com/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse, Capability::Embedding ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           'Free/evaluation access via NVIDIA Developer Program; limits may change',
			regUrl:             'https://build.nvidia.com/',
			modelsUrl:          'https://integrate.api.nvidia.com/v1/models',
		);
	}
}
