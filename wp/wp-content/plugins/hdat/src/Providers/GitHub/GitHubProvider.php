<?php
/**
 * @package HDAT\Providers\GitHub
 */

declare(strict_types=1);

namespace HDAT\Providers\GitHub;

use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Providers\AbstractOpenAICompatibleProvider;

defined( 'ABSPATH' ) || exit;

final class GitHubProvider extends AbstractOpenAICompatibleProvider {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'github',
			label:              'GitHub Models',
			apiFormat:          'github_models',
			tier:               CredentialTier::Free,
			baseUrl:            'https://models.github.ai/inference',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse ],
			supportsLiveModels: true,
			category:           'inference',
			rateInfo:           'Free for GitHub users; access and limits follow GitHub Models policy',
			regUrl:             'https://github.com/marketplace/models',
			modelsUrl:          'https://models.github.ai/catalog/models',
		);
	}

	public function buildModelsRequest( string $apiKey, ?string $baseUrl = null ): ?array {
		$url = self::meta()->modelsUrl;

		return $url ? [ $url, [] ] : null;
	}

	public function parseModelsResponse( array $body ): array {
		// GitHub catalog returns a flat JSON array (not wrapped in { data: [...] }).
		$raw = is_array( $body ) ? $body : [];

		// Filter to text-capable models only.
		$filtered = array_filter(
			$raw,
			static fn( array $m ) => in_array( 'text', $m['supported_output_modalities'] ?? [], true ),
		);

		return array_values(
			array_map(
				static fn( array $m ) => [
					'id'             => (string) ( $m['id'] ?? '' ),
					'name'           => (string) ( $m['name'] ?? $m['id'] ?? '' ),
					'context_window' => (int) ( $m['max_input_tokens'] ?? 0 ),
				],
				$filtered
			)
		);
	}
}
