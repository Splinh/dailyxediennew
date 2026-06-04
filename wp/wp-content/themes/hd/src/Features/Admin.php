<?php
/**
 * Theme Admin Customizations
 *
 * Handles all admin-side hooks and customizations:
 * custom columns, admin menus, styles, and UI enhancements.
 *
 * @package HD\Features
 * @author  HD
 */

namespace HD\Features;

use HD\Contracts\Feature;
use HD\Contracts\HasAdminContext;
use HD\Core\Asset;
use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class Admin extends Feature implements HasAdminContext {

	/* ---------- Feature ---------------------------------------- */

	public function boot(): void {
		$cb = static function (): void {
			if ( ! is_admin_bar_showing() ) {
				return;
			}

			wp_add_inline_style(
				'admin-bar',
				'
				.custom-admin-button{display:flex;align-items:center;gap:6px}
				.custom-admin-button .custom-icon{flex:0 0 auto}
				.custom-admin-button .custom-icon img{display:block;width:auto!important;height:15px!important}
				.custom-admin-button .custom-text{flex:1 1 0}
			'
			);
		};

		add_action( 'wp_enqueue_scripts', $cb );
		add_action( 'admin_enqueue_scripts', $cb );
	}

	/* ---------- HasAdminContext ---------------------------------- */

	public function adminBoot(): void {
		add_action( 'admin_menu', $this->adminMenu( ... ) );
		add_action( 'admin_init', $this->adminInit( ... ), 11 );
		add_action( 'admin_enqueue_scripts', $this->adminEnqueueScripts( ... ), 30 );
		add_action( 'enqueue_block_editor_assets', $this->blockEditorAssets( ... ) );

		/** Show a clear cache message */
		add_action( 'admin_notices', $this->adminNotices( ... ), 11 );
	}

	/* ---------- PUBLIC ------------------------------------------- */

	public function adminMenu(): void {
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );

		$settings = Helper::filterSettingOptions( 'admin_menu' );

		$hideMenu    = (array) ( $settings['admin_hide_menu'] ?? [] );
		$hideSubmenu = (array) ( $settings['admin_hide_submenu'] ?? [] );
		$ignoreUsers = array_map( 'intval', (array) ( $settings['admin_hide_menu_ignore_user'] ?? [] ) );

		$userId    = get_current_user_id();
		$isIgnored = in_array( $userId, $ignoreUsers, true );

		if ( ! $isIgnored ) {
			// admin menu
			foreach ( $hideMenu as $slug ) {
				$slug && remove_menu_page( $slug );
			}

			// admin submenu
			foreach ( $hideSubmenu as $menuSlug => $subItems ) {
				foreach ( (array) $subItems as $item ) {
					$item && remove_submenu_page( $menuSlug, $item );
				}
			}
		}

		// Other settings
		$removeMenuSetting = Helper::getThemeMod( 'remove_menu_setting' );
		foreach ( explode( "\n", (string) $removeMenuSetting ) as $slug ) {
			$slug && remove_menu_page( $slug );
		}
	}

	// --------------------------------------------------

	public function adminInit(): void {
		// editor-style for Classic Editor
		add_editor_style( Asset::src( 'editor-style.scss', true ) );

		$settings = Helper::filterSettingOptions( 'admin_list_table' );

		// Auto-detect: WP fires `{taxonomy}_row_actions` per taxonomy
		$termRowActions = array_unique(
			array_merge(
				(array) ( $settings['term_row_actions'] ?? [] ),
				array_values(
					get_taxonomies(
						[
							'public'  => true,
							'show_ui' => true,
						]
					)
				)
			)
		);

		// WP core only fires 'post_row_actions' (non-hierarchical),
		// 'page_row_actions' (hierarchical), and 'user_row_actions'.
		// Per-CPT filters (e.g., product_row_actions) don't exist.
		$postRowActions = [ 'user', 'post', 'page' ];

		$termThumbColumns    = (array) ( $settings['term_thumb_columns'] ?? [] );
		$excludeThumbColumns = (array) ( $settings['post_type_exclude_thumb_columns'] ?? [] );

		// https://wordpress.stackexchange.com/questions/77532/how-to-add-the-category-id-to-admin-page
		foreach ( $termRowActions as $term ) {
			add_filter( "{$term}_row_actions", $this->termRowActions( ... ), 11, 2 );
		}

		// customize row_actions
		foreach ( $postRowActions as $type ) {
			add_filter( "{$type}_row_actions", $this->postTypeRowActions( ... ), 11, 2 );
		}

		// exclude post columns
		foreach ( $excludeThumbColumns as $post ) {
			add_filter( "manage_{$post}_posts_columns", $this->manageColumnsExcludeHeader( ... ), 12 );
		}

		// thumb terms
		foreach ( $termThumbColumns as $term ) {
			add_filter( "manage_edit-{$term}_columns", $this->manageTermColumnsHeader( ... ), 11 );
			add_filter( "manage_{$term}_custom_column", $this->manageTermColumnsContent( ... ), 11, 3 );
		}

		// customize post, page
		add_filter( 'manage_posts_columns', $this->manageColumnsHeader( ... ), 11 );
		add_filter( 'manage_posts_custom_column', $this->manageColumnsContent( ... ), 11, 2 );
		add_filter( 'manage_pages_columns', $this->manageColumnsHeader( ... ), 5 );
		add_filter( 'manage_pages_custom_column', $this->manageColumnsContent( ... ), 5, 2 );
	}

	// --------------------------------------------------

	public function adminEnqueueScripts(): void {
		Asset::enqueueJS( 'admin.js', [ 'jquery' ], null, true, [ 'module', 'defer' ] );
	}

	// --------------------------------------------------

	public function blockEditorAssets(): void {
		Asset::enqueueCSS( 'editor-style.scss' );
	}

	// --------------------------------------------------

	public function adminNotices(): void {
		$message = get_transient( '_clear_cache_message' );

		if ( empty( $message ) ) {
			return;
		}

		Helper::messageSuccess( $message );

		// Delete transient unless a verified clear_cache request is in progress
		$clearCache = sanitize_text_field( wp_unslash( $_GET['clear_cache'] ?? '' ) );
		$nonce      = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

		if ( ! $clearCache || ! wp_verify_nonce( $nonce, 'hd_clear_cache' ) ) {
			delete_transient( '_clear_cache_message' );
		}
	}

	// --------------------------------------------------

	/**
	 * @param array  $actions
	 * @param object $_object
	 *
	 * @return array
	 */
	public function termRowActions( array $actions, object $_object ): array {
		return Helper::prepend( $actions, 'Id: ' . $_object->term_id, 'action_id' );
	}

	// --------------------------------------------------

	/**
	 * @param array  $actions
	 * @param object $_object
	 *
	 * @return array
	 */
	public function postTypeRowActions( array $actions, object $_object ): array {
		return in_array( $_object->post_type, [ 'product', 'site-review' ], true )
			? $actions
			: Helper::prepend( $actions, 'Id:' . $_object->ID, 'action_id' );
	}

	// --------------------------------------------------

	/**
	 * @param array $columns
	 *
	 * @return array
	 */
	public function manageColumnsHeader( array $columns ): array {
		return Helper::insertBefore(
			'title',
			$columns,
			[
				'post_thumb' => sprintf( '<span class="wc-image tips">%s</span>', __( 'Thumb', 'hd' ) ),
			]
		);
	}

	// --------------------------------------------------

	/**
	 * @param string $columnName
	 * @param int    $postId
	 *
	 * @return void
	 */
	public function manageColumnsContent( string $columnName, int $postId ): void {
		if ( $columnName !== 'post_thumb' ) {
			return;
		}

		$postType  = get_post_type( $postId );
		$thumbnail = Helper::postImageHTML( $postId, 'thumbnail' );

		match ( $postType ) {
			'video'   => $this->renderVideoThumb( $thumbnail, $postId ),
			'product' => null, // WooCommerce handles this
			default   => $this->renderDefaultThumb( $thumbnail ),
		};
	}

	// --------------------------------------------------

	/**
	 * @param array $columns
	 *
	 * @return array
	 */
	public function manageColumnsExcludeHeader( array $columns ): array {
		unset( $columns['post_thumb'] );

		return $columns;
	}

	// --------------------------------------------------

	/**
	 * @param array $columns
	 *
	 * @return array
	 */
	public function manageTermColumnsHeader( array $columns ): array {
		if ( ! Helper::isAcfActive() ) {
			return $columns;
		}

		return Helper::insertBefore(
			'name',
			$columns,
			[
				'term_thumb' => sprintf( '<span class="wc-image tips">%s</span>', __( 'Thumb', 'hd' ) ),
			]
		);
	}

	// --------------------------------------------------

	/**
	 * @param mixed  $out
	 * @param string $column
	 * @param int    $termId
	 *
	 * @return mixed
	 */
	public function manageTermColumnsContent( mixed $out, string $column, int $termId ): mixed {
		return match ( $column ) {
			'term_thumb' => Helper::acfTermThumb( $termId, $column, 'thumbnail', true ) ?: Helper::placeholderSrc(),
			default      => $out,
		};
	}

	/* ---------- PRIVATE ------------------------------------------ */

	/**
	 * @param string|null $thumbnail
	 *
	 * @return void
	 */
	private function renderDefaultThumb( ?string $thumbnail ): void {
		if ( $thumbnail ) {
			echo wp_kses_post( $thumbnail );
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded safe SVG placeholder
			echo Helper::placeholderSrc();
		}
	}

	// --------------------------------------------------

	/**
	 * @param string|null $thumbnail
	 * @param int         $postId
	 *
	 * @return void
	 */
	private function renderVideoThumb( ?string $thumbnail, int $postId ): void {
		if ( $thumbnail ) {
			echo wp_kses_post( $thumbnail );

			return;
		}

		$url = Helper::getField( 'url', $postId );
		if ( ! $url ) {
			return;
		}

		$imgSrc = Helper::youtubeImage( esc_url( $url ), 3 );
		if ( $imgSrc ) {
			echo '<img loading="lazy" alt="video" src="' . esc_url( $imgSrc ) . '" />';
		}
	}
}
