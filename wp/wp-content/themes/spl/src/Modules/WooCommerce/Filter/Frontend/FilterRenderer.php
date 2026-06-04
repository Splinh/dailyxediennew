<?php
/**
 * Filter Renderer — frontend filter rendering and URL query application.
 *
 * Handles:
 * - Layout-aware rendering (vertical/horizontal) via template parts
 * - Server-side URL filter application (SEO + direct access)
 * - Server-side chips rendering (active filter tags)
 *
 * @package SPL\Modules\WooCommerce\Filter\Frontend
 */

namespace SPL\Modules\WooCommerce\Filter\Frontend;

use SPL\Modules\WooCommerce\Filter\FilterManager;
use SPL\Modules\WooCommerce\Filter\Enum\AdoptiveMode;
use SPL\Modules\WooCommerce\Filter\FilterMeta;
use SPL\Modules\WooCommerce\Filter\FilterRegistry;
use SPL\Modules\WooCommerce\Filter\Integrations\PolylangIntegration;

defined( 'ABSPATH' ) || exit;

final class FilterRenderer {

	/**
	 * Register frontend hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_product_query', [ self::class, 'applyUrlFilters' ] );
		add_action( 'woocommerce_before_shop_loop', [ self::class, 'autoRender' ], 5 );
	}

	// ── Server-side URL Handling (SEO) ──────────────

	/**
	 * Apply URL filters to the main WC product query.
	 *
	 * Guards (from YITH pattern):
	 * - Must be main query
	 * - Must not be admin
	 * - Must have at least one matching hd_ param
	 *
	 * @param \WP_Query $query The main product query.
	 */
	public static function applyUrlFilters( \WP_Query $query ): void {
		// Guard: only main product query on frontend
		if ( ! $query->is_main_query() || is_admin() ) {
			return;
		}

		$filterConfigs = FilterManager::getFilterConfigs();
		if ( empty( $filterConfigs ) ) {
			return;
		}

		// Guard: check if any hd_ filter params exist in URL
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$getKeys    = array_keys( $_GET );
		$filterKeys = array_map( fn( array $c ): string => 'hd_' . sanitize_key( $c['id'] ), $filterConfigs );
		$activeKeys = array_intersect( $getKeys, $filterKeys );

		if ( empty( $activeKeys ) ) {
			return;
		}

		foreach ( $filterConfigs as $config ) {
			if ( empty( $config['enabled'] ) ) {
				continue;
			}

			$paramKey = 'hd_' . sanitize_key( $config['id'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter params on frontend
			$rawValue = isset( $_GET[ $paramKey ] ) ? wc_clean( wp_unslash( $_GET[ $paramKey ] ) ) : null;

			if ( null === $rawValue ) {
				continue;
			}

			$filterType = FilterRegistry::make( $config['type'], $config );
			if ( ! $filterType ) {
				continue;
			}

			$args = $query->query_vars;
			$filterType->applyToQuery( $args, self::normalizeUrlFilterValue( $rawValue ) );

			// Apply all modified args back to query
			foreach ( $args as $key => $val ) {
				if ( $val !== ( $query->query_vars[ $key ] ?? null ) ) {
					$query->set( $key, $val );
				}
			}
		}
	}

	// ── Rendering ───────────────────────────────────

	/**
	 * Auto-render filter on shop/archive when a default preset is configured.
	 */
	public static function autoRender(): void {
		if ( ! is_shop() && ! is_product_taxonomy() ) {
			return;
		}

		$presetId = FilterManager::resolvePresetId();
		if ( null === $presetId ) {
			return;
		}

		self::render( $presetId );
	}

	/**
	 * Render the filter UI for a given preset.
	 *
	 * @param int    $presetId Preset post ID.
	 * @param string $layout   Layout override (vertical|horizontal). Empty = use preset default.
	 * @param string $cssClass Extra CSS class.
	 */
	public static function render( int $presetId, string $layout = '', string $cssClass = '' ): void {
		$presetId      = PolylangIntegration::translatePresetId( $presetId );
		$filterConfigs = FilterManager::getFilterConfigs( $presetId );
		if ( empty( $filterConfigs ) ) {
			return;
		}

		// Read preset layout/trigger from meta, allow override
		if ( '' === $layout ) {
			$layout = get_post_meta( $presetId, FilterMeta::LAYOUT, true ) ?: 'vertical';
		}
		$trigger = get_post_meta( $presetId, FilterMeta::TRIGGER, true ) ?: 'hybrid';

		// Build filter instances + active values
		$groups = [];
		foreach ( $filterConfigs as $config ) {
			if ( empty( $config['enabled'] ) ) {
				continue;
			}

			$filterType = FilterRegistry::make( $config['type'], $config );
			if ( ! $filterType ) {
				continue;
			}

			$filterId = $config['id'] ?? '';
			$paramKey = 'hd_' . sanitize_key( $filterId );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$activeValues = isset( $_GET[ $paramKey ] )
				? self::normalizeUrlFilterValues( wc_clean( wp_unslash( $_GET[ $paramKey ] ) ) )
				: [];

			$html = $filterType->render( $activeValues, [] );
			if ( '' === $html ) {
				continue;
			}

			$groups[] = [
				'id'     => $filterId,
				'label'  => $config['label'] ?? '',
				'html'   => $html,
				'config' => $config,
			];
		}

		if ( empty( $groups ) ) {
			return;
		}

		$chipsHtml = self::renderChips( $presetId );

		// Delegate to template part
		$templateArgs = [
			'groups'    => $groups,
			'presetId'  => $presetId,
			'layout'    => $layout,
			'trigger'   => $trigger,
			'chipsHtml' => $chipsHtml,
			'class'     => $cssClass,
		];

		$viewFile = __DIR__ . '/views/filter-' . $layout . '.php';

		if ( file_exists( $viewFile ) ) {
			self::loadView( $viewFile, $templateArgs );
		} else {
			// Fallback: inline default rendering
			self::renderDefault( $templateArgs );
		}
	}

	/**
	 * Render server-side chips (active filter tags).
	 *
	 * @param int $presetId Preset ID.
	 *
	 * @return string Chips HTML.
	 */
	public static function renderChips( int $presetId ): string {
		$presetId = PolylangIntegration::translatePresetId( $presetId );
		$configs  = FilterManager::getFilterConfigs( $presetId );
		if ( empty( $configs ) ) {
			return '';
		}

		$chips = [];
		foreach ( $configs as $config ) {
			if ( empty( $config['enabled'] ) || empty( $config['show_chips'] ?? true ) ) {
				continue;
			}

			$filterId = $config['id'] ?? '';
			$paramKey = 'hd_' . sanitize_key( $filterId );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET[ $paramKey ] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$rawVal = wc_clean( wp_unslash( $_GET[ $paramKey ] ) );
			$values = self::normalizeUrlFilterValues( $rawVal );

			foreach ( $values as $value ) {
				$label     = self::resolveValueLabel( $config, $value );
				$removeUrl = self::buildRemoveUrl( $paramKey, $value );
				$chips[]   = sprintf(
					'<span class="hd-filter__chip" data-filter="%s" data-value="%s">%s: %s <a href="%s" class="hd-filter__chip-remove" aria-label="%s">&times;</a></span>',
					esc_attr( $filterId ),
					esc_attr( $value ),
					esc_html( $config['label'] ?? '' ),
					esc_html( $label ),
					esc_url( $removeUrl ),
					/* translators: %s: filter value label */
					esc_attr( sprintf( __( 'Remove %s', 'SPL' ), $label ) )
				);
			}
		}

		if ( empty( $chips ) ) {
			return '';
		}

		$resetUrl = self::currentBaseUrl();

		return '<div class="hd-filter__chips" data-filter-chips>'
			. implode( '', $chips )
			. sprintf(
				' <a href="%s" class="hd-filter__chip hd-filter__chip--reset">%s</a>',
				esc_url( $resetUrl ),
				esc_html__( 'Clear all', 'SPL' )
			)
			. '</div>';
	}

	/**
	 * Build chips HTML from active filters array (for API response).
	 *
	 * @param array<string, mixed> $activeFilters Filter ID => values map.
	 * @param array<int, array>    $configs       Filter configs.
	 *
	 * @return string Chips HTML.
	 */
	public static function buildChipsHtml( array $activeFilters, array $configs ): string {
		$chips = [];
		foreach ( $configs as $config ) {
			if ( empty( $config['enabled'] ) || empty( $config['show_chips'] ?? true ) ) {
				continue;
			}

			$filterId = $config['id'] ?? '';
			if ( ! isset( $activeFilters[ $filterId ] ) ) {
				continue;
			}

			$values = (array) $activeFilters[ $filterId ];
			foreach ( $values as $value ) {
				$label   = self::resolveValueLabel( $config, $value );
				$chips[] = sprintf(
					'<span class="hd-filter__chip" data-filter="%s" data-value="%s">%s: %s <button type="button" class="hd-filter__chip-remove" aria-label="%s">&times;</button></span>',
					esc_attr( $filterId ),
					esc_attr( $value ),
					esc_html( $config['label'] ?? '' ),
					esc_html( $label ),
					esc_attr( sprintf( __( 'Remove %s', 'SPL' ), $label ) )
				);
			}
		}

		if ( empty( $chips ) ) {
			return '';
		}

		return '<div class="hd-filter__chips" data-filter-chips>'
			. implode( '', $chips )
			. sprintf(
				' <button type="button" class="hd-filter__chip hd-filter__chip--reset" data-filter-reset>%s</button>',
				esc_html__( 'Clear all', 'SPL' )
			)
			. '</div>';
	}

	// ── Helpers ─────────────────────────────────────

	/**
	 * Normalize a frontend URL filter value.
	 *
	 * @param mixed $rawValue Raw cleaned URL value.
	 *
	 * @return mixed
	 */
	private static function normalizeUrlFilterValue( mixed $rawValue ): mixed {
		if ( is_array( $rawValue ) ) {
			return array_map( 'sanitize_text_field', $rawValue );
		}

		if ( is_string( $rawValue ) && str_contains( $rawValue, ',' ) ) {
			return array_map( 'sanitize_text_field', explode( ',', $rawValue ) );
		}

		return is_string( $rawValue ) ? sanitize_text_field( $rawValue ) : $rawValue;
	}

	/**
	 * Normalize a frontend URL filter value to a list for rendering.
	 *
	 * @param mixed $rawValue Raw cleaned URL value.
	 *
	 * @return array<int, string>
	 */
	private static function normalizeUrlFilterValues( mixed $rawValue ): array {
		$value = self::normalizeUrlFilterValue( $rawValue );

		return array_map(
			static fn( mixed $item ): string => (string) $item,
			array_values(
				array_filter(
					(array) $value,
					static fn( mixed $item ): bool => '' !== (string) $item
				)
			)
		);
	}

	/**
	 * Resolve a human-readable label for a filter value.
	 *
	 * @param array<string, mixed> $config Filter config.
	 * @param string               $value  Raw filter value.
	 *
	 * @return string Label.
	 */
	private static function resolveValueLabel( array $config, string $value ): string {
		$type = $config['type'] ?? '';

		// Taxonomy / Attribute — look up term name
		if ( in_array( $type, [ 'taxonomy', 'attribute' ], true ) && ! empty( $config['taxonomy'] ) ) {
			$term = get_term_by( 'slug', $value, $config['taxonomy'] );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term->name;
			}
		}

		// Price range — find matching range label
		if ( 'price_range' === $type && ! empty( $config['ranges'] ) ) {
			foreach ( $config['ranges'] as $range ) {
				$rangeKey = ( $range['min'] ?? 0 ) . '-' . ( $range['max'] ?? 0 );
				if ( $rangeKey === $value ) {
					return $range['label'] ?? $value;
				}
			}
		}

		// Rating
		if ( 'rating' === $type ) {
			return sprintf( __( '%s stars', 'SPL' ), $value );
		}

		return $value;
	}

	/**
	 * Build a URL with a specific filter value removed.
	 *
	 * @param string $paramKey GET param key (e.g., 'hd_color').
	 * @param string $value    Value to remove.
	 *
	 * @return string Modified URL.
	 */
	private static function buildRemoveUrl( string $paramKey, string $value ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Chip links mirror current query args after WordPress unslashing.
		$params = wp_unslash( $_GET );

		if ( isset( $params[ $paramKey ] ) ) {
			// Multi-value: comma-separated string
			if ( is_string( $params[ $paramKey ] ) && str_contains( $params[ $paramKey ], ',' ) ) {
				$parts = array_filter(
					explode( ',', $params[ $paramKey ] ),
					static fn( $v ) => $v !== $value
				);
				if ( empty( $parts ) ) {
					unset( $params[ $paramKey ] );
				} else {
					$params[ $paramKey ] = implode( ',', $parts );
				}
			} elseif ( is_array( $params[ $paramKey ] ) ) {
				$params[ $paramKey ] = array_filter(
					$params[ $paramKey ],
					static fn( $v ) => $v !== $value
				);
				if ( empty( $params[ $paramKey ] ) ) {
					unset( $params[ $paramKey ] );
				}
			} elseif ( $params[ $paramKey ] === $value ) {
				unset( $params[ $paramKey ] );
			}
		}

		$baseUrl = self::currentBaseUrl();

		return empty( $params ) ? $baseUrl : $baseUrl . '?' . http_build_query( $params );
	}

	/**
	 * Resolve the current request path on the configured site URL.
	 */
	private static function currentBaseUrl(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Request URI is unslashed then passed through home_url().
		$requestUri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' );
		$baseUrl    = strtok( home_url( $requestUri ), '?' );

		return is_string( $baseUrl ) && '' !== $baseUrl ? $baseUrl : home_url( '/' );
	}

	/**
	 * Default inline rendering when no template part exists.
	 *
	 * @param array<string, mixed> $args Template args.
	 */
	private static function renderDefault( array $args ): void {
		$layout   = $args['layout'] ?? 'vertical';
		$trigger  = $args['trigger'] ?? 'hybrid';
		$presetId = $args['presetId'] ?? 0;
		$groups   = $args['groups'] ?? [];
		$class    = $args['class'] ?? '';
		$chips    = $args['chipsHtml'] ?? '';

		printf(
			'<div class="hd-filter hd-filter--%s %s" data-wc-filter data-preset="%d" data-trigger="%s">',
			esc_attr( $layout ),
			esc_attr( $class ),
			absint( $presetId ),
			esc_attr( $trigger )
		);

		// Chips bar
		if ( $chips ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in renderChips
			echo $chips;
		}

		foreach ( $groups as $group ) {
			$config   = $group['config'];
			$collapse = ! empty( $config['collapse'] );

			$adoptive = AdoptiveMode::fromConfig( $config['adoptive'] ?? AdoptiveMode::Show->value )->value;
			printf(
				'<details class="hd-filter__group" data-filter-group="%s" data-adoptive="%s"%s>',
				esc_attr( $group['id'] ),
				esc_attr( $adoptive ),
				$collapse ? '' : ' open'
			);
			printf(
				'<summary class="hd-filter__title">%s</summary>',
				esc_html( $group['label'] )
			);
			printf(
				'<div class="hd-filter__body">%s</div>',
				$group['html'] // Already escaped in render methods
			);
			echo '</details>';
		}

		// Reset button
		printf(
			'<div class="hd-filter__actions"><button type="button" class="hd-filter__reset" data-filter-reset>%s</button></div>',
			esc_html__( 'Xóa bộ lọc', 'SPL' )
		);

		echo '</div>';
	}

	/**
	 * Isolate variable scope for view rendering.
	 *
	 * @param string               $file File path.
	 * @param array<string, mixed> $args Template arguments.
	 */
	private static function loadView( string $file, array $args ): void {
		require $file;

		// Satisfy unused parameter linters (Intelephense/PHPCS).
		unset( $args );
	}
}
