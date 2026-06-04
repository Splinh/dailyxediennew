<?php
/**
 * Traffic Monitor List Table — WP_List_Table implementation.
 *
 * Displays traffic logs with filtering by action, attack type, and IP.
 *
 * @package HDAddons\Modules\Logs\TrafficMonitor
 * @author  HD
 */

namespace HDAddons\Modules\Logs\TrafficMonitor;

use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class TrafficMonitorTable extends \WP_List_Table {

	/**
	 * Total items from the last query.
	 *
	 * @var int
	 */
	private int $totalItems = 0;

	// --------------------------------------------------

	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Traffic Log', 'hda' ),
				'plural'   => __( 'Traffic Logs', 'hda' ),
				'ajax'     => false,
			]
		);
	}

	// ══════════════════════════════════════════════════
	// Columns
	// ══════════════════════════════════════════════════

	/**
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'cb'          => '<input type="checkbox" />',
			'ip'          => __( 'IP Address', 'hda' ),
			'uri'         => __( 'URI', 'hda' ),
			'method'      => __( 'Method', 'hda' ),
			'action'      => __( 'Action', 'hda' ),
			'attack_type' => __( 'Attack Type', 'hda' ),
			'severity'    => __( 'Severity', 'hda' ),
			'created_at'  => __( 'Time', 'hda' ),
		];
	}

	/**
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return [
			'ip'          => [ 'ip', false ],
			'action'      => [ 'action', false ],
			'attack_type' => [ 'attack_type', false ],
			'severity'    => [ 'severity', false ],
			'created_at'  => [ 'created_at', true ],
		];
	}

	/**
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return [
			'delete' => __( 'Delete', 'hda' ),
		];
	}

	// ══════════════════════════════════════════════════
	// Data preparation
	// ══════════════════════════════════════════════════

	/**
	 * Process bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action(): void {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			return;
		}

		$ids = isset( $_REQUEST['log_ids'] ) ? array_map( 'absint', (array) $_REQUEST['log_ids'] ) : [];
		if ( ! empty( $ids ) ) {
			TrafficLogger::deleteByIds( $ids );
		}
	}

	/**
	 * @return void
	 */
	public function prepare_items(): void {
		$this->process_bulk_action();

		$per_page = 30;
		$page     = $this->get_pagenum();

		$search       = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
		$orderby      = sanitize_key( $_REQUEST['orderby'] ?? 'created_at' );
		$order        = sanitize_key( $_REQUEST['order'] ?? 'DESC' );
		$filterAction = sanitize_key( $_REQUEST['filter_action'] ?? '' );
		$filterType   = sanitize_key( $_REQUEST['filter_type'] ?? '' );
		$filterIp     = sanitize_text_field( wp_unslash( $_REQUEST['filter_ip'] ?? '' ) );

		$result = TrafficLogger::getLogs(
			[
				'per_page'    => $per_page,
				'page'        => $page,
				'search'      => $search,
				'orderby'     => $orderby,
				'order'       => $order,
				'action'      => $filterAction,
				'attack_type' => $filterType,
				'ip'          => $filterIp,
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

	// ══════════════════════════════════════════════════
	// Extra navigation (filters)
	// ══════════════════════════════════════════════════

	/**
	 * @param string $which Top or bottom.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		$currentAction = sanitize_key( $_REQUEST['filter_action'] ?? '' );
		$currentType   = sanitize_key( $_REQUEST['filter_type'] ?? '' );

		echo '<div class="alignleft actions">';

		// Action filter.
		$actions = [
			''        => __( 'All Actions', 'hda' ),
			'blocked' => 'Blocked',
			'logged'  => 'Logged',
			'allowed' => 'Allowed',
		];
		echo '<select name="filter_action">';
		foreach ( $actions as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $currentAction, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		// Attack type filter.
		$types = [
			''              => __( 'All Types', 'hda' ),
			'sqli'          => 'SQLi',
			'xss'           => 'XSS',
			'rce'           => 'RCE',
			'lfi'           => 'LFI',
			'bad_bot'       => 'Bad Bot',
			'rate_limit'    => 'Rate Limit',
			'author_scan'   => 'Author Scan',
			'ip_reputation' => 'IP Reputation',
			'brute_force'   => 'Brute Force',
			'404_flood'     => '404 Flood',
			'access_denied' => 'Access Denied',
		];
		echo '<select name="filter_type">';
		foreach ( $types as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $currentType, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		submit_button( __( 'Filter', 'hda' ), '', 'filter_submit', false );

		// Clear All button (only when items exist).
		if ( $this->totalItems > 0 ) {
			echo '&nbsp;';
			wp_nonce_field( 'hda_clear_all_traffic_logs', '_hda_clear_nonce', false );
			submit_button( __( 'Clear All', 'hda' ), 'delete', 'clear_all_logs', false );
		}

		echo '</div>';
	}

	// ══════════════════════════════════════════════════
	// Column renderers
	// ══════════════════════════════════════════════════

	/**
	 * Checkbox column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="log_ids[]" value="%d" />', absint( $item['id'] ) );
	}

	/**
	 * IP column with filter link.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_ip( $item ): string {
		$ip      = esc_html( $item['ip'] );
		$country = ! empty( $item['country'] ) ? ' <small>(' . esc_html( strtoupper( $item['country'] ) ) . ')</small>' : '';

		$filterUrl = add_query_arg(
			[
				'page'      => 'hda-traffic-monitor',
				'filter_ip' => $item['ip'],
			],
			admin_url( 'admin.php' )
		);

		$output = sprintf( '<code>%s</code>%s', $ip, $country );

		// Row actions.
		$actions = [
			'filter' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $filterUrl ),
				__( 'Filter IP', 'hda' )
			),
		];

		return $output . $this->row_actions( $actions );
	}

	/**
	 * URI column (truncated with title tooltip).
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_uri( $item ): string {
		$uri       = $item['uri'];
		$truncated = mb_strlen( $uri ) > 70
			? esc_html( mb_substr( $uri, 0, 70 ) ) . '&hellip;'
			: esc_html( $uri );

		return sprintf( '<span title="%s"><code>%s</code></span>', esc_attr( $uri ), $truncated );
	}

	/**
	 * Method column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_method( $item ): string {
		return esc_html( $item['method'] ?? 'GET' );
	}

	/**
	 * Action column with colored badge.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_action( $item ): string {
		$action = $item['action'] ?? 'allowed';
		$class  = match ( $action ) {
			'blocked'    => 'hda-badge hda-badge--danger',
			'logged'     => 'hda-badge hda-badge--warning',
			'challenged' => 'hda-badge hda-badge--info',
			default      => 'hda-badge hda-badge--success',
		};

		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), esc_html( ucfirst( $action ) ) );
	}

	/**
	 * Attack type column.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_attack_type( $item ): string {
		$type = $item['attack_type'] ?? '';
		if ( empty( $type ) ) {
			return '<em>—</em>';
		}

		$ruleId = ! empty( $item['rule_id'] ) ? ' <small>(' . esc_html( $item['rule_id'] ) . ')</small>' : '';

		return esc_html( strtoupper( $type ) ) . $ruleId;
	}

	/**
	 * Severity column with colored badge.
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_severity( $item ): string {
		$severity = $item['severity'] ?? '';
		if ( empty( $severity ) ) {
			return '<em>—</em>';
		}

		$class = match ( $severity ) {
			'critical' => 'hda-badge hda-badge--danger',
			'high'     => 'hda-badge hda-badge--warning',
			'medium'   => 'hda-badge hda-badge--info',
			default    => 'hda-badge hda-badge--muted',
		};

		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), esc_html( ucfirst( $severity ) ) );
	}

	/**
	 * Time column (human-readable).
	 *
	 * @param array $item Row data.
	 *
	 * @return string
	 */
	public function column_created_at( $item ): string {
		// created_at is stored in UTC (current_time('mysql', true) in TrafficLogger).
		$timestamp = strtotime( $item['created_at'] . ' UTC' );

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
	 * No items message.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No traffic logs recorded yet. Enable the Firewall module to start logging.', 'hda' );
	}
}
