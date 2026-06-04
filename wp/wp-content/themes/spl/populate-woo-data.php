<?php
/**
 * Populate WooCommerce products, categories, blog posts.
 *
 * Run via WP-CLI:
 * php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-woo-data.php
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

// ── 1. Product Categories ───────────────────────────

$categories = [
	'thao-duoc-kho'          => 'Thảo Dược Khô',
	'tra-tui-loc'            => 'Trà Túi Lọc',
	'bot-nguyen-chat'        => 'Bột Nguyên Chất',
	'si-bot-nguyen-chat'     => 'Sỉ Bột Nguyên Chất',
	'nguyen-lieu-nuoc-sam'   => 'Nguyên Liệu Nước Sâm',
	'gia-vi'                 => 'Gia Vị',
	'thao-duoc-tuoi'         => 'Thảo Dược Tươi',
	'tinh-dau-thien-nhien'   => 'Tinh Dầu Thiên Nhiên',
	'tao-do'                 => 'Táo Đỏ & Hạt Dinh Dưỡng',
	'hoa-thao-duoc'          => 'Hoa Thảo Dược',
];

$cat_ids = [];
echo "=== PRODUCT CATEGORIES ===" . PHP_EOL;
foreach ( $categories as $slug => $name ) {
	$existing = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $existing ) {
		$cat_ids[ $slug ] = $existing->term_id;
		echo "  Exists: {$name} (ID: {$existing->term_id})" . PHP_EOL;
	} else {
		$result = wp_insert_term( $name, 'product_cat', [ 'slug' => $slug ] );
		if ( ! is_wp_error( $result ) ) {
			$cat_ids[ $slug ] = $result['term_id'];
			echo "  ✓ Created: {$name} (ID: {$result['term_id']})" . PHP_EOL;
		} else {
			echo "  ✗ Error: {$name} - " . $result->get_error_message() . PHP_EOL;
		}
	}
}

// ── 2. WooCommerce Products ─────────────────────────

$products = [
	[
		'name'     => 'Trà Hoa Cúc Mật Ong',
		'slug'     => 'tra-hoa-cuc-mat-ong',
		'price'    => '85000',
		'sale'     => '65000',
		'cat'      => 'tra-tui-loc',
		'desc'     => 'Trà hoa cúc kết hợp mật ong nguyên chất, hỗ trợ giấc ngủ ngon, giảm stress hiệu quả.',
		'short'    => 'Trà hoa cúc mật ong - hỗ trợ giấc ngủ, giảm stress.',
		'featured' => true,
	],
	[
		'name'     => 'Bột Nghệ Nguyên Chất',
		'slug'     => 'bot-nghe-nguyen-chat',
		'price'    => '120000',
		'sale'     => '95000',
		'cat'      => 'bot-nguyen-chat',
		'desc'     => 'Bột nghệ nguyên chất 100% từ Đắk Lắk, hỗ trợ tiêu hóa, đẹp da, tăng sức đề kháng.',
		'short'    => 'Bột nghệ Đắk Lắk 100% nguyên chất.',
		'featured' => true,
	],
	[
		'name'     => 'Táo Đỏ Tân Cương Loại 1',
		'slug'     => 'tao-do-tan-cuong-loai-1',
		'price'    => '180000',
		'sale'     => '',
		'cat'      => 'tao-do',
		'desc'     => 'Táo đỏ Tân Cương (Hồng Táo) loại 1, quả to, ngọt tự nhiên, giàu vitamin C và chất chống oxy hóa.',
		'short'    => 'Táo đỏ Tân Cương loại 1 - quả to, ngọt tự nhiên.',
		'featured' => true,
	],
	[
		'name'     => 'Tinh Dầu Tràm Nguyên Chất',
		'slug'     => 'tinh-dau-tram-nguyen-chat',
		'price'    => '150000',
		'sale'     => '125000',
		'cat'      => 'tinh-dau-thien-nhien',
		'desc'     => 'Tinh dầu tràm 100% nguyên chất, chiết xuất tự nhiên, giúp kháng khuẩn, giữ ấm, phòng cảm cúm.',
		'short'    => 'Tinh dầu tràm nguyên chất 100%.',
		'featured' => true,
	],
	[
		'name'     => 'Trà Atiso Đà Lạt',
		'slug'     => 'tra-atiso-da-lat',
		'price'    => '95000',
		'sale'     => '79000',
		'cat'      => 'tra-tui-loc',
		'desc'     => 'Trà Atiso Đà Lạt thanh nhiệt, mát gan, hỗ trợ tiêu hóa. Sử dụng nguyên liệu từ vùng trồng Đà Lạt.',
		'short'    => 'Trà Atiso Đà Lạt - thanh nhiệt, mát gan.',
		'featured' => false,
	],
	[
		'name'     => 'Bột Đậu Đỏ',
		'slug'     => 'bot-dau-do',
		'price'    => '75000',
		'sale'     => '',
		'cat'      => 'bot-nguyen-chat',
		'desc'     => 'Bột đậu đỏ nguyên chất, giàu protein thực vật, hỗ trợ giảm cân, đẹp da.',
		'short'    => 'Bột đậu đỏ nguyên chất.',
		'featured' => false,
	],
	[
		'name'     => 'Gừng Khô Thái Lát',
		'slug'     => 'gung-kho-thai-lat',
		'price'    => '60000',
		'sale'     => '48000',
		'cat'      => 'thao-duoc-kho',
		'desc'     => 'Gừng khô thái lát mỏng, phơi tự nhiên, giữ nguyên tinh chất. Dùng pha trà, nấu nước gừng.',
		'short'    => 'Gừng khô thái lát - pha trà, nấu nước.',
		'featured' => false,
	],
	[
		'name'     => 'Trà Gạo Lứt Rang',
		'slug'     => 'tra-gao-lut-rang',
		'price'    => '55000',
		'sale'     => '',
		'cat'      => 'tra-tui-loc',
		'desc'     => 'Trà gạo lứt rang thơm ngon, hỗ trợ giảm cân, thanh lọc cơ thể, tốt cho tim mạch.',
		'short'    => 'Trà gạo lứt rang - giảm cân, thanh lọc.',
		'featured' => false,
	],
	[
		'name'     => 'Nguyên Liệu Nước Sâm',
		'slug'     => 'nguyen-lieu-nuoc-sam-goi',
		'price'    => '35000',
		'sale'     => '28000',
		'cat'      => 'nguyen-lieu-nuoc-sam',
		'desc'     => 'Gói nguyên liệu nước sâm đầy đủ, tiện lợi, chỉ cần nấu với nước. Thanh mát, giải nhiệt.',
		'short'    => 'Gói nước sâm tiện lợi - thanh mát giải nhiệt.',
		'featured' => true,
	],
	[
		'name'     => 'Bột Trà Xanh Matcha',
		'slug'     => 'bot-tra-xanh-matcha',
		'price'    => '195000',
		'sale'     => '165000',
		'cat'      => 'bot-nguyen-chat',
		'desc'     => 'Bột trà xanh Matcha nguyên chất, giàu chất chống oxy hóa EGCG, dùng pha trà hoặc làm bánh.',
		'short'    => 'Matcha nguyên chất - giàu chất chống oxy hóa.',
		'featured' => true,
	],
	[
		'name'     => 'Hoa Nhài Sấy Khô',
		'slug'     => 'hoa-nhai-say-kho',
		'price'    => '110000',
		'sale'     => '',
		'cat'      => 'hoa-thao-duoc',
		'desc'     => 'Hoa nhài sấy khô nguyên cánh, pha trà thơm dịu, an thần, giúp ngủ ngon.',
		'short'    => 'Hoa nhài sấy khô - trà thơm dịu, an thần.',
		'featured' => false,
	],
	[
		'name'     => 'Quế Thanh Hóa',
		'slug'     => 'que-thanh-hoa',
		'price'    => '45000',
		'sale'     => '',
		'cat'      => 'gia-vi',
		'desc'     => 'Quế Thanh Hóa chính gốc, thơm nồng, dùng nấu ăn hoặc pha trà quế. Chất lượng hàng đầu.',
		'short'    => 'Quế Thanh Hóa thơm nồng.',
		'featured' => false,
	],
];

echo PHP_EOL . "=== PRODUCTS ===" . PHP_EOL;
$product_ids = [];
foreach ( $products as $p ) {
	$existing = get_posts( [
		'name'        => $p['slug'],
		'post_type'   => 'product',
		'post_status' => 'publish',
		'numberposts' => 1,
	] );

	if ( ! empty( $existing ) ) {
		$product_ids[] = $existing[0]->ID;
		echo "  Exists: {$p['name']} (ID: {$existing[0]->ID})" . PHP_EOL;
		continue;
	}

	$id = wp_insert_post( [
		'post_title'   => $p['name'],
		'post_name'    => $p['slug'],
		'post_content' => $p['desc'],
		'post_excerpt' => $p['short'],
		'post_status'  => 'publish',
		'post_type'    => 'product',
	] );

	if ( is_wp_error( $id ) ) {
		echo "  ✗ Error: {$p['name']}" . PHP_EOL;
		continue;
	}

	$product_ids[] = $id;

	// Set product type.
	wp_set_object_terms( $id, 'simple', 'product_type' );

	// Set category.
	if ( isset( $cat_ids[ $p['cat'] ] ) ) {
		wp_set_object_terms( $id, [ $cat_ids[ $p['cat'] ] ], 'product_cat' );
	}

	// Set pricing.
	update_post_meta( $id, '_regular_price', $p['price'] );
	if ( ! empty( $p['sale'] ) ) {
		update_post_meta( $id, '_sale_price', $p['sale'] );
		update_post_meta( $id, '_price', $p['sale'] );
	} else {
		update_post_meta( $id, '_price', $p['price'] );
	}

	// Stock.
	update_post_meta( $id, '_stock_status', 'instock' );
	update_post_meta( $id, '_manage_stock', 'no' );
	update_post_meta( $id, '_visibility', 'visible' );

	// Featured.
	if ( ! empty( $p['featured'] ) ) {
		update_post_meta( $id, '_featured', 'yes' );
	}

	echo "  ✓ Created: {$p['name']} (ID: {$id}) - " . number_format( (int) $p['price'] ) . 'đ' . PHP_EOL;
}

// ── 3. Blog Posts ───────────────────────────────────

$blog_posts = [
	[
		'title'   => 'Tác dụng của trà thảo mộc đối với sức khỏe',
		'slug'    => 'tac-dung-tra-thao-moc',
		'content' => '<p>Trà thảo mộc từ lâu đã được biết đến với những lợi ích tuyệt vời cho sức khỏe. Từ việc hỗ trợ tiêu hóa, giảm stress đến tăng cường hệ miễn dịch, mỗi loại trà mang lại những công dụng riêng biệt.</p><h2>1. Hỗ trợ giấc ngủ</h2><p>Trà hoa cúc, trà lavender giúp thư giãn thần kinh, cải thiện chất lượng giấc ngủ tự nhiên mà không gây tác dụng phụ.</p><h2>2. Tăng cường miễn dịch</h2><p>Trà gừng, trà quế chứa nhiều chất chống oxy hóa, giúp cơ thể chống lại các tác nhân gây bệnh.</p><h2>3. Hỗ trợ tiêu hóa</h2><p>Trà bạc hà, trà gạo lứt hỗ trợ hệ tiêu hóa hoạt động trơn tru, giảm đầy hơi, khó tiêu.</p>',
		'excerpt' => 'Khám phá những lợi ích sức khỏe tuyệt vời từ trà thảo mộc thiên nhiên.',
	],
	[
		'title'   => 'Táo đỏ - Siêu thực phẩm bổ dưỡng cho sức khỏe',
		'slug'    => 'tao-do-sieu-thuc-pham-bo-duong',
		'content' => '<p>Táo đỏ (Hồng táo) là một trong những loại thực phẩm giàu dinh dưỡng nhất. Giàu vitamin C, chất chống oxy hóa và các khoáng chất thiết yếu, táo đỏ giúp bồi bổ sức khỏe toàn diện.</p><h2>Công dụng của táo đỏ</h2><ul><li>Bổ máu, hỗ trợ tạo hồng cầu</li><li>Tăng cường hệ miễn dịch</li><li>Đẹp da, chống lão hóa</li><li>Hỗ trợ giấc ngủ</li><li>Tốt cho tim mạch</li></ul><h2>Cách sử dụng</h2><p>Táo đỏ có thể ăn trực tiếp, nấu chè, hầm gà, hoặc ngâm mật ong. Mỗi ngày nên dùng 5-10 quả để đạt hiệu quả tốt nhất.</p>',
		'excerpt' => 'Tìm hiểu về giá trị dinh dưỡng và công dụng của táo đỏ đối với sức khỏe.',
	],
	[
		'title'   => 'Hướng dẫn chọn mua bột nguyên chất đúng chuẩn',
		'slug'    => 'huong-dan-chon-mua-bot-nguyen-chat',
		'content' => '<p>Bột nguyên chất ngày càng phổ biến trong đời sống hàng ngày. Từ bột nghệ, bột đậu đỏ đến bột trà xanh, mỗi loại đều có công dụng và cách sử dụng riêng.</p><h2>Cách phân biệt bột thật và bột giả</h2><p>Bột nguyên chất thường có màu tự nhiên không quá đậm, mùi thơm nhẹ đặc trưng, và tan đều khi hòa với nước ấm.</p><h2>Mẹo bảo quản</h2><p>Bảo quản bột nguyên chất nơi khô ráo, thoáng mát, tránh ánh nắng trực tiếp. Đậy kín sau mỗi lần sử dụng để giữ hương vị và chất lượng.</p>',
		'excerpt' => 'Mẹo phân biệt bột nguyên chất thật và cách chọn mua sản phẩm chất lượng.',
	],
	[
		'title'   => '5 loại trà thảo dược tốt nhất cho mùa hè',
		'slug'    => '5-loai-tra-thao-duoc-tot-nhat-mua-he',
		'content' => '<p>Mùa hè nóng bức, một ly trà thảo dược mát lạnh không chỉ giải khát mà còn bổ sung dưỡng chất cho cơ thể.</p><h2>1. Trà Atiso</h2><p>Thanh nhiệt, mát gan, đặc biệt tốt trong mùa hè oi ả.</p><h2>2. Trà Hoa Cúc</h2><p>Giải nhiệt, giảm stress, hỗ trợ giấc ngủ.</p><h2>3. Trà Bạc Hà</h2><p>Mát họng, thơm dịu, giúp tiêu hóa tốt.</p><h2>4. Trà Lạc Tiên</h2><p>An thần tự nhiên, giúp ngủ ngon.</p><h2>5. Nước Sâm</h2><p>Giải nhiệt hiệu quả, thanh mát cơ thể toàn diện.</p>',
		'excerpt' => 'Top 5 loại trà thảo dược giải nhiệt, mát lành cho những ngày hè oi ả.',
	],
	[
		'title'   => 'Tinh dầu tràm và những công dụng tuyệt vời',
		'slug'    => 'tinh-dau-tram-cong-dung',
		'content' => '<p>Tinh dầu tràm là một trong những loại tinh dầu được sử dụng phổ biến nhất tại Việt Nam, đặc biệt trong chăm sóc trẻ em và người lớn tuổi.</p><h2>Công dụng chính</h2><ul><li>Kháng khuẩn, khử trùng tự nhiên</li><li>Giữ ấm cơ thể, phòng cảm cúm</li><li>Giảm đau nhức cơ khớp</li><li>Đuổi muỗi, côn trùng hiệu quả</li></ul><h2>Cách sử dụng an toàn</h2><p>Nhỏ 2-3 giọt tinh dầu vào máy khuếch tán hoặc xoa nhẹ lên ngực, lòng bàn chân. Với trẻ em, nên pha loãng với dầu nền.</p>',
		'excerpt' => 'Khám phá công dụng kháng khuẩn, giữ ấm, giảm đau từ tinh dầu tràm thiên nhiên.',
	],
	[
		'title'   => 'Bí quyết nấu nước sâm giải nhiệt tại nhà',
		'slug'    => 'bi-quyet-nau-nuoc-sam-giai-nhiet',
		'content' => '<p>Nước sâm là thức uống truyền thống được ưa chuộng trong mùa hè. Với gói nguyên liệu nước sâm sẵn có, bạn có thể dễ dàng nấu tại nhà.</p><h2>Nguyên liệu cơ bản</h2><p>Rễ tranh, mía lau, râu bắp, lá dứa, nhân trần, la hán quả. Tất cả đã có sẵn trong gói nguyên liệu Lạc Huy.</p><h2>Cách nấu</h2><ol><li>Rửa sạch nguyên liệu</li><li>Cho vào nồi 3 lít nước</li><li>Đun sôi, hạ lửa nhỏ nấu 30-45 phút</li><li>Lọc, thêm đường phèn vừa ăn</li><li>Để nguội, bảo quản tủ lạnh 2-3 ngày</li></ol>',
		'excerpt' => 'Hướng dẫn nấu nước sâm giải nhiệt thơm ngon tại nhà với gói nguyên liệu sẵn có.',
	],
];

echo PHP_EOL . "=== BLOG POSTS ===" . PHP_EOL;
foreach ( $blog_posts as $post_data ) {
	$existing = get_posts( [
		'name'        => $post_data['slug'],
		'post_type'   => 'post',
		'post_status' => 'publish',
		'numberposts' => 1,
	] );

	if ( ! empty( $existing ) ) {
		echo "  Exists: {$post_data['title']}" . PHP_EOL;
		continue;
	}

	$id = wp_insert_post( [
		'post_title'   => $post_data['title'],
		'post_name'    => $post_data['slug'],
		'post_content' => $post_data['content'],
		'post_excerpt' => $post_data['excerpt'],
		'post_status'  => 'publish',
		'post_type'    => 'post',
	] );

	if ( ! is_wp_error( $id ) ) {
		echo "  ✓ Created: {$post_data['title']} (ID: {$id})" . PHP_EOL;
	}
}

// ── 4. WooCommerce Setup ────────────────────────────

// Set shop page.
$shop_page = get_page_by_path( 'shop' );
if ( $shop_page ) {
	update_option( 'woocommerce_shop_page_id', $shop_page->ID );
	echo PHP_EOL . "✓ WooCommerce shop page set (ID: {$shop_page->ID})" . PHP_EOL;
}

// Currency.
update_option( 'woocommerce_currency', 'VND' );
update_option( 'woocommerce_currency_pos', 'right_space' );
update_option( 'woocommerce_price_thousand_sep', '.' );
update_option( 'woocommerce_price_decimal_sep', ',' );
update_option( 'woocommerce_price_num_decimals', 0 );
echo "✓ WooCommerce currency set to VND" . PHP_EOL;

echo PHP_EOL . "=== Done! ===" . PHP_EOL;
echo "Products: " . count( $product_ids ) . PHP_EOL;
echo "Categories: " . count( $cat_ids ) . PHP_EOL;
