<?php
/**
 * 404 Monitor List Table - WP_List_Table implementation.
 *
 * @package HDAddons\Modules\Logs\Monitor404
 * @author  HD
 */

namespace HDAddons\Modules\Logs\Monitor404;

\defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class Monitor404Table extends \WP_List_Table {

	/**
	 * Total items from the last query (avoids re-counting in extra_tablenav).
	 *
	 * @var int
	 */
	private int $totalItems = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( '404 Log', 'hda' ),
				'plural'   => __( '404 Logs', 'hda' ),
				'ajax'     => false,
			]
		);
	}

	/**
	 * Get table columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'cb'         => '<input type="checkbox" />',
			'url'        => __( 'URL', 'hda' ),
			'referer'    => __( 'Referer', 'hda' ),
			'hit_count'  => __( 'Hits', 'hda' ),
			'ip_address' => __( 'IP Address', 'hda' ),
			'updated_at' => __( 'Last Hit', 'hda' ),
		];
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return [
			'url'        => [ 'url', false ],
			'hit_count'  => [ 'hit_count', true ],
			'updated_at' => [ 'updated_at', true ],
		];
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return [
			'delete' => __( 'Delete', 'hda' ),
		];
	}

	/**
	 * Process bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action(): void {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		// Verify nonce
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			return;
		}

		$ids = isset( $_REQUEST['log_ids'] ) ? array_map( 'absint', (array) $_REQUEST['log_ids'] ) : [];

		if ( ! empty( $ids ) ) {
			Monitor404::deleteByIds( $ids );
		}
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$this->process_bulk_action();

		$per_page = 30;
		$page     = $this->get_pagenum();

		$search  = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
		$orderby = sanitize_key( $_REQUEST['orderby'] ?? 'updated_at' );
		$order   = sanitize_key( $_REQUEST['order'] ?? 'DESC' );

		$result = Monitor404::getLogs(
			[
				'per_page' => $per_page,
				'page'     => $page,
				'search'   => $search,
				'orderby'  => $orderby,
				'order'    => $order,
			]
		);

		$this->items      = $result['items'];
		$this->totalItems = $result['total'];

		$this->set_pagination_args(
			[
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			]
		);

		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns(),
		];
	}

	/**
	 * Extra table navigation (filters).
	 *
	 * @param string $which Top or bottom.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		if ( $this->totalItems > 0 ) {
			echo '<div class="alignleft actions">';
			wp_nonce_field( 'hda_clear_all_404_logs', '_hda_clear_nonce', false );
			submit_button( __( 'Clear All Logs', 'hda' ), 'delete', 'clear_all_logs', false );
			echo '</div>';
		}
	}

	/**
	 * Checkbox column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="log_ids[]" value="%d" />',
			absint( $item['id'] )
		);
	}

	/**
	 * URL column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_url( $item ): string {
		$url           = esc_html( $item['url'] );
		$full_url      = esc_url( home_url( $item['url'] ) );
		$truncated_url = mb_strlen( $item['url'] ) > 80
			? esc_html( mb_substr( $item['url'], 0, 80 ) ) . '&hellip;'
			: $url;

		$output = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" title="%s"><code>%s</code></a>',
			$full_url,
			$url,
			$truncated_url
		);

		// Row action: Create Redirect (if redirect module is active)
		$actions = [];

		$actions['delete'] = sprintf(
			'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
			wp_nonce_url(
				add_query_arg(
					[
						'page'    => 'hda-404-monitor',
						'action'  => 'delete',
						'log_ids' => [ $item['id'] ],
					],
					admin_url( 'admin.php' )
				),
				'bulk-' . $this->_args['plural']
			),
			esc_js( __( 'Are you sure?', 'hda' ) ),
			__( 'Delete', 'hda' )
		);

		return $output . $this->row_actions( $actions );
	}

	/**
	 * Referer column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_referer( array $item ): string {
		$referer = $item['referer'];

		if ( empty( $referer ) ) {
			return '<em>' . esc_html__( 'Direct', 'hda' ) . '</em>';
		}

		$display = wp_parse_url( $referer, PHP_URL_HOST ) ?: $referer;

		return sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" title="%s">%s</a>',
			esc_url( $referer ),
			esc_attr( $referer ),
			esc_html( $display )
		);
	}

	/**
	 * Hit count column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_hit_count( array $item ): string {
		$count = (int) $item['hit_count'];

		$class = 'hit-count';
		if ( $count >= 100 ) {
			$class .= ' hit-count--high';
		} elseif ( $count >= 10 ) {
			$class .= ' hit-count--medium';
		}

		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), number_format_i18n( $count ) );
	}

	/**
	 * IP Address column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_ip_address( array $item ): string {
		return sprintf( '<code>%s</code>', esc_html( $item['ip_address'] ) );
	}

	/**
	 * Updated at column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_updated_at( array $item ): string {
		$timestamp = strtotime( $item['updated_at'] );

		return sprintf(
			'<time datetime="%s" title="%s">%s</time>',
			esc_attr( gmdate( 'c', $timestamp ) ),
			esc_attr( wp_date( 'Y-m-d H:i:s', $timestamp ) ),
			esc_html( human_time_diff( $timestamp, time() ) ) . ' ' . esc_html__( 'ago', 'hda' )
		);
	}

	/**
	 * Default column handler.
	 *
	 * @param array  $item        Row data.
	 * @param string $column_name Column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return esc_html( $item[ $column_name ] ?? '' );
	}

	/**
	 * Display when no items found.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No 404 errors have been logged yet.', 'hda' );
	}
}
