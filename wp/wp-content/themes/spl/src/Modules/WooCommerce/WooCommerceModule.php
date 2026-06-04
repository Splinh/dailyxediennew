<?php
/**
 * WooCommerce Module — Orchestrator.
 *
 * Boots core WC integrations (always-on) and enhanced sub-features
 * (conditionally enabled via settings).
 *
 * Sub-features are defined in FEATURES const (explicit, zero overhead).
 * 3rd party extends via `hd_wc_features` filter.
 *
 * @package SPL\Modules\WooCommerce
 */

namespace SPL\Modules\WooCommerce;

use SPL\Modules\AbstractModule;
use SPL\Modules\WooCommerce\Contracts\HasAPI;
use SPL\Modules\WooCommerce\Contracts\HasSettings;
use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class WooCommerceModule extends AbstractModule {

	/* ---------- ModuleInterface --------------------------------- */

	public static function slug(): string {
		return 'woocommerce';
	}

	public static function isActive(): bool {
		return Helper::isWoocommerceActive();
	}

	/**
	 * Built-in sub-features. Order matters: dependencies first.
	 * 3rd party extends via `hd_wc_features` filter.
	 *
	 * @var array<class-string<Contracts\WooFeatureInterface>>
	 */
	private const FEATURES = [
		Swatches\SwatchesManager::class,
		Gallery\GalleryManager::class,
		QuickView\QuickViewManager::class,
		Filter\FilterManager::class,
	];

	/** @var array<class-string<Contracts\WooFeatureInterface>>|null Cached result of apply_filters. */
	private static ?array $resolvedFeatures = null;

	/** @var array<string, class-string<Contracts\WooFeatureInterface>> slug => FQCN (booted only) */
	private static array $booted = [];

	/** @var list<class-string> API controller classes collected during boot. */
	private static array $apiClasses = [];

	/* ---------- Boot ---------------------------------------------------- */

	public function boot(): void {

		// ── Core WC integrations (always-on, NOT sub-features) ──
		( new Core\ThemeSupport() )->register();

		// ── Admin: settings page ──
		if ( is_admin() ) {
			Admin\WCSettings::init();
		}

		// ── Boot enhanced sub-features ──
		$settings = self::getCachedOptions();

		foreach ( self::getFeatures() as $featureClass ) {
			// slug() is STATIC — check settings WITHOUT instantiation
			$slug = $featureClass::slug();

			if ( empty( $settings[ $slug ] ) ) {
				continue; // Zero footprint — no new, no register
			}

			$feature = new $featureClass();
			$feature->register();
			self::$booted[ $slug ] = $featureClass;

			// Collect API classes for batch registration
			if ( $feature instanceof HasAPI ) {
				array_push( self::$apiClasses, ...$feature::apiClasses() );
			}
		}

		// Register all collected API routes in a single named hook
		if ( ! empty( self::$apiClasses ) ) {
			add_action( 'rest_api_init', [ self::class, 'registerApiRoutes' ] );
		}
	}

	/**
	 * Register REST API routes for all booted sub-features.
	 * Hooked to `rest_api_init` — named method for removability.
	 */
	public static function registerApiRoutes(): void {
		foreach ( self::$apiClasses as $apiClass ) {
			( new $apiClass() )->register_routes();
		}
	}

	/**
	 * ModuleRegistry calls this — return empty.
	 * API registration handled in boot() via HasAPI.
	 */
	public static function apiClasses(): array {
		return [];
	}

	/* ---------- Features (cached) --------------------------------------- */

	/**
	 * Resolved feature list. Cached to avoid running apply_filters() on every call.
	 *
	 * @return array<class-string<Contracts\WooFeatureInterface>>
	 */
	public static function getFeatures(): array {
		return self::$resolvedFeatures ??= apply_filters( 'hd_wc_features', self::FEATURES );
	}

	/* ---------- Defaults ------------------------------------------------ */

	/**
	 * Defaults — auto-generate feature toggles + merge HasSettings.
	 *
	 * Adding a feature to FEATURES auto-generates a toggle (enabled by default).
	 * To disable: save to wp_option 'hd_woocommerce' => [ 'quick_view' => false ].
	 * Future admin UI implementation will write to this DB option.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		$defaults = [];

		foreach ( self::getFeatures() as $featureClass ) {
			// Auto-generate toggle: slug → true (enabled by default)
			$defaults[ $featureClass::slug() ] = true;

			// Merge feature-specific settings (gallery_layout, gallery_zoom, etc.)
			if ( is_subclass_of( $featureClass, HasSettings::class ) ) {
				$defaults = array_merge( $defaults, $featureClass::defaults() );
			}
		}

		return $defaults;
	}

	/* ---------- Accessors ----------------------------------------------- */

	/** @return array<string, class-string> slug => FQCN of booted features */
	public static function getBooted(): array {
		return self::$booted;
	}
}
