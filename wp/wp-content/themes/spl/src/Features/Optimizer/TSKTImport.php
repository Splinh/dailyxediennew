<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Handle importing product specifications (TSKT) from a flat CSV file.
 * Supports both WordPress Admin context (file upload form) and WP-CLI commands.
 *
 * @package SPL
 */
final class TSKTImport {

	/**
	 * Register hooks and WP-CLI commands.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Hook menu under Products.
		add_action( 'admin_menu', [ self::class, 'registerAdminMenu' ] );

		// Handle POST import file.
		add_action( 'admin_init', [ self::class, 'handleAdminImport' ] );

		// Register WP-CLI command if available.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'tskt import', [ self::class, 'cliImport' ] );
		}
	}

	/**
	 * Add custom submenu page under "Products" menu.
	 *
	 * @return void
	 */
	public static function registerAdminMenu(): void {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Nhập/Xuất TSKT', 'spl' ),
			__( 'Nhập/Xuất TSKT', 'spl' ),
			'edit_products',
			'tskt-import-export',
			[ self::class, 'renderAdminPage' ]
		);
	}

	/**
	 * Render the administrative page with Export and Import panels.
	 *
	 * @return void
	 */
	public static function renderAdminPage(): void {
		$export_url = wp_nonce_url( admin_url( 'admin.php?action=export_tskt' ), 'export_tskt_nonce' );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Quản lý Thông số kỹ thuật (TSKT)', 'spl' ); ?></h1>
			<hr class="wp-header-end">

			<div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px;">
				<h2><?php esc_html_e( '1. Xuất thông số kỹ thuật', 'spl' ); ?></h2>
				<p><?php esc_html_e( 'Tải về file CSV chứa toàn bộ sản phẩm và các thông số kỹ thuật hiện có dưới dạng bảng dẹt.', 'spl' ); ?></p>
				<p>
					<a href="<?php echo esc_url( $export_url ); ?>" class="button button-primary button-large">
						<?php esc_html_e( 'Xuất CSV Thông Số Kỹ Thuật', 'spl' ); ?>
					</a>
				</p>
			</div>

			<div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px;">
				<h2><?php esc_html_e( '2. Nhập thông số kỹ thuật bằng CSV', 'spl' ); ?></h2>
				<p><?php esc_html_e( 'Chọn file CSV đã chỉnh sửa (với cấu trúc cột khớp với file xuất) để cập nhật hàng loạt thông số kỹ thuật.', 'spl' ); ?></p>

				<form method="post" enctype="multipart/form-data" action="">
					<?php wp_nonce_field( 'import_tskt_nonce', 'import_tskt_field' ); ?>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="tskt_csv_file"><?php esc_html_e( 'Chọn file CSV (.csv)', 'spl' ); ?></label>
							</th>
							<td>
								<input type="file" name="tskt_csv" id="tskt_csv_file" accept=".csv" required />
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="submit_tskt_import" class="button button-primary button-large" value="<?php esc_attr_e( 'Bắt đầu Nhập CSV', 'spl' ); ?>" />
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle admin post request for importing file.
	 *
	 * @return void
	 */
	public static function handleAdminImport(): void {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! isset( $_POST['submit_tskt_import'] ) ) {
			return;
		}

		if ( ! isset( $_POST['import_tskt_field'] ) || ! wp_verify_nonce( wp_unslash( $_POST['import_tskt_field'] ), 'import_tskt_nonce' ) ) {
			wp_die( esc_html__( 'Phiên xác thực lỗi. Vui lòng thử lại.', 'spl' ) );
		}

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền thực hiện hành động này.', 'spl' ) );
		}

		if ( empty( $_FILES['tskt_csv']['tmp_name'] ) ) {
			add_action( 'admin_notices', static function () {
				Helper::messageError( 'Vui lòng chọn một file CSV hợp lệ.' );
			} );
			return;
		}

		$file_path = $_FILES['tskt_csv']['tmp_name'];
		$result = self::processCsvImport( $file_path );

		add_action( 'admin_notices', static function () use ( $result ) {
			$msg = sprintf(
				'Nhập CSV hoàn tất! Đã cập nhật %d sản phẩm, bỏ qua %d sản phẩm.',
				$result['updated'],
				$result['skipped']
			);
			Helper::messageSuccess( $msg );
		} );
	}

	/**
	 * Normalize strings for safe, accented case-insensitive comparison.
	 *
	 * @param string $str Original string.
	 * @return string Normalized string.
	 */
	private static function normalizeString( string $str ): string {
		$str = html_entity_decode( $str, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$str = preg_replace( '/\s+/u', ' ', $str );
		return trim( mb_strtolower( $str, 'UTF-8' ) );
	}

	/**
	 * Process CSV file import.
	 *
	 * @param string $filePath Absolute path to CSV file.
	 * @return array{updated: int, skipped: int}
	 */
	public static function processCsvImport( string $filePath ): array {
		if ( ! file_exists( $filePath ) || ! is_readable( $filePath ) ) {
			return [ 'updated' => 0, 'skipped' => 0 ];
		}

		$content = file_get_contents( $filePath );
		if ( false === $content ) {
			return [ 'updated' => 0, 'skipped' => 0 ];
		}

		// Detect and handle encoding/BOM
		$first2 = substr( $content, 0, 2 );
		if ( "\xFF\xFE" === $first2 ) {
			$content = mb_convert_encoding( substr( $content, 2 ), 'UTF-8', 'UTF-16LE' );
		} elseif ( "\xFE\xFF" === $first2 ) {
			$content = mb_convert_encoding( substr( $content, 2 ), 'UTF-8', 'UTF-16BE' );
		} elseif ( "\xEF\xBB\xBF" === substr( $content, 0, 3 ) ) {
			$content = substr( $content, 3 );
		}

		// Detect delimiter from first line
		$lines = preg_split( '/\r\n|\r|\n/', $content );
		if ( empty( $lines ) || empty( $lines[0] ) ) {
			return [ 'updated' => 0, 'skipped' => 0 ];
		}

		$first_line = $lines[0];
		$comma_count = substr_count( $first_line, ',' );
		$semicolon_count = substr_count( $first_line, ';' );
		$tab_count = substr_count( $first_line, "\t" );

		$delimiter = ',';
		if ( $semicolon_count > $comma_count && $semicolon_count > $tab_count ) {
			$delimiter = ';';
		} elseif ( $tab_count > $comma_count && $tab_count > $semicolon_count ) {
			$delimiter = "\t";
		}

		// Save UTF-8 content to temp stream for fgetcsv
		$handle = fopen( 'php://temp', 'r+' );
		if ( ! $handle ) {
			return [ 'updated' => 0, 'skipped' => 0 ];
		}
		fwrite( $handle, $content );
		rewind( $handle );

		// Set locale to prevent Windows fgetcsv multibyte issues
		$old_locale = setlocale( LC_CTYPE, '0' );
		setlocale( LC_CTYPE, '.UTF8' );

		$headers = fgetcsv( $handle, 0, $delimiter );
		if ( ! $headers ) {
			fclose( $handle );
			setlocale( LC_CTYPE, $old_locale );
			return [ 'updated' => 0, 'skipped' => 0 ];
		}

		// Normalize headers to identify columns
		$headers = array_map( 'trim', $headers );

		$updated_count = 0;
		$skipped_count = 0;

		while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
			if ( empty( $row ) ) {
				continue;
			}

			// Map row to headers
			$data = [];
			foreach ( $headers as $index => $header ) {
				$data[ $header ] = $row[ $index ] ?? '';
			}

			// Identify product ID or SKU
			$product_id = isset( $data['ID'] ) ? (int) $data['ID'] : 0;
			$sku = isset( $data['SKU'] ) ? trim( (string) $data['SKU'] ) : '';

			$product = null;

			if ( $product_id > 0 ) {
				$product = wc_get_product( $product_id );
			}

			if ( ! $product && ! empty( $sku ) ) {
				$found_id = wc_get_product_id_by_sku( $sku );
				if ( $found_id > 0 ) {
					$product = wc_get_product( $found_id );
					$product_id = $found_id;
				}
			}

			if ( ! $product ) {
				$skipped_count++;
				continue;
			}

			// Parse specifications
			$specs = [];
			foreach ( $data as $key => $val ) {
				$key = trim( (string) $key );
				$val = trim( (string) $val );

				// Skip non-specification meta columns
				if ( in_array( strtoupper( $key ), [ 'ID', 'SKU', 'TITLE' ], true ) ) {
					continue;
				}

				if ( '' !== $val && '' !== $key ) {
					$specs[] = [
						'tskt_label' => $key,
						'tskt_value' => $val,
					];
				}
			}

			if ( ! empty( $specs ) ) {
				update_field( 'tskt_rows', $specs, $product_id );
				$updated_count++;
			} else {
				$skipped_count++;
			}
		}

		fclose( $handle );
		setlocale( LC_CTYPE, $old_locale );

		return [
			'updated' => $updated_count,
			'skipped' => $skipped_count,
		];
	}

	/**
	 * WP-CLI Import command callback.
	 *
	 * @param array $args Unnamed arguments.
	 * @param array $assoc_args Named parameters.
	 * @return void
	 */
	public static function cliImport( array $args, array $assoc_args ): void {
		$file = $args[0] ?? '';
		if ( empty( $file ) || ! file_exists( $file ) ) {
			\WP_CLI::error( 'Vui lòng cung cấp đường dẫn file CSV hợp lệ.' );
		}

		\WP_CLI::line( 'Đang nhập thông số kỹ thuật từ: ' . $file );
		$result = self::processCsvImport( $file );

		\WP_CLI::success(
			sprintf(
				'Nhập thành công! Đã cập nhật %d sản phẩm, bỏ qua %d sản phẩm.',
				$result['updated'],
				$result['skipped']
			)
		);
	}
}
