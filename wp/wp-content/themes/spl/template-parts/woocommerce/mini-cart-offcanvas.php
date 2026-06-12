<?php
/**
 * Centered mini-cart modal (DailyXeDien style).
 *
 * Replaces the old off-canvas drawer. Matches htmlmau/index.html cart-modal.
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
<!-- Mini-Cart Modal -->
<div id="cart-modal" class="dxd-modal" data-cart-modal aria-hidden="true">
	<div class="dxd-modal__overlay" data-cart-close></div>
	<div class="dxd-modal__panel dxd-minicart">
		<!-- Header -->
		<div class="dxd-minicart__header">
			<h3>
				<?php echo spl_icon( 'cart', 'w-5 h-5 text-accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Giỏ hàng của bạn', 'spl' ); ?>
				<span class="dxd-minicart__count" data-cart-count><?php echo esc_html( (string) $cart_count ); ?></span>
			</h3>
			<button type="button" data-cart-close aria-label="<?php esc_attr_e( 'Đóng giỏ hàng', 'spl' ); ?>">
				<?php echo spl_icon( 'close', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<!-- Cart Content (fragment-replaceable) -->
		<?php get_template_part( 'template-parts/woocommerce/mini-cart-content' ); ?>

		<!-- Footer -->
		<div class="dxd-minicart__footer">
			<div class="dxd-minicart__total">
				<span><?php esc_html_e( 'Tổng thanh toán:', 'spl' ); ?></span>
				<span class="dxd-minicart__total-price" data-cart-total><?php echo wp_kses_post( $cart_total ); ?></span>
			</div>
			<a href="<?php echo esc_url( $checkout_url ); ?>" class="dxd-minicart__checkout-btn">
				<?php esc_html_e( 'TIẾN HÀNH THANH TOÁN', 'spl' ); ?>
			</a>
			<a href="<?php echo esc_url( $cart_url ); ?>" class="dxd-minicart__viewcart-btn">
				<?php esc_html_e( 'Xem giỏ hàng đầy đủ', 'spl' ); ?>
			</a>
		</div>
	</div>
</div>
