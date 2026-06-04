<?php
/**
 * Home page — Product Categories grid section.
 *
 * Markup matches website/index.html (#categories) + inc/critical.css.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

$label   = $data['label'] ?? __( 'Danh Mục', 'spl' );
$heading = $data['heading'] ?? $data['title'] ?? __( 'Danh Mục Sản Phẩm', 'spl' );

// Columns on large screens (ACF select 3/4/5, default 4).
$cols = isset( $data['columns'] ) ? absint( $data['columns'] ) : 4;
$cols = max( 3, min( 5, $cols ?: 4 ) );

// Default leaf icon for category cards (product categories have no icon meta).
$cat_icon = '<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>';

?>
<section class="section-compact section-alt" id="categories">
	<div class="container">
		<div class="section-title reveal">
			<div class="section-title__label">
				<svg class="icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
				<?php echo esc_html( $label ); ?>
			</div>
			<h2 class="section-title__heading">
				<svg class="section-title__icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
				<?php echo esc_html( $heading ); ?>
			</h2>
			<div class="section-title__line"></div>
		</div>
		<div class="categories-grid" style="--cols:<?php echo esc_attr( $cols ); ?>;">
			<?php
			$rendered = false;

			if ( Helper::isWoocommerceActive() ) :
				$cats = get_terms( [
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'parent'     => 0,
					'orderby'    => 'menu_order',
					'order'      => 'ASC',
					'exclude'    => [ (int) get_option( 'default_product_cat' ) ],
				] );

				if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) :
					foreach ( $cats as $cat ) :
						$cat_link = get_term_link( $cat );
						if ( is_wp_error( $cat_link ) ) {
							continue;
						}
						$rendered = true;
						?>
						<a href="<?php echo esc_url( $cat_link ); ?>" class="category-card reveal">
							<div class="category-card__icon"><?php echo $cat_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></div>
							<div class="category-card__name"><?php echo esc_html( $cat->name ); ?></div>
							<div class="category-card__count"><?php echo (int) $cat->count; ?> <?php esc_html_e( 'sản phẩm', 'spl' ); ?></div>
						</a>
						<?php
					endforeach;
				endif;
			endif;

			if ( ! $rendered ) :
				// Static fallback when WooCommerce is inactive or no categories exist.
				$static_cats = [
					__( 'Thảo Dược Khô', 'spl' ),
					__( 'Trà Túi Lọc', 'spl' ),
					__( 'Bột Nguyên Chất', 'spl' ),
					__( 'Tinh Dầu Thiên Nhiên', 'spl' ),
					__( 'Bài Thuốc Nam', 'spl' ),
					__( 'Dược Liệu', 'spl' ),
				];
				foreach ( $static_cats as $name ) :
					?>
					<a href="#" class="category-card reveal">
						<div class="category-card__icon"><?php echo $cat_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></div>
						<div class="category-card__name"><?php echo esc_html( $name ); ?></div>
						<div class="category-card__count">0 <?php esc_html_e( 'sản phẩm', 'spl' ); ?></div>
					</a>
					<?php
				endforeach;
			endif;
			?>
		</div>
	</div>
</section>
