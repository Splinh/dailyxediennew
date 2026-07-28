<?php
/**
 * About — Why choose us section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$title = $data['title'] ?? 'VÌ SAO ĐẠI LÝ XE ĐIỆN LÀ LỰA CHỌN TỐT DÀNH CHO BẠN?';
$desc  = $data['description'] ?? 'Phương tiện di chuyển thông minh, hoạt động bằng nguồn năng lượng sạch và đáp ứng tiêu chuẩn khắt khe nhất';
$items = ! empty( $data['items'] ) ? $data['items'] : [
	[
		'title' => 'Năng lượng sạch & Tiết kiệm',
		'desc'  => 'Là một giải phương tiện thông minh của thời đại mới, xe đạp điện, xe máy điện và xe điện 3 bánh do Đại Lý Xe Điện cung cấp hoạt động bằng nguồn năng lượng sạch, không những có thể giúp bảo vệ môi trường, sức khỏe của con người mà còn có khả năng tiết kiệm chi phí tối đa. Sản phẩm hội tụ đầy đủ các yếu tố tốt nhất xứng đáng để các bạn lựa chọn như thiết kế sành điệu nhỏ gọn, khả năng di chuyển trên quãng đường xa, giá thành hợp lý… và rất nhiều lý do khác.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v8"/><path d="m4.93 10.93 4.24 4.24"/><path d="M2 18h20"/><path d="M20 10c0 5.523-4.477 10-10 10S0 15.523 0 10"/></svg>',
		'class' => 'bg-blue-50 text-blue-500',
	],
	[
		'title' => 'Đa dạng chủng loại & Chuẩn mực',
		'desc'  => 'Để có được một sản phẩm đạt những tiêu chí hoàn hảo như vậy, Đại Lý Xe Điện đã không ngừng cố gắng tìm kiếm các chủng loại có chất lượng cao, sản phẩm đẹp, đa dạng, kèm theo dịch vụ và giá thành phù hợp với người tiêu dùng Việt.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
		'class' => 'bg-emerald-50 text-emerald-500',
	],
	[
		'title' => 'Công nghệ hiện đại & Lắp ráp Việt Nam',
		'desc'  => 'Trong nỗ lực hoàn thiện sản phẩm của mình, Đại Lý Xe Điện cũng mạnh dạn đầu tư các loại trang thiết bị máy móc hiện đại để kiểm tra và bảo hành tận nơi cho khách hàng Việt Nam, ứng dụng công nghệ tiên tiến hàng đầu, trực tiếp lắp ráp các dòng xe đạp điện Bluera ngay tại Việt Nam.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
		'class' => 'bg-amber-50 text-amber-500',
	],
	[
		'title' => 'Kiểm định khắt khe & Đầy đủ hóa đơn',
		'desc'  => 'Mỗi sản phẩm của Đại Lý Xe Điện cung cấp đến tay người tiêu dùng đều được trải qua quy trình khắt khe nhất về chất lượng. Đại Lý Xe Điện tự hào là một trong những nhà cung cấp bán buôn và bán lẻ của các thương hiệu hàng đầu tại Việt Nam, đồng thời thực hiện nghiêm chỉnh quy định của Nhà nước về hàng hóa xuất bán có đầy đủ hóa đơn, đăng kiểm và đạt các tiêu chuẩn cho phép hoạt động của phương tiện xe điện hiện nay.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>',
		'class' => 'bg-rose-50 text-rose-500',
	],
];

$fallback_icons = [
	'<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
	'<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"/><path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/><path d="m2 16 6 6"/><circle cx="16" cy="9" r="2.9"/><circle cx="6" cy="5" r="3"/></svg>',
	'<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm0 0a9 9 0 1 1 18 0m0 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/></svg>',
	'<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
];

$fallback_classes = [
	'bg-blue-50 text-blue-500',
	'bg-emerald-50 text-emerald-500',
	'bg-amber-50 text-amber-500',
	'bg-rose-50 text-rose-500',
];
?>
<section class="py-12 md:py-16 bg-white">
	<div class="max-w-7xl mx-auto px-4">
		<!-- Section Header -->
		<div class="text-center mb-10 reveal">
			<div class="flex items-center gap-3 justify-center mb-4">
				<span class="w-1.5 h-6 bg-emerald-500 rounded-full"></span>
				<h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
			</div>
			<?php if ( $desc ) : ?>
				<p class="text-sm text-slate-500 max-w-xl mx-auto"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $items ) ) : ?>
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$box_class  = ! empty( $item['class'] ) ? $item['class'] : $fallback_classes[ $index % count( $fallback_classes ) ];
					$icon_class = ! empty( $item['icon'] ) ? $item['icon'] : $fallback_icons[ $index % count( $fallback_icons ) ];
					?>
					<div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-premium hover:shadow-hover-card hover:-translate-y-1 transition-all text-center group reveal">
						<div class="w-16 h-16 rounded-2xl <?php echo esc_attr( $box_class ); ?> flex items-center justify-center mx-auto mb-4 group-hover:bg-primary-50 group-hover:text-primary-500 transition-colors">
							<?php if ( strpos( $icon_class, '<svg' ) !== false ) : ?>
								<?php echo wp_kses( $icon_class, Helper::ksesSVG() ); ?>
							<?php else : ?>
								<i class="<?php echo esc_attr( $icon_class ); ?>"></i>
							<?php endif; ?>
						</div>
						<h3 class="font-extrabold text-slate-900 mb-2 text-base md:text-lg"><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
						<p class="text-xs md:text-sm text-slate-600 leading-relaxed"><?php echo esc_html( $item['desc'] ?? '' ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
