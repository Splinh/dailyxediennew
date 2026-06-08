<?php
/**
 * Home page — Best Sellers tabbed products section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];
$title = $data['title'] ?? __( 'Sản phẩm bán chạy', 'spl' );
$tabs = $data['tabs'] ?? [];

// Default tabs fallback if empty.
if ( empty( $tabs ) ) {
	$tabs = [
		[
			'tab_title' => 'XE ĐIỆN',
			'tab_icon'  => 'bicycle',
			'category'  => 0,
			'count'     => 5,
		],
		[
			'tab_title' => 'XE 50CC',
			'tab_icon'  => 'motorcycle',
			'category'  => 0,
			'count'     => 5,
		],
		[
			'tab_title' => 'XE MÁY ĐIỆN',
			'tab_icon'  => 'bolt',
			'category'  => 0,
			'count'     => 5,
		],
	];
}
?>
<section class="max-w-7xl mx-auto px-4 mb-16 scroll-mt-24" id="best-sellers">
	<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-primary rounded-full"></span>
			<h2 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>

		<!-- Tab Buttons list -->
		<div class="flex items-center gap-2 bg-slate-100/80 p-1.5 rounded-2xl overflow-x-auto no-scrollbar md:w-auto w-full" role="tablist" aria-label="<?php esc_attr_e( 'Lọc sản phẩm theo loại', 'spl' ); ?>">
			<?php foreach ( $tabs as $index => $tab ) :
				$tab_title = $tab['tab_title'] ?? '';
				$tab_icon = $tab['tab_icon'] ?? 'bicycle';
				$tab_slug = 'tab-bs-' . sanitize_title( $tab_title ) . '-' . $index;
				$active_btn_class = $index === 0
					? 'tab-btn active px-4 md:px-6 py-2.5 md:py-3 text-xs font-black rounded-xl transition-all whitespace-nowrap bg-gradient-to-r from-primary to-primary-hover text-white shadow-md shadow-primary/30'
					: 'tab-btn px-4 md:px-6 py-2.5 md:py-3 text-xs font-bold rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 transition-all whitespace-nowrap';
				?>
				<button onclick="switchTab('<?php echo esc_attr( $tab_slug ); ?>', this)"
					data-tab="<?php echo esc_attr( $tab_slug ); ?>"
					role="tab"
					aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
					class="<?php echo esc_attr( $active_btn_class ); ?>">
					<?php echo spl_icon( $tab_icon, 'w-3.5 h-3.5 mr-1.5 inline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $tab_title ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Tab Panels -->
	<div id="tab-container" class="relative min-h-[400px]">
		<?php foreach ( $tabs as $index => $tab ) :
			$tab_title = $tab['tab_title'] ?? '';
			$tab_slug = 'tab-bs-' . sanitize_title( $tab_title ) . '-' . $index;
			$cat_id = $tab['category'] ?? 0;
			$count = isset( $tab['count'] ) ? absint( $tab['count'] ) : 5;
			$active_panel_class = $index === 0 ? 'grid' : 'hidden grid';
			?>
			<div id="<?php echo esc_attr( $tab_slug ); ?>" class="tab-panel <?php echo esc_attr( $active_panel_class ); ?> grid-cols-2 lg:grid-cols-5 gap-4 md:gap-6">
				<?php
				$rendered = false;

				if ( Helper::isWoocommerceActive() ) :
					$query_args = [
						'post_type'           => 'product',
						'posts_per_page'      => $count,
						'orderby'             => 'date',
						'order'               => 'DESC',
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
						'fields'              => 'ids',
					];

					if ( $cat_id ) {
						$query_args['tax_query'] = [
							[
								'taxonomy' => 'product_cat',
								'field'    => 'term_id',
								'terms'    => $cat_id,
							],
						];
					}

					$products_query = new \WP_Query( $query_args );
					$product_ids    = array_values( array_filter( array_map( 'absint', $products_query->posts ) ) );

					if ( ! empty( $product_ids ) ) :
						$rendered = true;
						spl_prime_product_card_caches( $product_ids );
						foreach ( $product_ids as $product_id ) :
							get_template_part( 'parts/product-card', null, [ 'id' => $product_id ] );
						endforeach;
					endif;
				endif;

				if ( ! $rendered ) :
					// Static fallback products
					$static_products = [
						[ 'name' => 'Xe đạp điện Vespa Roma S', 'price' => 19900000, 'old_price' => 22000000, 'brand' => 'Vespa', 'sales' => '1.2k' ],
						[ 'name' => 'Xe đạp điện thể thao Xmen One', 'price' => 18500000, 'old_price' => 20500000, 'brand' => 'Xmen', 'sales' => '980' ],
						[ 'name' => 'Xe máy điện Vinfast Feliz S', 'price' => 29900000, 'old_price' => 0, 'brand' => 'Vinfast', 'sales' => '540' ],
						[ 'name' => 'Xe máy điện Dibao Pansy S4', 'price' => 21500000, 'old_price' => 23000000, 'brand' => 'Dibao', 'sales' => '820' ],
						[ 'name' => 'Xe đạp điện Bluera Cap X', 'price' => 12500000, 'old_price' => 14000000, 'brand' => 'Bluera', 'sales' => '320' ],
					];
					foreach ( $static_products as $p ) :
						?>
						<div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-premium hover:shadow-hover-card transition-all duration-300 flex flex-col justify-between group relative">
							<?php if ( $p['old_price'] > $p['price'] ) : ?>
								<span class="absolute top-2.5 left-2.5 bg-red-500 text-white font-black text-[9px] md:text-[10px] px-2 py-0.5 md:px-2.5 md:py-1 rounded-lg z-10 shadow-sm uppercase">Hot</span>
							<?php endif; ?>
							<div class="p-3 bg-slate-50/50 flex items-center justify-center h-36 md:h-48 relative overflow-hidden">
								<img loading="lazy" src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" alt="<?php echo esc_attr( $p['name'] ); ?>" class="max-h-full max-w-full object-contain transform group-hover:scale-105 transition-transform duration-300">
							</div>
							<div class="p-3 md:p-5 flex-grow flex flex-col justify-between">
								<div>
									<span class="text-[9px] md:text-[10px] text-slate-400 font-bold uppercase tracking-wider"><?php echo esc_html( $p['brand'] ); ?></span>
									<h3 class="font-bold text-slate-800 text-xs md:text-sm line-clamp-2 mt-0.5 group-hover:text-primary transition-colors leading-snug"><?php echo esc_html( $p['name'] ); ?></h3>
									<div class="flex items-center gap-0.5 mt-1.5 text-amber-400 text-[10px]" aria-label="Đánh giá 5 sao">
										<svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
										<svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
										<svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
										<svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
										<svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
										<span class="text-slate-400 ml-1 font-semibold">Đã bán <?php echo esc_html( $p['sales'] ); ?></span>
									</div>
								</div>
								<div class="mt-3">
									<div class="flex flex-wrap items-baseline gap-1 md:gap-2">
										<span class="text-sm md:text-base font-extrabold text-slate-900"><?php echo esc_html( number_format( $p['price'], 0, ',', '.' ) ); ?>đ</span>
										<?php if ( $p['old_price'] > 0 ) : ?>
											<span class="text-[10px] md:text-xs text-slate-400 line-through"><?php echo esc_html( number_format( $p['old_price'] / 1000000, 1 ) ); ?>M</span>
										<?php endif; ?>
									</div>
									<div class="grid grid-cols-5 gap-1.5 mt-3">
										<button class="col-span-4 bg-primary hover:bg-primary-hover active:scale-95 text-white text-[10px] md:text-xs font-bold py-2 md:py-2.5 rounded-lg transition-all">Mua ngay</button>
										<button class="bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-600 flex items-center justify-center rounded-lg transition-all" title="Thêm vào giỏ hàng">
											<?php echo spl_icon( 'cart', 'w-3.5 h-3.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</button>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach;
				endif;
				?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
