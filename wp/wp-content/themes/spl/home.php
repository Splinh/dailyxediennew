<?php
/**
 * The blog / posts listing template (home.php).
 *
 * Used when "Tin Tức" page is set as the posts page.
 * Matches the static design: archive header + featured post + grid with sidebar.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();

/**
 * Fallback image when a post has no featured thumbnail.
 *
 * @param int $index Card index, used to rotate through the demo images.
 * @return string Image URL.
 */
function spl_news_fallback_img( int $index = 0 ): string {
	$imgs = [
		'product-herbs.png',
		'product-tea.png',
		'product-oil.png',
		'product-flower.png',
		'product-powder.png',
		'product-thien-nien-kien.png',
	];
	return get_theme_file_uri( 'resources/img/' . $imgs[ $index % count( $imgs ) ] );
}

$is_first_page = ! is_paged();
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
			<span class="breadcrumb__current"><?php single_post_title( '' ); ?></span>
		</nav>
	</div>
</div>

<!-- Archive Header -->
<section class="archive-header">
	<div class="container">
		<div class="archive-header__content reveal">
			<div class="archive-header__icon">
				<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>
			</div>
			<div>
				<h1 class="archive-header__title"><?php esc_html_e( 'Tin Tức & Kiến Thức Sức Khỏe', 'spl' ); ?></h1>
				<p class="archive-header__desc"><?php esc_html_e( 'Cập nhật những bài viết mới nhất về thảo dược, bài thuốc nam, mẹo chăm sóc sức khỏe từ đội ngũ chuyên gia của chúng tôi.', 'spl' ); ?></p>
			</div>
		</div>
	</div>
</section>

<?php if ( have_posts() ) : ?>

	<?php
	// Featured post = first post on page 1 only.
	if ( $is_first_page ) :
		the_post();
		$cats      = get_the_category();
		$cat_name  = ! empty( $cats ) ? $cats[0]->name : __( 'Tin Tức', 'spl' );
		$feat_img  = get_the_post_thumbnail_url( get_the_ID(), 'large' ) ?: get_theme_file_uri( 'resources/img/blog-post-hero.png' );
		$read_time = max( 1, (int) round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) );
		?>
		<!-- Featured Post -->
		<section class="news-featured">
			<div class="container">
				<a href="<?php the_permalink(); ?>" class="news-featured__card reveal">
					<div class="news-featured__image">
						<img src="<?php echo esc_url( $feat_img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="eager" />
						<span class="news-featured__badge"><?php esc_html_e( 'Nổi bật', 'spl' ); ?></span>
					</div>
					<div class="news-featured__content">
						<div class="news-featured__meta">
							<span class="news-featured__cat">
								<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
								<?php echo esc_html( $cat_name ); ?>
							</span>
							<span>
								<svg class="icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
								<?php echo esc_html( get_the_date() ); ?>
							</span>
							<span>
								<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
								<?php echo esc_html( sprintf( /* translators: %d: minutes */ __( '%d phút đọc', 'spl' ), $read_time ) ); ?>
							</span>
						</div>
						<h2><?php the_title(); ?></h2>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 40 ) ); ?></p>
						<span class="news-featured__readmore">
							<?php esc_html_e( 'Đọc tiếp', 'spl' ); ?>
							<svg class="icon" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
						</span>
					</div>
				</a>
			</div>
		</section>
	<?php endif; ?>

	<!-- News Grid + Sidebar -->
	<section class="news-archive">
		<div class="container">
			<div class="news-layout">

				<!-- Articles Grid -->
				<div class="news-grid-area">
					<div class="news-grid">
						<?php
						$card_index = 0;
						while ( have_posts() ) :
							the_post();
							$cats     = get_the_category();
							$cat_name = ! empty( $cats ) ? $cats[0]->name : __( 'Tin Tức', 'spl' );
							$card_img = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' ) ?: spl_news_fallback_img( $card_index );
							?>
							<a href="<?php the_permalink(); ?>" class="news-card reveal">
								<div class="news-card__image">
									<img src="<?php echo esc_url( $card_img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
									<span class="news-card__cat"><?php echo esc_html( $cat_name ); ?></span>
								</div>
								<div class="news-card__body">
									<div class="news-card__meta">
										<span><svg class="icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?php echo esc_html( get_the_date() ); ?></span>
									</div>
									<h3><?php the_title(); ?></h3>
									<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
								</div>
							</a>
							<?php
							++$card_index;
						endwhile;
						?>
					</div>

					<?php
					// Pagination.
					the_posts_pagination( [
						'prev_text' => '<svg class="icon" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>',
						'next_text' => '<svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>',
					] );
					?>
				</div>

				<!-- Sidebar -->
				<aside class="post-sidebar reveal">

					<!-- Search -->
					<div class="sidebar-widget sidebar-widget--search">
						<h3><?php esc_html_e( 'Tìm kiếm bài viết', 'spl' ); ?></h3>
						<form class="sidebar-search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<input type="search" name="s" placeholder="<?php esc_attr_e( 'Tìm bài viết...', 'spl' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" />
							<button type="submit" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
							</button>
						</form>
					</div>

					<!-- Categories -->
					<?php
					$post_cats = get_categories( [ 'hide_empty' => true ] );
					if ( ! empty( $post_cats ) ) :
						?>
						<div class="sidebar-widget">
							<h3><?php esc_html_e( 'Danh mục bài viết', 'spl' ); ?></h3>
							<ul class="sidebar-categories">
								<?php foreach ( $post_cats as $cat ) : ?>
									<li><a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
										<svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
										<?php echo esc_html( $cat->name ); ?> <span>(<?php echo esc_html( (string) $cat->count ); ?>)</span>
									</a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<!-- Popular posts -->
					<?php
					$popular = get_posts( [
						'numberposts'      => 3,
						'orderby'          => 'comment_count',
						'suppress_filters' => false,
					] );
					if ( ! empty( $popular ) ) :
						?>
						<div class="sidebar-widget">
							<h3><?php esc_html_e( 'Bài viết nổi bật', 'spl' ); ?></h3>
							<div class="sidebar-popular">
								<?php
								foreach ( $popular as $i => $pop ) :
									$pop_img = get_the_post_thumbnail_url( $pop->ID, 'thumbnail' ) ?: spl_news_fallback_img( $i );
									?>
									<a href="<?php echo esc_url( get_permalink( $pop->ID ) ); ?>" class="sidebar-popular__item">
										<img src="<?php echo esc_url( $pop_img ); ?>" alt="<?php echo esc_attr( get_the_title( $pop->ID ) ); ?>" />
										<div>
											<h4><?php echo esc_html( get_the_title( $pop->ID ) ); ?></h4>
											<span><?php echo esc_html( get_the_date( '', $pop->ID ) ); ?></span>
										</div>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- CTA -->
					<?php $hotline = Helper::getField( 'hotline', 'option' ) ?: '098 750 33 60'; ?>
					<div class="sidebar-widget sidebar-widget--cta">
						<div class="sidebar-cta">
							<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
							<h4><?php esc_html_e( 'Cần tư vấn?', 'spl' ); ?></h4>
							<p><?php esc_html_e( 'Gọi ngay cho chúng tôi để được tư vấn miễn phí về sức khỏe và thảo dược.', 'spl' ); ?></p>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $hotline ) ); ?>" class="btn btn--primary btn--sm"><?php echo esc_html( $hotline ); ?></a>
						</div>
					</div>

				</aside>

			</div>
		</div>
	</section>

<?php else : ?>
	<section class="news-archive">
		<div class="container">
			<div class="archive-no-products" style="text-align:center; padding:var(--sp-11) 0;">
				<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/></svg>
				<p><?php esc_html_e( 'Chưa có bài viết nào.', 'spl' ); ?></p>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php get_footer(); ?>
