<?php
/**
 * Populate About Page ("Giới Thiệu") flexible content with authentic data.
 *
 * Can be run via WP-CLI:
 *   php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-about-dailyxedien.php
 * Or directly via PHP CLI:
 *   php wp/wp-content/themes/spl/populate-about-dailyxedien.php
 *
 * @package SPL
 */

if ( ! defined( 'ABSPATH' ) ) {
	$load_path = dirname( __DIR__, 3 ) . '/wp-load.php';
	if ( file_exists( $load_path ) ) {
		require_once $load_path;
	} else {
		echo "⚠ wp-load.php not found.\n";
		exit( 1 );
	}
}

// Security: If accessed via browser (not CLI), require administrator capability
if ( ! defined( 'WP_CLI' ) && PHP_SAPI !== 'cli' ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '<h1>Truy cập bị từ chối</h1><p>Bạn cần đăng nhập tài khoản Quản trị viên (Administrator) để thực thi script này.</p>', 403 );
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
}

if ( ! function_exists( 'update_field' ) ) {
	echo "⚠ ACF is not active. Please ensure Advanced Custom Fields Pro is active.\n";
	exit( 1 );
}

// ── 1. Find or create the About page ──
$page_id = 0;
$page    = get_page_by_path( 'gioi-thieu' );

if ( $page ) {
	$page_id = $page->ID;
} else {
	// Fallback to ID 936 or search by template/title
	$p936 = get_post( 936 );
	if ( $p936 && 'page' === $p936->post_type ) {
		$page_id = 936;
	} else {
		$found = get_posts( [
			'post_type'   => 'page',
			'meta_key'    => '_wp_page_template',
			'meta_value'  => 'templates/template-page-about.php',
			'numberposts' => 1,
		] );
		if ( ! empty( $found ) ) {
			$page_id = $found[0]->ID;
		}
	}
}

if ( ! $page_id ) {
	echo "⚠ About page not found. Creating a new page 'Giới Thiệu' (slug: gioi-thieu)...\n";
	$page_id = wp_insert_post( [
		'post_title'   => 'Giới Thiệu',
		'post_name'    => 'gioi-thieu',
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'page_template'=> 'templates/template-page-about.php',
	] );
}

if ( is_wp_error( $page_id ) || ! $page_id ) {
	echo "⚠ Failed to locate or create the About page.\n";
	exit( 1 );
}

// Ensure the page template is set
update_post_meta( $page_id, '_wp_page_template', 'templates/template-page-about.php' );
echo "✓ Target Page ID: {$page_id} (slug: " . get_post_field( 'post_name', $page_id ) . ")\n";

// ── 2. Prepare 11 Flexible Content Sections ──
$about_sections = [
	// 1. HERO SECTION
	[
		'acf_fc_layout' => 'about_hero',
		'disable'       => 0,
		'tag'           => 'Về chúng tôi',
		'title'         => 'Công ty TNHH Xe Điện <span class="text-emerald-400">Bluera Việt Nhật</span>',
		'description'   => 'Kính thưa Quý Khách hàng & Quý Đối tác — Đại Lý Xe Điện Bluera Việt Nhật được thành lập và hình thành trên nhu cầu thực tế về một đơn vị tiên phong trong lĩnh vực phân phối Xe điện của nhiều thương hiệu xe với chất lượng và giá thành tốt nhất và chế độ bảo hành cũng như chăm sóc về sau làm hài lòng mọi Khách hàng tại Việt Nam.',
	],

	// 2. STORY SECTION
	[
		'acf_fc_layout' => 'about_story',
		'disable'       => 0,
		'title'         => 'XE ĐẠP ĐIỆN ĐÃ RA ĐỜI NHƯ THẾ NÀO?',
		'content'       => '<p>Vấn đề ô nhiễm môi trường đã trở thành một vấn đề đáng báo động trên toàn cầu và đặc biệt là ở những quốc gia đang phát triển như Việt Nam, tình trạng ô nghiễm môi trường từ những hoạt động của con người đã trở thành nỗi lo sợ của mỗi người dân Việt.</p><p>Hàng ngày, chúng ta luôn phải đối mặt với những nguy cơ ô nhiễm làm ảnh hưởng nghiệm trọng tới chất lượng sống như ô nhiễm tiếng ồn, ô nhiễm khói bụi… Với mong muốn mang tới một giải pháp giao thông thân thiện với môi trường và an toàn cho người sử dụng, Đại Lý Xe Điện đã đưa ra thị trường nhiều sản phẩm xe điện – loại phương tiện di chuyển an toàn và thông minh, có khả năng cải thiện tình trạng môi trường, đem tới cho con người một bầu không khí trong lành.</p>',
		'badge_number'  => 'Công nghệ tiên tiến',
		'badge_label'   => 'Lắp ráp trực tiếp tại Việt Nam',
	],

	// 3. CEO MESSAGE SECTION
	[
		'acf_fc_layout' => 'about_ceo',
		'disable'       => 0,
		'tag'           => 'VIP TALK',
		'title'         => 'VIP TALK — THÔNG ĐIỆP TỪ BAN GIÁM ĐỐC',
		'subtitle'      => 'Phỏng vấn Ông Vũ Trọng Thanh — CEO của AI EBike / Bluera Việt Nhật',
		'content'       => '<p>“Chúng tôi bắt đầu hành trình từ khát khao mang đến cho người tiêu dùng Việt Nam những dòng xe điện chất lượng nhất, áp dụng công nghệ tiên tiến hàng đầu và lắp ráp trực tiếp tại Việt Nam.”</p><p>DailyXeDien.vn không ngừng nỗ lực phát triển mạng lưới phân phối và nâng cao chất lượng chăm sóc hậu mãi, nhằm đem tới sự an tâm tuyệt đối cho mọi gia đình trên toàn quốc.</p>',
		'ceo_name'      => 'ÔNG VŨ TRỌNG THANH',
		'ceo_title'     => 'TỔNG GIÁM ĐỐC / CEO AI EBIKE & BLUERA VIỆT NHẬT',
		'quote'         => 'Tâm thế phụng sự & Khát vọng xanh hóa giao thông Việt Nam',
	],

	// 4. MISSION SECTION
	[
		'acf_fc_layout' => 'about_mission',
		'disable'       => 0,
		'missions'      => [
			[
				'title' => 'Đổi mới công nghệ & Nhận thức giao thông',
				'desc'  => 'Đại Lý Xe Điện nỗ lực không ngừng nghỉ với mong muốn đem đến cho người tiêu dùng nhiều sản phẩm xe điện mang công nghệ tiên tiến. Đó chính là khát khao đem đến cho Việt Nam một môi trường sống tốt hơn, hạn chế khói bụi, tiếng ồn và quan trọng hơn cả, thay đổi nhận thức của người dân Việt trong việc lựa chọn phương tiện di chuyển hằng ngày.',
				'icon'  => '<svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
			],
			[
				'title' => 'Đóng góp cho xã hội & Môi trường',
				'desc'  => 'Đại Lý Xe Điện mong muốn những sản phẩm xe điện mà chúng tôi cung cấp sẽ có thể đóng góp cho xã hội, giúp cho Việt Nam trở thành một trong những quốc gia tích cực nhất trong việc bảo vệ môi trường, và từ đó có thể giúp người dân Việt Nam có một cuộc sống tốt đẹp hơn.',
				'icon'  => '<svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>',
			],
			[
				'title' => 'Gieo mầm xanh trên mọi cung đường',
				'desc'  => 'Chúng tôi nuôi hy vọng và mong chờ sản phẩm của mình sẽ trải một màu xanh đẹp đẽ trên mọi cung đường của đất nước, cũng như có thể gieo trồng được mầm xanh vào suy nghĩ, tư tưởng của tất cả người dân Việt.',
				'icon'  => '<svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v8"/><path d="m4.93 10.93 4.24 4.24"/><path d="M2 18h20"/></svg>',
			],
		],
	],

	// 5. VALUES SECTION
	[
		'acf_fc_layout' => 'about_values',
		'disable'       => 0,
		'badge'         => 'Giá Trị Cốt Lõi',
		'title'         => 'Giá trị cốt lõi',
		'values'        => [
			[
				'title' => 'Minh bạch',
				'desc'  => 'Giá niêm yết rõ ràng, không phát sinh chi phí ẩn. Khách hàng luôn biết trước tổng chi phí trước khi quyết định.',
				'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>',
			],
			[
				'title' => 'Tận tâm',
				'desc'  => 'Lắng nghe nhu cầu thực tế để tư vấn đúng xe, đúng mục đích sử dụng. Không ép bán, không phóng đại tính năng.',
				'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/><path d="M12 5 9.04 7.96a2.17 2.17 0 0 0 0 3.08c.82.82 2.13.85 3 .07l2.07-1.9a2.82 2.82 0 0 1 3.79 0l2.96 2.66"/><path d="m18 15-2-2"/><path d="m15 18-2-2"/></svg>',
			],
			[
				'title' => 'Trách nhiệm',
				'desc'  => 'Bảo hành đúng cam kết, xử lý khiếu nại nhanh gọn. Mỗi sản phẩm bán ra đều có lịch sử theo dõi hậu mãi rõ ràng.',
				'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
			],
			[
				'title' => 'Đổi mới',
				'desc'  => 'Liên tục cập nhật dòng xe mới, công nghệ mới. Ứng dụng nền tảng số để khách hàng dễ dàng tra cứu và theo dõi đơn hàng.',
				'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>',
			],
		],
	],

	// 6. WHY CHOOSE US SECTION
	[
		'acf_fc_layout' => 'about_why_choose_us',
		'disable'       => 0,
		'title'         => 'VÌ SAO ĐẠI LÝ XE ĐIỆN LÀ LỰA CHỌN TỐT DÀNH CHO BẠN?',
		'description'   => 'Phương tiện di chuyển thông minh, hoạt động bằng nguồn năng lượng sạch và đáp ứng tiêu chuẩn khắt khe nhất',
		'items'         => [
			[
				'title' => 'Năng lượng sạch & Tiết kiệm',
				'desc'  => 'Là một giải phương tiện thông minh của thời đại mới, xe đạp điện, xe máy điện và xe điện 3 bánh do Đại Lý Xe Điện cung cấp hoạt động bằng nguồn năng lượng sạch, không những có thể giúp bảo vệ môi trường, sức khỏe của con người mà còn có khả năng tiết kiệm chi phí tối đa. Sản phẩm hội tụ đầy đủ các yếu tố tốt nhất xứng đáng để các bạn lựa chọn như thiết kế sành điệu nhỏ gọn, khả năng di chuyển trên quãng đường xa, giá thành hợp lý… và rất nhiều lý do khác.',
				'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v8"/><path d="m4.93 10.93 4.24 4.24"/><path d="M2 18h20"/><path d="M20 10c0 5.523-4.477 10-10 10S0 15.523 0 10"/></svg>',
				'class' => 'bg-blue-50 text-blue-500',
			],
			[
				'title' => 'Đa dạng chủng loại & Chuẩn mực',
				'desc'  => 'Để có được một sản phẩm đạt những tiêu chí hoàn hảo như vậy, Đại Lý Xe Điện đã không ngừng cố gắng tìm kiếm các chủng loại có chất lượng cao, sản phẩm đẹp, đa dạng, kèm theo dịch vụ và giá thành phù hợp với người tiêu dùng Việt.',
				'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
				'class' => 'bg-emerald-50 text-emerald-500',
			],
			[
				'title' => 'Công nghệ hiện đại & Lắp ráp Việt Nam',
				'desc'  => 'Trong nỗ lực hoàn thiện sản phẩm của mình, Đại Lý Xe Điện cũng mạnh dạn đầu tư các loại trang thiết bị máy móc hiện đại để kiểm tra và bảo hành tận nơi cho khách hàng Việt Nam, ứng dụng công nghệ tiên tiến hàng đầu, trực tiếp lắp ráp các dòng xe đạp điện Bluera ngay tại Việt Nam.',
				'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
				'class' => 'bg-amber-50 text-amber-500',
			],
			[
				'title' => 'Kiểm định khắt khe & Đầy đủ hóa đơn',
				'desc'  => 'Mỗi sản phẩm của Đại Lý Xe Điện cung cấp đến tay người tiêu dùng đều được trải qua quy trình khắt khe nhất về chất lượng. Đại Lý Xe Điện tự hào là một trong những nhà cung cấp bán buôn và bán lẻ của các thương hiệu hàng đầu tại Việt Nam, đồng thời thực hiện nghiêm chỉnh quy định của Nhà nước về hàng hóa xuất bán có đầy đủ hóa đơn, đăng kiểm và đạt các tiêu chuẩn cho phép hoạt động của phương tiện xe điện hiện nay.',
				'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>',
				'class' => 'bg-rose-50 text-rose-500',
			],
		],
	],

	// 7. TIMELINE SECTION
	[
		'acf_fc_layout' => 'about_timeline',
		'disable'       => 0,
		'title'         => 'TỪNG MỐC DẤU ẤN',
		'description'   => 'Từng bước xây dựng hệ thống phân phối xe điện uy tín hàng đầu',
		'items'         => [
			[
				'year'  => '2013',
				'title' => 'Khởi đầu',
				'desc'  => 'Thành lập Công ty TNHH Xe Điện Bluera Việt Nhật & khai trương showroom DailyXeDien đầu tiên (23/09/2013).',
			],
			[
				'year'  => '2015',
				'title' => 'Nhà máy Bluera',
				'desc'  => 'Khai trương nhà máy sản xuất & lắp ráp xe điện công nghệ hiện đại đầu tiên đạt tiêu chuẩn TCVN, ISO.',
			],
			[
				'year'  => '2018',
				'title' => 'Mở rộng quy mô',
				'desc'  => 'Nâng cấp dây chuyền công nghệ hiện đại, liên kết linh kiện cao cấp và nhân rộng hệ thống đại lý.',
			],
			[
				'year'  => '2021',
				'title' => 'Chuyển đổi số',
				'desc'  => 'Triển khai hệ thống bảo hành điện tử 24/7 và nâng cấp trải nghiệm mua sắm số trên DailyXeDien.vn.',
			],
			[
				'year'  => '2023',
				'title' => 'Cột mốc 10 năm',
				'desc'  => 'Thành lập dự án AI Ebike (AIE) nghiên cứu và phát triển dòng sản phẩm xe điện thông minh thế hệ mới.',
			],
			[
				'year'  => '2024',
				'title' => 'Mạng lưới toàn quốc',
				'desc'  => 'Phát triển mạng lưới phân phối đạt 500+ đại lý ủy quyền và hợp tác đối tác chiến lược.',
			],
			[
				'year'  => '2026',
				'title' => 'Kỷ nguyên mới',
				'desc'  => 'Chuẩn hóa hệ thống showroom 3S & trung tâm kỹ thuật bảo hành ủy quyền chính hãng trên toàn quốc.',
			],
		],
	],

	// 8. TEAM SECTION
	[
		'acf_fc_layout' => 'about_team',
		'disable'       => 0,
		'title'         => 'Đội ngũ của chúng tôi',
		'description'   => 'Những con người tận tâm đứng sau mỗi chiếc xe giao đến tay bạn',
		'items'         => [
			[
				'name' => 'Nguyễn Văn A',
				'role' => 'Giám đốc điều hành',
				'desc' => 'Hơn 10 năm kinh nghiệm trong lĩnh vực phân phối xe điện.',
			],
			[
				'name' => 'Trần Thị B',
				'role' => 'Trưởng phòng kinh doanh',
				'desc' => 'Chuyên gia tư vấn xe điện cho gia đình và cá nhân.',
			],
			[
				'name' => 'Lê Văn C',
				'role' => 'Trưởng phòng kỹ thuật',
				'desc' => 'Phụ trách bảo hành, sửa chữa và kiểm tra chất lượng xe.',
			],
			[
				'name' => 'Phạm Thị D',
				'role' => 'Trưởng phòng CSKH',
				'desc' => 'Luôn lắng nghe và giải quyết mọi vấn đề sau mua hàng.',
			],
		],
	],

	// 9. PARTNERS SECTION
	[
		'acf_fc_layout' => 'about_partners',
		'disable'       => 0,
		'title'         => 'Đối tác & Thương hiệu',
		'description'   => 'Đại lý ủy quyền chính thức của các hãng xe điện hàng đầu',
		'items'         => [
			[ 'name' => 'BLUERA' ],
			[ 'name' => 'YADEA' ],
			[ 'name' => 'VINFAST' ],
			[ 'name' => 'XMEN' ],
			[ 'name' => 'BLUESUDA' ],
			[ 'name' => 'VESPA' ],
		],
	],

	// 10. STATS SECTION (Pre-populated, set disable => 1 by default so it matches frontend 1:1)
	[
		'acf_fc_layout' => 'about_stats',
		'disable'       => 1,
		'title'         => 'Những con số biết nói',
		'subtitle'      => 'LỜI HỨA CỦA DAILYXEDIEN.VN',
		'stats'         => [
			[ 'number' => '0%', 'label' => 'Tỉ lệ hàng giả, hàng nhái & linh kiện không rõ nguồn gốc' ],
			[ 'number' => '100%', 'label' => 'Xe điện chính hãng, đầy đủ kiểm định CO/CQ' ],
			[ 'number' => '20+', 'label' => 'Showroom & trung tâm bảo hành ủy quyền toàn quốc' ],
			[ 'number' => '10,000+', 'label' => 'Khách hàng tin dùng và đánh giá hài lòng 5 sao' ],
		],
	],

	// 11. CTA SECTION
	[
		'acf_fc_layout' => 'about_cta',
		'disable'       => 0,
		'title'         => 'Sẵn sàng đồng hành cùng bạn',
		'description'   => 'Bạn đang tìm xe điện cho bản thân hoặc gia đình? Hãy liên hệ ngay để được tư vấn miễn phí, chọn đúng xe phù hợp với ngân sách và nhu cầu sử dụng.',
		'btn_primary'   => [
			'title'  => 'Đăng ký tư vấn miễn phí',
			'url'    => home_url( '/lien-he/' ),
			'target' => '',
		],
		'btn_outline'   => [
			'title'  => 'Gọi 0933 505 222',
			'url'    => 'tel:0933505222',
			'target' => '',
		],
	],
];

// ── 3. Update ACF field ──
echo "Populating " . count( $about_sections ) . " sections into ACF field 'about_sections'...\n";
$res = update_field( 'about_sections', $about_sections, $page_id );

if ( $res ) {
	echo "✓ Successfully populated all " . count( $about_sections ) . " sections for Page ID: {$page_id}!\n";
} else {
	// Fallback to update_post_meta if update_field returned false (e.g. data identical)
	update_post_meta( $page_id, 'about_sections', $about_sections );
	update_post_meta( $page_id, '_about_sections', 'field_daily_about_fc' );
	echo "✓ Updated postmeta directly for Page ID: {$page_id}.\n";
}

if ( function_exists( 'clean_post_cache' ) ) {
	clean_post_cache( $page_id );
}

echo "=== FINISHED POPULATING ABOUT PAGE ===\n";
