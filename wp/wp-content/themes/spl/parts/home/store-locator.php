<?php
/**
 * Home page — Store Locator section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];
$title = $data['title'] ?? __( 'Hệ thống cửa hàng & đại lý ủy quyền', 'spl' );
$subtitle = $data['subtitle'] ?? __( 'Tìm địa chỉ đại lý gần bạn nhất', 'spl' );
$stores = $data['stores'] ?? [];

// Default fallback stores if empty.
if ( empty( $stores ) ) {
	$stores = [
		[
			'name'     => 'Đại Lý Ủy Quyền Bluera An Giang',
			'province' => 'AN GIANG',
			'phone'    => '0933 505 222',
			'type'     => 'authorized',
			'address'  => '123 Trần Hưng Đạo, P. Mỹ Phước, TP. Long Xuyên, An Giang',
			'map_url'  => 'https://maps.google.com/?q=123 Trần Hưng Đạo, Mỹ Phước, Long Xuyên, An Giang',
		],
		[
			'name'     => 'Đại Lý Ủy Quyền Yadea Vũng Tàu',
			'province' => 'BÀ RỊA - VŨNG TÀU',
			'phone'    => '0933 505 222',
			'type'     => 'authorized',
			'address'  => '456 Đường 30/4, P. Rạch Dừa, TP. Vũng Tàu',
			'map_url'  => 'https://maps.google.com/?q=456 Đường 30 tháng 4, Rạch Dừa, Vũng Tàu',
		],
		[
			'name'     => 'Cửa Hàng Ủy Quyền Dibao Bình Dương',
			'province' => 'BÌNH DƯƠNG',
			'phone'    => '0933 505 222',
			'type'     => 'regular',
			'address'  => '789 Đại Lộ Bình Dương, TP. Thủ Dầu Một, Bình Dương',
			'map_url'  => 'https://maps.google.com/?q=789 Đại Lộ Bình Dương, Thủ Dầu Một, Bình Dương',
		],
		[
			'name'     => 'Đại Lý Ủy Quyền VinFast Thủ Đức',
			'province' => 'TP. HỒ CHÍ MINH',
			'phone'    => '0933 505 222',
			'type'     => 'authorized',
			'address'  => '466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP.HCM',
			'map_url'  => 'https://maps.google.com/?q=466 Nguyễn Duy Trinh, Bình Trưng Đông, Thủ Đức, Hồ Chí Minh',
		],
		[
			'name'     => 'Cửa Hàng Xe Điện Vespa Q7',
			'province' => 'TP. HỒ CHÍ MINH',
			'phone'    => '0933 505 222',
			'type'     => 'regular',
			'address'  => '105 Nguyễn Thị Thập, P. Tân Hưng, Quận 7, TP.HCM',
			'map_url'  => 'https://maps.google.com/?q=105 Nguyễn Thị Thập, Tân Hưng, Quận 7, Hồ Chí Minh',
		],
	];
}

// Extract unique provinces for the carousel selector.
$provinces = array_unique( array_filter( array_map( function( $store ) {
	return strtoupper( trim( $store['province'] ?? '' ) );
}, $stores ) ) );
sort( $provinces );

// Fallback provinces if empty
if ( empty( $provinces ) ) {
	$provinces = [ 'AN GIANG', 'BÀ RỊA - VŨNG TÀU', 'BÌNH DƯƠNG', 'TP. HỒ CHÍ MINH' ];
}
?>
<section class="max-w-7xl mx-auto px-4 mb-16 scroll-mt-24" id="store-section">
	<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-emerald-500 rounded-full"></span>
			<h2 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<span class="text-sm font-semibold text-slate-400"><?php echo esc_html( $subtitle ); ?></span>
	</div>

	<!-- Horizontal province carousel bar -->
	<div class="relative mb-6">
		<button onclick="scrollProvinces('left')" aria-label="<?php esc_attr_e( 'Xem tỉnh trước', 'spl' ); ?>" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white shadow-md border border-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-50 focus:outline-none">
			<?php echo spl_icon( 'chevron-left', 'w-3 h-3' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<div id="province-scroll" class="flex gap-2 overflow-x-auto no-scrollbar px-10 py-1.5 scroll-smooth">
			<?php foreach ( $provinces as $index => $prov ) :
				$active_prov_cls = $index === 0
					? 'prov-btn active px-4 py-2 bg-emerald-500 text-white shadow-md hover:bg-emerald-600 text-xs font-bold rounded-full transition-all whitespace-nowrap'
					: 'prov-btn px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-bold rounded-full transition-all whitespace-nowrap';
				?>
				<button onclick="filterProvince('<?php echo esc_attr( $prov ); ?>', this)" class="<?php echo esc_attr( $active_prov_cls ); ?>">
					<?php echo esc_html( $prov ); ?>
				</button>
			<?php endforeach; ?>
		</div>
		<button onclick="scrollProvinces('right')" aria-label="<?php esc_attr_e( 'Xem tỉnh kế tiếp', 'spl' ); ?>" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white shadow-md border border-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-50 focus:outline-none">
			<?php echo spl_icon( 'chevron-right', 'w-3 h-3' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>

	<!-- Sub-tabs switching style -->
	<div class="flex items-center gap-6 border-b border-slate-200 pb-3 mb-6">
		<button onclick="switchStoreTab('authorized')" id="tab-auth-btn" class="flex items-center gap-2 text-sm font-bold text-emerald-600 border-b-2 border-emerald-600 pb-2 transition-all cursor-pointer focus:outline-none">
			<?php echo spl_icon( 'user', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'ĐẠI LÝ ỦY QUYỀN', 'spl' ); ?>
		</button>
		<button onclick="switchStoreTab('regular')" id="tab-reg-btn" class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-slate-600 transition-all pb-2 cursor-pointer focus:outline-none">
			<?php echo spl_icon( 'bolt', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'CỬA HÀNG ỦY QUYỀN', 'spl' ); ?>
		</button>
	</div>

	<!-- Dynamic Store List Area (Populated client-side via JSON) -->
	<div id="store-list-container" class="grid grid-cols-1 md:grid-cols-3 gap-6 min-h-[120px]">
		<!-- JavaScript will load store cards dynamically here -->
	</div>

	<!-- Embed stores data as JSON -->
	<script id="dxd-stores-data" type="application/json">
		<?php echo json_encode( $stores, JSON_UNESCAPED_UNICODE ); ?>
	</script>
</section>
