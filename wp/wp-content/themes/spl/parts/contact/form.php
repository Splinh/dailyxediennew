<?php
/**
 * Contact — Form + Map + Social + Hotline section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data          = $args ?? [];
$form_title    = $data['form_title'] ?? 'Gửi Tin Nhắn Cho Chúng Tôi';
$form_desc     = $data['form_desc'] ?? 'Để lại thông tin, chúng tôi sẽ liên hệ tư vấn miễn phí trong thời gian sớm nhất.';
$cf7_shortcode = $data['cf7_shortcode'] ?? '';
$map_title     = $data['map_title'] ?? 'Vị Trí Của Chúng Tôi';
$map_embed     = ! empty( $data['map_embed_url'] ) ? $data['map_embed_url'] : 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.522888177579!2d106.77353957591631!3d10.772594659223707!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752701b22e17eb%3A0xe54e38e6583d73b!2zNDY2IE5ndXnhu4VuIER1eSBUcmluaCwgQsOsbmggVHLGsG5nIMSQw7RuZywgUXXhuq1uIDIsIEjhu5MgQ2jDrSBNaW5oLCBWaeG7h3QgTmFt!5e0!3m2!1svi!2svn!4v1710000000000!5m2!1svi!2svn';
$social_title  = $data['social_title'] ?? 'Kết Nối Với Chúng Tôi';
$social_desc   = $data['social_desc'] ?? 'Theo dõi chúng tôi trên mạng xã hội để cập nhật sản phẩm mới và khuyến mãi hấp dẫn.';
$hotline_title = $data['hotline_title'] ?? 'Gọi Ngay Hotline';
$hotline_desc  = $data['hotline_desc'] ?? 'Tư vấn miễn phí, hỗ trợ 7 ngày/tuần';

$hotline     = Helper::getField( 'hotline', 'option' ) ?: '0933 505 222';
$hotline_url = 'tel:' . preg_replace( '/[^0-9+]/', '', $hotline );

$social    = get_option( 'social_link__options' ) ?: [];
$fb_url    = ! empty( $social['facebook']['url'] ) ? $social['facebook']['url'] : 'https://www.facebook.com/dailyxedien.vn';
$yt_url    = ! empty( $social['youtube']['url'] ) ? $social['youtube']['url'] : 'https://www.youtube.com/c/dailyxedien';
$zalo_url  = ! empty( $social['zalo']['url'] ) ? $social['zalo']['url'] : 'https://zalo.me/0933505222';
?>
<section class="mb-14 md:mb-20">
	<div class="container">
		<div class="grid grid-cols-1 lg:grid-cols-5 gap-8 md:gap-12">

			<!-- Form Area (3 cols) -->
			<div class="lg:col-span-3">
				<div class="bg-white border border-slate-100 rounded-2xl shadow-premium p-6 md:p-8">
					<div class="flex items-center gap-3 mb-6">
						<span class="w-1.5 h-6 bg-amber-500 rounded-full"></span>
						<h2 class="text-xl md:text-2xl font-black text-[#0B2545] tracking-tight"><?php echo esc_html( $form_title ); ?></h2>
					</div>
					<?php if ( $form_desc ) : ?>
						<p class="text-sm text-slate-500 mb-8 -mt-2"><?php echo esc_html( $form_desc ); ?></p>
					<?php endif; ?>

					<?php if ( $cf7_shortcode ) : ?>
						<div class="wpcf7-contact-form-wrapper">
							<?php echo do_shortcode( $cf7_shortcode ); ?>
						</div>
					<?php else : ?>
						<form class="space-y-5" method="post" action="#">
							<!-- Name + Phone -->
							<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
								<div>
									<label for="contact-name" class="block text-xs font-bold text-slate-700 mb-2">Họ và tên <span class="text-red-500">*</span></label>
									<input type="text" id="contact-name" name="name" required placeholder="Nhập họ tên của bạn"
										class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#0B2545] focus:ring-2 focus:ring-[#0B2545]/10 transition-all">
								</div>
								<div>
									<label for="contact-phone" class="block text-xs font-bold text-slate-700 mb-2">Số điện thoại <span class="text-red-500">*</span></label>
									<input type="tel" id="contact-phone" name="phone" required placeholder="0912 345 678" pattern="[0-9]{10,11}"
										class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#0B2545] focus:ring-2 focus:ring-[#0B2545]/10 transition-all">
								</div>
							</div>

							<!-- Email -->
							<div>
								<label for="contact-email" class="block text-xs font-bold text-slate-700 mb-2">Email</label>
								<input type="email" id="contact-email" name="email" placeholder="email@example.com"
									class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#0B2545] focus:ring-2 focus:ring-[#0B2545]/10 transition-all">
							</div>

							<!-- Subject -->
							<div>
								<label for="contact-subject" class="block text-xs font-bold text-slate-700 mb-2">Chủ đề <span class="text-red-500">*</span></label>
								<select id="contact-subject" name="subject" required
									class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#0B2545] focus:ring-2 focus:ring-[#0B2545]/10 transition-all appearance-none cursor-pointer">
									<option value="">— Chọn chủ đề —</option>
									<option value="tu-van">Tư vấn mua xe</option>
									<option value="bao-hanh">Bảo hành & Sửa chữa</option>
									<option value="tra-gop">Hỏi về trả góp</option>
									<option value="hop-tac">Hợp tác đại lý</option>
									<option value="gop-y">Góp ý dịch vụ</option>
									<option value="khieu-nai">Khiếu nại</option>
									<option value="khac">Khác</option>
								</select>
							</div>

							<!-- Message -->
							<div>
								<label for="contact-message" class="block text-xs font-bold text-slate-700 mb-2">Nội dung <span class="text-red-500">*</span></label>
								<textarea id="contact-message" name="message" required rows="5" placeholder="Mô tả chi tiết nội dung bạn muốn liên hệ..."
									class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#0B2545] focus:ring-2 focus:ring-[#0B2545]/10 transition-all resize-y"></textarea>
							</div>

							<!-- Submit Button: Amber/Orange -->
							<button type="submit" id="contact-submit-btn"
								class="w-full sm:w-auto bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-8 py-3.5 rounded-xl shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 transition-all flex items-center justify-center gap-2 text-sm active:scale-[0.98]">
								<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
								<span>Gửi thông tin liên hệ</span>
							</button>
						</form>
					<?php endif; ?>
				</div>
			</div>

			<!-- Sidebar Area (2 cols) -->
			<div class="lg:col-span-2 space-y-6">

				<!-- Business Hours -->
				<div class="bg-white border border-slate-100 rounded-2xl shadow-premium p-6">
					<div class="flex items-center gap-3 mb-5">
						<div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
							<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
						</div>
						<h3 class="font-bold text-[#0B2545] text-sm"><?php echo esc_html( $hotline_title ); ?></h3>
					</div>
					<div class="space-y-3 text-sm">
						<div class="flex justify-between items-center py-2 border-b border-slate-50">
							<span class="text-slate-600">Thứ 2 – Thứ 6</span>
							<span class="font-bold text-slate-800">8:00 – 21:00</span>
						</div>
						<div class="flex justify-between items-center py-2 border-b border-slate-50">
							<span class="text-slate-600">Thứ 7</span>
							<span class="font-bold text-slate-800">8:00 – 20:00</span>
						</div>
						<div class="flex justify-between items-center py-2 border-b border-slate-50">
							<span class="text-slate-600">Chủ nhật</span>
							<span class="font-bold text-slate-800">9:00 – 18:00</span>
						</div>
						<div class="flex justify-between items-center py-2">
							<span class="text-slate-600">Hotline tư vấn</span>
							<span class="font-bold text-amber-600">24/7</span>
						</div>
					</div>
				</div>

				<!-- Social Connections -->
				<?php if ( $fb_url || $yt_url || $zalo_url ) : ?>
					<div class="bg-white border border-slate-100 rounded-2xl shadow-premium p-6">
						<div class="flex items-center gap-3 mb-5">
							<div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
								<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
							</div>
							<h3 class="font-bold text-[#0B2545] text-sm"><?php echo esc_html( $social_title ); ?></h3>
						</div>
						<?php if ( $social_desc ) : ?>
							<p class="text-xs text-slate-500 mb-4"><?php echo esc_html( $social_desc ); ?></p>
						<?php endif; ?>
						<div class="grid grid-cols-2 gap-3">
							<?php if ( $fb_url ) : ?>
								<a href="<?php echo esc_url( $fb_url ); ?>" class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors group" target="_blank" rel="noopener">
									<svg class="w-5 h-5 text-blue-600 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
									<span class="text-xs font-semibold text-slate-700">Facebook</span>
								</a>
							<?php endif; ?>
							<?php if ( $yt_url ) : ?>
								<a href="<?php echo esc_url( $yt_url ); ?>" class="flex items-center gap-3 p-3 bg-red-50 hover:bg-red-100 rounded-xl transition-colors group" target="_blank" rel="noopener">
									<svg class="w-5 h-5 text-red-600 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/></svg>
									<span class="text-xs font-semibold text-slate-700">YouTube</span>
								</a>
							<?php endif; ?>
							<?php if ( $zalo_url ) : ?>
								<a href="<?php echo esc_url( $zalo_url ); ?>" class="flex items-center gap-3 p-3 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-colors group" target="_blank" rel="noopener">
									<svg class="w-5 h-5 text-indigo-600 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
									<span class="text-xs font-semibold text-slate-700">Zalo OA</span>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Support Box matching reference image style (Deep Navy + Amber Button) -->
				<div class="bg-[#0B2545] rounded-2xl p-6 text-white relative overflow-hidden border border-slate-800 shadow-xl">
					<div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl"></div>
					<div class="relative z-10">
						<div class="inline-block bg-amber-500 text-slate-950 text-[11px] font-black uppercase tracking-wider px-3 py-1 rounded-full mb-3 shadow-sm">
							HỖ TRỢ 24/7
						</div>
						<h3 class="font-extrabold text-base md:text-lg mb-2 leading-snug">Hệ Thống Xe Điện Bluera Việt Nhật</h3>
						<p class="text-xs text-slate-300 mb-5 leading-relaxed">Phân phối chính hãng, bảo hành tận tâm trên toàn quốc. Đội ngũ sẵn sàng giải đáp 24/7.</p>

						<div class="space-y-2.5">
							<a href="<?php echo esc_url( home_url( '/he-thong-cua-hang/' ) ); ?>" class="w-full inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-[#0B2545] px-4 py-3 rounded-xl text-xs font-black transition-all border border-white/80 shadow-md">
								<svg class="w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
								<span>Tìm cửa hàng gần nhất</span>
							</a>
							<a href="<?php echo esc_url( $hotline_url ); ?>" class="w-full inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-3 rounded-xl text-xs font-black transition-all shadow-md">
								<?php echo spl_icon( 'phone', 'w-4 h-4 text-slate-950 shrink-0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span>Gửi thông tin liên hệ / Hotline: <?php echo esc_html( $hotline ); ?></span>
							</a>
						</div>
					</div>
				</div>

			</div>

		</div>
	</div>
</section>
