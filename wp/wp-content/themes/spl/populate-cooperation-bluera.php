<?php
/**
 * Populate Cooperation Page (Cơ Hội Hợp Tác) with authentic Bluera Việt Nhật data.
 *
 * Source: https://bluerabike.com/tuyen-dai-ly-xe-dien/
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'update_field' ) ) {
	echo "⚠ ACF not active" . PHP_EOL;
	exit;
}

echo "=== START POPULATE COOPERATION PAGE (BLUERA VIỆT NHẬT) ===" . PHP_EOL;

// 1. Get or create the Cooperation page
$page = get_page_by_path( 'co-hoi-hop-tac' );

if ( ! $page ) {
	$page_id = wp_insert_post( [
		'post_title'   => 'Cơ Hội Hợp Tác',
		'post_name'    => 'co-hoi-hop-tac',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'page_template' => 'templates/template-page-cooperation.php',
	] );
	echo "✓ Created page 'Cơ Hội Hợp Tác' (ID: $page_id)" . PHP_EOL;
} else {
	$page_id = $page->ID;
	// Ensure template is assigned
	update_post_meta( $page_id, '_wp_page_template', 'templates/template-page-cooperation.php' );
	echo "✓ Found existing page 'Cơ Hội Hợp Tác' (ID: $page_id)" . PHP_EOL;
}

// 2. Build rich cooperation_sections ACF Flexible Content array
$cooperation_sections = [
	// Section 1: Hero
	[
		'acf_fc_layout' => 'cooperation_hero',
		'disable'       => 0,
		'tagline'       => 'Tuyển Đại Lý Xe Điện Bluera Việt Nhật 2026',
		'title'         => "Chuỗi Nhượng Quyền Xe Điện Chính Hãng\nĐảm Bảo 100%",
		'subtitle'      => 'Nhà sản xuất uy tín – Đa dạng mẫu mã – Giá tốt nhất thị trường. Bluera Việt Nhật sở hữu 03 nhà máy sản xuất hiện đại, chuyên cung cấp các dòng xe đạp điện, xe máy điện và xe 3 bánh điện chất lượng cao tích hợp APP kết nối thông minh.',
		'btn_text_1'    => 'Đăng ký tư vấn ngay',
		'btn_link_1'    => '#register-form',
		'btn_text_2'    => 'Xem gói chiết khấu',
		'btn_link_2'    => '#packages',
		'stats'         => [
			[ 'stat_number' => '03', 'stat_label' => 'Nhà máy sản xuất' ],
			[ 'stat_number' => '36', 'stat_label' => 'Tháng bảo hành' ],
			[ 'stat_number' => '35%', 'stat_label' => 'Chiết khấu tối đa' ],
			[ 'stat_number' => '100%', 'stat_label' => 'Độc quyền khu vực' ],
		],
	],

	// Section 2: Benefits
	[
		'acf_fc_layout' => 'cooperation_benefits',
		'disable'       => 0,
		'title'         => 'Quyền lợi & Chính sách hợp tác cùng Bluera Việt Nhật',
		'subtitle'      => 'Đồng hành phát triển bền vững – Tối ưu hóa lợi nhuận – Giảm thiểu mọi rủi ro kinh doanh',
		'cards'         => [
			[
				'icon'        => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
				'title'       => 'Chiết khấu hấp dẫn & Thưởng lớn',
				'description' => 'Chính sách chiết khấu cạnh tranh từ 20% đến 35%. Cơ chế thưởng nóng doanh số Tháng/Quý/Năm cực kỳ giá trị cho đại lý xuất sắc.'
			],
			[
				'icon'        => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2.5 3 6 3s6-1 6-3v-5"/></svg>',
				'title'       => 'Nguồn hàng 03 nhà máy ổn định',
				'description' => 'Sở hữu 03 nhà máy lắp ráp quy mô lớn đảm bảo nguồn cung liên tục. Thường xuyên cập nhật các mẫu xe điện công nghệ AI mới nhất.'
			],
			[
				'icon'        => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l11 11"/></svg>',
				'title'       => 'Hỗ trợ Marketing & Quảng cáo A-Z',
				'description' => 'Cung cấp bộ nhận diện thương hiệu, biển bảng, hình ảnh/video sản phẩm chất lượng cao và hỗ trợ ngân sách quảng cáo khu vực.'
			],
			[
				'icon'        => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
				'title'       => 'Đào tạo bài bản & Kỹ thuật chuyên sâu',
				'description' => 'Chuyển giao quy trình tư vấn bán hàng, kỹ năng chốt sale và đào tạo tay nghề sửa chữa/bảo hành chuyên nghiệp cho nhân viên.'
			],
			[
				'icon'        => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
				'title'       => 'Giải pháp App quản lý độc quyền',
				'description' => 'Tăng lợi nhuận bằng ứng dụng quản lý thông minh miễn phí, hỗ trợ mở rộng các mô hình kinh doanh mới như cho thuê xe điện.'
			],
			[
				'icon'        => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>',
				'title'       => 'Đổi trả linh hoạt & Bảo vệ độc quyền',
				'description' => 'Chính sách đổi trả hàng linh hoạt giải quyết rủi ro tồn kho. Cam kết bảo vệ vùng bán độc quyền, không cạnh tranh giá nội bộ.'
			]
		],
	],

	// Section 3: Packages
	[
		'acf_fc_layout' => 'cooperation_packages',
		'disable'       => 0,
		'title'         => 'Các gói nhượng quyền & Mức chiết khấu',
		'subtitle'      => 'Lựa chọn gói hợp tác tối ưu nhất cho quy mô tài chính và diện tích mặt bằng của bạn',
		'packages'      => [
			[
				'badge'       => 'Cơ bản khởi nghiệp',
				'title'       => 'Đại lý Cấp 3 (Điểm bán ủy quyền)',
				'subtitle'    => 'Dành cho cửa hàng quy mô nhỏ, điểm kinh doanh mới',
				'discount'    => '20%',
				'details'     => "Nhập hàng tối thiểu từ 10 xe/tháng\nHỗ trợ bộ nhận diện & tranh ảnh trang trí\nĐào tạo quy trình tư vấn bán hàng căn bản\nCung cấp đầy đủ giấy chứng nhận CO/CQ chính hãng\nx Bảo vệ độc quyền vùng bán theo Tỉnh\nx Ngân sách chạy quảng cáo riêng",
				'btn_text'    => 'Đăng ký tư vấn',
				'btn_link'    => '#register-form',
				'is_featured' => 0,
			],
			[
				'badge'       => 'Khuyên dùng - Phổ biến nhất',
				'title'       => 'Đại lý Cấp 2 (Showroom Tiêu Chuẩn)',
				'subtitle'    => 'Dành cho đại lý chuyên nghiệp muốn bứt phá doanh thu',
				'discount'    => '28%',
				'details'     => "Nhập hàng tối thiểu từ 30 xe/tháng\nHỗ trợ thiết kế 3D & thi công biển bảng Showroom\nĐào tạo bán hàng + kỹ thuật sửa chữa chuyên sâu\nBảo vệ độc quyền vùng bán theo Quận / Huyện\nMiễn phí sử dụng App quản lý vận hành thông minh\nx Ngân sách tài trợ sự kiện Khai trương",
				'btn_text'    => 'Đăng ký ngay',
				'btn_link'    => '#register-form',
				'is_featured' => 1,
			],
			[
				'badge'       => 'Đối tác chiến lược',
				'title'       => 'Đại lý Cấp 1 (Nhà Phân Phối Tỉnh)',
				'subtitle'    => 'Quy mô Showroom lớn, độc quyền phát triển toàn tỉnh',
				'discount'    => '35%',
				'details'     => "Nhập hàng từ 80 xe/tháng\nĐộc quyền phân phối trên toàn bộ Tỉnh / Thành phố\nTài trợ 100% ngân sách Marketing & Chạy Ads phủ sóng\nĐào tạo VIP + tham quan 03 nhà máy Bluera Việt Nhật\nCơ chế thưởng vượt mốc doanh số & Du lịch nước ngoài\nHỗ trợ làm lễ Khai trương hoành tráng & Truyền thông báo chí",
				'btn_text'    => 'Trở thành Nhà Phân Phối',
				'btn_link'    => '#register-form',
				'is_featured' => 0,
			]
		],
	],

	// Section 4: Process
	[
		'acf_fc_layout' => 'cooperation_process',
		'disable'       => 0,
		'title'         => 'Quy trình 4 bước đăng ký mở Đại lý',
		'subtitle'      => 'Quy trình chuẩn hóa – Hỗ trợ setup siêu tốc – Sẵn sàng vận hành ngay',
		'steps'         => [
			[
				'icon'        => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
				'title'       => '1. Điền Form Đăng Ký',
				'description' => 'Đăng ký thông tin trực tuyến hoặc liên hệ trực tiếp Hotline 0933 505 222.'
			],
			[
				'icon'        => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
				'title'       => '2. Tư Vấn & Thẩm Định',
				'description' => 'Chuyên viên tư vấn chính sách, khảo sát vị trí mặt bằng và lập phương án kinh doanh.'
			],
			[
				'icon'        => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H9H8"/></svg>',
				'title'       => '3. Ký Hợp Đồng & Setup',
				'description' => 'Ký kết hợp đồng đại lý, đội ngũ thiết kế & thi công setup cửa hàng chuẩn A-Z.'
			],
			[
				'icon'        => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4.5 16.5c-1.5 1.25-2.5 3.5-2.5 3.5h20c0 0-1-2.25-2.5-3.5L12 2 4.5 16.5z"/></svg>',
				'title'       => '4. Khai Trương & Vận Hành',
				'description' => 'Giao nhận sản phẩm, đào tạo kỹ thuật, chạy chiến dịch marketing khai trương bùng nổ.'
			]
		],
	],

	// Section 5: Form & Contact & FAQs
	[
		'acf_fc_layout' => 'cooperation_form',
		'disable'       => 0,
		'form_title'    => 'Đăng ký tư vấn mở Đại lý / Cửa hàng ủy quyền',
		'form_subtitle' => 'Để lại thông tin bên dưới, chuyên viên phát triển thị trường Bluera Việt Nhật sẽ liên hệ tư vấn chi tiết trong 24h.',
		'contact_title' => 'Liên hệ trực tiếp phòng phát triển Đại lý',
		'contact_subtitle' => 'Đội ngũ phát triển thị trường Bluera Việt Nhật sẵn sàng hỗ trợ tư vấn 24/7:',
		'contacts'      => [
			[
				'icon'  => '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
				'label' => 'Hotline tư vấn đại lý 24/7',
				'value' => '0933 505 222 (Phòng Kinh Doanh Bluera)',
			],
			[
				'icon'  => '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
				'label' => 'Email liên hệ đối tác',
				'value' => 'daily@bluerabike.com / info@dailyxedien.vn',
			],
			[
				'icon'  => '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
				'label' => 'Trụ sở chính & Showroom',
				'value' => '466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP.HCM',
			],
			[
				'icon'  => '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
				'label' => 'Thời gian làm việc',
				'value' => '08:00 – 17:30, Thứ 2 – Thứ 7 (Hỗ trợ Hotline 24/7)',
			]
		],
		'faq_title'     => 'Những câu hỏi thường gặp khi kinh doanh Xe Điện',
		'faqs'          => [
			[
				'question' => 'Giấy tờ pháp lý cần thiết để kinh doanh cửa hàng xe điện?',
				'answer'   => 'Bạn cần Giấy ĐKKD (doanh nghiệp hoặc hộ kinh doanh) và Giấy PCCC. Khi hợp tác với Bluera Việt Nhật, toàn bộ bộ hồ sơ kiểm định CQ, chứng nhận nguồn gốc xuất xứ CO của xe sẽ được cung cấp đầy đủ chuẩn Bộ GTVT.'
			],
			[
				'question' => 'Vốn đầu tư ban đầu tối thiểu bao nhiêu?',
				'answer'   => 'Vốn từ 50 triệu (Đại lý cấp 3) đến 200+ triệu (Showroom cấp 1). Số tiền này 100% dành cho tiền nhập hàng đợt đầu, Bluera hoàn toàn không thu bất kỳ khoản phí nhượng quyền thương hiệu nào.'
			],
			[
				'question' => 'Chưa có kinh nghiệm sửa chữa/bán xe điện có mở cửa hàng được không?',
				'answer'   => 'Hoàn toàn được. Bluera Việt Nhật sẽ cử chuyên gia hỗ trợ đào tạo 1-1 về quy trình tư vấn bán hàng, kỹ năng chốt sale và đào tạo kỹ thuật sửa chữa/bảo hành chi tiết cho đội ngũ nhân viên cửa hàng.'
			],
			[
				'question' => 'Chính sách bảo hành và linh kiện thay thế như thế nào?',
				'answer'   => 'Tất cả các dòng xe điện Bluera đều được bảo hành chính hãng đến 36 tháng. Sở hữu 03 nhà máy quy mô lớn đảm bảo nguồn cung linh kiện chính hãng sẵn có 100%, hỗ trợ đổi trả linh hoạt tránh tồn kho.'
			]
		]
	]
];

// Update ACF field on page
update_field( 'cooperation_sections', $cooperation_sections, $page_id );

echo "✓ Populated ACF field 'cooperation_sections' on Page ID $page_id" . PHP_EOL;
echo "=== POPULATE COOPERATION PAGE COMPLETED ===" . PHP_EOL;
