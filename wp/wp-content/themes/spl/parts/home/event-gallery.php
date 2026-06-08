<?php
/**
 * Home page — Event Gallery with Lightbox section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];
$title = $data['title'] ?? __( 'Hình ảnh sự kiện', 'spl' );
$subtitle = $data['subtitle'] ?? __( 'Hoạt động tại cửa hàng', 'spl' );
$gallery = $data['gallery'] ?? [];

// Default fallback events if empty.
if ( empty( $gallery ) ) {
	$gallery = [
		[ 'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=400&q=80', 'caption' => __( 'Khai trương đại lý mới', 'spl' ) ],
		[ 'image' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=400&q=80', 'caption' => __( 'Tri ân khách hàng', 'spl' ) ],
		[ 'image' => 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=400&q=80', 'caption' => __( 'Lái thử xe điện', 'spl' ) ],
		[ 'image' => 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?auto=format&fit=crop&w=400&q=80', 'caption' => __( 'Bảo dưỡng miễn phí', 'spl' ) ],
		[ 'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?auto=format&fit=crop&w=400&q=80', 'caption' => __( 'Ngày hội công nghệ', 'spl' ) ],
		[ 'image' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=400&q=80', 'caption' => __( 'Chuyển giao công nghệ', 'spl' ) ],
	];
}
?>
<section class="max-w-7xl mx-auto px-4 mb-16">
	<div class="flex items-center justify-between mb-8">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-primary rounded-full"></span>
			<h2 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<span class="text-sm font-semibold text-slate-400"><?php echo esc_html( $subtitle ); ?></span>
	</div>

	<!-- Lưới ảnh 6 cột -->
	<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 md:gap-6">
		<?php foreach ( $gallery as $index => $item ) :
			$img_id = $item['image'] ?? 0;
			$img_url = is_numeric( $img_id ) ? wp_get_attachment_image_url( $img_id, 'large' ) : (string) $img_id;
			$caption = $item['caption'] ?? '';
			?>
			<div onclick="openLightbox(<?php echo (int) $index; ?>)" class="bg-white border border-slate-100 p-2 rounded-2xl shadow-premium hover:shadow-hover-card transition-all duration-300 group cursor-pointer" data-lightbox-src="<?php echo esc_url( $img_url ); ?>" data-lightbox-cap="<?php echo esc_attr( $caption ); ?>">
				<div class="rounded-xl overflow-hidden aspect-[4/3] relative">
					<?php if ( $img_url ) : ?>
						<img loading="lazy" src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $caption ); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
					<?php else : ?>
						<div class="w-full h-full bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center text-primary">
							<?php echo spl_icon( 'map-pin', 'w-8 h-8 opacity-60' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>
					<div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
						<svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
					</div>
				</div>
				<p class="text-[11px] md:text-xs font-bold text-slate-700 mt-2 text-center truncate px-1"><?php echo esc_html( $caption ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<!-- Lightbox Modal Viewer -->
<div id="lightbox-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Xem ảnh sự kiện', 'spl' ); ?>" class="fixed inset-0 bg-slate-950/90 backdrop-blur-md z-[100] hidden items-center justify-center p-4" onclick="closeLightbox()">
	<button onclick="closeLightbox()" aria-label="<?php esc_attr_e( 'Đóng', 'spl' ); ?>" class="absolute top-4 right-4 z-55 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors focus:outline-none">
		<?php echo spl_icon( 'close', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
	<button onclick="navigateLightbox(-1); event.stopPropagation();" aria-label="<?php esc_attr_e( 'Ảnh trước', 'spl' ); ?>" class="absolute left-4 top-1/2 -translate-y-1/2 z-55 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all focus:outline-none">
		<?php echo spl_icon( 'chevron-left', 'w-6 h-6' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
	<button onclick="navigateLightbox(1); event.stopPropagation();" aria-label="<?php esc_attr_e( 'Ảnh tiếp theo', 'spl' ); ?>" class="absolute right-4 top-1/2 -translate-y-1/2 z-55 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all focus:outline-none">
		<?php echo spl_icon( 'chevron-right', 'w-6 h-6' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
	
	<div class="max-w-4xl text-center relative" onclick="event.stopPropagation()">
		<img id="lightbox-img" src="" alt="" class="max-w-full max-h-[75vh] object-contain rounded-xl shadow-2xl">
		<p id="lightbox-caption" class="text-white text-sm font-bold mt-4 tracking-wide"></p>
	</div>
</div>
