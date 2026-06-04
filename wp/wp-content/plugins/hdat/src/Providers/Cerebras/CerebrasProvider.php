<?php
/**
 * @package HDAT\Providers\Cerebras
 */

declare(strict_types=1);

namespace HDAT\Providers\Cerebras;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class CerebrasProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'cerebras',
			label:              'Cerebras',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://api.cerebras.ai/v1',
			capabilities:       [ Capability::Chat, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           'Free/evaluation API key; high-throughput inference, limits vary by plan',
			regUrl:             'https://cloud.cerebras.ai/',
			modelsUrl:          'https://api.cerebras.ai/v1/models',
		);
	}
}
