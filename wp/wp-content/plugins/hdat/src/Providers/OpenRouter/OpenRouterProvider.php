<?php
/**
 * @package HDAT\Providers\OpenRouter
 */

declare(strict_types=1);

namespace HDAT\Providers\OpenRouter;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

/**
 * OpenRouter provider — special treatment.
 *
 * - Adds HTTP-Referer + X-Title headers for traffic attribution.
 * - Records per-model rate-limit headers into OpenRouterPool after every response.
 * - Live model list is fetched by OpenRouterSync (cron) — pool UI uses cached data.
 *
 * Rate-limit recording happens in `parseResponse()` because that's the only
 * place we have both the model id and response headers.
 */
final class OpenRouterProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'openrouter',
			label:              'OpenRouter',
			apiFormat:          'openai_compatible',
			tier:               CredentialTier::Free,
			baseUrl:            'https://openrouter.ai/api/v1',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           ':free model variants; 20 RPM, 50 RPD without credits or 1000 RPD after $10+ credits',
			regUrl:             'https://openrouter.ai/keys',
			modelsUrl:          'https://openrouter.ai/api/v1/models',
		);
	}

	public function parseResponse( array $http ): \HDAT\Domain\Gateway\GatewayResponse {
		$response = parent::parseResponse( $http );

		$headers = $http['headers'] ?? [];
		if ( is_array( $headers ) && '' !== $response->model ) {
			OpenRouterPool::recordRateLimit( $response->model, $headers );
		}

		return $response;
	}

	protected function headers( \HDAT\Domain\Credential\Credential $cred ): array {
		$headers = parent::headers( $cred );

		$site = home_url( '/' );
		$name = get_bloginfo( 'name' ) ?: 'WordPress';

		$headers['HTTP-Referer'] = $site;
		$headers['X-Title']      = $name;

		return $headers;
	}
}
