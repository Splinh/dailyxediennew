<?php
/**
 * 404 — Page not found.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();

$is_woo    = Helper::isWoocommerceActive();
$shop_url  = $is_woo ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
$home_url  = home_url( '/' );
?>

<section class="error-404">
	<div class="container">
		<div class="error-404__inner reveal">
			<div class="error-404__icon">
				<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M11.7 11.2a5.18 5.18 0 0 1 3.3-2.2c2.5-.4 4-1 4-1s-.3 2.3-2 4c-1.7 1.7-3.3 2.5-3.3 2.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
			</div>
			<div class="error-404__code">404</div>
			<h1 class="error-404__title"><?php esc_html_e( 'Không tìm thấy trang', 'spl' ); ?></h1>
			<p class="error-404__desc"><?php esc_html_e( 'Trang bạn tìm có thể đã bị xóa, đổi tên hoặc tạm thời không khả dụng. Hãy thử tìm kiếm hoặc quay lại trang chủ.', 'spl' ); ?></p>

			<div class="error-404__search search-bar" role="search">
				<div class="search-bar__wrapper" data-search>
					<form action="<?php echo esc_url( $home_url ); ?>" method="get">
						<label for="search-input-404" class="sr-only"><?php esc_html_e( 'Tìm kiếm sản phẩm', 'spl' ); ?></label>
						<input id="search-input-404" type="search" class="search-bar__input" name="s" placeholder="<?php esc_attr_e( 'Tìm xe điện, xe máy điện, phụ kiện...', 'spl' ); ?>" autocomplete="off" data-search-input />
						<?php if ( $is_woo ) : ?>
							<input type="hidden" name="post_type" value="product" />
						<?php endif; ?>
						<button type="submit" class="search-bar__btn" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
							<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
						</button>
					</form>
					<div class="search-results" data-search-results hidden></div>
				</div>
			</div>

			<div class="error-404__actions">
				<a href="<?php echo esc_url( $home_url ); ?>" class="btn btn--primary">
					<svg class="icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
					<?php esc_html_e( 'Về trang chủ', 'spl' ); ?>
				</a>
				<?php if ( $shop_url ) : ?>
					<a href="<?php echo esc_url( $shop_url ); ?>" class="btn btn--outline">
						<svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
						<?php esc_html_e( 'Xem sản phẩm', 'spl' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<?php
		if ( $is_woo ) :
			$suggested = new WP_Query( [
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'posts_per_page'      => 4,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			] );

			if ( $suggested->have_posts() ) :
				?>
				<div class="error-404__suggest">
					<div class="section-title reveal">
						<div class="section-title__label">
							<svg class="icon" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
							<?php esc_html_e( 'Gợi ý cho bạn', 'spl' ); ?>
						</div>
						<h2 class="section-title__heading">
						<svg class="section-title__icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
						<?php esc_html_e( 'Sản phẩm mới nhất', 'spl' ); ?>
					</h2>
						<div class="section-title__line"></div>
					</div>
					<div class="products-grid">
						<?php
						while ( $suggested->have_posts() ) :
							$suggested->the_post();
							get_template_part( 'parts/product-card', null, [ 'id' => get_the_ID() ] );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</div>
				<?php
			endif;
		endif;
		?>
	</div>
</section>

<?php get_footer(); ?>
