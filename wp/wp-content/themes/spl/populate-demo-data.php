<?php
/**
 * Populate ACF data for Lạc Huy demo.
 *
 * Run once via WP-CLI:
 * php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-demo-data.php
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

// ── 1. Update Site Title & Tagline ──────────────────

update_option( 'blogname', 'Trà & Táo Đỏ Lạc Huy' );
update_option( 'blogdescription', 'Nơi cung cấp thực phẩm tự nhiên chất lượng và uy tín' );
update_option( 'timezone_string', 'Asia/Ho_Chi_Minh' );
update_option( 'date_format', 'd/m/Y' );
update_option( 'time_format', 'H:i' );
echo "✓ Site title & tagline updated\n";

// ── 2. Create Pages ─────────────────────────────────

$pages = [
	'trang-chu'  => [ 'title' => 'Trang Chủ', 'template' => 'templates/template-page-home.php' ],
	'gioi-thieu' => [ 'title' => 'Giới Thiệu', 'template' => '' ],
	'lien-he'    => [ 'title' => 'Liên Hệ', 'template' => '' ],
	'tin-tuc'    => [ 'title' => 'Tin Tức', 'template' => '' ],
];

$page_ids = [];
foreach ( $pages as $slug => $data ) {
	$existing = get_page_by_path( $slug );
	if ( $existing ) {
		$page_ids[ $slug ] = $existing->ID;
		echo "  Page '{$data['title']}' already exists (ID: {$existing->ID})\n";
	} else {
		$id = wp_insert_post( [
			'post_title'   => $data['title'],
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		] );
		if ( ! is_wp_error( $id ) ) {
			$page_ids[ $slug ] = $id;
			echo "  ✓ Created page '{$data['title']}' (ID: {$id})\n";
		}
	}

	// Set page template if specified.
	if ( ! empty( $data['template'] ) && ! empty( $page_ids[ $slug ] ) ) {
		update_post_meta( $page_ids[ $slug ], '_wp_page_template', $data['template'] );
	}
}

// Set homepage & posts page.
if ( ! empty( $page_ids['trang-chu'] ) ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $page_ids['trang-chu'] );
	echo "✓ Static front page set\n";
}
if ( ! empty( $page_ids['tin-tuc'] ) ) {
	update_option( 'page_for_posts', $page_ids['tin-tuc'] );
	echo "✓ Posts page set\n";
}

// ── 3. ACF Options Data ─────────────────────────────

if ( function_exists( 'update_field' ) ) {
	update_field( 'hotline', '098 750 33 60', 'option' );
	update_field( 'email', 'Lachuyhddt@gmail.com', 'option' );
	update_field( 'address', 'TP. Hồ Chí Minh, Việt Nam', 'option' );
	update_field( 'working_hours', 'Thứ 2 – Thứ 7: 08:00 – 17:30', 'option' );
	update_field( 'footer_desc', 'Chuyên cung cấp trà, táo đỏ và thực phẩm tự nhiên chất lượng. Nơi cung cấp thực phẩm tự nhiên chất lượng và uy tín.', 'option' );
	update_field( 'zalo_url', 'https://zalo.me/0987503360', 'option' );
	echo "✓ ACF options populated\n";
} else {
	// Fallback: write directly to options table.
	update_option( 'options_hotline', '098 750 33 60' );
	update_option( 'options_email', 'Lachuyhddt@gmail.com' );
	update_option( 'options_address', 'TP. Hồ Chí Minh, Việt Nam' );
	update_option( 'options_working_hours', 'Thứ 2 – Thứ 7: 08:00 – 17:30' );
	update_option( 'options_footer_desc', 'Chuyên cung cấp trà, táo đỏ và thực phẩm tự nhiên chất lượng. Nơi cung cấp thực phẩm tự nhiên chất lượng và uy tín.' );
	update_option( 'options_zalo_url', 'https://zalo.me/0987503360' );
	echo "✓ Options populated (ACF not active, used wp_options fallback)\n";
}

// ── 4. ACF Home Page Flexible Content ───────────────

$home_id = $page_ids['trang-chu'] ?? 0;
if ( $home_id && function_exists( 'update_field' ) ) {
	$home_sections = [
		// Hero
		[
			'acf_fc_layout' => 'hero',
			'badge_text'    => 'Thực Phẩm Tự Nhiên 100%',
			'title'         => 'Chăm Sóc Sức Khỏe Bằng <span>Thực Phẩm</span> Tự Nhiên',
			'description'   => 'Chuyên cung cấp trà thảo mộc, táo đỏ, bột nguyên chất, tinh dầu thiên nhiên với chất lượng cao nhất.',
			'btn_primary'   => [ 'title' => 'Mua Ngay', 'url' => '#products', 'target' => '' ],
			'btn_outline'   => [ 'title' => 'Tìm Hiểu Thêm', 'url' => '#about', 'target' => '' ],
		],
		// Features
		[
			'acf_fc_layout' => 'features',
			'features'      => [
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
					'title' => 'Giao Hàng Toàn Quốc',
					'desc'  => 'Miễn phí ship đơn từ 500K',
				],
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>',
					'title' => '100% Tự Nhiên',
					'desc'  => 'Nguồn gốc rõ ràng',
				],
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
					'title' => 'An Toàn & Chất Lượng',
					'desc'  => 'Kiểm định nghiêm ngặt',
				],
				[
					'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
					'title' => 'Tư Vấn Miễn Phí',
					'desc'  => 'Hotline: 098 750 33 60',
				],
			],
		],
		// Flash Sale
		[
			'acf_fc_layout' => 'flash_sale',
			'end_time'      => '',
		],
		// Categories
		[
			'acf_fc_layout' => 'categories',
			'title'         => 'Danh Mục Sản Phẩm',
			'subtitle'      => 'Khám phá thế giới thực phẩm tự nhiên',
		],
		// Products
		[
			'acf_fc_layout' => 'products',
			'title'         => 'Sản Phẩm Nổi Bật',
			'subtitle'      => 'Được khách hàng tin tưởng lựa chọn',
		],
		// About
		[
			'acf_fc_layout' => 'about',
			'title'         => 'Về Trà & Táo Đỏ Lạc Huy',
			'description'   => 'Với nhiều năm kinh nghiệm trong lĩnh vực thực phẩm tự nhiên, chúng tôi tự hào mang đến những sản phẩm chất lượng cao nhất. Trà & Táo Đỏ Lạc Huy — nơi cung cấp thực phẩm tự nhiên chất lượng và uy tín.',
			'stats'         => [
				[ 'number' => '10+', 'label' => 'Năm kinh nghiệm' ],
				[ 'number' => '500+', 'label' => 'Sản phẩm' ],
				[ 'number' => '10K+', 'label' => 'Khách hàng' ],
				[ 'number' => '50+', 'label' => 'Đối tác' ],
			],
		],
		// Blog
		[
			'acf_fc_layout' => 'blog',
			'title'         => 'Tin Tức & Kiến Thức',
			'subtitle'      => 'Cập nhật thông tin sức khỏe & thực phẩm tự nhiên',
		],
	];

	update_field( 'home_sections', $home_sections, $home_id );
	echo "✓ Home page ACF flexible content populated (7 sections)\n";

} elseif ( $home_id ) {
	echo "⚠ ACF not active — home page data not populated. Install & activate ACF Pro first.\n";
}

// ── 5. Create Sample Blog Posts ─────────────────────

$sample_posts = [
	[
		'title'   => 'Tác dụng của trà thảo mộc đối với sức khỏe',
		'content' => 'Trà thảo mộc từ lâu đã được biết đến với những lợi ích tuyệt vời cho sức khỏe. Từ việc hỗ trợ tiêu hóa, giảm stress đến tăng cường hệ miễn dịch, mỗi loại trà mang lại những công dụng riêng biệt.',
		'excerpt' => 'Khám phá những lợi ích sức khỏe tuyệt vời từ trà thảo mộc thiên nhiên.',
	],
	[
		'title'   => 'Táo đỏ - Siêu thực phẩm bổ dưỡng',
		'content' => 'Táo đỏ (Hồng táo) là một trong những loại thực phẩm giàu dinh dưỡng nhất. Giàu vitamin C, chất chống oxy hóa và các khoáng chất thiết yếu, táo đỏ giúp bồi bổ sức khỏe toàn diện.',
		'excerpt' => 'Tìm hiểu về giá trị dinh dưỡng và công dụng của táo đỏ đối với sức khỏe.',
	],
	[
		'title'   => 'Hướng dẫn chọn mua bột nguyên chất',
		'content' => 'Bột nguyên chất ngày càng phổ biến trong đời sống hàng ngày. Từ bột nghệ, bột đậu đỏ đến bột trà xanh, mỗi loại đều có công dụng và cách sử dụng riêng. Bài viết này sẽ hướng dẫn bạn cách phân biệt bột thật và bột giả.',
		'excerpt' => 'Mẹo phân biệt bột nguyên chất thật và cách chọn mua sản phẩm chất lượng.',
	],
];

foreach ( $sample_posts as $post_data ) {
	$existing = get_page_by_title( $post_data['title'], OBJECT, 'post' );
	if ( $existing ) {
		echo "  Post '{$post_data['title']}' already exists\n";
		continue;
	}

	$id = wp_insert_post( [
		'post_title'   => $post_data['title'],
		'post_content' => $post_data['content'],
		'post_excerpt' => $post_data['excerpt'],
		'post_status'  => 'publish',
		'post_type'    => 'post',
	] );

	if ( ! is_wp_error( $id ) ) {
		echo "  ✓ Created post '{$post_data['title']}' (ID: {$id})\n";
	}
}

// ── 6. Permalink Structure ──────────────────────────

update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();
echo "✓ Permalink set to /%postname%/\n";

echo "\n=== Demo data populated successfully! ===\n";
echo "Frontend: http://thaphaco.test\n";
echo "Admin: http://thaphaco.test/wp/wp-admin/\n";
echo "Login: admin / admin\n";
