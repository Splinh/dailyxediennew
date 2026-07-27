<?php
/**
 * Cart Page — DailyXeDien override.
 *
 * Card-based layout matching htmlmau/gio-hang.html design.
 * Uses Tailwind utility classes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' ); ?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<div class="flex flex-col gap-3 md:gap-4">
		<?php
		do_action( 'woocommerce_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
			$visible    = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );

			if ( $_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible ) {
				$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
				?>
				<div class="cart_item bg-white border border-slate-100 rounded-xl shadow-premium p-4 md:p-5 flex gap-4 md:gap-5 group hover:shadow-hover-card transition-all duration-300 <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', '', $cart_item, $cart_item_key ) ); ?>">
					<!-- Product Image -->
					<?php if ( $product_permalink ) : ?>
						<a href="<?php echo esc_url( $product_permalink ); ?>" class="w-24 h-24 md:w-28 md:h-28 rounded-lg overflow-hidden bg-slate-50 shrink-0 flex items-center justify-center">
							<?php echo $thumbnail; // phpcs:ignore ?>
						</a>
					<?php else : ?>
						<div class="w-24 h-24 md:w-28 md:h-28 rounded-lg overflow-hidden bg-slate-50 shrink-0 flex items-center justify-center">
							<?php echo $thumbnail; // phpcs:ignore ?>
						</div>
					<?php endif; ?>

					<!-- Product Info -->
					<div class="flex-1 min-w-0 flex flex-col justify-between">
						<div>
							<?php
							// Brand/category label.
							$cats = get_the_terms( $product_id, 'product_cat' );
							if ( $cats && ! is_wp_error( $cats ) ) :
								?>
								<span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider"><?php echo esc_html( $cats[0]->name ); ?></span>
							<?php endif; ?>

							<h3 class="font-bold text-slate-800 text-sm md:text-base leading-snug group-hover:text-primary transition-colors">
								<?php if ( $product_permalink ) : ?>
									<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_name ); ?></a>
								<?php else : ?>
									<?php echo wp_kses_post( $product_name ); ?>
								<?php endif; ?>
							</h3>

							<?php
							// Variation data (color, size, etc.).
							$item_data = wc_get_formatted_cart_item_data( $cart_item );
							if ( $item_data ) :
								?>
								<p class="text-xs text-slate-400 mt-1"><?php echo $item_data; // phpcs:ignore ?></p>
							<?php endif; ?>
						</div>

						<!-- Bottom row: qty + remove + price -->
						<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mt-3">
							<div class="flex items-center gap-3">
								<!-- Quantity Controls -->
								<div class="dxd-cart-qty flex items-center border border-slate-200 rounded-lg overflow-hidden">
									<?php
									if ( $_product->is_sold_individually() ) {
										$min_quantity = 1;
										$max_quantity = 1;
									} else {
										$min_quantity = 0;
										$max_quantity = $_product->get_max_purchase_quantity();
									}

									$product_quantity = woocommerce_quantity_input(
										[
											'input_name'  => "cart[{$cart_item_key}][qty]",
											'input_value' => $cart_item['quantity'],
											'max_value'   => $max_quantity,
											'min_value'   => $min_quantity,
											'product_name' => $product_name,
										],
										$_product,
										false
									);

									echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore
									?>
								</div>

								<!-- Remove -->
				<?php
				echo apply_filters( // phpcs:ignore
					'woocommerce_cart_item_remove_link',
					sprintf(
						'<a role="button" href="%s" class="remove-item inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-red-50 text-slate-400 hover:text-red-500 transition-all" aria-label="%s" data-product_id="%s" data-product_sku="%s" title="%s">%s</a>',
						esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
						esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
						esc_attr( $product_id ),
						esc_attr( $_product->get_sku() ),
						esc_attr__( 'Xóa sản phẩm', 'spl' ),
						spl_icon( 'trash-2', 'w-5 h-5' )
					),
					$cart_item_key
				);
				?>
							</div>

							<!-- Price -->
							<div class="text-right">
								<span class="text-base md:text-lg font-extrabold text-slate-900">
									<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore ?>
								</span>
								<?php if ( $_product->is_on_sale() ) : ?>
									<span class="text-[11px] text-slate-400 line-through ml-1.5">
										<?php echo wc_price( $_product->get_regular_price() * $cart_item['quantity'] ); // phpcs:ignore ?>
									</span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
		}

		do_action( 'woocommerce_cart_contents' );
		?>
	</div>

	<!-- Cart Actions Row -->
	<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-5 mt-2">
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="text-sm font-semibold text-primary hover:text-primary-hover transition-colors flex items-center gap-2">
			<?php echo spl_icon( 'arrow-left', 'w-3.5 h-3.5' ); // phpcs:ignore ?>
			<?php esc_html_e( 'Tiếp tục mua sắm', 'spl' ); ?>
		</a>

		<button type="submit" class="hidden" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"></button>

		<?php do_action( 'woocommerce_cart_actions' ); ?>
		<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
	</div>

	<?php do_action( 'woocommerce_after_cart_contents' ); ?>
	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

<div class="cart-collaterals">
	<?php do_action( 'woocommerce_cart_collaterals' ); ?>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
