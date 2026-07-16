<?php
/**
 * About — Hero section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$tag   = $data['tag'] ?? 'Về chúng tôi';
$title = $data['title'] ?? 'Về <span class="text-emerald-400">dailyxedien.vn</span>';
$desc  = $data['description'] ?? 'Hệ thống phân phối xe điện, xe máy điện, xe 50cc chính hãng — tư vấn rõ ràng, giá minh bạch, hậu mãi dễ theo dõi.';

// Fetch stats - first from passed arguments, then ACF field, else fallback defaults.
$stats = $data['stats'] ?? [];
if ( empty( $stats ) ) {
	$about_sections = Helper::getField( 'about_sections' );
	if ( $about_sections ) {
		foreach ( $about_sections as $sec ) {
			if ( ( $sec['acf_fc_layout'] ?? '' ) === 'about_stats' && ! empty( $sec['stats'] ) ) {
				$stats = $sec['stats'];
				break;
			}
		}
	}
}
if ( empty( $stats ) ) {
	$stats = [
		[ 'number' => '20+', 'label' => 'Cửa hàng', 'icon' => '<svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/></svg>' ],
		[ 'number' => '10K+', 'label' => 'Khách hàng', 'icon' => '<svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' ],
		[ 'number' => '50+', 'label' => 'Thương hiệu', 'icon' => '<svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>' ],
		[ 'number' => '98%', 'label' => 'Hài lòng', 'icon' => '<svg class="w-4 h-4 text-rose-400" fill="currentColor" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>' ],
	];
}

$fallback_icons = [
	'<svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>',
	'<svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
	'<svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
	'<svg class="w-4 h-4 text-rose-400" fill="currentColor" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
];
?>
<section class="relative w-full bg-primary-600 overflow-hidden py-10 md:py-14 text-white">
	<div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_50%,rgba(16,185,129,0.12),transparent_50%)] pointer-events-none"></div>
	
	<div class="relative z-10 max-w-7xl mx-auto px-4">
		<div class="reveal">
			<?php if ( $tag ) : ?>
				<span class="inline-flex items-center gap-1.5 text-xs text-white/80 font-semibold mb-3 uppercase tracking-wider">
					<svg class="icon w-3.5 h-3.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
					<?php echo esc_html( $tag ); ?>
				</span>
			<?php endif; ?>

			<?php if ( $title ) : ?>
				<h1 class="text-2xl md:text-3xl lg:text-4xl font-black leading-tight tracking-tight text-white">
					<?php echo wp_kses_post( $title ); ?>
				</h1>
			<?php endif; ?>

			<?php if ( $desc ) : ?>
				<p class="text-sm text-slate-200 mt-3 max-w-2xl leading-relaxed">
					<?php echo esc_html( $desc ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $stats ) ) : ?>
				<!-- Inline stats strip -->
				<div class="flex flex-wrap items-center gap-6 md:gap-10 mt-6 pt-6 border-t border-white/15">
					<?php foreach ( $stats as $index => $stat ) : ?>
						<div class="flex items-center gap-2.5">
							<div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center">
								<?php
								$icon_svg = $stat['icon'] ?? '';
								if ( empty( $icon_svg ) ) {
									$icon_svg = $fallback_icons[ $index % count( $fallback_icons ) ];
								}
								echo wp_kses( $icon_svg, Helper::ksesSVG() );
								?>
							</div>
							<div>
								<span class="text-lg font-black text-white block leading-none"><?php echo esc_html( $stat['number'] ?? '' ); ?></span>
								<span class="text-[10px] text-slate-300"><?php echo esc_html( $stat['label'] ?? '' ); ?></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

