<?php
/**
 * Portfolio Gallery — CPT, Taxonomy & Admin Import UI.
 *
 * Registers `dxd_gallery` CPT and `dxd_gallery_cat` taxonomy
 * for the homepage portfolio/event gallery tabs section.
 * Includes admin page to import from WordPress WXR XML export.
 *
 * @package SPL\Features\Optimizer
 */

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

final class PortfolioGallery {

	public const POST_TYPE = 'dxd_gallery';
	public const TAXONOMY  = 'dxd_gallery_cat';

	/**
	 * Register CPT, taxonomy, and admin hooks.
	 */
	public static function register(): void {
		add_action( 'init', [ self::class, 'registerPostType' ] );
		add_action( 'init', [ self::class, 'registerTaxonomy' ] );
		add_action( 'admin_menu', [ self::class, 'registerImportPage' ] );
		add_action( 'admin_init', [ self::class, 'handleImport' ] );
	}

	// ── CPT ─────────────────────────────────────────

	/**
	 * Register the dxd_gallery CPT.
	 */
	public static function registerPostType(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'public'             => true,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'has_archive'        => false,
				'supports'           => [ 'title', 'thumbnail' ],
				'menu_icon'          => 'dashicons-images-alt2',
				'menu_position'      => 25,
				'labels'             => [
					'name'               => __( 'Hình ảnh sự kiện', 'spl' ),
					'singular_name'      => __( 'Hình ảnh', 'spl' ),
					'add_new'            => __( 'Thêm hình ảnh', 'spl' ),
					'add_new_item'       => __( 'Thêm hình ảnh mới', 'spl' ),
					'edit_item'          => __( 'Sửa hình ảnh', 'spl' ),
					'view_item'          => __( 'Xem hình ảnh', 'spl' ),
					'all_items'          => __( 'Tất cả hình ảnh', 'spl' ),
					'search_items'       => __( 'Tìm hình ảnh', 'spl' ),
					'not_found'          => __( 'Không tìm thấy hình ảnh nào.', 'spl' ),
					'not_found_in_trash' => __( 'Không có hình ảnh nào trong thùng rác.', 'spl' ),
				],
			]
		);
	}

	// ── Taxonomy ────────────────────────────────────

	/**
	 * Register the dxd_gallery_cat taxonomy.
	 */
	public static function registerTaxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			[
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'labels'            => [
					'name'              => __( 'Danh mục hình ảnh', 'spl' ),
					'singular_name'     => __( 'Danh mục', 'spl' ),
					'search_items'      => __( 'Tìm danh mục', 'spl' ),
					'all_items'         => __( 'Tất cả danh mục', 'spl' ),
					'parent_item'       => __( 'Danh mục cha', 'spl' ),
					'parent_item_colon' => __( 'Danh mục cha:', 'spl' ),
					'edit_item'         => __( 'Sửa danh mục', 'spl' ),
					'update_item'       => __( 'Cập nhật danh mục', 'spl' ),
					'add_new_item'      => __( 'Thêm danh mục mới', 'spl' ),
					'new_item_name'     => __( 'Tên danh mục mới', 'spl' ),
					'menu_name'         => __( 'Danh mục', 'spl' ),
				],
			]
		);
	}

	// ── Admin Import Page ───────────────────────────

	/**
	 * Add Import submenu under "Hình ảnh sự kiện".
	 */
	public static function registerImportPage(): void {
		add_submenu_page(
			'edit.php?post_type=' . self::POST_TYPE,
			__( 'Import từ Flatsome', 'spl' ),
			__( '📥 Import XML', 'spl' ),
			'manage_options',
			'dxd-gallery-import',
			[ self::class, 'renderImportPage' ]
		);
	}

	/**
	 * Render the admin import page.
	 */
	public static function renderImportPage(): void {
		$results = get_transient( '_dxd_gallery_import_results' );
		if ( $results ) {
			delete_transient( '_dxd_gallery_import_results' );
		}

		$existing_count = wp_count_posts( self::POST_TYPE )->publish ?? 0;
		$term_count     = wp_count_terms( [ 'taxonomy' => self::TAXONOMY, 'hide_empty' => false ] );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">📥 Import Hình ảnh sự kiện từ Flatsome</h1>
			<hr class="wp-header-end">

			<?php if ( $results ) : ?>
				<div class="notice notice-<?php echo $results['errors'] ? 'warning' : 'success'; ?> is-dismissible">
					<p>
						<strong>✅ Import hoàn tất!</strong><br>
						📝 Tạo mới: <strong><?php echo (int) $results['created']; ?></strong> hình ảnh<br>
						⏭ Bỏ qua (đã tồn tại): <strong><?php echo (int) $results['skipped']; ?></strong><br>
						📁 Danh mục: <strong><?php echo (int) $results['terms']; ?></strong><br>
						🖼️ Ảnh tải về: <strong><?php echo (int) $results['images_ok']; ?></strong>
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
				<h2>📊 Hiện có</h2>
				<p>
					<strong><?php echo (int) $existing_count; ?></strong> hình ảnh |
					<strong><?php echo (int) $term_count; ?></strong> danh mục
				</p>
			</div>

			<div class="card" style="max-width:700px;margin-top:20px;padding:20px">
				<h2>📁 Upload file XML</h2>
				<p>Chọn file <code>.xml</code> đã export từ trang cũ (dailyxedien.vn) → Tools → Export → Featured Items.</p>

				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'dxd_gallery_import', 'dxd_gallery_import_nonce' ); ?>

					<table class="form-table" role="presentation">
						<tr>
							<th><label for="import_xml">File XML</label></th>
							<td><input type="file" name="import_xml" id="import_xml" accept=".xml" required></td>
						</tr>
						<tr>
							<th>Tùy chọn</th>
							<td>
								<label>
									<input type="checkbox" name="skip_images" value="1">
									Bỏ qua tải ảnh (import nhanh, chỉ tạo posts)
								</label>
							</td>
						</tr>
					</table>

					<?php submit_button( '🚀 Bắt đầu Import', 'primary large', 'dxd_gallery_do_import' ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	// ── Handle Import POST ──────────────────────────

	/**
	 * Process the XML import on admin_init.
	 */
	public static function handleImport(): void {
		if ( empty( $_POST['dxd_gallery_do_import'] ) ) {
			return;
		}

		if (
			! current_user_can( 'manage_options' )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['dxd_gallery_import_nonce'] ?? '' ) ),
				'dxd_gallery_import'
			)
		) {
			wp_die( esc_html__( 'Không có quyền thực hiện.', 'spl' ) );
		}

		$file = $_FILES['import_xml'] ?? null;
		if ( ! $file || UPLOAD_ERR_OK !== ( $file['error'] ?? 4 ) ) {
			wp_die( esc_html__( 'Vui lòng chọn file XML.', 'spl' ) );
		}

		// Validate file type.
		$filetype = wp_check_filetype( $file['name'], [ 'xml' => 'text/xml' ] );
		if ( ! $filetype['type'] ) {
			wp_die( esc_html__( 'File không hợp lệ. Chỉ chấp nhận .xml', 'spl' ) );
		}

		$skip_images = ! empty( $_POST['skip_images'] );
		$results     = self::processXmlImport( $file['tmp_name'], $skip_images );

		set_transient( '_dxd_gallery_import_results', $results, 60 );

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . self::POST_TYPE . '&page=dxd-gallery-import' ) );
		exit;
	}

	/**
	 * Parse WXR XML and import posts + terms + images.
	 *
	 * @param string $xml_path  Path to the uploaded XML file.
	 * @param bool   $skip_images Whether to skip image sideloading.
	 *
	 * @return array{created:int,skipped:int,terms:int,images_ok:int,images_fail:int,errors:string[]}
	 */
	private static function processXmlImport( string $xml_path, bool $skip_images ): array {
		$result = [
			'created'     => 0,
			'skipped'     => 0,
			'terms'       => 0,
			'images_ok'   => 0,
			'images_fail' => 0,
			'errors'      => [],
		];

		// Ensure media functions.
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		libxml_use_internal_errors( true );
		$xml = simplexml_load_file( $xml_path );
		if ( ! $xml ) {
			$result['errors'][] = 'Không thể phân tích file XML.';
			return $result;
		}

		$channel    = $xml->channel;
		$namespaces = $xml->getNamespaces( true );
		$wp_ns      = $namespaces['wp'] ?? 'http://wordpress.org/export/1.2/';

		// ── Pass 1: Collect attachments & featured items ──

		$attachment_map = []; // old_id => URL
		$featured_items = [];

		foreach ( $channel->item as $item ) {
			$wp        = $item->children( $wp_ns );
			$post_type = (string) $wp->post_type;

			if ( 'attachment' === $post_type ) {
				$old_id  = (int) $wp->post_id;
				$att_url = (string) $wp->attachment_url;
				if ( $old_id && $att_url ) {
					$attachment_map[ $old_id ] = $att_url;
				}
				continue;
			}

			if ( 'featured_item' !== $post_type ) {
				continue;
			}

			// Filter: only VI posts.
			$lang = '';
			foreach ( $item->category as $cat ) {
				if ( 'language' === (string) $cat['domain'] ) {
					$lang = (string) $cat['nicename'];
					break;
				}
			}
			if ( $lang && 'vi' !== $lang ) {
				continue;
			}

			// Category data.
			$cats = [];
			foreach ( $item->category as $cat ) {
				if ( 'featured_item_category' === (string) $cat['domain'] ) {
					$cats[] = [
						'slug' => (string) $cat['nicename'],
						'name' => (string) $cat,
					];
				}
			}

			// Thumbnail ID.
			$thumb_id = 0;
			foreach ( $wp->postmeta as $meta ) {
				if ( '_thumbnail_id' === (string) $meta->meta_key ) {
					$thumb_id = (int) $meta->meta_value;
					break;
				}
			}

			$featured_items[] = [
				'title'      => (string) $item->title,
				'slug'       => (string) $wp->post_name,
				'date'       => (string) $wp->post_date,
				'status'     => (string) $wp->status,
				'menu_order' => (int) $wp->menu_order,
				'thumb_id'   => $thumb_id,
				'categories' => $cats,
			];
		}

		// ── Pass 2: Create taxonomy terms ──

		$term_map    = [];
		$unique_cats = [];
		foreach ( $featured_items as $fi ) {
			foreach ( $fi['categories'] as $cat ) {
				$unique_cats[ $cat['slug'] ] = $cat['name'];
			}
		}

		foreach ( $unique_cats as $slug => $name ) {
			$existing = get_term_by( 'slug', $slug, self::TAXONOMY );
			if ( $existing ) {
				$term_map[ $slug ] = $existing->term_id;
				continue;
			}

			$term_result = wp_insert_term( $name, self::TAXONOMY, [ 'slug' => $slug ] );
			if ( is_wp_error( $term_result ) ) {
				$result['errors'][] = "Term '{$name}': " . $term_result->get_error_message();
				continue;
			}

			$term_map[ $slug ] = $term_result['term_id'];
			$result['terms']++;
		}

		// ── Pass 3: Create posts ──

		foreach ( $featured_items as $fi ) {
			if ( 'publish' !== $fi['status'] ) {
				$result['skipped']++;
				continue;
			}

			$slug = sanitize_title( $fi['slug'] );

			// Skip existing.
			$existing = get_page_by_path( $slug, OBJECT, self::POST_TYPE );
			if ( $existing ) {
				$result['skipped']++;
				continue;
			}

			$post_id = wp_insert_post( [
				'post_type'   => self::POST_TYPE,
				'post_title'  => sanitize_text_field( $fi['title'] ),
				'post_name'   => $slug,
				'post_status' => 'publish',
				'menu_order'  => $fi['menu_order'],
				'post_date'   => $fi['date'],
			], true );

			if ( is_wp_error( $post_id ) ) {
				$result['errors'][] = "Post '{$fi['title']}': " . $post_id->get_error_message();
				continue;
			}

			// Assign terms.
			$term_ids = [];
			foreach ( $fi['categories'] as $cat ) {
				if ( isset( $term_map[ $cat['slug'] ] ) ) {
					$term_ids[] = $term_map[ $cat['slug'] ];
				}
			}
			if ( $term_ids ) {
				wp_set_object_terms( $post_id, $term_ids, self::TAXONOMY );
			}

			// Sideload thumbnail.
			if ( ! $skip_images && $fi['thumb_id'] && isset( $attachment_map[ $fi['thumb_id'] ] ) ) {
				$thumb_url = $attachment_map[ $fi['thumb_id'] ];
				$attach_id = media_sideload_image( $thumb_url, $post_id, sanitize_text_field( $fi['title'] ), 'id' );
				if ( is_wp_error( $attach_id ) ) {
					$result['images_fail']++;
				} else {
					set_post_thumbnail( $post_id, $attach_id );
					$result['images_ok']++;
				}
			}

			$result['created']++;
		}

		return $result;
	}
}
