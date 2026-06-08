<?php
/**
 * Home page — Video Review & Customer Testimonials section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

// ── Video settings ──
$video_title    = $data['video_title'] ?? __( 'Video nổi bật', 'spl' );
$video_subtitle = $data['video_subtitle'] ?? __( 'Trải nghiệm thực tế', 'spl' );
$video_url      = $data['video_url'] ?? 'https://www.youtube.com/embed/dQw4w9WgXcQ';
$video_dur      = $data['video_duration'] ?? '04:35';
$video_thumb_id = $data['video_thumbnail'] ?? 0;
$video_thumb    = is_numeric( $video_thumb_id ) ? wp_get_attachment_image_url( $video_thumb_id, 'large' ) : (string) $video_thumb_id;
if ( ! $video_thumb ) {
	$video_thumb = 'https://images.unsplash.com/photo-1595054179361-b0e66d9bb7a3?auto=format&fit=crop&w=1200&q=80';
}
$playlist       = $data['playlist'] ?? [];

// Fallback playlist if empty.
if ( empty( $playlist ) ) {
	$playlist = [
		[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=300&q=80' ],
		[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=300&q=80' ],
		[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=300&q=80' ],
		[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => 'https://images.unsplash.com/photo-1595054179361-b0e66d9bb7a3?auto=format&fit=crop&w=300&q=80' ],
	];
}

// ── Testimonials settings ──
$testi_title    = $data['testimonial_title'] ?? __( 'Cảm nhận khách hàng', 'spl' );
$testi_subtitle = $data['testimonial_subtitle'] ?? __( 'Đánh giá thực tế', 'spl' );
$testimonials   = $data['testimonials'] ?? [];

// Fallback testimonials if empty.
if ( empty( $testimonials ) ) {
	$testimonials = [
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
	];
}
?>
<section class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
	<!-- Video Widget -->
	<div class="lg:col-span-2 bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-premium flex flex-col justify-between">
		<div>
			<div class="flex items-center justify-between mb-4">
				<h3 class="font-extrabold text-base md:text-lg text-slate-900 flex items-center gap-2">
					<?php echo spl_icon( 'bolt', 'w-5 h-5 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $video_title ); ?>
				</h3>
				<span class="text-xs text-slate-400"><?php echo esc_html( $video_subtitle ); ?></span>
			</div>
			<!-- Play trigger -->
			<div onclick="openVideoModal('<?php echo esc_url( $video_url ); ?>')" class="relative rounded-xl overflow-hidden group aspect-video bg-slate-900 cursor-pointer shadow-md">
				<?php if ( $video_thumb ) : ?><img loading="lazy" src="<?php echo esc_url( $video_thumb ); ?>" alt="<?php echo esc_attr( $video_title ); ?>" class="w-full h-full object-cover opacity-80 group-hover:scale-102 transition-transform duration-300"><?php endif; ?>
				<div class="absolute inset-0 bg-slate-900/40 flex items-center justify-center">
					<div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-white text-primary flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform">
						<svg class="w-6 h-6 fill-current ml-1" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					</div>
				</div>
				<span class="absolute bottom-3 right-3 bg-slate-900/70 text-white text-[10px] font-bold px-2 py-0.5 rounded"><?php echo esc_html( $video_dur ); ?></span>
			</div>
		</div>

		<!-- Mini playlist thumbnails -->
		<div class="grid grid-cols-4 gap-2.5 md:gap-3 mt-4">
			<?php foreach ( $playlist as $item ) :
				$p_url = $item['video_url'] ?? '';
				$p_thumb_id = $item['thumbnail'] ?? 0;
				$p_thumb = is_numeric( $p_thumb_id ) ? wp_get_attachment_image_url( $p_thumb_id, 'medium' ) : (string) $p_thumb_id;
				?>
				<div onclick="openVideoModal('<?php echo esc_url( $p_url ); ?>')" class="rounded-lg overflow-hidden border border-slate-200 aspect-video cursor-pointer opacity-80 hover:opacity-100 transition-opacity bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center">
					<?php if ( $p_thumb ) : ?>
						<img loading="lazy" src="<?php echo esc_url( $p_thumb ); ?>" class="w-full h-full object-cover">
					<?php else : ?>
						<svg class="w-4 h-4 fill-current text-white/70" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Testimonials Widget -->
	<div class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-premium flex flex-col justify-between">
		<div>
			<div class="flex items-center justify-between mb-4">
				<h3 class="font-extrabold text-base md:text-lg text-slate-900 flex items-center gap-2">
					<?php echo spl_icon( 'mail', 'w-5 h-5 text-amber-500' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $testi_title ); ?>
				</h3>
				<span class="text-xs text-slate-400"><?php echo esc_html( $testi_subtitle ); ?></span>
			</div>
			
			<!-- Vertical Scroller Container -->
			<div class="h-[280px] overflow-hidden relative" id="testimonial-container" onmouseenter="pauseTestimonial()" onmouseleave="resumeTestimonial()">
				<div class="space-y-4 absolute w-full transition-transform duration-500 ease-out" id="testimonial-scroller" style="transform: translateY(0px);">
					<?php foreach ( $testimonials as $row ) :
						$t_name = $row['name'] ?? '';
						$t_loc  = $row['location'] ?? '';
						$t_avatar = $row['avatar_text'] ?? 'MA';
						$t_rating = isset( $row['rating'] ) ? absint( $row['rating'] ) : 5;
						$t_comment = $row['comment'] ?? '';
						?>
						<div class="border border-slate-50 bg-slate-50/30 p-3.5 rounded-xl">
							<div class="flex items-center gap-3">
								<div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold border border-slate-200 text-xs"><?php echo esc_html( $t_avatar ); ?></div>
								<div>
									<span class="font-bold text-slate-800 text-xs block"><?php echo esc_html( $t_name ); ?></span>
									<span class="text-[10px] text-slate-400 block"><?php echo esc_html( $t_loc ); ?></span>
								</div>
								<div class="ml-auto text-amber-400 text-[10px] flex gap-0.5">
									<?php for ( $i = 0; $i < $t_rating; $i++ ) : ?>
										<svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
									<?php endfor; ?>
								</div>
							</div>
							<p class="text-xs text-slate-500 mt-2 italic leading-relaxed"><?php echo esc_html( $t_comment ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="flex items-center justify-between mt-4 border-t border-slate-100 pt-3">
			<span class="text-xs text-slate-400"><?php esc_html_e( 'Tự động trượt lên', 'spl' ); ?></span>
			<div class="flex gap-1">
				<button onclick="scrollTestimonials(-1)" aria-label="<?php esc_attr_e( 'Trượt lên', 'spl' ); ?>" class="w-7 h-7 rounded-full border border-slate-200 hover:bg-slate-50 text-slate-500 flex items-center justify-center text-xs transition-all focus:outline-none">
					<?php echo spl_icon( 'chevron-down', 'w-3.5 h-3.5 rotate-180' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<button onclick="scrollTestimonials(1)" aria-label="<?php esc_attr_e( 'Trượt xuống', 'spl' ); ?>" class="w-7 h-7 rounded-full border border-slate-200 hover:bg-slate-50 text-slate-500 flex items-center justify-center text-xs transition-all focus:outline-none">
					<?php echo spl_icon( 'chevron-down', 'w-3.5 h-3.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
		</div>
	</div>
</section>

<!-- Simple standard YouTube video modal overlay (Will be triggered by page-home.js) -->
<div id="video-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Trình phát video', 'spl' ); ?>" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100] hidden items-center justify-center p-4" onclick="closeVideoModal()">
	<div class="bg-black w-full max-w-4xl aspect-video rounded-2xl overflow-hidden shadow-2xl relative" onclick="event.stopPropagation()">
		<button onclick="closeVideoModal()" aria-label="<?php esc_attr_e( 'Đóng', 'spl' ); ?>" class="absolute top-4 right-4 z-55 w-10 h-10 rounded-full bg-black/60 hover:bg-black text-white flex items-center justify-center transition-colors focus:outline-none">
			<?php echo spl_icon( 'close', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<iframe id="video-iframe" class="w-full h-full" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	</div>
</div>
