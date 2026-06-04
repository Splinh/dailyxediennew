<?php
/**
 * Form Entries List Table
 *
 * @package HD\Modules\Form\Admin
 */

namespace HD\Modules\Form\Admin;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use HD\Modules\Form\FormConfig;
use HD\Modules\Form\Repository\FormEntryRepository;

defined( 'ABSPATH' ) || exit;

class FormEntriesListTable extends \WP_List_Table {

	private FormEntryRepository $repo;

	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Entry', 'hd' ),
				'plural'   => __( 'Entries', 'hd' ),
				'ajax'     => false,
			]
		);

		$this->repo = new FormEntryRepository();

		$this->process_bulk_action();
	}

	public function get_columns(): array {
		return [
			'cb'         => '<input type="checkbox" />',
			'id'         => __( 'ID', 'hd' ),
			'form_type'  => __( 'Form Type', 'hd' ),
			'name'       => __( 'Name', 'hd' ),
			'email'      => __( 'Email', 'hd' ),
			'phone'      => __( 'Phone', 'hd' ),
			'status'     => __( 'Status', 'hd' ),
			'ip_address' => __( 'IP Address', 'hd' ),
			'created_at' => __( 'Date', 'hd' ),
		];
	}

	public function get_sortable_columns(): array {
		return [
			'id'         => [ 'id', true ],
			'name'       => [ 'name', false ],
			'email'      => [ 'email', false ],
			'created_at' => [ 'created_at', false ],
		];
	}

	public function get_bulk_actions(): array {
		return [
			'mark_read' => __( 'Mark Read', 'hd' ),
			'mark_spam' => __( 'Mark Spam', 'hd' ),
			'trash'     => __( 'Move to Trash', 'hd' ),
			'delete'    => __( 'Delete Permanently', 'hd' ),
		];
	}

	public function process_bulk_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = $this->current_action();
		if ( ! $action ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$entries = isset( $_REQUEST['entry'] ) ? (array) wp_unslash( $_REQUEST['entry'] ) : [];
		$ids     = array_map( 'absint', $entries );

		if ( empty( $ids ) ) {
			return;
		}

		match ( $action ) {
			'mark_read' => $this->repo->bulkUpdateStatus( $ids, 'read' ),
			'mark_spam' => $this->repo->bulkUpdateStatus( $ids, 'spam' ),
			'trash'     => $this->repo->bulkUpdateStatus( $ids, 'trash' ),
			'delete'    => $this->repo->bulkDelete( $ids ),
			default     => null,
		};

		delete_transient( 'hd_form_unread_count' );

		$sendback = remove_query_arg( [ 'action', 'action2', 'entry', 'action_id', 'action2_id' ], wp_get_referer() ?: admin_url( 'admin.php?page=hd-form-entries' ) );
		wp_safe_redirect( $sendback );
		exit;
	}

	public function prepare_items(): void {
		$perPage  = 20;
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$currentPage = $this->get_pagenum();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$orderBy = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ?? 'id' ) );
		$order   = sanitize_text_field( wp_unslash( $_REQUEST['order'] ?? 'DESC' ) );

		$filters = [];
		if ( ! empty( $_REQUEST['status'] ) ) {
			$filters['status'] = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );
		}
		if ( ! empty( $_REQUEST['form_type'] ) ) {
			$filters['form_type'] = sanitize_text_field( wp_unslash( $_REQUEST['form_type'] ) );
		}
		if ( ! empty( $_REQUEST['s'] ) ) {
			$filters['search'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
		}
		// phpcs:enable

		$totalItems  = $this->repo->countAll( $filters );
		$this->items = $this->repo->findAll( $filters, $currentPage, $perPage, $orderBy, $order );

		$this->set_pagination_args(
			[
				'total_items' => $totalItems,
				'per_page'    => $perPage,
				'total_pages' => (int) ceil( $totalItems / $perPage ),
			]
		);
	}

	protected function column_default( $item, $column_name ) {
		if ( 'status' === $column_name ) {
			$label = match ( $item['status'] ) {
				'new'     => __( 'New', 'hd' ),
				'read'    => __( 'Read', 'hd' ),
				'spam'    => __( 'Spam', 'hd' ),
				'starred' => __( 'Starred', 'hd' ),
				'trash'   => __( 'Trash', 'hd' ),
				default   => ucfirst( $item['status'] ),
			};

			return sprintf(
				'<span class="hd-entry-status hd-entry-status--%s">%s</span>',
				esc_attr( $item['status'] ),
				esc_html( $label )
			);
		}

		return match ( $column_name ) {
			'form_type'  => esc_html( FormConfig::getFormType( $item['form_type'] )['label'] ?? $item['form_type'] ),
			'name'       => esc_html( $item['name'] ),
			'email'      => esc_html( $item['email'] ),
			'phone'      => esc_html( $item['phone'] ),
			'ip_address' => esc_html( $item['ip_address'] ),
			'created_at' => esc_html( $item['created_at'] ),
			default      => isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '',
		};
	}

	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'entry',
			esc_attr( (string) absint( $item['id'] ) )
		);
	}

	protected function column_id( $item ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : 'hd-form-entries';

		$id        = absint( $item['id'] );
		$viewUrl   = add_query_arg(
			[
				'page'   => $page,
				'action' => 'view',
				'entry'  => $id,
			],
			admin_url( 'admin.php' )
		);
		$deleteUrl = wp_nonce_url(
			add_query_arg(
				[
					'page'    => $page,
					'action'  => 'delete',
					'entry[]' => $id,
				],
				admin_url( 'admin.php' )
			),
			'bulk-' . $this->_args['plural']
		);

		$actions = [
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $viewUrl ), esc_html__( 'View', 'hd' ) ),
			'delete' => sprintf( '<a href="%s" class="delete" onclick="return confirm(\'%s\');">%s</a>', esc_url( $deleteUrl ), esc_js( __( 'Are you sure?', 'hd' ) ), __( 'Delete', 'hd' ) ),
		];

		return sprintf( '%1$s %2$s', esc_html( (string) $id ), $this->row_actions( $actions ) );
	}

	protected function get_views() {
		$views = [];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : 'all';

		$statuses = [
			'all'     => __( 'All', 'hd' ),
			'new'     => __( 'New', 'hd' ),
			'read'    => __( 'Read', 'hd' ),
			'starred' => __( 'Starred', 'hd' ),
			'spam'    => __( 'Spam', 'hd' ),
			'trash'   => __( 'Trash', 'hd' ),
		];

		$baseUrl = admin_url( 'admin.php?page=hd-form-entries' );

		// Optimize: single query for all counts.
		$counts     = $this->repo->countByStatus();
		$totalCount = array_sum( $counts );

		foreach ( $statuses as $status => $label ) {
			$url   = 'all' === $status ? $baseUrl : add_query_arg( 'status', $status, $baseUrl );
			$class = $current === $status ? ' class="current"' : '';

			$count = 'all' === $status ? $totalCount : ( $counts[ $status ] ?? 0 );

			if ( 'all' === $status || $count > 0 ) {
				$views[ $status ] = sprintf( '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>', esc_url( $url ), $class, $label, $count );
			}
		}

		return $views;
	}
}
