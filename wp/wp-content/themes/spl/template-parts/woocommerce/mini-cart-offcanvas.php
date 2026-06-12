<?php
/**
 * Off-canvas mini-cart drawer (Taodo/SPL style).
 *
 * Right-side slide-in drawer matching taodo.splworks.com design.
 * Fragment-replaceable content in mini-cart-content.php.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$cart_url     = wc_get_cart_url();
$checkout_url = wc_get_checkout_url();
$cart_count   = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$cart_total   = WC()->cart ? WC()->cart->get_cart_total() : wc_price( 0 );
?>
<!-- Mini-Cart Overlay -->
<div class="dxd-cart-overlay" data-cart-close></div>

<!-- Mini-Cart Off-Canvas Drawer -->
<aside class="dxd-cart-drawer" id="mini-cart-offcanvas" data-cart-modal aria-hidden="true" aria-label="<?php esc_attr_e( 'Giỏ hàng', 'spl' ); ?>">

	<!-- Header -->
	<div class="dxd-cart-drawer__header">
		<div>
			<span class="dxd-cart-drawer__eyebrow"><?php esc_html_e( 'Đơn hàng của bạn', 'spl' ); ?></span>
			<h2><?php esc_html_e( 'Giỏ hàng', 'spl' ); ?> <span class="dxd-cart-drawer__count" data-cart-count><?php echo esc_html( (string) $cart_count ); ?></span></h2>
		</div>
		<button type="button" class="dxd-cart-drawer__close" data-cart-close aria-label="<?php esc_attr_e( 'Đóng giỏ hàng', 'spl' ); ?>">
			<?php echo spl_icon( 'close', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>

	<!-- Cart Content (fragment-replaceable) -->
	<?php get_template_part( 'template-parts/woocommerce/mini-cart-content' ); ?>

	<!-- Footer -->
	<div class="dxd-cart-drawer__footer">
		<div class="dxd-cart-drawer__total">
			<span><?php esc_html_e( 'Tổng cộng', 'spl' ); ?></span>
			<strong data-cart-total><?php echo wp_kses_post( $cart_total ); ?></strong>
		</div>
		<div class="dxd-cart-drawer__actions">
			<a href="<?php echo esc_url( $cart_url ); ?>" class="dxd-cart-drawer__btn dxd-cart-drawer__btn--outline">
				<?php echo spl_icon( 'cart', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Xem giỏ hàng', 'spl' ); ?>
			</a>
			<a href="<?php echo esc_url( $checkout_url ); ?>" class="dxd-cart-drawer__btn dxd-cart-drawer__btn--primary">
				<?php esc_html_e( 'Thanh toán', 'spl' ); ?>
				<?php echo spl_icon( 'arrow-right', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
	</div>
</aside>
