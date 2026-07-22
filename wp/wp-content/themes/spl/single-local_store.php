<?php
/**
 * Single template for local_store CPT.
 *
 * Matches htmlmau/chi-tiet-daily.html layout using Tailwind.
 * Sections: Breadcrumb → Store Header + Gallery + Description | Sidebar → Other Stores
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();

$sid     = get_the_ID();
$title   = get_the_title();
$address = get_post_meta( $sid, 'localstore_address', true );
$phone   = get_post_meta( $sid, 'localstore_phone', true );
$hotline_store = get_post_meta( $sid, 'localstore_hotline', true );
$email   = get_post_meta( $sid, 'localstore_email', true );
$open_hours = get_post_meta( $sid, 'localstore_open', true );
$brand   = get_post_meta( $sid, 'localstore_brand', true );
$website = get_post_meta( $sid, 'localstore_website', true );
$established = get_post_meta( $sid, 'localstore_established', true );
$exhibit = get_post_meta( $sid, 'localstore_exhibit', true );
$lat     = (float) get_post_meta( $sid, 'localstore_maps_lat', true );
$lng     = (float) get_post_meta( $sid, 'localstore_maps_lng', true );

// Taxonomy.
$store_type_terms = get_the_terms( $sid, 'store_type' );
$state_terms      = get_the_terms( $sid, 'local_store_state' );
$type_name   = ( $store_type_terms && ! is_wp_error( $store_type_terms ) ) ? $store_type_terms[0]->name : '';
$type_slug   = ( $store_type_terms && ! is_wp_error( $store_type_terms ) ) ? $store_type_terms[0]->slug : '';
$province    = ( $state_terms && ! is_wp_error( $state_terms ) ) ? $state_terms[0]->name : '';
$is_dai_ly   = str_contains( strtolower( $type_slug . $type_name ), 'dai-ly' ) || str_contains( strtolower( $type_name ), 'đại lý' );

// Phones array.
$phones = array_filter( array_map( 'trim', array_merge(
	$phone ? explode( ',', $phone ) : [],
	$hotline_store ? [ $hotline_store ] : []
) ) );
$first_phone     = $phones[0] ?? '';
$first_phone_url = 'tel:' . preg_replace( '/\s+/', '', $first_phone );

// Gallery — post thumbnail + content images.
$gallery_images = [];
$thumb = get_the_post_thumbnail_url( $sid, 'large' );
if ( $thumb ) {
	$gallery_images[] = $thumb;
}

// Dealer listing page URL.
$dealer_page = get_pages( [ 'meta_key' => '_wp_page_template', 'meta_value' => 'templates/template-page-daily.php' ] );
$dealer_url  = ! empty( $dealer_page ) ? get_permalink( $dealer_page[0] ) : home_url( '/' );

// Map embed URL.
$map_embed = '';
if ( $lat && $lng ) {
	$map_embed = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.5!2d' . $lng . '!3d' . $lat . '!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2z!5e0!3m2!1svi!2s!4v1';
}
$map_dir_url = ( $lat && $lng ) ? 'https://www.google.com/maps/dir//' . $lat . ',' . $lng . '/' : '#';
?>

<style>
/* Bulletproof styling for brand-blue detail buttons to prevent theme overrides */
#store-detail .btn-detail-brand {
	background-color: #ffffff !important;
	color: #1e73be !important;
	border-color: #1e73be !important;
	transition: all 0.2s ease-in-out !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
	text-decoration: none !important;
}
#store-detail .btn-detail-brand:hover {
	background-color: #1e73be !important;
	color: #ffffff !important;
}
</style>

<!-- BREADCRUMB -->
<div class="bg-white border-b border-slate-100">
	<div class="max-w-7xl mx-auto px-4 py-3">
		<nav class="flex items-center gap-2 text-xs text-slate-400" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-[#1e73be] transition-colors"><?php esc_html_e( 'Trang chủ', 'spl' ); ?></a>
			<svg class="w-2 h-2" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
			<a href="<?php echo esc_url( $dealer_url ); ?>" class="hover:text-[#1e73be] transition-colors"><?php esc_html_e( 'Hệ thống cửa hàng', 'spl' ); ?></a>
			<svg class="w-2 h-2" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
			<span class="text-slate-700 font-semibold"><?php echo esc_html( $title ); ?></span>
		</nav>
	</div>
</div>

<!-- STORE DETAIL -->
<main class="max-w-7xl mx-auto px-4 py-6 md:py-10" id="store-detail">
	<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">

		<!-- LEFT: Gallery + Description (2 cols) -->
		<div class="lg:col-span-2 space-y-6">

			<!-- Store Header -->
			<div class="flex flex-col sm:flex-row sm:items-center gap-4 bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)]">
				<div class="w-16 h-16 bg-[#f0f5ff] rounded-2xl flex items-center justify-center shrink-0">
					<?php echo spl_icon( 'store', 'w-8 h-8 text-[#1e73be]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="flex-1">
					<div class="flex flex-wrap items-center gap-2 mb-1">
						<h1 class="text-lg md:text-xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h1>
						<?php if ( $type_name ) : ?>
							<span class="text-[9px] font-bold uppercase tracking-wider text-white <?php echo $is_dai_ly ? 'bg-emerald-500' : 'bg-[#1e73be]'; ?> px-2 py-0.5 rounded-md">
								<?php echo esc_html( $type_name ); ?>
							</span>
						<?php endif; ?>
					</div>
					<div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
						<?php if ( $province ) : ?>
							<span class="flex items-center gap-1">
								<?php echo spl_icon( 'map-pin', 'w-3.5 h-3.5 text-[#1e73be]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php echo esc_html( $province ); ?>
							</span>
						<?php endif; ?>
						<span class="flex items-center gap-0.5 text-amber-400">
							<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
							<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
							<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
							<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
							<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" style="clip-path: polygon(0 0, 50% 0, 50% 100%, 0% 100%);"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
							<span class="text-slate-400 ml-1">4.8 (52 đánh giá)</span>
						</span>
					</div>
				</div>
				<div class="flex gap-2 shrink-0">
					<?php if ( $first_phone ) : ?>
						<a href="<?php echo esc_url( $first_phone_url ); ?>" class="w-10 h-10 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-600 flex items-center justify-center transition-colors" title="<?php esc_attr_e( 'Gọi ngay', 'spl' ); ?>">
							<?php echo spl_icon( 'phone', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( $map_dir_url ); ?>" target="_blank" class="w-10 h-10 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 flex items-center justify-center transition-colors" title="<?php esc_attr_e( 'Chỉ đường', 'spl' ); ?>">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
					</a>
					<button class="w-10 h-10 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 flex items-center justify-center transition-colors" title="<?php esc_attr_e( 'Chia sẻ', 'spl' ); ?>" onclick="if(navigator.share){navigator.share({title:document.title,url:window.location.href});}else{navigator.clipboard.writeText(window.location.href);alert('Đã copy link chia sẻ!');}">
						<?php echo spl_icon( 'share', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>
			</div>

			<!-- Featured Image -->
			<?php if ( false && $thumb ) : // Temporarily hidden ?>
			<div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)]">
				<div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
					<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="w-full h-full object-cover" loading="eager" />
				</div>
			</div>
			<?php endif; ?>

			<!-- Store Description -->
			<div class="bg-white border border-slate-100 rounded-2xl p-5 md:p-7 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)]">
				<h2 class="text-base md:text-lg font-black text-slate-900 mb-4 flex items-center gap-2">
					<svg class="w-4 h-4 text-[#1e73be]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
					<?php esc_html_e( 'Giới thiệu cửa hàng', 'spl' ); ?>
				</h2>
				<div class="text-sm text-slate-600 leading-relaxed space-y-3 prose max-w-none">
					<?php the_content(); ?>
				</div>
			</div>

			<!-- Map Embed -->
			<?php if ( $map_embed ) : ?>
			<div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)]">
				<h2 class="text-base font-black text-slate-900 p-5 pb-0 flex items-center gap-2">
					<svg class="w-4 h-4 text-[#1e73be]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
					<?php esc_html_e( 'Bản đồ', 'spl' ); ?>
				</h2>
				<div class="p-4">
					<div class="rounded-xl overflow-hidden border border-slate-200">
						<iframe src="<?php echo esc_url( $map_embed ); ?>" style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="w-full h-64 md:h-96 rounded-xl"></iframe>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<!-- RIGHT: Contact Info Sidebar -->
		<div class="space-y-5">
			<!-- Contact Card -->
			<div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] sticky top-[85px]">
				<h3 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
					<?php echo spl_icon( 'user', 'w-4 h-4 text-[#1e73be]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Thông tin liên hệ', 'spl' ); ?>
				</h3>
				<div class="space-y-3.5">
					<?php if ( $address ) : ?>
					<div class="flex items-start gap-3">
						<div class="w-8 h-8 rounded-lg bg-[#f0f5ff] text-[#1e73be] flex items-center justify-center shrink-0 mt-0.5">
							<?php echo spl_icon( 'map-pin', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div>
							<p class="text-[10px] font-bold text-slate-400 uppercase"><?php esc_html_e( 'Địa chỉ', 'spl' ); ?></p>
							<p class="text-xs text-slate-700 leading-relaxed"><?php echo esc_html( $address ); ?></p>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( $phones ) : ?>
					<div class="flex items-start gap-3">
						<div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center shrink-0 mt-0.5">
							<?php echo spl_icon( 'phone', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div>
							<p class="text-[10px] font-bold text-slate-400 uppercase"><?php esc_html_e( 'Điện thoại', 'spl' ); ?></p>
							<?php foreach ( $phones as $p ) : ?>
								<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $p ) ); ?>" class="text-xs font-bold text-slate-800 hover:text-[#1e73be] transition-colors block">
									<?php echo esc_html( $p ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>

					<div class="flex items-start gap-3">
						<div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center shrink-0 mt-0.5">
							<?php echo spl_icon( 'clock', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div>
							<p class="text-[10px] font-bold text-slate-400 uppercase"><?php esc_html_e( 'Giờ mở cửa', 'spl' ); ?></p>
							<p class="text-xs text-slate-700"><?php echo esc_html( $open_hours ?: '7:30 – 18:00 (Thứ 2 – Chủ nhật)' ); ?></p>
							<span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 mt-0.5"><span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> <?php esc_html_e( 'Đang mở cửa', 'spl' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Action buttons -->
				<div class="grid grid-cols-2 gap-2.5 mt-5">
					<?php if ( $first_phone ) : ?>
					<a href="<?php echo esc_url( $first_phone_url ); ?>" class="flex items-center justify-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs py-3 rounded-xl transition-colors shadow-sm">
						<?php echo spl_icon( 'phone', 'w-3.5 h-3.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Gọi ngay', 'spl' ); ?>
					</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( $map_dir_url ); ?>" target="_blank" class="flex items-center justify-center gap-1.5 bg-[#1e73be] hover:bg-[#165da0] text-white font-bold text-xs py-3 rounded-xl transition-colors shadow-sm">
						<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
						<?php esc_html_e( 'Chỉ đường', 'spl' ); ?>
					</a>
				</div>
				<?php if ( $first_phone ) : 
					$zalo_phone = preg_replace( '/\s+/', '', $first_phone );
					?>
				<a href="https://zalo.me/<?php echo esc_attr( $zalo_phone ); ?>" target="_blank" class="flex items-center justify-center gap-2 mt-2.5 bg-blue-500 hover:bg-blue-600 text-white font-bold text-xs py-3 rounded-xl transition-colors shadow-sm">
					<?php echo spl_icon( 'message-circle', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Nhắn tin Zalo', 'spl' ); ?>
				</a>
				<?php endif; ?>
			</div>

			<!-- Quick Stats -->
			<div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)]">
				<h3 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
					<?php echo spl_icon( 'file-text', 'w-4 h-4 text-[#1e73be]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Thông tin đại lý', 'spl' ); ?>
				</h3>
				<div class="space-y-3">
					<?php if ( $type_name ) : ?>
					<div class="flex items-center justify-between text-xs">
						<span class="text-slate-500"><?php esc_html_e( 'Loại cửa hàng', 'spl' ); ?></span>
						<span class="font-bold <?php echo $is_dai_ly ? 'text-emerald-600 bg-emerald-50' : 'text-[#1e73be] bg-[#f0f5ff]'; ?> px-2 py-0.5 rounded-md"><?php echo esc_html( $type_name ); ?></span>
					</div>
					<?php endif; ?>
					<?php if ( $province ) : ?>
					<div class="flex items-center justify-between text-xs border-t border-slate-50 pt-3">
						<span class="text-slate-500"><?php esc_html_e( 'Khu vực', 'spl' ); ?></span>
						<span class="font-bold text-slate-700"><?php echo esc_html( $province ); ?></span>
					</div>
					<?php endif; ?>
					<?php if ( $established ) : ?>
					<div class="flex items-center justify-between text-xs border-t border-slate-50 pt-3">
						<span class="text-slate-500"><?php esc_html_e( 'Hoạt động từ', 'spl' ); ?></span>
						<span class="font-bold text-slate-700"><?php echo esc_html( $established ); ?></span>
					</div>
					<?php endif; ?>
					<?php if ( $exhibit ) : ?>
					<div class="flex items-center justify-between text-xs border-t border-slate-50 pt-3">
						<span class="text-slate-500"><?php esc_html_e( 'Xe đang trưng bày', 'spl' ); ?></span>
						<span class="font-bold text-slate-700"><?php echo esc_html( $exhibit ); ?></span>
					</div>
					<?php endif; ?>
					<?php if ( $brand ) : ?>
					<div class="flex items-center justify-between text-xs border-t border-slate-50 pt-3">
						<span class="text-slate-500"><?php esc_html_e( 'Thương hiệu chính', 'spl' ); ?></span>
						<span class="font-bold text-primary"><?php echo esc_html( $brand ); ?></span>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Back to all stores -->
			<a href="<?php echo esc_url( $dealer_url ); ?>" class="flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-3 rounded-xl transition-colors">
				<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
				<?php esc_html_e( 'Xem tất cả cửa hàng', 'spl' ); ?>
			</a>
		</div>
	</div>

	<!-- OTHER STORES (compact, matching homepage style) -->
	<?php
	$all_stores = function_exists( 'dxd_dealer_get_stores' ) ? dxd_dealer_get_stores() : [];
	// Filter out current store, then pick 6 random.
	$others = array_values( array_filter( $all_stores, function ( $s ) use ( $sid ) {
		return $s['id'] !== $sid;
	} ) );
	if ( $others ) :
		shuffle( $others );
		$others = array_slice( $others, 0, 6 );
	?>
	<section class="mt-12 md:mt-16 border-t border-slate-100 pt-10">
		<div class="flex items-center gap-3 mb-6">
			<span class="w-1.5 h-6 bg-[#1e73be] rounded-full"></span>
			<h2 class="text-xl font-black text-slate-900 tracking-tight"><?php esc_html_e( 'Cửa hàng khác', 'spl' ); ?></h2>
		</div>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
			<?php foreach ( $others as $o ) :
				$o_dl  = str_contains( $o['ty'], 'dai-ly' ) || str_contains( $o['tn'], 'Đại' );
				$o_tag = $o_dl ? 'bg-emerald-500' : 'bg-[#1e73be]';
				$img   = $o['img'] ?: "data:image/svg+xml," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 140"><rect fill="#f1f5f9" width="200" height="140"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#94a3b8" font-size="14">DXD</text></svg>');
				$dir_url = ( $o['la'] && $o['lo'] ) ? 'https://www.google.com/maps/dir//' . $o['la'] . ',' . $o['lo'] . '/' : 'https://maps.google.com/?q=' . rawurlencode( $o['t'] . ', ' . $o['a'] );
			?>
				<div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] group hover:shadow-[0_20px_40px_-4px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
					<div>
						<!-- Store Image 4:3 -->
						<div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
							<a href="<?php echo esc_url( $o['u'] ); ?>" class="block h-full w-full">
								<img loading="lazy" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $o['t'] ); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
							</a>
							<?php if ( $o['tn'] ) : ?>
								<span class="absolute top-2.5 left-2.5 <?php echo esc_attr( $o_tag ); ?> text-white font-bold text-[9px] px-2 py-0.5 rounded-md uppercase"><?php echo esc_html( $o['tn'] ); ?></span>
							<?php endif; ?>
						</div>
						<!-- Card Content -->
						<div class="p-5 space-y-4">
							<div>
								<a href="<?php echo esc_url( $o['u'] ); ?>" class="block">
									<h3 class="font-bold text-slate-800 text-sm leading-snug group-hover:text-[#1e73be] transition-colors line-clamp-2"><?php echo esc_html( $o['t'] ); ?></h3>
								</a>
							</div>
						</div>
					</div>
					<!-- Actions & Address -->
					<div class="p-5 pt-0 space-y-3.5">
						<div class="grid grid-cols-2 gap-2">
							<a href="<?php echo esc_url( $dir_url ); ?>" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs py-2.5 rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5">
								<svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
								<?php esc_html_e( 'Chỉ đường', 'spl' ); ?>
							</a>
							<a href="<?php echo esc_url( $o['u'] ); ?>" class="btn-detail-brand border active:scale-95 font-bold text-xs py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition-all duration-200">
								<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
								<?php esc_html_e( 'Chi tiết', 'spl' ); ?>
							</a>
						</div>
						<p class="text-[10px] text-slate-400 flex items-start gap-1.5 leading-relaxed line-clamp-2">
							<svg class="w-3 h-3 text-[#1e73be] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
							<span><?php echo esc_html( $o['a'] ); ?></span>
						</p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>
</main>

<?php
get_footer();
