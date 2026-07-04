<?php
/**
 * Cooperation template part - Registration Form & FAQs.
 *
 * @package SPL
 */

use SPL\Core\Helper;

$form_title       = $args['form_title'] ?? 'Đăng ký đại lý';
$form_subtitle    = $args['form_subtitle'] ?? 'Chúng tôi sẽ liên hệ bạn trong vòng 24h làm việc.';
$cf7_shortcode    = $args['cf7_shortcode'] ?? '';
$contact_title    = $args['contact_title'] ?? 'Liên hệ trực tiếp';
$contact_subtitle = $args['contact_subtitle'] ?? 'Đội ngũ phát triển đại lý luôn sẵn sàng tư vấn chi tiết về chính sách hợp tác.';

$contacts = $args['contacts'] ?? [
	[
		'icon'  => '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
		'label' => 'Hotline 1',
		'value' => '0933 505 222 (Mr. Tuấn)',
	],
	[
		'icon'  => '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
		'label' => 'Hotline 2',
		'value' => '0901 234 567 (Ms. Trang)',
	],
	[
		'icon'  => '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
		'label' => 'Email',
		'value' => 'daily@dailyxedien.vn',
	],
	[
		'icon'  => '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
		'label' => 'Thời gian',
		'value' => '8:00 – 18:00, T2 – CN',
	]
];

$faq_title = $args['faq_title'] ?? 'Câu hỏi thường gặp';
$faqs      = $args['faqs'] ?? [
	[
		'question' => 'Vốn đầu tư ban đầu bao nhiêu?',
		'answer'   => 'Tùy gói hợp tác, vốn từ 50 triệu (Cấp 3) đến 200 triệu (Cấp 1). Bao gồm tiền hàng đợt đầu, không yêu cầu phí nhượng quyền.',
	],
	[
		'question' => 'Có cần kinh nghiệm bán xe không?',
		'answer'   => 'Không bắt buộc. Chúng tôi có chương trình đào tạo toàn diện từ kỹ thuật, tư vấn bán hàng đến xử lý bảo hành.',
	],
	[
		'question' => 'Hàng tồn kho xử lý thế nào?',
		'answer'   => 'Hỗ trợ đổi mẫu xe chậm bán, điều chuyển hàng giữa các đại lý trong cùng khu vực. Không để đại lý chịu rủi ro tồn kho.',
	],
	[
		'question' => 'Thời gian hoàn vốn dự kiến?',
		'answer'   => 'Trung bình 3-6 tháng với Cấp 3, 6-12 tháng với Cấp 1 (tuỳ vùng và mức độ đầu tư). Đội ngũ hỗ trợ đánh giá chi tiết khi tư vấn.',
	]
];
?>

<section class="max-w-7xl mx-auto px-4 py-14 md:py-20 border-t border-slate-100" id="register-form">
	<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-12">
		<!-- Form Column -->
		<div class="bg-white border border-slate-100 rounded-2xl p-6 md:p-8 shadow-premium">
			<div class="flex items-center gap-3 mb-6">
				<span class="w-1.5 h-6 bg-primary rounded-full"></span>
				<h2 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $form_title ); ?></h2>
			</div>

			<?php if ( ! empty( $cf7_shortcode ) ) : ?>
				<div class="cf7-cooperation-form">
					<?php echo do_shortcode( $cf7_shortcode ); ?>
				</div>
			<?php else : ?>
				<form id="coop-static-form" class="space-y-4">
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<div>
							<label class="block text-xs font-bold text-slate-700 mb-1.5"><?php esc_html_e( 'Họ và tên', 'spl' ); ?> <span class="text-red-500">*</span></label>
							<input type="text" required placeholder="Nguyễn Văn A" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all duration-200">
						</div>
						<div>
							<label class="block text-xs font-bold text-slate-700 mb-1.5"><?php esc_html_e( 'Số điện thoại', 'spl' ); ?> <span class="text-red-500">*</span></label>
							<input type="tel" required placeholder="09xx xxx xxx" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all duration-200">
						</div>
					</div>
					<div>
						<label class="block text-xs font-bold text-slate-700 mb-1.5"><?php esc_html_e( 'Email', 'spl' ); ?></label>
						<input type="email" placeholder="email@example.com" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all duration-200">
					</div>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<div>
							<label class="block text-xs font-bold text-slate-700 mb-1.5"><?php esc_html_e( 'Tỉnh/Thành phố', 'spl' ); ?> <span class="text-red-500">*</span></label>
							<select required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary bg-white cursor-pointer transition-all duration-200">
								<option value=""><?php esc_html_e( 'Chọn tỉnh thành', 'spl' ); ?></option>
								<option>TP. Hồ Chí Minh</option>
								<option>Hà Nội</option>
								<option>Đà Nẵng</option>
								<option>Cần Thơ</option>
								<option>Bình Dương</option>
								<option>Đồng Nai</option>
								<option>Long An</option>
								<option>Tây Ninh</option>
								<option>Khác</option>
							</select>
						</div>
						<div>
							<label class="block text-xs font-bold text-slate-700 mb-1.5"><?php esc_html_e( 'Gói hợp tác quan tâm', 'spl' ); ?></label>
							<select class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary bg-white cursor-pointer transition-all duration-200">
								<option>Đại lý Cấp 3 (20%)</option>
								<option selected>Đại lý Cấp 2 (28%)</option>
								<option>Đại lý Cấp 1 (35%)</option>
							</select>
						</div>
					</div>
					<div>
						<label class="block text-xs font-bold text-slate-700 mb-1.5"><?php esc_html_e( 'Bạn đã có cửa hàng chưa?', 'spl' ); ?></label>
						<div class="flex items-center gap-4 py-1">
							<label class="flex items-center gap-2 cursor-pointer">
								<input type="radio" name="has_store" value="yes" class="accent-primary w-4 h-4">
								<span class="text-xs text-slate-600"><?php esc_html_e( 'Đã có cửa hàng', 'spl' ); ?></span>
							</label>
							<label class="flex items-center gap-2 cursor-pointer">
								<input type="radio" name="has_store" value="no" checked class="accent-primary w-4 h-4">
								<span class="text-xs text-slate-600"><?php esc_html_e( 'Chưa có, muốn mở mới', 'spl' ); ?></span>
							</label>
						</div>
					</div>
					<div>
						<label class="block text-xs font-bold text-slate-700 mb-1.5"><?php esc_html_e( 'Ghi chú / câu hỏi', 'spl' ); ?></label>
						<textarea rows="3" placeholder="Mô tả nhu cầu hợp tác, quy mô kinh doanh mong muốn..." class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all duration-200 resize-none"></textarea>
					</div>
					<button type="submit" id="coop-submit-btn" class="w-full bg-primary hover:bg-primary-hover active:scale-[0.98] text-white font-bold text-sm py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
						<span><?php esc_html_e( 'GỬI ĐĂNG KÝ ĐẠI LÝ', 'spl' ); ?></span>
					</button>
					<p class="text-[10px] text-slate-400 text-center"><?php echo esc_html( $form_subtitle ); ?></p>
				</form>

				<script>
					(function() {
						const form = document.getElementById('coop-static-form');
						if (!form) return;
						form.addEventListener('submit', function(e) {
							e.preventDefault();
							const btn = document.getElementById('coop-submit-btn');
							if (!btn) return;
							
							const originalHTML = btn.innerHTML;
							btn.innerHTML = '<svg class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span><?php esc_attr_e( 'Đang gửi...', 'spl' ); ?></span>';
							btn.disabled = true;

							setTimeout(() => {
								btn.innerHTML = '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> <span><?php esc_attr_e( 'Đã gửi thành công!', 'spl' ); ?></span>';
								btn.classList.remove('bg-primary', 'hover:bg-primary-hover');
								btn.classList.add('bg-emerald-500');

								setTimeout(() => {
									btn.innerHTML = originalHTML;
									btn.classList.remove('bg-emerald-500');
									btn.classList.add('bg-primary', 'hover:bg-primary-hover');
									btn.disabled = false;
									form.reset();
								}, 3000);
							}, 1500);
						});
					})();
				</script>
			<?php endif; ?>
		</div>

		<!-- Direct Contacts & FAQs Column -->
		<div class="space-y-6">
			<?php if ( ! empty( $contacts ) ) : ?>
				<div class="bg-gradient-to-br from-primary to-[#1a5f9f] rounded-2xl p-6 md:p-8 text-white">
					<div class="w-12 h-12 bg-white/15 rounded-xl flex items-center justify-center mb-4">
						<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
					</div>
					<h3 class="text-lg font-bold mb-2"><?php echo esc_html( $contact_title ); ?></h3>
					<p class="text-sm text-white/70 leading-relaxed mb-5"><?php echo esc_html( $contact_subtitle ); ?></p>
					
					<div class="space-y-3.5 text-sm">
						<?php foreach ( $contacts as $contact ) : ?>
							<div class="flex items-start gap-3">
								<?php 
								if ( str_starts_with( trim( $contact['icon'] ), '<svg' ) ) {
									echo $contact['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								} else {
									echo '<i class="' . esc_attr( $contact['icon'] ) . '"></i>';
								}
								?>
								<div>
									<span class="text-xs text-white/50 block leading-none mb-1"><?php echo esc_html( $contact['label'] ); ?></span>
									<strong class="text-white font-semibold"><?php echo esc_html( $contact['value'] ); ?></strong>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $faqs ) ) : ?>
				<div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-premium">
					<h3 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
						<svg class="w-4 h-4 text-primary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
						<span><?php echo esc_html( $faq_title ); ?></span>
					</h3>
					
					<div class="space-y-3 cooperation-faq-accordion">
						<?php foreach ( $faqs as $faq ) : ?>
							<details class="group border border-slate-100 rounded-xl overflow-hidden transition-all duration-300">
								<summary class="flex items-center justify-between p-3.5 cursor-pointer text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors select-none">
									<span><?php echo esc_html( $faq['question'] ); ?></span>
									<svg class="w-3 h-3 text-slate-400 transition-transform duration-200 group-open:rotate-180 shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
								</summary>
								<div class="px-3.5 pb-3.5 text-xs text-slate-500 leading-relaxed border-t border-slate-50/50 pt-2.5">
									<?php echo wp_kses_post( wpautop( $faq['answer'] ) ); ?>
								</div>
							</details>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
