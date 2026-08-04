<?php
/**
 * Template Name: Hệ Thống Đại Lý
 *
 * Dealer system page — lists all local_store CPT entries.
 * Matches htmlmau/he-thong-dai-ly.html layout using Tailwind.
 *
 * Sections:
 *   Header → Province carousel → Type tabs → Store cards grid
 *   → Store Locator (search + list + map)
 *   → CTA: Become a dealer
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Tailwind content scanning targets for dynamically generated classes in JS script:
 *
 * text-[#1e73be] border-[#1e73be] bg-[#1e73be] hover:bg-[#1e73be] hover:text-white
 * border-[#e0ebff] bg-[#f0f5ff] group-hover:text-[#1e73be] border-l-[#1e73be]
 * bg-slate-50 border-l-4 border-l-[#1e73be] hover:bg-[#165da0] hover:text-[#165da0]
 * bg-emerald-500 bg-[#1e73be] text-emerald-600 bg-emerald-50 border-emerald-100
 * hover:bg-emerald-600 hover:text-white border-emerald-600 text-emerald-600
 */

// Enqueue FontAwesome for the premium icons.
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0' );
} );

get_header();

$hotline     = Helper::getField( 'hotline', 'option' ) ?: '098 750 33 60';
$hotline_url = 'tel:' . preg_replace( '/\s+/', '', $hotline );

// ──────────────────────────────────────────────────────────────
// Query all provinces (local_store_state taxonomy terms).
// ──────────────────────────────────────────────────────────────
$provinces = get_terms( [
	'taxonomy'   => 'local_store_state',
	'hide_empty' => true,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );

if ( is_wp_error( $provinces ) ) {
	$provinces = [];
}

// ──────────────────────────────────────────────────────────────
// Query all store types (store_type taxonomy terms).
// ──────────────────────────────────────────────────────────────
$store_types = get_terms( [
	'taxonomy'   => 'store_type',
	'hide_empty' => true,
] );

if ( is_wp_error( $store_types ) ) {
	$store_types = [];
}

// ──────────────────────────────────────────────────────────────
// Get store data from plugin helper (cached, single source of truth).
// ──────────────────────────────────────────────────────────────
$stores_data = function_exists( 'dxd_dealer_get_stores' ) ? dxd_dealer_get_stores() : [];

// Prioritize provinces containing at least one store of the default type
if ( ! empty( $provinces ) && ! empty( $store_types ) && ! empty( $stores_data ) ) {
	$default_type = $store_types[0]->slug;
	$provinces_with_default = [];
	foreach ( $stores_data as $s ) {
		if ( $s['p'] && $s['ty'] === $default_type ) {
			$provinces_with_default[ $s['p'] ] = true;
		}
	}
	if ( ! empty( $provinces_with_default ) ) {
		$group_with = [];
		$group_without = [];
		foreach ( $provinces as $prov ) {
			if ( isset( $provinces_with_default[ $prov->name ] ) ) {
				$group_with[] = $prov;
			} else {
				$group_without[] = $prov;
			}
		}
		$provinces = array_merge( $group_with, $group_without );
	}
}

?>

<style>
/* Bulletproof styling for brand-blue detail buttons to prevent theme overrides */
#store-content .btn-detail-brand {
	background-color: #ffffff !important;
	color: #0B2545 !important;
	border-color: #0B2545 !important;
	transition: all 0.2s ease-in-out !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
	text-decoration: none !important;
}
#store-content .btn-detail-brand:hover {
	background-color: #0B2545 !important;
	color: #ffffff !important;
}
</style>

<!-- ===== SECTION HEADER ===== -->
<main id="store-content" class="max-w-7xl mx-auto px-4 py-8">

	<!-- ===== STORE LOCATOR: SEARCH + MAP ===== -->
	<section class="mb-16">
		<div class="flex items-center gap-3 mb-6">
			<span class="w-1.5 h-6 bg-[#1e73be] rounded-full"></span>
			<h2 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight"><?php esc_html_e( 'TÌM KIẾM CỬA HÀNG GẦN BẠN', 'spl' ); ?></h2>
		</div>

		<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
			<!-- LEFT: Search + Store List -->
			<div class="bg-white border border-slate-100 rounded-2xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] overflow-hidden">
				<!-- Search filters -->
				<div class="p-4 md:p-5 space-y-3 border-b border-slate-100 bg-slate-50/50">
					<div class="relative">
						<input type="text" id="locator-search" placeholder="<?php esc_attr_e( 'Nhập từ khoá tìm kiếm theo tên', 'spl' ); ?>" class="w-full pl-4 pr-10 py-2.5 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:border-[#0B2545] focus:ring-2 focus:ring-[#0B2545]/10 transition-all" />
						<button class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#0B2545]" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
							<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
						</button>
					</div>
					<div class="grid grid-cols-2 gap-3">
						<select id="locator-province" class="px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:border-[#0B2545] focus:ring-2 focus:ring-[#0B2545]/10 transition-all cursor-pointer">
							<option value=""><?php esc_html_e( 'Toàn Quốc', 'spl' ); ?></option>
							<?php foreach ( $provinces as $prov ) : ?>
								<option value="<?php echo esc_attr( $prov->name ); ?>"><?php echo esc_html( $prov->name ); ?></option>
							<?php endforeach; ?>
						</select>
						<select id="locator-type" class="px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:border-[#0B2545] focus:ring-2 focus:ring-[#0B2545]/10 transition-all cursor-pointer">
							<option value=""><?php esc_html_e( 'Tất cả loại', 'spl' ); ?></option>
							<?php foreach ( $store_types as $st ) : ?>
								<option value="<?php echo esc_attr( $st->slug ); ?>"><?php echo esc_html( $st->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<!-- Store listing -->
				<div id="locator-list" class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto">
					<!-- JS renders items here -->
				</div>
			</div>

			<!-- RIGHT: Google Maps -->
			<div class="bg-white border border-slate-100 rounded-2xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] overflow-hidden relative">
				<div class="h-full min-h-[500px] lg:min-h-[700px]">
					<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724937.8318799785!2d104.5!3d12.5!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31157a4d736a1e5f%3A0x9c4d3b2e3c5b6a7d!2zVmnhu4d0IE5hbQ!5e0!3m2!1svi!2svn!4v1"
							class="w-full h-full border-0" allowfullscreen loading="lazy" title="<?php esc_attr_e( 'Bản đồ hệ thống đại lý toàn quốc', 'spl' ); ?>"></iframe>
				</div>

				<!-- Custom Map Info Overlay -->
				<div id="map-info-overlay" class="hidden absolute top-4 left-4 right-4 md:right-auto md:w-[380px] bg-white rounded-2xl shadow-[0_10px_30px_-5px_rgba(0,0,0,0.15)] border border-slate-100 p-4 z-10 transition-all duration-300">
					<!-- Close button -->
					<button onclick="window.closeMapOverlay()" class="absolute top-2.5 right-2.5 w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center text-xs transition-colors" aria-label="<?php esc_html_e( 'Đóng', 'spl' ); ?>">
						<i class="fa-solid fa-xmark"></i>
					</button>
					<!-- Content -->
					<div id="map-info-content" class="mt-1"></div>
				</div>
			</div>
		</div>
	</section>

	<!-- ===== SECTION HEADER ===== -->
	<section class="mb-6">
		<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
			<div class="flex items-center gap-3">
				<span class="w-1.5 h-6 bg-amber-500 rounded-full"></span>
				<h1 class="text-2xl font-black text-[#0B2545] tracking-tight">
					<?php esc_html_e( 'HỆ THỐNG CỬA HÀNG & ĐẠI LÝ ỦY QUYỀN', 'spl' ); ?>
				</h1>
			</div>
			<span class="text-sm font-semibold text-slate-400"><?php esc_html_e( 'Tìm địa chỉ đại lý gần bạn nhất', 'spl' ); ?></span>
		</div>
	</section>

	<!-- ===== PROVINCE CAROUSEL ===== -->
	<?php if ( $provinces ) : ?>
	<section class="mb-6">
		<div class="relative">
			<button onclick="scrollProvinces('left')" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-7 h-7 md:w-8 md:h-8 rounded-full bg-[#0B2545] hover:bg-[#13315C] text-white shadow-md flex items-center justify-center transition-all border-none" aria-label="<?php esc_attr_e( 'Cuộn trái', 'spl' ); ?>">
				<svg class="w-3.5 h-3.5 text-white stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
			</button>
			<div id="province-scroll" class="flex gap-2 overflow-x-auto px-10 py-1.5 scroll-smooth" style="-ms-overflow-style:none;scrollbar-width:none;">
				<?php
				$first = true;
				foreach ( $provinces as $prov ) :
					$active_cls = $first ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold';
					?>
					<button onclick="filterProvince('<?php echo esc_js( $prov->name ); ?>', this)"
							class="prov-btn px-4 py-2 text-xs rounded-full transition-all whitespace-nowrap <?php echo $active_cls; ?>">
						<?php echo esc_html( mb_strtoupper( $prov->name ) ); ?>
					</button>
					<?php
					$first = false;
				endforeach;
				?>
			</div>
			<button onclick="scrollProvinces('right')" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-7 h-7 md:w-8 md:h-8 rounded-full bg-[#0B2545] hover:bg-[#13315C] text-white shadow-md flex items-center justify-center transition-all border-none" aria-label="<?php esc_attr_e( 'Cuộn phải', 'spl' ); ?>">
				<svg class="w-3.5 h-3.5 text-white stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg>
			</button>
		</div>
	</section>
	<?php endif; ?>

	<!-- ===== TYPE TABS ===== -->
	<?php if ( $store_types ) : ?>
	<section class="mb-6">
		<div class="flex items-center gap-6 border-b border-slate-200 pb-3">
			<?php
			$first_type = true;
			foreach ( $store_types as $st ) :
				$tab_cls = $first_type
					? 'text-amber-600 border-b-2 border-amber-500 font-black'
					: 'text-slate-400 hover:text-slate-600 font-bold';
				?>
				<button onclick="switchStoreTab('<?php echo esc_js( $st->slug ); ?>', this)"
						class="store-tab-btn flex items-center gap-2 text-sm pb-2 transition-all <?php echo $tab_cls; ?>">
					<?php if ( $first_type ) : ?>
						<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 1 0 7.75"/></svg>
					<?php else : ?>
						<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
					<?php endif; ?>
					<?php echo esc_html( mb_strtoupper( $st->name ) ); ?>
				</button>
				<?php
				$first_type = false;
			endforeach;
			?>
		</div>
	</section>
	<?php endif; ?>

	<!-- ===== STORE CARDS (JS Rendered) ===== -->
	<section class="mb-16">
		<div id="store-list-container" class="grid grid-cols-1 md:grid-cols-3 gap-6">
			<!-- JS will render store cards here -->
		</div>
		<p id="store-empty" class="hidden text-center py-12 text-slate-400 text-sm">
			<?php esc_html_e( 'Không tìm thấy cửa hàng nào phù hợp.', 'spl' ); ?>
		</p>
	</section>



	<!-- ===== CTA: BECOME A DEALER ===== -->
	<section class="mb-16">
		<div class="bg-gradient-to-br from-[#0B2545] via-[#13315C] to-[#0A192F] rounded-2xl p-8 md:p-12 text-white relative overflow-hidden border border-slate-800 shadow-xl">
			<div class="absolute top-0 right-0 w-40 h-40 bg-amber-500/10 rounded-full blur-2xl"></div>
			<div class="absolute bottom-0 left-0 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl"></div>
			<div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
				<div class="max-w-xl">
					<h2 class="text-xl md:text-2xl font-black mb-2 text-white"><?php esc_html_e( 'Trở thành đối tác của dailyxedien.vn?', 'spl' ); ?></h2>
					<p class="text-sm text-slate-300 leading-relaxed"><?php esc_html_e( 'Mở đại lý xe điện với thương hiệu uy tín, nhận hỗ trợ marketing, đào tạo nhân viên và nguồn hàng chính hãng từ 50+ thương hiệu.', 'spl' ); ?></p>
				</div>
				<div class="flex items-center gap-3 shrink-0">
					<?php
					$hop_tac_page = get_page_by_path( 'hop-tac' );
					$hop_tac_url  = $hop_tac_page ? get_permalink( $hop_tac_page ) : '#';
					?>
					<a href="<?php echo esc_url( $hop_tac_url ); ?>" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-6 py-3.5 rounded-xl text-sm transition-all flex items-center gap-2 shadow-lg shadow-amber-500/25 active:scale-95">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M7 11v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1h3m0 0V7a4 4 0 0 1 4-4h0a4 4 0 0 1 4 4v4h3a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-8"/></svg>
						<span><?php esc_html_e( 'Đăng ký ngay', 'spl' ); ?></span>
					</a>
					<a href="<?php echo esc_url( $hotline_url ); ?>" class="bg-white hover:bg-slate-50 text-[#0B2545] px-6 py-3.5 rounded-xl font-black text-sm transition-all flex items-center gap-2 shadow-md hover:shadow-lg border border-white/80 active:scale-95">
						<?php echo spl_icon( 'phone', 'w-4 h-4 text-[#0B2545] shrink-0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Gọi tư vấn', 'spl' ); ?></span>
					</a>
				</div>
			</div>
		</div>
	</section>

</main>

<!-- ===== INLINE STORE DATA + JS ===== -->
<script>
(function() {
	'use strict';

	const D = <?php echo wp_json_encode( $stores_data, JSON_UNESCAPED_UNICODE ); ?>;

	// State.
	let cp = '<?php echo esc_js( ! empty( $provinces ) ? $provinces[0]->name : '' ); ?>';
	let ct = '<?php echo esc_js( ! empty( $store_types ) ? $store_types[0]->slug : '' ); ?>';
	let activeLocatorStoreId = null;

	// Placeholder SVG.
	const PH = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 140"><rect fill="#f1f5f9" width="200" height="140"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#94a3b8" font-size="14">DXD</text></svg>');

	// ── Province carousel scroll ──
	window.scrollProvinces = function(dir) {
		var el = document.getElementById('province-scroll');
		if (el) el.scrollBy({ left: dir === 'left' ? -200 : 200, behavior: 'smooth' });
	};

	// ── Province filter ──
	window.filterProvince = function(name, btn) {
		cp = name;
		document.querySelectorAll('.prov-btn').forEach(function(b) {
			b.className = b.className.replace(/bg-amber-500 text-slate-950 font-black shadow-md/g, 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold');
		});
		if (btn) {
			btn.className = btn.className.replace(/bg-slate-100 text-slate-700 hover:bg-slate-200/g, 'bg-amber-500 text-slate-950 font-black shadow-md');
		}
		renderCards();
	};

	// ── Type tab switch ──
	window.switchStoreTab = function(slug, btn) {
		ct = (ct === slug) ? '' : slug;
		document.querySelectorAll('.store-tab-btn').forEach(function(b) {
			b.classList.remove('text-amber-600', 'border-b-2', 'border-amber-500', 'font-black');
			b.classList.add('text-slate-400', 'font-bold');
		});
		if (btn && ct) {
			btn.classList.remove('text-slate-400');
			btn.classList.add('text-amber-600', 'border-b-2', 'border-amber-500', 'font-black');
		}
		renderCards();
	};

	function filter(list) {
		var f = list;
		if (cp) f = f.filter(function(s) { return s.p === cp; });
		if (ct) f = f.filter(function(s) { return s.ty === ct; });
		return f;
	}

	// ── Store cards grid ──
	function renderCards() {
		var c = document.getElementById('store-list-container');
		var e = document.getElementById('store-empty');
		if (!c) return;
		var f = filter(D);
		if (!f.length) {
			c.innerHTML = '<div class="col-span-full py-16 text-center text-slate-400">' +
				'<i class="fa-solid fa-map-location-dot text-4xl mb-3 text-slate-300"></i>' +
				'<p class="font-bold text-slate-600">Chưa có ' + (ct === 'dai-ly-uy-quyen' ? 'đại lý ủy quyền' : 'cửa hàng ủy quyền') + ' tại khu vực này.</p>' +
				'<p class="text-xs text-slate-400 mt-1">Hệ thống đang mở rộng dịch vụ, vui lòng liên hệ tư vấn hỗ trợ mua xe online.</p>' +
				'</div>';
			if (e) e.classList.add('hidden');
			return;
		}
		if (e) e.classList.add('hidden');

		c.innerHTML = f.map(function(s) {
			var img = s.img || PH;
			var dl = s.ty.indexOf('dai-ly') >= 0 || s.tn.indexOf('Đại') >= 0;
			var tagClass = dl ? 'bg-emerald-500' : 'bg-[#1e73be]';
			var tagColor = dl ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 'text-[#1e73be] bg-[#f0f5ff] border-[#e0ebff]';
			var phones = [s.ph, s.hl].filter(Boolean).map(function(p) { return p.trim(); });
			
			var phonesHtml = phones.map(function(phone) {
				var cleanPhone = phone.replace(/\s+/g, '');
				return '<a href="tel:' + cleanPhone + '" class="inline-flex items-center gap-1.5 hover:text-[#1e73be] text-slate-700 font-bold transition-colors">' +
					'<i class="fa-solid fa-phone text-blue-500 text-xs shrink-0"></i> <span>' + phone + '</span></a>';
			}).join('');

			var dirUrl = (s.la && s.lo) ? 'https://www.google.com/maps/dir//' + s.la + ',' + s.lo + '/' : 'https://maps.google.com/?q=' + encodeURIComponent(s.t + ', ' + s.a);

			return '<div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] group hover:shadow-[0_20px_40px_-4px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">' +
				'<div>' +
					'<!-- Store Image 4:3 -->' +
					'<div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">' +
						'<img loading="lazy" src="' + img + '" alt="' + s.t + '" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">' +
						'<span class="absolute top-2.5 left-2.5 ' + tagClass + ' text-white font-bold text-[9px] px-2 py-0.5 rounded-md uppercase">' + s.tn + '</span>' +
					'</div>' +
					'<!-- Card Content -->' +
					'<div class="p-5 space-y-4">' +
						'<div>' +
							'<h3 class="font-black text-slate-800 text-sm leading-snug group-hover:text-[#1e73be] transition-colors">' + s.t + '</h3>' +
							'<div class="mt-2.5">' +
								'<span class="inline-block text-[10px] font-bold px-3 py-1 rounded-full border ' + tagColor + '">' +
									'<i class="fa-regular fa-star mr-1"></i> ' + s.tn +
								'</span>' +
							'</div>' +
						'</div>' +
						(phonesHtml ? '<div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs font-semibold py-2 border-y border-slate-50">' + phonesHtml + '</div>' : '') +
					'</div>' +
				'</div>' +
				'<!-- Actions & Address -->' +
				'<div class="p-5 pt-0 space-y-3.5">' +
					'<div class="grid grid-cols-2 gap-2">' +
						'<a href="' + dirUrl + '" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs py-2.5 rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5">' +
							'<i class="fa-solid fa-location-arrow"></i> Chỉ đường' +
						'</a>' +
						'<a href="' + s.u + '" class="btn-detail-brand border active:scale-95 font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-1.5">' +
							'<i class="fa-solid fa-circle-info"></i> Chi tiết' +
						'</a>' +
					'</div>' +
					'<p class="text-[10px] text-slate-400 flex items-start gap-1.5 leading-relaxed">' +
						'<i class="fa-solid fa-map-pin text-[#1e73be] shrink-0 mt-0.5"></i>' +
						'<span>' + s.a + '</span>' +
					'</p>' +
				'</div>' +
			'</div>';
		}).join('');
	}

	// ── Close map info overlay ──
	window.closeMapOverlay = function() {
		var overlay = document.getElementById('map-info-overlay');
		if (overlay) overlay.classList.add('hidden');
	};

	// ── Select store in locator list ──
	window.selectLocatorStore = function(id) {
		activeLocatorStoreId = id;
		renderLocator();
		// Update map iframe
		var s = D.find(function(item) { return item.id === id; });
		if (s) {
			var iframe = document.querySelector('#store-content iframe');
			if (iframe) {
				var mapSrc = '';
				if (s.la && s.lo) {
					mapSrc = 'https://maps.google.com/maps?q=' + s.la + ',' + s.lo + '&z=16&output=embed';
				} else {
					mapSrc = 'https://maps.google.com/maps?q=' + encodeURIComponent(s.t + ', ' + s.a) + '&z=16&output=embed';
				}
				iframe.src = mapSrc;
			}

			// Update custom map info overlay
			var overlay = document.getElementById('map-info-overlay');
			var content = document.getElementById('map-info-content');
			if (overlay && content) {
				var img = s.img || PH;
				var dl = s.ty.indexOf('dai-ly') >= 0 || s.tn.indexOf('Đại') >= 0;
				var badge = dl ? 'bg-[#f0f5ff] text-[#1e73be] border-[#e0ebff]' : 'bg-amber-50 text-amber-700 border-amber-200';
				var phones = [s.ph, s.hl].filter(Boolean);
				var phonesHtml = phones.map(function(p) { 
					var cleanP = p.trim().replace(/\s+/g, '');
					return '<a href="tel:' + cleanP + '" onclick="event.stopPropagation();" class="inline-flex items-center gap-1.5 hover:text-[#1e73be] transition-colors"><i class="fa-solid fa-phone text-blue-500 text-xs shrink-0"></i> <strong class="text-slate-700 font-bold">' + p.trim() + '</strong></a>'; 
				}).join('');
				var mapUrl = (s.la && s.lo) ? 'https://www.google.com/maps/dir//' + s.la + ',' + s.lo + '/' : 'https://maps.google.com/?q=' + encodeURIComponent(s.t + ', ' + s.a);

				content.innerHTML = '<div class="flex gap-3.5 items-start">' +
					'<div class="w-24 h-18 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-100"><img src="' + img + '" alt="' + s.t + '" class="w-full h-full object-cover"></div>' +
					'<div class="flex-1 min-w-0 text-left">' +
						'<h3 class="font-black text-slate-800 text-xs sm:text-sm uppercase leading-snug line-clamp-2">' + s.t + '</h3>' +
						'<div class="mt-1.5">' +
							'<span class="inline-block text-[9px] font-bold px-2 py-0.5 rounded border ' + badge + '">' + s.tn + '</span>' +
						'</div>' +
					'</div>' +
				'</div>' +
				'<div class="mt-3 pt-2.5 border-t border-slate-100 space-y-2 text-xs text-slate-500 text-left">' +
					'<p class="flex items-start gap-1.5 text-slate-600 leading-relaxed"><i class="fa-solid fa-location-dot text-[#1e73be] mt-0.5 shrink-0"></i> <span>' + s.a + '</span></p>' +
					(phonesHtml ? '<div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-600 mt-1">' + phonesHtml + '</div>' : '') +
					'<div class="grid grid-cols-2 gap-2 mt-3 pt-1">' +
						'<a href="' + mapUrl + '" target="_blank" class="border border-emerald-600 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-xl py-2 px-3 text-[11px] font-bold flex items-center justify-center gap-1.5 transition-all">' +
							'<i class="fa-solid fa-paper-plane text-[10px]"></i> Chỉ đường' +
						'</a>' +
						'<a href="' + s.u + '" class="btn-detail-brand border rounded-xl py-2 px-3 text-[11px] font-bold flex items-center justify-center gap-1.5 transition-all">' +
							'<i class="fa-solid fa-circle-info text-[10px]"></i> Xem chi tiết' +
						'</a>' +
					'</div>' +
				'</div>';

				overlay.classList.remove('hidden');
			}

			// Scroll map into view on mobile
			if (window.innerWidth < 1024) {
				var mapContainer = iframe ? iframe.parentElement : null;
				if (mapContainer) {
					mapContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
			}
		}
	};

	// ── Locator search/filter ──
	function renderLocator() {
		var c = document.getElementById('locator-list');
		if (!c) return;
		var kw = (document.getElementById('locator-search') || {}).value || '';
		kw = kw.toLowerCase();
		var pv = (document.getElementById('locator-province') || {}).value || '';
		var tp = (document.getElementById('locator-type') || {}).value || '';

		var f = D;
		if (kw) f = f.filter(function(s) { return s.t.toLowerCase().indexOf(kw) >= 0 || s.a.toLowerCase().indexOf(kw) >= 0; });
		if (pv) f = f.filter(function(s) { return s.p === pv; });
		if (tp) f = f.filter(function(s) { return s.ty === tp; });

		if (!f.length) {
			c.innerHTML = '<p class="p-6 text-center text-slate-400 text-sm">Không tìm thấy cửa hàng nào.</p>';
			return;
		}

		c.innerHTML = f.map(function(s) {
			var img = s.img || PH;
			var dl = s.ty.indexOf('dai-ly') >= 0 || s.tn.indexOf('Đại') >= 0;
			var badge = dl ? 'bg-[#f0f5ff] text-[#1e73be] border-[#e0ebff]' : 'bg-amber-50 text-amber-700 border-amber-200';
			var phones = [s.ph, s.hl].filter(Boolean);
			var phonesHtml = phones.map(function(p) { 
				var cleanP = p.trim().replace(/\s+/g, '');
				return '<a href="tel:' + cleanP + '" onclick="event.stopPropagation();" class="inline-flex items-center gap-1.5 hover:text-[#1e73be] transition-colors"><i class="fa-solid fa-phone text-blue-500 text-xs shrink-0"></i> <strong class="text-slate-700 font-bold">' + p.trim() + '</strong></a>'; 
			}).join('');
			var mapUrl = (s.la && s.lo) ? 'https://www.google.com/maps/dir//' + s.la + ',' + s.lo + '/' : 'https://maps.google.com/?q=' + encodeURIComponent(s.t + ', ' + s.a);
			var activeCls = (s.id === activeLocatorStoreId) ? 'bg-slate-50 border-l-4 border-l-[#1e73be]' : '';

			return '<div onclick="window.selectLocatorStore(' + s.id + ')" class="p-4 hover:bg-slate-50/50 cursor-pointer transition-all ' + activeCls + '">' +
				'<div class="flex gap-3.5 items-start">' +
					'<div class="w-24 h-18 sm:w-28 sm:h-20 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-100"><img src="' + img + '" alt="' + s.t + '" class="w-full h-full object-cover" loading="lazy"></div>' +
					'<div class="flex-1 min-w-0">' +
						'<h3 class="font-black text-slate-800 text-xs sm:text-sm uppercase leading-snug group-hover:text-[#1e73be] transition-colors line-clamp-2">' + s.t + '</h3>' +
						'<div class="mt-1.5">' +
							'<span class="inline-block text-[9px] sm:text-[10px] font-bold px-2 py-0.5 rounded border ' + badge + '">' + s.tn + '</span>' +
						'</div>' +
					'</div>' +
				'</div>' +
				'<div class="mt-3 pt-2.5 border-t border-slate-100 space-y-2 text-xs text-slate-500">' +
					'<p class="flex items-start gap-1.5 text-slate-600 leading-relaxed"><i class="fa-solid fa-location-dot text-[#1e73be] mt-0.5 shrink-0"></i> <span>' + s.a + '</span></p>' +
					(phonesHtml ? '<div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-600 mt-1">' + phonesHtml + '</div>' : '') +
					'<div class="grid grid-cols-2 gap-2 mt-3 pt-1">' +
						'<a href="' + mapUrl + '" target="_blank" onclick="event.stopPropagation();" class="border border-emerald-600 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-xl py-2 px-3 text-[11px] font-bold flex items-center justify-center gap-1.5 transition-all">' +
							'<i class="fa-solid fa-paper-plane text-[10px]"></i> Chỉ đường' +
						'</a>' +
						'<a href="' + s.u + '" onclick="event.stopPropagation();" class="btn-detail-brand border rounded-xl py-2 px-3 text-[11px] font-bold flex items-center justify-center gap-1.5 transition-all">' +
							'<i class="fa-solid fa-circle-info text-[10px]"></i> Xem chi tiết' +
						'</a>' +
					'</div>' +
				'</div>' +
			'</div>';
		}).join('');
	}

	// Bind events.
	var si = document.getElementById('locator-search');
	var sp = document.getElementById('locator-province');
	var st = document.getElementById('locator-type');
	if (si) si.addEventListener('input', function() { activeLocatorStoreId = null; renderLocator(); });
	if (sp) sp.addEventListener('change', function() { activeLocatorStoreId = null; renderLocator(); });
	if (st) st.addEventListener('change', function() { activeLocatorStoreId = null; renderLocator(); });

	// Initial render.
	renderCards();
	renderLocator();

	// Select first locator item by default if available.
	if (D.length > 0) {
		var first = D[0];
		window.selectLocatorStore(first.id);
	}
})();
</script>

<?php
get_footer();
