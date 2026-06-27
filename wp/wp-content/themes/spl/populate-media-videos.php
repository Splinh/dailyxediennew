<?php
/**
 * Populate Media Reviews (video section) with real YouTube videos from dailyxedien.vn.
 *
 * Thumbnails auto-generated from YouTube embed URLs by the template.
 * Playlist items include title for caption overlay.
 *
 * Run via WP-CLI:
 * php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-media-videos.php
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'update_field' ) ) {
	echo "⚠ ACF not active\n";
	exit;
}

$home_id = (int) get_option( 'page_on_front' );
if ( ! $home_id ) {
	echo "⚠ No front page set\n";
	exit;
}

echo "Front Page ID: {$home_id}\n";

// ── Video data scraped from dailyxedien.vn ──
$videos = [
	[
		'id'    => 'XMfE5XmpWn0',
		'title' => 'Xe Điện Bluera Việt Nhật Lọt Top 10 Thương Hiệu Dẫn Đầu Việt Nam 2026',
	],
	[
		'id'    => 'YmoliAqhJn8',
		'title' => 'Bluera Việt Nhật — CAFETEK Đưa Tin Sau Sự Kiện Top 10 Thương Hiệu Dẫn Đầu 2026',
	],
	[
		'id'    => 'DHrISK53OPs',
		'title' => 'Xe Điện Bluera Việt Nhật Hút Khách Tại Triển Lãm Autotech & Accessories 2024',
	],
	[
		'id'    => 'xFo863UkIE4',
		'title' => 'Khám Phá AIE Smile I — Mẫu Xe Đạp Điện Cỡ Lớn Khẳng Định Vị Thế Dẫn Đầu',
	],
	[
		'id'    => 'YzBZK1FDI7I',
		'title' => 'AI EBike Giới Thiệu Dòng Xe Điện A.I Tại Triển Lãm Quốc Tế Xe Hai Bánh Việt Nam 2024',
	],
	[
		'id'    => 'ZljyfMUV4DI',
		'title' => 'Xe 3 Gác Điện Chở Hàng Bluera 2024',
	],
	[
		'id'    => '0haAatnAXTg',
		'title' => 'Chạm Mặt Camelo I8 — Mẫu Xe Đạp Điện Đẹp Lạ Dành Cho Nàng Thơ',
	],
	[
		'id'    => '0SCyPv943Rg',
		'title' => 'Tour Solo Xe Đạp Điện AI EBike Smile Đi Vũng Tàu Hết 143km',
	],
];

// ── Build playlist (thumbnail = 0 → template auto-generates from YouTube URL) ──
$playlist = [];
foreach ( $videos as $v ) {
	$playlist[] = [
		'title'     => $v['title'],
		'video_url' => "https://www.youtube.com/embed/{$v['id']}",
		'thumbnail' => 0, // Let template auto-generate from YouTube.
	];
}

$main_video = $videos[0];

$media_data = [
	'acf_fc_layout'        => 'media_reviews',
	'disable'              => 0,
	'video_title'          => 'Video Đại Lý Xe Điện',
	'video_subtitle'       => 'Trải nghiệm thực tế từ các sự kiện và đánh giá xe',
	'video_url'            => "https://www.youtube.com/embed/{$main_video['id']}",
	'video_duration'       => '',
	'video_thumbnail'      => 0, // Auto-generate from YouTube URL.
	'playlist'             => $playlist,
	'testimonial_title'    => 'Cảm nhận khách hàng',
	'testimonial_subtitle' => 'Đánh giá thực tế',
	'testimonials'         => [
		// ── 3 đánh giá từ dailyxedien.vn ──
		[
			'name'        => 'A. Thanh Lộc',
			'location'    => 'Nhân viên văn phòng',
			'avatar_text' => 'TL',
			'rating'      => 5,
			'comment'     => '"Tôi đã tìm nhiều lựa chọn trước khi quyết định chọn Bluera vì sản phẩm chất lượng, dịch vụ khách hàng tốt. Một lựa chọn đáng giá cho người muốn sở hữu xe đạp điện."',
		],
		[
			'name'        => 'A. Xuân Thanh',
			'location'    => 'Kinh doanh tự do',
			'avatar_text' => 'XT',
			'rating'      => 5,
			'comment'     => '"Mình mua xe điện vì mục đích tiết kiệm chi phí khi phải giao hàng liên tục cho khách. Nên mình đánh giá xe Aie Smile này thật sự rất đáng để mua so với tầm giá."',
		],
		[
			'name'        => 'Bạn Toàn',
			'location'    => 'Sinh viên',
			'avatar_text' => 'BT',
			'rating'      => 5,
			'comment'     => '"Ban đầu mình cũng không tin đây là chiếc xe đạp điện với nhiều tính năng và công nghệ thông minh mới nhất mà giá lại hợp túi tiền cho các bạn học sinh cấp 2 cấp 3."',
		],
		// ── 3 đánh giá bổ sung ──
		[
			'name'        => 'Nguyễn Minh Anh',
			'location'    => 'TP. Thủ Đức, TP.HCM',
			'avatar_text' => 'MA',
			'rating'      => 5,
			'comment'     => '"Xe chạy êm, nhân viên hướng dẫn kỹ cách sạc và dùng định vị. Sạc đầy đi được khá xa. Rất hài lòng!"',
		],
		[
			'name'        => 'Trần Quốc Bảo',
			'location'    => 'Biên Hòa, Đồng Nai',
			'avatar_text' => 'QB',
			'rating'      => 5,
			'comment'     => '"Giao xe nhanh, nhân viên tận tình hướng dẫn. Mình yên tâm hơn nhờ có quản lý pin và bảo hành rõ ràng."',
		],
		[
			'name'        => 'Hoàng Nam',
			'location'    => 'Quận 7, TP.HCM',
			'avatar_text' => 'HN',
			'rating'      => 5,
			'comment'     => '"Dịch vụ bảo dưỡng vàng 3 năm cực chu đáo. Hệ thống đại lý chuyên nghiệp, đáng tin cậy lắm!"',
		],
	],
];

// ── Read existing home_sections and replace ──
$sections = get_field( 'home_sections', $home_id );

if ( ! is_array( $sections ) ) {
	echo "⚠ home_sections is empty or not set.\n";
	exit;
}

echo "\n── Updating media_reviews section ──\n";

$found = false;
foreach ( $sections as $i => $s ) {
	if ( ( $s['acf_fc_layout'] ?? '' ) === 'media_reviews' ) {
		$sections[ $i ] = $media_data;
		$found          = true;
		echo "  ✓ Replaced media_reviews at index {$i}\n";
		break;
	}
}

if ( ! $found ) {
	$sections[] = $media_data;
	echo "  ✓ Appended media_reviews to end of sections\n";
}

// ── Save ──
update_field( 'home_sections', $sections, $home_id );

echo "\n── Video playlist ──\n";
foreach ( $videos as $idx => $v ) {
	$label = $idx === 0 ? '★ Main' : "  #{$idx}  ";
	echo "  {$label} [{$v['id']}] {$v['title']}\n";
}

echo "\n✅ Media Reviews populated with " . count( $videos ) . " YouTube videos (with titles).\n";
echo "   Thumbnails: auto-generated from YouTube embed URLs (no sideload).\n";
echo "Done.\n";
