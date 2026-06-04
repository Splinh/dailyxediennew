<?php
/**
 * Post Type Archive - Assign static pages as archive pages for custom post types.
 *
 * @package HDAddons\Modules\PostTypeArchive
 */

namespace HDAddons\Modules\PostTypeArchive;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class PostTypeArchiveModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'post_type_archive';
	}

	public static function title(): string {
		return 'Post Type Archive';
	}

	public static function description(): string {
		return 'Assign a page as CPT archive.';
	}

	public static function group(): string {
		return 'tools';
	}


	// ── Constants ───────────────────────────────────

	public const KEY_PTA_PAGES = 'pta_pages';

	/**
	 * Cached settings: post_type => page_id mapping.
	 *
	 * @var array<string, int>
	 */
	private array $archivePages;

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		$this->archivePages = self::getArchivePages();

		if ( empty( $this->archivePages ) ) {
			return;
		}

		add_action( 'init', $this->addRewriteRules( ... ) );
		add_action( 'pre_get_posts', $this->handlePageAsArchive( ... ) );

		if ( is_admin() ) {
			add_filter( 'display_post_states', $this->addArchivePageState( ... ), 10, 2 );
		}
	}

	// ── Options ─────────────────────────────────────

	/**
	 * Get archive page assignments.
	 *
	 * @return array<string, int> Post type slug => page ID mapping.
	 */
	public static function getArchivePages(): array {
		$options = Helper::getOption( self::optionKey(), [] );

		if ( ! is_array( $options ) || empty( $options[ self::KEY_PTA_PAGES ] ) ) {
			return [];
		}

		$pages = [];
		foreach ( $options[ self::KEY_PTA_PAGES ] as $postType => $pageId ) {
			$pageId = absint( $pageId );
			if ( $pageId > 0 ) {
				$pages[ sanitize_key( $postType ) ] = $pageId;
			}
		}

		return $pages;
	}

	// ── Eligible Post Types ─────────────────────────

	/**
	 * Get post types that can have an archive page assigned.
	 *
	 * @return array<string, \WP_Post_Type>
	 */
	public static function getEligiblePostTypes(): array {
		$postTypes = get_post_types(
			[
				'public'   => true,
				'_builtin' => false,
			],
			'objects'
		);

		$wooExclude = [ 'product', 'shop_order', 'shop_coupon', 'shop_order_refund' ];

		$eligible = [];
		foreach ( $postTypes as $slug => $postType ) {
			if ( in_array( $slug, $wooExclude, true ) ) {
				continue;
			}

			if ( empty( $postType->has_archive ) ) {
				$eligible[ $slug ] = $postType;
			}
		}

		return $eligible;
	}

	// ── Rewrite Rules ───────────────────────────────

	public function addRewriteRules(): void {
		foreach ( $this->archivePages as $postType => $pageId ) {
			$page = get_post( $pageId );
			if ( ! $page || 'publish' !== $page->post_status ) {
				continue;
			}

			$pageSlug = $page->post_name;

			$ancestors = get_post_ancestors( $pageId );
			if ( ! empty( $ancestors ) ) {
				$slugParts = [];
				foreach ( array_reverse( $ancestors ) as $ancestorId ) {
					$ancestor = get_post( $ancestorId );
					if ( $ancestor ) {
						$slugParts[] = $ancestor->post_name;
					}
				}
				$slugParts[] = $pageSlug;
				$pageSlug    = implode( '/', $slugParts );
			}

			add_rewrite_rule(
				'^' . preg_quote( $pageSlug, '/' ) . '/page/([0-9]+)/?',
				'index.php?pagename=' . $pageSlug . '&paged=$matches[1]',
				'top'
			);
		}
	}

	// ── Query Handling ──────────────────────────────

	public function handlePageAsArchive( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		foreach ( $this->archivePages as $postType => $pageId ) {
			$obj = get_post_type_object( $postType );
			if ( ! $obj ) {
				continue;
			}

			if ( ! empty( $obj->has_archive ) ) {
				continue;
			}

			if ( ! $query->is_page( $pageId ) ) {
				continue;
			}

			$query->set( 'post_type', $postType );
			$query->set( 'posts_per_page', Helper::getOption( 'posts_per_page' ) );
			$query->set( 'paged', max( 1, (int) $query->get( 'paged' ) ) );
			$query->set( 'post_status', 'publish' );
			$query->set( 'pagename', '' );

			$query->is_page              = false;
			$query->is_archive           = true;
			$query->is_post_type_archive = true;
			$query->is_home              = false;
			$query->is_singular          = false;

			break;
		}
	}

	// ── Admin UI ────────────────────────────────────

	public function addArchivePageState( array $postStates, \WP_Post $post ): array {
		if ( 'page' !== get_post_type( $post ) ) {
			return $postStates;
		}

		foreach ( $this->archivePages as $postType => $pageId ) {
			if ( $pageId !== $post->ID ) {
				continue;
			}

			$obj = get_post_type_object( $postType );
			if ( ! $obj ) {
				continue;
			}

			$label = sprintf(
				/* translators: %s: post type singular name */
				__( 'Archive Page (%s)', 'hda' ),
				$obj->labels->singular_name ?? ucfirst( $postType )
			);

			$postStates[ 'page_archive_' . $postType ] = esc_html( $label );
		}

		return $postStates;
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		$ptaPages = [];

		if ( ! empty( $data['pta_pages'] ) ) {
			foreach ( (array) $data['pta_pages'] as $postType => $pageId ) {
				$postType = sanitize_key( $postType );
				$pageId   = absint( $pageId );

				if ( $pageId > 0 && get_post_type( $pageId ) === 'page' && get_post_status( $pageId ) === 'publish' ) {
					$ptaPages[ $postType ] = $pageId;
				}
			}
		}

		$options = [ self::KEY_PTA_PAGES => $ptaPages ];
		self::saveOrRemove( self::optionKey(), $options, true );

		flush_rewrite_rules();
	}
}
