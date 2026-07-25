<?php
/**
 * Home page — Product Categories grid section.
 *
 * @package SPL
 */


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
<section class="max-w-7xl mx-auto px-4 mb-8 md:mb-16">
	<div class="flex items-center justify-between mb-8">
		<div class="flex items-center gap-3">
			<span class="w-1.5 h-6 bg-primary rounded-full"></span>
			<h2 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<span class="text-sm font-semibold text-slate-400"><?php echo esc_html( $subtitle ); ?></span>
	</div>

	<div class="grid grid-cols-2 md:grid-cols-3 <?php echo esc_attr( $cols_class ); ?> gap-3 md:gap-6">
		<?php
		$selected_ids = $data['selected_categories'] ?? [];
		$selected_ids = is_array( $selected_ids ) ? array_filter( array_map( 'absint', $selected_ids ) ) : [];

		if ( ! empty( $selected_ids ) ) {
			// Admin picked specific categories — fetch in exact order.
			$cats = [];
			foreach ( $selected_ids as $tid ) {
				$term = get_term( $tid, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$cats[] = $term;
				}
			}
		} else {
			// Fallback: show all top-level product categories.
			$all_cats    = spl_get_product_categories();
			$default_cat = (int) get_option( 'default_product_cat' );
			$cats        = $default_cat
				? array_filter( $all_cats, static fn( $c ) => $c->term_id !== $default_cat )
				: $all_cats;
		}

		$rendered = false;

		if ( ! empty( $cats ) ) :
			foreach ( $cats as $cat ) :
				$cat_link = get_term_link( $cat );
				if ( is_wp_error( $cat_link ) ) { continue; }
				$rendered = true;

				// Check for category image attachment.
				$slug         = $cat->slug;
				$thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
				$image_url    = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : '';

				$icon_name = 'bolt';
				if ( false !== stripos( $slug, 'dap' ) || false !== stripos( $slug, 'dap-dien' ) || false !== stripos( $slug, 'xe-dien' ) ) {
					$icon_name = 'bicycle';
				} elseif ( false !== stripos( $slug, '50cc' ) || false !== stripos( $slug, '50-cc' ) ) {
					$icon_name = 'motorcycle';
				} elseif ( false !== stripos( $slug, 'may-dien' ) ) {
					$icon_name = 'bolt';
				} elseif ( false !== stripos( $slug, '3-banh' ) || false !== stripos( $slug, 'ba-banh' ) ) {
					$icon_name = 'map-pin';
				}
				?>
				<a href="<?php echo esc_url( $cat_link ); ?>" class="bg-white/90 backdrop-blur-xs border border-slate-100 hover:border-primary/40 p-3.5 md:p-5 rounded-2xl text-center shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md flex flex-col items-center justify-between group h-full relative">
					<div class="w-16 h-16 md:w-20 md:h-20 bg-slate-50 rounded-2xl flex items-center justify-center p-2 mb-2.5 md:mb-3 group-hover:bg-primary-50 transition-colors shrink-0 overflow-hidden shadow-inner">
						<?php if ( $image_url ) : ?>
							<img loading="lazy" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" class="w-full h-full object-contain transform group-hover:scale-110 transition-transform duration-300">
						<?php else : ?>
							<?php echo spl_icon( $icon_name, 'w-7 h-7 md:w-8 md:h-8 text-slate-400 group-hover:text-primary transition-colors' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					</div>
					<div>
						<span class="font-extrabold text-slate-900 text-xs md:text-sm group-hover:text-primary transition-colors leading-snug block"><?php echo esc_html( $cat->name ); ?></span>
						<?php if ( isset( $cat->count ) && (int) $cat->count > 0 ) : ?>
							<span class="text-[10px] text-slate-400 font-semibold mt-0.5 block"><?php echo esc_html( sprintf( __( '%d mẫu xe', 'spl' ), $cat->count ) ); ?></span>
						<?php endif; ?>
					</div>
				</a>
				<?php
			endforeach;
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
