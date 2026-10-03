<?php
/**
 * Populate Video Hub with full real videos from bluerabike.com, aiebike.vn and DailyXeDien.
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

echo "=== ĐỒNG BỘ VIDEO TỪ BLUERABIKE.COM & AIEBIKE.VN VÀO DAILYXEDIEN ===\n";

// 1. Ensure categories are registered & seeded
if ( function_exists( 'spl_seed_video_categories' ) ) {
	spl_seed_video_categories();
}

$default_cats = [
	'gioi-thieu-xe'      => 'Giới thiệu & Đánh giá Xe',
	'nha-may-san-xuat'   => 'Nhà máy & Dây chuyền SX',
	'su-kien-hoat-dong'  => 'Sự kiện & Hoạt động',
	'tiktok-shorts'      => 'Video Ngắn (TikTok / Shorts)',
	'huong-dan-su-dung'  => 'Hướng dẫn sử dụng & Mẹo xe',
	'phong-su-bao-chi'   => 'Phóng sự & Báo chí',
];

foreach ( $default_cats as $slug => $name ) {
	if ( ! term_exists( $slug, 'video-cat' ) ) {
		wp_insert_term( $name, 'video-cat', [ 'slug' => $slug ] );
	}
}

// 2. Full Combined Video Dataset (bluerabike.com + aiebike.vn + dailyxedien)
$all_videos = [
	// ─── TỪ AIEBIKE.VN & SỰ KIỆN NỔI BẬT ───
	[
		'title'       => 'AI EBIKE – Dấu ấn tại VIETNAM EXCELLENT BRANDS 2026 | AIE MS1 Pro – Hạng mục Sản phẩm & Dịch vụ',
		'url'         => 'https://www.youtube.com/watch?v=_c0keOGRrS8',
		'youtube_id'  => '_c0keOGRrS8',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '03:25',
		'badge'       => 'Brands 2026',
		'is_featured' => 1,
		'cat'         => 'su-kien-hoat-dong',
		'excerpt'     => 'AI EBIKE vinh dự nhận giải thưởng Vietnam Excellent Brands 2026 với mẫu xe trợ lực điện thông minh AIE MS1 Pro.',
	],
	[
		'title'       => 'Hướng Dẫn Tải & Cài Đặt App AI EBike Trên Điện Thoại | Chi Tiết Từng Bước Cho Đại Lý/Khách Hàng',
		'url'         => 'https://www.youtube.com/watch?v=kixDXBEGGcU',
		'youtube_id'  => 'kixDXBEGGcU',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '02:50',
		'badge'       => 'Cài App',
		'is_featured' => 0,
		'cat'         => 'huong-dan-su-dung',
		'excerpt'     => 'Video hướng dẫn chi tiết cách tải app, kích hoạt tài khoản và kết nối Bluetooth với xe điện AI EBIKE.',
	],
	[
		'title'       => '📱 TỔNG QUAN CHỨC NĂNG BẢO HÀNH ĐIỆN TỬ | Khi Mua Xe Đạp Điện AI EBIKE Qua APP',
		'url'         => 'https://www.youtube.com/watch?v=C3S_6PPHY-I',
		'youtube_id'  => 'C3S_6PPHY-I',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '04:12',
		'badge'       => 'Bảo Hành App',
		'is_featured' => 0,
		'cat'         => 'huong-dan-su-dung',
		'excerpt'     => 'Hướng dẫn sử dụng sổ bảo hành điện tử thông minh, tra cứu lịch sử sửa chữa và cứu hộ xe qua ứng dụng di động.',
	],
	[
		'title'       => 'AI EBike Giới Thiệu Dòng Xe Điện A.I Tại Triển Lãm Quốc Tế Xe Hai Bánh Việt Nam 2024',
		'url'         => 'https://www.youtube.com/watch?v=YzBZK1FDI7I',
		'youtube_id'  => 'YzBZK1FDI7I',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '04:50',
		'badge'       => 'Công Nghệ AI',
		'is_featured' => 0,
		'cat'         => 'su-kien-hoat-dong',
		'excerpt'     => 'Ghi hình phóng sự tại Triển lãm Quốc tế Xe hai bánh Việt Nam 2024, nơi AI EBike giới thiệu dải sản phẩm xe điện tích hợp công nghệ AI.',
	],
	[
		'title'       => 'Xe Đạp Điện AIE Smile Đồng Hành Cùng Showcase Phim Lật Mặt 7',
		'url'         => 'https://www.youtube.com/watch?v=_zb53u4AbfI',
		'youtube_id'  => '_zb53u4AbfI',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '03:25',
		'badge'       => 'Showcase',
		'is_featured' => 0,
		'cat'         => 'su-kien-hoat-dong',
		'excerpt'     => 'Mẫu xe đạp điện AI EBIKE Smile đồng hành cùng đạo diễn Lý Hải và đoàn phim Lật Mặt 7 - Một Điều Ước.',
	],
	[
		'title'       => 'Công Nghệ Động Cơ Controller Inside Motor (Bộ Điều Khiển Trong Động Cơ)',
		'url'         => 'https://www.youtube.com/watch?v=rVwSzl3G2Yw',
		'youtube_id'  => 'rVwSzl3G2Yw',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '04:10',
		'badge'       => 'Công Nghệ AI',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Tìm hiểu công nghệ đột phá tích hợp bộ điều khiển IC thông minh trực tiếp bên trong động cơ xe điện.',
	],
	[
		'title'       => 'Khám Phá Sức Hút Của Xe Điện Smile Tại Triển Lãm AUTOTECH & ACCESSORIES 2024 – HTV',
		'url'         => 'https://www.youtube.com/watch?v=pe4M8ZglrkU',
		'youtube_id'  => 'pe4M8ZglrkU',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '03:40',
		'badge'       => 'HTV Tin Tức',
		'is_featured' => 0,
		'cat'         => 'su-kien-hoat-dong',
		'excerpt'     => 'Đài truyền hình HTV đưa tin về gian hàng xe điện thông minh thu hút đông đảo khách tham quan tại SECC.',
	],
	[
		'title'       => 'AI EBIKE – Tìm Hiểu Nhà Máy Lắp Ráp & Sản Xuất Quy Mô 10.000m2',
		'url'         => 'https://www.youtube.com/watch?v=B23jICKYtv4',
		'youtube_id'  => 'B23jICKYtv4',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '05:15',
		'badge'       => 'Nhà Máy 4.0',
		'is_featured' => 0,
		'cat'         => 'nha-may-san-xuat',
		'excerpt'     => 'Thực tế dây chuyền sơn robot tĩnh điện, dập khung tự động và kiểm thử nghiêm ngặt tại nhà máy sản xuất.',
	],
	[
		'title'       => 'Hành Trình Trải Nghiệm Đi Từ Sài Gòn – Bình Phước Bằng Xe Đạp Điện Smile',
		'url'         => 'https://www.youtube.com/watch?v=PQzLq3WQNiE',
		'youtube_id'  => 'PQzLq3WQNiE',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '08:50',
		'badge'       => 'Hành Trình',
		'is_featured' => 0,
		'cat'         => 'huong-dan-su-dung',
		'excerpt'     => 'Thử thách độ bền và khả năng tiêu thụ điện năng của dòng xe Smile trên chặng đường liên tỉnh hơn 100km.',
	],
	[
		'title'       => 'Tour Solo Xe Đạp Điện AI EBike Smile Đi Vũng Tàu Hết 143km',
		'url'         => 'https://www.youtube.com/watch?v=0SCyPv943Rg',
		'youtube_id'  => '0SCyPv943Rg',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '12:35',
		'badge'       => 'Trải Nghiệm',
		'is_featured' => 0,
		'cat'         => 'huong-dan-su-dung',
		'excerpt'     => 'Review thực tế hành trình đi biển Vũng Tàu bằng xe điện AIE Smile, trải nghiệm êm ái, bền bỉ qua đèo dốc.',
	],
	[
		'title'       => 'Lễ Trao Giải Khán Giả Trúng Thưởng Giải Đặc Biệt Trong Showcase Lật Mặt 7 Một Điều Ước',
		'url'         => 'https://www.youtube.com/watch?v=1jnLJckcwWw',
		'youtube_id'  => '1jnLJckcwWw',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '04:20',
		'badge'       => 'Lật Mặt 7',
		'is_featured' => 0,
		'cat'         => 'su-kien-hoat-dong',
		'excerpt'     => 'Trao tặng chiếc xe đạp điện AI EBIKE Smile phiên bản đặc biệt cho vị khán giả may mắn nhất sự kiện.',
	],

	// ─── TỪ BLUERABIKE.COM ───
	[
		'title'       => 'Bluera Việt Nhật — CAFETEK Đưa Tin Sau Sự Kiện Top 10 Thương Hiệu Dẫn Đầu 2026',
		'url'         => 'https://www.youtube.com/watch?v=YmoliAqhJn8',
		'youtube_id'  => 'YmoliAqhJn8',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '03:40',
		'badge'       => 'Truyền Hình',
		'is_featured' => 1,
		'cat'         => 'phong-su-bao-chi',
		'excerpt'     => 'Chương trình CAFETEK đài truyền hình HTV đưa tin phóng sự về công nghệ sản xuất xe điện Bluera hiện đại bậc nhất.',
	],
	[
		'title'       => 'Xe Điện Bluera Việt Nhật Lọt Top 10 Thương Hiệu Dẫn Đầu Việt Nam 2026',
		'url'         => 'https://www.youtube.com/watch?v=XMfE5XmpWn0',
		'youtube_id'  => 'XMfE5XmpWn0',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '04:15',
		'badge'       => 'Tiêu Điểm',
		'is_featured' => 1,
		'cat'         => 'su-kien-hoat-dong',
		'excerpt'     => 'Toàn cảnh lễ trao giải vinh danh Xe Điện Bluera Việt Nhật tại Nhà hát Quân đội, đánh dấu bước chuyển mình vượt bậc.',
	],
	[
		'title'       => 'Xe 3 Gác Điện Chở Hàng Bluera 2024 — Sức Chở Bền Bỉ, Tiết Kiệm Chi Phí',
		'url'         => 'https://www.youtube.com/watch?v=ZljyfMUV4DI',
		'youtube_id'  => 'ZljyfMUV4DI',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '03:45',
		'badge'       => 'Xe Ba Gác',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Dòng xe ba gác điện chở hàng đa năng, tải trọng mạnh mẽ, vận hành êm ái cho các cơ sở kinh doanh.',
	],
	[
		'title'       => 'BLUESUDA EM15 – Sức Mạnh Công Nghệ Chuẩn Châu Âu',
		'url'         => 'https://www.youtube.com/watch?v=TJq_xvXwkl0',
		'youtube_id'  => 'TJq_xvXwkl0',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '04:30',
		'badge'       => 'Xe Trợ Lực',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Mẫu xe đạp trợ lực điện cao cấp Bluesuda EM15 với pin Lithium tháo rời, khung nhôm khí động học.',
	],
	[
		'title'       => 'Trải Nghiệm Xe Điện BL8 | Động Cơ 500W, Đi 50km/1 Lần Sạc',
		'url'         => 'https://www.youtube.com/watch?v=veKijC_GENM',
		'youtube_id'  => 'veKijC_GENM',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '05:20',
		'badge'       => 'Trải Nghiệm Xe',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Trải nghiệm thực tế khả năng tăng tốc, leo dốc và cảm giác lái đầm chắc của xe điện Bluera BL8.',
	],
	[
		'title'       => 'Review Xe Điện AIE MS1 – Xe Đạp Trợ Lực Quốc Dân Cho Học Sinh, Sinh Viên?',
		'url'         => 'https://www.youtube.com/watch?v=8uhGlizP-gw',
		'youtube_id'  => '8uhGlizP-gw',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '06:15',
		'badge'       => 'Review Xe',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Đánh giá chi tiết mẫu xe trợ lực điện MS1: giá thành hợp lý, kiểu dáng thể thao và pin bền bỉ.',
	],
	[
		'title'       => 'Xe Điện Bluera Việt Nhật Hút Khách Tại Triển Lãm Autotech & Accessories 2024',
		'url'         => 'https://www.youtube.com/watch?v=DHrISK53OPs',
		'youtube_id'  => 'DHrISK53OPs',
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
		'youtube_id'  => 'xFo863UkIE4',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '06:25',
		'badge'       => 'Đánh Giá Xe',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Đánh giá chi tiết thiết kế khung sườn hợp kim, động cơ chống nước và khả năng tải trọng của AIE Smile I.',
	],
	[
		'title'       => 'Chạm Mặt Camelo I8 — Mẫu Xe Đạp Điện Đẹp Lạ Dành Cho Nàng Thơ',
		'url'         => 'https://www.youtube.com/watch?v=r4h0d9n4F2g',
		'youtube_id'  => 'r4h0d9n4F2g',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '07:15',
		'badge'       => 'Camelo I8',
		'is_featured' => 0,
		'cat'         => 'gioi-thieu-xe',
		'excerpt'     => 'Thiết kế bo tròn cổ điển phong cách Ý, màu sơn pastel thanh lịch cùng cốp xe rộng rãi.',
	],

	// ─── VIDEO NGẮN TIKTOK & SHORTS (VERTICAL 9:16) ───
	[
		'title'       => 'Điểm Tin Nhanh Triển Lãm Quốc Tế Xe Hai Bánh SECC Cùng AI EBIKE',
		'url'         => 'https://www.youtube.com/shorts/GbmzHlRXG24',
		'youtube_id'  => 'GbmzHlRXG24',
		'orientation' => 'vertical_9_16',
		'source'      => 'youtube',
		'duration'    => '00:45',
		'badge'       => 'Shorts 60s',
		'is_featured' => 0,
		'cat'         => 'tiktok-shorts',
		'excerpt'     => 'Không khí rộn ràng tại triển lãm xe điện SECC cùng hàng nghìn khách hàng trải nghiệm.',
	],
	[
		'title'       => 'Trải Nghiệm Tính Năng Thông Minh Trên Dòng Xe Điện AIE SMILE 2024',
		'url'         => 'https://www.youtube.com/shorts/YzBZK1FDI7I',
		'youtube_id'  => 'YzBZK1FDI7I',
		'orientation' => 'vertical_9_16',
		'source'      => 'youtube',
		'duration'    => '00:59',
		'badge'       => 'TikTok Hot',
		'is_featured' => 0,
		'cat'         => 'tiktok-shorts',
		'excerpt'     => 'Tính năng chống trộm thông minh, định vị GPS và kết nối ứng dụng điện thoại tiện lợi.',
	],
	[
		'title'       => 'Top 3 Mẫu Xe Đạp Điện Học Sinh Bán Chạy Nhất Tuần Này ⚡',
		'url'         => 'https://www.youtube.com/shorts/9bK0x98fEzo',
		'youtube_id'  => '9bK0x98fEzo',
		'orientation' => 'vertical_9_16',
		'source'      => 'youtube',
		'duration'    => '00:58',
		'badge'       => 'Bán Chạy',
		'is_featured' => 0,
		'cat'         => 'tiktok-shorts',
		'excerpt'     => 'Tổng hợp 3 mẫu xe điện thời trang, an toàn, được các bạn học sinh và phụ huynh săn đón nhiều nhất.',
	],
	[
		'title'       => 'Thử Nghiệm Lội Nước 40cm Với Xe Điện Bluera Chống Nước Chuẩn IP67 🌊',
		'url'         => 'https://www.youtube.com/shorts/gY8M9vR7qXU',
		'youtube_id'  => 'gY8M9vR7qXU',
		'orientation' => 'vertical_9_16',
		'source'      => 'youtube',
		'duration'    => '00:45',
		'badge'       => 'Chống Nước IP67',
		'is_featured' => 0,
		'cat'         => 'tiktok-shorts',
		'excerpt'     => 'Thử thách lội qua vùng nước ngập 40cm trong mưa lớn — động cơ và hệ thống điện vẫn hoạt động an toàn tuyệt đối.',
	],
	[
		'title'       => 'Mẹo Sạc Bình Ắc Quy Xe Điện Tăng Tuổi Thọ Gấp Đôi Bạn Cần Biết 🔋',
		'url'         => 'https://www.youtube.com/watch?v=1W7F3FvjVfM',
		'youtube_id'  => '1W7F3FvjVfM',
		'orientation' => 'horizontal_16_9',
		'source'      => 'youtube',
		'duration'    => '05:30',
		'badge'       => 'Mẹo Sử Dụng',
		'is_featured' => 0,
		'cat'         => 'huong-dan-su-dung',
		'excerpt'     => 'Chia sẻ kinh nghiệm thực tế về thời gian sạc, nhiệt độ sạc và cách bảo quản bình ắc quy bền đẹp theo năm tháng.',
	],
];

// 3. Find a sample product to link if available
$first_prod = get_posts( [
	'post_type'      => 'product',
	'posts_per_page' => 1,
	'post_status'    => 'publish',
] );
$sample_product_id = ! empty( $first_prod ) ? $first_prod[0]->ID : null;

// 4. Synchronize each video into the database
global $wpdb;
$added_count   = 0;
$updated_count = 0;

foreach ( $all_videos as $item ) {
	$yt_id = $item['youtube_id'] ?? '';
	$url   = $item['url'];
	$title = $item['title'];

	// Check if video already exists by youtube_id in meta or title
	$existing_id = null;
	if ( $yt_id ) {
		$found_by_meta = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE (meta_key = 'link_video' OR meta_key = 'video_url') AND meta_value LIKE %s LIMIT 1",
			'%' . $wpdb->esc_like( $yt_id ) . '%'
		) );
		if ( $found_by_meta ) {
			$existing_id = (int) $found_by_meta;
		}
	}

	if ( ! $existing_id ) {
		$found_by_title = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'video' LIMIT 1",
			$title
		) );
		if ( $found_by_title ) {
			$existing_id = (int) $found_by_title;
		}
	}

	if ( $existing_id ) {
		// Update existing post
		wp_update_post( [
			'ID'           => $existing_id,
			'post_title'   => $title,
			'post_content' => $item['excerpt'],
			'post_excerpt' => $item['excerpt'],
			'post_status'  => 'publish',
		] );
		$post_id = $existing_id;
		$updated_count++;
		echo "  ↻ Cập nhật video: [{$post_id}] {$title}\n";
	} else {
		// Create new post
		$post_id = wp_insert_post( [
			'post_title'   => $title,
			'post_content' => $item['excerpt'],
			'post_excerpt' => $item['excerpt'],
			'post_status'  => 'publish',
			'post_type'    => 'video',
		] );
		$added_count++;
		echo "  ✓ Thêm video mới: [{$post_id}] {$title}\n";
	}

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		// Update metadata & ACF
		update_post_meta( $post_id, 'link_video', $url );
		update_post_meta( $post_id, 'video_url', $url );
		update_post_meta( $post_id, 'video_source', $item['source'] );
		update_post_meta( $post_id, 'video_orientation', $item['orientation'] );
		update_post_meta( $post_id, 'video_duration', $item['duration'] );
		update_post_meta( $post_id, 'video_badge', $item['badge'] );
		update_post_meta( $post_id, 'is_featured', $item['is_featured'] ? '1' : '0' );

		if ( $sample_product_id ) {
			update_post_meta( $post_id, 'related_product', $sample_product_id );
		}

		// Taxonomy term
		if ( ! empty( $item['cat'] ) ) {
			wp_set_object_terms( $post_id, $item['cat'], 'video-cat' );
		}
	}
}

// 5. Ensure Video Hub Page exists & points to template
$video_page = get_posts( [
	'post_type'  => 'page',
	'meta_key'   => '_wp_page_template',
	'meta_value' => 'templates/template-page-video.php',
	'number'     => 1,
] );

if ( empty( $video_page ) ) {
	$page_by_path = get_page_by_path( 'video' );
	if ( $page_by_path ) {
		update_post_meta( $page_by_path->ID, '_wp_page_template', 'templates/template-page-video.php' );
		echo "  ✓ Gán template cho trang hiện có /video/ [ID: {$page_by_path->ID}]\n";
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
			echo "  ✓ Tạo trang mới 'Thư Viện Video' (/video/) [ID: {$new_page_id}]\n";
		}
	}
} else {
	echo "  • Trang Video Hub đã cấu hình: [ID: {$video_page[0]->ID}] {$video_page[0]->post_title} (/video/)\n";
}

echo "=== HOÀN TẤT ĐỒNG BỘ: Thêm mới {$added_count} video, cập nhật {$updated_count} video ===\n";
