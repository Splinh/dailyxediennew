<?php
/**
 * Home page — Video Review & Customer Testimonials section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

/**
 * Extract YouTube video ID from an embed or watch URL.
 *
 * @param string $url YouTube embed/watch URL.
 * @return string Video ID or empty string.
 */
function dxd_yt_id( string $url ): string {
	if ( preg_match( '#(?:embed/|watch\?v=|youtu\.be/)([a-zA-Z0-9_-]{11})#', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * Get YouTube thumbnail URL from a video ID.
 *
 * @param string $video_id YouTube video ID.
 * @param string $quality  Thumbnail quality: hqdefault, maxresdefault, etc.
 * @return string Thumbnail URL.
 */
function dxd_yt_thumb_url( string $video_id, string $quality = 'hqdefault' ): string {
	return $video_id ? "https://img.youtube.com/vi/{$video_id}/{$quality}.jpg" : '';
}

// ── Video settings ──
$video_title    = $data['video_title'] ?? __( 'Video nổi bật', 'spl' );
$video_subtitle = $data['video_subtitle'] ?? __( 'Trải nghiệm thực tế', 'spl' );
$video_url      = $data['video_url'] ?? 'https://www.youtube.com/embed/dQw4w9WgXcQ';
$video_dur      = $data['video_duration'] ?? '';
$video_thumb_id = $data['video_thumbnail'] ?? 0;
$video_thumb    = is_numeric( $video_thumb_id ) && $video_thumb_id ? wp_get_attachment_image_url( (int) $video_thumb_id, 'large' ) : '';
// Auto-generate from YouTube embed URL if no attachment thumbnail.
if ( ! $video_thumb ) {
	$video_thumb = dxd_yt_thumb_url( dxd_yt_id( $video_url ), 'maxresdefault' );
}
$playlist = $data['playlist'] ?? [];

// Fallback playlist if empty.
if ( empty( $playlist ) ) {
	$playlist = [
		[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => '', 'duration' => '04:35' ],
		[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => '', 'duration' => '03:20' ],
		[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => '', 'duration' => '05:10' ],
		[ 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'thumbnail' => '', 'duration' => '02:45' ],
	];
}

// Resolve all playlist thumbnails for JS hydration.
$playlist_resolved = [];
foreach ( $playlist as $item ) {
	$p_url      = $item['video_url'] ?? '';
	$p_thumb_id = $item['thumbnail'] ?? 0;
	$p_thumb    = is_numeric( $p_thumb_id ) && $p_thumb_id ? wp_get_attachment_image_url( (int) $p_thumb_id, 'medium' ) : '';
	$p_thumb_lg = is_numeric( $p_thumb_id ) && $p_thumb_id ? wp_get_attachment_image_url( (int) $p_thumb_id, 'large' ) : '';
	// Auto-generate from YouTube URL if no attachment.
	if ( ! $p_thumb ) {
		$yt_id      = dxd_yt_id( $p_url );
		$p_thumb    = dxd_yt_thumb_url( $yt_id, 'mqdefault' );
		$p_thumb_lg = dxd_yt_thumb_url( $yt_id, 'maxresdefault' );
	}
	$playlist_resolved[] = [
		'url'      => $p_url,
		'thumb'    => $p_thumb,
		'thumbLg'  => $p_thumb_lg,
		'duration' => $item['duration'] ?? '',
		'title'    => $item['title'] ?? '',
	];
}

$playlist_count = count( $playlist_resolved );
$has_slider     = $playlist_count > 4;

// Main video title (from first playlist item or fallback).
$main_video_caption = $playlist_resolved[0]['title'] ?? '';

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
<!-- Playlist data for JS hydration -->
<script type="application/json" id="dxd-playlist-data"><?php echo wp_json_encode( $playlist_resolved ); ?></script>

<section class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 md:mb-16">
	<!-- Video Widget -->
	<div class="lg:col-span-2 bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-premium flex flex-col justify-between">
		<div>
			<div class="flex items-center justify-between mb-4">
				<h3 class="font-extrabold text-base md:text-lg text-slate-900 flex items-center gap-2">
					<?php echo spl_icon( 'bolt', 'w-5 h-5 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $video_title ); ?>
				</h3>
				<span class="text-xs text-slate-400"><?php echo esc_html( $video_subtitle ); ?></span>
			</div>
			<!-- Main video preview — click to open popup -->
			<div id="video-main-trigger" data-video-url="<?php echo esc_url( $video_url ); ?>" onclick="openVideoModal(this.getAttribute('data-video-url'))" class="relative rounded-xl overflow-hidden group aspect-video bg-slate-900 cursor-pointer shadow-md">
				<img id="video-main-thumb" loading="lazy" src="<?php echo esc_url( $video_thumb ); ?>" alt="<?php echo esc_attr( $video_title ); ?>" class="w-full h-full object-cover opacity-80 group-hover:scale-102 transition-transform duration-300 <?php echo $video_thumb ? '' : 'hidden'; ?>">
				<div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent flex items-center justify-center">
					<div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-white/95 text-primary flex items-center justify-center shadow-xl transform group-hover:scale-110 transition-transform backdrop-blur-sm">
						<svg class="w-6 h-6 fill-current ml-1" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					</div>
				</div>
				<?php if ( $main_video_caption ) : ?>
					<p id="video-main-caption" class="absolute bottom-3 left-3 right-16 text-white text-xs md:text-sm font-bold leading-snug drop-shadow-lg line-clamp-2"><?php echo esc_html( $main_video_caption ); ?></p>
				<?php endif; ?>
				<?php if ( $video_dur ) : ?>
					<span id="video-main-duration" class="absolute bottom-3 right-3 bg-slate-900/70 text-white text-[10px] font-bold px-2 py-0.5 rounded"><?php echo esc_html( $video_dur ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<!-- Mini playlist thumbnails (slider when > 4) -->
		<div class="relative mt-3" id="video-playlist-wrapper">
			<?php if ( $has_slider ) : ?>
				<div id="video-playlist-swiper" class="swiper closest-swiper" data-fx-slider>
					<div class="swiper-wrapper" data-swiper-options='{"slidesPerView":4,"spaceBetween":12,"navigation":true,"watchSlidesProgress":true,"breakpoints":{"320":{"slidesPerView":2,"spaceBetween":8},"640":{"slidesPerView":3,"spaceBetween":10},"1024":{"slidesPerView":4,"spaceBetween":12}}}'>
						<?php foreach ( $playlist_resolved as $idx => $p ) : ?>
							<div onclick="selectVideo(<?php echo (int) $idx; ?>)" data-playlist-idx="<?php echo (int) $idx; ?>" class="swiper-slide video-thumb-item h-auto! rounded-lg overflow-hidden border-2 aspect-video cursor-pointer hover:opacity-100 transition-all bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center relative <?php echo 0 === $idx ? 'border-primary opacity-100 ring-2 ring-primary/30' : 'border-slate-200 opacity-70'; ?>">
								<?php if ( $p['thumb'] ) : ?>
									<img loading="lazy" src="<?php echo esc_url( $p['thumb'] ); ?>" alt="" class="w-full h-full object-cover">
								<?php endif; ?>
								<div class="absolute inset-0 bg-slate-900/30 flex items-center justify-center">
									<svg class="w-4 h-4 fill-current text-white/80" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
								</div>
								<?php if ( $p['duration'] ) : ?>
									<span class="absolute bottom-1 right-1 bg-slate-900/70 text-white text-[8px] font-bold px-1.5 py-0.5 rounded"><?php echo esc_html( $p['duration'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					
					<!-- Navigation controls -->
					<div class="swiper-controls">
						<button class="swiper-button swiper-button-prev absolute -left-1 top-1/2 -translate-y-1/2 z-10 size-7 rounded-full bg-white shadow-md border border-slate-200 hover:bg-primary hover:text-white hover:border-primary text-slate-500 flex items-center justify-center transition-all duration-200 focus:outline-none disabled:opacity-30 disabled:pointer-events-none">
							<svg class="w-3 h-3" style="fill: none !important;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
						</button>
						<button class="swiper-button swiper-button-next absolute -right-1 top-1/2 -translate-y-1/2 z-10 size-7 rounded-full bg-white shadow-md border border-slate-200 hover:bg-primary hover:text-white hover:border-primary text-slate-500 flex items-center justify-center transition-all duration-200 focus:outline-none disabled:opacity-30 disabled:pointer-events-none">
							<svg class="w-3 h-3" style="fill: none !important;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
						</button>
					</div>
				</div>
			<?php else : ?>
				<div class="grid grid-cols-4 gap-2.5 md:gap-3">
					<?php foreach ( $playlist_resolved as $idx => $p ) : ?>
						<div onclick="selectVideo(<?php echo (int) $idx; ?>)" data-playlist-idx="<?php echo (int) $idx; ?>" class="video-thumb-item rounded-lg overflow-hidden border-2 aspect-video cursor-pointer hover:opacity-100 transition-all bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center relative <?php echo 0 === $idx ? 'border-primary opacity-100 ring-2 ring-primary/30' : 'border-slate-200 opacity-70'; ?>">
							<?php if ( $p['thumb'] ) : ?>
								<img loading="lazy" src="<?php echo esc_url( $p['thumb'] ); ?>" alt="" class="w-full h-full object-cover">
							<?php endif; ?>
							<div class="absolute inset-0 bg-slate-900/30 flex items-center justify-center">
								<svg class="w-4 h-4 fill-current text-white/80" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
							</div>
							<?php if ( $p['duration'] ) : ?>
								<span class="absolute bottom-1 right-1 bg-slate-900/70 text-white text-[8px] font-bold px-1.5 py-0.5 rounded"><?php echo esc_html( $p['duration'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Testimonials Widget -->
	<div class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-premium flex flex-col justify-between">
		<div class="flex-1 flex flex-col min-h-0">
			<div class="flex items-center justify-between mb-4">
				<h3 class="font-extrabold text-base md:text-lg text-slate-900 flex items-center gap-2">
					<?php echo spl_icon( 'mail', 'w-5 h-5 text-amber-500' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $testi_title ); ?>
				</h3>
				<span class="text-xs text-slate-400"><?php echo esc_html( $testi_subtitle ); ?></span>
			</div>
			
			<!-- Vertical Scroller Container -->
			<div class="h-[240px] lg:h-auto lg:flex-1 min-h-0 overflow-hidden relative" id="testimonial-container" onmouseenter="pauseTestimonial()" onmouseleave="resumeTestimonial()">
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

<!-- Simple standard YouTube video modal overlay (Will be triggered by resources/scripts/home.js) -->
<div id="video-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Trình phát video', 'spl' ); ?>" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100] hidden items-center justify-center p-4" onclick="closeVideoModal()">
	<div class="bg-black w-full max-w-4xl aspect-video rounded-2xl overflow-hidden shadow-2xl relative" onclick="event.stopPropagation()">
		<button onclick="closeVideoModal()" aria-label="<?php esc_attr_e( 'Đóng', 'spl' ); ?>" class="absolute top-4 right-4 z-55 w-10 h-10 rounded-full bg-black/60 hover:bg-black text-white flex items-center justify-center transition-colors focus:outline-none">
			<?php echo spl_icon( 'close', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<iframe id="video-iframe" class="w-full h-full" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
	</div>
</div>
