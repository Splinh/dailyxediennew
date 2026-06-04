<?php
/**
 * @package HDAT\Providers\DashScope
 */

declare(strict_types=1);

namespace HDAT\Providers\DashScope;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class DashScopeProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'dashscope',
			label:              'Alibaba DashScope',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://dashscope.aliyuncs.com/compatible-mode/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'official',
			rateInfo:           'Free trial credits for Qwen models',
			regUrl:             'https://bailian.console.aliyun.com/',
			modelsUrl:          'https://dashscope.aliyuncs.com/compatible-mode/v1/models',
		);
	}
}
