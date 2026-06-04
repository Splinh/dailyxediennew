<?php
/**
 * @package HDAT\Providers\Gemini
 */

declare(strict_types=1);

namespace HDAT\Providers\Gemini;

use HDAT\Domain\Credential\Credential;
use HDAT\Domain\Credential\CredentialTier;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Domain\Gateway\GatewayUsage;
use HDAT\Domain\Provider\Capability;
use HDAT\Domain\Provider\ProviderCapsule;
use HDAT\Domain\Provider\ProviderMeta;
use HDAT\Domain\Routing\FailureCategory;

defined( 'ABSPATH' ) || exit;

/**
 * Google Gemini native API.
 *
 * Endpoint pattern:
 *   {base}/models/{model}:generateContent?key={api_key}
 *   {base}/models/{model}:streamGenerateContent?alt=sse&key={api_key}
 *
 * Streaming is SSE with JSON payloads. Roles are `user`/`model`; we map
 * OpenAI's `assistant` → `model`. System prompts go into `systemInstruction`.
 */
final class GeminiProvider implements ProviderCapsule {

	public static function meta(): ProviderMeta {
		return new ProviderMeta(
			id:                 'gemini',
			label:              'Google Gemini',
			apiFormat:          'google_gemini',
			tier:               CredentialTier::Free,
			baseUrl:            'https://generativelanguage.googleapis.com/v1beta',
			capabilities:       [ Capability::Chat, Capability::Vision, Capability::FunctionCall, Capability::ToolUse, Capability::Embedding ],
			supportsLiveModels: true,
			category:           'official',
			rateInfo:           'Free tier (not available in EU/UK/CH)',
			regUrl:             'https://aistudio.google.com/app/apikey',
			modelsUrl:          'https://generativelanguage.googleapis.com/v1beta/models',
		);
	}

	public function buildRequest( GatewayRequest $req, Credential $cred ): array {
		$base   = rtrim( $cred->baseUrl ?: self::meta()->baseUrl, '/' );
		$method = $req->stream ? 'streamGenerateContent' : 'generateContent';
		$query  = $req->stream ? '?alt=sse&key=' : '?key=';
		$url    = $base . '/models/' . rawurlencode( (string) $req->model ) . ':' . $method . $query . rawurlencode( $cred->apiKey );

		$system   = '';
		$contents = [];

		foreach ( $req->messages as $msg ) {
			$role    = $msg['role'] ?? 'user';
			$content = $msg['content'] ?? '';

			if ( 'system' === $role ) {
				$system .= ( '' === $system ? '' : "\n" ) . $content;
				continue;
			}

			$contents[] = [
				'role'  => 'assistant' === $role ? 'model' : 'user',
				'parts' => [ [ 'text' => is_string( $content ) ? $content : (string) wp_json_encode( $content ) ] ],
			];
		}

		$body = [
			'contents'         => $contents,
			'generationConfig' => array_filter(
				[
					'temperature'     => $req->temperature,
					'maxOutputTokens' => $req->maxTokens,
				],
				static fn( $v ) => null !== $v,
			),
		];

		if ( '' !== $system ) {
			$body['systemInstruction'] = [ 'parts' => [ [ 'text' => $system ] ] ];
		}

		foreach ( $req->extra as $k => $v ) {
			$body[ $k ] = $v;
		}

		return [ $url, [ 'Content-Type' => 'application/json' ], $body ];
	}

	public function parseResponse( array $http ): GatewayResponse {
		$data      = $http['body'] ?? [];
		$candidate = $data['candidates'][0] ?? [];
		$parts     = $candidate['content']['parts'] ?? [];
		$content   = '';
		foreach ( $parts as $part ) {
			$content .= $part['text'] ?? '';
		}

		$usage = $data['usageMetadata'] ?? [];

		return new GatewayResponse(
			content:      $content,
			model:        $data['modelVersion'] ?? self::meta()->id,
			provider:     self::meta()->id,
			usage:        new GatewayUsage(
				promptTokens:     (int) ( $usage['promptTokenCount'] ?? 0 ),
				completionTokens: (int) ( $usage['candidatesTokenCount'] ?? 0 ),
				totalTokens:      (int) ( $usage['totalTokenCount'] ?? 0 ),
			),
			finishReason: $candidate['finishReason'] ?? null,
		);
	}

	public function parseStreamChunk( string $line ): ?string {
		$line = trim( $line );
		if ( '' === $line ) {
			return null;
		}

		$data = json_decode( $line, true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		$parts = $data['candidates'][0]['content']['parts'] ?? [];
		$out   = '';
		foreach ( $parts as $part ) {
			$out .= $part['text'] ?? '';
		}

		return '' === $out ? null : $out;
	}

	public function classifyError( int $status, array $body ): FailureCategory {
		return match ( true ) {
			429 === $status                       => FailureCategory::RateLimit,
			in_array( $status, [ 401, 403 ], true ) => FailureCategory::Auth,
			408 === $status                       => FailureCategory::Timeout,
			$status >= 500                        => FailureCategory::Server,
			default                               => FailureCategory::Unknown,
		};
	}

	public function buildModelsRequest( string $apiKey, ?string $baseUrl = null ): ?array {
		$meta = self::meta();
		$url  = $baseUrl
			? rtrim( $baseUrl, '/' ) . '/models'
			: $meta->modelsUrl;

		if ( ! $url ) {
			return null;
		}

		$url .= '?key=' . rawurlencode( $apiKey ) . '&pageSize=100';

		return [ $url, [] ];
	}

	public function parseModelsResponse( array $body ): array {
		$raw = $body['models'] ?? [];

		return array_values(
			array_map(
				static fn( array $m ) => [
					'id'             => str_replace( 'models/', '', (string) ( $m['name'] ?? '' ) ),
					'name'           => (string) ( $m['displayName'] ?? $m['name'] ?? '' ),
					'context_window' => (int) ( $m['inputTokenLimit'] ?? 0 ),
				],
				is_array( $raw ) ? $raw : []
			)
		);
	}
}
