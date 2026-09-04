<?php
/**
 * Contact — Info cards section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$cards = ! empty( $data['cards'] ) ? $data['cards'] : [
	[
		'title' => 'Hotline 24/7',
		'value' => '0933 505 222',
		'note'  => 'Tư vấn & đặt hàng mọi lúc',
	],
	[
		'title' => 'Email',
		'value' => 'info@dailyxedien.vn',
		'note'  => 'Phản hồi trong vòng 2 giờ',
	],
	[
		'title' => 'Chat Zalo',
		'value' => 'Nhắn tin ngay',
		'note'  => 'Trả lời nhanh trong 5 phút',
	],
	[
		'title' => 'Showroom chính',
		'value' => '466 Nguyễn Duy Trinh, Thủ Đức',
		'note'  => 'Mở cửa: 8:00 – 17:00 (Thứ 2 – Chủ nhật)',
	],
];

$fallback_icons = [
	'<svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
	'<svg class="icon" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
	'<svg class="icon" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
	'<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
];
?>
<?php if ( ! empty( $cards ) ) : ?>
<section class="mb-14 md:mb-20 mt-12">
	<div class="container">
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
			<?php foreach ( $cards as $index => $card ) :
				// Determine link destination
				$link_url = '#';
				$target   = '';
				$val      = trim( (string) ( $card['value'] ?? '' ) );
				if ( 0 === $index ) {
					$link_url = 'tel:' . preg_replace( '/[^0-9+]/', '', $val );
				} elseif ( 1 === $index ) {
					$link_url = 'mailto:' . $val;
				} elseif ( 2 === $index ) {
					$link_url = 'https://zalo.me/' . preg_replace( '/[^0-9+]/', '', $val ?: '0933505222' );
					$target   = 'target="_blank"';
				} else {
					$link_url = 'https://maps.google.com/?q=' . urlencode( $val );
					$target   = 'target="_blank"';
				}

				// Color mappings for icons
				$color_classes = [
					'bg-amber-500/10 text-amber-600',
					'bg-sky-500/10 text-sky-600',
					'bg-blue-500/10 text-blue-600',
					'bg-slate-900/10 text-slate-800',
				];
				$bg_color = $color_classes[ $index % count( $color_classes ) ];
				?>
				<a href="<?php echo esc_url( $link_url ); ?>" <?php echo $target; ?> class="contact-card bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:border-amber-400/50 hover:-translate-y-1 transition-all text-center group cursor-pointer block">
					<div class="w-16 h-16 rounded-2xl <?php echo esc_attr( $bg_color ); ?> flex items-center justify-center mx-auto mb-4 transition-transform group-hover:scale-110 <?php echo 0 === $index ? 'pulse-ring' : ''; ?>">
						<?php
						$icon = trim( (string) ( $card['icon'] ?? '' ) );
						$icon = $icon ?: $fallback_icons[ $index % count( $fallback_icons ) ];
						echo wp_kses( $icon, Helper::ksesSVG() );
						?>
					</div>
					<h3 class="font-bold text-slate-700 mb-1 text-xs uppercase tracking-wider"><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
					<p class="text-base font-black text-[#0B2545] tracking-tight group-hover:text-amber-600 transition-colors"><?php echo wp_kses_post( $card['value'] ?? '' ); ?></p>
					<?php if ( ! empty( $card['note'] ) ) : ?>
						<p class="text-xs text-slate-400 mt-1"><?php echo esc_html( $card['note'] ); ?></p>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>
