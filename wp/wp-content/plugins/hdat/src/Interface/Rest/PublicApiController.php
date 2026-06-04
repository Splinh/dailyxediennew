<?php
/**
 * @package HDAT\Interface\Rest
 */

declare(strict_types=1);

namespace HDAT\Interface\Rest;

use HDAT\Application\GatewayService;
use HDAT\Application\NoRouteAvailableException;
use HDAT\Auth\BearerTokenAuthenticator;
use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Domain\Gateway\GatewayResponse;
use HDAT\Infrastructure\Http\SseEmitter;
use HDAT\Infrastructure\Routing\QuotaExceededException;
use HDAT\Kernel\ProviderRegistry;
use HDAT\Kernel\Settings;
use HDAT\Providers\OpenRouter\OpenRouterPool;

defined( 'ABSPATH' ) || exit;

/**
 * Namespace `hdat/v1` — OpenAI-compatible public API.
 *
 * Two endpoints:
 *   POST /chat/completions   dispatch via GatewayService; supports stream:true
 *   GET  /models             union of static provider models + OpenRouter pool
 *
 * Auth: BearerTokenAuthenticator (permission_callback). On success, the
 * resolved ConsumerToken is attached as `consumer_token` request attribute.
 *
 * The chat handler is the only one that can call `exit` — required for SSE so
 * WP doesn't emit its trailing JSON envelope. Non-stream responses use the
 * normal WP_REST_Response path so wp_send_json + headers behave correctly.
 */
final class PublicApiController extends AbstractApiController {

	public function __construct(
		private readonly GatewayService $gateway,
		private readonly BearerTokenAuthenticator $auth,
	) {}

	public function register(): void {
		$permission = [ $this->auth, 'authenticate' ];
		$ns         = 'hdat/v1';

		$this->route( $ns, '/chat/completions', 'POST', 'chat', $permission );
		$this->route( $ns, '/models', 'GET', 'models', $permission );
	}

	public function chat( \WP_REST_Request $request ): \WP_REST_Response {
		$consumer = $this->resolveConsumer( $request );
		$body     = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return $this->error( 'invalid_body', 'Request body must be JSON.', 400 );
		}

		$messages = $body['messages'] ?? null;
		if ( ! is_array( $messages ) || empty( $messages ) ) {
			return $this->error( 'invalid_messages', 'messages must be a non-empty array.', 400 );
		}

		$req = new GatewayRequest(
			messages:    $messages,
			model:       isset( $body['model'] ) ? (string) $body['model'] : null,
			provider:    isset( $body['provider'] ) ? (string) $body['provider'] : null,
			temperature: isset( $body['temperature'] ) ? (float) $body['temperature'] : 0.7,
			maxTokens:   isset( $body['max_tokens'] ) ? (int) $body['max_tokens'] : 2048,
			stream: ! empty( $body['stream'] ),
			consumerId:  $consumer->id->value > 0 ? $consumer->id : null,
			extra:       $this->extractExtras( $body ),
		);

		if ( $req->stream ) {
			$emitter = new SseEmitter();

			try {
				$this->gateway->stream( $req, $emitter );
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

		try {
			[ $response, $ctx ] = $this->gateway->dispatch( $req );
		} catch ( QuotaExceededException $e ) {
			return $this->error( 'quota_exceeded', $e->getMessage(), 429, [ 'dimension' => $e->dimension ] );
		} catch ( NoRouteAvailableException $e ) {
			return $this->error(
				'no_route_available',
				$e->getMessage(),
				502,
				[
					'attempts'      => $e->attempts,
					'last_category' => $e->lastCategory,
				]
			);
		} catch ( \Throwable $t ) {
			return $this->error( 'gateway_error', $t->getMessage(), 500 );
		}

		$res = new \WP_REST_Response( $this->formatChat( $response ), 200 );

		if ( null !== $ctx && Settings::get( 'route_headers', true ) ) {
			$res->header( 'X-Routed-Via', $ctx->toHeader() );
			$res->header( 'X-Fallback-Attempts', (string) $ctx->attempts );
			$res->header( 'X-Credential-ID', (string) $ctx->credentialId->value );
		}

		if ( $response->cached ) {
			$res->header( 'X-HDAT-Cache', 'HIT' );
		}

		return $res;
	}

	public function models( \WP_REST_Request $request ): \WP_REST_Response {
		$consumer = $this->resolveConsumer( $request );
		$models   = [];
		$seen     = [];

		foreach ( ProviderRegistry::all() as $meta ) {
			if ( ! $consumer->allowsProvider( $meta->id ) ) {
				continue;
			}

			foreach ( $meta->staticModels as $m ) {
				if ( isset( $seen[ $m->id ] ) || ! $consumer->allowsModel( $m->id ) ) {
					continue;
				}
				$seen[ $m->id ] = true;
				$models[]       = [
					'id'       => $m->id,
					'object'   => 'model',
					'owned_by' => $meta->id,
				];
			}
		}

		if ( $consumer->allowsProvider( 'openrouter' ) ) {
			foreach ( OpenRouterPool::getCachedModels() ?? [] as $m ) {
				$id = (string) ( $m['id'] ?? '' );
				if ( '' === $id || isset( $seen[ $id ] ) || ! $consumer->allowsModel( $id ) ) {
					continue;
				}
				$seen[ $id ] = true;
				$models[]    = [
					'id'       => $id,
					'object'   => 'model',
					'owned_by' => 'openrouter',
				];
			}
		}

		return new \WP_REST_Response(
			[
				'object' => 'list',
				'data'   => $models,
			],
			200
		);
	}

	private function resolveConsumer( \WP_REST_Request $request ): ConsumerToken {
		$attrs = $request->get_attributes();
		$token = $attrs['consumer_token'] ?? null;

		return $token instanceof ConsumerToken ? $token : ConsumerToken::internal();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function formatChat( GatewayResponse $r ): array {
		return [
			'id'      => 'chatcmpl-' . uniqid(),
			'object'  => 'chat.completion',
			'created' => time(),
			'model'   => $r->model,
			'choices' => [
				[
					'index'         => 0,
					'message'       => [
						'role'    => 'assistant',
						'content' => $r->content,
					],
					'finish_reason' => $r->finishReason ?? 'stop',
				],
			],
			'usage'   => [
				'prompt_tokens'     => $r->usage->promptTokens,
				'completion_tokens' => $r->usage->completionTokens,
				'total_tokens'      => $r->usage->totalTokens,
			],
		];
	}

	/**
	 * @param array<string, mixed> $body
	 * @return array<string, mixed>
	 */
	private function extractExtras( array $body ): array {
		$known = [ 'messages', 'model', 'provider', 'temperature', 'max_tokens', 'stream' ];
		$extra = [];
		foreach ( $body as $k => $v ) {
			if ( ! in_array( $k, $known, true ) ) {
				$extra[ $k ] = $v;
			}
		}

		return $extra;
	}

	/**
	 * @param array<string, mixed> $extra
	 */
	private function error( string $code, string $message, int $status, array $extra = [] ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'error' => array_merge(
					[
						'code'    => $code,
						'message' => $message,
					],
					$extra,
				),
			],
			$status
		);
	}
}
