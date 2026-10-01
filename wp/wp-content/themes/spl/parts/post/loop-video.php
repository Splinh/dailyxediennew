<?php
/**
 * Loop template for Video items on Homepage & Archives
 *
 * @package SPL
 * @author  SPL
 */

defined( 'ABSPATH' ) || exit;

global $post;

$post_id   = $post->ID;
$title     = $args['title'] ?? get_the_title( $post_id );
$title_tag = $args['title_tag'] ?? 'h3';
$ratio     = $args['ratio'] ?? 'aspect-16-9';

// Lấy toàn bộ dữ liệu video chuẩn hoá
$video_data = function_exists( 'spl_get_video_data' ) ? spl_get_video_data( $post_id ) : [];

$link_video  = $video_data['url'] ?? ( function_exists( 'get_field' ) ? get_field( 'link_video', $post_id ) : '' );
$thumb_url   = $video_data['thumb_url'] ?? '';
$source      = $video_data['source'] ?? 'youtube';
$duration    = $video_data['duration'] ?? '';
$badge       = $video_data['badge'] ?? '';
$orientation = $video_data['orientation'] ?? 'horizontal_16_9';
$embed_url   = $video_data['embed_url'] ?? '';
$iframe      = $video_data['iframe'] ?? '';

if ( empty( $thumb_url ) ) {
	$thumb_url = get_the_post_thumbnail_url( $post_id, 'large' ) ?: ( defined( 'THEME_URL' ) ? THEME_URL . 'assets/img/placeholder.png' : '' );
}
?>

<div class="item video-item vh-home-card"
	data-video-trigger
	data-source="<?php echo esc_attr( $source ); ?>"
	data-orientation="<?php echo esc_attr( $orientation ); ?>"
	data-embed-url="<?php echo esc_attr( $embed_url ); ?>"
	data-iframe="<?php echo esc_attr( $iframe ); ?>"
	data-title="<?php echo esc_attr( $title ); ?>">

	<div class="cover vh-home-cover play-video" data-youtube="<?php echo esc_url( $link_video ); ?>">
		<span class="res <?php echo esc_attr( $ratio ); ?>">
			<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" width="480" height="270" loading="lazy" decoding="async" class="vh-cover-img">
			
			<!-- Video Overlay with Play Button & Badges -->
			<div class="vh-cover-overlay">
				<!-- Top Badges -->
				<div class="vh-overlay-top">
					<?php if ( ! empty( $badge ) ) : ?>
						<span class="vh-badge-tag"><?php echo esc_html( $badge ); ?></span>
					<?php else : ?>
						<span class="vh-badge-tag">
							<svg class="vh-cam-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
							Video
						</span>
					<?php endif; ?>

					<?php if ( $source === 'tiktok' ) : ?>
						<span class="vh-source-pill tiktok">
							<svg viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.89 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.32 0 .62.06.9.16V9.08a6.34 6.34 0 0 0-.9-.07A6.33 6.33 0 0 0 3.16 15.34a6.33 6.33 0 0 0 6.33 6.33 6.33 6.33 0 0 0 6.33-6.33V8.89a8.28 8.28 0 0 0 3.77 1.09V6.69z"/></svg>
							TikTok
						</span>
					<?php else : ?>
						<span class="vh-source-pill youtube">
							<svg viewBox="0 0 24 24"><path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/></svg>
							YouTube
						</span>
					<?php endif; ?>
				</div>

				<!-- Centered Play Button -->
				<div class="vh-center-play-btn" aria-hidden="true">
					<div class="vh-play-pulse"></div>
					<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
				</div>

				<!-- Bottom Duration -->
				<?php if ( ! empty( $duration ) ) : ?>
					<div class="vh-duration-tag">
						<svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
						<?php echo esc_html( $duration ); ?>
					</div>
				<?php endif; ?>
			</div>
		</span>
	</div>

	<div class="content">
		<<?php echo tag_escape( $title_tag ); ?> class="title">
			<?php if ( $link_video ) : ?>
				<a class="link-cover play-video" href="#" data-youtube="<?php echo esc_url( $link_video ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
					<?php echo esc_html( $title ); ?>
				</a>
			<?php else : ?>
				<?php echo esc_html( $title ); ?>
			<?php endif; ?>
		</<?php echo tag_escape( $title_tag ); ?>>
	</div>
</div>
