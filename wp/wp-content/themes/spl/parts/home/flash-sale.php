<?php
/**
 * Home page — Flash Sale section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

// Countdown end time (ACF or default +24h).
$end_time = $data['end_time'] ?? '';
if ( empty( $end_time ) ) {
	$end_time = wp_date( 'c', strtotime( '+24 hours' ) );
} else {
	// Ensure ISO format for cross-browser JS Date parsing.
	$end_time = wp_date( 'c', strtotime( $end_time ) );
}

// Number of products to show (ACF, default 4).
$count = isset( $data['count'] ) ? absint( $data['count'] ) : 0;
$count = $count > 0 ? $count : 4;

// Columns on large screens (ACF select 3/4/5, default 4).
$cols = isset( $data['columns'] ) ? absint( $data['columns'] ) : 4;
$cols = max( 3, min( 5, $cols ?: 4 ) );

// Get on-sale WooCommerce products.
$sale_products = [];
if ( Helper::isWoocommerceActive() ) {
	$sale_ids = wc_get_product_ids_on_sale();
	if ( ! empty( $sale_ids ) ) {
		$sale_query = new \WP_Query( [
			'post_type'      => 'product',
			'posts_per_page' => $count,
			'post__in'       => $sale_ids,
			'orderby'        => 'rand',
		] );
		$sale_products = $sale_query->posts;
	}
}

?>
<section class="section-compact">
	<div class="flash-sale container reveal">
		<div class="flash-sale__header">
			<div class="flash-sale__title">
				<svg class="icon" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
				FLASH SALE
			</div>
			<div class="countdown" data-end="<?php echo esc_attr( $end_time ); ?>">
				<div class="countdown__block" data-cd="hours">00</div>
				<span class="countdown__sep">:</span>
				<div class="countdown__block" data-cd="minutes">00</div>
				<span class="countdown__sep">:</span>
				<div class="countdown__block" data-cd="seconds">00</div>
			</div>
		</div>
		<div class="products-grid products-grid--cols" style="--cols:<?php echo esc_attr( $cols ); ?>;">
			<?php
			if ( ! empty( $sale_products ) ) :
				foreach ( $sale_products as $post ) :
					$product = wc_get_product( $post->ID );
					if ( ! $product ) {
						continue;
					}
					get_template_part( 'parts/product-card', null, [ 'product' => $product ] );
				endforeach;
				wp_reset_postdata();
			else :
				?>
				<div class="flash-sale__empty"><?php esc_html_e( 'Sản phẩm flash sale sẽ được cập nhật sớm!', 'spl' ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</section>
