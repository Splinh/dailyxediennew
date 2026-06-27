<?php
/**
 * Empty cart page — DailyXeDien override.
 *
 * Premium empty-state design matching htmlmau/gio-hang.html.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="max-w-6xl mx-auto px-4 py-16 md:py-24">

	<?php
	/*
	 * Notices (e.g. "Product removed. Undo?")
	 * @hooked wc_empty_cart_message — 10
	 */
	do_action( 'woocommerce_cart_is_empty' );
	?>

	<!-- Empty State -->
	<div class="text-center max-w-lg mx-auto">
		<!-- Icon -->
		<div class="w-28 h-28 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-8">
			<?php echo spl_icon( 'shopping-cart', 'w-12 h-12 text-slate-300' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<!-- Title -->
		<h2 class="text-xl md:text-2xl font-black text-slate-700 mb-3 tracking-tight">
			<?php esc_html_e( 'Giỏ hàng trống', 'spl' ); ?>
		</h2>

		<!-- Description -->
		<p class="text-sm text-slate-400 mb-8 leading-relaxed max-w-md mx-auto">
			<?php esc_html_e( 'Bạn chưa có sản phẩm nào trong giỏ hàng. Hãy khám phá bộ sưu tập xe điện và phụ kiện chính hãng của chúng tôi!', 'spl' ); ?>
		</p>

		<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
			<a
				class="inline-flex items-center gap-2.5 bg-primary hover:bg-primary-hover text-white font-bold px-8 py-3.5 rounded-xl shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5 active:scale-[0.98] text-sm no-underline"
				href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>"
			>
				<?php echo spl_icon( 'arrow-left', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Khám phá sản phẩm', 'spl' ) ) ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
