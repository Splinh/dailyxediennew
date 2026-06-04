<?php
/**
 * WordPress Post, Term, Excerpt, and Template helpers.
 *
 * Provides static utility methods for retrieving and displaying
 * post/term data, excerpts, page templates, and cached term queries.
 *
 * @package SPL\Traits
 */

namespace SPL\Traits;

use SPL\Core\Cache;
use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

trait WpPost {

	// =============================================================
	// TERM HELPERS
	// =============================================================

	/**
	 * @param mixed $termId
	 * @param string $taxonomy
	 * @param string $output
	 *
	 * @return \WP_Term|\WP_Error|false|array|null
	 */
	public static function getTerm( mixed $termId, string $taxonomy = '', string $output = OBJECT ): \WP_Term|\WP_Error|false|array|null {
		if ( is_numeric( $termId ) ) {
			return get_term( (int) $termId, $taxonomy, $output );
		}

		return get_term_by( 'slug', $termId, $taxonomy, $output ) ?: get_term_by( 'name', $termId, $taxonomy, $output );
	}

	// -------------------------------------------------------------

	/**
	 * Get the primary hierarchical taxonomy for a post type.
	 *
	 * Built-in fast paths for core types; auto-detects for custom post types
	 * by scanning registered taxonomies (hierarchical + public first wins).
	 * Falls back to settings config if no taxonomy is auto-detected.
	 *
	 * @param string|null $postType
	 *
	 * @return string|null
	 */
	public static function getTaxonomyByPostType( ?string $postType ): ?string {
		if ( ! $postType ) {
			return null;
		}

		// Built-in fast paths
		if ( 'post' === $postType ) {
			return 'category';
		}

		if ( 'product' === $postType ) {
			return Helper::isWoocommerceActive() ? 'product_cat' : null;
		}

		// Auto-detect: prefer {cpt}_cat convention, then first hierarchical + public
		// @see _hd_detect_primary_taxonomy() in config/helpers.php for matching logic
		$taxonomies   = get_object_taxonomies( $postType, 'objects' );
		$conventional = $postType . '_cat';

		if ( isset( $taxonomies[ $conventional ] ) && $taxonomies[ $conventional ]->hierarchical && $taxonomies[ $conventional ]->public ) {
			return $conventional;
		}

		foreach ( $taxonomies as $tax ) {
			if ( $tax->hierarchical && $tax->public ) {
				return $tax->name;
			}
		}

		// Fallback to settings config
		return Helper::filterSettingOptions( 'post_type_terms' )[ $postType ] ?? null;
	}

	// -------------------------------------------------------------

	/**
	 * Retrieves the appropriate taxonomy for a given post.
	 *
	 * @param mixed $post Post object or ID.
	 * @param string|null $taxonomy Specific taxonomy to use.
	 *
	 * @return string|null The taxonomy name or null if no valid taxonomy found.
	 */
	public static function getTaxonomy( mixed $post, ?string $taxonomy = null ): ?string {
		$post = get_post( $post );
		if ( ! $post || is_wp_error( $post ) ) {
			return null;
		}

		$postType = get_post_type( $post );
		if ( ! $postType ) {
			return null;
		}

		if ( ! $taxonomy ) {
			$taxonomy = self::getTaxonomyByPostType( $postType ) ?: "{$postType}_cat";
		}

		return taxonomy_exists( $taxonomy ) ? $taxonomy : null;
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $post
	 * @param string|null $taxonomy
	 * @param string|null $wrapperOpen
	 * @param string|null $wrapperClose
	 *
	 * @return string|null
	 */
	public static function postTerms( mixed $post = null, ?string $taxonomy = '', ?string $wrapperOpen = '<div class="terms-links links flex items-center flex-wrap gap-3">', ?string $wrapperClose = '</div>' ): ?string {
		$post = get_post( $post );
		if ( ! $post || is_wp_error( $post ) ) {
			return null;
		}

		$taxonomy = self::getTaxonomy( $post, $taxonomy );
		if ( ! $taxonomy ) {
			return null;
		}

		$postTerms = get_the_terms( $post, $taxonomy );
		if ( ! is_array( $postTerms ) || ! $postTerms || is_wp_error( $postTerms ) ) {
			return null;
		}

		$link = '';
		foreach ( $postTerms as $term ) {
			if ( $term->slug ) {
				$link .= '<a href="' . esc_url( get_term_link( $term ) ) . '" title="' . esc_attr( $term->name ) . '">' . esc_html( $term->name ) . '</a>';
			}
		}

		if ( $wrapperOpen && $wrapperClose ) {
			$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"><path d="M6 5a2 2 0 0 1 2-2h4.157a2 2 0 0 1 1.656.879L15.249 6H19a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2v-5a3 3 0 0 0-3-3h-3.22l-1.14-1.682A3 3 0 0 0 9.157 6H6z"/><path d="M3 9a2 2 0 0 1 2-2h4.157a2 2 0 0 1 1.656.879L12.249 10H3zm0 3v7a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-7z"/></g></svg>';
			$svg .= '<span class="sr-only">' . esc_html__( 'Danh mục', 'SPL' ) . '</span>';
			$link = $wrapperOpen . $svg . $link . $wrapperClose;
		}

		return $link;
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $post
	 * @param string|null $taxonomy
	 *
	 * @return \WP_Term|null
	 */
	public static function primaryTerm( mixed $post, ?string $taxonomy = '' ): ?\WP_Term {
		$post = get_post( $post );
		if ( ! $post || is_wp_error( $post ) ) {
			return null;
		}

		$taxonomy = self::getTaxonomy( $post, $taxonomy );
		if ( ! $taxonomy ) {
			return null;
		}

		$postTerms = get_the_terms( $post, $taxonomy );
		if ( ! is_array( $postTerms ) || ! $postTerms || is_wp_error( $postTerms ) ) {
			return null;
		}

		$termIds = wp_list_pluck( $postTerms, 'term_id' );

		// Support for Rank Math SEO plugin
		$primaryTermId = (int) get_post_meta( $post->ID, 'rank_math_primary_' . $taxonomy, true );
		if ( $primaryTermId && in_array( $primaryTermId, $termIds, true ) ) {
			$term = get_term( $primaryTermId, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}

		// Support for Yoast SEO plugin
		if ( class_exists( 'WPSEO_Primary_Term' ) ) {
			try {
				$yoastPrimaryTerm = new \WPSEO_Primary_Term( $taxonomy, $post );
				if ( method_exists( $yoastPrimaryTerm, 'get_primary_term' ) ) {
					$primaryTermId = (int) $yoastPrimaryTerm->get_primary_term();
					if ( $primaryTermId && in_array( $primaryTermId, $termIds, true ) ) {
						$term = get_term( $primaryTermId, $taxonomy );
						if ( $term && ! is_wp_error( $term ) ) {
							return $term;
						}
					}
				}
			} catch ( \Throwable $e ) {
				Helper::errorLog( 'Error getting Yoast primary term: ' . $e->getMessage() );
			}
		}

		// Support for All-in-one SEO plugin
		if ( function_exists( 'aioseo' ) ) {
			$aioseoId = (int) get_post_meta( $post->ID, '_aioseo_primary_' . $taxonomy, true );
			if ( $aioseoId && in_array( $aioseoId, $termIds, true ) ) {
				$term = get_term( $aioseoId, $taxonomy );
				if ( $term && ! is_wp_error( $term ) ) {
					return $term;
				}
			}
		}

		return $postTerms[0] ?? null;
	}

	// -------------------------------------------------------------

	/**
	 * Get the primary term link HTML.
	 *
	 * @param array $options Options for rendering primary term.
	 *
	 * @return string|null The HTML output or null if no term found.
	 */
	public static function getPrimaryTerm( array $options = [] ): ?string {
		$defaults = [
			'post'          => null,
			'taxonomy'      => '',
			'class'         => '',
			'extra_content' => null,
			'wrapper_open'  => '<div class="terms">',
			'wrapper_close' => '</div>',
		];

		$opts = wp_parse_args( $options, $defaults );

		$term = self::primaryTerm( $opts['post'], $opts['taxonomy'] );
		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		$classAttr    = $opts['class'] ? ' class="' . esc_attr( $opts['class'] ) . '"' : '';
		$extraContent = $opts['extra_content'] ?? '';
		$link         = '<a' . $classAttr . ' href="' . esc_url( get_term_link( $term, $opts['taxonomy'] ) ) . '" title="' . esc_attr( $term->name ) . '">' . esc_html( $term->name ) . $extraContent . '</a>';

		return ( $opts['wrapper_open'] && $opts['wrapper_close'] )
			? $opts['wrapper_open'] . $link . $opts['wrapper_close']
			: $link;
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $taxonomy
	 * @param int $id
	 * @param string $sep
	 *
	 * @return void
	 */
	public static function hashTags( ?string $taxonomy = 'post_tag', int $id = 0, string $sep = '' ): void {
		$taxonomy    = $taxonomy ?: 'post_tag';
		$hashtagList = get_the_term_list( $id, $taxonomy, '', $sep );

		if ( ! $hashtagList || is_wp_error( $hashtagList ) ) {
			return;
		}

		printf(
			'<div class="hashtag-links links flex items-center flex-wrap gap-3">%1$s<span class="sr-only">%2$s</span>%3$s</div>',
			'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M18.045 3.007L12.31 3a1.97 1.97 0 0 0-1.4.585l-7.33 7.394a2 2 0 0 0 0 2.805l6.573 6.631a1.96 1.96 0 0 0 1.4.585a1.97 1.97 0 0 0 1.4-.585l7.409-7.477A2 2 0 0 0 21 11.479v-5.5a2.97 2.97 0 0 0-2.955-2.972m-2.452 6.438a1 1 0 1 1 0-2a1 1 0 0 1 0 2"/></svg>',
			esc_html__( 'Từ khóa', 'SPL' ),
			$hashtagList
		);
	}

	// -------------------------------------------------------------

	/**
	 * Get child terms of a parent term with caching.
	 *
	 * @param mixed  $termId   Parent term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @param bool   $hideEmpty Whether to hide empty terms.
	 *
	 * @return \WP_Term[]|null
	 */
	public static function childTerms( mixed $termId, string $taxonomy, bool $hideEmpty = true ): ?array {
		if ( ! $termId || ! taxonomy_exists( $taxonomy ) ) {
			return null;
		}

		$terms = self::cachedChildTerms( (int) $termId, $taxonomy, $hideEmpty );

		return $terms ?: null;
	}

	// -------------------------------------------------------------

	/**
	 * Get hierarchical terms with caching.
	 *
	 * @param string|null $taxonomy        Taxonomy name.
	 * @param bool        $hideEmpty       Whether to hide empty terms.
	 * @param mixed       $parentId        Parent term ID.
	 * @param mixed|null  $selectedRequest Selected term(s).
	 * @param int|null    $disabledParent  Parent ID to disable children.
	 * @param bool        $onlyParent      Only return parent terms.
	 *
	 * @return array|null
	 */
	public static function hierarchyTerms(
		?string $taxonomy,
		bool $hideEmpty = true,
		mixed $parentId = null,
		mixed $selectedRequest = null,
		?int $disabledParent = null,
		bool $onlyParent = false
	): ?array {
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return null;
		}

		$parent   = $parentId !== null ? (int) $parentId : 0;
		$cacheKey = "hierarchy_terms_{$taxonomy}_{$parent}_" . (int) $hideEmpty;

		$terms = Cache::remember(
			$cacheKey,
			static fn() => self::doHierarchyQuery( $taxonomy, $hideEmpty, $parent ),
			'theme_taxonomies',
			HOUR_IN_SECONDS
		);

		if ( empty( $terms ) ) {
			return null;
		}

		$options = [];
		foreach ( $terms as $term ) {
			$options = [ ...$options, ...self::buildTreeTerms( $term, $hideEmpty, 0, $selectedRequest, $disabledParent, $onlyParent ) ];
		}

		return $options;
	}

	// -------------------------------------------------------------

	/**
	 * Execute hierarchy terms query.
	 */
	private static function doHierarchyQuery( string $taxonomy, bool $hideEmpty, int $parentId ): array {
		$terms = get_terms(
			[
				'taxonomy'     => $taxonomy,
				'hide_empty'   => $hideEmpty,
				'hierarchical' => true,
				'parent'       => $parentId,
			]
		);

		return ( empty( $terms ) || is_wp_error( $terms ) ) ? [] : $terms;
	}

	// -------------------------------------------------------------

	/**
	 * Build tree terms recursively (uses cached child terms).
	 */
	private static function buildTreeTerms(
		mixed $term,
		bool $hideEmpty = true,
		int $depth = 0,
		mixed $selectedRequest = null,
		?int $disabledParent = null,
		bool $onlyParent = false
	): array {
		if ( ! $term?->term_id ) {
			return [];
		}

		$prefix = str_repeat( '— ', $depth );

		$selected = is_array( $selectedRequest )
			? in_array( $term->term_id, $selectedRequest, true )
			: selected( $selectedRequest, $term->term_id, false ) !== '';

		$disabled = isset( $disabledParent ) && $term->parent === $disabledParent;

		$options = [
			[
				'value'    => $term->term_id,
				'label'    => $prefix . $term->name,
				'selected' => $selected,
				'disabled' => $disabled,
			],
		];

		if ( $onlyParent ) {
			return $options;
		}

		$childTerms = self::childTerms( $term->term_id, $term->taxonomy, $hideEmpty );

		if ( ! empty( $childTerms ) ) {
			foreach ( $childTerms as $childTerm ) {
				$options = [ ...$options, ...self::buildTreeTerms( $childTerm, $hideEmpty, $depth + 1, $selectedRequest, $disabledParent ) ];
			}
		}

		return $options;
	}

	// =============================================================
	// EXCERPT HELPERS
	// =============================================================

	/**
	 * @param mixed|null $post
	 * @param string|null $cssClass
	 * @param string|null $defaultTag
	 *
	 * @return string|null
	 */
	public static function loopExcerpt( mixed $post = null, ?string $cssClass = 'excerpt', ?string $defaultTag = 'p' ): ?string {
		$excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
		if ( ! trim( $excerpt ) ) {
			return null;
		}

		if ( ! $cssClass ) {
			return esc_html( $excerpt );
		}

		$tag = tag_escape( $defaultTag ?? 'p' );

		return '<' . $tag . ' class="' . esc_attr( $cssClass ) . '">' . esc_html( $excerpt ) . '</' . $tag . '>';
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed|null $post
	 * @param string|null $cssClass
	 * @param string|null $defaultTag
	 *
	 * @return string|null
	 */
	public static function postExcerpt( mixed $post = null, ?string $cssClass = 'excerpt', ?string $defaultTag = 'p' ): ?string {
		$post = get_post( $post );
		if ( ! $post || ! Helper::stripSpace( $post->post_excerpt ) ) {
			return null;
		}

		if ( ! $cssClass ) {
			return wp_kses_post( $post->post_excerpt );
		}

		$tag = tag_escape( $defaultTag ?? 'p' );

		return '<' . $tag . ' class="' . esc_attr( $cssClass ) . '">' . wp_kses_post( $post->post_excerpt ) . '</' . $tag . '>';
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $term
	 * @param string|null $cssClass
	 * @param string|null $defaultTag
	 * @param bool $stripTags
	 *
	 * @return string|null
	 */
	public static function termExcerpt(
		mixed $term = 0,
		?string $cssClass = 'term-excerpt',
		?string $defaultTag = 'div',
		bool $stripTags = false,
	): ?string {
		$description = term_description( (int) ( $term ?: 0 ) );
		if ( ! Helper::stripSpace( $description ) ) {
			return null;
		}

		if ( $stripTags ) {
			$description = wp_strip_all_tags( $description );
		}

		if ( ! $cssClass ) {
			return wp_kses_post( $description );
		}

		$tag = tag_escape( $defaultTag ?? 'div' );

		return '<' . $tag . ' class="' . esc_attr( $cssClass ) . '">' . wp_kses_post( $description ) . '</' . $tag . '>';
	}

	// =============================================================
	// PAGE TEMPLATE HELPERS
	// =============================================================

	/**
	 * Check if current page is home, front page, or a specific template.
	 *
	 * @param string $homeTemplate Template file path or regex pattern.
	 *
	 * @return bool
	 */
	public static function isHomeOrFrontPage( string $homeTemplate = '' ): bool {
		if ( is_home() || is_front_page() ) {
			return true;
		}

		$homeTemplate = $homeTemplate ?: 'templates/template-page-home.php';

		return Helper::isPageTemplate( $homeTemplate );
	}

	// -------------------------------------------------------------

	/**
	 * Get page by template name with caching.
	 *
	 * @param string $template Template file path.
	 *
	 * @return \WP_Post|null
	 */
	public static function getPageTemplate( string $template ): ?\WP_Post {
		$cacheKey = 'page_template_' . md5( $template );

		return Cache::remember(
			$cacheKey,
			static fn() => self::doPageTemplateQuery( $template ),
			'theme_posts',
			DAY_IN_SECONDS
		);
	}

	// -------------------------------------------------------------

	/**
	 * Execute page template query.
	 */
	private static function doPageTemplateQuery( string $template ): ?\WP_Post {
		$query = new \WP_Query(
			[
				'post_type'      => 'page',
				'posts_per_page' => 1,
				'meta_query'     => [
					[
						'key'   => '_wp_page_template',
						'value' => $template,
					],
				],
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'fields'         => 'ids',
			]
		);

		$postId = $query->posts[0] ?? null;

		return $postId ? get_post( $postId ) : null;
	}

	// -------------------------------------------------------------

	/**
	 * @param string $template
	 *
	 * @return string|null
	 */
	public static function getPageLinkTemplate( string $template ): ?string {
		$post = self::getPageTemplate( $template );
		if ( ! $post ) {
			return null;
		}

		return get_permalink( $post ) ?: null;
	}

	// =============================================================
	// CACHED TERMS
	// =============================================================

	/**
	 * Get cached terms.
	 *
	 * Use this instead of get_terms() for better performance.
	 * Cache is automatically invalidated when terms are updated.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param array  $args     Optional get_terms arguments.
	 * @param int    $ttl      Cache TTL in seconds. Default 1 hour.
	 *
	 * @return \WP_Term[]|array
	 */
	public static function cachedTerms( string $taxonomy, array $args = [], int $ttl = HOUR_IN_SECONDS ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		$args['taxonomy'] = $taxonomy;

		$cacheKey = 'terms_' . md5( wp_json_encode( $args ) );

		return Cache::remember(
			$cacheKey,
			static fn() => self::doCachedTermsQuery( $args ),
			'theme_taxonomies',
			$ttl
		);
	}

	// -------------------------------------------------------------

	/**
	 * Get cached child terms of a parent term.
	 *
	 * @param int|string $parentId  Parent term ID.
	 * @param string     $taxonomy  Taxonomy name.
	 * @param bool       $hideEmpty Whether to hide empty terms.
	 * @param int        $ttl       Cache TTL in seconds.
	 *
	 * @return \WP_Term[]|array
	 */
	public static function cachedChildTerms( int|string $parentId, string $taxonomy, bool $hideEmpty = true, int $ttl = HOUR_IN_SECONDS ): array {
		return self::cachedTerms(
			$taxonomy,
			[
				'parent'     => (int) $parentId,
				'hide_empty' => $hideEmpty,
			],
			$ttl
		);
	}

	// -------------------------------------------------------------

	/**
	 * Execute get_terms query.
	 */
	private static function doCachedTermsQuery( array $args ): array {
		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		return $terms;
	}
}
