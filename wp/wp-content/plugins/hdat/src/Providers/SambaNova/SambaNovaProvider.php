<?php
/**
 * @package HDAT\Providers\SambaNova
 */

declare(strict_types=1);

namespace HDAT\Providers\SambaNova;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class SambaNovaProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'sambanova',
			label:              'SambaNova',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://api.sambanova.ai/v1',
			capabilities:       [ Capability::Chat ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           'SambaCloud OpenAI-compatible API; free/evaluation access may vary',
			regUrl:             'https://cloud.sambanova.ai/',
			modelsUrl:          'https://api.sambanova.ai/v1/models',
		);
	}
}
