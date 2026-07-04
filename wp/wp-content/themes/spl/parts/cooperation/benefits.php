<?php
/**
 * Cooperation template part - Benefits (Quyền lợi).
 *
 * @package SPL
 */

use SPL\Core\Helper;

$title    = $args['title'] ?? 'Tại sao hợp tác với chúng tôi?';
$subtitle = $args['subtitle'] ?? 'Dailyxedien.vn cung cấp mọi thứ bạn cần để kinh doanh xe điện thành công';
$cards    = $args['cards'] ?? [
	[
		'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
		'title' => 'Chiết khấu hấp dẫn',
		'description' => 'Chiết khấu từ 20% đến 35% giá bán lẻ. Bonus thưởng doanh số hàng tháng cho đại lý đạt target.'
	],
	[
		'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l11 11"/></svg>',
		'title' => 'Hỗ trợ marketing',
		'description' => 'Cung cấp ảnh/video sản phẩm chuyên nghiệp, banner quảng cáo, hỗ trợ chạy ads Facebook/Google.'
	],
	[
		'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2.5 3 6 3s6-1 6-3v-5"/></svg>',
		'title' => 'Đào tạo bán hàng',
		'description' => 'Chương trình đào tạo kỹ thuật, tư vấn bán hàng, xử lý bảo hành — cập nhật liên tục từng quý.'
	],
	[
		'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
		'title' => 'Kho hàng & vận chuyển',
		'description' => 'Hệ thống kho phân phối 3 miền. Giao hàng nhanh 1-3 ngày, hỗ trợ đổi trả hàng lỗi dễ dàng.'
	],
	[
		'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
		'title' => 'Bảo hành toàn quốc',
		'description' => 'Chế độ bảo hành chính hãng 3 năm. Linh kiện thay thế sẵn có tại kho, xử lý nhanh trong 24h.'
	],
	[
		'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>',
		'title' => 'Bảo vệ vùng bán',
		'description' => 'Cam kết bảo vệ vùng bán cho đại lý. Không cạnh tranh giá nội bộ, đảm bảo lợi nhuận ổn định.'
	]
];
?>

<section class="max-w-7xl mx-auto px-4 py-14 md:py-20">
	<div class="text-center mb-12">
		<div class="flex items-center gap-3 justify-center mb-4">
			<span class="w-1.5 h-6 bg-primary rounded-full"></span>
			<h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>
		<?php if ( $subtitle ) : ?>
			<p class="text-sm text-slate-500 max-w-xl mx-auto"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
	</div>
	
	<?php if ( ! empty( $cards ) ) : ?>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
			<?php foreach ( $cards as $card ) : ?>
				<div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-premium hover:shadow-hover-card hover:-translate-y-1 transition-all duration-300 group">
					<div class="w-12 h-12 rounded-xl bg-slate-50 text-primary flex items-center justify-center mb-4 group-hover:bg-primary group-hover:text-white transition-colors duration-300">
						<?php 
						if ( str_starts_with( trim( $card['icon'] ), '<svg' ) ) {
							echo $card['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo '<i class="' . esc_attr( $card['icon'] ) . ' text-xl"></i>';
						}
						?>
					</div>
					<h3 class="font-bold text-slate-800 text-sm mb-2 group-hover:text-primary transition-colors duration-300"><?php echo esc_html( $card['title'] ); ?></h3>
					<p class="text-xs text-slate-500 leading-relaxed"><?php echo esc_html( $card['description'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
