<?php
/**
 * Fragment-replaceable mini-cart content.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$free_shipping_min = (float) apply_filters( 'spl_free_shipping_minimum', 500000 );
$subtotal          = WC()->cart ? (float) WC()->cart->get_subtotal() : 0;
$remaining         = max( 0, $free_shipping_min - $subtotal );
$progress          = $free_shipping_min > 0 ? min( 100, ( $subtotal / $free_shipping_min ) * 100 ) : 0;
?>
<div class="mini-cart-offcanvas__content">
	<?php if ( WC()->cart && ! WC()->cart->is_empty() ) : ?>
		<div class="mini-cart-offcanvas__shipping">
			<?php if ( $remaining > 0 ) : ?>
				<p><?php printf( esc_html__( 'Mua thêm %s để được miễn phí giao hàng.', 'spl' ), wp_kses_post( wc_price( $remaining ) ) ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Đơn hàng của bạn được miễn phí giao hàng.', 'spl' ); ?></p>
			<?php endif; ?>
			<div class="mini-cart-progress"><span style="width: <?php echo esc_attr( $progress ); ?>%"></span></div>
		</div>
	<?php endif; ?>

	<div class="mini-cart-offcanvas__items">
		<?php woocommerce_mini_cart(); ?>
	</div>
</div>
