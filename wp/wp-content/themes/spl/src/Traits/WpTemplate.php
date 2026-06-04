<?php
/**
 * Template display utility methods.
 *
 * Contains breadcrumbs, pagination, and block template helpers.
 * Split from WpMisc trait for better separation of concerns.
 *
 * @author HD
 */

namespace SPL\Traits;

defined( 'ABSPATH' ) || exit;

trait WpTemplate {
	/**
	 * Get the regex pattern to match breadcrumb anchors.
	 *
	 * @return string
	 */
	private static function getBreadcrumbAnchorPattern(): string {
		return '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/u';
	}

	// -------------------------------------------------------------

	/**
	 * @param string $slug
	 * @param array $args
	 * @param bool $useCache
	 * @param int $cacheInHours
	 *
	 * @return void
	 */
	public static function blockTemplate( string $slug, array $args = [], bool $useCache = true, int $cacheInHours = 6 ): void {
		$blockSlug = trim( str_replace( [ '/', '-' ], '_', $slug ), '_' );
		do_action( 'enqueue_assets_blocks_' . $blockSlug );

		// Disable cache inside The Loop or for logged-in users (nonces, user-specific data).
		if ( in_the_loop() || is_user_logged_in() ) {
			$useCache = false;
		}

		if ( ! $useCache ) {
			get_template_part( $slug, null, $args );

			return;
		}

		$cacheKey     = 'hd_block_cache_' . md5( $slug . wp_json_encode( $args ) );
		$cachedOutput = wp_cache_get( $cacheKey, 'hd_block_cache' );

		if ( $cachedOutput !== false && is_string( $cachedOutput ) && $cachedOutput !== '' ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached output from get_template_part().
			echo $cachedOutput;

			return;
		}

		ob_start();
		get_template_part( $slug, null, $args );
		$output = ob_get_clean();

		// Cache blocks up to 256KB — large enough for most templates,
		// prevents caching extremely large pages that waste cache memory.
		if ( $output && strlen( $output ) <= 262144 ) {
			wp_cache_set( $cacheKey, $output, 'hd_block_cache', $cacheInHours * HOUR_IN_SECONDS );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output from get_template_part().
		echo $output;
	}

	// -------------------------------------------------------------

	/**
	 * @return void
	 */
	public static function breadCrumbs(): void {
		global $post, $wp_query;

		if ( is_front_page() ) {
			return;
		}

		$before      = '<li class="current">';
		$after       = '</li>';
		$breadcrumbs = [];

		// Home
		$breadcrumbs[] = '<li><a class="home" href="' . esc_url( self::home() ) . '">' . esc_html__( 'Trang chủ', 'SPL' ) . '</a></li>';

		// WooCommerce Shop Page
		if ( function_exists( 'is_shop' ) && \is_shop() && self::isWoocommerceActive() ) {
			$breadcrumbs[] = $before . esc_html( get_the_title( self::getOption( 'woocommerce_shop_page_id' ) ) ) . $after;
		} elseif ( $wp_query?->is_posts_page ) {
			$breadcrumbs[] = $before . esc_html( get_the_title( self::getOption( 'page_for_posts', true ) ) ) . $after;
		} elseif ( $wp_query?->is_post_type_archive ) {
			$breadcrumbs[] = $before . esc_html( post_type_archive_title( '', false ) ) . $after;
		} elseif ( is_page() || is_attachment() ) {
			if ( $post?->post_parent ) {
				$parentId          = $post->post_parent;
				$parentBreadcrumbs = [];

				while ( $parentId ) {
					$page = get_post( $parentId );
					if ( ! $page || count( $parentBreadcrumbs ) > 10 ) {
						break;
					}

					$parentBreadcrumbs[] = '<li><a href="' . esc_url( get_permalink( $page->ID ) ) . '">' . esc_html( get_the_title( $page->ID ) ) . '</a></li>';
					$parentId            = $page->post_parent;
				}

				$breadcrumbs = [ ...$breadcrumbs, ...array_reverse( $parentBreadcrumbs ) ];
			}
			$breadcrumbs[] = $before . esc_html( get_the_title() ) . $after;
		} elseif ( is_single() && ! is_attachment() ) {
			$postType = get_post_type_object( get_post_type() );
			if ( $postType ) {
				$taxonomies = get_object_taxonomies( $postType->name );

				if ( ! $taxonomies ) {
					$archiveLink = get_post_type_archive_link( $postType->name );
					if ( $archiveLink ) {
						$breadcrumbs[] = '<li><a href="' . esc_url( $archiveLink ) . '">' . esc_html( $postType->labels->singular_name ) . '</a></li>';
					}
				} else {
					$term = \SPL_Query::primaryTerm( $post );
					if ( $term ) {
						$catCode = get_term_parents_list( $term->term_id, $term->taxonomy, [ 'separator' => '' ] );
						if ( $catCode && ! is_wp_error( $catCode ) ) {
							preg_match_all( self::getBreadcrumbAnchorPattern(), $catCode, $catMatches, PREG_SET_ORDER );
							foreach ( $catMatches as $catMatch ) {
								$breadcrumbs[] = '<li><a href="' . esc_url( $catMatch[1] ) . '">' . esc_html( wp_strip_all_tags( $catMatch[2] ) ) . '</a></li>';
							}
						}
					}
				}
			}

			$before        = '<li class="current current-title">';
			$breadcrumbs[] = $before . esc_html( get_the_title() ) . $after;
		} elseif ( is_search() ) {
			$breadcrumbs[] = $before . sprintf( esc_html__( 'Kết quả tìm kiếm cho: %s', 'SPL' ), get_search_query() ) . $after;
		} elseif ( is_tag() ) {
			$breadcrumbs[] = $before . sprintf( esc_html__( 'Lưu trữ: %s', 'SPL' ), esc_html( single_tag_title( '', false ) ) ) . $after;
		} elseif ( is_author() ) {
			global $author;
			$userdata      = get_userdata( $author );
			$breadcrumbs[] = $before . esc_html( $userdata?->display_name ) . $after;
		} elseif ( is_day() || is_month() || is_year() ) {
			if ( is_day() ) {
				$breadcrumbs[] = '<li><a href="' . esc_url( get_year_link( get_the_time( 'Y' ) ) ) . '">' . esc_html( get_the_time( 'Y' ) ) . '</a></li>';
				$breadcrumbs[] = '<li><a href="' . esc_url( get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) ) . '">' . esc_html( get_the_time( 'F' ) ) . '</a></li>';
				$breadcrumbs[] = $before . esc_html( get_the_time( 'd' ) ) . $after;
			} elseif ( is_month() ) {
				$breadcrumbs[] = '<li><a href="' . esc_url( get_year_link( get_the_time( 'Y' ) ) ) . '">' . esc_html( get_the_time( 'Y' ) ) . '</a></li>';
				$breadcrumbs[] = $before . esc_html( get_the_time( 'F' ) ) . $after;
			} elseif ( is_year() ) {
				$breadcrumbs[] = $before . esc_html( get_the_time( 'Y' ) ) . $after;
			}
		} elseif ( is_category() || is_tax() ) {
			$catObj = get_queried_object();

			if ( $catObj && $catObj->parent ) {
				$catCode = get_term_parents_list(
					$catObj->term_id,
					$catObj->taxonomy,
					[
						'separator' => '',
						'inclusive' => false,
					]
				);
				if ( $catCode && ! is_wp_error( $catCode ) ) {
					preg_match_all( self::getBreadcrumbAnchorPattern(), $catCode, $catMatches, PREG_SET_ORDER );
					foreach ( $catMatches as $catMatch ) {
						$breadcrumbs[] = '<li><a href="' . esc_url( $catMatch[1] ) . '">' . esc_html( wp_strip_all_tags( $catMatch[2] ) ) . '</a></li>';
					}
				}
			}

			$breadcrumbs[] = $before . esc_html( single_cat_title( '', false ) ) . $after;
		} elseif ( is_404() ) {
			$breadcrumbs[] = $before . esc_html__( 'Không tìm thấy', 'SPL' ) . $after;
		}

		// Pagination
		if ( get_query_var( 'paged' ) && (int) get_query_var( 'paged' ) > 1 ) {
			$breadcrumbs[] = $before . ' (' . esc_html__( 'trang', 'SPL' ) . ' ' . absint( get_query_var( 'paged' ) ) . ')' . $after;
		}

		// Display Breadcrumbs
		echo '<ul id="breadcrumbs" class="breadcrumbs flex flex-row flex-wrap space-x-4" aria-label="Breadcrumbs">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each crumb is built with esc_url/esc_html above.
		echo implode( '', $breadcrumbs );
		echo '</ul>';

		// Breadcrumb Schema
		$schemaItems = [];
		$position    = 1;

		foreach ( $breadcrumbs as $crumbHtml ) {
			if ( preg_match( self::getBreadcrumbAnchorPattern(), $crumbHtml, $matches ) ) {
				$schemaItems[] = [
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => wp_strip_all_tags( $matches[2] ),
					'item'     => esc_url( $matches[1] ),
				];
			} else {
				$schemaItems[] = [
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => wp_strip_all_tags( $crumbHtml ),
				];
			}
		}

		$schema = [
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $schemaItems,
		];

		wp_print_inline_script_tag(
			wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			[ 'type' => 'application/ld+json' ]
		);
	}

	// -------------------------------------------------------------

	/**
	 * @param \WP_Query|null $query
	 * @param bool $get
	 *
	 * @return void
	 */
	public static function paginateLinks( ?\WP_Query $query = null, bool $get = false ): void {
		global $wp_query;

		$query = $query ?: $wp_query;
		if ( ! $query || $query->max_num_pages <= 1 ) {
			return;
		}

		$pagenumLink = html_entity_decode( get_pagenum_link() );
		$urlParts    = explode( '?', $pagenumLink, 2 );
		$pagenumLink = trailingslashit( $urlParts[0] ) . '%_%';

		$current = max( 1, get_query_var( 'paged' ) );
		$base    = $get ? add_query_arg( 'page', '%#%' ) : $pagenumLink;

		if ( $get && ! empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current = absint( $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$paginateLinks = paginate_links(
			[
				'base'      => $base,
				'current'   => $current,
				'total'     => $query->max_num_pages,
				'end_size'  => 1,
				'mid_size'  => 2,
				'prev_next' => true,
				'prev_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>',
				'next_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>',
				'type'      => 'list',
			]
		);

		$paginateLinks = str_replace(
			[
				"<ul class='page-numbers'>",
				'<li><span class="page-numbers dots">&hellip;</span></li>',
				'<li><span aria-current="page" class="page-numbers current">',
				'<li>',
			],
			[
				'<ul class="pagination page-numbers u-flex-center flex-row flex-wrap space-x-2 mt-6 lg:mt-8">',
				'<li class="size-9"><span class="page-numbers dots ellipsis"><svg aria-hidden="true"><use href="#icon-dots-horizontal-outline"></use></svg></span></li>',
				'<li class="size-9"><span aria-current="page" class="sr-only">You\'re on page </span><span aria-current="page" class="page-numbers current">',
				'<li class="size-9">',
			],
			$paginateLinks
		);

		$paginateLinks = preg_replace( [ '/\bpage-numbers\b\s*/', '/\s*class=""/' ], '', $paginateLinks ) ?? '';

		if ( $paginateLinks ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output from paginate_links() (WP core).
			echo '<nav class="nav-pagination" aria-label="Pagination">' . $paginateLinks . '</nav>';
		}
	}
}
