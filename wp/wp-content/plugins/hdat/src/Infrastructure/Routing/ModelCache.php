<?php
/**
 * @package HDAT\Infrastructure\Routing
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Routing;

use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Provider\ModelMeta;
use HDAT\Infrastructure\Http\CurlAdapter;
use HDAT\Kernel\ProviderRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Caches the model list fetched from a provider's live API.
 *
 * When a credential has no preferredModel and the provider declares no
 * staticModels, AiRouter asks this class for a usable chat model.
 *
 * Storage: WP transients (`hdat_models_{credentialId}`), TTL 6 hours.
 * On cache miss the class makes a single GET to the provider's modelsUrl,
 * filters out non-chat models, and caches the resulting list.
 *
 * If the fetch fails (network error, bad key) the result is cached as
 * empty for 10 minutes to avoid hammering a broken endpoint.
 */
final class ModelCache {

	private const TTL          = 6 * HOUR_IN_SECONDS;
	private const NEGATIVE_TTL = 10 * MINUTE_IN_SECONDS;

	/**
	 * Regex to exclude non-chat models (embedding, audio, image, TTS, etc.).
	 */
	private const NON_CHAT_PATTERN = '/embed|whisper|tts|audio|speech|orpheus|dall-e|image|stable-diffusion|flux|sdxl|moderation|rerank|guard/i';

	public function __construct(
		private readonly CurlAdapter $http,
	) {}

	/**
	 * Get a list of chat models for the given credential.
	 *
	 * @return ModelMeta[] Models suitable for chat. May be empty if fetch
	 *                     failed or provider has no models.
	 */
	public function getModels( Credential $cred ): array {
		$key    = 'hdat_models_' . $cred->id->value;
		$cached = get_transient( $key );

		if ( is_array( $cached ) ) {
			return $this->hydrateModels( $cached );
		}

		if ( ! ProviderRegistry::hasForCredential( $cred ) ) {
			return [];
		}

		$raw = $this->fetchAndFilter( $cred );

		// Cache result; use short TTL for failures to allow retry.
		$ttl = $raw ? self::TTL : self::NEGATIVE_TTL;
		set_transient( $key, $raw, $ttl );

		return $this->hydrateModels( $raw );
	}

	/**
	 * @return array<int, array{id: string, name: string, context_window: int}>
	 */
	private function fetchAndFilter( Credential $cred ): array {
		$provider = ProviderRegistry::getForCredential( $cred );
		$baseUrl  = $cred->baseUrl ?: null;

		$req = $provider->buildModelsRequest( $cred->apiKey, $baseUrl );

		if ( ! $req ) {
			return [];
		}

		try {
			[ $url, $headers ] = $req;
			$response          = $this->http->get( $url, $headers );
		} catch ( \Throwable $e ) {
			return [];
		}

		if ( $response['status'] >= 400 ) {
			return [];
		}

		$parsed = $provider->parseModelsResponse( $response['body'] );

		// Filter non-chat models and empty IDs.
		$chat = array_filter(
			$parsed,
			static fn( array $m ) => '' !== ( $m['id'] ?? '' ) && ! preg_match( self::NON_CHAT_PATTERN, $m['id'] )
		);

		return array_values( $chat ) ?: $parsed;
	}

	/**
	 * Convert raw cached arrays into ModelMeta objects.
	 *
	 * @param array<int, array{id: string, name?: string, context_window?: int}> $raw
	 * @return ModelMeta[]
	 */
	private function hydrateModels( array $raw ): array {
		return array_map(
			static fn( array $m ) => new ModelMeta(
				id:            (string) ( $m['id'] ?? '' ),
				name:          (string) ( $m['name'] ?? $m['id'] ?? '' ),
				contextWindow: (int) ( $m['context_window'] ?? 0 ),
			),
			$raw
		);
	}
}
