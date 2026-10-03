<?php
/**
 * The template for displaying `Video Hub`
 * Template Name: Video Hub (Thư viện Video)
 * Template Post Type: page
 *
 * @package SPL
 * @author  SPL
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) {
	the_post();
}

if ( post_password_required() ) {
	echo get_the_password_form();
	get_footer();
	return;
}

// ACF Page Settings
$page_subtitle = ( function_exists( 'get_field' ) ? get_field( 'video_page_subtitle' ) : '' ) ?: 'Khám phá thế giới xe điện Đại Lý Xe Điện qua các thước phim sống động, trải nghiệm thực tế và đánh giá chi tiết.';
$shorts_title  = ( function_exists( 'get_field' ) ? get_field( 'video_shorts_title' ) : '' ) ?: 'Đại Lý Xe Điện Shorts & TikTok Viral ⚡';
$hero_override = function_exists( 'get_field' ) ? get_field( 'video_hero_override' ) : null;
$shop_page_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/san-pham/' ) );

// ── 1. QUERY HERO SPOTLIGHT VIDEOS ──
$hero_main_id = null;
if ( ! empty( $hero_override ) ) {
	$hero_main_id = is_object( $hero_override ) ? (int) $hero_override->ID : (int) $hero_override;
} else {
	$featured_q = new WP_Query( [
		'post_type'      => 'video',
		'posts_per_page' => 1,
		'meta_key'       => 'is_featured',
		'meta_value'     => '1',
		'post_status'    => 'publish',
	] );
	if ( $featured_q->have_posts() ) {
		$hero_main_id = (int) $featured_q->posts[0]->ID;
	}
	wp_reset_postdata();
}

// Fallback to latest video if no featured
if ( ! $hero_main_id ) {
	$latest_q = new WP_Query( [
		'post_type'      => 'video',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	] );
	if ( $latest_q->have_posts() ) {
		$hero_main_id = (int) $latest_q->posts[0]->ID;
	}
	wp_reset_postdata();
}

$hero_main_data = $hero_main_id ? spl_get_video_data( $hero_main_id ) : null;

// Playlist sidebar (4 next recent videos excluding hero main)
$sidebar_videos = [];
$sidebar_args   = [
	'post_type'      => 'video',
	'posts_per_page' => 4,
	'post_status'    => 'publish',
];
if ( $hero_main_id ) {
	$sidebar_args['post__not_in'] = [ $hero_main_id ];
}
$sidebar_q = new WP_Query( $sidebar_args );
if ( $sidebar_q->have_posts() ) {
	foreach ( $sidebar_q->posts as $p ) {
		$sidebar_videos[] = spl_get_video_data( (int) $p->ID );
	}
}
wp_reset_postdata();

// ── 2. QUERY SHORTS / TIKTOK 9:16 VIDEOS ──
$shorts_videos = [];
$shorts_q      = new WP_Query( [
	'post_type'      => 'video',
	'posts_per_page' => 12,
	'post_status'    => 'publish',
	'tax_query'      => [
		'relation' => 'OR',
		[
			'taxonomy' => 'video-cat',
			'field'    => 'slug',
			'terms'    => [ 'tiktok-shorts', 'video-ngan', 'shorts', 'tiktok' ],
		],
	],
] );
if ( $shorts_q->have_posts() ) {
	foreach ( $shorts_q->posts as $p ) {
		$shorts_videos[] = spl_get_video_data( (int) $p->ID );
	}
}
wp_reset_postdata();

// Fallback: Query by meta or check all videos if taxonomy not assigned yet
if ( empty( $shorts_videos ) ) {
	$shorts_q2 = new WP_Query( [
		'post_type'      => 'video',
		'posts_per_page' => 20,
		'post_status'    => 'publish',
	] );
	if ( $shorts_q2->have_posts() ) {
		foreach ( $shorts_q2->posts as $p ) {
			$vdata = spl_get_video_data( (int) $p->ID );
			if ( ! empty( $vdata['orientation'] ) && ( $vdata['orientation'] === 'vertical_9_16' || $vdata['source'] === 'tiktok' ) ) {
				$shorts_videos[] = $vdata;
			}
		}
	}
	wp_reset_postdata();
}

// ── 3. QUERY ALL CATEGORIES & GALLERY VIDEOS ──
$categories = get_terms( [
	'taxonomy'   => 'video-cat',
	'hide_empty' => false,
] );
if ( is_wp_error( $categories ) || ! is_array( $categories ) ) {
	$categories = [];
}

$gallery_q = new WP_Query( [
	'post_type'      => 'video',
	'posts_per_page' => 60,
	'post_status'    => 'publish',
] );
$gallery_videos = [];
if ( $gallery_q->have_posts() ) {
	foreach ( $gallery_q->posts as $p ) {
		$gallery_videos[] = spl_get_video_data( (int) $p->ID );
	}
}
wp_reset_postdata();

// Pin top 3 priority videos requested by user:
// 1. TỔNG QUAN CHỨC NĂNG BẢO HÀNH ĐIỆN TỬ (C3S_6PPHY-I)
// 2. Hướng Dẫn Tải & Cài Đặt App AI EBike (kixDXBEGGcU)
// 3. AI EBIKE – Dấu ấn tại VIETNAM EXCELLENT BRANDS 2026 (_c0keOGRrS8)
$pinned_priority_keys = [
	'C3S_6PPHY-I',
	'kixDXBEGGcU',
	'_c0keOGRrS8',
];

$top_videos       = [];
$remaining_videos = [];

foreach ( $gallery_videos as $vid ) {
	$vid_yt    = $vid['youtube_id'] ?? '';
	$is_pinned = false;
	foreach ( $pinned_priority_keys as $idx => $pin_key ) {
		if ( ( $vid_yt && $vid_yt === $pin_key ) || ( ! empty( $vid['url'] ) && strpos( $vid['url'], $pin_key ) !== false ) ) {
			$top_videos[ $idx ] = $vid;
			$is_pinned          = true;
			break;
		}
	}
	if ( ! $is_pinned ) {
		$remaining_videos[] = $vid;
	}
}

// Fallback: If any pinned video was outside the initial query, fetch explicitly
foreach ( $pinned_priority_keys as $idx => $pin_key ) {
	if ( ! isset( $top_videos[ $idx ] ) ) {
		$pin_q = new WP_Query( [
			'post_type'      => 'video',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => 'link_video',
					'value'   => $pin_key,
					'compare' => 'LIKE',
				],
				[
					'key'     => 'video_url',
					'value'   => $pin_key,
					'compare' => 'LIKE',
				],
			],
		] );
		if ( $pin_q->have_posts() ) {
			$top_videos[ $idx ] = spl_get_video_data( (int) $pin_q->posts[0]->ID );
		}
		wp_reset_postdata();
	}
}

ksort( $top_videos );
$gallery_videos = array_merge( array_values( $top_videos ), $remaining_videos );
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
	<div class="container">
		<nav class="breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<svg class="icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
				<?php esc_html_e( 'Trang chủ', 'spl' ); ?>
			</a>
			<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
			<span class="breadcrumb__current"><?php the_title(); ?></span>
		</nav>
	</div>
</div>

<div class="vh-page">

	<!-- ===== HEADER BANNER ===== -->
	<header class="vh-hero-header">
		<div class="container">
			<div class="vh-header-badge">
				<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
				ĐẠI LÝ XE ĐIỆN MEDIA HUB
			</div>
			<h1 class="vh-header-title">Thư Viện Video <span>Đại Lý Xe Điện</span></h1>
			<p class="vh-header-desc"><?php echo esc_html( $page_subtitle ); ?></p>
		</div>
	</header>

	<div class="container">

		<!-- ===== 1. HERO SPOTLIGHT CINEMA SECTION ===== -->
		<?php if ( ! empty( $hero_main_data ) && ! empty( $hero_main_data['title'] ) ) : ?>
		<section class="vh-spotlight-section">
			<div class="vh-spotlight-grid">
				
				<!-- Main Featured Player Card -->
				<div class="vh-main-player"
					data-video-trigger
					data-source="<?php echo esc_attr( $hero_main_data['source'] ?? 'youtube' ); ?>"
					data-orientation="<?php echo esc_attr( $hero_main_data['orientation'] ?? 'horizontal_16_9' ); ?>"
					data-embed-url="<?php echo esc_attr( $hero_main_data['embed_url'] ?? '' ); ?>"
					data-iframe="<?php echo esc_attr( $hero_main_data['iframe'] ?? '' ); ?>"
					data-title="<?php echo esc_attr( $hero_main_data['title'] ?? '' ); ?>"
					data-prod-url="<?php echo esc_url( $shop_page_url ); ?>"
					data-prod-name="<?php esc_attr_e( 'Xem trang sản phẩm', 'spl' ); ?>">
					
					<div class="vh-main-media">
						<?php if ( ! empty( $hero_main_data['thumb_id'] ) ) : ?>
							<?php
							echo wp_get_attachment_image( $hero_main_data['thumb_id'], 'full', false, [
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'decoding'      => 'async',
								'alt'           => esc_attr( $hero_main_data['title'] ),
							] );
							?>
						<?php elseif ( ! empty( $hero_main_data['thumb_url'] ) ) : ?>
							<img src="<?php echo esc_url( $hero_main_data['thumb_url'] ); ?>" alt="<?php echo esc_attr( $hero_main_data['title'] ); ?>" loading="eager" fetchpriority="high">
						<?php endif; ?>

						<div class="vh-player-overlay">
							<div class="vh-top-tags">
								<span class="vh-badge-live">Tiêu Điểm</span>
								<?php if ( ! empty( $hero_main_data['badge'] ) ) : ?>
									<span class="vh-badge-custom"><?php echo esc_html( $hero_main_data['badge'] ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $hero_main_data['duration'] ) ) : ?>
									<span class="vh-duration-tag"><?php echo esc_html( $hero_main_data['duration'] ); ?></span>
								<?php endif; ?>
							</div>

							<!-- Centered Play Button -->
							<div class="vh-play-center" aria-label="Phát video">
								<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
							</div>
						</div>
					</div>

					<div class="vh-bottom-info">
						<h2 class="vh-spotlight-title"><?php echo esc_html( $hero_main_data['title'] ); ?></h2>
						<div class="vh-spotlight-meta">
							<?php if ( ! empty( $hero_main_data['categories'] ) && is_array( $hero_main_data['categories'] ) ) : ?>
								<span class="vh-cat-name"><?php echo esc_html( $hero_main_data['categories'][0] ); ?></span>
								<span>•</span>
							<?php endif; ?>
							<span><?php echo esc_html( $hero_main_data['date'] ?? '' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Playlist Sidebar -->
				<div class="vh-playlist-side">
					<h3 class="vh-side-heading">
						<svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/></svg>
						Video Nổi Bật Tiếp Theo
					</h3>
					<div class="vh-side-list">
						<?php if ( ! empty( $sidebar_videos ) ) : ?>
							<?php foreach ( $sidebar_videos as $item ) : ?>
								<div class="vh-side-item"
									data-video-trigger
									data-source="<?php echo esc_attr( $item['source'] ?? 'youtube' ); ?>"
									data-orientation="<?php echo esc_attr( $item['orientation'] ?? 'horizontal_16_9' ); ?>"
									data-embed-url="<?php echo esc_attr( $item['embed_url'] ?? '' ); ?>"
									data-iframe="<?php echo esc_attr( $item['iframe'] ?? '' ); ?>"
									data-title="<?php echo esc_attr( $item['title'] ?? '' ); ?>"
									data-prod-url="<?php echo esc_url( $shop_page_url ); ?>"
									data-prod-name="<?php esc_attr_e( 'Xem trang sản phẩm', 'spl' ); ?>">
									<div class="vh-side-thumb">
										<?php if ( ! empty( $item['thumb_url'] ) ) : ?>
											<img src="<?php echo esc_url( $item['thumb_url'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" loading="lazy">
										<?php endif; ?>
										<div class="vh-mini-play">
											<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
										</div>
										<?php if ( ! empty( $item['duration'] ) ) : ?>
											<span class="vh-mini-duration"><?php echo esc_html( $item['duration'] ); ?></span>
										<?php endif; ?>
									</div>
									<div class="vh-side-details">
										<h4 class="vh-side-title"><?php echo esc_html( $item['title'] ); ?></h4>
										<div class="vh-side-meta">
											<span><?php echo esc_html( $item['date'] ?? '' ); ?></span>
											<?php if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) : ?>
												<span>• <?php echo esc_html( $item['categories'][0] ); ?></span>
											<?php endif; ?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p style="color: #94a3b8; font-size: 13px; margin: auto;">Đang cập nhật thêm video mới...</p>
						<?php endif; ?>
					</div>
				</div>

			</div>
		</section>
		<?php endif; ?>

		<!-- ===== 2. TIKTOK & SHORTS 9:16 CAROUSEL SECTION ===== -->
		<?php if ( ! empty( $shorts_videos ) ) : ?>
		<section class="vh-shorts-section">
			<div class="vh-section-head">
				<div class="vh-head-left">
					<div class="vh-tiktok-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.89 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.32 0 .62.06.9.16V9.08a6.34 6.34 0 0 0-.9-.07A6.33 6.33 0 0 0 3.16 15.34a6.33 6.33 0 0 0 6.33 6.33 6.33 6.33 0 0 0 6.33-6.33V8.89a8.28 8.28 0 0 0 3.77 1.09V6.69z"/></svg>
					</div>
					<div>
						<h2 class="vh-head-title"><?php echo esc_html( $shorts_title ); ?></h2>
						<p class="vh-head-sub">Trải nghiệm tính năng xe & điểm tin nhanh 60s</p>
					</div>
				</div>
				<div class="vh-swiper-nav">
					<button class="vh-nav-btn vh-shorts-prev" aria-label="Xem trước">
						<svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
					</button>
					<button class="vh-nav-btn vh-shorts-next" aria-label="Xem tiếp">
						<svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
					</button>
				</div>
			</div>

			<!-- Swiper Container -->
			<div class="swiper vh-shorts-swiper">
				<div class="swiper-wrapper">
					<?php foreach ( $shorts_videos as $short ) : ?>
						<div class="swiper-slide">
							<div class="vh-short-card"
								data-video-trigger
								data-source="<?php echo esc_attr( $short['source'] ?? 'tiktok' ); ?>"
								data-orientation="vertical_9_16"
								data-embed-url="<?php echo esc_attr( $short['embed_url'] ?? '' ); ?>"
								data-iframe="<?php echo esc_attr( $short['iframe'] ?? '' ); ?>"
								data-title="<?php echo esc_attr( $short['title'] ?? '' ); ?>"
								data-prod-url="<?php echo esc_url( $shop_page_url ); ?>"
								data-prod-name="<?php esc_attr_e( 'Xem trang sản phẩm', 'spl' ); ?>">
								
								<?php if ( ! empty( $short['thumb_url'] ) ) : ?>
									<img src="<?php echo esc_url( $short['thumb_url'] ); ?>" alt="<?php echo esc_attr( $short['title'] ); ?>" loading="lazy">
								<?php endif; ?>

								<div class="vh-short-overlay">
									<div class="vh-short-top">
										<?php if ( ! empty( $short['badge'] ) ) : ?>
											<span class="vh-badge-tag"><?php echo esc_html( $short['badge'] ); ?></span>
										<?php else : ?>
											<span class="vh-badge-tag">9:16</span>
										<?php endif; ?>

										<div class="vh-source-badge">
											<?php if ( ( $short['source'] ?? '' ) === 'tiktok' ) : ?>
												<svg viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.89 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.32 0 .62.06.9.16V9.08a6.34 6.34 0 0 0-.9-.07A6.33 6.33 0 0 0 3.16 15.34a6.33 6.33 0 0 0 6.33 6.33 6.33 6.33 0 0 0 6.33-6.33V8.89a8.28 8.28 0 0 0 3.77 1.09V6.69z"/></svg>
											<?php else : ?>
												<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
											<?php endif; ?>
										</div>
									</div>

									<div class="vh-short-play-btn" aria-hidden="true">
										<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
									</div>

									<div class="vh-short-bottom">
										<h3 class="vh-short-title"><?php echo esc_html( $short['title'] ); ?></h3>
										<div class="vh-short-meta">
											<?php if ( ! empty( $short['duration'] ) ) : ?>
												<span class="vh-short-duration">
													<svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
													<?php echo esc_html( $short['duration'] ); ?>
												</span>
											<?php endif; ?>
											<span><?php echo esc_html( $short['date'] ?? '' ); ?></span>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<!-- ===== 3. CATEGORY FILTER TABS & SEARCH BAR ===== -->
		<section class="vh-filter-section">
			<div class="vh-filter-wrapper">
				
				<!-- Category Tabs -->
				<div class="vh-category-tabs">
					<button class="vh-tab-item is-active" data-category="all">
						Tất cả video
						<span class="vh-tab-count"><?php echo count( $gallery_videos ); ?></span>
					</button>
					<?php if ( ! empty( $categories ) && is_array( $categories ) ) : ?>
						<?php foreach ( $categories as $cat ) : ?>
							<?php if ( is_object( $cat ) && isset( $cat->slug, $cat->name ) ) : ?>
								<button class="vh-tab-item" data-category="<?php echo esc_attr( $cat->slug ); ?>">
									<?php echo esc_html( $cat->name ); ?>
									<?php if ( ! empty( $cat->count ) && $cat->count > 0 ) : ?>
										<span class="vh-tab-count"><?php echo esc_html( $cat->count ); ?></span>
									<?php endif; ?>
								</button>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<!-- Search & Ratio Filter -->
				<div class="vh-filter-tools">
					<div class="vh-search-box">
						<svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
						<input type="text" class="vh-search-input" placeholder="Tìm kiếm video, dòng xe...">
					</div>
					<select class="vh-ratio-select" aria-label="Lọc theo định dạng">
						<option value="all">Tất cả định dạng</option>
						<option value="16_9">🎬 Video Ngang (16:9)</option>
						<option value="9_16">📱 Video Dọc (9:16)</option>
					</select>
				</div>

			</div>
		</section>

		<!-- ===== 4. VIDEO GALLERY GRID ===== -->
		<section class="vh-gallery-section">
			<div class="vh-video-grid">
				<?php if ( ! empty( $gallery_videos ) ) : ?>
					<?php foreach ( $gallery_videos as $vid ) : ?>
						<?php
							$cat_slugs_arr = ( ! empty( $vid['category_slugs'] ) && is_array( $vid['category_slugs'] ) ) ? $vid['category_slugs'] : [];
							$cat_slugs_str = implode( ' ', $cat_slugs_arr );
						?>
						<article class="vh-card"
							data-categories="<?php echo esc_attr( $cat_slugs_str ); ?>"
							data-orientation="<?php echo esc_attr( $vid['orientation'] ?? 'horizontal_16_9' ); ?>">
							
							<!-- Thumbnail Area -->
							<div class="vh-card-media"
								data-video-trigger
								data-source="<?php echo esc_attr( $vid['source'] ?? 'youtube' ); ?>"
								data-orientation="<?php echo esc_attr( $vid['orientation'] ?? 'horizontal_16_9' ); ?>"
								data-embed-url="<?php echo esc_attr( $vid['embed_url'] ?? '' ); ?>"
								data-iframe="<?php echo esc_attr( $vid['iframe'] ?? '' ); ?>"
								data-title="<?php echo esc_attr( $vid['title'] ?? '' ); ?>"
								data-prod-url="<?php echo esc_url( $shop_page_url ); ?>"
								data-prod-name="<?php esc_attr_e( 'Xem trang sản phẩm', 'spl' ); ?>">
								
								<?php if ( ! empty( $vid['thumb_url'] ) ) : ?>
									<img src="<?php echo esc_url( $vid['thumb_url'] ); ?>" alt="<?php echo esc_attr( $vid['title'] ); ?>" loading="lazy">
								<?php endif; ?>

								<div class="vh-card-badges">
									<?php if ( ! empty( $vid['categories'] ) && is_array( $vid['categories'] ) ) : ?>
										<span class="vh-badge-cat"><?php echo esc_html( $vid['categories'][0] ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $vid['badge'] ) ) : ?>
										<span class="vh-badge-custom"><?php echo esc_html( $vid['badge'] ); ?></span>
									<?php endif; ?>
								</div>

								<?php if ( ! empty( $vid['duration'] ) ) : ?>
									<span class="vh-card-duration"><?php echo esc_html( $vid['duration'] ); ?></span>
								<?php endif; ?>

								<div class="vh-card-play">
									<div class="vh-play-circle">
										<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
									</div>
								</div>
							</div>

							<!-- Body Area -->
							<div class="vh-card-body">
								<h3 class="vh-card-title"
									data-video-trigger
									data-source="<?php echo esc_attr( $vid['source'] ?? 'youtube' ); ?>"
									data-orientation="<?php echo esc_attr( $vid['orientation'] ?? 'horizontal_16_9' ); ?>"
									data-embed-url="<?php echo esc_attr( $vid['embed_url'] ?? '' ); ?>"
									data-iframe="<?php echo esc_attr( $vid['iframe'] ?? '' ); ?>"
									data-title="<?php echo esc_attr( $vid['title'] ?? '' ); ?>"
									data-prod-url="<?php echo esc_url( $shop_page_url ); ?>"
									data-prod-name="<?php esc_attr_e( 'Xem trang sản phẩm', 'spl' ); ?>">
									<?php echo esc_html( $vid['title'] ); ?>
								</h3>

								<?php if ( ! empty( $vid['excerpt'] ) ) : ?>
									<p class="vh-card-desc"><?php echo esc_html( wp_strip_all_tags( $vid['excerpt'] ) ); ?></p>
								<?php endif; ?>

								<!-- Action Footer: Shop / Product Catalog Link -->
								<div class="vh-related-prod">
									<span class="vh-card-date">
										<svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor" style="opacity: 0.65; margin-right: 4px; vertical-align: -1px;"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
										<?php echo esc_html( $vid['date'] ?? '' ); ?>
									</span>
									<a href="<?php echo esc_url( $shop_page_url ); ?>" class="vh-prod-btn" target="_blank" rel="noopener">
										Xem trang sản phẩm &rarr;
									</a>
								</div>
							</div>

						</article>
					<?php endforeach; ?>
				<?php endif; ?>

				<!-- Empty state when search/filter returns 0 results -->
				<div class="vh-empty-state" style="<?php echo empty( $gallery_videos ) ? 'display: block;' : 'display: none;'; ?>">
					<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
					<h4>Không tìm thấy video phù hợp</h4>
					<p>Vui lòng thử tìm kiếm bằng từ khóa khác hoặc chọn danh mục khác.</p>
				</div>
			</div>
		</section>

	</div>

</div>

<!-- ===== 5. UNIVERSAL SMART LIGHTBOX MODAL ===== -->
<div class="vh-modal" role="dialog" aria-modal="true" aria-label="Trình xem video">
	<div class="vh-modal-backdrop"></div>
	
	<button class="vh-modal-close" aria-label="Đóng video">
		<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
	</button>

	<div class="vh-modal-container is-horizontal">
		<div class="vh-modal-frame">
			<!-- Dynamic Iframe / Video injected here on click -->
		</div>
		<div class="vh-modal-footer">
			<h4 class="vh-modal-title"></h4>
			<a href="<?php echo esc_url( $shop_page_url ); ?>" class="vh-modal-prod-link" target="_blank" rel="noopener">
				Xem trang sản phẩm &rarr;
			</a>
		</div>
	</div>
</div>

<?php
// SEO Structured Data (CollectionPage + VideoObject)
if ( ! empty( $gallery_videos ) ) {
	$schema_videos = [];
	$max_schema    = min( count( $gallery_videos ), 10 );
	for ( $i = 0; $i < $max_schema; $i++ ) {
		$gv = $gallery_videos[ $i ];
		if ( empty( $gv['embed_url'] ) && empty( $gv['url'] ) ) {
			continue;
		}
		$schema_videos[] = [
			'@type'        => 'VideoObject',
			'name'         => $gv['title'],
			'description'  => ! empty( $gv['excerpt'] ) ? $gv['excerpt'] : $gv['title'],
			'thumbnailUrl' => ! empty( $gv['thumb_url'] ) ? [ $gv['thumb_url'] ] : [],
			'embedUrl'     => $gv['embed_url'] ?: $gv['url'],
			'uploadDate'   => get_the_date( 'c', $gv['id'] ) ?: '',
		];
	}

	if ( ! empty( $schema_videos ) ) {
		$json_ld = [
			'@context'        => 'https://schema.org',
			'@type'           => 'CollectionPage',
			'name'            => get_the_title(),
			'description'     => $page_subtitle,
			'url'             => get_permalink(),
			'mainEntity'      => [
				'@type'           => 'ItemList',
				'itemListElement' => array_map( static function ( $item, $idx ) {
					return [
						'@type'    => 'ListItem',
						'position' => $idx + 1,
						'item'     => $item,
					];
				}, $schema_videos, array_keys( $schema_videos ) ),
			],
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}

get_footer();
