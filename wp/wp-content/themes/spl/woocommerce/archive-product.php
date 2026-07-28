<?php
/**
 * WooCommerce Archive (Shop / Category) Template.
 *
 * Overrides WooCommerce's default archive-product.php.
 * Matches the HTML mockup archive.html layout.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();

$hotline         = Helper::getField( 'hotline', 'option' ) ?: '098 750 33 60';
$hotline_url     = 'tel:' . preg_replace( '/\s+/', '', $hotline );
$is_shop         = is_shop() && ! is_search();
$is_search       = is_search();
$queried         = get_queried_object();

if ( $is_search ) {
	$search_query    = get_search_query();
	$cat_name        = sprintf(
		/* translators: %s: search query */
		__( 'Kết quả tìm kiếm: "%s"', 'spl' ),
		$search_query
	);
	$cat_description = sprintf(
		/* translators: %d: number of results */
		_n( 'Tìm thấy %d sản phẩm', 'Tìm thấy %d sản phẩm', $wp_query->found_posts ?? 0, 'spl' ),
		$wp_query->found_posts ?? 0
	);
} else {
	$cat_name        = $is_shop ? __( 'Tất Cả Sản Phẩm', 'spl' ) : ( $queried->name ?? '' );
	$cat_description = $is_shop ? __( 'Khám phá các dòng xe điện, xe máy điện, xe 50cc chính hãng chất lượng cao. Bảo hành 3 năm, hỗ trợ trả góp 0%.', 'spl' ) : ( $queried->description ?? '' );
}
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
			<?php if ( $is_search ) : ?>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'Sản phẩm', 'spl' ); ?></a>
				<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
				<span class="breadcrumb__current"><?php esc_html_e( 'Tìm kiếm', 'spl' ); ?></span>
			<?php elseif ( ! $is_shop && $queried instanceof WP_Term ) : ?>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'Sản phẩm', 'spl' ); ?></a>
				<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
				<span class="breadcrumb__current"><?php echo esc_html( $cat_name ); ?></span>
			<?php else : ?>
				<span class="breadcrumb__current"><?php echo esc_html( $cat_name ); ?></span>
			<?php endif; ?>
		</nav>
	</div>
</div>

<!-- ===== ARCHIVE HEADER ===== -->
<section class="archive-header<?php echo $is_search ? ' archive-header--search' : ''; ?>">
	<div class="container">
		<div class="archive-header__content reveal">
			<div class="archive-header__icon">
				<?php if ( $is_search ) : ?>
					<svg class="icon icon-xl" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
				<?php else : ?>
					<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M11.7 11.2a5.18 5.18 0 0 1 3.3-2.2c2.5-.4 4-1 4-1s-.3 2.3-2 4c-1.7 1.7-3.3 2.5-3.3 2.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
				<?php endif; ?>
			</div>
			<div>
				<h1 class="archive-header__title"><?php echo esc_html( $cat_name ); ?></h1>
				<?php if ( $cat_description ) : ?>
					<p class="archive-header__desc"><?php echo esc_html( $cat_description ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( $is_search ) : ?>
			<form role="search" method="get" class="search-form-inline" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<div class="search-form-inline__wrapper">
					<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					<input type="search"
						name="s"
						value="<?php echo esc_attr( $search_query ); ?>"
						placeholder="<?php esc_attr_e( 'Tìm kiếm sản phẩm...', 'spl' ); ?>"
						aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>"
						required />
					<input type="hidden" name="post_type" value="product" />
					<button type="submit" class="btn btn--primary btn--sm">
						<?php esc_html_e( 'Tìm kiếm', 'spl' ); ?>
					</button>
				</div>
			</form>
		<?php endif; ?>
	</div>
</section>

<!-- ===== FILTERS + PRODUCTS ===== -->
<section class="archive-main">
	<div class="container">
		<div class="archive-layout<?php echo $is_search ? ' archive-layout--full' : ''; ?>">

		<?php if ( ! $is_search ) : ?>
			<div class="archive-filter-overlay" id="archive-filter-overlay"></div>

			<aside class="archive-sidebar reveal" id="archive-sidebar">
				<div class="lg:hidden flex items-center justify-between p-4 border-b border-slate-100 mb-4 bg-slate-50">
					<span class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
						<svg class="w-4 h-4 text-[#1e73be]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
						<?php esc_html_e( 'Bộ lọc sản phẩm', 'spl' ); ?>
					</span>
					<button type="button" id="archive-filter-close" class="w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
					</button>
				</div>

				<?php
				if ( class_exists( '\SPL\Modules\WooCommerce\Filter\Frontend\FilterRenderer' ) ) {
					$preset_id = \SPL\Modules\WooCommerce\Filter\FilterManager::resolvePresetId() ?: 0;
					\SPL\Modules\WooCommerce\Filter\Frontend\FilterRenderer::render( $preset_id, 'vertical', 'archive-filter-form', [ 'mobile_popup' => false ] );
				}
				?>

				<!-- Sidebar CTA -->
				<div class="sidebar-widget sidebar-widget--cta">
					<div class="sidebar-cta">
						<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
						<h4><?php esc_html_e( 'Tư vấn mua sỉ?', 'spl' ); ?></h4>
						<p><?php esc_html_e( 'Liên hệ để nhận báo giá sỉ tốt nhất cho đại lý.', 'spl' ); ?></p>
						<a href="<?php echo esc_url( $hotline_url ); ?>" class="btn btn--primary btn--sm"><?php echo esc_html( $hotline ); ?></a>
					</div>
				</div>
			</aside>
		<?php endif; ?>

			<!-- Products Grid -->
			<div class="archive-products">
				<!-- Toolbar -->
				<div class="archive-toolbar reveal">
					<div class="archive-toolbar__results woocommerce-result-count">
						<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
						<?php
						global $wp_query;
						$total   = (int) $wp_query->found_posts;
						$current = max( 1, get_query_var( 'paged', 1 ) );
						$per     = (int) get_query_var( 'posts_per_page', 12 );
						$from    = ( $current - 1 ) * $per + 1;
						$to      = min( $current * $per, $total );
						printf(
							/* translators: %1$d: from, %2$d: to, %3$d: total */
							esc_html__( 'Hiển thị %1$d-%2$d trong %3$d sản phẩm', 'spl' ),
							(int) $from,
							(int) $to,
							(int) $total
						);
						?>
					</div>
					<div class="archive-toolbar__actions">
						<?php if ( ! $is_search ) : ?>
							<button type="button" class="archive-filter-toggle" id="archive-filter-toggle" aria-controls="archive-sidebar" aria-expanded="false">
								<svg class="icon" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
								<?php esc_html_e( 'Lọc', 'spl' ); ?>
							</button>
						<?php endif; ?>
						<div class="archive-sort">
							<label for="sort-select"><?php esc_html_e( 'Sắp xếp:', 'spl' ); ?></label>
							<?php
							// phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$current_order = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';
							?>
							<select id="sort-select" onchange="if(this.value){window.location=this.value}">
								<?php
								$sort_options = [
									'menu_order' => __( 'Mặc định', 'spl' ),
									'price'      => __( 'Giá: Thấp → Cao', 'spl' ),
									'price-desc' => __( 'Giá: Cao → Thấp', 'spl' ),
									'popularity' => __( 'Bán chạy nhất', 'spl' ),
									'date'       => __( 'Mới nhất', 'spl' ),
									'rating'     => __( 'Đánh giá cao', 'spl' ),
								];
								foreach ( $sort_options as $value => $label ) :
									$url = add_query_arg( 'orderby', $value );
									?>
									<option value="<?php echo esc_url( $url ); ?>" <?php selected( $current_order, $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="archive-view-toggle">
							<button class="archive-view-btn active" data-view="grid" aria-label="<?php esc_attr_e( 'Dạng lưới', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
							</button>
							<button class="archive-view-btn" data-view="list" aria-label="<?php esc_attr_e( 'Dạng danh sách', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
							</button>
						</div>
					</div>
				</div>

				<!-- Products -->
				<div class="products-grid products" id="archive-products">
					<?php
					if ( woocommerce_product_loop() ) :
						while ( have_posts() ) :
							the_post();
							get_template_part( 'parts/product-card', null, [ 'id' => get_the_ID() ] );
						endwhile;
						wp_reset_postdata();
					else :
						?>
						<div class="archive-no-products">
							<svg class="icon icon-xl" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
							<p><?php esc_html_e( 'Không tìm thấy sản phẩm nào.', 'spl' ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<!-- Smart Pagination -->
				<?php
				$total_pages = (int) $wp_query->max_num_pages;
				if ( $total_pages > 1 ) :
					?>
					<div class="pagination woocommerce-pagination reveal">
						<?php
						// 1. First Page (<<)
						if ( $current > 1 ) :
							?>
							<a href="<?php echo esc_url( get_pagenum_link( 1 ) ); ?>" class="pagination__btn" title="<?php esc_attr_e( 'Trang đầu', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><polyline points="11 18 5 12 11 6"/><polyline points="18 18 12 12 18 6"/></svg>
							</a>
						<?php endif; ?>

						<?php
						// 2. Previous (<)
						if ( $current > 1 ) :
							?>
							<a href="<?php echo esc_url( get_pagenum_link( $current - 1 ) ); ?>" class="pagination__btn pagination__btn--prev" title="<?php esc_attr_e( 'Trang trước', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
							</a>
						<?php endif; ?>

						<?php
						// Calculate smart page range
						$range     = [ 1 ];
						$adjacents = 1;

						if ( $total_pages >= 2 ) {
							$range[] = 2;
						}

						for ( $i = max( 3, $current - $adjacents ); $i <= min( $total_pages - 2, $current + $adjacents ); $i++ ) {
							$range[] = $i;
						}

						if ( $total_pages >= 3 ) {
							$range[] = $total_pages - 1;
							$range[] = $total_pages;
						}

						$range = array_values( array_unique( $range ) );
						sort( $range );

						$prev = 0;
						foreach ( $range as $page ) :
							if ( $prev > 0 && $page - $prev > 1 ) :
								?>
								<span class="pagination__dots px-1 text-slate-400 font-bold select-none">...</span>
							<?php endif; ?>

							<?php if ( $page === $current ) : ?>
								<span class="pagination__page active"><?php echo (int) $page; ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( get_pagenum_link( $page ) ); ?>" class="pagination__page"><?php echo (int) $page; ?></a>
							<?php endif; ?>

							<?php
							$prev = $page;
						endforeach;
						?>

						<?php
						// 3. Next (>)
						if ( $current < $total_pages ) :
							?>
							<a href="<?php echo esc_url( get_pagenum_link( $current + 1 ) ); ?>" class="pagination__btn pagination__btn--next" title="<?php esc_attr_e( 'Trang sau', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
							</a>
						<?php endif; ?>

						<?php
						// 4. Last Page (>>)
						if ( $current < $total_pages ) :
							?>
							<a href="<?php echo esc_url( get_pagenum_link( $total_pages ) ); ?>" class="pagination__btn" title="<?php esc_attr_e( 'Trang cuối', 'spl' ); ?>">
								<svg class="icon" viewBox="0 0 24 24"><polyline points="13 18 19 12 13 6"/><polyline points="6 18 12 12 6 6"/></svg>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
	var toggleBtn = document.getElementById('archive-filter-toggle');
	var closeBtn = document.getElementById('archive-filter-close');
	var sidebar = document.getElementById('archive-sidebar');
	var overlay = document.getElementById('archive-filter-overlay');
	var body = document.body;

	if (toggleBtn && sidebar && overlay) {
		toggleBtn.addEventListener('click', function() {
			sidebar.classList.add('is-open');
			overlay.classList.add('is-visible');
			body.classList.add('overflow-hidden');
		});

		var closeSidebar = function() {
			sidebar.classList.remove('is-open');
			overlay.classList.remove('is-visible');
			body.classList.remove('overflow-hidden');
		};

		if (closeBtn) {
			closeBtn.addEventListener('click', closeSidebar);
		}
		overlay.addEventListener('click', closeSidebar);

		// Close mobile filter drawer after AJAX update
		document.addEventListener('hd:filter:updated', closeSidebar);
	}
});
</script>

<?php get_footer(); ?>
