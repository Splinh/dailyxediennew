<?php
/**
 * @package HDAT\Providers\OpenAI
 */

declare(strict_types=1);

namespace HDAT\Providers\OpenAI;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class OpenAIProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'openai',
			label:              'OpenAI',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Paid,
			baseUrl:            'https://api.openai.com/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse, Capability::Image, Capability::Embedding ],
			supportsLiveModels: true,
			category:           'official',
			rateInfo:           'Pay-per-use',
			regUrl:             'https://platform.openai.com/api-keys',
			modelsUrl:          'https://api.openai.com/v1/models',
		);
	}
}
