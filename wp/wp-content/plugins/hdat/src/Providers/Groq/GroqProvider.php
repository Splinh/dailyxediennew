<?php
/**
 * @package HDAT\Providers\Groq
 */

declare(strict_types=1);

namespace HDAT\Providers\Groq;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class GroqProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'groq',
			label:              'Groq',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://api.groq.com/openai/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           'Free, 14,400 RPD most models',
			regUrl:             'https://console.groq.com/keys',
			modelsUrl:          'https://api.groq.com/openai/v1/models',
		);
	}
}
