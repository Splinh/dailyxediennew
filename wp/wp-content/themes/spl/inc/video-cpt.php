<?php
/**
 * Custom Post Type: Video & Taxonomy: Video Category
 * For DailyXeDien Video Hub
 *
 * @package SPL
 * @author  SPL
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Custom Post Type 'video' & Taxonomy 'video-cat'
 */
add_action( 'init', 'spl_register_video_cpt_and_taxonomies', 5 );

function spl_register_video_cpt_and_taxonomies(): void {
	// 1. Taxonomy: Video Category (video-cat)
	$tax_labels = [
		'name'              => __( 'Danh mục Video', 'spl' ),
		'singular_name'     => __( 'Danh mục Video', 'spl' ),
		'search_items'      => __( 'Tìm danh mục', 'spl' ),
		'all_items'         => __( 'Tất cả danh mục', 'spl' ),
		'parent_item'       => __( 'Danh mục cha', 'spl' ),
		'parent_item_colon' => __( 'Danh mục cha:', 'spl' ),
		'edit_item'         => __( 'Chỉnh sửa danh mục', 'spl' ),
		'update_item'       => __( 'Cập nhật danh mục', 'spl' ),
		'add_new_item'      => __( 'Thêm danh mục mới', 'spl' ),
		'new_item_name'     => __( 'Tên danh mục mới', 'spl' ),
		'menu_name'         => __( 'Danh mục Video', 'spl' ),
	];

	register_taxonomy(
		'video-cat',
		[ 'video' ],
		[
			'hierarchical'      => true,
			'labels'            => $tax_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [
				'slug'       => 'video-cat',
				'with_front' => false,
			],
			'show_in_rest'      => true,
		]
	);

	// 2. Custom Post Type: Video (video)
	$cpt_labels = [
		'name'               => __( 'Video Đại Lý Xe Điện', 'spl' ),
		'singular_name'      => __( 'Video', 'spl' ),
		'menu_name'          => __( 'Video', 'spl' ),
		'name_admin_bar'     => __( 'Video', 'spl' ),
		'add_new'            => __( 'Thêm Video mới', 'spl' ),
		'add_new_item'       => __( 'Thêm Video mới', 'spl' ),
		'new_item'           => __( 'Video mới', 'spl' ),
		'edit_item'          => __( 'Chỉnh sửa Video', 'spl' ),
		'view_item'          => __( 'Xem Video', 'spl' ),
		'all_items'          => __( 'Tất cả Video', 'spl' ),
		'search_items'       => __( 'Tìm kiếm Video', 'spl' ),
		'not_found'          => __( 'Không tìm thấy video nào', 'spl' ),
		'not_found_in_trash' => __( 'Không có video nào trong thùng rác', 'spl' ),
	];

	register_post_type(
		'video',
		[
			'labels'             => $cpt_labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => [
				'slug'       => 'videos',
				'with_front' => false,
			],
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 21,
			'menu_icon'          => 'dashicons-video-alt3',
			'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
			'show_in_rest'       => true,
			'taxonomies'         => [ 'video-cat' ],
		]
	);
}

/**
 * Add 'menu_order' (Thứ tự) column to Video admin list table
 */
add_filter( 'manage_video_posts_columns', 'spl_video_admin_order_column' );
function spl_video_admin_order_column( array $columns ): array {
	$columns['menu_order'] = __( 'Thứ tự', 'spl' );
	return $columns;
}

add_action( 'manage_video_posts_custom_column', 'spl_video_admin_order_column_value', 10, 2 );
function spl_video_admin_order_column_value( string $column, int $post_id ): void {
	if ( $column === 'menu_order' ) {
		echo (int) get_post_field( 'menu_order', $post_id );
	}
}

add_filter( 'manage_edit-video_sortable_columns', 'spl_video_admin_order_sortable' );
function spl_video_admin_order_sortable( array $columns ): array {
	$columns['menu_order'] = 'menu_order';
	return $columns;
}

/**
 * Admin list default sort by menu_order ASC, date DESC
 */
add_action( 'pre_get_posts', 'spl_video_admin_default_order' );
function spl_video_admin_default_order( WP_Query $query ): void {
	if ( is_admin() && $query->is_main_query() && $query->get( 'post_type' ) === 'video' ) {
		if ( ! isset( $_GET['orderby'] ) ) {
			$query->set( 'orderby', [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			] );
		}
	}
}

/**
 * Seed default video categories if empty
 */
add_action( 'admin_init', 'spl_seed_video_categories' );

function spl_seed_video_categories(): void {
	if ( get_option( 'spl_video_cats_seeded_v3' ) ) {
		return;
	}

	$default_cats = [
		[
			'name' => 'Giới thiệu & Đánh giá Xe',
			'slug' => 'gioi-thieu-xe',
		],
		[
			'name' => 'Nhà máy & Dây chuyền SX',
			'slug' => 'nha-may-san-xuat',
		],
		[
			'name' => 'Sự kiện & Hoạt động',
			'slug' => 'su-kien-hoat-dong',
		],
		[
			'name' => 'Video Ngắn (TikTok / Shorts)',
			'slug' => 'tiktok-shorts',
		],
		[
			'name' => 'Hướng dẫn sử dụng & Mẹo xe',
			'slug' => 'huong-dan-su-dung',
		],
		[
			'name' => 'Phóng sự & Báo chí',
			'slug' => 'phong-su-bao-chi',
		],
	];

	foreach ( $default_cats as $cat ) {
		if ( ! term_exists( $cat['slug'], 'video-cat' ) ) {
			wp_insert_term( $cat['name'], 'video-cat', [ 'slug' => $cat['slug'] ] );
		}
	}

	update_option( 'spl_video_cats_seeded_v3', 1 );
}

/**
 * Extract YouTube ID from various YouTube URL formats (standard, shorts, embed, youtu.be)
 *
 * @param mixed $url
 * @return string|false
 */
function spl_extract_youtube_id( mixed $url ): string|false {
	if ( empty( $url ) || ! is_string( $url ) ) {
		return false;
	}

	$url = trim( $url );

	$patterns = [
		'/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([^"&?\/\s]{11})/i',
		'/^[a-zA-Z0-9_-]{11}$/',
	];

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $url, $matches ) ) {
			return $matches[1] ?? $matches[0];
		}
	}

	return false;
}

/**
 * Extract TikTok Video ID from URL
 *
 * @param mixed $url
 * @return string|false
 */
function spl_extract_tiktok_id( mixed $url ): string|false {
	if ( empty( $url ) || ! is_string( $url ) ) {
		return false;
	}

	$url = trim( $url );

	if ( preg_match( '/video\/(\d+)/', $url, $matches ) ) {
		return $matches[1];
	}

	return false;
}

/**
 * Helper to get clean video metadata for template rendering
 * Supports both new fields and legacy 'link_video' / 'video-cat' fields
 *
 * @param int|null $post_id
 * @return array
 */
function spl_get_video_data( ?int $post_id = null ): array {
	try {
		$post_id = $post_id ?: get_the_ID();
		if ( ! $post_id ) {
			return [];
		}

		// Retrieve URL safely with fallback to legacy field 'link_video'
		$raw_url = function_exists( 'get_field' ) ? get_field( 'link_video', $post_id ) : null;
		if ( empty( $raw_url ) ) {
			$raw_url = function_exists( 'get_field' ) ? get_field( 'video_url', $post_id ) : null;
		}
		if ( empty( $raw_url ) ) {
			$raw_url = get_post_meta( $post_id, 'link_video', true );
		}
		if ( empty( $raw_url ) ) {
			$raw_url = get_post_meta( $post_id, 'video_url', true );
		}

		$url = is_string( $raw_url ) ? trim( $raw_url ) : '';

		$source      = function_exists( 'get_field' ) ? get_field( 'video_source', $post_id ) : '';
		$orientation = function_exists( 'get_field' ) ? get_field( 'video_orientation', $post_id ) : '';
		$iframe      = function_exists( 'get_field' ) ? (string) ( get_field( 'video_iframe', $post_id ) ?: '' ) : '';
		$duration    = function_exists( 'get_field' ) ? (string) ( get_field( 'video_duration', $post_id ) ?: '' ) : '';
		$badge       = function_exists( 'get_field' ) ? (string) ( get_field( 'video_badge', $post_id ) ?: '' ) : '';
		$is_featured = function_exists( 'get_field' ) ? (bool) get_field( 'is_featured', $post_id ) : false;
		$related_pid = function_exists( 'get_field' ) ? get_field( 'related_product', $post_id ) : null;

		// Auto-detect source if not explicitly set
		if ( empty( $source ) || ! is_string( $source ) ) {
			if ( ! empty( $url ) ) {
				if ( stripos( $url, 'tiktok.com' ) !== false ) {
					$source = 'tiktok';
				} elseif ( stripos( $url, 'facebook.com' ) !== false ) {
					$source = 'facebook';
				} elseif ( preg_match( '/\.(mp4|webm|ogg)$/i', $url ) ) {
					$source = 'mp4_direct';
				} else {
					$source = 'youtube';
				}
			} elseif ( ! empty( $iframe ) ) {
				$source = 'custom_iframe';
			} else {
				$source = 'youtube';
			}
		}

		// Auto-detect orientation if not set or if TikTok/Shorts link
		if ( empty( $orientation ) || ! is_string( $orientation ) ) {
			if ( stripos( $url, '/shorts/' ) !== false || stripos( $url, 'tiktok.com' ) !== false || $source === 'tiktok' ) {
				$orientation = 'vertical_9_16';
			} else {
				$orientation = 'horizontal_16_9';
			}
		}

		// Determine thumbnail (Highest quality 'full' size and YouTube maxresdefault)
		$thumb_url = '';
		$thumb_id  = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$thumb_url = wp_get_attachment_image_url( $thumb_id, 'full' ) ?: wp_get_attachment_image_url( $thumb_id, 'large' ) ?: '';
		}

		$youtube_id = spl_extract_youtube_id( $url );
		$tiktok_id  = spl_extract_tiktok_id( $url );

		// Fallback thumbnail from YouTube if no custom thumbnail set (Use high quality 480x360)
		$known_broken_ids = [ '1W7F3FvjVfM', 'r4h0d9n4F2g', 'gY8M9vR7qXU', '9bK0x98fEzo' ];
		if ( empty( $thumb_url ) && $youtube_id && ! in_array( $youtube_id, $known_broken_ids, true ) ) {
			$thumb_url = "https://i.ytimg.com/vi/{$youtube_id}/hqdefault.jpg";
		}

		// Embed URL for lightbox
		$embed_url = '';
		if ( $source === 'youtube' && $youtube_id ) {
			$embed_url = "https://www.youtube-nocookie.com/embed/{$youtube_id}?autoplay=1&rel=0&modestbranding=1";
		} elseif ( $source === 'tiktok' && $tiktok_id ) {
			$embed_url = "https://www.tiktok.com/embed/v2/{$tiktok_id}";
		} elseif ( $source === 'mp4_direct' ) {
			$embed_url = esc_url( $url );
		} else {
			$embed_url = esc_url( $url );
		}

		// Related Product info safely
		$product_data = null;
		if ( ! empty( $related_pid ) ) {
			$prod_id = 0;
			if ( is_object( $related_pid ) && isset( $related_pid->ID ) ) {
				$prod_id = (int) $related_pid->ID;
			} elseif ( is_numeric( $related_pid ) ) {
				$prod_id = (int) $related_pid;
			}

			if ( $prod_id > 0 && function_exists( 'wc_get_product' ) ) {
				$prod = wc_get_product( $prod_id );
				if ( $prod && is_object( $prod ) && method_exists( $prod, 'get_name' ) ) {
					$product_data = [
						'id'         => $prod_id,
						'title'      => $prod->get_name(),
						'permalink'  => get_permalink( $prod_id ),
						'price_html' => method_exists( $prod, 'get_price_html' ) ? $prod->get_price_html() : '',
						'thumb'      => get_the_post_thumbnail_url( $prod_id, 'thumbnail' ) ?: '',
					];
				}
			}
		}

		// Categories (check 'video-cat')
		$terms          = get_the_terms( $post_id, 'video-cat' );
		$category_names = [];
		$category_slugs = [];
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( is_object( $term ) && isset( $term->name ) ) {
					$category_names[] = $term->name;
					$category_slugs[] = $term->slug;
				}
			}
		}

		return [
			'id'              => $post_id,
			'title'           => get_the_title( $post_id ) ?: '',
			'excerpt'         => get_the_excerpt( $post_id ) ?: '',
			'source'          => $source,
			'orientation'     => $orientation, // 'horizontal_16_9' or 'vertical_9_16'
			'url'             => $url,
			'embed_url'       => $embed_url,
			'iframe'          => $iframe,
			'duration'        => $duration,
			'badge'           => $badge,
			'is_featured'     => $is_featured,
			'thumb_id'        => $thumb_id,
			'thumb_url'       => $thumb_url,
			'youtube_id'      => $youtube_id,
			'tiktok_id'       => $tiktok_id,
			'related_product' => $product_data,
			'categories'      => $category_names,
			'category_slugs'  => $category_slugs,
			'date'            => get_the_date( 'd/m/Y', $post_id ) ?: '',
			'menu_order'      => (int) ( get_post_field( 'menu_order', $post_id ) ?: 0 ),
		];
	} catch ( \Throwable $e ) {
		return [
			'id'              => $post_id ?: 0,
			'title'           => get_the_title( $post_id ) ?: '',
			'excerpt'         => '',
			'source'          => 'youtube',
			'orientation'     => 'horizontal_16_9',
			'url'             => '',
			'embed_url'       => '',
			'iframe'          => '',
			'duration'        => '',
			'badge'           => '',
			'is_featured'     => false,
			'thumb_id'        => 0,
			'thumb_url'       => '',
			'youtube_id'      => false,
			'tiktok_id'       => false,
			'related_product' => null,
			'categories'      => [],
			'category_slugs'  => [],
			'date'            => '',
			'menu_order'      => 0,
		];
	}
}
