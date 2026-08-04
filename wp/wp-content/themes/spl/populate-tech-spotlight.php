<?php
/**
 * Populate Tech Spotlight section ACF data on the homepage.
 *
 * Sideloads 3 generated tech images into the Media Library, then updates
 * the tech_spotlight layout inside the home_sections flexible content.
 *
 * Run via WP-CLI:
 * php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-tech-spotlight.php
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

// ── Helper: sideload image from theme resources/img ──
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Sideload a local theme image into the WP Media Library.
 * Returns existing attachment ID if an attachment with the same filename exists.
 *
 * @param string $filename Filename in resources/img/ (e.g. 'bms-battery.png').
 * @param string $alt_text Alt text for the attachment.
 * @return int Attachment ID (0 on failure).
 */
function dxd_sideload_theme_image( string $filename, string $alt_text = '' ): int {
	// Check if already uploaded.
	$existing = get_posts( [
		'post_type'   => 'attachment',
		'post_status' => 'inherit',
		'meta_query'  => [
			[
				'key'     => '_wp_attached_file',
				'value'   => $filename,
				'compare' => 'LIKE',
			],
		],
		'posts_per_page' => 1,
		'fields'         => 'ids',
	] );

	if ( ! empty( $existing ) ) {
		$id = $existing[0];
		echo "  ↳ Reuse existing media #{$id} for {$filename}\n";
		return $id;
	}

	$theme_path = get_theme_file_path( "resources/img/{$filename}" );
	if ( ! file_exists( $theme_path ) ) {
		echo "  ⚠ File not found: {$theme_path}\n";
		return 0;
	}

	// Copy to tmp for sideload.
	$tmp = wp_tempnam( $filename );
	copy( $theme_path, $tmp );

	$file_array = [
		'name'     => $filename,
		'tmp_name' => $tmp,
	];

	$attach_id = media_handle_sideload( $file_array, 0, $alt_text );

	if ( is_wp_error( $attach_id ) ) {
		echo "  ⚠ Sideload failed for {$filename}: {$attach_id->get_error_message()}\n";
		@unlink( $tmp );
		return 0;
	}

	// Set alt text.
	if ( $alt_text ) {
		update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt_text );
	}

	echo "  ✓ Uploaded {$filename} → media #{$attach_id}\n";
	return $attach_id;
}

// ── Sideload tech images ──
echo "\n── Uploading tech images ──\n";
$img_bms         = dxd_sideload_theme_image( 'bms-battery-v2.png', 'Hệ thống Pin LFP & BMS Thông Minh' );
$img_fingerprint = dxd_sideload_theme_image( 'fingerprint-lock.png', 'Khóa Vân Tay Bảo Mật Sinh Trắc Học' );
$img_app         = dxd_sideload_theme_image( 'smart-app-connect-v2.png', 'Kết Nối App Quản Lý Xe Thông Minh' );

// ── Read existing home_sections ──
$sections = get_field( 'home_sections', $home_id );

if ( ! is_array( $sections ) ) {
	echo "⚠ home_sections is empty or not set.\n";
	exit;
}

echo "\n── Updating tech_spotlight section ──\n";

$tech_data = [
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
			'description'  => 'Pin LFP (Lithium Iron Phosphate) thế hệ mới tích hợp bộ mạch quản lý BMS 16 cell giúp điều phối dòng xả tối ưu, tự ngắt khi quá nhiệt, chống quá tải và gia tăng tuổi thọ pin gấp 3 lần so với pin chì thông thường. Được trang bị trên các dòng xe Bluera và AI Ebike.',
			'image'        => $img_bms,
			'details'      => [
				[ 'label' => 'Tuổi thọ Pin', 'value' => '2.000 chu kỳ sạc/xả' ],
				[ 'label' => 'Quãng đường sạc', 'value' => '80–120km / một lần sạc' ],
				[ 'label' => 'Công nghệ bảo vệ', 'value' => 'Chống nước IP67 tuyệt đối' ],
			],
		],
		[
			'feature_id'   => 'fingerprint',
			'feature_name' => 'Mở khóa Vân Tay',
			'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-4.3-3-7-7-7s-7 2.7-7 7 3 7 7 7z"/><path d="M12 2a10 10 0 0 0-10 10c0 2.2.8 4.2 2 5.7"/><path d="M14 15a2 2 0 1 0-4 0"/></svg>',
			'title'        => 'Khóa Vân Tay Một Chạm — Bảo Mật Sinh Trắc Học',
			'description'  => 'Công nghệ vân tay bán dẫn (capacitive) tích hợp ngay tay lái, nhận diện chỉ 0.1 giây, hoạt động ổn định dưới mưa và mồ hôi. Chống sao chép vân tay giả, kết hợp chìa khóa CNC dự phòng. Có trên các dòng xe AI Ebike S5, S7 và Bluera Sportage.',
			'image'        => $img_fingerprint,
			'details'      => [
				[ 'label' => 'Tốc độ nhận diện', 'value' => '0.1 giây / lần quét' ],
				[ 'label' => 'Dung lượng lưu trữ', 'value' => 'Lên đến 10 vân tay' ],
				[ 'label' => 'Khóa dự phòng', 'value' => 'Chìa CNC chống sao chép' ],
			],
		],
		[
			'feature_id'   => 'smart-app',
			'feature_name' => 'Kết nối App Thông Minh',
			'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
			'title'        => 'Hệ Sinh Thái IoT & Ứng Dụng Di Động AI EBIKE',
			'description'  => 'Ứng dụng tích hợp công nghệ đỉnh cao, kết nối IoT thời gian thực, quản lý pin BMS thông minh, định vị GPS toàn cầu chính xác cao và kích hoạt bảo hành điện tử tiện lợi. Tải ứng dụng để tối ưu hóa trải nghiệm lái xe điện của bạn.',
			'image'        => $img_app,
			'details'      => [
				[ 'label' => 'Hệ sinh thái', 'value' => 'Kết nối IoT thời gian thực' ],
				[ 'label' => 'Định vị xe', 'value' => 'GPS chính xác cao' ],
				[ 'label' => 'Bảo hành', 'value' => 'Kích hoạt điện tử tự động' ],
			],
		],
		[
			'feature_id'   => 'download-app',
			'feature_name' => 'Tải Ứng Dụng AI EBIKE',
			'icon'         => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
			'title'        => 'Hệ Sinh Thái & Tải Ứng Dụng Di Động AI EBIKE',
			'description'  => 'Quét mã QR hoặc truy cập App Store / Google Play để cài đặt ứng dụng AI EBIKE chính thức. Tự động kết nối xe, quản lý pin BMS, định vị GPS chính xác và kích hoạt bảo hành điện tử.',
			'image'        => $img_app,
			'details'      => [
				[ 'label' => 'Nền tảng hỗ trợ', 'value' => 'iOS & Android' ],
				[ 'label' => 'Dung lượng app', 'value' => '~ 45 MB' ],
				[ 'label' => 'Cập nhật', 'value' => 'Miễn phí trọn đời' ],
			],
		],
	],
];

// ── Find and replace tech_spotlight in the sections array ──
$found = false;
foreach ( $sections as $i => $s ) {
	if ( ( $s['acf_fc_layout'] ?? '' ) === 'tech_spotlight' ) {
		$sections[ $i ] = $tech_data;
		$found          = true;
		echo "  ✓ Replaced tech_spotlight at index {$i}\n";
		break;
	}
}

if ( ! $found ) {
	// Insert after best_sellers (index 3) if not found.
	array_splice( $sections, 4, 0, [ $tech_data ] );
	echo "  ✓ Inserted tech_spotlight at index 4\n";
}

// ── Save back ──
update_field( 'home_sections', $sections, $home_id );

echo "\n✅ Tech Spotlight populated with 3 features + {$img_bms}, {$img_fingerprint}, {$img_app} media IDs.\n";
echo "Done.\n";
