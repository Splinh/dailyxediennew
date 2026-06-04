<?php
/**
 * @package HDAT\Providers\DeepSeek
 */

declare(strict_types=1);

namespace HDAT\Providers\DeepSeek;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class DeepSeekProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'deepseek',
			label:              'DeepSeek',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Paid,
			baseUrl:            'https://api.deepseek.com/v1',
			capabilities:       [ Capability::Chat, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'official',
			rateInfo:           'Pay-per-use (very cheap)',
			regUrl:             'https://platform.deepseek.com/api_keys',
			modelsUrl:          'https://api.deepseek.com/v1/models',
		);
	}
}
