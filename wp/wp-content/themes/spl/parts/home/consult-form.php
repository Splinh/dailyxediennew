<?php
/**
 * Home page — Consultation / Lead capture form (Đăng ký tư vấn).
 *
 * Reads ACF: title, subtitle, cf7_shortcode. When a Contact Form 7 shortcode is
 * provided it is rendered; otherwise the built-in demo form (validated client-side
 * by handleConsultSubmit() in inc/page-home.js) is shown.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data     = $args ?? [];
$title    = $data['title'] ?? __( 'ĐĂNG KÝ NHẬN BÁO GIÁ & TƯ VẤN MIỄN PHÍ', 'spl' );
$subtitle = $data['subtitle'] ?? __( 'Để lại thông tin để nhận ngay voucher mua sắm, mũ bảo hiểm chính hãng cùng combo bảo dưỡng vàng 3 năm.', 'spl' );
$cf7      = trim( (string) ( $data['cf7_shortcode'] ?? '' ) );

$interests = [ 'Xe máy điện', 'Xe điện Vespa', 'Xe đạp điện', 'Xe 50cc', 'Xe 3 bánh' ];
$regions   = [
	'TP. Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Bình Dương', 'Đồng Nai', 'Bà Rịa - Vũng Tàu',
	'Lâm Đồng', 'An Giang', 'Bình Phước', 'Bình Thuận', 'Cần Thơ', 'Hậu Giang', 'Kiên Giang',
	'Long An', 'Tây Ninh', 'Tiền Giang', 'Bến Tre', 'Trà Vinh', 'Vĩnh Long', 'Đồng Tháp',
	'Sóc Trăng', 'Bạc Liêu', 'Cà Mau', 'Khánh Hòa', 'Ninh Thuận', 'Gia Lai', 'Đắk Lắk',
	'Đắk Nông', 'Kon Tum', 'Bình Định', 'Phú Yên', 'Quảng Ngãi', 'Quảng Nam', 'Hải Phòng',
];

$input_class = 'w-full px-4 py-3 border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none rounded-xl text-sm transition-all bg-slate-50';
$label_class = 'text-xs font-bold text-slate-500 uppercase tracking-wider block';
?>
<section class="max-w-7xl mx-auto px-4 mb-16 scroll-mt-24" id="consult-form">
	<div class="bg-gradient-to-r from-primary-600 to-indigo-700 rounded-3xl p-6 md:p-12 text-white relative overflow-hidden shadow-premium">
		<div class="absolute -right-10 -bottom-10 w-80 h-80 bg-white/5 rounded-full blur-2xl"></div>
		<div class="absolute top-0 left-1/3 w-60 h-60 bg-emerald-500/10 rounded-full blur-3xl"></div>

		<div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">

			<!-- Content side -->
			<div class="lg:col-span-5 space-y-4 text-center lg:text-left">
				<span class="bg-emerald-500/20 text-emerald-300 font-bold text-xs px-3.5 py-1.5 rounded-full uppercase tracking-wider inline-block"><?php esc_html_e( 'Ưu đãi độc quyền hôm nay', 'spl' ); ?></span>
				<h2 class="text-2xl md:text-4xl font-black tracking-tight leading-tight"><?php echo esc_html( $title ); ?></h2>
				<p class="text-xs md:text-sm text-indigo-100 leading-relaxed"><?php echo esc_html( $subtitle ); ?></p>
				<div class="flex items-center gap-4 justify-center lg:justify-start pt-2 text-xs">
					<span class="flex items-center gap-1.5">
						<?php echo spl_icon( 'bolt', 'w-4 h-4 text-yellow-400' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Hỗ trợ trả góp 0%', 'spl' ); ?>
					</span>
					<span class="flex items-center gap-1.5">
						<svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
						<?php esc_html_e( 'Cam kết bảo mật', 'spl' ); ?>
					</span>
				</div>
			</div>

			<!-- Form side -->
			<div class="lg:col-span-7 bg-white text-slate-800 p-5 md:p-8 rounded-2xl shadow-xl">
				<?php if ( '' !== $cf7 ) : ?>
					<?php echo do_shortcode( $cf7 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<form onsubmit="event.preventDefault(); if(window.handleConsultSubmit){handleConsultSubmit();}" class="space-y-4">
						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div class="space-y-1">
								<label for="consult-name" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Họ và tên của bạn', 'spl' ); ?></label>
								<input type="text" id="consult-name" required placeholder="<?php esc_attr_e( 'Nhập tên...', 'spl' ); ?>" class="<?php echo esc_attr( $input_class ); ?>">
							</div>
							<div class="space-y-1">
								<label for="consult-phone" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Số điện thoại liên hệ', 'spl' ); ?></label>
								<input type="tel" id="consult-phone" required placeholder="<?php esc_attr_e( 'Nhập SĐT...', 'spl' ); ?>" class="<?php echo esc_attr( $input_class ); ?>">
							</div>
						</div>

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div class="space-y-1">
								<label for="consult-interest" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Dòng xe bạn quan tâm', 'spl' ); ?></label>
								<select id="consult-interest" class="<?php echo esc_attr( $input_class ); ?>">
									<?php foreach ( $interests as $opt ) : ?>
										<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="space-y-1">
								<label for="consult-region" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Khu vực sinh sống', 'spl' ); ?></label>
								<select id="consult-region" class="<?php echo esc_attr( $input_class ); ?>">
									<?php foreach ( $regions as $opt ) : ?>
										<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="space-y-1">
							<label for="consult-message" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Yêu cầu tư vấn chi tiết', 'spl' ); ?></label>
							<textarea id="consult-message" rows="3" placeholder="<?php esc_attr_e( 'Ghi chú thêm nhu cầu của bạn (trả góp 0%, xe cho học sinh, báo giá đại lý tỉnh...)', 'spl' ); ?>" class="<?php echo esc_attr( $input_class ); ?> resize-none"></textarea>
						</div>

						<button type="submit" class="w-full py-4 bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-white font-bold text-sm rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
							<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
							<?php esc_html_e( 'GỬI YÊU CẦU ĐĂNG KÝ NGAY', 'spl' ); ?>
						</button>
					</form>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>

<!-- Toast notification (driven by showToast() in inc/page-home.js) -->
<div id="toast-notify" role="status" aria-live="polite" class="fixed bottom-6 right-6 z-[120] max-w-xs bg-white border border-slate-100 shadow-hover-card rounded-xl px-4 py-3 flex items-center gap-3 opacity-0 translate-y-5 pointer-events-none transition-all duration-300">
	<span id="toast-icon" class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs shrink-0"></span>
	<span id="toast-msg" class="text-xs font-semibold text-slate-700"></span>
</div>
