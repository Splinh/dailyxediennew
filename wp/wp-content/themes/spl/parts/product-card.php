<?php
/**
 * Shared product card — matches the HTML mockup (website/index.js createProductCard).
 *
 * Usage:
 *   get_template_part( 'parts/product-card', null, [ 'product' => $product ] );
 *   get_template_part( 'parts/product-card', null, [ 'id' => $product_id ] );
 *   // or inside a WC loop with no args (uses get_the_ID()).
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

$extra_classes = array_filter(
	array_map(
		'sanitize_html_class',
		preg_split( '/\s+/', (string) ( $data['class'] ?? '' ) ) ?: []
	)
);
$card_classes  = implode( ' ', array_merge( [ 'product-card', 'reveal' ], $extra_classes ) );

/** @var \WC_Product|null $card_product */
$card_product = $data['product'] ?? null;
if ( ! $card_product instanceof \WC_Product ) {
	$card_product = function_exists( 'wc_get_product' ) ? wc_get_product( $data['id'] ?? get_the_ID() ) : null;
}
if ( ! $card_product instanceof \WC_Product ) {
	return;
}

$pid       = $card_product->get_id();
$permalink = get_permalink( $pid );
$name      = $card_product->get_name();
$image_url = wp_get_attachment_image_url( $card_product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src();
// First product category name.
$cat_name = '';
$terms    = get_the_terms( $pid, 'product_cat' );
if ( $terms && ! is_wp_error( $terms ) ) {
	$cat_name = $terms[0]->name;
}

// Sale badge + prices — single price, no extra queries.
$badge          = '';
$price_old_html = '';

if ( $card_product->is_type( 'variable' ) ) {
	// Variation prices are cached in a WC transient — no extra DB queries.
	$prices = $card_product->get_variation_prices( true );
	$min_id = current( array_keys( $prices['price'] ) );
	$min_price   = (float) current( $prices['price'] );
	$min_regular = (float) ( $prices['regular_price'][ $min_id ] ?? $min_price );

	$price_current_html = wc_price( $min_price );

	if ( $min_regular > $min_price ) {
		$price_old_html = wc_price( $min_regular );
		$badge = '-' . round( ( ( $min_regular - $min_price ) / $min_regular ) * 100 ) . '%';
	}
} elseif ( $card_product->is_on_sale() ) {
	$reg  = (float) $card_product->get_regular_price();
	$sale = (float) $card_product->get_sale_price();
	$price_current_html = wc_price( wc_get_price_to_display( $card_product ) );
	$price_old_html     = wc_price( wc_get_price_to_display( $card_product, [ 'price' => $reg ] ) );
	$badge = ( $reg > 0 && $sale > 0 )
		? '-' . round( ( ( $reg - $sale ) / $reg ) * 100 ) . '%'
		: __( 'Giảm giá', 'spl' );
} else {
	$price_current_html = $card_product->get_price_html();
}

$purchasable = $card_product->is_purchasable() && $card_product->is_in_stock() && ! $card_product->is_type( 'variable' );
?>
<div class="<?php echo esc_attr( $card_classes ); ?>">
	<a href="<?php echo esc_url( $permalink ); ?>" class="product-card__link">
		<div class="product-card__image">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
			<?php if ( $badge ) : ?>
				<span class="product-card__badge"><?php echo esc_html( $badge ); ?></span>
			<?php endif; ?>
			<?php /* TODO: tạm ẩn Yêu thích (wishlist) + Xem nhanh (quick view) — bỏ comment để bật lại. ?>
			<div class="product-card__actions">
				<button type="button" class="product-card__action-btn wishlist-btn" aria-label="<?php esc_attr_e( 'Yêu thích', 'spl' ); ?>" onclick="event.preventDefault();event.stopPropagation();">
					<svg class="icon" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
				</button>
				<button type="button" class="product-card__action-btn" data-wc-quickview data-product-id="<?php echo esc_attr( $pid ); ?>" aria-label="<?php esc_attr_e( 'Xem nhanh', 'spl' ); ?>">
					<svg class="icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
				</button>
			</div>
			<?php */ ?>
		</div>
		<div class="product-card__body">
			<?php if ( $cat_name ) : ?>
				<span class="product-card__category"><?php echo esc_html( $cat_name ); ?></span>
			<?php endif; ?>
			<h3 class="product-card__name"><?php echo esc_html( $name ); ?></h3>
			<div class="product-card__price">
				<span class="product-card__price-current"><?php echo wp_kses_post( $price_current_html ); ?></span>
				<?php if ( $price_old_html ) : ?>
					<span class="product-card__price-old"><?php echo wp_kses_post( $price_old_html ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</a>
	<?php if ( $purchasable ) : ?>
		<button type="button" class="product-card__add-to-cart add-cart-btn" data-product-id="<?php echo esc_attr( $pid ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s product name */ __( 'Thêm %s vào giỏ hàng', 'spl' ), $name ) ); ?>">
			<svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
			<?php esc_html_e( 'Thêm vào giỏ', 'spl' ); ?>
		</button>
	<?php else : ?>
		<a href="<?php echo esc_url( $permalink ); ?>" class="product-card__add-to-cart">
			<svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
			<?php esc_html_e( 'Xem chi tiết', 'spl' ); ?>
		</a>
	<?php endif; ?>
</div>
