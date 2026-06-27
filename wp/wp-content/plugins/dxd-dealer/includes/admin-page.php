<?php
/**
 * Admin page for DXD Dealer — CSV Import/Export UI.
 *
 * Menu position: under the "Đại lý" CPT menu.
 *
 * @package DXD_Dealer
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'dxd_dealer_admin_menu' );
add_action( 'admin_init', 'dxd_dealer_handle_admin_actions' );

/**
 * Register sub-menu page under CPT menu.
 */
function dxd_dealer_admin_menu(): void {
	add_submenu_page(
		'edit.php?post_type=local_store',
		__( 'Import / Export CSV', 'dxd-dealer' ),
		__( 'Import / Export', 'dxd-dealer' ),
		'manage_options',
		'dxd-dealer-csv',
		'dxd_dealer_csv_page'
	);
}

/**
 * Handle import/export form submissions.
 */
function dxd_dealer_handle_admin_actions(): void {
	// ── Export ──
	if (
		isset( $_GET['page'], $_GET['action'] )
		&& $_GET['page'] === 'dxd-dealer-csv'
		&& $_GET['action'] === 'export'
	) {
		check_admin_referer( 'dxd_dealer_export' );
		dxd_dealer_export_csv();
		// exit is inside dxd_dealer_export_csv().
	}

	// ── Import ──
	if (
		isset( $_POST['dxd_dealer_import'] )
		&& check_admin_referer( 'dxd_dealer_import' )
	) {
		if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
			add_settings_error( 'dxd_dealer', 'no_file', __( 'Vui lòng chọn file CSV.', 'dxd-dealer' ), 'error' );
			return;
		}

		$file = $_FILES['csv_file']['tmp_name'];
		$result = dxd_dealer_import_csv( $file );

		$msg = sprintf(
			__( 'Import hoàn tất: %d mới, %d cập nhật, %d bỏ qua.', 'dxd-dealer' ),
			$result['imported'],
			$result['updated'],
			$result['skipped']
		);

		if ( $result['errors'] ) {
			$msg .= ' ' . __( 'Lỗi:', 'dxd-dealer' ) . ' ' . implode( ' | ', $result['errors'] );
			add_settings_error( 'dxd_dealer', 'import_partial', $msg, 'warning' );
		} else {
			add_settings_error( 'dxd_dealer', 'import_success', $msg, 'success' );
		}

		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( admin_url( 'edit.php?post_type=local_store&page=dxd-dealer-csv&settings-updated=true' ) );
		exit;
	}
}

/**
 * Render the admin page.
 */
function dxd_dealer_csv_page(): void {
	if ( isset( $_GET['settings-updated'] ) ) {
		settings_errors( 'dxd_dealer' );
	}

	$store_count = wp_count_posts( 'local_store' );
	$total       = ( $store_count->publish ?? 0 ) + ( $store_count->draft ?? 0 );
	$export_url  = wp_nonce_url(
		admin_url( 'edit.php?post_type=local_store&page=dxd-dealer-csv&action=export' ),
		'dxd_dealer_export'
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'DXD Dealer — Import / Export CSV', 'dxd-dealer' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Quản lý dữ liệu cửa hàng/đại lý qua file CSV. Dùng để đồng bộ giữa các website.', 'dxd-dealer' ); ?></p>

		<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 20px; max-width: 900px;">
			<!-- IMPORT -->
			<div class="card" style="padding: 20px;">
				<h2 style="margin-top:0;">
					<span class="dashicons dashicons-upload" style="color:#1e73be;"></span>
					<?php esc_html_e( 'Import CSV', 'dxd-dealer' ); ?>
				</h2>
				<p class="description"><?php esc_html_e( 'Upload file CSV để import/cập nhật danh sách cửa hàng. Nếu mã cửa hàng hoặc tên đã tồn tại sẽ cập nhật, không tạo trùng.', 'dxd-dealer' ); ?></p>
				<form method="post" enctype="multipart/form-data" style="margin-top:16px;">
					<?php wp_nonce_field( 'dxd_dealer_import' ); ?>
					<p>
						<input type="file" name="csv_file" accept=".csv" required />
					</p>
					<p>
						<button type="submit" name="dxd_dealer_import" value="1" class="button button-primary">
							<span class="dashicons dashicons-database-import" style="vertical-align:middle; margin-right:4px;"></span>
							<?php esc_html_e( 'Import', 'dxd-dealer' ); ?>
						</button>
					</p>
				</form>
				<hr />
				<h4 style="margin-bottom:4px;"><?php esc_html_e( 'Cột CSV yêu cầu:', 'dxd-dealer' ); ?></h4>
				<code style="font-size:11px; display:block; background:#f6f7f7; padding:8px; border-radius:4px; word-break:break-all;">
					<?php echo esc_html( implode( ', ', dxd_dealer_csv_headers() ) ); ?>
				</code>
			</div>

			<!-- EXPORT -->
			<div class="card" style="padding: 20px;">
				<h2 style="margin-top:0;">
					<span class="dashicons dashicons-download" style="color:#10b981;"></span>
					<?php esc_html_e( 'Export CSV', 'dxd-dealer' ); ?>
				</h2>
				<p class="description">
					<?php
					printf(
						/* translators: %d = number of stores */
						esc_html__( 'Xuất toàn bộ %d cửa hàng ra file CSV để backup hoặc import vào website khác.', 'dxd-dealer' ),
						(int) $total
					);
					?>
				</p>
				<p style="margin-top:16px;">
					<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-database-export" style="vertical-align:middle; margin-right:4px;"></span>
						<?php esc_html_e( 'Export CSV', 'dxd-dealer' ); ?>
					</a>
				</p>
				<hr />
				<h4 style="margin-bottom:4px;"><?php esc_html_e( 'Lưu ý:', 'dxd-dealer' ); ?></h4>
				<ul style="font-size:12px; color:#666; list-style:disc; padding-left:16px;">
					<li><?php esc_html_e( 'File CSV UTF-8 (BOM) — mở trực tiếp bằng Excel.', 'dxd-dealer' ); ?></li>
					<li><?php esc_html_e( 'Gallery URLs phân cách bằng ký tự |', 'dxd-dealer' ); ?></li>
					<li><?php esc_html_e( 'Import lại file export sẽ cập nhật (không tạo trùng).', 'dxd-dealer' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
	<?php
}
