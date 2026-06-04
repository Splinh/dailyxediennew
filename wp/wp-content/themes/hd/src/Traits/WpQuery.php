<?php
/**
 * WordPress Query builder helpers with caching.
 *
 * Provides static utility methods for querying posts
 * by terms, latest posts, related posts, and cached queries.
 *
 * @package HD\Traits
 */

namespace HD\Traits;

use HD\Core\Cache;
use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

trait WpQuery {

	// =============================================================
	// PUBLIC QUERY METHODS
	// =============================================================

	/**
	 * Query posts by term IDs with caching support.
	 *
	 * @param array $options Query options.
	 *
	 * @return \WP_Query|array|false
	 */
	public static function queryByTerms( array $options = [] ): \WP_Query|array|false {
		$config = self::buildTermsQueryConfig( $options );
		if ( ! $config ) {
			return false;
		}

		$ids = self::executeWithCache(
			$config['cache_key'],
			$config['cache_expire'],
			$config['is_random'],
			static fn() => self::doTermsQuery( $config )
		);

		return self::processQueryResult( $ids, $config['post_type'], $config['return_query'] );
	}

	// -------------------------------------------------------------

	/**
	 * Query posts by single term object.
	 *
	 * @param object|int $term    Term object or ID.
	 * @param array      $options Query options.
	 *
	 * @return \WP_Query|array|false
	 */
	public static function queryByTerm( object|int $term, array $options = [] ): \WP_Query|array|false {
		if ( ! $term ) {
			return false;
		}

		if ( is_numeric( $term ) ) {
			$term = get_term( (int) $term );
		}

		if ( ! $term || is_wp_error( $term ) || empty( $term->taxonomy ) || empty( $term->term_id ) ) {
			return false;
		}

		$options['terms']    = [ (int) $term->term_id ];
		$options['taxonomy'] = (string) $term->taxonomy;

		$options['include_children'] ??= true;

		return self::queryByTerms( $options );
	}

	// -------------------------------------------------------------

	/**
	 * Query latest posts with caching support.
	 *
	 * @param array $options Query options.
	 *
	 * @return \WP_Query|array|false
	 */
	public static function queryByLatestPosts( array $options = [] ): \WP_Query|array|false {
		$config = self::buildLatestQueryConfig( $options );

		$ids = self::executeWithCache(
			$config['cache_key'],
			$config['cache_expire'],
			false,
			static fn() => self::doLatestQuery( $config )
		);

		return self::processQueryResult( $ids, $config['post_type'], $config['return_query'] );
	}

	// -------------------------------------------------------------

	/**
	 * Query related posts by taxonomy.
	 *
	 * @param int|object $postId   Post ID or object.
	 * @param string     $taxonomy Taxonomy name.
	 * @param array      $options  Query options.
	 *
	 * @return \WP_Query|array|false
	 */
	public static function queryByRelated( int|object $postId, string $taxonomy = '', array $options = [] ): \WP_Query|array|false {
		$postId    = is_object( $postId ) ? $postId->ID : (int) $postId;
		$postTerms = get_the_terms( $postId, $taxonomy );

		if ( ! is_array( $postTerms ) || ! $postTerms ) {
			return false;
		}

		$defaults = [
			'terms'            => wp_list_pluck( $postTerms, 'term_id' ),
			'post_type'        => get_post_type( $postId ),
			'taxonomy'         => $taxonomy,
			'limit'            => 6,
			'include_children' => false,
			'exclude_ids'      => [ $postId ],
		];

		return self::queryByTerms( wp_parse_args( $options, $defaults ) );
	}

	// -------------------------------------------------------------

	/**
	 * Cached WP_Query wrapper.
	 *
	 * Use this instead of new WP_Query() when you want caching.
	 * Cache is automatically invalidated when posts are updated.
	 *
	 * @param array  $args        WP_Query arguments.
	 * @param string $cacheKey    Optional custom cache key. Auto-generated if empty.
	 * @param int    $ttl         Cache TTL in seconds. Default 1 hour.
	 * @param bool   $returnQuery Return WP_Query object (true) or just post IDs (false).
	 *
	 * @return \WP_Query|array|false
	 */
	public static function cachedQuery( array $args, string $cacheKey = '', int $ttl = HOUR_IN_SECONDS, bool $returnQuery = true ): \WP_Query|array|false {
		$orderby = $args['orderby'] ?? 'date';

		// Skip cache for random order or no TTL
		if ( $orderby === 'rand' || $ttl <= 0 ) {
			return self::executeCachedQuery( $args, $returnQuery );
		}

		// Generate cache key if not provided
		$cacheKey = $cacheKey ?: 'query_' . md5( wp_json_encode( $args ) );

		// Modify args to only fetch IDs (smaller cache)
		$args['fields'] = 'ids';

		$ids = Cache::remember(
			$cacheKey,
			static fn() => self::executeCachedQueryForIds( $args ),
			'theme_queries',
			$ttl
		);

		if ( empty( $ids ) ) {
			return false;
		}

		return $returnQuery
			? self::buildCachedQueryResult( $ids, $args['post_type'] ?? 'post', $args['post_status'] ?? 'publish' )
			: $ids;
	}

	// =============================================================
	// CONFIG BUILDERS (private)
	// =============================================================

	/**
	 * Build configuration array for terms query.
	 */
	private static function buildTermsQueryConfig( array $options ): array|false {
		$defaults = [
			'terms'            => [],
			'post_type'        => 'post',
			'taxonomy'         => '',
			'limit'            => 12,
			'return_query'     => true,
			'include_children' => false,
			'exclude_ids'      => [],
			'orderby'          => 'date',
			'order'            => 'DESC',
			'cache_expire'     => 600,
		];

		$opts    = wp_parse_args( $options, $defaults );
		$termIds = self::normalizeTermIds( $opts['terms'] );

		if ( ! $termIds ) {
			return false;
		}

		$postType = (string) $opts['post_type'] ?: 'post';
		$taxonomy = (string) $opts['taxonomy'] ?: ( $postType === 'product' ? 'product_cat' : 'category' );
		$limit    = min( max( (int) $opts['limit'], -1 ), 100 );

		if ( $limit === -1 ) {
			$limit = 100;
		}

		// Orderby validation
		$isProduct = ( $postType === 'product' && function_exists( 'wc_get_products' ) );
		$allowedWp = [ 'date', 'title', 'menu_order', 'rand', 'modified', 'id' ];
		$allowedWc = [ 'date', 'price', 'rating', 'popularity', 'title', 'menu_order', 'rand', 'id', 'modified' ];
		$allowed   = $isProduct ? $allowedWc : $allowedWp;
		$orderby   = in_array( strtolower( $opts['orderby'] ), $allowed, true ) ? strtolower( $opts['orderby'] ) : 'date';
		$order     = strtoupper( $opts['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$excludeIds = array_values( array_unique( array_map( 'intval', (array) $opts['exclude_ids'] ) ) );
		$hideOos    = $isProduct && Helper::getOption( 'woocommerce_hide_out_of_stock_items' ) === 'yes';

		$ckeyParts = [ $postType, $taxonomy, $termIds, $limit, $orderby, $order, $hideOos, $opts['include_children'], $excludeIds ];

		return [
			'term_ids'          => $termIds,
			'post_type'         => $postType,
			'taxonomy'          => $taxonomy,
			'limit'             => $limit,
			'orderby'           => $orderby,
			'order'             => $order,
			'exclude_ids'       => $excludeIds,
			'include_children'  => (bool) $opts['include_children'],
			'return_query'      => (bool) $opts['return_query'],
			'cache_expire'      => max( (int) $opts['cache_expire'], 0 ),
			'cache_key'         => 'qbt:terms:' . md5( wp_json_encode( $ckeyParts ) ),
			'is_product'        => $isProduct,
			'is_random'         => $orderby === 'rand',
			'hide_out_of_stock' => $hideOos,
		];
	}

	// -------------------------------------------------------------

	/**
	 * Build configuration array for latest posts query.
	 */
	private static function buildLatestQueryConfig( array $options ): array {
		$defaults = [
			'post_type'    => 'post',
			'limit'        => 10,
			'return_query' => true,
			'exclude_ids'  => [],
			'since'        => false,
			'cache_expire' => 600,
		];

		$opts       = wp_parse_args( $options, $defaults );
		$postType   = (string) $opts['post_type'] ?: 'post';
		$limit      = min( max( (int) $opts['limit'], -1 ), 100 );
		$excludeIds = array_values( array_unique( array_map( 'intval', (array) $opts['exclude_ids'] ) ) );

		if ( $limit === -1 ) {
			$limit = 100;
		}

		$sinceTs = false;
		if ( $opts['since'] ) {
			$tmp = strtotime( (string) $opts['since'] );
			if ( $tmp && $tmp > 0 && $tmp <= time() ) {
				$sinceTs = $tmp;
			}
		}

		$isProduct = $postType === 'product' && function_exists( 'wc_get_products' );
		$ckeyParts = [ 'latest', $postType, $limit, (int) $sinceTs, $excludeIds ];

		return [
			'post_type'    => $postType,
			'limit'        => $limit,
			'exclude_ids'  => $excludeIds,
			'since_ts'     => $sinceTs,
			'return_query' => (bool) $opts['return_query'],
			'cache_expire' => max( (int) $opts['cache_expire'], 0 ),
			'cache_key'    => 'qbt:latest:' . md5( wp_json_encode( $ckeyParts ) ),
			'is_product'   => $isProduct,
		];
	}

	// =============================================================
	// QUERY EXECUTORS (private)
	// =============================================================

	/**
	 * Execute terms-based query and return post IDs.
	 */
	private static function doTermsQuery( array $config ): array {
		$taxQuery = [
			[
				'taxonomy'         => $config['taxonomy'],
				'field'            => 'term_id',
				'terms'            => $config['term_ids'],
				'operator'         => 'IN',
				'include_children' => $config['include_children'],
			],
		];

		// WooCommerce products
		if ( $config['is_product'] && function_exists( 'wc_get_products' ) ) {
			$wcArgs = [
				'status'    => 'publish',
				'limit'     => $config['limit'],
				'return'    => 'ids',
				'orderby'   => $config['orderby'],
				'order'     => $config['order'],
				'tax_query' => $taxQuery,
				'exclude'   => $config['exclude_ids'],
			];

			if ( $config['hide_out_of_stock'] ) {
				$wcArgs['stock_status'] = 'instock';
			}

			$ids = \wc_get_products( $wcArgs );

			return is_array( $ids ) ? $ids : [];
		}

		// Standard WP_Query
		$query = new \WP_Query(
			[
				'post_type'           => $config['post_type'],
				'post_status'         => 'publish',
				'posts_per_page'      => $config['limit'],
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'tax_query'           => $taxQuery,
				'orderby'             => $config['orderby'],
				'order'               => $config['order'],
				'post__not_in'        => $config['exclude_ids'],
			]
		);

		return $query->posts ?: [];
	}

	// -------------------------------------------------------------

	/**
	 * Execute latest posts query and return post IDs.
	 */
	private static function doLatestQuery( array $config ): array {
		// WooCommerce products (without date filter)
		if ( $config['is_product'] && ! $config['since_ts'] && function_exists( 'wc_get_products' ) ) {
			$wcArgs = [
				'status'  => 'publish',
				'limit'   => $config['limit'],
				'return'  => 'ids',
				'orderby' => 'date',
				'order'   => 'DESC',
				'exclude' => $config['exclude_ids'],
			];

			if ( Helper::getOption( 'woocommerce_hide_out_of_stock_items' ) === 'yes' ) {
				$wcArgs['stock_status'] = 'instock';
			}

			$ids = \wc_get_products( $wcArgs );

			return is_array( $ids ) ? $ids : [];
		}

		// Standard WP_Query
		$args = [
			'post_type'           => $config['post_type'],
			'post_status'         => 'publish',
			'fields'              => 'ids',
			'posts_per_page'      => $config['limit'],
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'post__not_in'        => $config['exclude_ids'],
		];

		if ( $config['since_ts'] ) {
			$args['date_query'] = [
				[
					'after'     => [
						'year'  => (int) gmdate( 'Y', $config['since_ts'] ),
						'month' => (int) gmdate( 'n', $config['since_ts'] ),
						'day'   => (int) gmdate( 'j', $config['since_ts'] ),
					],
					'inclusive' => true,
				],
			];
		}

		$query = new \WP_Query( $args );

		return $query->posts ?: [];
	}

	// =============================================================
	// UTILITY METHODS (private)
	// =============================================================

	/**
	 * Execute query with optional caching.
	 */
	private static function executeWithCache( string $cacheKey, int $cacheExpire, bool $skipCache, callable $callback ): array {
		if ( $skipCache || $cacheExpire === 0 ) {
			return $callback();
		}

		return Cache::remember( $cacheKey, $callback, 'theme_queries', $cacheExpire );
	}

	// -------------------------------------------------------------

	/**
	 * Process query result - return IDs or WP_Query object.
	 *
	 * Both paths prime caches (posts, meta, terms, thumbnails) to prevent N+1 in templates.
	 */
	private static function processQueryResult( array $ids, string $postType, bool $returnQuery ): \WP_Query|array|false {
		if ( empty( $ids ) ) {
			return false;
		}

		if ( ! $returnQuery ) {
			self::primeLoopCaches( $ids );

			return $ids;
		}

		$query = new \WP_Query(
			[
				'post_type'           => $postType,
				'post_status'         => 'publish',
				'posts_per_page'      => count( $ids ),
				'post__in'            => $ids,
				'orderby'             => 'post__in',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			]
		);

		// WP_Query primes post/meta/terms — add thumbnail attachments
		update_post_thumbnail_cache( $query );

		return $query;
	}

	// -------------------------------------------------------------

	/**
	 * Bulk-prime post, meta, term, and thumbnail caches for an array of post IDs.
	 *
	 * Call this before looping over IDs to prevent N+1 queries in templates.
	 * Safe to call multiple times — WordPress skips already-cached objects.
	 *
	 * @param int[] $ids Post IDs to prime.
	 */
	public static function primeLoopCaches( array $ids ): void {
		if ( empty( $ids ) ) {
			return;
		}

		// Prime post objects + meta + terms in 3 bulk queries
		_prime_post_caches( $ids, true, true );

		// Prime thumbnail attachment caches (post objects + meta)
		$thumbIds = array_filter(
			array_map(
				static fn( $id ) => (int) get_post_meta( $id, '_thumbnail_id', true ),
				$ids
			)
		);

		if ( $thumbIds ) {
			_prime_post_caches( $thumbIds, false, true );
		}
	}

	// -------------------------------------------------------------

	/**
	 * Normalize term IDs from various input formats.
	 */
	private static function normalizeTermIds( mixed $terms ): array {
		if ( empty( $terms ) ) {
			return [];
		}

		if ( is_object( $terms ) && ! empty( $terms->term_id ) ) {
			return [ (int) $terms->term_id ];
		}

		// CSV string from shortcode attrs: "1,2,3"
		if ( is_string( $terms ) ) {
			$terms = array_map( 'intval', explode( ',', $terms ) );

			return array_values( array_unique( array_filter( $terms ) ) );
		}

		if ( is_array( $terms ) ) {
			return array_values( array_unique( array_filter( array_map( 'intval', $terms ) ) ) );
		}

		if ( is_numeric( $terms ) ) {
			return [ (int) $terms ];
		}

		return [];
	}

	// -------------------------------------------------------------

	/**
	 * Execute WP_Query and return based on returnQuery flag.
	 */
	private static function executeCachedQuery( array $args, bool $returnQuery ): \WP_Query|array|false {
		$query = new \WP_Query( $args );

		if ( $returnQuery ) {
			return $query;
		}

		return $query->posts ?: false;
	}

	// -------------------------------------------------------------

	/**
	 * Execute WP_Query and return only post IDs.
	 */
	private static function executeCachedQueryForIds( array $args ): array {
		$query = new \WP_Query( $args );

		return $query->posts ?: [];
	}

	// -------------------------------------------------------------

	/**
	 * Build optimized WP_Query from cached IDs.
	 */
	private static function buildCachedQueryResult( array $ids, string $postType, string $postStatus ): \WP_Query {
		return new \WP_Query(
			[
				'post_type'           => $postType,
				'post_status'         => $postStatus,
				'posts_per_page'      => count( $ids ),
				'post__in'            => $ids,
				'orderby'             => 'post__in',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			]
		);
	}
}
