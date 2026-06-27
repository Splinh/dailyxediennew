<?php
/**
 * Cart totals — DailyXeDien override.
 *
 * Tailwind card layout matching htmlmau/gio-hang.html sidebar.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.6
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cart_totals <?php echo ( WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>">

	<?php do_action( 'woocommerce_before_cart_totals' ); ?>

	<!-- Cart Totals Card -->
	<div class="bg-white border border-slate-100 rounded-xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] p-5">
		<h2 class="font-bold text-slate-800 text-sm flex items-center gap-2 mb-4">
			<?php echo spl_icon( 'file-text', 'w-4 h-4 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Tóm tắt đơn hàng', 'spl' ); ?>
		</h2>

		<div class="space-y-3 text-sm">
			<!-- Subtotal -->
			<div class="flex justify-between items-center">
				<span class="text-slate-500"><?php esc_html_e( 'Tạm tính', 'spl' ); ?></span>
				<span class="font-bold text-slate-800"><?php wc_cart_totals_subtotal_html(); ?></span>
			</div>

			<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
				<div class="flex justify-between items-center">
					<span class="text-slate-500"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
					<span class="font-semibold text-red-500"><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
				<?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>
				<div class="flex justify-between items-center">
					<span class="text-slate-500"><?php esc_html_e( 'Phí vận chuyển', 'spl' ); ?></span>
					<span class="font-semibold text-emerald-600">
						<?php
						$packages = WC()->shipping()->get_packages();
						$has_free = false;
						foreach ( $packages as $package ) {
							if ( isset( $package['rates'] ) ) {
								foreach ( $package['rates'] as $rate ) {
									if ( 0 == $rate->cost ) { // phpcs:ignore
										$has_free = true;
										break 2;
									}
								}
							}
						}
						if ( $has_free ) {
							esc_html_e( 'Miễn phí', 'spl' );
						} else {
							echo wp_kses_post( WC()->cart->get_cart_shipping_total() );
						}
						?>
					</span>
				</div>
				<?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>
			<?php endif; ?>

			<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
				<div class="flex justify-between items-center">
					<span class="text-slate-500"><?php echo esc_html( $fee->name ); ?></span>
					<span class="font-semibold text-slate-800"><?php wc_cart_totals_fee_html( $fee ); ?></span>
				</div>
			<?php endforeach; ?>

			<?php
			if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
				foreach ( WC()->cart->get_tax_totals() as $tax ) {
					?>
					<div class="flex justify-between items-center">
						<span class="text-slate-500"><?php echo esc_html( $tax->label ); ?></span>
						<span class="font-semibold text-slate-800"><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
					</div>
					<?php
				}
			}
			?>

			<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

			<!-- Total -->
			<div class="border-t border-slate-100 pt-3 flex justify-between items-center">
				<span class="font-bold text-slate-800"><?php esc_html_e( 'Tổng cộng', 'spl' ); ?></span>
				<span class="text-xl font-black text-primary"><?php wc_cart_totals_order_total_html(); ?></span>
			</div>

			<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>
		</div>

		<!-- Checkout Button -->
		<div class="mt-5 space-y-3">
			<div class="wc-proceed-to-checkout">
				<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
			</div>
			<p class="text-center text-[10px] text-slate-400 flex items-center justify-center gap-1.5">
				<?php echo spl_icon( 'shield', 'w-3.5 h-3.5 text-emerald-500' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Thanh toán an toàn & bảo mật 100%', 'spl' ); ?>
			</p>
		</div>
	</div>

	<?php do_action( 'woocommerce_after_cart_totals' ); ?>

</div>
