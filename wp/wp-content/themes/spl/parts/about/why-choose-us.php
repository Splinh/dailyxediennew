<?php
/**
 * About — Why choose us section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$title = $data['title'] ?? 'Tại sao chọn dailyxedien.vn?';
$desc  = $data['description'] ?? 'Những lý do khách hàng tin tưởng và gắn bó với chúng tôi';
$items = ! empty( $data['items'] ) ? $data['items'] : [
	[
		'title' => 'Sản phẩm chính hãng',
		'desc'  => 'Nhập khẩu và phân phối trực tiếp từ nhà máy, đầy đủ giấy tờ và CO/CQ.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
		'class' => 'bg-blue-50 text-blue-500',
	],
	[
		'title' => 'Giá tốt - Trả góp 0%',
		'desc'  => 'Cam kết giá cạnh tranh nhất thị trường. Hỗ trợ trả góp lãi suất 0% nhanh chóng.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"/><path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/><path d="m2 16 6 6"/><circle cx="16" cy="9" r="2.9"/><circle cx="6" cy="5" r="3"/></svg>',
		'class' => 'bg-emerald-50 text-emerald-500',
	],
	[
		'title' => 'Tư vấn tận tâm',
		'desc'  => 'Đội ngũ nhân viên được đào tạo bài bản, tư vấn đúng nhu cầu, không ép bán hàng.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm0 0a9 9 0 1 1 18 0m0 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/></svg>',
		'class' => 'bg-amber-50 text-amber-500',
	],
	[
		'title' => 'Hậu mãi minh bạch',
		'desc'  => 'Bảo hành chính hãng, sửa chữa tại đại lý, theo dõi lịch sử bảo trì dễ dàng.',
		'icon'  => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
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
					$box_class  = $item['class'] ?? $fallback_classes[ $index % count( $fallback_classes ) ];
					$icon_class = $item['icon'] ?? $fallback_icons[ $index % count( $fallback_icons ) ];
					?>
					<div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-premium hover:shadow-hover-card hover:-translate-y-1 transition-all text-center group reveal">
						<div class="w-16 h-16 rounded-2xl <?php echo esc_attr( $box_class ); ?> flex items-center justify-center mx-auto mb-4 group-hover:bg-primary-50 group-hover:text-primary-500 transition-colors">
							<?php if ( strpos( $icon_class, '<svg' ) !== false ) : ?>
								<?php echo wp_kses( $icon_class, Helper::ksesSVG() ); ?>
							<?php else : ?>
								<i class="<?php echo esc_attr( $icon_class ); ?>"></i>
							<?php endif; ?>
						</div>
						<h3 class="font-bold text-slate-800 mb-2 text-sm md:text-base"><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
						<p class="text-xs text-slate-500 leading-relaxed"><?php echo esc_html( $item['desc'] ?? '' ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
