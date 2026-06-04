<?php
/**
 * @package HDAT\Providers\HuggingFace
 */

declare(strict_types=1);

namespace HDAT\Providers\HuggingFace;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class HuggingFaceProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'huggingface',
			label:              'Hugging Face',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://router.huggingface.co/v1',
			capabilities:       [ Capability::Chat ],
			supportsLiveModels: false,
			category:           'inference',
			rateInfo:           'Inference Providers free tier/credits; OpenAI-compatible chat endpoint only',
			regUrl:             'https://huggingface.co/settings/tokens',
			modelsUrl:          'https://router.huggingface.co/v1/models',
		);
	}
}
