<?php
/**
 * Filter Manager — sub-feature entry point for AJAX Product Filter.
 *
 * Orchestrates FilterRegistry, FilterRenderer (frontend), and FilterAPI.
 * Provides filter instance configurations.
 *
 * @package HD\Modules\WooCommerce\Filter
 */

namespace HD\Modules\WooCommerce\Filter;

use HD\Core\DB;
use HD\Modules\WooCommerce\Contracts\HasAPI;
use HD\Modules\WooCommerce\Contracts\HasSettings;
use HD\Modules\WooCommerce\Contracts\WooFeatureInterface;
use HD\Modules\WooCommerce\Filter\Integrations\PolylangIntegration;
use HD\Modules\WooCommerce\WooCommerceModule;

defined( 'ABSPATH' ) || exit;

final class FilterManager implements WooFeatureInterface, HasAPI, HasSettings {
	private const COUNTS_CACHE_FLUSH_HOOK = 'hd_wc_filter_flush_counts_cache';

	public static function slug(): string {
		return 'ajax_filter';
	}

	public function register(): void {

		// Register filter preset CPT
		add_action( 'init', [ self::class, 'registerPostType' ] );

		PolylangIntegration::register();

		// Boot filter type registry (CONST array + extensions)
		FilterRegistry::boot();

		// Frontend: rendering + URL query application + shortcodes
		( new Frontend\FilterRenderer() )->register();
		Frontend\FilterShortcode::init();

		// Invalidate filter counts cache when any product changes
		add_action( 'clean_post_cache', [ self::class, 'invalidateCountsCache' ], 10, 2 );
		add_action( self::COUNTS_CACHE_FLUSH_HOOK, [ self::class, 'invalidateAllCountsCaches' ] );

		// Invalidate cache when preset changes
		add_action( 'save_post_' . FilterMeta::POST_TYPE, [ self::class, 'handlePresetSave' ] );

		// Suppress canonical redirect on filtered URLs (YITH pattern)
		if ( ! is_admin() ) {
			add_filter( 'redirect_canonical', [ self::class, 'maybeSuppressCanonicalRedirect' ] );
		}

		// Admin: preset editor + submenu + custom columns
		if ( is_admin() ) {
			add_action( 'admin_menu', [ self::class, 'addPresetSubmenu' ], 100 );

			// Custom columns for CPT list table
			$cpt = FilterMeta::POST_TYPE;
			add_filter( "manage_{$cpt}_posts_columns", [ self::class, 'presetColumns' ] );
			add_action( "manage_{$cpt}_posts_custom_column", [ self::class, 'presetColumnContent' ], 10, 2 );

			// Preset editor meta-box
			( new Admin\FilterPresetEditor() )->register();
		}
	}

	public static function apiClasses(): array {
		return [ API\FilterAPI::class ];
	}

	/** @inheritDoc */
	public static function settingsFields(): array {
		return [
			'filter_default_preset' => [
				'type'    => 'select',
				'options' => self::getPresetOptions(),
			],
		];
	}

	/** @inheritDoc */
	public static function defaults(): array {
		return [
			'filter_default_preset' => 0,
		];
	}

	// ── CPT Registration ────────────────────────────

	/**
	 * Register filter preset CPT. Hooked to `init`.
	 */
	public static function registerPostType(): void {
		register_post_type(
			FilterMeta::POST_TYPE,
			[
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => false,
				'supports'     => [ 'title' ],
				'labels'       => [
					'name'               => __( 'Filter Presets', 'hd' ),
					'singular_name'      => __( 'Filter Preset', 'hd' ),
					'add_new'            => __( 'Add Filter Preset', 'hd' ),
					'add_new_item'       => __( 'Add New Filter Preset', 'hd' ),
					'edit_item'          => __( 'Edit Filter Preset', 'hd' ),
					'all_items'          => __( 'Filter Presets', 'hd' ),
					'search_items'       => __( 'Search Presets', 'hd' ),
					'not_found'          => __( 'No presets found.', 'hd' ),
					'not_found_in_trash' => __( 'No presets found in Trash.', 'hd' ),
				],
			]
		);
	}

	/**
	 * Add preset submenu under HD WooCommerce menu.
	 */
	public static function addPresetSubmenu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'HD Filter Presets', 'hd' ),
			__( 'HD Filter Presets', 'hd' ),
			'manage_options',
			'edit.php?post_type=' . FilterMeta::POST_TYPE
		);
	}

	/**
	 * Custom columns for preset list table.
	 *
	 * @param array<string, string> $columns Existing columns.
	 *
	 * @return array<string, string>
	 */
	public static function presetColumns( array $columns ): array {
		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['hd_layout']    = __( 'Layout', 'hd' );
				$new['hd_filters']   = __( 'Filters', 'hd' );
				$new['hd_shortcode'] = __( 'Shortcode', 'hd' );
			}
		}

		return $new;
	}

	/**
	 * Custom column content for preset list table.
	 *
	 * @param string $column  Column key.
	 * @param int    $postId  Post ID.
	 */
	public static function presetColumnContent( string $column, int $postId ): void {
		switch ( $column ) {
			case 'hd_layout':
				$layout = get_post_meta( $postId, FilterMeta::LAYOUT, true ) ?: 'vertical';
				echo esc_html( ucfirst( $layout ) );
				break;

			case 'hd_filters':
				$config = get_post_meta( $postId, FilterMeta::CONFIG, true );
				$items  = is_array( $config ) ? $config : ( json_decode( (string) $config, true ) ?: [] );
				echo absint( count( $items ) );
				break;

			case 'hd_shortcode':
				printf(
					'<code>[hd_filter id="%d"]</code>',
					absint( $postId )
				);
				break;
		}
	}

	// ── Filter Instance Configuration ───────────────

	/** @var array<int|string, array<int, array<string, mixed>>> Per-request config cache. */
	private static array $configCache = [];

	/**
	 * Get filter instance configurations.
	 *
	 * Loads from CPT preset when available, falls back to hardcoded defaults.
	 * Extensible via `hd_wc_filter_configs` hook.
	 *
	 * @param int|null $presetId Specific preset ID, or null to auto-resolve.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function getFilterConfigs( ?int $presetId = null ): array {
		$presetId ??= self::resolvePresetId();
		$cacheKey   = $presetId ?? 'default';

		if ( isset( self::$configCache[ $cacheKey ] ) ) {
			return self::$configCache[ $cacheKey ];
		}

		// Try loading from CPT preset
		$configs = null !== $presetId ? self::loadPresetConfig( $presetId ) : [];

		// Fallback: hardcoded defaults for backward compat
		if ( empty( $configs ) ) {
			$configs = self::getDefaultConfigs();
		}

		// Sort by position
		usort( $configs, static fn( array $a, array $b ): int => ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 ) );

		/**
		 * Filter configurations. Add/remove/reorder filters.
		 */
		$configs = apply_filters( 'hd_wc_filter_configs', $configs );

		self::$configCache[ $cacheKey ] = $configs;

		return $configs;
	}

	/**
	 * Load filter configs from a CPT preset.
	 *
	 * @param int $id Preset post ID.
	 *
	 * @return array<int, array<string, mixed>> Normalized filter items, or empty array.
	 */
	public static function loadPresetConfig( int $id ): array {
		$post = get_post( $id );

		if ( ! $post || FilterMeta::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return [];
		}

		// Check if preset is enabled
		$enabled = get_post_meta( $id, FilterMeta::ENABLED, true );
		if ( '0' === $enabled ) {
			return [];
		}

		$raw = get_post_meta( $id, FilterMeta::CONFIG, true );
		if ( ! is_array( $raw ) ) {
			$raw = json_decode( (string) $raw, true );
		}

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return [];
		}

		return array_map( [ self::class, 'normalizeItemConfig' ], $raw );
	}

	/**
	 * Resolve which preset to use based on current context.
	 *
	 * Resolution order:
	 * 1. $_GET['hd_preset'] — explicit URL param
	 * 2. WCSettings filter_default_preset — admin-configured default
	 * 3. First published hd_filter_preset — ultimate fallback
	 *
	 * @return int|null Preset ID, or null if no presets exist.
	 */
	public static function resolvePresetId(): ?int {
		// 1. Explicit URL param
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['hd_preset'] ) ) {
			$explicit = absint( $_GET['hd_preset'] );
			if ( $explicit > 0 ) {
				return PolylangIntegration::translatePresetId( $explicit );
			}
		}

		// 2. Admin-configured default
		$options   = WooCommerceModule::getCachedOptions();
		$defaultId = absint( $options['filter_default_preset'] ?? 0 );
		if ( $defaultId > 0 ) {
			return PolylangIntegration::translatePresetId( $defaultId );
		}

		// 3. First published preset
		$first = get_posts(
			[
				'post_type'      => FilterMeta::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'ASC',
			]
		);

		return ! empty( $first ) ? PolylangIntegration::translatePresetId( (int) $first[0] ) : null;
	}

	/**
	 * Normalize a single filter item config by merging with defaults.
	 *
	 * @param array<string, mixed> $raw Raw config item.
	 *
	 * @return array<string, mixed> Normalized config.
	 */
	public static function normalizeItemConfig( array $raw ): array {
		return array_merge( FilterMeta::ITEM_DEFAULTS, $raw );
	}

	/**
	 * Get published preset options for settings dropdown.
	 *
	 * @return array<int|string, string> [id => title]
	 */
	public static function getPresetOptions(): array {
		$options = [ 0 => __( '— None (use defaults) —', 'hd' ) ];

		$presets = get_posts(
			[
				'post_type'      => FilterMeta::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		foreach ( $presets as $preset ) {
			$options[ $preset->ID ] = $preset->post_title ?: __( '(no title)', 'hd' );
		}

		return $options;
	}

	/**
	 * Hardcoded default filter configs (backward compatibility).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function getDefaultConfigs(): array {
		return [
			[
				'id'       => 'cat_1',
				'type'     => 'taxonomy',
				'label'    => __( 'Danh mục', 'hd' ),
				'taxonomy' => 'product_cat',
				'display'  => 'hierarchy',
				'adoptive' => 'hide',
				'position' => 1,
				'enabled'  => true,
			],
			[
				'id'       => 'price_1',
				'type'     => 'price_range',
				'label'    => __( 'Khoảng giá', 'hd' ),
				'mode'     => 'custom_ranges',
				'ranges'   => [
					[
						'min'   => 0,
						'max'   => 500000,
						'label' => __( 'Dưới 500k', 'hd' ),
					],
					[
						'min'   => 500000,
						'max'   => 1000000,
						'label' => __( '500k – 1 triệu', 'hd' ),
					],
					[
						'min'   => 1000000,
						'max'   => 5000000,
						'label' => __( '1 – 5 triệu', 'hd' ),
					],
					[
						'min'   => 5000000,
						'max'   => 0,
						'label' => __( 'Trên 5 triệu', 'hd' ),
					],
				],
				'adoptive' => 'hide',
				'position' => 2,
				'enabled'  => true,
			],
			[
				'id'       => 'rating_1',
				'type'     => 'rating',
				'label'    => __( 'Đánh giá', 'hd' ),
				'adoptive' => 'show',
				'position' => 3,
				'enabled'  => true,
			],
			[
				'id'       => 'stock_1',
				'type'     => 'stock',
				'label'    => __( 'Tình trạng', 'hd' ),
				'adoptive' => 'hide',
				'position' => 4,
				'enabled'  => true,
			],
			[
				'id'       => 'search_1',
				'type'     => 'search',
				'label'    => __( 'Tìm kiếm', 'hd' ),
				'position' => 5,
				'enabled'  => true,
			],
		];
	}

	/**
	 * Handle preset save — clear config cache and invalidate count transients.
	 * Hooked to `save_post_hd_filter_preset`.
	 */
	public static function handlePresetSave(): void {
		self::$configCache = [];
		self::invalidateAllCountsCaches();
	}

	/**
	 * Suppress canonical redirect when filter query params are present.
	 * Hooked to `redirect_canonical`.
	 *
	 * @param string|false $redirect The redirect URL, or false to cancel.
	 *
	 * @return string|false
	 */
	public static function maybeSuppressCanonicalRedirect( string|false $redirect ): string|false {
		if ( ! self::hasFilterQueryParamPrefix() || ! self::isFilterArchiveRequest() ) {
			return $redirect;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$configIds      = array_map(
			static fn( array $c ): string => 'hd_' . sanitize_key( $c['id'] ),
			self::getFilterConfigs()
		);
		$hasFilterParam = ! empty( array_intersect( array_keys( $_GET ), $configIds ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $hasFilterParam ? false : $redirect;
	}

	private static function hasFilterQueryParamPrefix(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( array_keys( $_GET ) as $key ) {
			if ( is_string( $key ) && str_starts_with( $key, 'hd_' ) ) {
				return true;
			}
		}

		return false;
	}

	private static function isFilterArchiveRequest(): bool {
		return ( function_exists( 'is_shop' ) && is_shop() )
			|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );
	}

	// --- Cache invalidation ---------------------------------------------------

	/**
	 * Invalidate filter counts transients when any product changes.
	 *
	 * @param int      $postId Post ID.
	 * @param \WP_Post $post   Post object.
	 */
	public static function invalidateCountsCache( int $postId, \WP_Post $post ): void {
		if ( 'product' !== $post->post_type && 'product_variation' !== $post->post_type ) {
			return;
		}

		self::scheduleCountsCacheFlush();
	}

	/**
	 * Invalidate all filter counts caches (called on preset save).
	 */
	public static function invalidateAllCountsCaches(): void {
		self::deleteCountsTransients();
	}

	/**
	 * Delete all `hd_fc_*` transients from the options table.
	 * WP has no wildcard delete_transient, so we use direct SQL.
	 */
	private static function deleteCountsTransients(): void {
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( API\FilterAPI::COUNTS_CACHE_GROUP );
		}

		$db      = DB::db();
		$pattern = $db->esc_like( '_transient_hd_fc_' ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional bulk transient cleanup
		$db->query( $db->prepare( "DELETE FROM {$db->options} WHERE option_name LIKE %s OR option_name LIKE %s", $pattern, str_replace( '_transient_', '_transient_timeout_', $pattern ) ) );
	}

	private static function scheduleCountsCacheFlush(): void {
		if ( wp_next_scheduled( self::COUNTS_CACHE_FLUSH_HOOK ) ) {
			return;
		}

		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::COUNTS_CACHE_FLUSH_HOOK );
	}
}
