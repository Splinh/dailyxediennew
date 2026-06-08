<?php
/**
 * Populate Home page flexible content and theme options with dailyxedien.vn mockup data.
 *
 * Run via WP-CLI:
 * php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-home-dailyxedien.php
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'update_field' ) ) {
	echo "⚠ ACF not active" . PHP_EOL;
	exit;
}

$home_id = (int) get_option( 'page_on_front' );
if ( ! $home_id ) {
	// Fallback to first page if no front page is set
	$pages = get_pages( [ 'number' => 1 ] );
	if ( ! empty( $pages ) ) {
		$home_id = $pages[0]->ID;
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
		echo "✓ Set page ID {$home_id} as Front Page" . PHP_EOL;
	} else {
		echo "⚠ No pages found to set as front page" . PHP_EOL;
		exit;
	}
}

echo "Front Page ID: {$home_id}" . PHP_EOL;

// ── Query dynamic product categories if WooCommerce is active ──
$cat_items = [];
if ( function_exists( 'get_terms' ) ) {
	$cats = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
		'number'     => 4,
	] );
	if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) {
		foreach ( $cats as $cat ) {
			$cat_items[] = $cat->term_id;
		}
	}
}

$cat_id_1 = $cat_items[0] ?? 0;
$cat_id_2 = $cat_items[1] ?? 0;
$cat_id_3 = $cat_items[2] ?? 0;
$cat_id_4 = $cat_items[3] ?? 0;

// ── Setup home sections ──
$home_sections = [
	// 1. HERO SLIDER
	[
		'acf_fc_layout' => 'hero_slider',
		'disable'       => 0,
		'slides'        => [
			[
				'bg_image' => 'https://images.unsplash.com/photo-1595054179361-b0e66d9bb7a3?auto=format&fit=crop&w=1920&q=80',
				'link'     => [ 'title' => 'Xem xe điện', 'url' => '#best-sellers', 'target' => '' ],
				'title'    => 'Banner Hè Sang Chảnh - Ưu đãi lớn',
			],
			[
				'bg_image' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=1920&q=80',
				'link'     => [ 'title' => 'Xem chi tiết', 'url' => '#best-sellers', 'target' => '' ],
				'title'    => 'Thế hệ xe điện thể thao bứt phá',
			]
		],
	],
	// 2. USP BAR
	[
		'acf_fc_layout' => 'usp_bar',
		'disable'       => 0,
		'features'      => [
			[
				'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
				'title' => 'MIỄN PHÍ GIAO HÀNG',
				'desc'  => 'Áp dụng bán kính lên đến 10km',
			],
			[
				'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>',
				'title' => '1 ĐỔI 1 TRONG 7 NGÀY',
				'desc'  => 'Nếu có lỗi sản xuất từ nhà máy',
			],
			[
				'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
				'title' => 'BẢO HÀNH CHÍNH HÃNG',
				'desc'  => 'Hệ thống đại lý ủy quyền uy tín',
			],
			[
				'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
				'title' => 'THANH TOÁN LINH HOẠT',
				'desc'  => 'Trả góp lãi suất 0% mượt mà',
			],
		],
	],
	// 3. CATEGORIES
	[
		'acf_fc_layout' => 'categories',
		'disable'       => 0,
		'title'         => 'Danh mục nổi bật',
		'subtitle'      => 'Chọn nhanh theo nhu cầu',
		'columns'       => '6',
	],
	// 4. BEST SELLERS
	[
		'acf_fc_layout' => 'best_sellers',
		'disable'       => 0,
		'title'         => 'Sản phẩm bán chạy',
		'tabs'          => [
			[ 'tab_title' => 'XE ĐIỆN', 'tab_icon' => 'bicycle', 'category' => $cat_id_1, 'count' => 5 ],
			[ 'tab_title' => 'XE 50CC', 'tab_icon' => 'motorcycle', 'category' => $cat_id_2, 'count' => 5 ],
			[ 'tab_title' => 'XE MÁY ĐIỆN', 'tab_icon' => 'bolt', 'category' => $cat_id_3, 'count' => 5 ],
			[ 'tab_title' => 'XE 3 BÁNH', 'tab_icon' => 'truck', 'category' => $cat_id_4, 'count' => 5 ],
		],
	],
	// 5. TECH SPOTLIGHT
	[
		'acf_fc_layout' => 'tech_spotlight',
		'disable'       => 0,
		'title'         => 'Công nghệ thông minh',
		'subtitle'      => 'Công nghệ bứt phá mọi giới hạn',
		'features'      => [
			[
				'feature_id'   => 'bms',
				'feature_name' => 'Quản lý Pin BMS',
				'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="16" height="12" rx="2" ry="2"/><line x1="22" y1="11" x2="22" y2="15"/><line x1="6" y1="11" x2="10" y2="11"/><line x1="8" y1="9" x2="8" y2="13"/></svg>',
				'title'        => 'Hệ thống Pin LFP & Quản lý Pin BMS Thông Minh',
				'description'  => 'Pin LFP thế hệ mới kết hợp cùng bộ mạch quản lý BMS giúp điều phối dòng xả tối ưu, kiểm soát nhiệt độ phòng tránh cháy nổ tuyệt đối và gia tăng tuổi thọ pin gấp 3 lần bình thường.',
				'image'        => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=400&q=80',
				'details'      => [
					[ 'label' => 'Tuổi thọ Pin', 'value' => '2.000 chu kỳ sạc/xả' ],
					[ 'label' => 'Quãng đường sạc', 'value' => '120km / một lần sạc' ],
					[ 'label' => 'Công nghệ bảo vệ', 'value' => 'Chống nước IP67 tuyệt đối' ],
				],
			],
			[
				'feature_id'   => 'fingerprint',
				'feature_name' => 'Mở khóa Vân Tay',
				'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-4.3-3-7-7-7s-7 2.7-7 7 3 7 7 7z"/><path d="M12 2a10 10 0 0 0-10 10c0 2.2.8 4.2 2 5.7"/><path d="M14 15a2 2 0 1 0-4 0"/></svg>',
				'title'        => 'Khóa Vân Tay Một Chạm Siêu Nhạy',
				'description'  => 'Công nghệ vân tay sinh trắc học tiên tiến tích hợp ngay trên tay lái. Nhận diện chỉ 0.1s, chống sao chép và bảo mật tuyệt đối cho phương tiện của bạn.',
				'image'        => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=400&q=80',
				'details'      => [
					[ 'label' => 'Tốc độ nhận diện', 'value' => '0.1 giây' ],
					[ 'label' => 'Dung lượng lưu trữ', 'value' => 'Đến 10 vân tay khác nhau' ],
					[ 'label' => 'Khóa cơ học dự phòng', 'value' => 'Tích hợp chìa CNC chống sao chép' ],
				],
			],
		],
	],
	// 6. PROMO BANNERS
	[
		'acf_fc_layout' => 'promo_banners',
		'disable'       => 0,
		'banners'       => [
			[
				'badge'       => 'Ưu đãi kép',
				'title'       => 'TRẢ GÓP 0%',
				'description' => 'Thủ tục nhanh chóng 15 phút, xét duyệt tức thì.',
				'gradient'    => 'blue',
				'link'        => [ 'title' => 'XEM NGAY', 'url' => '#consult-form' ],
			],
			[
				'badge'       => 'Chất lượng vàng',
				'title'       => 'ẮC QUY CHÍNH HÃNG',
				'description' => 'Bền bỉ vượt thời gian, an toàn tối đa cho xe của bạn.',
				'gradient'    => 'dark',
				'link'        => [ 'title' => 'MUA NGAY', 'url' => '#consult-form' ],
			],
			[
				'badge'       => 'Độc quyền',
				'title'       => 'PHỤ KIỆN CHÍNH HÃNG',
				'description' => 'Phụ tùng, trang trí đa dạng, nâng tầm xế yêu.',
				'gradient'    => 'green',
				'link'        => [ 'title' => 'XEM NGAY', 'url' => '#consult-form' ],
			],
		],
	],
	// 7. MEDIA REVIEWS
	[
		'acf_fc_layout' => 'media_reviews',
		'disable'       => 0,
		'video_title'    => 'Video nổi bật',
		'video_subtitle' => 'Trải nghiệm thực tế',
		'video_url'      => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
		'video_duration' => '04:35',
		'video_thumbnail'=> 'https://images.unsplash.com/photo-1595054179361-b0e66d9bb7a3?auto=format&fit=crop&w=1200&q=80',
		'playlist'       => [
			[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=300&q=80' ],
			[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=300&q=80' ],
			[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=300&q=80' ],
			[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => 'https://images.unsplash.com/photo-1595054179361-b0e66d9bb7a3?auto=format&fit=crop&w=300&q=80' ],
		],
		'testimonial_title'    => 'Cảm nhận khách hàng',
		'testimonial_subtitle' => 'Đánh giá thực tế',
		'testimonials'         => [
			[ 'name' => 'Nguyễn Minh Anh', 'location' => 'TP. Thủ Đức, TP.HCM', 'avatar_text' => 'MA', 'rating' => 5, 'comment' => '"Xe chạy êm, nhân viên hướng dẫn kỹ cách sạc và dùng định vị. Sạc đầy đi được khá xa. Rất hài lòng!"' ],
			[ 'name' => 'Trần Quốc Bảo', 'location' => 'Biên Hòa, Đồng Nai', 'avatar_text' => 'QB', 'rating' => 5, 'comment' => '"Giao xe nhanh, nhân viên tận tình hướng dẫn. Mình yên tâm hơn nhờ có quản lý pin và bảo hành rõ ràng."' ],
			[ 'name' => 'Hoàng Nam', 'location' => 'Quận 7, TP.HCM', 'avatar_text' => 'HN', 'rating' => 5, 'comment' => '"Dịch vụ bảo dưỡng vàng 3 năm cực chu đáo. Hệ thống đại lý chuyên nghiệp, đáng tin cậy lắm!"' ],
		]
	],
	// 8. EVENT GALLERY
	[
		'acf_fc_layout' => 'event_gallery',
		'disable'       => 0,
		'title'         => 'Hình ảnh sự kiện',
		'subtitle'      => 'Hoạt động tại cửa hàng',
		'gallery'       => [
			[ 'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=400&q=80', 'caption' => 'Khai trương đại lý mới' ],
			[ 'image' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=400&q=80', 'caption' => 'Tri ân khách hàng' ],
			[ 'image' => 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=400&q=80', 'caption' => 'Lái thử xe điện' ],
			[ 'image' => 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?auto=format&fit=crop&w=400&q=80', 'caption' => 'Bảo dưỡng miễn phí' ],
			[ 'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?auto=format&fit=crop&w=400&q=80', 'caption' => 'Ngày hội công nghệ' ],
			[ 'image' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=400&q=80', 'caption' => 'Chuyển giao công nghệ' ],
		],
	],
	// 9. STORE LOCATOR
	[
		'acf_fc_layout' => 'store_locator',
		'disable'       => 0,
		'title'         => 'Hệ thống cửa hàng & đại lý ủy quyền',
		'subtitle'      => 'Tìm địa chỉ đại lý gần bạn nhất',
		'stores'        => [
			[
				'name'     => 'Đại lý Bluera Việt Nhật An Duy',
				'province' => 'AN GIANG',
				'phone'    => '0838149149',
				'type'     => 'authorized',
				'address'  => 'Ngã 3 chợ cũ, Thôn 7, Xã Lộc An, Huyện Bảo Lâm, Tỉnh Lâm Đồng',
				'map_url'  => 'https://maps.google.com/?q=Ngã 3 chợ cũ, Thôn 7, Xã Lộc An, Huyện Bảo Lâm, Tỉnh Lâm Đồng',
			],
			[
				'name'     => 'Đại lý Bluera Việt Nhật Bảo Thắng',
				'province' => 'AN GIANG',
				'phone'    => '0918224868',
				'type'     => 'authorized',
				'address'  => 'A254/5 Bàu Bàng, Chánh Nghĩa, TP. Thủ Dầu Một, Bình Dương',
				'map_url'  => 'https://maps.google.com/?q=A254/5 Bàu Bàng, Chánh Nghĩa, Thủ Dầu Một, Bình Dương',
			],
			[
				'name'     => 'Đại lý Bluera Việt Nhật Chung Chín',
				'province' => 'AN GIANG',
				'phone'    => '0975000151',
				'type'     => 'authorized',
				'address'  => 'C11/1 Quốc Lộ 1A, Khu phố 3, Thị Trấn Tân Túc, Bình Chánh, Tp. HCM',
				'map_url'  => 'https://maps.google.com/?q=C11/1 Quốc Lộ 1A, Khu phố 3, Thị Trấn Tân Túc, Bình Chánh, Tp. HCM',
			],
			[
				'name'     => 'Đại lý Bluera Việt Nhật Vũng Tàu Xanh',
				'province' => 'BÀ RỊA - VŨNG TÀU',
				'phone'    => '0933505222',
				'type'     => 'authorized',
				'address'  => '150 Ba Cu, Phường 3, Thành phố Vũng Tàu, Bà Rịa - Vũng Tàu',
				'map_url'  => 'https://maps.google.com/?q=150 Ba Cu, Phường 3, Vũng Tàu, Bà Rịa - Vũng Tàu',
			],
			[
				'name'     => 'Cửa hàng Bluera Thuận An',
				'province' => 'BÌNH DƯƠNG',
				'phone'    => '0909123456',
				'type'     => 'authorized',
				'address'  => '45 Đại Lộ Bình Dương, Thuận Giao, Thuận An, Bình Dương',
				'map_url'  => 'https://maps.google.com/?q=45 Đại Lộ Bình Dương, Thuận Giao, Thuận An, Bình Dương',
			],
			[
				'name'     => 'Đại lý Đồng Xoài Motor',
				'province' => 'BÌNH PHƯỚC',
				'phone'    => '0911223344',
				'type'     => 'authorized',
				'address'  => '888 Phú Riềng Đỏ, Tân Xuân, Đồng Xoài, Bình Phước',
				'map_url'  => 'https://maps.google.com/?q=888 Phú Riềng Đỏ, Tân Xuân, Đồng Xoài, Bình Phước',
			],
			[
				'name'     => 'Showroom Bluera Việt Nhật Thủ Đức',
				'province' => 'TP. HỒ CHÍ MINH',
				'phone'    => '0933505222',
				'type'     => 'authorized',
				'address'  => '466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP.HCM',
				'map_url'  => 'https://maps.google.com/?q=466 Nguyễn Duy Trinh, Bình Trưng Đông, Thủ Đức, Hồ Chí Minh',
			],
			[
				'name'     => 'Showroom Bluera Việt Nhật Gò Vấp',
				'province' => 'TP. HỒ CHÍ MINH',
				'phone'    => '0938123456',
				'type'     => 'authorized',
				'address'  => '539 Quang Trung, P. 10, Q. Gò Vấp, TP.HCM',
				'map_url'  => 'https://maps.google.com/?q=539 Quang Trung, Gò Vấp, Hồ Chí Minh',
			],
			[
				'name'     => 'Showroom Bluera Việt Nhật Bình Tân',
				'province' => 'TP. HỒ CHÍ MINH',
				'phone'    => '0901224567',
				'type'     => 'authorized',
				'address'  => '621 Tên Lửa, P. Bình Trị Đông B, Q. Bình Tân, TP.HCM',
				'map_url'  => 'https://maps.google.com/?q=621 Tên Lửa, Bình Trị Đông B, Bình Tân, Hồ Chí Minh',
			]
		],
	],
	// 10. BRANDS
	[
		'acf_fc_layout' => 'brands',
		'disable'       => 0,
		'title'         => 'ĐỐI TÁC ĐỒNG HÀNH',
		'subtitle'      => 'Các nhãn hiệu liên kết phân phối trực tiếp',
		'brands'        => [
			[ 'name' => 'VINFAST', 'sub_label' => 'Smart E-Scooter', 'logo' => '', 'link' => '#' ],
			[ 'name' => 'YADEA', 'sub_label' => 'Global Brand', 'logo' => '', 'link' => '#' ],
			[ 'name' => 'DIBAO', 'sub_label' => 'Taiwan Quality', 'logo' => '', 'link' => '#' ],
			[ 'name' => 'OSAKAR', 'sub_label' => 'Japan Standard', 'logo' => '', 'link' => '#' ],
			[ 'name' => 'KAZUKI', 'sub_label' => 'Modern Look', 'logo' => '', 'link' => '#' ],
			[ 'name' => 'TAILG', 'sub_label' => 'Premium Quality', 'logo' => '', 'link' => '#' ],
			[ 'name' => 'NIJIA', 'sub_label' => 'Youthful Style', 'logo' => '', 'link' => '#' ],
			[ 'name' => 'DK BIKE', 'sub_label' => 'Eco-Friendly', 'logo' => '', 'link' => '#' ],
		],
	],
	// 11. NEWS
	[
		'acf_fc_layout' => 'news',
		'disable'       => 0,
		'title'         => 'Tin tức nổi bật',
		'subtitle'      => 'Cập nhật tin tức và mẹo vặt sử dụng xe hữu ích',
		'count'         => 3,
	],
	// 12. CONSULT FORM
	[
		'acf_fc_layout' => 'consult_form',
		'disable'       => 0,
		'title'         => 'ĐĂNG KÝ NHẬN KHUYẾN MÃI LỚN',
		'subtitle'      => 'Cơ hội nhận gói bảo dưỡng vàng 3 năm & quà tặng đặc biệt trị giá lên đến 3 triệu đồng.',
		'cf7_shortcode' => '',
	],
];

update_field( 'home_sections', $home_sections, $home_id );
echo "✓ Home page flexible content populated successfully!" . PHP_EOL;

// ── Setup option fields ──
update_field( 'hotline_label', 'Hotline tư vấn 24/7', 'option' );
update_field( 'hotline', '0933 505 222', 'option' );
update_field( 'email', 'info@dailyxedien.vn', 'option' );
update_field( 'address', '466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP.HCM', 'option' );
update_field( 'logo_tagline', 'Hệ thống xe điện lớn nhất Việt Nam', 'option' );
update_field( 'footer_desc', 'Dailyxedien.vn - Hệ thống phân phối xe điện, xe 50cc, xe máy điện chính hãng. Cam kết sản phẩm rõ nguồn gốc, chính sách giá minh bạch và hậu mãi dễ theo dõi.', 'option' );
update_field( 'website_url', 'https://www.dailyxedien.vn', 'option' );
update_field( 'facebook_url', 'https://www.facebook.com/DaiLyXeDien/', 'option' );
update_field( 'youtube_url', 'https://www.youtube.com/@XeDien', 'option' );
update_field( 'tiktok_url', 'https://www.tiktok.com/@dailyxedienhcm', 'option' );
update_field( 'zalo_url', 'https://zalo.me/0933505222', 'option' );

// topbar links
$topbar_links = [
	[ 'link' => [ 'title' => 'Sứ Mệnh', 'url' => home_url( '/su-menh/' ) ] ],
	[ 'link' => [ 'title' => 'Cơ Hội Hợp Tác', 'url' => home_url( '/co-hoi-hop-tac/' ) ] ],
	[ 'link' => [ 'title' => 'Hệ Thống Cửa Hàng', 'url' => home_url( '/he-thong-cua-hang/' ) ] ],
	[ 'link' => [ 'title' => 'Tin Tức', 'url' => home_url( '/tin-tuc/' ) ] ],
];
update_field( 'topbar_links', $topbar_links, 'option' );

// product trust
$product_trust = [
	[ 'icon' => 'truck', 'text' => 'Miễn phí giao hàng bán kính 10km' ],
	[ 'icon' => 'clock', 'text' => 'Giao hàng nhanh chóng' ],
	[ 'icon' => 'return', 'text' => 'Đổi trả miễn phí 7 ngày đầu' ],
];
update_field( 'product_trust', $product_trust, 'option' );

echo "✓ Theme Options populated successfully!" . PHP_EOL;
echo "=== POPULATE COMPLETED ===" . PHP_EOL;
