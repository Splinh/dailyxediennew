<?php
/**
 * Product Import module – Import sản phẩm WooCommerce + ACF TSKT từ XML export.
 *
 * Reads WXR XML exported from the old site (dailyxedien.vn),
 * imports products, variations, taxonomy terms, product attributes,
 * featured/gallery images (via sideload), and ACF repeater tskt_rows.
 *
 * Toggle ON/OFF in HDA Settings → Modules.
 *
 * @package HDAddons\Modules\ProductImport
 */

namespace HDAddons\Modules\ProductImport;

use HDAddons\Modules\AbstractModule;
use HDAddons\Plugin;

defined( 'ABSPATH' ) || exit;

final class ProductImportModule extends AbstractModule {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'product_import';
	}

	public static function title(): string {
		return 'Product Import';
	}

	public static function description(): string {
		return 'Import sản phẩm WooCommerce + ACF TSKT từ XML export site cũ.';
	}

	public static function group(): string {
		return 'tools';
	}

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		// Admin only — WC availability checked at render time.
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'registerMenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'wp_ajax_spl_import_parse', [ $this, 'ajaxParse' ] );
		add_action( 'wp_ajax_spl_import_execute', [ $this, 'ajaxExecute' ] );
		add_action( 'wp_ajax_spl_import_search_products', [ $this, 'ajaxSearchProducts' ] );
	}

	// ── Admin Menu ──────────────────────────────────

	public function registerMenu(): void {
		add_management_page(
			'Import Sản Phẩm + ACF',
			'📦 Import SP + ACF',
			Plugin::CAPABILITY,
			'spl-product-import',
			[ $this, 'renderPage' ]
		);
	}

	// ── Enqueue ─────────────────────────────────────

	public function enqueueAssets( string $hook ): void {
		if ( ! str_contains( $hook, 'spl-product-import' ) ) {
			return;
		}

		wp_enqueue_style( 'spl-product-import', false );
		wp_add_inline_style( 'spl-product-import', $this->inlineCss() );
	}

	// ── AJAX: Parse XML (preview) ───────────────────

	public function ajaxParse(): void {
		check_ajax_referer( 'spl_product_import', 'nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( 'No permission' );
		}

		$file = $_FILES['xml_file'] ?? null;
		if ( ! $file || UPLOAD_ERR_OK !== $file['error'] ) {
			wp_send_json_error( 'Upload file thất bại' );
		}

		$xml_content = file_get_contents( $file['tmp_name'] );
		if ( ! $xml_content ) {
			wp_send_json_error( 'Không đọc được file' );
		}

		$items = ImportParser::parse( $xml_content );
		if ( empty( $items ) ) {
			wp_send_json_error( 'Không tìm thấy sản phẩm nào trong XML' );
		}

		// Save XML to temp for later import.
		$upload_dir = wp_upload_dir();
		$temp_file  = $upload_dir['basedir'] . '/import-temp-' . wp_hash( (string) time() ) . '.xml';
		file_put_contents( $temp_file, $xml_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		// Build preview data.
		$preview   = [];
		$att_count = 0;

		foreach ( $items as $item ) {
			if ( 'attachment' === $item['post_type'] ) {
				++$att_count;
				continue;
			}

			if ( 'product' !== $item['post_type'] && 'product_variation' !== $item['post_type'] ) {
				continue;
			}

			$tskt_count = 0;
			foreach ( $item['meta'] as $k => $v ) {
				if ( preg_match( '/^tskt_rows_\d+_tskt_label$/', $k ) ) {
					++$tskt_count;
				}
			}

			$cats = [];
			foreach ( $item['terms'] as $t ) {
				if ( 'category' === $t['domain'] || 'product_cat' === $t['domain'] ) {
					$cats[] = $t['name'];
				}
			}

			$preview[] = [
				'id'     => $item['post_id'],
				'title'  => $item['title'],
				'type'   => $item['post_type'],
				'status' => $item['status'],
				'parent' => $item['post_parent'],
				'tskt'   => $tskt_count,
				'cats'   => implode( ', ', $cats ),
			];
		}

		wp_send_json_success( [
			'products'    => $preview,
			'attachments' => $att_count,
			'temp_file'   => basename( $temp_file ),
			'total_items' => count( $items ),
		] );
	}

	// ── AJAX: Execute Import ────────────────────────

	public function ajaxExecute(): void {
		check_ajax_referer( 'spl_product_import', 'nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( 'No permission' );
		}

		@set_time_limit( 600 );   // phpcs:ignore
		wp_raise_memory_limit( 'admin' );

		$temp_name   = sanitize_file_name( $_POST['temp_file'] ?? '' );
		$upload_dir  = wp_upload_dir();
		$temp_file   = $upload_dir['basedir'] . '/' . $temp_name;
		$import_imgs = ! empty( $_POST['import_images'] );
		$skip_exist  = ! empty( $_POST['skip_existing'] );

		if ( ! file_exists( $temp_file ) ) {
			wp_send_json_error( 'File tạm không tồn tại. Vui lòng upload lại.' );
		}

		$xml_content = file_get_contents( $temp_file ); // phpcs:ignore
		$items       = ImportParser::parse( $xml_content );

		if ( empty( $items ) ) {
			wp_send_json_error( 'Không có dữ liệu' );
		}

		$engine  = new ImportEngine();
		$results = $engine->run( $items, $import_imgs, $skip_exist );

		@unlink( $temp_file ); // phpcs:ignore

		wp_send_json_success( $results );
	}

	// ── AJAX: Search existing products ──────────────

	public function ajaxSearchProducts(): void {
		check_ajax_referer( 'spl_product_import', 'nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( 'No permission' );
		}

		$search   = sanitize_text_field( $_GET['q'] ?? '' );
		$page     = max( 1, (int) ( $_GET['page'] ?? 1 ) );
		$per_page = 30;

		$args = [
			'limit'   => $per_page,
			'offset'  => ( $page - 1 ) * $per_page,
			'status'  => 'any',
			'type'    => [ 'simple', 'variable', 'external', 'grouped' ],
			'orderby' => 'title',
			'order'   => 'ASC',
			'return'  => 'objects',
		];

		if ( $search ) {
			$args['s'] = $search;
		}

		$products = wc_get_products( $args );
		$results  = [];

		foreach ( $products as $p ) {
			$text = $p->get_name();
			if ( $p->get_sku() ) {
				$text .= ' [' . $p->get_sku() . ']';
			}

			$results[] = [ 'id' => $p->get_id(), 'text' => $text ];
		}

		wp_send_json( [ 'results' => $results ] );
	}

	// ── Render Page ─────────────────────────────────

	public function renderPage(): void {
		if ( ! function_exists( 'WC' ) || ! WC() ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>WooCommerce chưa được kích hoạt!</p></div></div>';
			return;
		}

		$nonce = wp_create_nonce( 'spl_product_import' );
		require __DIR__ . '/views/import-page.php';
	}

	// ── Inline CSS ──────────────────────────────────

	private function inlineCss(): string {
		return <<<'CSS'
#spl-import-app { max-width: 1100px; }
.imp-card {
	background: #fff; border: 1px solid #c3c4c7; border-radius: 8px;
	padding: 24px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.imp-card h2 { margin-top: 0; color: #1d2327; }
.imp-stats { display: flex; gap: 16px; flex-wrap: wrap; margin: 16px 0; }
.imp-stat {
	background: #f8f9fa; padding: 10px 18px; border-radius: 6px;
	border: 1px solid #dee2e6; font-size: 13px;
}
.imp-stat strong { color: #1e73be; font-size: 18px; display: block; }
.imp-stat.success { background: #d4edda; border-color: #c3e6cb; }
.imp-stat.error   { background: #f8d7da; border-color: #f5c6cb; }
.imp-status { margin-top: 12px; padding: 10px; border-radius: 6px; }
.imp-status.error   { background: #f8d7da; color: #721c24; }
.imp-status.success { background: #d4edda; color: #155724; }
.imp-table-wrap {
	max-height: 400px; overflow-y: auto; margin: 16px 0;
	border: 1px solid #ddd; border-radius: 4px;
}
.imp-badge {
	display: inline-block; padding: 2px 8px; border-radius: 4px;
	font-size: 12px; background: #e9ecef;
}
.imp-badge.success { background: #d4edda; color: #155724; }
.imp-options { margin: 16px 0; display: flex; gap: 24px; flex-wrap: wrap; }
.imp-options label { font-size: 13px; cursor: pointer; }
.imp-progress {
	background: #e9ecef; border-radius: 8px; height: 24px;
	overflow: hidden; margin: 16px 0;
}
.imp-progress-bar {
	background: linear-gradient(90deg, #1e73be, #2196f3);
	height: 100%; width: 0; border-radius: 8px; transition: width .5s ease;
}
.imp-log {
	max-height: 300px; overflow-y: auto; background: #f8f9fa;
	border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; margin-top: 12px;
}
.imp-log-line { padding: 4px 8px; margin: 2px 0; font-size: 13px; border-radius: 3px; }
.imp-log-line:nth-child(odd) { background: #fff; }
.imp-log-line.error { background: #f8d7da; color: #721c24; }
#imp-file { margin-right: 12px; }
CSS;
	}
}
