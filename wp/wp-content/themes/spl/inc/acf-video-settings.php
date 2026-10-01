<?php
/**
 * ACF Fields Registration for Video Hub
 * Fully backwards-compatible with existing 'link_video' and 'video-cat' data.
 *
 * @package SPL
 * @author  SPL
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'spl_register_video_acf_fields' );

function spl_register_video_acf_fields(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// ── 1. Field Group: Cấu hình Video (gắn vào post type 'video') ──
	acf_add_local_field_group( [
		'key'                   => 'group_video_details',
		'title'                 => __( 'Cấu hình Video Chi Tiết', 'spl' ),
		'fields'                => [
			// Video Source
			[
				'key'           => 'field_video_source',
				'label'         => __( 'Nguồn Video', 'spl' ),
				'name'          => 'video_source',
				'type'          => 'select',
				'instructions'  => __( 'Chọn nền tảng lưu trữ video', 'spl' ),
				'required'      => 1,
				'choices'       => [
					'youtube'       => 'YouTube (Link video chuẩn hoặc YouTube Shorts)',
					'tiktok'        => 'TikTok (Link video TikTok)',
					'facebook'      => 'Facebook Watch / Reels',
					'mp4_direct'    => 'Video trực tiếp (Link MP4/Media Host)',
					'custom_iframe' => 'Mã nhúng Iframe tùy biến',
				],
				'default_value' => 'youtube',
				'return_format' => 'value',
				'wrapper'       => [ 'width' => '50' ],
			],

			// Video Orientation (Aspect Ratio)
			[
				'key'           => 'field_video_orientation',
				'label'         => __( 'Tỷ lệ khung hình / Kiểu hiển thị', 'spl' ),
				'name'          => 'video_orientation',
				'type'          => 'button_group',
				'instructions'  => __( 'Chọn kiểu video ngang hoặc dọc', 'spl' ),
				'required'      => 1,
				'choices'       => [
					'horizontal_16_9' => 'Ngang 16:9 (YouTube, Sự kiện, Phóng sự)',
					'vertical_9_16'   => 'Đứng 9:16 (TikTok, Shorts, Reels)',
				],
				'default_value' => 'horizontal_16_9',
				'layout'        => 'horizontal',
				'wrapper'       => [ 'width' => '50' ],
			],

			// Video URL (Named 'link_video' for 100% legacy compatibility with old video posts)
			[
				'key'               => 'field_link_video',
				'label'             => __( 'Đường dẫn Video (URL)', 'spl' ),
				'name'              => 'link_video',
				'type'              => 'url',
				'instructions'      => __( 'Dán link YouTube / Shorts / TikTok / MP4 vào đây (Hệ thống tự động lấy ảnh bìa HD)', 'spl' ),
				'required'          => 0,
				'conditional_logic' => [
					[
						[
							'field'    => 'field_video_source',
							'operator' => '!=',
							'value'    => 'custom_iframe',
						],
					],
				],
				'placeholder'       => 'https://www.youtube.com/watch?v=... hoặc https://www.tiktok.com/@dailyxedien/video/...',
				'wrapper'           => [ 'width' => '100' ],
			],

			// Video Custom Iframe
			[
				'key'               => 'field_video_iframe',
				'label'             => __( 'Mã nhúng Iframe tùy biến', 'spl' ),
				'name'              => 'video_iframe',
				'type'              => 'textarea',
				'instructions'      => __( 'Dán toàn bộ thẻ <iframe>...</iframe> nếu dùng nguồn nhúng đặc biệt', 'spl' ),
				'rows'              => 3,
				'conditional_logic' => [
					[
						[
							'field'    => 'field_video_source',
							'operator' => '==',
							'value'    => 'custom_iframe',
						],
					],
				],
				'wrapper'           => [ 'width' => '100' ],
			],

			// Duration
			[
				'key'          => 'field_video_duration',
				'label'        => __( 'Thời lượng hiển thị', 'spl' ),
				'name'         => 'video_duration',
				'type'         => 'text',
				'instructions' => __( 'Ví dụ: 03:45, 00:59, 12:20', 'spl' ),
				'placeholder'  => '03:45',
				'wrapper'      => [ 'width' => '33.33' ],
			],

			// Badge / Label
			[
				'key'          => 'field_video_badge',
				'label'        => __( 'Huy hiệu nổi bật (Badge)', 'spl' ),
				'name'         => 'video_badge',
				'type'         => 'text',
				'instructions' => __( 'Ví dụ: Hot, Mới ra mắt, Phóng sự, 4K, TikTok Viral', 'spl' ),
				'placeholder'  => 'Hot',
				'wrapper'      => [ 'width' => '33.33' ],
			],

			// Is Featured (Hero Spotlight)
			[
				'key'           => 'field_video_is_featured',
				'label'         => __( 'Đặt làm Video Tiêu Điểm', 'spl' ),
				'name'          => 'is_featured',
				'type'          => 'true_false',
				'instructions'  => __( 'Hiển thị video này ở khu vực Hero Cinema đầu trang', 'spl' ),
				'default_value' => 0,
				'ui'            => 1,
				'wrapper'       => [ 'width' => '33.33' ],
			],

			// Related Product (WooCommerce Product Post Object)
			[
				'key'           => 'field_video_related_product',
				'label'         => __( 'Sản phẩm / Dòng xe liên quan', 'spl' ),
				'name'          => 'related_product',
				'type'          => 'post_object',
				'instructions'  => __( 'Gắn dòng xe tương ứng để hiển thị nút xem chi tiết và mua hàng ngay cạnh video', 'spl' ),
				'post_type'     => [ 'product' ],
				'allow_null'    => 1,
				'multiple'      => 0,
				'return_format' => 'object',
				'ui'            => 1,
				'wrapper'       => [ 'width' => '100' ],
			],
		],
		'location'              => [
			[
				[
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'video',
				],
			],
		],
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
	] );

	// ── 2. Field Group: Cấu hình Trang Video Hub (gắn vào template-page-video.php) ──
	acf_add_local_field_group( [
		'key'                   => 'group_video_page_settings',
		'title'                 => __( 'Cấu hình Trang Video Hub', 'spl' ),
		'fields'                => [
			// Page Subtitle / Tagline
			[
				'key'           => 'field_video_page_subtitle',
				'label'         => __( 'Mô tả trang / Slogan', 'spl' ),
				'name'          => 'video_page_subtitle',
				'type'          => 'text',
				'default_value' => 'Khám phá thế giới xe điện Đại Lý Xe Điện qua các thước phim sống động, trải nghiệm thực tế và đánh giá chi tiết.',
				'wrapper'       => [ 'width' => '100' ],
			],

			// Hero Custom Video Selection (Optional Override)
			[
				'key'           => 'field_video_hero_override',
				'label'         => __( 'Ghim Video Tiêu Điểm Lớn (Hero Spotlight)', 'spl' ),
				'name'          => 'video_hero_override',
				'type'          => 'post_object',
				'instructions'  => __( 'Nếu chọn, video này sẽ luôn xuất hiện ở khung Cinema lớn nhất bên trái. Nếu để trống, hệ thống sẽ tự lấy video mới nhất được đánh dấu Tiêu Điểm.', 'spl' ),
				'post_type'     => [ 'video' ],
				'allow_null'    => 1,
				'multiple'      => 0,
				'return_format' => 'id',
				'ui'            => 1,
				'wrapper'       => [ 'width' => '50' ],
			],

			// Shorts Section Title
			[
				'key'           => 'field_video_shorts_title',
				'label'         => __( 'Tiêu đề khu vực TikTok & Shorts (9:16)', 'spl' ),
				'name'          => 'video_shorts_title',
				'type'          => 'text',
				'default_value' => 'Đại Lý Xe Điện Shorts & TikTok Viral ⚡',
				'wrapper'       => [ 'width' => '50' ],
			],
		],
		'location'              => [
			[
				[
					'param'    => 'page_template',
					'operator' => '==',
					'value'    => 'templates/template-page-video.php',
				],
			],
		],
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
	] );
}

/**
 * Auto-load fallback for legacy field 'link_video' & 'video_url'
 */
add_filter( 'acf/load_value/name=link_video', 'spl_acf_fallback_link_video', 10, 3 );
function spl_acf_fallback_link_video( $value, $post_id, $field ) {
	if ( empty( $value ) && is_numeric( $post_id ) ) {
		$value = get_post_meta( (int) $post_id, 'video_url', true )
			?: get_post_meta( (int) $post_id, 'youtube_url', true )
			?: '';
	}
	return $value;
}

/**
 * When saving 'link_video', also sync to 'video_url' for compatibility
 */
add_filter( 'acf/update_value/name=link_video', 'spl_acf_sync_video_url', 10, 3 );
function spl_acf_sync_video_url( $value, $post_id, $field ) {
	if ( is_numeric( $post_id ) ) {
		update_post_meta( (int) $post_id, 'video_url', (string) $value );
	}
	return $value;
}

/**
 * Auto-populate 'video_source' from URL if not yet set on existing videos
 */
add_filter( 'acf/load_value/name=video_source', 'spl_acf_auto_detect_video_source', 10, 3 );
function spl_acf_auto_detect_video_source( $value, $post_id, $field ) {
	if ( empty( $value ) && is_numeric( $post_id ) ) {
		$url = get_post_meta( (int) $post_id, 'link_video', true ) ?: get_post_meta( (int) $post_id, 'video_url', true );
		if ( ! empty( $url ) && is_string( $url ) ) {
			if ( stripos( $url, 'tiktok.com' ) !== false ) {
				return 'tiktok';
			}
			if ( stripos( $url, 'facebook.com' ) !== false ) {
				return 'facebook';
			}
			if ( preg_match( '/\.(mp4|webm|ogg)$/i', $url ) ) {
				return 'mp4_direct';
			}
			return 'youtube';
		}
	}
	return $value ?: 'youtube';
}

/**
 * Auto-populate 'video_orientation' from URL if not yet set on existing videos
 */
add_filter( 'acf/load_value/name=video_orientation', 'spl_acf_auto_detect_video_orientation', 10, 3 );
function spl_acf_auto_detect_video_orientation( $value, $post_id, $field ) {
	if ( empty( $value ) && is_numeric( $post_id ) ) {
		$url = get_post_meta( (int) $post_id, 'link_video', true ) ?: get_post_meta( (int) $post_id, 'video_url', true );
		if ( ! empty( $url ) && is_string( $url ) && ( stripos( $url, 'tiktok.com' ) !== false || stripos( $url, '/shorts/' ) !== false ) ) {
			return 'vertical_9_16';
		}
		return 'horizontal_16_9';
	}
	return $value ?: 'horizontal_16_9';
}
