<?php
/**
 * About — Values section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data   = $args ?? [];
$badge  = $data['badge'] ?? 'Giá Trị Cốt Lõi';
$title  = $data['title'] ?? 'Giá trị cốt lõi';
$values = ! empty( $data['values'] ) ? $data['values'] : [
	[
		'title' => 'Minh bạch',
		'desc'  => 'Giá niêm yết rõ ràng, không phát sinh chi phí ẩn. Khách hàng luôn biết trước tổng chi phí trước khi quyết định.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>',
		'class' => 'bg-blue-50 text-blue-500',
	],
	[
		'title' => 'Tận tâm',
		'desc'  => 'Lắng nghe nhu cầu thực tế để tư vấn đúng xe, đúng mục đích sử dụng. Không ép bán, không phóng đại tính năng.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/><path d="M12 5 9.04 7.96a2.17 2.17 0 0 0 0 3.08c.82.82 2.13.85 3 .07l2.07-1.9a2.82 2.82 0 0 1 3.79 0l2.96 2.66"/><path d="m18 15-2-2"/><path d="m15 18-2-2"/></svg>',
		'class' => 'bg-emerald-50 text-emerald-500',
	],
	[
		'title' => 'Trách nhiệm',
		'desc'  => 'Bảo hành đúng cam kết, xử lý khiếu nại nhanh gọn. Mỗi sản phẩm bán ra đều có lịch sử theo dõi hậu mãi rõ ràng.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
		'class' => 'bg-amber-50 text-amber-500',
	],
	[
		'title' => 'Đổi mới',
		'desc'  => 'Liên tục cập nhật dòng xe mới, công nghệ mới. Ứng dụng nền tảng số để khách hàng dễ dàng tra cứu và theo dõi đơn hàng.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>',
		'class' => 'bg-violet-50 text-violet-500',
	],
];

$fallback_icons = [
	'<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>',
	'<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/></svg>',
	'<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
	'<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>',
];

$fallback_classes = [
	'bg-blue-50 text-blue-500',
	'bg-emerald-50 text-emerald-500',
	'bg-amber-50 text-amber-500',
	'bg-violet-50 text-violet-500',
];
?>
<section class="py-12 md:py-16 bg-white">
	<div class="max-w-7xl mx-auto px-4">
		<!-- Section Header -->
		<div class="text-center mb-10 reveal">
			<div class="flex items-center gap-3 justify-center mb-3">
				<span class="w-1.5 h-5 bg-amber-500 rounded-full"></span>
				<h3 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h3>
			</div>
			<p class="text-base text-slate-600 max-w-lg mx-auto leading-relaxed">Những nguyên tắc chúng tôi cam kết giữ vững trong mọi hoạt động</p>
		</div>

		<?php if ( ! empty( $values ) ) : ?>
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
				<?php foreach ( $values as $index => $item ) : ?>
					<?php
					$box_class  = $item['class'] ?? $fallback_classes[ $index % count( $fallback_classes ) ];
					$icon_class = $item['icon'] ?? $fallback_icons[ $index % count( $fallback_icons ) ];
					?>
					<div class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-premium hover:shadow-hover-card hover:-translate-y-1 transition-all group reveal">
						<div class="w-12 h-12 rounded-xl <?php echo esc_attr( $box_class ); ?> flex items-center justify-center mb-4 group-hover:bg-primary-50 group-hover:text-primary-500 transition-colors">
							<?php if ( strpos( $icon_class, '<svg' ) !== false ) : ?>
								<?php echo wp_kses( $icon_class, Helper::ksesSVG() ); ?>
							<?php else : ?>
								<i class="<?php echo esc_attr( $icon_class ); ?>"></i>
							<?php endif; ?>
						</div>
						<h4 class="font-extrabold text-slate-900 text-base mb-2"><?php echo esc_html( $item['title'] ?? '' ); ?></h4>
						<p class="text-sm md:text-base text-slate-600 leading-relaxed font-normal"><?php echo esc_html( $item['desc'] ?? '' ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

