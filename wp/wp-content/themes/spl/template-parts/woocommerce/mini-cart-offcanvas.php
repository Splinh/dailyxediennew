<?php
/**
 * Off-canvas mini cart.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mini-cart-overlay" data-mini-cart-close></div>
<aside class="mini-cart-offcanvas" id="mini-cart-offcanvas" aria-hidden="true" aria-label="<?php esc_attr_e( 'Giỏ hàng', 'spl' ); ?>">
	<div class="mini-cart-offcanvas__header">
		<div>
			<span class="mini-cart-offcanvas__eyebrow"><?php esc_html_e( 'Đơn hàng của bạn', 'spl' ); ?></span>
			<h2><?php esc_html_e( 'Giỏ hàng', 'spl' ); ?></h2>
		</div>
		<button type="button" class="mini-cart-offcanvas__close" data-mini-cart-close aria-label="<?php esc_attr_e( 'Đóng giỏ hàng', 'spl' ); ?>">&times;</button>
	</div>
	<?php get_template_part( 'template-parts/woocommerce/mini-cart-content' ); ?>
</aside>
