<?php
/**
 * Re-populate Home page flexible content with updated Lạc Huy data.
 *
 * Run via WP-CLI:
 * php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-home-updated.php
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
	echo "⚠ No front page set" . PHP_EOL;
	exit;
}
echo "Home page ID: {$home_id}" . PHP_EOL;

// ── Get product IDs ─────────────────────────────────

$flash_sale_slugs = [
	'tra-hoa-cuc-mat-ong',
	'bot-nghe-nguyen-chat',
	'gung-kho-thai-lat',
	'tra-atiso-da-lat',
];

$featured_slugs = [
	'tao-do-tan-cuong-loai-1',
	'bot-tra-xanh-matcha',
	'tinh-dau-tram-nguyen-chat',
	'nguyen-lieu-nuoc-sam-goi',
	'tra-hoa-cuc-mat-ong',
	'bot-nghe-nguyen-chat',
	'hoa-nhai-say-kho',
	'tra-gao-lut-rang',
];

function _get_product_ids_by_slug( array $slugs ): array {
	$ids = [];
	foreach ( $slugs as $slug ) {
		$posts = get_posts( [
			'name'        => $slug,
			'post_type'   => 'product',
			'post_status' => 'publish',
			'numberposts' => 1,
			'fields'      => 'ids',
		] );
		if ( ! empty( $posts ) ) {
			$ids[] = $posts[0];
		}
	}
	return $ids;
}

$flash_ids    = _get_product_ids_by_slug( $flash_sale_slugs );
$featured_ids = _get_product_ids_by_slug( $featured_slugs );

echo "Flash sale products: " . count( $flash_ids ) . PHP_EOL;
echo "Featured products: " . count( $featured_ids ) . PHP_EOL;

// ── Get category IDs ────────────────────────────────

$cat_slugs = [
	'thao-duoc-kho',
	'tra-tui-loc',
	'bot-nguyen-chat',
	'tao-do',
	'nguyen-lieu-nuoc-sam',
	'tinh-dau-thien-nhien',
	'hoa-thao-duoc',
	'gia-vi',
];

$cat_items = [];
foreach ( $cat_slugs as $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term ) {
		$cat_items[] = [
			'category'    => $term->term_id,
			'icon'        => '',
			'description' => '',
		];
	}
}

// ── Get blog post IDs ───────────────────────────────

$blog_posts = get_posts( [
	'post_type'   => 'post',
	'post_status' => 'publish',
	'numberposts' => 3,
	'orderby'     => 'date',
	'order'       => 'DESC',
	'fields'      => 'ids',
] );

echo "Blog posts: " . count( $blog_posts ) . PHP_EOL;

// ── Home sections ───────────────────────────────────

$home_sections = [
	// 1. HERO
	[
		'acf_fc_layout'  => 'hero',
		'badge'          => 'Thực Phẩm Tự Nhiên 100%',
		'title'          => 'Chăm Sóc Sức Khỏe Bằng <span>Thực Phẩm</span> Thiên Nhiên',
		'description'    => 'Chuyên cung cấp trà, táo đỏ, bột nguyên chất, tinh dầu thiên nhiên với chất lượng cao nhất. Nơi cung cấp thực phẩm tự nhiên chất lượng và uy tín.',
		'btn_primary'    => [ 'title' => 'Mua Ngay', 'url' => '/san-pham/', 'target' => '' ],
		'btn_secondary'  => [ 'title' => 'Tìm Hiểu Thêm', 'url' => '/gioi-thieu/', 'target' => '' ],
	],
	// 2. FEATURES
	[
		'acf_fc_layout' => 'features',
		'items'         => [
			[
				'icon'  => '<svg class="icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
				'title' => 'Giao Hàng Toàn Quốc',
				'desc'  => 'Miễn phí ship đơn từ 500K',
			],
			[
				'icon'  => '<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>',
				'title' => '100% Thiên Nhiên',
				'desc'  => 'Nguồn gốc rõ ràng, uy tín',
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
	// 3. FLASH SALE
	[
		'acf_fc_layout' => 'flash_sale',
		'title'         => 'FLASH SALE',
		'product_ids'   => $flash_ids,
	],
	// 4. CATEGORIES
	[
		'acf_fc_layout' => 'categories',
		'badge'         => 'Danh Mục',
		'title'         => 'Danh Mục Sản Phẩm',
		'categories'    => $cat_items,
	],
	// 5. PRODUCTS
	[
		'acf_fc_layout' => 'products',
		'badge'         => 'Nổi Bật',
		'title'         => 'Sản Phẩm Bán Chạy',
		'product_ids'   => $featured_ids,
	],
	// 6. ABOUT
	[
		'acf_fc_layout' => 'about',
		'badge'         => 'Về Chúng Tôi',
		'title'         => 'Trà & Táo Đỏ Lạc Huy',
		'description'   => 'Nơi cung cấp thực phẩm tự nhiên chất lượng và uy tín. Với nhiều năm kinh nghiệm, Lạc Huy cam kết mang đến những sản phẩm tốt nhất từ thiên nhiên.',
		'stats'         => [
			[ 'number' => '120+', 'label' => 'Sản phẩm' ],
			[ 'number' => '5000+', 'label' => 'Khách hàng' ],
			[ 'number' => '30+', 'label' => 'Vùng nguyên liệu' ],
		],
		'btn'           => [ 'title' => 'Tìm hiểu thêm', 'url' => '/gioi-thieu/', 'target' => '' ],
	],
	// 7. BLOG
	[
		'acf_fc_layout' => 'blog',
		'badge'         => 'Tin Tức',
		'title'         => 'Kiến Thức Sức Khỏe',
		'post_ids'      => $blog_posts,
		'btn'           => [ 'title' => 'Xem tất cả', 'url' => '/tin-tuc/', 'target' => '' ],
	],
];

update_field( 'home_sections', $home_sections, $home_id );
echo PHP_EOL . "✓ Home page: 7 sections populated with real IDs" . PHP_EOL;

// ── Options update (Lạc Huy) ────────────────────────

update_field( 'company_name', 'Trà & Táo Đỏ Lạc Huy', 'option' );
update_field( 'hotline', '098 750 33 60', 'option' );
update_field( 'email', 'Lachuyhddt@gmail.com', 'option' );
update_field( 'address', 'TP. Hồ Chí Minh, Việt Nam', 'option' );
update_field( 'footer_tagline', 'Nơi cung cấp thực phẩm tự nhiên chất lượng và uy tín', 'option' );
update_field( 'footer_copyright', '© 2026 Trà & Táo Đỏ Lạc Huy. All rights reserved.', 'option' );
update_field( 'zalo_url', 'https://zalo.me/0987503360', 'option' );

echo "✓ Options updated with Lạc Huy info" . PHP_EOL;
echo PHP_EOL . "=== DONE ===" . PHP_EOL;
