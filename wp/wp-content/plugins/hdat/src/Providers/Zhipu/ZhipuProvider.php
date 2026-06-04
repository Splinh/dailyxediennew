<?php
/**
 * @package HDAT\Providers\Zhipu
 */

declare(strict_types=1);

namespace HDAT\Providers\Zhipu;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ModelMeta;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class ZhipuProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'zhipu',
			label:              'Z.AI / BigModel (GLM)',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://api.z.ai/api/paas/v4',
			capabilities:       [ Capability::Chat, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'official',
			rateInfo:           'GLM Flash/free models available; China users may set base_url to open.bigmodel.cn',
			regUrl:             'https://z.ai/manage-apikey/apikey-list',
			modelsUrl:          'https://api.z.ai/api/paas/v4/models',
			staticModels:       [
				new ModelMeta( 'glm-4.7-flash', 'GLM-4.7 Flash', 128_000 ),
				new ModelMeta( 'glm-z1-flash', 'GLM-Z1 Flash (Reasoning)', 32_000 ),
			],
		);
	}
}
