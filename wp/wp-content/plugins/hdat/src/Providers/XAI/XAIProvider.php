<?php
/**
 * @package HDAT\Providers\XAI
 */

declare(strict_types=1);

namespace HDAT\Providers\XAI;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class XAIProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'xai',
			label:              'xAI',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Paid,
			baseUrl:            'https://api.x.ai/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'official',
			rateInfo:           'OpenAI-compatible Grok API; paid usage',
			regUrl:             'https://console.x.ai/',
			modelsUrl:          'https://api.x.ai/v1/models',
		);
	}
}
