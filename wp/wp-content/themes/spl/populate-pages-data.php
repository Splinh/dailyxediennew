<?php
/**
 * Populate ACF data for About & Contact pages (Lạc Huy).
 *
 * Run via WP-CLI:
 * php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-pages-data.php
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

// ── 1. Ensure pages exist & set templates ────────────

$pages = [
	'gioi-thieu' => [ 'title' => 'Giới Thiệu', 'template' => 'templates/template-page-about.php' ],
	'lien-he'    => [ 'title' => 'Liên Hệ', 'template' => 'templates/template-page-contact.php' ],
];

$page_ids = [];
foreach ( $pages as $slug => $data ) {
	$existing = get_posts( [
		'name'        => $slug,
		'post_type'   => 'page',
		'post_status' => 'publish',
		'numberposts' => 1,
	] );

	if ( ! empty( $existing ) ) {
		$page_ids[ $slug ] = $existing[0]->ID;
		echo "  Page '{$data['title']}' exists (ID: {$existing[0]->ID})\n";
	} else {
		$id = wp_insert_post( [
			'post_title'  => $data['title'],
			'post_name'   => $slug,
			'post_status' => 'publish',
			'post_type'   => 'page',
		] );
		if ( ! is_wp_error( $id ) ) {
			$page_ids[ $slug ] = $id;
			echo "  ✓ Created page '{$data['title']}' (ID: {$id})\n";
		}
	}

	if ( ! empty( $page_ids[ $slug ] ) ) {
		update_post_meta( $page_ids[ $slug ], '_wp_page_template', $data['template'] );
		echo "  ✓ Template set: {$data['template']}\n";
	}
}

if ( ! function_exists( 'update_field' ) ) {
	echo "⚠ ACF not active — flexible content not populated.\n";
	exit;
}

// ── 2. About Page ───────────────────────────────────

$about_id = $page_ids['gioi-thieu'] ?? 0;
if ( $about_id ) {
	$about_sections = [
		// Hero
		[
			'acf_fc_layout' => 'about_hero',
			'tag'           => 'Về chúng tôi',
			'title'         => 'Trà & Táo Đỏ Lạc Huy <br/><span>Gắn Kết Thiên Nhiên — Bảo Vệ Sức Khỏe</span>',
			'description'   => 'Với nhiều năm đồng hành cùng sức khỏe người Việt, Lạc Huy tự hào là đơn vị cung cấp thực phẩm tự nhiên chất lượng với cam kết 100% nguồn gốc tự nhiên.',
		],
		// Story
		[
			'acf_fc_layout' => 'about_story',
			'title'         => 'Câu Chuyện Của Chúng Tôi',
			'content'       => '<p>Trà & Táo Đỏ Lạc Huy được thành lập tại TP. Hồ Chí Minh với sứ mệnh mang đến những sản phẩm thực phẩm tự nhiên tốt nhất cho người tiêu dùng Việt Nam.</p><p>Xuất phát từ tình yêu với y học cổ truyền và những bài thuốc quý từ đời ông cha, chúng tôi đã xây dựng mạng lưới thu mua nguyên liệu trực tiếp từ các vùng trồng trọng điểm trên khắp Việt Nam.</p><p>Mỗi sản phẩm của Lạc Huy đều trải qua quy trình kiểm soát chất lượng nghiêm ngặt, từ khâu thu hái, chế biến đến đóng gói, đảm bảo giữ nguyên tinh chất và dưỡng chất tự nhiên.</p>',
			'badge_number'  => '10+',
			'badge_label'   => 'Năm kinh nghiệm',
		],
		// Values
		[
			'acf_fc_layout' => 'about_values',
			'badge'         => 'Giá Trị Cốt Lõi',
			'title'         => 'Tại Sao Chọn Lạc Huy?',
			'values'        => [
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>',
					'title' => '100% Tự Nhiên',
					'desc'  => 'Cam kết sản phẩm hoàn toàn từ thiên nhiên, không chất bảo quản, không phụ gia hóa học. Nguyên liệu được thu hái từ các vùng dược liệu sạch.',
				],
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
					'title' => 'Kiểm Soát Chất Lượng',
					'desc'  => 'Quy trình kiểm định nghiêm ngặt từ khâu thu mua đến thành phẩm. Mỗi lô hàng đều được kiểm tra vi sinh và hàm lượng hoạt chất.',
				],
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
					'title' => 'Đội Ngũ Chuyên Gia',
					'desc'  => 'Đội ngũ chuyên gia thực phẩm giàu kinh nghiệm, luôn sẵn sàng tư vấn và hỗ trợ khách hàng lựa chọn sản phẩm phù hợp.',
				],
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
					'title' => 'Giao Hàng Toàn Quốc',
					'desc'  => 'Hệ thống logistics chuyên nghiệp, giao hàng nhanh chóng đến mọi tỉnh thành. Đóng gói cẩn thận, bảo quản đúng tiêu chuẩn.',
				],
			],
		],
		// Stats
		[
			'acf_fc_layout' => 'about_stats',
			'stats'         => [
				[ 'number' => '120', 'label' => 'Sản phẩm tự nhiên' ],
				[ 'number' => '5000', 'label' => 'Khách hàng tin dùng' ],
				[ 'number' => '30', 'label' => 'Vùng nguyên liệu' ],
				[ 'number' => '10', 'label' => 'Năm hoạt động' ],
			],
		],
		// Mission
		[
			'acf_fc_layout' => 'about_mission',
			'missions'      => [
				[
					'icon'  => '<svg class="icon icon-xl" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
					'title' => 'Sứ Mệnh',
					'desc'  => 'Mang đến những sản phẩm thực phẩm tự nhiên tốt nhất, góp phần bảo vệ và nâng cao sức khỏe cộng đồng.',
				],
				[
					'icon'  => '<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
					'title' => 'Tầm Nhìn',
					'desc'  => 'Trở thành thương hiệu thực phẩm tự nhiên hàng đầu Việt Nam, đưa sản phẩm Việt vươn tầm quốc tế.',
				],
				[
					'icon'  => '<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
					'title' => 'Cam Kết',
					'desc'  => 'Chất lượng là hàng đầu, sức khỏe khách hàng là trên hết. Mỗi sản phẩm đều đạt tiêu chuẩn an toàn cao nhất.',
				],
			],
		],
		// CTA
		[
			'acf_fc_layout' => 'about_cta',
			'title'         => 'Hãy Để Chúng Tôi Đồng Hành Cùng Sức Khỏe Của Bạn',
			'description'   => 'Liên hệ ngay để được tư vấn miễn phí về các sản phẩm phù hợp với nhu cầu của bạn.',
			'btn_primary'   => [ 'title' => 'Xem Sản Phẩm', 'url' => '/san-pham/', 'target' => '' ],
			'btn_outline'   => [ 'title' => 'Liên Hệ Ngay', 'url' => '/lien-he/', 'target' => '' ],
		],
	];

	update_field( 'about_sections', $about_sections, $about_id );
	echo "✓ About page: 6 sections populated\n";
}

// ── 3. Contact Page ─────────────────────────────────

$contact_id = $page_ids['lien-he'] ?? 0;
if ( $contact_id ) {
	$contact_sections = [
		// Info cards
		[
			'acf_fc_layout' => 'contact_info',
			'cards'         => [
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
					'title' => 'Hotline',
					'value' => '<a href="tel:0987503360">098 750 33 60</a>',
					'note'  => 'Thứ 2 – Thứ 7: 08:00 – 17:30',
				],
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
					'title' => 'Email',
					'value' => '<a href="mailto:Lachuyhddt@gmail.com">Lachuyhddt@gmail.com</a>',
					'note'  => 'Phản hồi trong 24 giờ',
				],
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
					'title' => 'Địa Chỉ',
					'value' => 'TP. Hồ Chí Minh, Việt Nam',
					'note'  => 'Nhận hàng trực tiếp tại kho',
				],
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
					'title' => 'Giờ Làm Việc',
					'value' => 'Thứ 2 – Thứ 7: 08:00 – 17:30',
					'note'  => 'Nghỉ Chủ nhật & ngày lễ',
				],
			],
		],
		// Form + Map
		[
			'acf_fc_layout'  => 'contact_form',
			'form_title'     => 'Gửi Tin Nhắn Cho Chúng Tôi',
			'form_desc'      => 'Để lại thông tin, chúng tôi sẽ liên hệ tư vấn miễn phí trong thời gian sớm nhất.',
			'cf7_shortcode'  => '',
			'map_title'      => 'Vị Trí Của Chúng Tôi',
			'map_embed_url'  => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.394!2d106.6602!3d10.7769!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTDCsDQ2JzM2LjgiTiAxMDbCsDM5JzM2LjciRQ!5e0!3m2!1svi!2svn!4v1',
			'social_title'   => 'Kết Nối Với Chúng Tôi',
			'social_desc'    => 'Theo dõi Lạc Huy trên mạng xã hội để cập nhật sản phẩm mới và khuyến mãi.',
			'hotline_title'  => 'Gọi Ngay Hotline',
			'hotline_desc'   => 'Tư vấn miễn phí, hỗ trợ 6 ngày/tuần',
		],
		// FAQ
		[
			'acf_fc_layout' => 'contact_faq',
			'badge'         => 'Câu Hỏi Thường Gặp',
			'title'         => 'Bạn Cần Hỗ Trợ Gì?',
			'faqs'          => [
				[
					'question' => 'Lạc Huy có giao hàng toàn quốc không?',
					'answer'   => 'Có, chúng tôi giao hàng trên toàn quốc qua các đối tác vận chuyển uy tín. Đơn hàng tại TP.HCM sẽ được giao trong 1-2 ngày, tỉnh thành khác từ 2-5 ngày làm việc. Miễn phí ship đơn từ 500K.',
				],
				[
					'question' => 'Có chính sách đổi trả không?',
					'answer'   => 'Chúng tôi hỗ trợ đổi trả trong vòng 7 ngày kể từ khi nhận hàng nếu sản phẩm bị lỗi do nhà sản xuất hoặc không đúng với mô tả. Vui lòng liên hệ hotline 098 750 33 60 để được hỗ trợ.',
				],
				[
					'question' => 'Tôi muốn mua sỉ/đại lý thì liên hệ ai?',
					'answer'   => 'Vui lòng gọi trực tiếp hotline 098 750 33 60 hoặc gửi email đến Lachuyhddt@gmail.com với tiêu đề "Hợp tác kinh doanh". Đội ngũ kinh doanh sẽ liên hệ bạn trong 24 giờ.',
				],
				[
					'question' => 'Sản phẩm có giấy chứng nhận ATTP không?',
					'answer'   => 'Tất cả sản phẩm của Lạc Huy đều có đầy đủ giấy chứng nhận An Toàn Thực Phẩm (ATTP) theo quy định của Bộ Y tế. Khách hàng có thể yêu cầu xem giấy chứng nhận khi mua hàng.',
				],
				[
					'question' => 'Phương thức thanh toán nào được chấp nhận?',
					'answer'   => 'Chúng tôi chấp nhận thanh toán COD (nhận hàng trả tiền), chuyển khoản ngân hàng, và ví điện tử (Momo, ZaloPay). Đối với đơn hàng sỉ, vui lòng thanh toán trước 50%.',
				],
			],
		],
	];

	update_field( 'contact_sections', $contact_sections, $contact_id );
	echo "✓ Contact page: 3 sections populated\n";
}

echo "\n=== Sub-pages data populated! ===\n";
echo "About: http://thaphaco.test/gioi-thieu/\n";
echo "Contact: http://thaphaco.test/lien-he/\n";
