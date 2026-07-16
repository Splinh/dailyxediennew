<?php
/**
 * About — Timeline section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$title = $data['title'] ?? 'Hành trình phát triển';
$desc  = $data['description'] ?? 'Từng bước xây dựng hệ thống phân phối xe điện uy tín';
$items = ! empty( $data['items'] ) ? $data['items'] : [
	[
		'year'  => '2020',
		'title' => 'Khởi nghiệp từ đam mê',
		'desc'  => 'Bắt đầu với cửa hàng đầu tiên tại TP. Thủ Đức, chuyên xe điện và xe đạp điện cho học sinh, sinh viên.',
		'icon'  => '<svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/></svg>',
		'class' => 'bg-primary-500 shadow-primary-500/30',
		'color' => 'text-primary-500',
	],
	[
		'year'  => '2022',
		'title' => 'Mở rộng hệ thống',
		'desc'  => 'Phát triển thêm 5 cửa hàng tại các quận trung tâm TP.HCM, hợp tác phân phối với Bluera, Yadea và VinFast.',
		'icon'  => '<svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>',
		'class' => 'bg-emerald-500 shadow-emerald-500/30',
		'color' => 'text-emerald-500',
	],
	[
		'year'  => '2024',
		'title' => 'Vươn tầm toàn quốc',
		'desc'  => 'Hệ thống đại lý mở rộng ra các tỉnh miền Trung và Tây Nguyên. Ra mắt nền tảng bán hàng trực tuyến dailyxedien.vn.',
		'icon'  => '<svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/></svg>',
		'class' => 'bg-amber-500 shadow-amber-500/30',
		'color' => 'text-amber-500',
	],
	[
		'year'  => '2026',
		'title' => 'Hệ thống 20+ cửa hàng',
		'desc'  => 'Đạt mốc 20+ cửa hàng và đại lý ủy quyền trên toàn quốc. Khai trương đại lý mới tại Lâm Đồng, Đắk Lắk.',
		'icon'  => '<svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
		'class' => 'bg-rose-500 shadow-rose-500/30',
		'color' => 'text-rose-500',
	],
];

$fallback_icons = [
	'<svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/></svg>',
	'<svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>',
	'<svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/></svg>',
	'<svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
];

$fallback_classes = [
	'bg-primary-500 shadow-primary-500/30',
	'bg-emerald-500 shadow-emerald-500/30',
	'bg-amber-500 shadow-amber-500/30',
	'bg-rose-500 shadow-rose-500/30',
];

$fallback_colors = [
	'text-primary-500',
	'text-emerald-500',
	'text-amber-500',
	'text-rose-500',
];
?>
<section class="py-12 md:py-16 bg-white overflow-hidden">
	<div class="max-w-7xl mx-auto px-4">
		<!-- Section Header -->
		<div class="text-center mb-10 reveal">
			<div class="flex items-center gap-3 justify-center mb-4">
				<span class="w-1.5 h-6 bg-primary-500 rounded-full"></span>
				<h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
			</div>
			<?php if ( $desc ) : ?>
				<p class="text-sm text-slate-500 max-w-xl mx-auto"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $items ) ) : ?>
			<div class="relative">
				<!-- Vertical line -->
				<div class="absolute left-4 md:left-1/2 top-0 bottom-0 w-0.5 bg-slate-200 md:-translate-x-1/2 pointer-events-none"></div>

				<div class="space-y-8 md:space-y-12">
					<?php foreach ( $items as $index => $item ) : ?>
						<?php
						$is_even     = ( $index % 2 === 0 );
						$dot_class   = $item['class'] ?? $fallback_classes[ $index % count( $fallback_classes ) ];
						$year_color  = $item['color'] ?? $fallback_colors[ $index % count( $fallback_colors ) ];
						$icon_class  = $item['icon'] ?? $fallback_icons[ $index % count( $fallback_icons ) ];
						?>
						<div class="relative flex flex-col md:flex-row md:items-center gap-4 md:gap-8 reveal">
							
							<!-- Desktop Left Content / Right Content depending on index -->
							<div class="md:w-1/2 <?php echo $is_even ? 'md:text-right md:pr-12' : 'md:order-1 order-2'; ?> pl-12 md:pl-0">
								<?php if ( $is_even ) : ?>
									<span class="text-xs font-bold <?php echo esc_attr( $year_color ); ?> uppercase tracking-wider"><?php echo esc_html( $item['year'] ?? '' ); ?></span>
									<h3 class="font-bold text-slate-800 text-base mt-1"><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
									<p class="text-xs text-slate-500 mt-1 leading-relaxed"><?php echo esc_html( $item['desc'] ?? '' ); ?></p>
								<?php endif; ?>
							</div>

							<!-- Center Dot with Icon -->
							<div class="absolute left-4 md:left-1/2 w-8 h-8 <?php echo esc_attr( $dot_class ); ?> rounded-full flex items-center justify-center -translate-x-1/2 shadow-lg z-10">
								<?php if ( strpos( $icon_class, '<svg' ) !== false ) : ?>
									<?php echo wp_kses( $icon_class, Helper::ksesSVG() ); ?>
								<?php else : ?>
									<i class="<?php echo esc_attr( $icon_class ); ?>"></i>
								<?php endif; ?>
							</div>

							<!-- Desktop Right Content / Left Content depending on index -->
							<div class="md:w-1/2 <?php echo $is_even ? 'pl-12' : 'md:pl-12 pl-12 md:order-2 order-1'; ?>">
								<?php if ( ! $is_even ) : ?>
									<span class="text-xs font-bold <?php echo esc_attr( $year_color ); ?> uppercase tracking-wider"><?php echo esc_html( $item['year'] ?? '' ); ?></span>
									<h3 class="font-bold text-slate-800 text-base mt-1"><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
									<p class="text-xs text-slate-500 mt-1 leading-relaxed"><?php echo esc_html( $item['desc'] ?? '' ); ?></p>
								<?php endif; ?>
							</div>

						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
