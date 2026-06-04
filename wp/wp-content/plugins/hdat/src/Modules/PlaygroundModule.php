<?php
/**
 * @package HDAT\Modules
 */

declare(strict_types=1);

namespace HDAT\Modules;

use HDAT\Application\GatewayService;
use HDAT\Application\NoRouteAvailableException;
use HDAT\Auth\WpNonceAuthenticator;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Infrastructure\Http\SseEmitter;
use HDAT\Infrastructure\Routing\QuotaExceededException;
use HDAT\Kernel\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Streaming chat playground.
 *
 * Registers a nonce-authed admin REST endpoint
 * (`hdat/v1/admin/playground/stream`) that streams chat completions via
 * GatewayService using the internal consumer.
 */
final class PlaygroundModule implements ModuleInterface {

	public static function slug(): string {
		return 'playground';
	}

	public static function title(): string {
		return 'Playground';
	}

	public static function description(): string {
		return 'Streaming chat playground in admin UI for testing routes and providers.';
	}

	public static function alwaysActive(): bool {
		return true;
	}

	public function boot(): void {
		add_action( 'rest_api_init', [ $this, 'registerRoute' ] );
	}

	public function registerRoute(): void {
		$auth = Plugin::instance()->container()->make( WpNonceAuthenticator::class );

		register_rest_route(
			'hdat/v1',
			'/admin/playground/stream',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'stream' ],
				'permission_callback' => [ $auth, 'check' ],
			]
		);
	}

	public function stream( \WP_REST_Request $request ): \WP_REST_Response {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return new \WP_REST_Response( [ 'error' => 'invalid_body' ], 400 );
		}

		$messages = $body['messages'] ?? null;
		if ( ! is_array( $messages ) || empty( $messages ) ) {
			return new \WP_REST_Response( [ 'error' => 'invalid_messages' ], 400 );
		}

		$req = new GatewayRequest(
			messages:    $messages,
			model:       isset( $body['model'] ) ? (string) $body['model'] : null,
			provider:    isset( $body['provider'] ) ? (string) $body['provider'] : null,
			temperature: isset( $body['temperature'] ) ? (float) $body['temperature'] : 0.7,
			maxTokens:   isset( $body['max_tokens'] ) ? (int) $body['max_tokens'] : 2048,
			stream:      true,
		);

		$gateway = Plugin::instance()->container()->make( GatewayService::class );
		$emitter = new SseEmitter();

		try {
			$gateway->stream( $req, $emitter );
		} catch ( QuotaExceededException $e ) {
			$emitter->sendError( 'quota_exceeded:' . $e->dimension );
		} catch ( NoRouteAvailableException $e ) {
			$emitter->sendError( $e->getMessage() );
		} catch ( \Throwable $t ) {
			$emitter->sendError( 'gateway_error:' . $t->getMessage() );
		}

		$emitter->sendDone();
		exit;
	}
}
