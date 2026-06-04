<?php
/**
 * Trait for handling AJAX requests and database operations for sorting.
 *
 * @package HDAddons\Modules\CustomSorting
 */

namespace HDAddons\Modules\CustomSorting;

use HDAddons\Helper;
use HDAddons\Plugin;
use HDAddons\DB;

\defined( 'ABSPATH' ) || exit;

trait AjaxHandlerTrait {

	/**
	 * Nonce action prefix for AJAX requests.
	 */
	private static string $nonceAction = 'custom_sorting_';

	/**
	 * Refresh menu order values on admin init.
	 * Only runs when needed (checks if reordering is required).
	 *
	 * @return void
	 */
	public function refresh(): void {
		if ( wp_doing_ajax() ) {
			return;
		}

		// Skip if no post types or taxonomies configured
		if ( empty( $this->orderPostType ) && empty( $this->orderTaxonomy ) ) {
			return;
		}

		// Check if refresh is needed (throttle to once per hour)
		$lastRefresh = get_transient( 'hda_sorting_last_refresh' );
		if ( $lastRefresh && ! ( isset( $_GET['force_refresh_order'] ) && current_user_can( Plugin::CAPABILITY ) ) ) {
			return;
		}

		$this->refreshPostTypeOrder();
		$this->refreshTaxonomyOrder();

		set_transient( 'hda_sorting_last_refresh', time(), HOUR_IN_SECONDS );
	}

	// ------------------------------------------------------

	/**
	 * Refresh post type menu order.
	 */
	private function refreshPostTypeOrder(): void {
		if ( empty( $this->orderPostType ) ) {
			return;
		}

		$wpdb = DB::db();

		foreach ( $this->orderPostType as $postType ) {
			$result = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS cnt, MAX(menu_order) AS max_order, MIN(menu_order) AS min_order, SUM(menu_order) AS sum_order
					FROM {$wpdb->posts}
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')",
					$postType
				)
			);

			// Skip if already properly ordered or empty
			if ( ! $result || (int) $result->cnt === 0 ) {
				continue;
			}

			if ( (int) $result->cnt === (int) $result->max_order && (int) $result->min_order === 1 && (int) $result->sum_order === ( (int) $result->cnt * ( (int) $result->cnt + 1 ) / 2 ) ) {
				continue;
			}

			// Re-sequence menu_order for this post type
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
					ORDER BY menu_order ASC",
					$postType
				)
			);

			$pairs = [];
			foreach ( $rows as $key => $row ) {
				$pairs[ (int) $row->ID ] = $key + 1;
			}
			$this->batchUpdateOrder( $wpdb, $wpdb->posts, 'ID', 'menu_order', $pairs );
		}
	}

	// ------------------------------------------------------

	/**
	 * Refresh taxonomy term order.
	 */
	private function refreshTaxonomyOrder(): void {
		if ( empty( $this->orderTaxonomy ) ) {
			return;
		}

		$wpdb = DB::db();

		foreach ( $this->orderTaxonomy as $taxonomy ) {
			$result = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS cnt, MAX(term_order) AS max_order, MIN(term_order) AS min_order, SUM(term_order) AS sum_order
					FROM {$wpdb->terms} AS t
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
					WHERE tt.taxonomy = %s",
					$taxonomy
				)
			);

			// Skip if already properly ordered or empty
			if ( ! $result || (int) $result->cnt === 0 ) {
				continue;
			}

			if ( (int) $result->cnt === (int) $result->max_order && (int) $result->min_order === 1 && (int) $result->sum_order === ( (int) $result->cnt * ( (int) $result->cnt + 1 ) / 2 ) ) {
				continue;
			}

			// Get terms and update in batch
			$terms = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.term_id
					FROM {$wpdb->terms} AS t
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
					WHERE tt.taxonomy = %s
					ORDER BY term_order ASC",
					$taxonomy
				)
			);

			$pairs = [];
			foreach ( $terms as $key => $term ) {
				$pairs[ (int) $term->term_id ] = $key + 1;
			}
			$this->batchUpdateOrder( $wpdb, $wpdb->terms, 'term_id', 'term_order', $pairs );
		}
	}

	// ------------------------------------------------------

	/**
	 * AJAX handler for updating post menu order.
	 *
	 * @return void
	 */
	public function updateMenuOrderAjax(): void {
		if ( ! wp_doing_ajax() ) {
			wp_die( -1 );
		}

		// Verify nonce and capabilities
		if ( ! check_ajax_referer( self::$nonceAction . get_current_user_id(), 'nonce', false ) ) {
			wp_send_json_error( 'Invalid nonce', 403 );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Insufficient permissions', 403 );
		}

		if ( ! isset( $_POST['order'] ) ) {
			wp_send_json_error( 'Missing order data', 400 );
		}

		parse_str( wp_unslash( $_POST['order'] ), $data );

		if ( empty( $data ) ) {
			wp_send_json_error( 'Invalid data', 400 );
		}

		$idArr = $this->extractIds( $data );

		if ( empty( $idArr ) ) {
			wp_send_json_error( 'No IDs provided', 400 );
		}

		$wpdb = DB::db();

		// Batch query: get all menu orders at once
		$placeholders  = implode( ',', array_fill( 0, count( $idArr ), '%d' ) );
		$menuOrderRows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, menu_order FROM {$wpdb->posts} WHERE ID IN ({$placeholders})",
				...$idArr
			)
		);

		// Map ID to menu_order
		$menuOrderMap = [];
		foreach ( $menuOrderRows as $row ) {
			$menuOrderMap[ $row->ID ] = (int) $row->menu_order;
		}

		// Get sorted menu orders
		$menuOrders = array_values( $menuOrderMap );
		sort( $menuOrders );

		// Batch update
		$pairs = [];
		foreach ( $idArr as $position => $id ) {
			if ( isset( $menuOrders[ $position ] ) ) {
				$pairs[ $id ] = $menuOrders[ $position ];
			}
		}
		$this->batchUpdateOrder( $wpdb, $wpdb->posts, 'ID', 'menu_order', $pairs );

		// Clear caches
		foreach ( $idArr as $id ) {
			clean_post_cache( $id );
		}

		// Clear refresh transient to force recheck
		delete_transient( 'hda_sorting_last_refresh' );

		do_action( 'hda_update_menu_order_post_type' );
		wp_send_json_success();
	}

	// ------------------------------------------------------

	/**
	 * AJAX handler for updating taxonomy term order.
	 *
	 * @return void
	 */
	public function updateMenuOrderTagsAjax(): void {
		if ( ! wp_doing_ajax() ) {
			wp_die( -1 );
		}

		// Verify nonce and capabilities
		if ( ! check_ajax_referer( self::$nonceAction . get_current_user_id(), 'nonce', false ) ) {
			wp_send_json_error( 'Invalid nonce', 403 );
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( 'Insufficient permissions', 403 );
		}

		if ( ! isset( $_POST['order'] ) ) {
			wp_send_json_error( 'Missing order data', 400 );
		}

		parse_str( wp_unslash( $_POST['order'] ), $data );

		if ( empty( $data ) ) {
			wp_send_json_error( 'Invalid data', 400 );
		}

		$idArr = $this->extractIds( $data );

		if ( empty( $idArr ) ) {
			wp_send_json_error( 'No IDs provided', 400 );
		}

		$wpdb = DB::db();

		// Batch query: get all term orders at once
		$placeholders  = implode( ',', array_fill( 0, count( $idArr ), '%d' ) );
		$termOrderRows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id, term_order FROM {$wpdb->terms} WHERE term_id IN ({$placeholders})",
				...$idArr
			)
		);

		// Map ID to term_order
		$termOrderMap = [];
		foreach ( $termOrderRows as $row ) {
			$termOrderMap[ $row->term_id ] = (int) $row->term_order;
		}

		// Get sorted term orders
		$termOrders = array_values( $termOrderMap );
		sort( $termOrders );

		// Batch update
		$pairs = [];
		foreach ( $idArr as $position => $id ) {
			if ( isset( $termOrders[ $position ] ) ) {
				$pairs[ $id ] = $termOrders[ $position ];
			}
		}
		$this->batchUpdateOrder( $wpdb, $wpdb->terms, 'term_id', 'term_order', $pairs );

		// Clear caches (O(1) bulk clean instead of N+1)
		$taxonomy = sanitize_key( wp_unslash( $_POST['taxonomy'] ?? '' ) );
		if ( ! $taxonomy ) {
			$firstTerm = get_term( reset( $idArr ) );
			$taxonomy  = ( $firstTerm && ! is_wp_error( $firstTerm ) ) ? $firstTerm->taxonomy : '';
		}

		if ( $taxonomy ) {
			clean_term_cache( $idArr, $taxonomy );
		}

		// Clear refresh transient
		delete_transient( 'hda_sorting_last_refresh' );

		do_action( 'hda_update_menu_order_taxonomy' );
		wp_send_json_success();
	}

	// ------------------------------------------------------

	/**
	 * Extract IDs from parsed order data.
	 *
	 * @param array $data Parsed data.
	 *
	 * @return int[] Array of post/term IDs.
	 */
	private function extractIds( array $data ): array {
		foreach ( $data as $values ) {
			if ( is_array( $values ) ) {
				return array_map( 'absint', array_values( $values ) );
			}
		}

		return [];
	}

	// ------------------------------------------------------

	/**
	 * Batch update order values using a single CASE WHEN query.
	 *
	 * @param \wpdb  $wpdb     Database instance.
	 * @param string $table    Table name.
	 * @param string $idCol    ID column name.
	 * @param string $orderCol Order column name.
	 * @param array  $pairs    ID => order pairs.
	 */
	private function batchUpdateOrder( \wpdb $wpdb, string $table, string $idCol, string $orderCol, array $pairs ): void {
		if ( empty( $pairs ) ) {
			return;
		}

		$chunks = array_chunk( $pairs, 500, true ); // Chunk size of 500 to prevent MySQL packet limits

		foreach ( $chunks as $chunk ) {
			$cases = [];
			$ids   = [];

			foreach ( $chunk as $id => $order ) {
				$cases[] = $wpdb->prepare( 'WHEN %d THEN %d', $id, $order );
				$ids[]   = absint( $id );
			}

			$caseStr = implode( ' ', $cases );
			$idStr   = implode( ',', $ids );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Values above are prepared/absint'd.
			$wpdb->query( "UPDATE {$table} SET {$orderCol} = CASE {$idCol} {$caseStr} END WHERE {$idCol} IN ({$idStr})" );
		}
	}

	// ------------------------------------------------------

	/**
	 * Update order options after save.
	 *
	 * @return void
	 */
	public function updateOptions(): void {
		$wpdb = DB::db();

		$this->updatePostTypeOptions( $wpdb );
		$this->updateTaxonomyOptions( $wpdb );
	}

	// ------------------------------------------------------

	/**
	 * Update post type ordering options.
	 *
	 * @param \wpdb $wpdb Database instance.
	 */
	private function updatePostTypeOptions( \wpdb $wpdb ): void {
		if ( empty( $this->orderPostType ) ) {
			return;
		}

		foreach ( $this->orderPostType as $postType ) {
			$postType = sanitize_key( $postType );

			$result = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS cnt, MAX(menu_order) AS max_order, MIN(menu_order) AS min_order, SUM(menu_order) AS sum_order
					FROM {$wpdb->posts}
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')",
					$postType
				)
			);

			if ( ! $result || (int) $result->cnt === 0 || ( (int) $result->cnt === (int) $result->max_order && (int) $result->min_order === 1 && (int) $result->sum_order === ( (int) $result->cnt * ( (int) $result->cnt + 1 ) / 2 ) ) ) {
				continue;
			}

			$orderBy = ( 'page' === $postType ) ? 'post_title ASC' : 'post_date DESC';

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
					ORDER BY {$orderBy}",
					$postType
				)
			);

			$pairs = [];
			foreach ( $results as $key => $row ) {
				$pairs[ (int) $row->ID ] = $key + 1;
			}
			$this->batchUpdateOrder( $wpdb, $wpdb->posts, 'ID', 'menu_order', $pairs );
		}
	}

	// ------------------------------------------------------

	/**
	 * Update taxonomy ordering options.
	 *
	 * @param \wpdb $wpdb Database instance.
	 */
	private function updateTaxonomyOptions( \wpdb $wpdb ): void {
		if ( empty( $this->orderTaxonomy ) ) {
			return;
		}

		foreach ( $this->orderTaxonomy as $taxonomy ) {
			$taxonomy = sanitize_key( $taxonomy );

			$result = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS cnt, MAX(term_order) AS max_order, MIN(term_order) AS min_order, SUM(term_order) AS sum_order
					FROM {$wpdb->terms} AS t
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
					WHERE tt.taxonomy = %s",
					$taxonomy
				)
			);

			if ( ! $result || (int) $result->cnt === 0 || ( (int) $result->cnt === (int) $result->max_order && (int) $result->min_order === 1 && (int) $result->sum_order === ( (int) $result->cnt * ( (int) $result->cnt + 1 ) / 2 ) ) ) {
				continue;
			}

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.term_id
					FROM {$wpdb->terms} AS t
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
					WHERE tt.taxonomy = %s
					ORDER BY name ASC",
					$taxonomy
				)
			);

			$pairs = [];
			foreach ( $results as $key => $row ) {
				$pairs[ (int) $row->term_id ] = $key + 1;
			}
			$this->batchUpdateOrder( $wpdb, $wpdb->terms, 'term_id', 'term_order', $pairs );
		}
	}

	// ------------------------------------------------------

	/**
	 * Reset all custom ordering.
	 *
	 * @return void
	 */
	public function resetAll(): void {
		$wpdb = DB::db();

		// Reset posts
		if ( ! empty( $this->orderPostType ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $this->orderPostType ), '%s' ) );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET menu_order = 0 WHERE post_type IN ({$placeholders})",
					...$this->orderPostType
				)
			);
		}

		// Reset taxonomy (only configured taxonomies, not all terms)
		if ( ! empty( $this->orderTaxonomy ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $this->orderTaxonomy ), '%s' ) );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->terms} AS t
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
					SET t.term_order = 0
					WHERE tt.taxonomy IN ({$placeholders})",
					...$this->orderTaxonomy
				)
			);
		}

		// Clean up options and state
		Helper::removeOption( self::optionKey() );
		Helper::setThemeMod( '_custom_sorting_', 0 );
		delete_transient( 'hda_sorting_last_refresh' );
	}
}
