<?php
/**
 * @package HDAT\Kernel
 */

declare(strict_types=1);

namespace HDAT\Kernel;

use HDAT\Infrastructure\Asset;
use HDAT\Infrastructure\Persistence\QuotaWindowRepository;
use HDAT\Infrastructure\Persistence\ResponseCacheRepository;
use HDAT\Infrastructure\Persistence\RouteStateRepository;
use HDAT\Infrastructure\Persistence\StickyRouteRepository;
use HDAT\Infrastructure\Persistence\UsageLedgerRepository;
use HDAT\Interface\Admin\AdminPage;
use HDAT\Interface\Rest\CredentialAdminController;
use HDAT\Interface\Rest\CustomProviderAdminController;
use HDAT\Interface\Rest\TokenAdminController;
use HDAT\Interface\Rest\OpenRouterAdminController;
use HDAT\Interface\Rest\SystemAdminController;
use HDAT\Interface\Rest\PublicApiController;
use HDAT\Infrastructure\Routing\ModelHealthChecker;
use HDAT\Providers\OpenRouter\OpenRouterSync;
use HDAT\Updater\GitHubUpdater;

defined( 'ABSPATH' ) || exit;

/**
 * Wires WordPress hooks to container services.
 *
 * Kept thin: every closure delegates to the container so swapping
 * implementations (e.g., a fake controller in tests) is one bind() call.
 */
final class HookRegistrar {

	public function __construct(
		private readonly Container $container,
	) {}

	public function register(): void {
		add_action(
			'rest_api_init',
			function (): void {
				$this->container->make( PublicApiController::class )->register();
				$this->container->make( CredentialAdminController::class )->register();
				$this->container->make( TokenAdminController::class )->register();
				$this->container->make( OpenRouterAdminController::class )->register();
				$this->container->make( SystemAdminController::class )->register();
				$this->container->make( CustomProviderAdminController::class )->register();
			}
		);

		// Admin menu + asset enqueue.
		$this->container->make( AdminPage::class )->register();

		// GitHub auto-updater — admin context only, never during cron.
		if ( is_admin() && ! wp_doing_cron() ) {
			new GitHubUpdater();
		}

		// Script tag attribute injection (e.g., type="module").
		add_filter(
			'script_loader_tag',
			[ Asset::class, 'scriptLoaderTag' ],
			11,
			2
		);

		// Cron pruners — schedules themselves are registered in Plugin::activate().
		add_action(
			'hdat_usage_cleanup',
			fn() => $this->container->make( UsageLedgerRepository::class )->pruneOld()
		);
		add_action(
			'hdat_response_cache_gc',
			fn() => $this->container->make( ResponseCacheRepository::class )->pruneExpired()
		);
		add_action(
			'hdat_route_state_cleanup',
			fn() => $this->container->make( RouteStateRepository::class )->pruneExpired()
		);
		add_action(
			'hdat_quota_windows_cleanup',
			fn() => $this->container->make( QuotaWindowRepository::class )->pruneExpired()
		);
		// DC2: sticky-routing is now wired (GatewayService remembers, AiRouter promotes).
		add_action(
			'hdat_sticky_routes_cleanup',
			fn() => $this->container->make( StickyRouteRepository::class )->pruneExpired()
		);

		// OpenRouter model catalog sync (every 6h).
		add_action(
			'hdat_openrouter_sync',
			fn() => $this->container->make( OpenRouterSync::class )->run()
		);

		// Model deprecation health check (daily).
		add_action(
			'hdat_model_health_check',
			fn() => $this->container->make( ModelHealthChecker::class )->check()
		);
	}
}
