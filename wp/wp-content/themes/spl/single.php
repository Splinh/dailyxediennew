<?php
/**
 * The template for displaying all single posts.
 *
 * Matches website/single-post.html (post-* markup).
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id   = get_the_ID();
	$cats      = get_the_category();
	$cat_name  = ! empty( $cats ) ? $cats[0]->name : __( 'Tin tức', 'spl' );
	$hero_url  = get_the_post_thumbnail_url( $post_id, 'full' ) ?: get_theme_file_uri( 'resources/img/blog-post-hero.png' );

	// Author.
	$author_name = get_the_author();
	$author_bio  = get_the_author_meta( 'description' );
	$author_role = $author_bio ? '' : __( 'Chuyên gia xe điện', 'spl' );
	$initials    = '';
	foreach ( array_slice( explode( ' ', trim( $author_name ) ), -2 ) as $w ) {
		$initials .= mb_substr( $w, 0, 1 );
	}
	$initials = mb_strtoupper( $initials ?: 'TP' );

	// Reading time (≈200 words/min).
	$word_count = count( preg_split( '/\s+/', trim( wp_strip_all_tags( get_the_content() ) ) ) );
	$read_time  = max( 1, (int) ceil( $word_count / 200 ) );

	// Views (only if a counter meta exists).
	$views = (int) get_post_meta( $post_id, 'post_views_count', true );

	// Options for social/hotline.
	$hotline     = Helper::getField( 'hotline', 'option' ) ?: '098 750 33 60';
	$hotline_url = 'tel:' . preg_replace( '/\s+/', '', $hotline );
	$fb_url      = Helper::getField( 'facebook_url', 'option' );
	$yt_url      = Helper::getField( 'youtube_url', 'option' );
	?>

	<!-- ===== BREADCRUMB ===== -->
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

	<article class="post-article">
		<div class="container">
			<div class="post-layout">

				<!-- Article Content -->
				<div class="post-content reveal">
					<!-- Hero Image -->
					<div class="post-hero">
						<img src="<?php echo esc_url( $hero_url ); ?>" alt="<?php the_title_attribute(); ?>" loading="eager" />
						<div class="post-hero__overlay">
							<div class="post-hero__category">
								<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
								<?php echo esc_html( $cat_name ); ?>
							</div>
						</div>
					</div>

					<!-- Post Meta -->
					<div class="post-meta">
						<div class="post-meta__author">
							<div class="post-meta__avatar"><?php echo esc_html( $initials ); ?></div>
							<div>
								<strong><?php echo esc_html( $author_name ); ?></strong>
								<span><?php echo esc_html( $author_role ?: wp_trim_words( $author_bio, 6 ) ); ?></span>
							</div>
						</div>
						<div class="post-meta__info">
							<span>
								<svg class="icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
								<?php echo esc_html( get_the_date() ); ?>
							</span>
							<span>
								<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
								<?php echo esc_html( sprintf( __( '%d phút đọc', 'spl' ), $read_time ) ); ?>
							</span>
							<?php if ( $views > 0 ) : ?>
								<span>
									<svg class="icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
									<?php echo esc_html( sprintf( __( '%s lượt xem', 'spl' ), number_format_i18n( $views ) ) ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>

					<!-- Title -->
					<h1 class="post-title"><?php the_title(); ?></h1>

					<!-- Body -->
					<div class="post-body">
						<?php the_content(); ?>
					</div>

					<!-- Share & Tags -->
					<div class="post-footer">
						<div class="post-tags">
							<svg class="icon" viewBox="0 0 24 24"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
							<?php
							$tags = get_the_tags();
							if ( $tags && ! is_wp_error( $tags ) ) {
								foreach ( $tags as $tag ) {
									printf( '<a href="%s">%s</a>', esc_url( get_tag_link( $tag ) ), esc_html( $tag->name ) );
								}
							} else {
								echo '<a href="' . esc_url( get_category_link( $cats[0] ?? 0 ) ) . '">' . esc_html( $cat_name ) . '</a>';
							}
							?>
						</div>
						<div class="post-share">
							<span><?php esc_html_e( 'Chia sẻ:', 'spl' ); ?></span>
							<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode( get_permalink() ); ?>" class="post-share__btn post-share__btn--fb" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Chia sẻ Facebook', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
							</a>
							<a href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode( get_permalink() ); ?>&text=<?php echo rawurlencode( get_the_title() ); ?>" class="post-share__btn post-share__btn--tw" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Chia sẻ Twitter', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
							</a>
							<button class="post-share__btn post-share__btn--copy" aria-label="<?php esc_attr_e( 'Sao chép link', 'spl' ); ?>" id="copy-link-btn" data-url="<?php echo esc_url( get_permalink() ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
							</button>
						</div>
					</div>

					<!-- Author Box -->
					<div class="post-author-box">
						<div class="post-author-box__avatar">
							<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
						</div>
						<div class="post-author-box__info">
							<h4><?php echo esc_html( $author_name ); ?></h4>
							<p><?php echo esc_html( $author_bio ?: __( 'Đội ngũ chuyên gia xe điện của DailyXeDien, chia sẻ kiến thức chọn xe, sử dụng và bảo dưỡng để mang đến trải nghiệm tốt nhất cho khách hàng.', 'spl' ) ); ?></p>
							<div class="post-author-box__social">
								<a href="<?php echo esc_url( $fb_url ?: '#' ); ?>"<?php echo $fb_url ? ' target="_blank" rel="noopener"' : ''; ?>>Facebook</a>
								<a href="<?php echo esc_url( $yt_url ?: '#' ); ?>"<?php echo $yt_url ? ' target="_blank" rel="noopener"' : ''; ?>>Youtube</a>
								<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Website', 'spl' ); ?></a>
							</div>
						</div>
					</div>

					<!-- Related Posts -->
					<?php
					$related = get_posts( [
						'post_type'      => 'post',
						'posts_per_page' => 3,
						'post__not_in'   => [ $post_id ],
						'category__in'   => wp_list_pluck( $cats, 'term_id' ),
						'orderby'        => 'date',
						'order'          => 'DESC',
					] );
					if ( $related ) :
						?>
						<div class="post-related">
							<h3>
								<svg class="icon" viewBox="0 0 24 24"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>
								<?php esc_html_e( 'Bài viết liên quan', 'spl' ); ?>
							</h3>
							<div class="post-related__grid">
								<?php foreach ( $related as $rp ) :
									$rp_thumb = get_the_post_thumbnail_url( $rp->ID, 'medium' ) ?: get_theme_file_uri( 'resources/img/product-herbs.png' );
									?>
									<a href="<?php echo esc_url( get_permalink( $rp ) ); ?>" class="post-related__item">
										<img src="<?php echo esc_url( $rp_thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $rp ) ); ?>" />
										<div>
											<h4><?php echo esc_html( get_the_title( $rp ) ); ?></h4>
											<span><?php echo esc_html( get_the_date( '', $rp ) ); ?></span>
										</div>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
						<?php wp_reset_postdata(); ?>
					<?php endif; ?>
				</div>

				<!-- Sidebar -->
				<aside class="post-sidebar reveal">
					<div class="sidebar-widget sidebar-widget--search">
						<h3><?php esc_html_e( 'Tìm kiếm', 'spl' ); ?></h3>
						<form class="sidebar-search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
							<input type="search" name="s" placeholder="<?php esc_attr_e( 'Tìm bài viết...', 'spl' ); ?>" value="<?php echo get_search_query(); ?>" />
							<button type="submit" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
							</button>
						</form>
					</div>

					<div class="sidebar-widget">
						<h3><?php esc_html_e( 'Danh mục bài viết', 'spl' ); ?></h3>
						<ul class="sidebar-categories">
							<?php
							$sidebar_cats = get_categories( [ 'hide_empty' => false, 'number' => 8 ] );
							foreach ( $sidebar_cats as $sc ) :
								?>
								<li><a href="<?php echo esc_url( get_category_link( $sc ) ); ?>">
									<svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
									<?php echo esc_html( $sc->name ); ?> <span>(<?php echo (int) $sc->count; ?>)</span>
								</a></li>
							<?php endforeach; ?>
						</ul>
					</div>

					<div class="sidebar-widget">
						<h3><?php esc_html_e( 'Bài viết nổi bật', 'spl' ); ?></h3>
						<div class="sidebar-popular">
							<?php
							$popular = get_posts( [
								'post_type'      => 'post',
								'posts_per_page' => 3,
								'post__not_in'   => [ $post_id ],
								'orderby'        => 'comment_count',
								'order'          => 'DESC',
							] );
							foreach ( $popular as $pp ) :
								$pp_thumb = get_the_post_thumbnail_url( $pp->ID, 'thumbnail' ) ?: get_theme_file_uri( 'resources/img/product-flower.png' );
								?>
								<a href="<?php echo esc_url( get_permalink( $pp ) ); ?>" class="sidebar-popular__item">
									<img src="<?php echo esc_url( $pp_thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $pp ) ); ?>" />
									<div>
										<h4><?php echo esc_html( get_the_title( $pp ) ); ?></h4>
										<span><?php echo esc_html( get_the_date( '', $pp ) ); ?></span>
									</div>
								</a>
							<?php endforeach; ?>
							<?php wp_reset_postdata(); ?>
						</div>
					</div>

					<div class="sidebar-widget sidebar-widget--cta">
						<div class="sidebar-cta">
							<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
							<h4><?php esc_html_e( 'Cần tư vấn?', 'spl' ); ?></h4>
							<p><?php esc_html_e( 'Gọi ngay cho chúng tôi để được tư vấn miễn phí về các dòng xe điện.', 'spl' ); ?></p>
							<a href="<?php echo esc_url( $hotline_url ); ?>" class="btn btn--primary btn--sm"><?php echo esc_html( $hotline ); ?></a>
						</div>
					</div>
				</aside>

			</div>
		</div>
	</article>

	<?php
endwhile;

get_footer();
