<?php
/**
 * Storefront WooCommerce UI integrations.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_add_to_cart_fragments', 'spl_cart_fragments', 20 );
add_filter( 'woocommerce_widget_cart_item_quantity', 'spl_mini_cart_quantity', 20, 3 );
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
	}

	if ( is_cart() ) {
		add_action( 'woocommerce_before_cart_table', 'spl_render_cart_intro', 5 );
		add_action( 'woocommerce_before_cart', 'spl_open_cart_grid', 20 );
		add_action( 'woocommerce_before_cart_collaterals', 'spl_switch_cart_grid_column', 5 );
		add_action( 'woocommerce_after_cart', 'spl_close_cart_grid', 5 );
	}

	if ( is_checkout() ) {
		add_action( 'woocommerce_checkout_before_customer_details', 'spl_open_checkout_grid', 1 );
		add_action( 'woocommerce_checkout_after_customer_details', 'spl_switch_checkout_grid_column', 99 );
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

	$fragments['#cart-badge'] = '<span class="btn-icon__badge" id="cart-badge">' . esc_html( $count ) . '</span>';

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

	return '<div class="container">' . (string) ob_get_clean() . $content . '</div>';
}

function spl_render_cart_intro(): void {
	?>
	<div class="commerce-panel__intro">
		<h1><?php esc_html_e( 'Giỏ hàng của bạn', 'spl' ); ?></h1>
		<p><?php esc_html_e( 'Kiểm tra sản phẩm, số lượng và ưu đãi trước khi thanh toán.', 'spl' ); ?></p>
	</div>
	<?php
}

function spl_open_cart_grid(): void {
	echo '<div class="commerce-shell"><div class="commerce-cart-grid"><section class="commerce-panel commerce-cart-grid__main">';
}

function spl_switch_cart_grid_column(): void {
	echo '</section><aside class="commerce-panel commerce-cart-grid__aside">';
}

function spl_close_cart_grid(): void {
	echo '</aside></div></div>';
}

function spl_open_checkout_grid(): void {
	echo '<div class="commerce-checkout-grid"><section class="commerce-panel commerce-checkout-grid__main">';
}

function spl_switch_checkout_grid_column(): void {
	echo '</section><aside class="commerce-panel commerce-checkout-grid__aside">';
}

function spl_close_checkout_grid(): void {
	echo '</aside></div>';
}
