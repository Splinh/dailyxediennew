<?php
/**
 * @package HDAT\Providers\Moonshot
 */

declare(strict_types=1);

namespace HDAT\Providers\Moonshot;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class MoonshotProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'moonshot',
			label:              'Moonshot AI (Kimi)',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Paid,
			baseUrl:            'https://api.moonshot.ai/v1',
			capabilities:       [ Capability::Chat, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'official',
			rateInfo:           'OpenAI-compatible Kimi API; pricing varies by model',
			regUrl:             'https://platform.kimi.ai/console/api-keys',
			modelsUrl:          'https://api.moonshot.ai/v1/models',
		);
	}
}
