<?php
/**
 * @package HDAT\Kernel
 */

declare(strict_types=1);

namespace HDAT\Kernel;

use HDAT\Application\GatewayService;
use HDAT\Auth\BearerTokenAuthenticator;
use HDAT\Auth\WpNonceAuthenticator;
use HDAT\Infrastructure\Crypto\KeyEncryptor;
use HDAT\Infrastructure\DB\Migrator;
use HDAT\Infrastructure\Http\CurlAdapter;
use HDAT\Infrastructure\Persistence\ConsumerTokenRepository;
use HDAT\Infrastructure\Persistence\CredentialRepository;
use HDAT\Infrastructure\Persistence\QuotaWindowRepository;
use HDAT\Infrastructure\Persistence\ResponseCacheRepository;
use HDAT\Infrastructure\Persistence\RouteStateRepository;
use HDAT\Infrastructure\Persistence\StickyRouteRepository;
use HDAT\Infrastructure\Persistence\UsageLedgerRepository;
use HDAT\Infrastructure\Routing\AiRouter;
use HDAT\Infrastructure\Routing\CircuitBreaker;
use HDAT\Infrastructure\Routing\ModelCache;
use HDAT\Infrastructure\Routing\QuotaPolicy;
use HDAT\Infrastructure\Routing\RouteScorer;
use HDAT\Infrastructure\Routing\TokenEstimator;
use HDAT\Infrastructure\Routing\RequestShaper;
use HDAT\Infrastructure\Routing\ModelHealthChecker;
use HDAT\Interface\Admin\AdminPage;
use HDAT\Interface\Rest\CredentialAdminController;
use HDAT\Interface\Rest\CustomProviderAdminController;
use HDAT\Interface\Rest\TokenAdminController;
use HDAT\Interface\Rest\OpenRouterAdminController;
use HDAT\Interface\Rest\SystemAdminController;
use HDAT\Interface\Rest\PublicApiController;
use HDAT\Modules\ImageGenModule;
use HDAT\Modules\PlaygroundModule;
use HDAT\Providers\OpenRouter\OpenRouterSync;
use HDAT\Providers\Anthropic\AnthropicProvider;
use HDAT\Providers\Cerebras\CerebrasProvider;
use HDAT\Providers\Cloudflare\CloudflareProvider;
use HDAT\Providers\Cohere\CohereProvider;
use HDAT\Providers\DashScope\DashScopeProvider;
use HDAT\Providers\DeepInfra\DeepInfraProvider;
use HDAT\Providers\DeepSeek\DeepSeekProvider;
use HDAT\Providers\Fireworks\FireworksProvider;
use HDAT\Providers\Gemini\GeminiProvider;
use HDAT\Providers\GitHub\GitHubProvider;
use HDAT\Providers\Groq\GroqProvider;
use HDAT\Providers\HuggingFace\HuggingFaceProvider;
use HDAT\Providers\MiniMax\MiniMaxProvider;
use HDAT\Providers\Mistral\MistralProvider;
use HDAT\Providers\Moonshot\MoonshotProvider;
use HDAT\Providers\Nvidia\NvidiaProvider;
use HDAT\Providers\OpenAI\OpenAIProvider;
use HDAT\Providers\OpenRouter\OpenRouterProvider;
use HDAT\Providers\SambaNova\SambaNovaProvider;
use HDAT\Providers\SiliconFlow\SiliconFlowProvider;
use HDAT\Providers\Together\TogetherProvider;
use HDAT\Providers\XAI\XAIProvider;
use HDAT\Providers\Zhipu\ZhipuProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin orchestrator.
 *
 * `boot()` is called once on `plugins_loaded`. It builds the container,
 * registers providers and modules, then wires hooks via HookRegistrar.
 * Activation/deactivation hooks are static so WP can call them without
 * loading the full plugin.
 */
final class Plugin {

	private static ?self $instance = null;

	private Container $container;

	private const SCHEDULES = [
		'hdat_usage_cleanup'         => 'daily',
		'hdat_response_cache_gc'     => 'daily',
		'hdat_route_state_cleanup'   => 'daily',
		'hdat_quota_windows_cleanup' => 'daily',
		'hdat_sticky_routes_cleanup' => 'hourly',
		'hdat_openrouter_sync'       => 'sixhours',
		'hdat_model_health_check'    => 'daily',
	];

	public static function boot(): void {
		if ( null !== self::$instance ) {
			return;
		}

		self::$instance = new self();
		self::$instance->init();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::boot();
		}

		return self::$instance;
	}

	public static function activate(): void {
		( new Migrator() )->run();
		self::registerCustomCronIntervals();
		self::ensureSchedules();
	}

	public static function deactivate(): void {
		foreach ( array_keys( self::SCHEDULES ) as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
	}

	public function container(): Container {
		return $this->container;
	}

	private function __construct() {
		$this->container = new Container();
	}

	private function init(): void {
		$this->registerProviders();
		$this->registerModules();
		self::registerCustomCronIntervals();
		$this->bindServices();

		( new HookRegistrar( $this->container ) )->register();

		// Boot enabled modules. Disabled modules never instantiate.
		ModuleRegistry::boot();

		// Self-heal: re-schedule if missed (e.g., plugin update without re-activation).
		self::ensureSchedules();
	}

	/**
	 * Wire infrastructure + application services into the container.
	 *
	 * Bindings are lazy — none of these classes is instantiated until the
	 * container is asked for them. Order matters only for clarity; the lazy
	 * factories handle the dependency graph at make() time.
	 */
	private function bindServices(): void {
		$c = $this->container;

		$c->bind( KeyEncryptor::class, static fn() => new KeyEncryptor() );
		$c->bind( CurlAdapter::class, static fn() => new CurlAdapter() );
		$c->bind( TokenEstimator::class, static fn() => new TokenEstimator() );

		$c->bind( CredentialRepository::class, static fn( Container $cc ) => new CredentialRepository( $cc->make( KeyEncryptor::class ) ) );
		$c->bind( ConsumerTokenRepository::class, static fn() => new ConsumerTokenRepository() );
		$c->bind( UsageLedgerRepository::class, static fn() => new UsageLedgerRepository() );
		$c->bind( ResponseCacheRepository::class, static fn() => new ResponseCacheRepository() );
		$c->bind( RouteStateRepository::class, static fn() => new RouteStateRepository() );
		$c->bind( QuotaWindowRepository::class, static fn() => new QuotaWindowRepository() );
		$c->bind( StickyRouteRepository::class, static fn() => new StickyRouteRepository() );

		$c->bind( RouteScorer::class, static fn( Container $cc ) => new RouteScorer( $cc->make( RouteStateRepository::class ) ) );
		$c->bind( CircuitBreaker::class, static fn( Container $cc ) => new CircuitBreaker( $cc->make( RouteStateRepository::class ) ) );
		$c->bind( QuotaPolicy::class, static fn( Container $cc ) => new QuotaPolicy( $cc->make( QuotaWindowRepository::class ) ) );
		$c->bind( RequestShaper::class, static fn() => new RequestShaper() );

		$c->bind( ModelCache::class, static fn( Container $cc ) => new ModelCache( $cc->make( CurlAdapter::class ) ) );
		$c->bind(
			ModelHealthChecker::class,
			static fn( Container $cc ) => new ModelHealthChecker(
				$cc->make( CredentialRepository::class ),
				$cc->make( ModelCache::class ),
			)
		);

		$c->bind(
			AiRouter::class,
			static fn( Container $cc ) => new AiRouter(
				$cc->make( CredentialRepository::class ),
				$cc->make( RouteScorer::class ),
				$cc->make( CircuitBreaker::class ),
				$cc->make( QuotaPolicy::class ),
				$cc->make( ModelCache::class ),
				$cc->make( StickyRouteRepository::class ),
			)
		);

		$c->bind(
			GatewayService::class,
			static fn( Container $cc ) => new GatewayService(
				$cc->make( AiRouter::class ),
				$cc->make( CredentialRepository::class ),
				$cc->make( ConsumerTokenRepository::class ),
				$cc->make( CurlAdapter::class ),
				$cc->make( CircuitBreaker::class ),
				$cc->make( QuotaPolicy::class ),
				$cc->make( QuotaWindowRepository::class ),
				$cc->make( UsageLedgerRepository::class ),
				$cc->make( ResponseCacheRepository::class ),
				$cc->make( TokenEstimator::class ),
				$cc->make( RequestShaper::class ),
				$cc->make( StickyRouteRepository::class ),
			)
		);

		$c->bind(
			BearerTokenAuthenticator::class,
			static fn( Container $cc ) => new BearerTokenAuthenticator( $cc->make( ConsumerTokenRepository::class ) )
		);

		$c->bind(
			PublicApiController::class,
			static fn( Container $cc ) => new PublicApiController(
				$cc->make( GatewayService::class ),
				$cc->make( BearerTokenAuthenticator::class ),
			)
		);

		$c->bind( WpNonceAuthenticator::class, static fn() => new WpNonceAuthenticator() );
		$c->bind( OpenRouterSync::class, static fn() => new OpenRouterSync() );

		$c->bind(
			CredentialAdminController::class,
			static fn( Container $cc ) => new CredentialAdminController(
				$cc->make( WpNonceAuthenticator::class ),
				$cc->make( CredentialRepository::class ),
				$cc->make( CurlAdapter::class ),
			)
		);

		$c->bind(
			TokenAdminController::class,
			static fn( Container $cc ) => new TokenAdminController(
				$cc->make( WpNonceAuthenticator::class ),
				$cc->make( ConsumerTokenRepository::class ),
			)
		);

		$c->bind(
			OpenRouterAdminController::class,
			static fn( Container $cc ) => new OpenRouterAdminController(
				$cc->make( WpNonceAuthenticator::class ),
			)
		);

		$c->bind(
			SystemAdminController::class,
			static fn( Container $cc ) => new SystemAdminController(
				$cc->make( WpNonceAuthenticator::class ),
				$cc->make( UsageLedgerRepository::class ),
				$cc->make( RouteStateRepository::class ),
				$cc->make( CredentialRepository::class ),
				$cc->make( AiRouter::class ),
				$cc->make( TokenEstimator::class ),
			)
		);

		$c->bind(
			CustomProviderAdminController::class,
			static fn( Container $cc ) => new CustomProviderAdminController(
				$cc->make( WpNonceAuthenticator::class ),
				$cc->make( CurlAdapter::class ),
				$cc->make( CredentialRepository::class ),
			)
		);

		$c->bind( AdminPage::class, static fn() => new AdminPage() );
	}

	private function registerProviders(): void {
		// Official Provider APIs.
		ProviderRegistry::register( OpenAIProvider::class );
		ProviderRegistry::register( GeminiProvider::class );
		ProviderRegistry::register( AnthropicProvider::class );
		ProviderRegistry::register( DeepSeekProvider::class );
		ProviderRegistry::register( ZhipuProvider::class );
		ProviderRegistry::register( DashScopeProvider::class );
		ProviderRegistry::register( MistralProvider::class );
		ProviderRegistry::register( CohereProvider::class );
		ProviderRegistry::register( XAIProvider::class );
		ProviderRegistry::register( MoonshotProvider::class );
		ProviderRegistry::register( MiniMaxProvider::class );

		// Inference Providers.
		ProviderRegistry::register( GroqProvider::class );
		ProviderRegistry::register( SambaNovaProvider::class );
		ProviderRegistry::register( CerebrasProvider::class );
		ProviderRegistry::register( TogetherProvider::class );
		ProviderRegistry::register( FireworksProvider::class );
		ProviderRegistry::register( DeepInfraProvider::class );
		ProviderRegistry::register( GitHubProvider::class );
		ProviderRegistry::register( NvidiaProvider::class );
		ProviderRegistry::register( SiliconFlowProvider::class );
		ProviderRegistry::register( HuggingFaceProvider::class );
		ProviderRegistry::register( CloudflareProvider::class );
		ProviderRegistry::register( OpenRouterProvider::class );
	}

	private function registerModules(): void {
		ModuleRegistry::register( ImageGenModule::class );
		ModuleRegistry::register( PlaygroundModule::class );
	}

	private static function registerCustomCronIntervals(): void {
		add_filter(
			'cron_schedules',
			static function ( array $schedules ): array {
				if ( ! isset( $schedules['sixhours'] ) ) {
					$schedules['sixhours'] = [
						'interval' => 6 * HOUR_IN_SECONDS,
						'display'  => __( 'Every 6 hours', 'hdat' ),
					];
				}

				return $schedules;
			}
		);
	}

	private static function ensureSchedules(): void {
		foreach ( self::SCHEDULES as $hook => $recurrence ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, $recurrence, $hook );
			}
		}
	}
}
