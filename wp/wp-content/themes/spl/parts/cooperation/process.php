<?php
/**
 * Cooperation template part - Process (Quy trình).
 *
 * @package SPL
 */

use SPL\Core\Helper;

$title    = $args['title'] ?? 'Quy trình hợp tác';
$subtitle = $args['subtitle'] ?? 'Chỉ 4 bước đơn giản để trở thành đại lý chính thức';

$steps = $args['steps'] ?? [
	[
		'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
		'title' => 'Đăng ký thông tin',
		'description' => 'Điền form đăng ký bên dưới hoặc gọi hotline tư vấn'
	],
	[
		'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
		'title' => 'Tư vấn & thẩm định',
		'description' => 'Đội ngũ liên hệ trao đổi nhu cầu, khảo sát địa điểm'
	],
	[
		'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H9H8"/></svg>',
		'title' => 'Ký hợp đồng',
		'description' => 'Thống nhất điều khoản, ký hợp đồng đại lý chính thức'
	],
	[
		'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4.5 16.5c-1.5 1.25-2.5 3.5-2.5 3.5h20c0 0-1-2.25-2.5-3.5L12 2 4.5 16.5z"/></svg>',
		'title' => 'Khai trương & vận hành',
		'description' => 'Nhập hàng, setup cửa hàng, đào tạo và bắt đầu kinh doanh'
	]
];
?>

<section class="max-w-7xl mx-auto px-4 py-14 md:py-20 border-t border-slate-100">
	<div class="text-center mb-12">
		<div class="flex items-center gap-3 justify-center mb-4">
			<span class="w-1.5 h-6 bg-amber-500 rounded-full"></span>
			<h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<?php if ( $subtitle ) : ?>
			<p class="text-sm text-slate-500 max-w-xl mx-auto"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $steps ) ) : ?>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
			<?php 
			$idx = 1;
			foreach ( $steps as $step ) : 
				// Pick colors based on step number to look rich
				$colors = [
					1 => [ 'bg' => 'bg-primary/5 text-primary', 'badge' => 'bg-primary' ],
					2 => [ 'bg' => 'bg-emerald-50 text-emerald-600', 'badge' => 'bg-emerald-500' ],
					3 => [ 'bg' => 'bg-amber-50 text-amber-600', 'badge' => 'bg-amber-500' ],
					4 => [ 'bg' => 'bg-blue-50 text-blue-600', 'badge' => 'bg-blue-500' ],
				];
				$color = $colors[ $idx ] ?? $colors[1];
				?>
				<div class="text-center group">
					<div class="w-16 h-16 <?php echo $color['bg']; ?> rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-primary group-hover:text-white transition-all duration-300 relative">
						<?php 
						if ( str_starts_with( trim( $step['icon'] ), '<svg' ) ) {
							echo $step['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo '<i class="' . esc_attr( $step['icon'] ) . ' text-xl"></i>';
						}
						?>
						<span class="absolute -top-2 -right-2 w-6 h-6 <?php echo $color['badge']; ?> group-hover:bg-emerald-500 text-white rounded-full text-xs font-black flex items-center justify-center transition-colors duration-300"><?php echo esc_html( $idx ); ?></span>
					</div>
					<h3 class="font-bold text-slate-800 text-sm mb-1 group-hover:text-primary transition-colors duration-300"><?php echo esc_html( $step['title'] ); ?></h3>
					<p class="text-xs text-slate-500 leading-relaxed"><?php echo esc_html( $step['description'] ); ?></p>
				</div>
				<?php 
				$idx++;
			endforeach; 
			?>
		</div>
	<?php endif; ?>
</section>
