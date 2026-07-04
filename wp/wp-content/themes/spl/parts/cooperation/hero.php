<?php
/**
 * Cooperation template part - Hero banner.
 *
 * @package SPL
 */

use SPL\Core\Helper;

$tagline    = $args['tagline'] ?? 'Tuyển Đại Lý Toàn Quốc 2026';
$title      = $args['title'] ?? "Cùng dailyxedien.vn\nkiến tạo thị trường xe điện";
$subtitle   = $args['subtitle'] ?? 'Trở thành đại lý ủy quyền chính hãng — nhận chiết khấu lên đến 35%, hỗ trợ marketing toàn diện, đào tạo bán hàng chuyên nghiệp.';
$btn_text_1 = $args['btn_text_1'] ?? 'Đăng ký ngay';
$btn_link_1 = $args['btn_link_1'] ?? '#register-form';
$btn_text_2 = $args['btn_text_2'] ?? 'Xem gói hợp tác';
$btn_link_2 = $args['btn_link_2'] ?? '#packages';
$stats      = $args['stats'] ?? [
	[ 'stat_number' => '200+', 'stat_label' => 'Đại lý hiện tại' ],
	[ 'stat_number' => '63', 'stat_label' => 'Tỉnh thành' ],
	[ 'stat_number' => '35%', 'stat_label' => 'Chiết khấu tối đa' ],
	[ 'stat_number' => '50+', 'stat_label' => 'Thương hiệu' ],
];
?>

<section class="relative w-full bg-gradient-to-br from-slate-900 via-[#1a5f9f] to-[#1e73be] overflow-hidden py-16 md:py-24 text-center">
	<div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_80%,rgba(16,185,129,0.15),transparent_50%)]"></div>
	<div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(59,130,246,0.1),transparent_50%)]"></div>
	
	<div class="relative z-10 max-w-7xl mx-auto px-4">
		<?php if ( $tagline ) : ?>
			<div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 text-xs font-semibold text-emerald-300 mb-5">
				<svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.25-2.5 3.5-2.5 3.5h20c0 0-1-2.25-2.5-3.5L12 2 4.5 16.5z"/></svg>
				<span><?php echo esc_html( $tagline ); ?></span>
			</div>
		<?php endif; ?>

		<?php if ( $title ) : ?>
			<h1 class="text-3xl md:text-4xl lg:text-5xl font-black text-white leading-tight tracking-tight max-w-3xl mx-auto">
				<?php echo nl2br( esc_html( $title ) ); ?>
			</h1>
		<?php endif; ?>

		<?php if ( $subtitle ) : ?>
			<p class="text-sm md:text-base text-slate-300 mt-4 max-w-2xl mx-auto leading-relaxed">
				<?php echo esc_html( $subtitle ); ?>
			</p>
		<?php endif; ?>

		<div class="flex flex-wrap gap-3 justify-center mt-8">
			<?php if ( $btn_text_1 ) : ?>
				<a href="<?php echo esc_url( $btn_link_1 ); ?>" class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-7 py-3.5 rounded-xl font-bold text-sm transition-all transform hover:scale-[1.03] shadow-lg shadow-emerald-500/30">
					<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
					<?php echo esc_html( $btn_text_1 ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $btn_text_2 ) : ?>
				<a href="<?php echo esc_url( $btn_link_2 ); ?>" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 text-white px-7 py-3.5 rounded-xl font-bold text-sm transition-all transform hover:scale-[1.03]">
					<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
					<?php echo esc_html( $btn_text_2 ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $stats ) ) : ?>
			<div class="flex flex-wrap items-center justify-center gap-8 md:gap-12 mt-10 pt-8 border-t border-white/10">
				<?php foreach ( $stats as $stat ) : ?>
					<div class="text-center shrink-0 min-w-[120px]">
						<span class="text-2xl md:text-3xl font-black text-white block"><?php echo esc_html( $stat['stat_number'] ); ?></span>
						<span class="text-[10px] text-slate-300 uppercase font-bold tracking-wider"><?php echo esc_html( $stat['stat_label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
