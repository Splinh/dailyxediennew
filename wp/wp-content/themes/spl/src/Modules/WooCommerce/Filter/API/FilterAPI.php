<?php
/**
 * Filter API — REST endpoint for AJAX product filtering.
 *
 * POST /hd/v1/wc-filter/products
 *
 * Receives active filters, builds WP_Query, server-renders products HTML,
 * and returns counts for each filter option.
 *
 * @package SPL\Modules\WooCommerce\Filter\API
 */

namespace SPL\Modules\WooCommerce\Filter\API;

use SPL\API\AbstractAPI;
use SPL\Modules\WooCommerce\Filter\FilterManager;
use SPL\Modules\WooCommerce\Filter\FilterRegistry;
use SPL\Modules\WooCommerce\Filter\Frontend\FilterRenderer;

defined( 'ABSPATH' ) || exit;

final class FilterAPI extends AbstractAPI {
	public const COUNTS_CACHE_GROUP = 'hd_wc_filter_counts';

	private const COUNTS_CACHE_INDEX_KEY = 'hd_fc_index';
	private const COUNTS_CACHE_MAX_KEYS  = 250;

	/**
	 * Register REST routes.
	 */
	protected function registerRoutes(): void {
		register_rest_route(
			REST_NAMESPACE,
			'/wc-filter/products',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => $this->filter( ... ),
				'permission_callback' => '__return_true',
				'args'                => [
					'filters'   => [
						'type'              => 'object',
						'default'           => [],
						'sanitize_callback' => $this->sanitizeFilters( ... ),
					],
					'page'      => [
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					],
					'per_page'  => [
						'type'              => 'integer',
						'default'           => 12,
						'sanitize_callback' => 'absint',
					],
					'preset_id' => [
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			REST_NAMESPACE,
			'/wc-filter/terms',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => $this->terms( ... ),
				'permission_callback' => static fn() => current_user_can( 'edit_posts' ),
				'args'                => [
					'taxonomy' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					'search'   => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'per_page' => [
						'type'              => 'integer',
						'default'           => 50,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Sanitize filters parameter — sanitize each value per type.
	 *
	 * @param mixed $filters Raw filters.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitizeFilters( mixed $filters ): array {
		if ( ! is_array( $filters ) ) {
			return [];
		}

		$clean = [];
		foreach ( $filters as $key => $value ) {
			$key = sanitize_key( $key );
			if ( is_array( $value ) ) {
				$clean[ $key ] = array_map( 'sanitize_text_field', $value );
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $clean;
	}

	/**
	 * Handle term search request for Exclude Terms Select2.
	 *
	 * GET /hd/v1/wc-filter/terms?taxonomy=product_cat&search=foo&per_page=50
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	private function terms( \WP_REST_Request $request ): \WP_REST_Response {
		$taxonomy = $request->get_param( 'taxonomy' );
		$search   = $request->get_param( 'search' );
		$perPage  = min( 200, absint( $request->get_param( 'per_page' ) ?: 50 ) );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $this->sendResponse( [ 'message' => __( 'Invalid taxonomy.', 'SPL' ) ], 400 );
		}

		$queryArgs = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => $perPage,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];
		if ( '' !== $search ) {
			$queryArgs['name__like'] = $search;
		}

		$terms  = get_terms( $queryArgs );
		$result = [];

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$result[] = [
					'id'   => $term->slug,
					'text' => $term->name . ' (' . $term->count . ')',
				];
			}
		}

		return $this->sendResponse( [], 200, $result );
	}

	/**
	 * Handle filter request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	private function filter( \WP_REST_Request $request ): \WP_REST_Response {

		// Nonce verify
		$nonceCheck = $this->verifyNonce( $request );
		if ( $nonceCheck ) {
			return $nonceCheck;
		}

		// Rate limit: 30 requests per minute
		$rateLimitCheck = $this->rateLimit( 'wc_filter', 30, 60 );
		if ( $rateLimitCheck instanceof \WP_REST_Response ) {
			return $rateLimitCheck;
		}

		// Load configured filter instances (preset-aware)
		$presetId      = absint( $request->get_param( 'preset_id' ) );
		$filterConfigs = $presetId > 0
			? FilterManager::getFilterConfigs( $presetId )
			: FilterManager::getFilterConfigs();
		$filters       = (array) $request->get_param( 'filters' );

		// Build base query args
		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => min( 48, absint( $request->get_param( 'per_page' ) ?: 12 ) ),
			'paged'          => max( 1, absint( $request->get_param( 'page' ) ?: 1 ) ),
			'tax_query'      => [ 'relation' => 'AND' ],
			'meta_query'     => [ 'relation' => 'AND' ],
		];

		// Build filter instances once — reuse for applyToQuery + getCounts
		$filterInstances = [];
		foreach ( $filterConfigs as $config ) {
			if ( empty( $config['enabled'] ) ) {
				continue;
			}

			$filterType = FilterRegistry::make( $config['type'], $config );
			if ( $filterType ) {
				$filterInstances[ $config['id'] ] = $filterType;
			}
		}

		// Apply each active filter to query
		foreach ( $filterInstances as $filterId => $filterType ) {
			if ( ! isset( $filters[ $filterId ] ) ) {
				continue;
			}

			$filterType->applyToQuery( $args, $filters[ $filterId ] );
		}

		// Execute query
		$query = new \WP_Query( $args );

		// Server-render products HTML
		$productsHtml = $this->renderProducts( $query );

		// Get counts for each filter — transient cached by preset + args
		$countsCacheKey = $this->countsCacheKey( $presetId, $args );
		$counts         = $this->getCountsCache( $countsCacheKey );

		if ( false === $counts ) {
			$counts = [];
			foreach ( $filterInstances as $filterId => $filterType ) {
				$typeCounts = $filterType->getCounts( $args );
				if ( ! empty( $typeCounts ) ) {
					$counts[ $filterId ] = $typeCounts;
				}
			}

			$this->setCountsCache( $countsCacheKey, $counts );
		}

		// Build chips HTML from active filters
		$chipsHtml = FilterRenderer::buildChipsHtml( $filters, $filterConfigs );

		return $this->sendResponse(
			[],
			200,
			[
				'products_html'   => $productsHtml,
				'pagination_html' => $this->renderPagination( $query ),
				'result_count'    => $query->found_posts,
				'counts'          => $counts,
				'chips_html'      => $chipsHtml,
			]
		);
	}

	/**
	 * Render products from query using WC template parts.
	 *
	 * @param \WP_Query $query Product query.
	 *
	 * @return string Rendered HTML.
	 */
	private function renderProducts( \WP_Query $query ): string {
		ob_start();

		if ( $query->have_posts() ) {
			woocommerce_product_loop_start();

			while ( $query->have_posts() ) {
				$query->the_post();
				wc_get_template_part( 'content', 'product' );
			}

			woocommerce_product_loop_end();
		} else {
			wc_get_template( 'loop/no-products-found.php' );
		}

		$html = ob_get_clean();
		wp_reset_postdata();

		return $html;
	}

	/**
	 * Render pagination from query.
	 *
	 * @param \WP_Query $query Product query.
	 *
	 * @return string Rendered pagination HTML.
	 */
	private function renderPagination( \WP_Query $query ): string {
		if ( $query->max_num_pages <= 1 ) {
			return '';
		}

		ob_start();

		echo '<nav class="woocommerce-pagination">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post applied
		echo wp_kses_post(
			paginate_links(
				[
					'total'   => $query->max_num_pages,
					'current' => max( 1, $query->get( 'paged' ) ),
					'format'  => '?paged=%#%',
					'type'    => 'list',
				]
			) ?? ''
		);
		echo '</nav>';

		return ob_get_clean();
	}

	/**
	 * Count caches vary by active filters, not by the current product page.
	 *
	 * @param array<string, mixed> $args Query args.
	 */
	private function countsCacheKey( int $presetId, array $args ): string {
		unset( $args['paged'], $args['posts_per_page'] );
		$args = $this->sortRecursive( $args );

		return 'hd_fc_' . $presetId . '_' . md5( wp_json_encode( $args ) ?: '' );
	}

	private function getCountsCache( string $key ): mixed {
		if ( $this->usesPersistentObjectCache() ) {
			return wp_cache_get( $key, self::COUNTS_CACHE_GROUP );
		}

		return get_transient( $key );
	}

	/**
	 * @param array<string, mixed> $counts
	 */
	private function setCountsCache( string $key, array $counts ): void {
		if ( $this->usesPersistentObjectCache() ) {
			wp_cache_set( $key, $counts, self::COUNTS_CACHE_GROUP, HOUR_IN_SECONDS );
			return;
		}

		set_transient( $key, $counts, HOUR_IN_SECONDS );
		$this->trackCountsTransient( $key );
	}

	private function trackCountsTransient( string $key ): void {
		$keys   = get_transient( self::COUNTS_CACHE_INDEX_KEY );
		$keys   = is_array( $keys ) ? array_values( array_filter( $keys, 'is_string' ) ) : [];
		$keys   = array_values( array_diff( $keys, [ $key ] ) );
		$keys[] = $key;

		$keyCount = count( $keys );
		if ( $keyCount > self::COUNTS_CACHE_MAX_KEYS ) {
			$evicted = array_slice( $keys, 0, $keyCount - self::COUNTS_CACHE_MAX_KEYS );
			foreach ( $evicted as $oldest ) {
				if ( is_string( $oldest ) ) {
					delete_transient( $oldest );
				}
			}
			$keys = array_slice( $keys, -self::COUNTS_CACHE_MAX_KEYS );
		}

		$ttl = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : HOUR_IN_SECONDS * 24;
		set_transient( self::COUNTS_CACHE_INDEX_KEY, $keys, $ttl );
	}

	private function usesPersistentObjectCache(): bool {
		return function_exists( 'wp_using_ext_object_cache' )
			&& wp_using_ext_object_cache()
			&& function_exists( 'wp_cache_get' )
			&& function_exists( 'wp_cache_set' );
	}

	/**
	 * @param array<string|int, mixed> $value
	 *
	 * @return array<string|int, mixed>
	 */
	private function sortRecursive( array $value ): array {
		foreach ( $value as $key => $item ) {
			if ( is_array( $item ) ) {
				$value[ $key ] = $this->sortRecursive( $item );
			}
		}

		if ( array_is_list( $value ) ) {
			return $value;
		}

		ksort( $value );

		return $value;
	}
}
