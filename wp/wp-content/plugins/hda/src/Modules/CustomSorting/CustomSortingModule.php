<?php
/**
 * Custom Sorting — Drag-and-Drop Sortable for posts and terms.
 *
 * @package HDAddons\Modules\CustomSorting
 */

namespace HDAddons\Modules\CustomSorting;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Asset;
use HDAddons\DB;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class CustomSortingModule extends AbstractModule implements HasSettings {

	use PostOrderTrait;
	use TaxonomyOrderTrait;
	use AjaxHandlerTrait;

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'custom_sorting';
	}

	public static function title(): string {
		return 'Custom Sorting';
	}

	public static function description(): string {
		return 'Drag-and-drop ordering for posts and terms.';
	}

	public static function group(): string {
		return 'tools';
	}


	// ── Constants ───────────────────────────────────

	public const KEY_ORDER_POST_TYPE = 'order_post_type';
	public const KEY_ORDER_TAXONOMY  = 'order_taxonomy';

	/**
	 * Post types enabled for custom sorting.
	 */
	private array $orderPostType;

	/**
	 * Taxonomies enabled for custom sorting.
	 */
	private array $orderTaxonomy;

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		$options = Helper::getOption( self::optionKey(), [] );

		$this->orderPostType = $options[ self::KEY_ORDER_POST_TYPE ] ?? [];
		$this->orderTaxonomy = $options[ self::KEY_ORDER_TAXONOMY ] ?? [];

		if ( ! empty( $this->orderPostType ) || ! empty( $this->orderTaxonomy ) ) {
			self::ensureTermOrderColumn();
			$this->initHooks();
		}
	}

	// ── DB Setup ────────────────────────────────────

	/**
	 * Ensure term_order column exists (runs once, cached via theme_mod).
	 */
	public static function ensureTermOrderColumn(): void {
		if ( Helper::getThemeMod( '_custom_sorting_' ) ) {
			return;
		}

		$wpdb = DB::db();

		if ( ! $wpdb->query( "DESCRIBE {$wpdb->terms} `term_order`" ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->terms} ADD `term_order` INT( 4 ) NULL DEFAULT '0'" );
		}

		Helper::setThemeMod( '_custom_sorting_', 1 );
	}

	// ── Hooks ───────────────────────────────────────

	private function initHooks(): void {
		add_action( 'admin_enqueue_scripts', $this->enqueueAdminScripts( ... ), 33 );
		add_action( 'admin_init', $this->refresh( ... ) );

		// posts
		add_action( 'pre_get_posts', $this->customOrderPreGetPosts( ... ) );

		// dynamic hook get_(adjacent)_post_sort
		add_filter( 'get_previous_post_sort', $this->customOrderPreviousPostSort( ... ) );
		add_filter( 'get_next_post_sort', $this->customOrderNextPostSort( ... ) );

		// dynamic hook get_(adjacent)_post_where
		add_filter( 'get_previous_post_where', $this->customOrderPreviousPostWhere( ... ) );
		add_filter( 'get_next_post_where', $this->customOrderNextPostWhere( ... ) );

		// terms
		add_filter( 'get_terms_args', $this->customOrderGetTermsArgs( ... ), 10, 2 );

		// ajax
		add_action( 'wp_ajax_update-menu-order', $this->updateMenuOrderAjax( ... ) );
		add_action( 'wp_ajax_update-menu-order-tags', $this->updateMenuOrderTagsAjax( ... ) );
	}

	// ── Admin Scripts ───────────────────────────────

	public function enqueueAdminScripts( string $hook_suffix ): void {
		if ( ! $this->shouldLoadSortingScript() ) {
			return;
		}

		Asset::localize(
			'jquery-core',
			'customSortingVars',
			[
				'nonce'   => wp_create_nonce( self::$nonceAction . get_current_user_id() ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			]
		);
		Asset::enqueueJS(
			'sorting.js',
			[ 'jquery-core', 'jquery-ui-sortable' ],
			null,
			true,
			[ 'module', 'defer' ]
		);
	}

	/**
	 * Check if sorting script should be loaded.
	 */
	private function shouldLoadSortingScript(): bool {
		if ( empty( $this->orderPostType ) && empty( $this->orderTaxonomy ) ) {
			return false;
		}

		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

		if ( isset( $_GET['orderby'] ) || str_contains( $request_uri, 'action=edit' ) || str_contains( $request_uri, 'wp-admin/post-new.php' ) ) {
			return false;
		}

		$post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ?? '' ) );
		$taxonomy  = sanitize_text_field( wp_unslash( $_GET['taxonomy'] ?? '' ) );

		if ( ! empty( $this->orderPostType ) ) {
			if ( $post_type && empty( $taxonomy ) && in_array( $post_type, $this->orderPostType, true ) ) {
				return true;
			}
			if ( empty( $post_type ) && str_contains( $request_uri, 'wp-admin/edit.php' ) && in_array( 'post', $this->orderPostType, true ) ) {
				return true;
			}
		}

		if ( ! empty( $this->orderTaxonomy ) && $taxonomy && in_array( $taxonomy, $this->orderTaxonomy, true ) ) {
			return true;
		}

		return false;
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		$order_reset = ! empty( $data['order_reset'] ) ? sanitize_text_field( $data['order_reset'] ) : '';
		$options     = [];

		if ( empty( $order_reset ) ) {
			foreach ( [ self::KEY_ORDER_POST_TYPE, self::KEY_ORDER_TAXONOMY ] as $field ) {
				if ( ! empty( $data[ $field ] ) ) {
					$options[ $field ] = array_map( 'sanitize_text_field', (array) $data[ $field ] );
				}
			}
		}

		try {
			$instance = new self();

			if ( ! empty( $options ) ) {
				self::ensureTermOrderColumn();
				Helper::updateOption( self::optionKey(), $options );
				$instance->boot();
				$instance->updateOptions();
			} else {
				// Ensure it knows what to reset from the previously saved options.
				$instance->boot();
				$instance->resetAll();
			}
		} catch ( \Exception $e ) {
			Helper::errorLog( 'HDA: Custom sorting update failed - ' . $e->getMessage() );
		}
	}
}
