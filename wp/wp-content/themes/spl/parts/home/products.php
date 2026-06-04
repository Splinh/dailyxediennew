<?php
/**
 * Home page — Featured Products section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

$label   = $data['label'] ?? __( 'Bán Chạy', 'spl' );
$heading = $data['heading'] ?? $data['title'] ?? __( 'Sản Phẩm Nổi Bật', 'spl' );

// Section settings (ACF): category (term id, empty = newest) + count (default 4).
$cat_id = isset( $data['category'] ) ? absint( $data['category'] ) : 0;
$count  = isset( $data['count'] ) ? absint( $data['count'] ) : 0;
$count  = $count > 0 ? $count : 6;
$query_count = $count + ( $count % 2 );

// Columns on large screens (ACF select 3/4/5, default 4).
$cols = isset( $data['columns'] ) ? absint( $data['columns'] ) : 4;
$cols = max( 3, min( 5, $cols ?: 4 ) );

// Unique IDs per instance (section can repeat). First instance keeps #products
// so the hero "Mua Ngay" anchor still works.
static $spl_products_instance = 0;
++$spl_products_instance;
$section_id = $spl_products_instance === 1 ? 'products' : 'products-' . $spl_products_instance;
$grid_id    = 'featured-products-' . $spl_products_instance;

?>
<section class="section-compact" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container">
		<div class="section-title reveal">
			<div class="section-title__label">
				<svg class="icon" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
				<?php echo esc_html( $label ); ?>
			</div>
			<h2 class="section-title__heading">
				<svg class="section-title__icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
				<?php echo esc_html( $heading ); ?>
			</h2>
			<div class="section-title__line"></div>
		</div>
		<div class="products-grid products-grid--cols" id="<?php echo esc_attr( $grid_id ); ?>" style="--cols:<?php echo esc_attr( $cols ); ?>;">
			<?php
			if ( Helper::isWoocommerceActive() ) :
				$query_args = [
					'post_type'           => 'product',
					'posts_per_page'      => $query_count,
					'orderby'             => 'date',
					'order'               => 'DESC',
					'ignore_sticky_posts' => true,
				];

				// Selected category → that category. Empty → newest products overall.
				if ( $cat_id ) {
					$query_args['tax_query'] = [
						[
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => $cat_id,
						],
					];
				}

				$products_query = new \WP_Query( $query_args );

				if ( $products_query->have_posts() ) :
					$item_index = 0;
					while ( $products_query->have_posts() ) :
						$products_query->the_post();
						++$item_index;
						get_template_part(
							'parts/product-card',
							null,
							[
								'id'    => get_the_ID(),
								'class' => $item_index > $count ? 'product-card--mobile-extra' : '',
							]
						);
					endwhile;
					wp_reset_postdata();
				endif;
			else :
				// Static fallback.
				$fallback = [
					[ 'name' => 'Bột Nghệ Nguyên Chất', 'price' => '120.000₫', 'img' => 'product-powder.png' ],
					[ 'name' => 'Trà Hoa Cúc Túi Lọc', 'price' => '85.000₫', 'img' => 'product-tea.png' ],
					[ 'name' => 'Tinh Dầu Tràm Trà', 'price' => '195.000₫', 'img' => 'product-oil.png' ],
					[ 'name' => 'Thiên Niên Kiện', 'price' => '150.000₫', 'img' => 'product-thien-nien-kien.png' ],
				];
				foreach ( $fallback as $item ) :
					?>
					<div class="product-card reveal">
						<a href="#" class="product-card__link">
							<div class="product-card__image">
								<img src="<?php echo esc_url( get_theme_file_uri( 'resources/img/' . $item['img'] ) ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" loading="lazy" />
							</div>
							<div class="product-card__body">
								<h3 class="product-card__name"><?php echo esc_html( $item['name'] ); ?></h3>
								<div class="product-card__price">
									<span class="product-card__price-current"><?php echo esc_html( $item['price'] ); ?></span>
								</div>
							</div>
						</a>
					</div>
					<?php
				endforeach;
			endif;
			?>
		</div>
	</div>
</section>
