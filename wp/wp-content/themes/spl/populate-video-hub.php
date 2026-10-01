<?php
/**
 * Populate Video Hub with real videos and create the Video Hub page if not exists.
 *
 * Can be run via CLI:
 * php wp/wp-content/themes/spl/populate-video-hub.php
 * or WP-CLI eval-file
 *
 * @package SPL
 * @author  SPL
 */

// If not in WordPress context, attempt to bootstrap
if ( ! defined( 'ABSPATH' ) ) {
	$wp_load = __DIR__ . '/../../../../wp/wp-load.php';
	if ( file_exists( $wp_load ) ) {
		require_once $wp_load;
	} else {
		$wp_load_alt = __DIR__ . '/../../../wp-load.php';
		if ( file_exists( $wp_load_alt ) ) {
			require_once $wp_load_alt;
		}
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	echo "⚠ WordPress context not found.\n";
	exit( 1 );
}

echo "=== POPULATING VIDEO HUB (ĐẠI LÝ XE ĐIỆN) ===\n";

// 1. Ensure categories are registered & seeded
if ( function_exists( 'spl_seed_video_categories' ) ) {
	spl_seed_video_categories();
}

$cat_map = [
	'gioi-thieu' => 'gioi-thieu-xe',
	'nha-may'    => 'nha-may-san-xuat',
	'su-kien'    => 'su-kien-hoat-dong',
	'shorts'     => 'tiktok-shorts',
	'huong-dan'  => 'huong-dan-su-dung',
	'phong-su'   => 'phong-su-bao-chi',
];

// 2. Video Dataset
$sample_videos = [
	// Hero / Featured
	[
		'title'       => 'Xe Điện Bluera Việt Nhật Lọt Top 10 Thương Hiệu Dẫn Đầu Việt Nam 2026',
		'url'         => 'https://www.youtube.com/watch?v=XMfE5XmpWn0',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '04:15',
		'badge'       => 'Tiêu Điểm',
		'is_featured' => 1,
		'cat'         => 'su-kien-hoat-dong',
		'excerpt'     => 'Toàn cảnh lễ trao giải vinh danh Xe Điện Bluera Việt Nhật tại Nhà hát Quân đội, đánh dấu bước chuyển mình vượt bậc.',
	],
	[
		'title'       => 'Bluera Việt Nhật — CAFETEK Đưa Tin Sau Sự Kiện Top 10 Thương Hiệu Dẫn Đầu 2026',
		'url'         => 'https://www.youtube.com/watch?v=YmoliAqhJn8',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '03:40',
		'badge'       => 'Truyền Hình',
		'is_featured' => 0,
		'cat'         => 'phong-su-bao-chi',
		'excerpt'     => 'Chương trình CAFETEK đài truyền hình HTV đưa tin phóng sự về công nghệ sản xuất xe điện Bluera hiện đại bậc nhất.',
	],
	[
		'title'       => 'Xe Điện Bluera Việt Nhật Hút Khách Tại Triển Lãm Autotech & Accessories 2024',
		'url'         => 'https://www.youtube.com/watch?v=DHrISK53OPs',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '05:12',
		'badge'       => 'Triển Lãm',
		'is_featured' => 0,
		'cat'         => 'su-kien-hoat-dong',
		'excerpt'     => 'Hàng nghìn lượt khách tham quan và chạy thử các mẫu xe đạp điện, xe máy điện thế hệ mới tại SECC.',
	],
	[
		'title'       => 'Khám Phá AIE Smile I — Mẫu Xe Đạp Điện Cỡ Lớn Khẳng Định Vị Thế Dẫn Đầu',
		'url'         => 'https://www.youtube.com/watch?v=xFo863UkIE4',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '06:25',
		'badge'       => 'Đánh Giá Xe',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Đánh giá chi tiết thiết kế khung sườn hợp kim, động cơ chống nước và khả năng tải trọng của AIE Smile I.',
	],
	[
		'title'       => 'AI EBike Giới Thiệu Dòng Xe Điện A.I Tại Triển Lãm Quốc Tế Xe Hai Bánh Việt Nam 2024',
		'url'         => 'https://www.youtube.com/watch?v=YzBZK1FDI7I',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '04:50',
		'badge'       => 'Công Nghệ AI',
		'is_featured' => 0,
		'cat'         => 'nha-may-san-xuat',
		'excerpt'     => 'Hệ thống định vị GPS thông minh, khoá xe qua smartphone và quản lý dung lượng pin chuẩn xác.',
	],
	[
		'title'       => 'Chạm Mặt Camelo I8 — Mẫu Xe Đạp Điện Đẹp Lạ Dành Cho Nàng Thơ',
		'url'         => 'https://www.youtube.com/watch?v=0haAatnAXTg',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '03:15',
		'badge'       => 'Mẫu Mới',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Kiểu dáng bo tròn phong cách cổ điển thanh lịch, bảng màu pastel ngọt ngào rất được lòng phái đẹp.',
	],
	[
		'title'       => 'Tour Solo Xe Đạp Điện AI EBike Smile Đi Vũng Tàu Hết 143km',
		'url'         => 'https://www.youtube.com/watch?v=0SCyPv943Rg',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '08:45',
		'badge'       => 'Trải Nghiệm Thực Tế',
		'is_featured' => 0,
		'cat'         => 'huong-dan-su-dung',
		'excerpt'     => 'Hành trình vượt 143km từ TP.HCM đến Vũng Tàu chỉ với 1 lần sạc đầy, kiểm chứng quãng đường thực tế.',
	],
	[
		'title'       => 'Xe 3 Gác Điện Chở Hàng Bluera 2024 — Sức Chở Bền Bỉ, Tiết Kiệm Chi Phí',
		'url'         => 'https://www.youtube.com/watch?v=ZljyfMUV4DI',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '05:30',
		'badge'       => 'Xe Chở Hàng',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Giải pháp vận chuyển hàng hoá đô thị tối ưu với chi phí vận hành siêu rẻ so với xe xăng.',
	],

	// Shorts & TikTok (Vertical 9:16)
	[
		'title'       => 'Top 3 Mẫu Xe Đạp Điện Học Sinh Bán Chạy Nhất Tuần Này ⚡',
		'url'         => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
		'orientation' => 'vertical_9_16',
		'source'      => 'tiktok',
		'duration'    => '00:58',
		'badge'       => 'Shorts Viral',
		'is_featured' => 0,
		'cat'         => 'tiktok-shorts',
		'excerpt'     => 'Điểm nhanh 3 mẫu xe điện nhỏ gọn, không cần bằng lái, giá dưới 10 triệu.',
	],
	[
		'title'       => 'Thử Nghiệm Lội Nước 40cm Với Xe Điện Bluera Chống Nước Chuẩn IP67 🌊',
		'url'         => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
		'orientation' => 'vertical_9_16',
		'source'      => 'tiktok',
		'duration'    => '00:45',
		'badge'       => 'Test Nước IP67',
		'is_featured' => 0,
		'cat'         => 'tiktok-shorts',
		'excerpt'     => 'Mùa mưa bão ngập đường không còn là nỗi lo nhờ công nghệ chống nước khép kín động cơ.',
	],
	[
		'title'       => 'Hướng Dẫn Mở Khoá Xe Bằng Thẻ Từ NFC 1 Chạm Siêu Tiện Lợi 💳',
		'url'         => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
		'orientation' => 'vertical_9_16',
		'source'      => 'youtube',
		'duration'    => '00:35',
		'badge'       => 'Mẹo Xe 60s',
		'is_featured' => 0,
		'cat'         => 'tiktok-shorts',
		'excerpt'     => 'Không cần chìa khoá cơ, chỉ cần quẹt thẻ từ NFC hoặc smartphone là xe tự khởi động.',
	],
	[
		'title'       => 'Cận Cảnh Sắc Màu Camelo I8 - Mẫu Xe Hot Nhất Mùa Tựu Trường 🌸',
		'url'         => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
		'orientation' => 'vertical_9_16',
		'source'      => 'tiktok',
		'duration'    => '00:52',
		'badge'       => 'Hot Trend',
		'is_featured' => 0,
		'cat'         => 'tiktok-shorts',
		'excerpt'     => 'Màu hồng pastel, xanh mint và kem sữa cực tôn dáng cho các bạn nữ.',
	],
	[
		'title'       => 'Mẹo Sạc Bình Ắc Quy Xe Điện Tăng Tuổi Thọ Gấp Đôi Bạn Cần Biết 🔋',
		'url'         => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
		'orientation' => 'vertical_9_16',
		'source'      => 'youtube',
		'duration'    => '00:59',
		'badge'       => 'Mẹo Hay',
		'is_featured' => 0,
		'cat'         => 'tiktok-shorts',
		'excerpt'     => 'Thời điểm sạc lý tưởng và cách bảo quản bộ sạc thông minh tự ngắt khi đầy.',
	],
];

// Find a product to link as related product if available
$first_prod = get_posts( [
	'post_type'      => 'product',
	'posts_per_page' => 1,
	'post_status'    => 'publish',
] );
$sample_product_id = ! empty( $first_prod ) ? $first_prod[0]->ID : null;

// 3. Insert or Update Video Posts
$created_count = 0;
foreach ( $sample_videos as $item ) {
	$existing = get_page_by_title( $item['title'], OBJECT, 'video' );
	if ( $existing ) {
		$post_id = $existing->ID;
		echo "  • Video already exists: [{$post_id}] {$item['title']}\n";
	} else {
		$post_id = wp_insert_post( [
			'post_title'   => $item['title'],
			'post_content' => $item['excerpt'],
			'post_excerpt' => $item['excerpt'],
			'post_status'  => 'publish',
			'post_type'    => 'video',
		] );
		$created_count++;
		echo "  ✓ Created video post: [{$post_id}] {$item['title']}\n";
	}

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		// Update meta & ACF fields
		update_post_meta( $post_id, 'link_video', $item['url'] );
		update_post_meta( $post_id, 'video_url', $item['url'] );
		update_post_meta( $post_id, 'video_source', $item['source'] );
		update_post_meta( $post_id, 'video_orientation', $item['orientation'] );
		update_post_meta( $post_id, 'video_duration', $item['duration'] );
		update_post_meta( $post_id, 'video_badge', $item['badge'] );
		update_post_meta( $post_id, 'is_featured', $item['is_featured'] ? '1' : '0' );

		if ( $sample_product_id ) {
			update_post_meta( $post_id, 'related_product', $sample_product_id );
		}

		// Assign taxonomy term
		if ( ! empty( $item['cat'] ) ) {
			wp_set_object_terms( $post_id, $item['cat'], 'video-cat' );
		}
	}
}

// 4. Create or Ensure Video Hub Page
$video_page = get_pages( [
	'meta_key'   => '_wp_page_template',
	'meta_value' => 'templates/template-page-video.php',
	'number'     => 1,
] );

if ( empty( $video_page ) ) {
	// Check by slug
	$page_by_path = get_page_by_path( 'video' );
	if ( $page_by_path ) {
		update_post_meta( $page_by_path->ID, '_wp_page_template', 'templates/template-page-video.php' );
		echo "  ✓ Updated existing page /video/ with template-page-video.php\n";
	} else {
		$new_page_id = wp_insert_post( [
			'post_title'     => 'Thư Viện Video',
			'post_name'      => 'video',
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
		] );
		if ( $new_page_id && ! is_wp_error( $new_page_id ) ) {
			update_post_meta( $new_page_id, '_wp_page_template', 'templates/template-page-video.php' );
			echo "  ✓ Created new page 'Thư Viện Video' (/video/) with template-page-video.php\n";
		}
	}
} else {
	echo "  • Video Hub page already configured: ID [{$video_page[0]->ID}] ({$video_page[0]->post_title})\n";
}

echo "=== FINISHED POPULATING VIDEO HUB ({$created_count} new videos) ===\n";
