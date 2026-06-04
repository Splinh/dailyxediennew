<?php
/**
 * Populate ACF Options (footer/company + activity) for the Lạc Huy demo.
 *
 * Known Lạc Huy data → correct values. Missing data → thaphaco demo values
 * (per client request, for demo only — replace with real data before production).
 *
 * Run via WP-CLI:
 *   php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-options-data.php
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'update_field' ) ) {
	echo "⚠ ACF not active" . PHP_EOL;
	exit;
}

// selector (field key) => value. Keys match acf-json/group_lachuy_options.json.
$fields = [
	// Known Lạc Huy data (from docs/client-info.md).
	'field_lachuy_opt_company_name'   => 'CÔNG TY TNHH TRÀ & TÁO ĐỎ LẠC HUY',

	// Missing → thaphaco demo data (replace before production).
	'field_lachuy_opt_company_intl'   => 'THAPHACO PHARMACEUTICAL COMPANY LIMITED',
	'field_lachuy_opt_company_tax'    => '0316573568',
	'field_lachuy_opt_complaint_phone'=> '0979.58.78.63',
	'field_lachuy_opt_addr_showroom'  => '22/21 Đường Số 21, P8 Gò Vấp, TP.HCM',
	'field_lachuy_opt_addr_farm'      => "Thôn 7, Xã M'Leo, Huyện Ea Súp, Tỉnh Đắk Lắk",
	'field_lachuy_opt_addr_factory'   => 'Cụm Công Nghiệp Tân An, TP. Buôn Ma Thuột, Đắk Lắk',
	'field_lachuy_opt_bank_account'   => '66868868 - Ngân hàng ACB - Vũ Văn Thắng',
	'field_lachuy_opt_facebook'       => 'https://www.facebook.com/thaphaco/',
	'field_lachuy_opt_youtube'        => 'https://www.youtube.com/channel/UCFMSYelcWCfj9OzJMz_sW-Q',
	'field_lachuy_opt_tiktok'         => 'https://www.tiktok.com/@thaoduocthaphaco',

	// Self / defaults.
	'field_lachuy_opt_website_url'    => home_url( '/' ),
	'field_lachuy_opt_activity_title' => 'Hình Ảnh Hoạt Động Công Ty',
	'field_lachuy_opt_activity_subtitle' => 'Lạc Huy',
];

foreach ( $fields as $key => $value ) {
	update_field( $key, $value, 'option' );
	echo "✓ {$key} = {$value}" . PHP_EOL;
}

echo "Done — " . count( $fields ) . " options updated." . PHP_EOL;
