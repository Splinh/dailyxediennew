<?php
/**
 * Populate Theme Options with dailyxedien.vn mockup data.
 *
 * Run via WP-CLI:
 * php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-dxd-options-data.php
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'update_field' ) ) {
	echo "⚠ ACF not active" . PHP_EOL;
	exit;
}

echo "=== START POPULATE THEME OPTIONS ===" . PHP_EOL;

// ── Header Options ──
update_field( 'hotline_label', 'Hotline tư vấn 24/7', 'option' );
update_field( 'logo_tagline', 'Hệ thống xe điện lớn nhất Việt Nam', 'option' );

// topbar links
$topbar_links = [
	[ 'link' => [ 'title' => 'Sứ Mệnh', 'url' => home_url( '/su-menh/' ) ] ],
	[ 'link' => [ 'title' => 'Cơ Hội Hợp Tác', 'url' => home_url( '/co-hoi-hop-tac/' ) ] ],
	[ 'link' => [ 'title' => 'Hệ Thống Cửa Hàng', 'url' => home_url( '/he-thong-cua-hang/' ) ] ],
	[ 'link' => [ 'title' => 'Tin Tức', 'url' => home_url( '/tin-tuc/' ) ] ],
];
update_field( 'topbar_links', $topbar_links, 'option' );
echo "✓ Populated Header options" . PHP_EOL;

// ── Contact Options ──
update_field( 'hotline', '0933 505 222', 'option' );
update_field( 'email', 'info@dailyxedien.vn', 'option' );
update_field( 'address', '466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP.HCM', 'option' );
update_field( 'working_hours', 'Thứ 2 – Thứ 7: 08:00 – 17:30', 'option' );
echo "✓ Populated Contact options" . PHP_EOL;

// ── Footer Options ──
update_field( 'footer_desc', 'Dailyxedien.vn - Hệ thống phân phối xe điện, xe 50cc, xe máy điện chính hãng. Cam kết sản phẩm rõ nguồn gốc, chính sách giá minh bạch và hậu mãi dễ theo dõi.', 'option' );
update_field( 'company_name', 'CÔNG TY TNHH DAILYXEDIEN VIỆT NAM', 'option' );
update_field( 'company_tax', '0314159265', 'option' );
update_field( 'website_url', 'https://www.dailyxedien.vn', 'option' );
update_field( 'gov_badge_url', 'https://online.gov.vn/', 'option' );
echo "✓ Populated Footer options" . PHP_EOL;

// ── Social Options ──
update_field( 'facebook_url', 'https://www.facebook.com/DaiLyXeDien/', 'option' );
update_field( 'youtube_url', 'https://www.youtube.com/@XeDien', 'option' );
update_field( 'zalo_url', 'https://zalo.me/0933505222', 'option' );
update_field( 'tiktok_url', 'https://www.tiktok.com/@dailyxedienhcm', 'option' );
echo "✓ Populated Social options" . PHP_EOL;

// ── Product Badges ──
$product_trust = [
	[ 'icon' => 'truck', 'text' => 'Miễn phí giao hàng bán kính 10km' ],
	[ 'icon' => 'clock', 'text' => 'Giao hàng nhanh chóng' ],
	[ 'icon' => 'return', 'text' => 'Đổi trả miễn phí 7 ngày đầu' ],
];
update_field( 'product_trust', $product_trust, 'option' );
echo "✓ Populated Product Badges options" . PHP_EOL;

// ── Floating Button Toggles ──
update_field( 'show_zalo_float', 1, 'option' );
update_field( 'show_phone_float', 1, 'option' );
update_field( 'show_back_to_top', 1, 'option' );
echo "✓ Populated Floating Button toggles" . PHP_EOL;

// ── Tracking & Analytics ──
update_field( 'tracking_enabled', 1, 'option' );
update_field( 'ga4_measurement_id', '', 'option' );
update_field( 'ga4_ads_conversion_id', '', 'option' );
update_field( 'ga4_ads_conversion_label', '', 'option' );
update_field( 'fb_pixel_id', '', 'option' );
echo "✓ Populated Tracking defaults (IDs empty — fill in Admin)" . PHP_EOL;

// ── Seasonal / Holiday Decorations ──
update_field( 'seasonal_enabled', 0, 'option' );
update_field( 'seasonal_preset', 'none', 'option' );
update_field( 'seasonal_custom_class', '', 'option' );
update_field( 'seasonal_bar_text', '', 'option' );
update_field( 'seasonal_bar_link', '', 'option' );
update_field( 'seasonal_bar_color', '#dc2626', 'option' );
echo "✓ Populated Seasonal defaults (disabled)" . PHP_EOL;

echo "=== POPULATE THEME OPTIONS COMPLETED ===" . PHP_EOL;
