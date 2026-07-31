<?php
/**
 * Shared product card — dailyxedien.vn.
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
$card_classes  = implode( ' ', array_merge( [ 'bg-white border border-slate-200/90 hover:border-primary/50 rounded-xl overflow-hidden shadow-[0_2px_10px_rgba(0,0,0,0.04)] hover:shadow-[0_12px_24px_rgba(7,88,183,0.12)] transition-all duration-300 flex flex-col justify-between group relative' ], $extra_classes ) );

/** @var \WC_Product|null $card_product */
$card_product = $data['product'] ?? null;
if ( ! $card_product instanceof \WC_Product ) {
	$card_product = function_exists( 'wc_get_product' ) ? wc_get_product( $data['id'] ?? get_the_ID() ) : null;
}
if ( ! $card_product instanceof \WC_Product ) {
	return;
}

$pid = $card_product->get_id();

static $spl_product_card_cache = [];

$cached_card_data = null;
if ( isset( $spl_product_card_cache[ $pid ] ) ) {
	$cached_card_data = $spl_product_card_cache[ $pid ];
} else {
	$transient_key    = 'spl_pcard_' . $pid;
	$cached_card_data = get_transient( $transient_key );
	if ( is_array( $cached_card_data ) ) {
		$spl_product_card_cache[ $pid ] = $cached_card_data;
	}
}

if ( is_array( $cached_card_data ) ) {
	[
		'permalink'          => $permalink,
		'name'               => $name,
		'image_url'          => $image_url,
		'cat_name'           => $cat_name,
		'badge'              => $badge,
		'price_current_html' => $price_current_html,
		'price_old_html'     => $price_old_html,
		'purchasable'        => $purchasable,
		'average_rating'     => $average_rating,
		'total_sales'        => $total_sales,
	] = $cached_card_data;
} else {
	$permalink = get_permalink( $pid );
	$name      = $card_product->get_name();
	$image_url = wp_get_attachment_image_url( $card_product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src();

	$cat_name = '';
	$terms    = get_the_terms( $pid, 'product_cat' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$cat_name = $terms[0]->name;
	}

	$badge          = '';
	$price_old_html = '';

	if ( $card_product->is_type( 'variable' ) ) {
		$prices = $card_product->get_variation_prices( true );

		if ( empty( $prices['price'] ) ) {
			$price_current_html = $card_product->get_price_html();
		} else {
			$min_id      = array_key_first( $prices['price'] );
			$min_price   = (float) $prices['price'][ $min_id ];
			$min_regular = (float) ( $prices['regular_price'][ $min_id ] ?? $min_price );

			$price_current_html = wc_price( $min_price );

			if ( $min_regular > $min_price ) {
				$price_old_html = wc_price( $min_regular );
				$badge          = '-' . round( ( ( $min_regular - $min_price ) / $min_regular ) * 100 ) . '%';
			}
		}
	} elseif ( $card_product->is_on_sale() ) {
		$reg                = (float) $card_product->get_regular_price();
		$sale               = (float) $card_product->get_sale_price();
		$price_current_html = wc_price( wc_get_price_to_display( $card_product ) );
		$price_old_html     = wc_price( wc_get_price_to_display( $card_product, [ 'price' => $reg ] ) );
		$badge              = ( $reg > 0 && $sale > 0 )
			? '-' . round( ( ( $reg - $sale ) / $reg ) * 100 ) . '%'
			: __( 'Giảm giá', 'spl' );
	} else {
		$price_current_html = $card_product->get_price_html();
	}

	// Dynamic badges (Hot, Mới)
	$is_new      = ( time() - get_post_time( 'U', false, $pid ) ) < ( 30 * DAY_IN_SECONDS );
	$is_featured = $card_product->is_featured();
	if ( ! $badge ) {
		if ( $is_featured ) {
			$badge = __( 'Hot', 'spl' );
		} elseif ( $is_new ) {
			$badge = __( 'Mới', 'spl' );
		}
	}

	$purchasable    = $card_product->is_purchasable() && $card_product->is_in_stock() && ! $card_product->is_type( 'variable' );
	$average_rating = $card_product->get_average_rating();
	$total_sales    = $card_product->get_total_sales();

	$cached_card_data = compact(
		'permalink',
		'name',
		'image_url',
		'cat_name',
		'badge',
		'price_current_html',
		'price_old_html',
		'purchasable',
		'average_rating',
		'total_sales'
	);

	$spl_product_card_cache[ $pid ] = $cached_card_data;
	set_transient( 'spl_pcard_' . $pid, $cached_card_data, 12 * HOUR_IN_SECONDS );
}

$sales_formatted = $total_sales >= 1000 ? round( $total_sales / 1000, 1 ) . 'k' : $total_sales;
$stars_count     = $average_rating > 0 ? round( $average_rating ) : 5;
?>
<div class="<?php echo esc_attr( $card_classes ); ?>">
	<div class="absolute top-2.5 left-2.5 flex flex-col gap-1 z-10 items-start">
		<?php if ( $badge ) :
			$badge_color = ( stripos( $badge, 'hot' ) !== false || stripos( $badge, '-' ) !== false ) ? 'bg-red-500' : 'bg-emerald-500';
			?>
			<span class="<?php echo esc_attr( $badge_color ); ?> text-white font-black text-[9px] md:text-[10px] px-2 py-0.5 md:px-2.5 md:py-1 rounded-md shadow-sm uppercase tracking-wider"><?php echo esc_html( $badge ); ?></span>
		<?php endif; ?>
	</div>

	<a href="<?php echo esc_url( $permalink ); ?>" class="block">
		<div class="bg-slate-50/60 flex items-center justify-center h-40 md:h-52 p-3 relative overflow-hidden group-hover:bg-slate-50 transition-colors">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="300" height="300" loading="lazy" class="max-h-full max-w-full object-contain transform group-hover:scale-105 transition-transform duration-300" style="aspect-ratio: 1;" />
		</div>
	</a>

	<div class="p-3.5 md:p-4 flex-grow flex flex-col justify-between">
		<div>
			<?php if ( $cat_name ) : ?>
				<span class="product-card-cat-name text-slate-500 font-bold uppercase tracking-wider block mb-0.5"><?php echo esc_html( $cat_name ); ?></span>
			<?php endif; ?>
			<h3 class="font-extrabold text-slate-900 text-xs md:text-sm line-clamp-2 mt-1 group-hover:text-primary transition-colors leading-snug">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $name ); ?></a>
			</h3>
			<div class="flex flex-wrap items-center gap-y-1 gap-x-0.5 mt-1.5 text-amber-400 text-[10px]" aria-label="<?php echo esc_attr( sprintf( __( 'Đánh giá %s sao', 'spl' ), $stars_count ) ); ?>">
				<?php for ( $i = 0; $i < 5; $i++ ) :
					$fill_class = $i < $stars_count ? 'fill-current' : 'text-slate-200';
					?>
					<svg class="w-3 h-3 <?php echo esc_attr( $fill_class ); ?>" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
				<?php endfor; ?>
				<span class="text-slate-500 ml-1.5 font-bold text-[10px]"><?php echo esc_html( sprintf( __( 'Đã bán %s', 'spl' ), $sales_formatted ) ); ?></span>
			</div>
		</div>

		<div class="mt-3.5">
			<div class="flex flex-wrap items-baseline gap-1 md:gap-2">
				<span class="text-sm md:text-base font-black text-red-600"><?php echo wp_kses_post( $price_current_html ); ?></span>
				<?php if ( $price_old_html ) : ?>
					<span class="text-[10px] md:text-xs text-slate-400 line-through"><?php echo wp_kses_post( $price_old_html ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( $purchasable ) : ?>
				<div class="flex items-center gap-1.5 mt-3 max-w-[200px]">
					<a href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() . '?add-to-cart=' . $pid : '#' ); ?>" class="flex-1 bg-primary hover:bg-primary-hover active:scale-95 text-white text-[11px] md:text-xs font-bold min-h-[36px] md:min-h-[38px] px-3 rounded-md transition-all text-center flex items-center justify-center shadow-sm shadow-primary/20"><?php esc_html_e( 'Mua ngay', 'spl' ); ?></a>
					<button type="button" class="add-cart-btn active:scale-95 min-h-[36px] md:min-h-[38px] w-[36px] md:w-[38px] shrink-0 flex items-center justify-center rounded-md transition-all bg-slate-100 text-slate-800 hover:bg-primary-50 hover:text-primary border border-slate-200/60" data-product-id="<?php echo esc_attr( $pid ); ?>" title="<?php esc_attr_e( 'Thêm vào giỏ', 'spl' ); ?>">
						<?php echo spl_icon( 'cart', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>
			<?php else : ?>
				<div class="mt-3 max-w-[160px]">
					<a href="<?php echo esc_url( $permalink ); ?>" class="w-full inline-flex bg-primary hover:bg-primary-hover active:scale-95 text-white text-[11px] md:text-xs font-bold min-h-[36px] md:min-h-[38px] px-4 rounded-md transition-all text-center items-center justify-center shadow-sm shadow-primary/20"><?php esc_html_e( 'Xem chi tiết', 'spl' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
