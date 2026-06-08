<?php
/**
 * Home page — USP / Commitments bar.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];
$features = $data['features'] ?? [];

if ( empty( $features ) ) {
	$features = [
		[
			'icon'  => '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
			'title' => __( 'MIỄN PHÍ GIAO HÀNG', 'spl' ),
			'desc'  => __( 'Áp dụng bán kính lên đến 10km', 'spl' ),
		],
		[
			'icon'  => '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>',
			'title' => __( '1 ĐỔI 1 TRONG 7 NGÀY', 'spl' ),
			'desc'  => __( 'Nếu có lỗi sản xuất từ nhà máy', 'spl' ),
		],
		[
			'icon'  => '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
			'title' => __( 'BẢO HÀNH CHÍNH HÃNG', 'spl' ),
			'desc'  => __( 'Hệ thống đại lý ủy quyền uy tín', 'spl' ),
		],
		[
			'icon'  => '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
			'title' => __( 'THANH TOÁN LINH HOẠT', 'spl' ),
			'desc'  => __( 'Trả góp lãi suất 0% mượt mà', 'spl' ),
		],
	];
}
?>
<section class="max-w-7xl mx-auto px-4 mb-16 mt-8">
	<div class="flex overflow-x-auto snap-x snap-mandatory gap-4 pb-4 no-scrollbar lg:grid lg:grid-cols-4 lg:pb-0">
		<?php foreach ( $features as $feat ) :
			$icon_code = $feat['icon'] ?? '';
			$title = $feat['title'] ?? '';
			$desc = $feat['desc'] ?? '';
			?>
			<div class="snap-center bg-white border border-slate-100 hover:border-primary-100 p-5 rounded-2xl flex items-center gap-4 shadow-premium transition-all hover:shadow-hover-card shrink-0 w-[80%] md:w-auto">
				<div class="w-12 h-12 rounded-xl bg-primary-50 text-primary flex items-center justify-center text-xl shrink-0">
					<?php
					if ( $icon_code ) {
						// Clean class attribute from raw input SVG to match layout
						$clean_icon = preg_replace( '/class="[^"]+"/', 'class="w-6 h-6 text-primary"', $icon_code );
						echo $clean_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin raw SVG code input.
					} else {
						echo spl_icon( 'bolt', 'w-6 h-6 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
				<div>
					<h3 class="font-bold text-slate-800 text-sm"><?php echo esc_html( $title ); ?></h3>
					<p class="text-xs text-slate-500 mt-0.5"><?php echo esc_html( $desc ); ?></p>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
