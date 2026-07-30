<?php
/**
 * Storefront WooCommerce UI integrations.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_add_to_cart_fragments', 'spl_cart_fragments', 20 );
add_filter( 'woocommerce_widget_cart_item_quantity', 'spl_mini_cart_quantity', 20, 3 );
add_filter( 'loop_shop_per_page', function() { return 16; }, 20 );
add_action( 'woocommerce_product_query', function( $q ) {
	if ( ! is_admin() && $q->is_main_query() ) {
		if ( is_product_category( 'san-pham-moi' ) ) {
			$q->set( 'orderby', 'date' );
			$q->set( 'order', 'DESC' );
		} else {
			$q->set( 'orderby', 'menu_order title' );
			$q->set( 'order', 'ASC' );
		}
	}
}, 20 );

// Auto-assign category "Sản phẩm mới" when publishing/saving a new product
add_action( 'save_post_product', function( $post_id, $post, $update ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( empty( $post ) || $post->post_status !== 'publish' ) return;

	$term = get_term_by( 'slug', 'san-pham-moi', 'product_cat' );
	if ( $term ) {
		wp_set_object_terms( $post_id, (int) $term->term_id, 'product_cat', true );
	}
}, 10, 3 );
add_action( 'wp_ajax_spl_update_mini_cart_qty', 'spl_update_mini_cart_quantity' );
add_action( 'wp_ajax_nopriv_spl_update_mini_cart_qty', 'spl_update_mini_cart_quantity' );
add_action( 'wp_ajax_spl_search_products', 'spl_ajax_search_products' );
add_action( 'wp_ajax_nopriv_spl_search_products', 'spl_ajax_search_products' );
add_action( 'wp_footer', 'spl_render_search_config', 4 );
add_action( 'woocommerce_product_query', 'spl_apply_archive_price_range' );
add_action( 'wp', 'spl_register_woocommerce_ui_hooks' );

/**
 * Register storefront hooks after WooCommerce conditionals are available.
 */
function spl_register_woocommerce_ui_hooks(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_action( 'spl_footer_action', 'spl_render_mini_cart', 5 );
	add_action( 'wp_footer', 'spl_render_mini_cart_config', 4 );

	if ( is_cart() || is_checkout() ) {
		add_filter( 'the_content', 'spl_prepend_checkout_steps_to_content', 5 );
		add_action( 'wp_enqueue_scripts', 'spl_enqueue_commerce_css' );
	}

	if ( is_cart() ) {
		add_action( 'woocommerce_before_cart_table', 'spl_render_cart_intro', 5 );
		add_action( 'woocommerce_before_cart', 'spl_open_cart_grid', 20 );
		add_action( 'woocommerce_before_cart_collaterals', 'spl_switch_cart_grid_column', 5 );
		add_action( 'woocommerce_before_cart_totals', 'spl_render_cart_coupon_card', 5 );
		add_action( 'woocommerce_after_cart', 'spl_close_cart_grid', 5 );
		add_action( 'woocommerce_after_cart_totals', 'spl_render_cart_shipping_info', 10 );
		add_action( 'woocommerce_after_cart_totals', 'spl_render_cart_support', 20 );
	}

	if ( is_checkout() ) {
		add_action( 'woocommerce_checkout_before_customer_details', 'spl_open_checkout_grid', 1 );
		add_action( 'woocommerce_checkout_after_customer_details', 'spl_switch_checkout_grid_column', 99 );
		add_action( 'woocommerce_checkout_after_order_review', 'spl_render_checkout_trust_badges', 5 );
		add_action( 'woocommerce_checkout_after_order_review', 'spl_close_checkout_grid', 99 );
	}
}

/**
 * Refresh the header badge and mini-cart body after cart mutations.
 *
 * @param array<string, string> $fragments Existing fragments.
 *
 * @return array<string, string>
 */
function spl_cart_fragments( array $fragments ): array {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$total = WC()->cart ? WC()->cart->get_cart_total() : wc_price( 0 );

	// Header + mobile bottom-nav badge (all elements with data-cart-count).
	$badge = '<span class="bg-sale text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full absolute -top-2.5 -right-4 shadow-sm" data-cart-count>' . esc_html( $count ) . '</span>';
	$fragments['[data-cart-count]'] = $badge;

	// Mini-cart modal count badge.
	$fragments['.dxd-minicart__count'] = '<span class="dxd-minicart__count" data-cart-count>' . esc_html( $count ) . '</span>';

	// Mini-cart total price.
	$fragments['[data-cart-total]'] = '<span class="dxd-minicart__total-price" data-cart-total>' . wp_kses_post( $total ) . '</span>';

	// Legacy badge (backward compat).
	$fragments['#cart-badge'] = '<span class="btn-icon__badge" id="cart-badge">' . esc_html( $count ) . '</span>';

	// Fragment-replaceable cart content.
	ob_start();
	get_template_part( 'template-parts/woocommerce/mini-cart-content' );
	$fragments['.mini-cart-offcanvas__content'] = (string) ob_get_clean();

	return $fragments;
}

/**
 * Add quantity controls to mini-cart rows.
 *
 * @param string $content       Default quantity HTML.
 * @param array  $cart_item     Cart row.
 * @param string $cart_item_key Cart row key.
 */
function spl_mini_cart_quantity( string $content, array $cart_item, string $cart_item_key ): string {
	$product = $cart_item['data'] ?? null;
	if ( ! $product instanceof WC_Product ) {
		return $content;
	}

	$price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $product ), $cart_item, $cart_item_key );

	ob_start();
	?>
	<div class="mini-cart-quantity">
		<span class="mini-cart-quantity__price"><?php echo esc_html( $cart_item['quantity'] ); ?> &times; <?php echo wp_kses_post( $price ); ?></span>
		<div class="mini-cart-qty" data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>">
			<button type="button" data-mini-cart-minus aria-label="<?php esc_attr_e( 'Giảm số lượng', 'spl' ); ?>">-</button>
			<input type="number" value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" min="0" inputmode="numeric" aria-label="<?php esc_attr_e( 'Số lượng', 'spl' ); ?>" />
			<button type="button" data-mini-cart-plus aria-label="<?php esc_attr_e( 'Tăng số lượng', 'spl' ); ?>">+</button>
		</div>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * AJAX quantity update used by the off-canvas cart.
 */
function spl_update_mini_cart_quantity(): void {
	check_ajax_referer( 'spl_mini_cart', 'nonce' );

	$key      = wc_clean( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
	$quantity = max( 0, absint( $_POST['quantity'] ?? 0 ) );

	if ( ! WC()->cart || ! $key || ! WC()->cart->get_cart_item( $key ) ) {
		wp_send_json_error( [ 'message' => __( 'Sản phẩm trong giỏ hàng không hợp lệ.', 'spl' ) ], 400 );
	}

	WC()->cart->set_quantity( $key, $quantity, true );
	WC_AJAX::get_refreshed_fragments();
}

/**
 * Render mini cart once near the end of the document.
 */
function spl_render_mini_cart(): void {
	get_template_part( 'template-parts/woocommerce/mini-cart-offcanvas' );
}

/**
 * Browser config for mini-cart AJAX updates.
 */
function spl_render_mini_cart_config(): void {
	printf(
		'<script>window.splMiniCart=%s;</script>',
		wp_json_encode(
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'spl_mini_cart' ),
			]
		)
	);
}

/**
 * Browser config for the header live product search.
 */
function spl_render_search_config(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	printf(
		'<script>window.splSearch=%s;</script>',
		wp_json_encode(
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'spl_search' ),
			]
		)
	);
}

/**
 * AJAX: live product search for the header search bar.
 *
 * Returns matching products, or the newest products when the term is empty.
 */
function spl_ajax_search_products(): void {
	check_ajax_referer( 'spl_search', 'nonce' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_success( [ 'items' => [], 'term' => '' ] );
	}

	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

	$query_args = [
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $term !== '' ? 6 : 4,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'tax_query'           => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			[
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'exclude-from-search',
				'operator' => 'NOT IN',
			],
		],
	];

	if ( $term !== '' ) {
		$query_args['s'] = $term;
	}

	$search = new WP_Query( $query_args );
	$items  = [];

	foreach ( $search->posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$items[] = [
			'id'    => $product->get_id(),
			'title' => $product->get_name(),
			'url'   => get_permalink( $product->get_id() ),
			'price' => $product->get_price_html(),
			'image' => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
		];
	}

	wp_send_json_success(
		[
			'items'   => $items,
			'term'    => $term,
			'is_seed' => $term === '',
		]
	);
}

/**
 * Apply the compact archive price selector to WooCommerce's product query.
 *
 * @param WP_Query $query Main product query.
 */
function spl_apply_archive_price_range( WP_Query $query ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$range = isset( $_GET['price_range'] ) ? sanitize_text_field( wp_unslash( $_GET['price_range'] ) ) : '';
	if ( ! $range || ! str_contains( $range, '-' ) ) {
		return;
	}

	[ $min, $max ] = array_map( 'absint', explode( '-', $range, 2 ) );
	$clause        = [
		'key'  => '_price',
		'type' => 'NUMERIC',
	];

	if ( $max > 0 ) {
		$clause['value']   = [ $min, $max ];
		$clause['compare'] = 'BETWEEN';
	} else {
		$clause['value']   = $min;
		$clause['compare'] = '>=';
	}

	$meta_query   = (array) $query->get( 'meta_query' );
	$meta_query[] = $clause;
	$query->set( 'meta_query', $meta_query );
}

/**
 * Shared cart and checkout progress.
 */
function spl_render_checkout_steps(): void {
	get_template_part( 'template-parts/woocommerce/checkout-steps' );
}

/**
 * Prepend progress steps and wrap content in .container for cart & checkout.
 *
 * @param string $content Current page content.
 */
function spl_prepend_checkout_steps_to_content( string $content ): string {
	if ( ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	static $rendered = false;
	if ( $rendered ) {
		return $content;
	}

	$rendered = true;
	ob_start();
	spl_render_checkout_steps();

	return (string) ob_get_clean() . $content;
}

function spl_render_cart_intro(): void {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	?>
	<div class="flex items-center gap-2.5 mb-5 md:mb-8">
		<span class="w-1 h-6 bg-primary rounded-full"></span>
		<h1 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight"><?php esc_html_e( 'Giỏ hàng của bạn', 'spl' ); ?></h1>
		<?php if ( $count ) : ?>
			<span class="bg-primary/10 text-primary text-[11px] font-semibold px-2.5 py-0.5 rounded-full"><?php echo esc_html( $count . ' sản phẩm' ); ?></span>
		<?php endif; ?>
	</div>
	<?php
}

function spl_open_cart_grid(): void {
	echo '<div class="max-w-6xl mx-auto px-3 sm:px-4 py-6 md:py-10"><div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8"><section class="lg:col-span-2">';
}

function spl_switch_cart_grid_column(): void {
	echo '</section><aside class="lg:col-span-1 space-y-5">';
}

function spl_close_cart_grid(): void {
	echo '</aside></div></div>';
}

/**
 * Coupon card in cart sidebar (matches gio-hang.html mockup).
 *
 * Standalone form that POSTs to cart URL — WC handles coupon natively.
 */
function spl_render_cart_coupon_card(): void {
	if ( ! wc_coupons_enabled() ) {
		return;
	}
	?>
	<div class="bg-white border border-slate-100 rounded-xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] p-5 mb-5">
		<h3 class="font-bold text-slate-800 text-sm flex items-center gap-2 mb-3">
			<?php echo spl_icon( 'tag', 'w-4 h-4 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Mã giảm giá', 'spl' ); ?>
		</h3>
		<form method="post" action="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<div class="flex gap-2">
				<input
					type="text"
					name="coupon_code"
					id="sidebar_coupon_code"
					value=""
					placeholder="<?php esc_attr_e( 'Nhập mã coupon', 'spl' ); ?>"
					class="flex-1 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-primary focus:bg-white transition-all"
				/>
				<button
					type="submit"
					name="apply_coupon"
					value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"
					class="bg-primary hover:bg-primary-hover text-white px-4 py-2.5 rounded-lg text-xs font-bold transition-colors whitespace-nowrap"
				><?php esc_html_e( 'Áp dụng', 'spl' ); ?></button>
			</div>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		</form>
		<?php
		// Show applied coupons.
		$coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
		if ( $coupons ) :
			?>
			<div class="mt-3 space-y-1.5">
				<?php foreach ( $coupons as $code ) : ?>
					<div class="flex items-center justify-between bg-emerald-50 rounded-lg px-3 py-2">
						<span class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
							<?php echo spl_icon( 'check-circle', 'w-3.5 h-3.5 text-emerald-500' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo esc_html( $code ); ?>
						</span>
						<a
							href="<?php echo esc_url( add_query_arg( 'remove_coupon', rawurlencode( $code ), wc_get_cart_url() ) ); ?>"
							class="text-xs text-red-400 hover:text-red-600 font-semibold transition-colors no-underline"
						><?php echo spl_icon( 'x', 'w-3.5 h-3.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

function spl_open_checkout_grid(): void {
	echo '<div class="max-w-6xl mx-auto px-4 py-8 md:py-10"><div class="grid grid-cols-1 lg:grid-cols-12 gap-8"><section class="lg:col-span-7 space-y-6">';
}

function spl_switch_checkout_grid_column(): void {
	echo '</section><aside class="lg:col-span-5 lg:sticky lg:top-24 lg:self-start"><div class="bg-white border border-slate-100 rounded-xl shadow-premium p-5 md:p-6 space-y-5">';
}

function spl_close_checkout_grid(): void {
	echo '</div></aside></div></div>';
}

/**
 * Enqueue commerce styles on cart/checkout pages (Vite-built).
 */
function spl_enqueue_commerce_css(): void {
	\SPL\Core\Asset::enqueueCSS( 'commerce.scss' );
}

/**
 * Shipping info panel below cart totals (matches gio-hang.html sidebar).
 */
function spl_render_cart_shipping_info(): void {
	?>
	<div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 border border-emerald-200/50 rounded-xl p-5 mt-5">
		<h4 class="font-bold text-emerald-800 text-sm flex items-center gap-2 mb-3">
			<?php echo spl_icon( 'truck', 'w-4 h-4 text-emerald-600' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Chính sách giao hàng', 'spl' ); ?>
		</h4>
		<ul class="space-y-2.5 list-none p-0 m-0">
			<li class="flex items-start gap-2 text-xs text-emerald-700">
				<?php echo spl_icon( 'check-circle', 'w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><strong><?php esc_html_e( 'Miễn phí', 'spl' ); ?></strong> <?php esc_html_e( 'giao xe trong bán kính 10km', 'spl' ); ?></span>
			</li>
			<li class="flex items-start gap-2 text-xs text-emerald-700">
				<?php echo spl_icon( 'check-circle', 'w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Giao toàn quốc qua vận chuyển chuyên dụng', 'spl' ); ?></span>
			</li>
			<li class="flex items-start gap-2 text-xs text-emerald-700">
				<?php echo spl_icon( 'check-circle', 'w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Hỗ trợ trả góp 0% lãi suất, duyệt 15 phút', 'spl' ); ?></span>
			</li>
		</ul>
	</div>
	<?php
}

/**
 * Support contact card below shipping info.
 */
function spl_render_cart_support(): void {
	?>
	<div class="bg-white border border-slate-100 rounded-xl shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] p-5 mt-5">
		<h4 class="font-bold text-slate-800 text-sm flex items-center gap-2 mb-3">
			<?php echo spl_icon( 'headphones', 'w-4 h-4 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Cần hỗ trợ?', 'spl' ); ?>
		</h4>
		<div class="space-y-2">
			<a href="tel:0933505222" class="flex items-center gap-3 p-3 bg-primary/5 hover:bg-primary/10 rounded-lg transition-colors no-underline">
				<?php echo spl_icon( 'phone', 'w-4 h-4 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div>
					<span class="font-bold text-slate-800 text-xs block">0933 505 222</span>
					<p class="text-[10px] text-slate-400 m-0"><?php esc_html_e( 'Hotline 24/7', 'spl' ); ?></p>
				</div>
			</a>
			<a href="https://zalo.me/0933505222" target="_blank" rel="noopener" class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors no-underline">
				<?php echo spl_icon( 'message-circle', 'w-4 h-4 text-blue-500' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div>
					<span class="font-bold text-slate-800 text-xs block"><?php esc_html_e( 'Chat Zalo', 'spl' ); ?></span>
					<p class="text-[10px] text-slate-400 m-0"><?php esc_html_e( 'Phản hồi nhanh 5 phút', 'spl' ); ?></p>
				</div>
			</a>
		</div>
	</div>
	<?php
}

/**
 * Trust badges after checkout order review (matches thanh-toan.html).
 */
function spl_render_checkout_trust_badges(): void {
	?>
	<div class="grid grid-cols-3 gap-3 mt-6 pt-5 border-t border-slate-100">
		<div class="text-center">
			<div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-1.5">
				<?php echo spl_icon( 'shield', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<p class="text-[10px] font-bold text-slate-600 m-0"><?php esc_html_e( 'Bảo mật 100%', 'spl' ); ?></p>
		</div>
		<div class="text-center">
			<div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center mx-auto mb-1.5">
				<?php echo spl_icon( 'refresh-cw', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<p class="text-[10px] font-bold text-slate-600 m-0"><?php esc_html_e( 'Đổi trả 7 ngày', 'spl' ); ?></p>
		</div>
		<div class="text-center">
			<div class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-1.5">
				<?php echo spl_icon( 'headphones', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<p class="text-[10px] font-bold text-slate-600 m-0"><?php esc_html_e( 'Hỗ trợ 24/7', 'spl' ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * Translate WooCommerce Checkout & Cart text strings into Vietnamese.
 */
add_filter( 'gettext', 'spl_translate_woocommerce_strings', 20, 3 );
function spl_translate_woocommerce_strings( string $translated_text, string $text, string $domain ): string {
	if ( $domain !== 'woocommerce' ) {
		return $translated_text;
	}

	switch ( $text ) {
		case 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.':
		case 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.':
			return 'Rất tiếc, hiện tại chưa có phương thức thanh toán tự động khả dụng trên hệ thống. Vui lòng liên hệ Hotline 0933 505 222 để được tư vấn và hỗ trợ giao xe nhanh nhất!';

		case 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.':
		case 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our privacy policy.':
			return 'Thông tin cá nhân của bạn sẽ được sử dụng để xử lý đơn hàng, bảo mật dữ liệu và hỗ trợ trải nghiệm tốt nhất theo đúng chính sách bảo mật của chúng tôi.';

		case 'Place order':
		case 'Place Order':
		case 'PLACE ORDER':
			return 'XÁC NHẬN ĐẶT HÀNG';

		case 'Billing details':
			return 'Thông tin thanh toán';

		case 'Your order':
			return 'Đơn hàng của bạn';

		case 'Additional information':
			return 'Thông tin bổ sung';

		case 'Order notes':
			return 'Ghi chú đơn hàng';

		case 'Order notes (optional)':
			return 'Ghi chú đơn hàng (tùy chọn)';

		case 'Product':
			return 'Sản phẩm';

		case 'Subtotal':
			return 'Tạm tính';

		case 'Total':
			return 'Tổng cộng';

		case 'Have a coupon?':
			return 'Bạn có mã ưu đãi?';

		case 'Click here to enter your code':
			return 'Nhấp vào đây để nhập mã';

		case 'First name':
			return 'Tên';

		case 'Last name':
			return 'Họ';

		case 'Phone':
			return 'Số điện thoại';

		case 'Email address':
			return 'Địa chỉ Email';

		case 'Town / City':
			return 'Tỉnh / Thành phố';

		case 'Street address':
			return 'Số nhà, tên đường, phường/xã';
	}

	return $translated_text;
}

