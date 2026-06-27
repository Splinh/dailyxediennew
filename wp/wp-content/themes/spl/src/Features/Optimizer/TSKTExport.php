<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Handle exporting product specifications (TSKT) to a flat CSV file.
 * Supports both WordPress Admin context (browser download) and WP-CLI commands.
 *
 * @package SPL
 */
final class TSKTExport {

	/**
	 * Register hooks and WP-CLI commands.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Hook export action handling on admin_init.
		add_action( 'admin_init', [ self::class, 'handleAdminExport' ] );

		// Register WP-CLI command if available.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'tskt export', [ self::class, 'cliExport' ] );
		}
	}

	/**
	 * Handle CSV export request from WP Admin dashboard.
	 *
	 * @return void
	 */
	public static function handleAdminExport(): void {
		if ( ! is_admin() ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
		if ( 'export_tskt' !== $action ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'export_tskt_nonce' ) ) {
			wp_die( esc_html__( 'Phiên làm việc đã hết hạn. Vui lòng thử lại.', 'spl' ) );
		}

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền thực hiện hành động này.', 'spl' ) );
		}

		self::triggerBrowserDownload();
		exit;
	}

	/**
	 * Emit HTTP headers and stream CSV to browser.
	 *
	 * @return void
	 */
	public static function triggerBrowserDownload(): void {
		$filename = 'tskt-specifications-' . date( 'Y-m-d-H-i-s' ) . '.csv';

		header( 'Content-Encoding: UTF-8' );
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		if ( ! $output ) {
			return;
		}

		// Write UTF-8 BOM for correct display in Excel.
		fwrite( $output, "\xEF\xBB\xBF" );

		self::writeCsvToStream( $output );
		fclose( $output );
	}

	/**
	 * Write CSV data to a given stream.
	 *
	 * @param resource $stream File or memory stream.
	 * @return void
	 */
	public static function writeCsvToStream( $stream ): void {
		// 1. Fetch all products.
		$products = get_posts( [
			'post_type'      => 'product',
			'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
			'posts_per_page' => -1,
		] );

		// 2. Discover all unique spec labels.
		$unique_labels = [];
		$product_specs = [];

		foreach ( $products as $post ) {
			$specs = Helper::getField( 'tskt_rows', $post->ID );
			if ( ! empty( $specs ) && is_array( $specs ) ) {
				$product_specs[ $post->ID ] = [];
				foreach ( $specs as $row ) {
					$label = trim( (string) ( $row['tskt_label'] ?? '' ) );
					$value = trim( (string) ( $row['tskt_value'] ?? '' ) );

					if ( '' !== $label ) {
						$unique_labels[ $label ] = true;
						$product_specs[ $post->ID ][ $label ] = $value;
					}
				}
			}
		}

		$sorted_labels = array_keys( $unique_labels );
		sort( $sorted_labels );

		// 3. Write CSV Header.
		$headers = array_merge( [ 'ID', 'SKU', 'Title' ], $sorted_labels );
		fputcsv( $stream, $headers );

		// 4. Write CSV Rows.
		foreach ( $products as $post ) {
			$product = wc_get_product( $post->ID );
			if ( ! $product ) {
				continue;
			}

			$row_data = [
				$post->ID,
				$product->get_sku(),
				$post->post_title,
			];

			$specs_map = $product_specs[ $post->ID ] ?? [];
			foreach ( $sorted_labels as $label ) {
				$row_data[] = $specs_map[ $label ] ?? '';
			}

			fputcsv( $stream, $row_data );
		}
	}

	/**
	 * WP-CLI Export command callback.
	 *
	 * @param array $args Unnamed arguments.
	 * @param array $assoc_args Named parameters.
	 * @return void
	 */
	public static function cliExport( array $args, array $assoc_args ): void {
		$file = $assoc_args['file'] ?? '';
		if ( empty( $file ) ) {
			\WP_CLI::error( 'Vui lòng cung cấp tham số --file=<duong_dan_file.csv>' );
		}

		$stream = fopen( $file, 'w' );
		if ( ! $stream ) {
			\WP_CLI::error( 'Không thể ghi vào file: ' . $file );
		}

		// Write BOM
		fwrite( $stream, "\xEF\xBB\xBF" );

		self::writeCsvToStream( $stream );
		fclose( $stream );

		\WP_CLI::success( 'Đã xuất thông số kỹ thuật ra file: ' . $file );
	}
}
