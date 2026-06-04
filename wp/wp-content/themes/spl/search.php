<?php
/**
 * Search results template.
 *
 * Renders search results for posts/pages only.
 * Product searches are handled by woocommerce/archive-product.php
 * (WooCommerce hijacks template_include for post_type=product).
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

// Product search is handled by WooCommerce's archive-product.php — bail out.
if ( Helper::isWoocommerceActive() && get_query_var( 'post_type' ) === 'product' ) {
	return;
}

get_header();

$query_text   = get_search_query();
$total_found  = $wp_query->found_posts ?? 0;
$is_product   = false; // Product searches never reach here.
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
			<span class="breadcrumb__current"><?php esc_html_e( 'Kết quả tìm kiếm', 'spl' ); ?></span>
		</nav>
	</div>
</div>

<!-- Search Header -->
<section class="search-header">
	<div class="container">
		<div class="search-header__content reveal">
			<div class="search-header__icon">
				<svg class="icon icon-xl" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
			</div>
			<div>
				<h1 class="search-header__title">
					<?php
					if ( $query_text ) :
						printf(
							/* translators: %s: search query */
							esc_html__( 'Kết quả tìm kiếm cho: "%s"', 'spl' ),
							'<span class="search-header__keyword">' . esc_html( $query_text ) . '</span>'
						);
					else :
						esc_html_e( 'Tìm Kiếm', 'spl' );
					endif;
					?>
				</h1>
				<p class="search-header__meta">
					<?php
					printf(
						/* translators: %d: number of results */
						esc_html( _n( 'Tìm thấy %d kết quả', 'Tìm thấy %d kết quả', $total_found, 'spl' ) ),
						$total_found
					);

					if ( $is_product ) :
						echo ' · <span class="search-header__type">';
						esc_html_e( 'Sản phẩm', 'spl' );
						echo '</span>';
					endif;
					?>
				</p>
			</div>
		</div>

		<!-- Re-search form -->
		<form role="search" method="get" class="search-form-inline" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<div class="search-form-inline__wrapper">
				<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
				<input type="search"
					name="s"
					value="<?php echo esc_attr( $query_text ); ?>"
					placeholder="<?php esc_attr_e( 'Nhập từ khóa tìm kiếm...', 'spl' ); ?>"
					aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>"
					required />
				<?php if ( $is_product ) : ?>
					<input type="hidden" name="post_type" value="product" />
				<?php endif; ?>
				<button type="submit" class="btn btn--primary btn--sm">
					<?php esc_html_e( 'Tìm kiếm', 'spl' ); ?>
				</button>
			</div>
			<?php if ( $is_product && Helper::isWoocommerceActive() ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 's', $query_text, home_url( '/' ) ) ); ?>" class="search-form-inline__toggle">
					<?php esc_html_e( 'Tìm trong tất cả →', 'spl' ); ?>
				</a>
			<?php elseif ( Helper::isWoocommerceActive() ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 's' => $query_text, 'post_type' => 'product' ], home_url( '/' ) ) ); ?>" class="search-form-inline__toggle">
					<?php esc_html_e( 'Chỉ tìm sản phẩm →', 'spl' ); ?>
				</a>
			<?php endif; ?>
		</form>
	</div>
</section>

<!-- Search Results -->
<section class="search-results-section">
	<div class="container">
		<?php if ( have_posts() ) : ?>

			<?php if ( $is_product && Helper::isWoocommerceActive() ) : ?>
				<!-- Product results: grid layout -->
				<div class="products-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						$product = wc_get_product( get_the_ID() );
						if ( ! $product ) {
							continue;
						}

						$image_url = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src();
						$is_sale   = $product->is_on_sale();
						?>
						<div class="product-card reveal">
							<?php if ( $is_sale ) : ?>
								<span class="product-card__badge"><?php esc_html_e( 'Giảm giá', 'spl' ); ?></span>
							<?php endif; ?>
							<a href="<?php the_permalink(); ?>" class="product-card__link">
								<div class="product-card__image">
									<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy" />
								</div>
								<div class="product-card__body">
									<h3 class="product-card__name"><?php echo esc_html( $product->get_name() ); ?></h3>
									<div class="product-card__price">
										<?php echo wp_kses_post( $product->get_price_html() ); ?>
									</div>
								</div>
							</a>
						</div>
					<?php endwhile; ?>
				</div>

			<?php else : ?>
				<!-- Mixed results: blog-style list -->
				<div class="blog-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						$thumb     = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
						$post_type = get_post_type();

						// Type badge text.
						$type_label = match ( $post_type ) {
							'product' => __( 'Sản phẩm', 'spl' ),
							'page'    => __( 'Trang', 'spl' ),
							default   => __( 'Bài viết', 'spl' ),
						};
						?>
						<article class="blog-card reveal">
							<?php if ( $thumb ) : ?>
								<a href="<?php the_permalink(); ?>" class="blog-card__image">
									<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
								</a>
							<?php endif; ?>
							<div class="blog-card__content">
								<div class="blog-card__meta">
									<span class="blog-card__badge blog-card__badge--<?php echo esc_attr( $post_type ); ?>">
										<?php echo esc_html( $type_label ); ?>
									</span>
									<span class="blog-card__date">
										<svg class="icon icon-sm" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
										<?php echo esc_html( get_the_date() ); ?>
									</span>
								</div>
								<h2 class="blog-card__title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h2>
								<p class="blog-card__excerpt">
									<?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?>
								</p>
								<a href="<?php the_permalink(); ?>" class="blog-card__link">
									<?php esc_html_e( 'Xem chi tiết', 'spl' ); ?>
									<svg class="icon icon-sm" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
								</a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
			<?php endif; ?>

			<?php
			// Pagination.
			the_posts_pagination( [
				'prev_text' => '<svg class="icon" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>',
				'next_text' => '<svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>',
				'class'     => 'pagination',
			] );
			?>

		<?php else : ?>

			<!-- No results -->
			<div class="search-no-results reveal">
				<div class="search-no-results__icon">
					<svg viewBox="0 0 24 24" width="80" height="80" stroke="currentColor" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="11" cy="11" r="8"/>
						<path d="m21 21-4.3-4.3"/>
						<line x1="8" y1="8" x2="14" y2="14" stroke-width="2"/>
						<line x1="14" y1="8" x2="8" y2="14" stroke-width="2"/>
					</svg>
				</div>
				<h2><?php esc_html_e( 'Không tìm thấy kết quả', 'spl' ); ?></h2>
				<p><?php esc_html_e( 'Rất tiếc, không có kết quả nào phù hợp với từ khóa của bạn. Hãy thử tìm kiếm với từ khóa khác.', 'spl' ); ?></p>

				<!-- Suggestions -->
				<div class="search-no-results__suggestions">
					<h3><?php esc_html_e( 'Gợi ý:', 'spl' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Kiểm tra lại chính tả', 'spl' ); ?></li>
						<li><?php esc_html_e( 'Sử dụng từ khóa ngắn gọn hơn', 'spl' ); ?></li>
						<li><?php esc_html_e( 'Thử tìm kiếm với từ khóa khác', 'spl' ); ?></li>
					</ul>
				</div>

				<!-- Popular products -->
				<?php if ( Helper::isWoocommerceActive() ) : ?>
					<div class="search-no-results__popular">
						<h3><?php esc_html_e( 'Sản phẩm phổ biến:', 'spl' ); ?></h3>
						<div class="products-grid products-grid--compact">
							<?php
							$popular = new \WP_Query( [
								'post_type'      => 'product',
								'posts_per_page' => 4,
								'meta_key'       => 'total_sales',
								'orderby'        => 'meta_value_num',
								'order'          => 'DESC',
							] );

							if ( ! $popular->have_posts() ) {
								$popular = new \WP_Query( [
									'post_type'      => 'product',
									'posts_per_page' => 4,
									'orderby'        => 'date',
									'order'          => 'DESC',
								] );
							}

							while ( $popular->have_posts() ) :
								$popular->the_post();
								$product   = wc_get_product( get_the_ID() );
								if ( ! $product ) {
									continue;
								}
								$image_url = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src();
								?>
								<div class="product-card reveal">
									<a href="<?php the_permalink(); ?>" class="product-card__link">
										<div class="product-card__image">
											<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy" />
										</div>
										<div class="product-card__body">
											<h3 class="product-card__name"><?php echo esc_html( $product->get_name() ); ?></h3>
											<div class="product-card__price">
												<?php echo wp_kses_post( $product->get_price_html() ); ?>
											</div>
										</div>
									</a>
								</div>
							<?php endwhile; ?>
							<?php wp_reset_postdata(); ?>
						</div>
					</div>
				<?php endif; ?>

				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary" style="margin-top: var(--sp-5);">
					<svg class="icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
					<?php esc_html_e( 'Về trang chủ', 'spl' ); ?>
				</a>
			</div>

		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
