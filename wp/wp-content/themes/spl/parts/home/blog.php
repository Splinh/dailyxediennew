<?php
/**
 * Home page — Blog / Latest News section.
 *
 * Markup matches website/index.html (#blog) + inc/critical.css.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

$label   = $data['label'] ?? __( 'Tin Tức', 'spl' );
$heading = $data['heading'] ?? $data['title'] ?? __( 'Tạp Chí Sức Khỏe', 'spl' );

$posts_query = new \WP_Query( [
	'post_type'           => 'post',
	'posts_per_page'      => 3,
	'orderby'             => 'date',
	'order'               => 'DESC',
	'ignore_sticky_posts' => true,
] );

?>
<section class="section-compact" id="blog">
	<div class="container">
		<div class="section-title reveal">
			<div class="section-title__label">
				<svg class="icon" viewBox="0 0 24 24"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>
				<?php echo esc_html( $label ); ?>
			</div>
			<h2 class="section-title__heading">
				<svg class="section-title__icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
				<?php echo esc_html( $heading ); ?>
			</h2>
			<div class="section-title__line"></div>
		</div>
		<div class="blog-grid">
			<?php
			if ( $posts_query->have_posts() ) :
				while ( $posts_query->have_posts() ) :
					$posts_query->the_post();
					$thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
					if ( ! $thumb_url ) {
						$thumb_url = get_theme_file_uri( 'resources/img/blog-post-hero.png' );
					}
					?>
					<a href="<?php the_permalink(); ?>" class="blog-card reveal" style="text-decoration:none;color:inherit;">
						<div class="blog-card__image">
							<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" />
						</div>
						<div class="blog-card__body">
							<div class="blog-card__date">
								<svg class="icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
								<?php echo esc_html( get_the_date() ); ?>
							</div>
							<h3 class="blog-card__title"><?php the_title(); ?></h3>
							<p class="blog-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
						</div>
					</a>
					<?php
				endwhile;
				wp_reset_postdata();
			else :
				?>
				<p class="text-center"><?php esc_html_e( 'Chưa có bài viết nào.', 'spl' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="reveal" style="text-align:center;margin-top:var(--sp-7);">
			<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/tin-tuc/' ) ); ?>" class="btn btn--outline">
				<?php esc_html_e( 'Xem tất cả bài viết', 'spl' ); ?>
			</a>
		</div>
	</div>
</section>
