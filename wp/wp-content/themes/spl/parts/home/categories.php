<?php
/**
 * Home page — Product Categories grid section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];
$title = $data['title'] ?? __( 'Danh mục nổi bật', 'spl' );
$subtitle = $data['subtitle'] ?? __( 'Chọn nhanh theo nhu cầu', 'spl' );
$columns = isset( $data['columns'] ) ? absint( $data['columns'] ) : 6;
$columns = max( 3, min( 6, $columns ?: 6 ) );

// Columns class map.
$cols_class_map = [
	3 => 'lg:grid-cols-3',
	4 => 'lg:grid-cols-4',
	5 => 'lg:grid-cols-5',
	6 => 'lg:grid-cols-6',
];
$cols_class = $cols_class_map[ $columns ] ?? 'lg:grid-cols-6';

?>
<section class="max-w-7xl mx-auto px-4 mb-16">
	<div class="flex items-center justify-between mb-8">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-primary rounded-full"></span>
			<h2 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<span class="text-sm font-semibold text-slate-400"><?php echo esc_html( $subtitle ); ?></span>
	</div>

	<div class="grid grid-cols-2 md:grid-cols-3 <?php echo esc_attr( $cols_class ); ?> gap-4 md:gap-6">
		<?php
		$rendered = false;

		if ( Helper::isWoocommerceActive() ) :
			$cats = get_terms( [
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => 0,
				'orderby'    => 'menu_order',
				'order'      => 'ASC',
				'exclude'    => [ (int) get_option( 'default_product_cat' ) ],
			] );

			if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) :
				foreach ( $cats as $cat ) :
					$cat_link = get_term_link( $cat );
					if ( is_wp_error( $cat_link ) ) { continue; }
					$rendered = true;

					// Select icon depending on category slug/name.
					$slug = $cat->slug;
					$icon_name = 'bolt';
					if ( false !== stripos( $slug, 'dap' ) || false !== stripos( $slug, 'dap-dien' ) || false !== stripos( $slug, 'xe-dien' ) ) {
						$icon_name = 'bicycle';
					} elseif ( false !== stripos( $slug, '50cc' ) || false !== stripos( $slug, '50-cc' ) ) {
						$icon_name = 'motorcycle';
					} elseif ( false !== stripos( $slug, 'may-dien' ) ) {
						$icon_name = 'bolt';
					} elseif ( false !== stripos( $slug, '3-banh' ) || false !== stripos( $slug, 'ba-banh' ) ) {
						$icon_name = 'map-pin'; // use map-pin or fallback
					}
					?>
					<a href="<?php echo esc_url( $cat_link ); ?>" class="bg-white hover:border-primary border border-slate-100 p-4 md:p-6 rounded-2xl text-center shadow-premium transition-all hover:-translate-y-1 hover:shadow-hover-card flex flex-col items-center group">
						<div class="w-16 h-16 md:w-20 md:h-20 bg-slate-50 rounded-full flex items-center justify-center p-2 mb-3 md:mb-4 group-hover:bg-primary-50 transition-colors">
							<?php echo spl_icon( $icon_name, 'w-8 h-8 text-slate-400 group-hover:text-primary transition-colors' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<span class="font-bold text-slate-800 text-xs md:text-sm group-hover:text-primary transition-colors"><?php echo esc_html( $cat->name ); ?></span>
					</a>
					<?php
				endforeach;
			endif;
		endif;

		if ( ! $rendered ) :
			// Static fallback.
			$fallback = [
				[ 'name' => 'Xe Điện', 'slug' => 'xe-dien', 'icon' => 'bicycle' ],
				[ 'name' => 'Xe 50cc', 'slug' => 'xe-50cc', 'icon' => 'motorcycle' ],
				[ 'name' => 'Xe Máy Điện', 'slug' => 'xe-may-dien', 'icon' => 'bolt' ],
				[ 'name' => 'Xe 3 Bánh', 'slug' => 'xe-3-banh', 'icon' => 'bolt' ],
				[ 'name' => 'Xe Đạp Điện', 'slug' => 'xe-dap-dien', 'icon' => 'bicycle' ],
				[ 'name' => 'Phụ Kiện', 'slug' => 'phu-kien', 'icon' => 'bolt' ],
			];
			foreach ( $fallback as $item ) :
				?>
				<a href="#" class="bg-white hover:border-primary border border-slate-100 p-4 md:p-6 rounded-2xl text-center shadow-premium transition-all hover:-translate-y-1 hover:shadow-hover-card flex flex-col items-center group">
					<div class="w-16 h-16 md:w-20 md:h-20 bg-slate-50 rounded-full flex items-center justify-center p-2 mb-3 md:mb-4 group-hover:bg-primary-50 transition-colors">
						<?php echo spl_icon( $item['icon'], 'w-8 h-8 text-slate-400 group-hover:text-primary transition-colors' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<span class="font-bold text-slate-800 text-xs md:text-sm group-hover:text-primary transition-colors"><?php echo esc_html( $item['name'] ); ?></span>
				</a>
				<?php
			endforeach;
		endif;
		?>
	</div>
</section>
