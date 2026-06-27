<?php
/**
 * Post Importer Module.
 *
 * Imports posts with categories, tags, featured images, and SEO metadata from JSON.
 *
 * @package SPL\Features\Optimizer
 */

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

final class PostImporter {

	/**
	 * Register hook listeners.
	 */
	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'registerImportPage' ] );
		add_action( 'admin_init', [ self::class, 'handleImport' ] );
	}

	/**
	 * Add Import submenu under "Posts" (Tin tức).
	 */
	public static function registerImportPage(): void {
		add_submenu_page(
			'edit.php',
			__( 'Import Tin Tức', 'spl' ),
			__( '📥 Import JSON', 'spl' ),
			'manage_options',
			'dxd-post-import',
			[ self::class, 'renderImportPage' ]
		);
	}

	/**
	 * Render the admin import page.
	 */
	public static function renderImportPage(): void {
		$results = get_transient( '_dxd_post_import_results' );
		if ( $results ) {
			delete_transient( '_dxd_post_import_results' );
		}

		$existing_count = wp_count_posts( 'post' )->publish ?? 0;
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">📥 Import Tin Tức từ dailyxedien.vn</h1>
			<hr class="wp-header-end">

			<?php if ( $results ) : ?>
				<div class="notice notice-<?php echo ! empty( $results['errors'] ) ? 'warning' : 'success'; ?> is-dismissible">
					<p>
						<strong>✅ Import hoàn tất!</strong><br>
						📝 Tạo mới: <strong><?php echo (int) $results['created']; ?></strong> bài viết<br>
						🔄 Cập nhật/Ghi đè: <strong><?php echo (int) $results['updated']; ?></strong> bài viết<br>
						skip Bỏ qua (đã tồn tại): <strong><?php echo (int) $results['skipped']; ?></strong><br>
						🖼️ Ảnh đại diện tải về: <strong><?php echo (int) $results['images_ok']; ?></strong>
						<?php if ( $results['images_fail'] ) : ?>
							| ⚠ Lỗi ảnh: <strong><?php echo (int) $results['images_fail']; ?></strong>
						<?php endif; ?>
						<?php if ( ! empty( $results['errors'] ) ) : ?>
							<br>⚠ Lỗi: <?php echo esc_html( implode( '; ', $results['errors'] ) ); ?>
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="card" style="max-width:700px;margin-top:20px;padding:20px">
				<h2>📊 Thống kê hiện tại</h2>
				<p>
					Đang có: <strong><?php echo (int) $existing_count; ?></strong> bài viết đã xuất bản.
				</p>
			</div>

			<div class="card" style="max-width:700px;margin-top:20px;padding:20px">
				<h2>📁 Upload file JSON</h2>
				<p>Chọn file <code>.json</code> đã export từ plugin <strong>DXD Exporter</strong> ở trang cũ.</p>

				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'dxd_post_import', 'dxd_post_import_nonce' ); ?>

					<table class="form-table" role="presentation">
						<tr>
							<th><label for="import_json">File JSON</label></th>
							<td><input type="file" name="import_json" id="import_json" accept=".json" required></td>
						</tr>
						<tr>
							<th>Ghi đè bài viết</th>
							<td>
								<label>
									<input type="checkbox" name="overwrite" value="1">
									Cập nhật/Ghi đè nếu trùng slug (để cập nhật lại SEO meta hoặc nội dung)
								</label>
							</td>
						</tr>
						<tr>
							<th>Tải hình ảnh</th>
							<td>
								<label>
									<input type="checkbox" name="skip_images" value="1">
									Bỏ qua tải ảnh đại diện (import nhanh hơn)
								</label>
							</td>
						</tr>
					</table>

					<?php submit_button( '🚀 Bắt đầu Import', 'primary large', 'dxd_post_do_import' ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Process the JSON import on admin_init.
	 */
	public static function handleImport(): void {
		if ( empty( $_POST['dxd_post_do_import'] ) ) {
			return;
		}

		if (
			! current_user_can( 'manage_options' )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['dxd_post_import_nonce'] ?? '' ) ),
				'dxd_post_import'
			)
		) {
			wp_die( esc_html__( 'Không có quyền thực hiện.', 'spl' ) );
		}

		$file = $_FILES['import_json'] ?? null;
		if ( ! $file || UPLOAD_ERR_OK !== ( $file['error'] ?? 4 ) ) {
			wp_die( esc_html__( 'Vui lòng chọn file JSON hợp lệ.', 'spl' ) );
		}

		// Validate file type (JSON).
		$filetype = wp_check_filetype( $file['name'], [ 'json' => 'application/json' ] );
		if ( ! $filetype['type'] ) {
			wp_die( esc_html__( 'File không hợp lệ. Chỉ chấp nhận .json', 'spl' ) );
		}

		$overwrite   = ! empty( $_POST['overwrite'] );
		$skip_images = ! empty( $_POST['skip_images'] );

		$results = self::processJsonImport( $file['tmp_name'], $overwrite, $skip_images );

		set_transient( '_dxd_post_import_results', $results, 60 );

		wp_safe_redirect( admin_url( 'edit.php?page=dxd-post-import' ) );
		exit;
	}

	/**
	 * Parse JSON and import posts + terms + images.
	 */
	public static function processJsonImport( string $file_path, bool $overwrite, bool $skip_images ): array {
		$content = file_get_contents( $file_path );
		$data    = json_decode( $content, true );

		$results = [
			'created'     => 0,
			'updated'     => 0,
			'skipped'     => 0,
			'images_ok'   => 0,
			'images_fail' => 0,
			'errors'      => [],
		];

		if ( ! is_array( $data ) ) {
			$results['errors'][] = 'Dữ liệu file JSON không đúng định dạng.';
			return $results;
		}

		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		foreach ( $data as $item ) {
			$title  = sanitize_text_field( $item['title'] ?? '' );
			$slug   = sanitize_title( $item['slug'] ?? '' );
			$status = sanitize_text_field( $item['status'] ?? 'publish' );

			if ( ! $title || ! $slug ) {
				continue;
			}

			// Check existing post by slug.
			$existing = get_page_by_path( $slug, OBJECT, 'post' );
			$post_id  = 0;
			$is_update = false;

			if ( $existing ) {
				if ( ! $overwrite ) {
					$results['skipped']++;
					continue;
				}
				$post_id   = $existing->ID;
				$is_update = true;
			}

			$post_data = [
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $item['content'] ?? '',
				'post_excerpt' => $item['excerpt'] ?? '',
				'post_status'  => $status,
				'post_date'    => $item['date'] ?? date( 'Y-m-d H:i:s' ),
				'post_type'    => 'post',
			];

			if ( $is_update ) {
				$post_data['ID'] = $post_id;
				$res = wp_update_post( $post_data, true );
			} else {
				$res = wp_insert_post( $post_data, true );
			}

			if ( is_wp_error( $res ) ) {
				$results['errors'][] = "Lỗi khi xử lý bài viết '{$title}': " . $res->get_error_message();
				continue;
			}

			$post_id = $res;

			// Handle Categories.
			$cat_ids = [];
			if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
				foreach ( $item['categories'] as $cat ) {
					$c_name = sanitize_text_field( $cat['name'] );
					$c_slug = sanitize_title( $cat['slug'] );
					$term   = get_term_by( 'slug', $c_slug, 'category' );
					if ( $term ) {
						$cat_ids[] = $term->term_id;
					} else {
						$new_term = wp_insert_term( $c_name, 'category', [ 'slug' => $c_slug ] );
						if ( ! is_wp_error( $new_term ) ) {
							$cat_ids[] = $new_term['term_id'];
						}
					}
				}
			}
			if ( ! empty( $cat_ids ) ) {
				wp_set_post_categories( $post_id, $cat_ids );
			}

			// Handle Tags.
			$tag_names = [];
			if ( ! empty( $item['tags'] ) && is_array( $item['tags'] ) ) {
				foreach ( $item['tags'] as $tag ) {
					$tag_names[] = sanitize_text_field( $tag['name'] );
				}
			}
			if ( ! empty( $tag_names ) ) {
				wp_set_post_tags( $post_id, $tag_names, true );
			}

			// Handle SEO Meta.
			if ( ! empty( $item['seo_meta'] ) && is_array( $item['seo_meta'] ) ) {
				foreach ( $item['seo_meta'] as $key => $val ) {
					update_post_meta( $post_id, sanitize_key( $key ), wp_kses_post( $val ) );
				}
			}

			// Handle Featured Image.
			if ( ! $skip_images && ! empty( $item['featured_image'] ) ) {
				// Avoid downloading again if it already has a thumbnail (on overwrite).
				if ( ! $is_update || ! has_post_thumbnail( $post_id ) ) {
					$img_url   = esc_url_raw( $item['featured_image'] );
					$attach_id = media_sideload_image( $img_url, $post_id, $title, 'id' );
					if ( is_wp_error( $attach_id ) ) {
						$results['images_fail']++;
					} else {
						set_post_thumbnail( $post_id, $attach_id );
						$results['images_ok']++;
					}
				}
			}

			if ( $is_update ) {
				$results['updated']++;
			} else {
				$results['created']++;
			}
		}

		return $results;
	}
}
