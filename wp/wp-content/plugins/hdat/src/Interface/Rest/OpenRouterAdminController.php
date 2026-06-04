<?php
/**
 * @package HDAT\Interface\Rest
 */

declare(strict_types=1);

namespace HDAT\Interface\Rest;

use HDAT\Auth\WpNonceAuthenticator;
use HDAT\Providers\OpenRouter\OpenRouterPool;
use HDAT\Providers\OpenRouter\OpenRouterSync;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for OpenRouter management.
 */
final class OpenRouterAdminController extends AbstractApiController {

	public function __construct(
		private readonly WpNonceAuthenticator $auth,
	) {}

	public function register(): void {
		$perm = [ $this->auth, 'check' ];
		$ns   = 'hdat/v1/admin';

		$this->route( $ns, '/openrouter/models', 'GET', 'openrouterModels', $perm );
		$this->route( $ns, '/openrouter/all-models', 'GET', 'openrouterAllModels', $perm );
		$this->route( $ns, '/openrouter/rate-limits', 'GET', 'openrouterRateLimits', $perm );
		$this->route( $ns, '/openrouter/pool', 'GET', 'getOpenrouterPool', $perm );
		$this->route( $ns, '/openrouter/pool', 'PUT', 'setOpenrouterPool', $perm );
		$this->route( $ns, '/openrouter/sync', 'POST', 'openrouterSync', $perm );
	}

	public function openrouterModels(): \WP_REST_Response {
		return new \WP_REST_Response( OpenRouterPool::getCachedModels() ?? [], 200 );
	}

	public function openrouterAllModels(): \WP_REST_Response {
		return new \WP_REST_Response( OpenRouterPool::getCachedAllModels() ?? [], 200 );
	}

	public function openrouterRateLimits(): \WP_REST_Response {
		return new \WP_REST_Response( OpenRouterPool::getAllRateLimits(), 200 );
	}

	public function getOpenrouterPool(): \WP_REST_Response {
		return new \WP_REST_Response( OpenRouterPool::getPool(), 200 );
	}

	public function setOpenrouterPool( \WP_REST_Request $r ): \WP_REST_Response {
		$config = $r->get_json_params();

		OpenRouterPool::setPool( is_array( $config ) ? $config : [] );

		return new \WP_REST_Response( OpenRouterPool::getPool(), 200 );
	}

	public function openrouterSync(): \WP_REST_Response {
		( new OpenRouterSync() )->run();

		$models = OpenRouterPool::getCachedModels();

		return new \WP_REST_Response(
			[
				'synced'      => true,
				'model_count' => is_array( $models ) ? count( $models ) : 0,
			],
			200
		);
	}
}
