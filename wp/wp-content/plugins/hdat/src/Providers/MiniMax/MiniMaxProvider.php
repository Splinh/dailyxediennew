<?php
/**
 * @package HDAT\Providers\MiniMax
 */

declare(strict_types=1);

namespace HDAT\Providers\MiniMax;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class MiniMaxProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'minimax',
			label:              'MiniMax',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Paid,
			baseUrl:            'https://api.minimax.io/v1',
			capabilities:       [ Capability::Chat, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: false,
			category:           'official',
			rateInfo:           'OpenAI-compatible chat completions; token plans vary by region',
			regUrl:             'https://platform.minimax.io/',
			modelsUrl:          'https://api.minimax.io/v1/models',
		);
	}
}
