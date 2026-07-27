<?php
/**
 * Populate About Page (Giới Thiệu) with authentic data & images matching Unila style.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'update_field' ) ) {
	echo "⚠ ACF not active" . PHP_EOL;
	exit;
}

echo "=== START POPULATE ABOUT PAGE (GIỚI THIỆU) ===" . PHP_EOL;

// 1. Get or create the About page
$page = get_page_by_path( 'gioi-thieu' );
if ( ! $page ) {
	$page = get_page_by_path( 've-chung-toi' );
}

if ( ! $page ) {
	$page_id = wp_insert_post( [
		'post_title'    => 'Giới Thiệu',
		'post_name'     => 'gioi-thieu',
		'post_status'   => 'publish',
		'post_type'     => 'page',
		'page_template' => 'templates/template-page-about.php',
	] );
	echo "✓ Created page 'Giới Thiệu' (ID: $page_id)" . PHP_EOL;
} else {
	$page_id = $page->ID;
	update_post_meta( $page_id, '_wp_page_template', 'templates/template-page-about.php' );
	echo "✓ Found existing page 'Giới Thiệu' (ID: $page_id)" . PHP_EOL;
}

// 2. Build rich about_sections Flexible Content array
$about_sections = [
	// Section 1: Hero
	[
		'acf_fc_layout' => 'about_hero',
		'disable'       => 0,
		'tag'           => 'Hệ Thống Xe Điện Uy Tín Hàng Đầu',
		'title'         => 'Hành Trình Mang Lại Giải Pháp Di Chuyển Xanh & Bền Vững',
		'description'   => 'DailyXeDien.vn tự hào là hệ thống phân phối xe điện, xe máy điện và xe 50cc chính hãng lớn nhất khu vực phía Nam với chuỗi 20+ showroom hiện đại.',
	],

	// Section 2: Story
	[
		'acf_fc_layout' => 'about_story',
		'disable'       => 0,
		'title'         => 'Chúng tôi là ai?',
		'content'       => '<strong>DailyXeDien.vn</strong> là hệ thống phân phối xe điện, xe máy điện và xe 50cc chính hãng, hoạt động với mục tiêu mang đến trải nghiệm mua xe rõ ràng, minh bạch cho mọi khách hàng.<br><br>Chúng tôi không chỉ bán xe — chúng tôi hướng dẫn khách hàng chọn đúng sản phẩm phù hợp với nhu cầu thực tế. Từ xe đi làm hằng ngày, xe đi học cho con, đến xe vận chuyển nhỏ cho kinh doanh — mỗi dòng xe đều được tư vấn kỹ lưỡng.',
		'badge_number'  => 'Uy tín hàng đầu',
		'badge_label'   => 'Đại lý ủy quyền chính hãng',
	],

	// Section 3: CEO Message ("Thông điệp từ trái tim")
	[
		'acf_fc_layout' => 'about_ceo',
		'disable'       => 0,
		'tag'           => 'Thông điệp từ trái tim',
		'title'         => 'THÔNG ĐIỆP TỪ BAN GIÁM ĐỐC',
		'subtitle'      => 'Tâm thế phụng sự & Khát vọng xanh hóa giao thông Việt Nam',
		'content'       => 'Tôi bắt đầu hành trình này không từ những điều to lớn, mà từ những điều giản dị nhất – chính là niềm đam mê mang đến cho người tiêu dùng Việt Nam phương tiện di chuyển thông minh, an toàn và tiết kiệm.<br><br>Từ lúc bắt đầu chặng đường khởi nghiệp đầy thử thách, tôi luôn kiên định xây dựng DailyXeDien.vn không chỉ lớn lên bằng con số, mà còn tạo ra <strong>giá trị thực</strong>. Những giá trị này được hình thành từ sự tận tâm phụng sự khách hàng và niềm tự hào thương hiệu Việt.<br><br>Theo đuổi hạnh phúc và sự an tâm của mọi gia đình khi chọn mua xe điện – đó chính là triết lý, là động lực và niềm tin lớn nhất của chúng tôi.<br><br>Chúng tôi không chỉ bán xe – chúng tôi đang kiến tạo một tương lai giao thông xanh bền vững cho thế hệ mai sau.',
		'ceo_name'      => 'ÔNG NGUYỄN VĂN ĐỨC',
		'ceo_title'     => 'TỔNG GIÁM ĐỐC / FOUNDER DAILYXEDIEN',
		'ceo_avatar'    => 'https://dailyxedien.vn/wp-content/uploads/2026/02/khai-truong-dai-ly-xe-dien-bluera-viet-nhat-ron-bike-pro-tai-can-tho-dlxd.jpg',
	],

	// Section 4: Timeline ("Từng mốc dấu ấn")
	[
		'acf_fc_layout' => 'about_timeline',
		'disable'       => 0,
		'title'         => 'Từng mốc dấu ấn',
		'items'         => [
			[
				'year'  => '2015',
				'desc'  => 'Khởi đầu. Thành lập cửa hàng đầu tiên tại TP. Thủ Đức, chuyên xe đạp điện và xe máy điện cho học sinh, sinh viên.',
				'image' => 'https://dailyxedien.vn/wp-content/uploads/2026/02/khai-truong-dai-ly-xe-dien-bluera-viet-nhat-ron-bike-pro-tai-can-tho-dlxd.jpg',
			],
			[
				'year'  => '2016',
				'desc'  => 'Bước tiến đầu. Khai trương showroom xe điện 3S đạt chuẩn đầu tiên với trung tâm bảo hành kỹ thuật chuyên sâu.',
				'image' => 'https://bluerabike.com/wp-content/uploads/2024/09/bluera-bieu-tuong-xe-dien.jpg',
			],
			[
				'year'  => '2018',
				'desc'  => 'Mở rộng. Hệ thống đạt 5 showroom chính hãng tại các quận trung tâm TP.HCM, hợp tác cùng Bluera & VinFast.',
				'image' => 'https://unila.com.vn/wp-content/uploads/2024/10/gia-cong-my-pham-sach-unila-nha-may.jpg',
			],
			[
				'year'  => '2022',
				'desc'  => 'Tái cấu trúc. Chuẩn hóa dịch vụ hậu mãi, bảo hành chính hãng 3 năm và triển khai chính sách trả góp 0% lãi suất.',
				'image' => 'https://unila.com.vn/wp-content/uploads/2026/04/XUONG.jpg',
			],
			[
				'year'  => '2023',
				'desc'  => 'Chuyển mình. Tập trung nghiên cứu và phát triển hệ thống bán hàng đa kênh Omnichannel, nâng cao trải nghiệm mua sắm.',
				'image' => 'https://unila.com.vn/wp-content/uploads/2025/07/nha-may-gia-cong-my-pham-1.jpg',
			],
			[
				'year'  => '2024',
				'desc'  => 'Kỷ nguyên mới. Ra mắt nền tảng bán hàng trực tuyến DailyXeDien.vn và ứng dụng tra cứu bảo hành điện tử.',
				'image' => 'https://unila.com.vn/wp-content/uploads/2026/04/LAB.jpg',
			],
			[
				'year'  => '2025',
				'desc'  => 'Đồng hành. Ký kết hợp tác chiến lược nhượng quyền chuỗi đại lý ủy quyền cùng nhà máy sản xuất Bluera Việt Nhật.',
				'image' => 'https://unila.com.vn/wp-content/uploads/2024/10/gia-cong-my-pham-sach-unila-xu-the-moi.jpg',
			],
			[
				'year'  => '2026',
				'desc'  => 'Tiến xa. Cột mốc 20+ showroom và trung tâm bảo hành ủy quyền chính hãng trên toàn quốc.',
				'image' => 'https://unila.com.vn/wp-content/uploads/2025/07/nha-may-gia-cong-my-pham-4.jpg',
			],
			[
				'year'  => 'Đến 2030',
				'desc'  => 'Khẳng định. Hướng tới mốc 50+ showroom, tiên phong hệ sinh thái di chuyển xanh bền vững tại Việt Nam.',
				'image' => 'https://unila.com.vn/wp-content/uploads/2025/07/nha-may-gia-cong-my-pham-1.jpg',
			],
		],
	],

	// Section 5: Stats ("Những con số biết nói" & "Lời hứa của DailyXeDien.vn")
	[
		'acf_fc_layout' => 'about_stats',
		'disable'       => 0,
		'title'         => 'Những con số biết nói',
		'subtitle'      => 'LỜI HỨA CỦA DAILYXEDIEN.VN',
		'image'         => 'https://unila.com.vn/wp-content/uploads/2026/03/NHUNG-CON-SO-BIET-NOI-e1775466007157.png',
		'stats'         => [
			[ 'number' => '0%', 'label' => 'Tỉ lệ hàng giả, hàng nhái & linh kiện không rõ nguồn gốc' ],
			[ 'number' => '100%', 'label' => 'Xe điện chính hãng, đầy đủ kiểm định CO/CQ' ],
			[ 'number' => '20+', 'label' => 'Showroom & trung tâm bảo hành ủy quyền toàn quốc' ],
			[ 'number' => '10,000+', 'label' => 'Khách hàng tin dùng và đánh giá hài lòng 5 sao' ],
		],
		'promises'      => [
			[
				'title' => 'Chất lượng bền vững',
				'desc'  => 'Mỗi chiếc xe xuất kho đều trải qua quy trình kiểm tra 18 bước khắt khe. Chúng tôi cam kết 100% khung sườn, động cơ và ắc quy/pin Lithium đạt tiêu chuẩn an toàn tuyệt đối trước khi bàn giao cho khách hàng.',
			],
			[
				'title' => 'Năng lực chuyên môn sâu',
				'desc'  => 'Đội ngũ kỹ sư và kỹ thuật viên được đào tạo trực tiếp từ các hãng sản xuất tên tuổi như Bluera, VinFast, Yadea. Xử lý sự cố chính xác, hỗ trợ sửa chữa lưu động và bảo dưỡng định kỳ tận tâm.',
			],
			[
				'title' => 'Sự minh bạch tuyệt đối',
				'desc'  => 'Chúng tôi công khai 100% giá niêm yết, chính sách bảo hành bằng văn bản rõ ràng và cam kết không có bất kỳ chi phí ẩn nào. Khách hàng luôn biết trước toàn bộ chi phí trước khi xuống tiền mua xe.',
			],
			[
				'title' => 'Tâm thế đồng hành lâu dài',
				'desc'  => 'DailyXeDien.vn không dừng lại ở khâu bán xe, chúng tôi đồng hành cùng khách hàng trên mọi nẻo đường. Dịch vụ cứu hộ xe điện 24/7 và ứng dụng tra cứu lịch sử bảo hành giúp bạn hoàn toàn an tâm sử dụng.',
			],
		],
	],
];

// Update ACF field
update_field( 'about_sections', $about_sections, $page_id );
flush_rewrite_rules();

echo "✓ Successfully populated About Page sections for Page ID: $page_id!" . PHP_EOL;
echo "=== FINISHED POPULATE ABOUT PAGE ===" . PHP_EOL;
