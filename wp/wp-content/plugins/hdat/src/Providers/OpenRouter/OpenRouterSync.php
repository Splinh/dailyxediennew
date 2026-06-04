<?php
/**
 * @package HDAT\Providers\OpenRouter
 */

declare(strict_types=1);

namespace HDAT\Providers\OpenRouter;

defined( 'ABSPATH' ) || exit;

/**
 * Cron-driven OpenRouter model catalog sync.
 *
 * Runs every 6h via `hdat_openrouter_sync` action. Fetches the public model
 * list, filters to free variants (`:free` suffix), and stores in
 * OpenRouterPool::OPT_MODELS.
 *
 * The HTTP call uses wp_remote_get because this is a one-shot, non-streaming
 * request — CurlAdapter is reserved for the request path that needs streaming
 * support and timeout tuning.
 */
final class OpenRouterSync {

	private const ENDPOINT = 'https://openrouter.ai/api/v1/models';

	/**
	 * Hook target. Registered in HookRegistrar (Phase 4).
	 */
	public function run(): void {
		$response = wp_remote_get(
			self::ENDPOINT,
			[
				'timeout' => 15,
				'headers' => [
					'Accept' => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! isset( $body['data'] ) ) {
			return;
		}

		$free = [];
		$all  = [];
		foreach ( (array) $body['data'] as $model ) {
			$id = $model['id'] ?? '';
			if ( ! is_string( $id ) || '' === $id ) {
				continue;
			}

			$entry = [
				'id'             => $id,
				'name'           => $model['name'] ?? $id,
				'context_length' => (int) ( $model['context_length'] ?? 0 ),
				'pricing'        => $model['pricing'] ?? null,
				'modalities'     => $model['architecture']['input_modalities'] ?? [ 'text' ],
				'top_provider'   => $model['top_provider'] ?? null,
			];

			$all[] = $entry;

			if ( str_ends_with( $id, ':free' ) ) {
				$free[] = $entry;
			}
		}

		OpenRouterPool::setCachedModels( $free );
		OpenRouterPool::setCachedAllModels( $all );
	}
}
