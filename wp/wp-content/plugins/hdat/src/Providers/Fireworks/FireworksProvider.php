<?php
/**
 * @package HDAT\Providers\Fireworks
 */

declare(strict_types=1);

namespace HDAT\Providers\Fireworks;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class FireworksProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'fireworks',
			label:              'Fireworks AI',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Paid,
			baseUrl:            'https://api.fireworks.ai/inference/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse, Capability::Image, Capability::Embedding ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           'OpenAI-compatible serverless/dedicated inference',
			regUrl:             'https://app.fireworks.ai/settings/users/api-keys',
			modelsUrl:          'https://api.fireworks.ai/inference/v1/models',
		);
	}
}
