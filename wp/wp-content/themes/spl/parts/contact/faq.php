<?php
/**
 * Contact — FAQ section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$badge = $data['badge'] ?? 'Câu Hỏi Thường Gặp';
$title = $data['title'] ?? 'Câu hỏi thường gặp';
$faqs  = ! empty( $data['faqs'] ) ? $data['faqs'] : [
	[
		'question' => 'Tôi có thể đặt hàng online và nhận xe tại nhà không?',
		'answer'   => 'Có. Bạn có thể đặt hàng qua website, Hotline hoặc Zalo. Chúng tôi giao xe miễn phí trong bán kính 10km, đồng thời hỗ trợ giao toàn quốc qua đối tác vận chuyển chuyên dụng. Đội ngũ kỹ thuật sẽ hướng dẫn sử dụng tại nhà.',
	],
	[
		'question' => 'Xe được bảo hành bao lâu?',
		'answer'   => 'Tùy hãng xe, thời gian bảo hành từ 1–3 năm theo chính sách chính hãng. Ắc quy bảo hành riêng 12–24 tháng. Bạn có thể bảo hành tại bất kỳ đại lý ủy quyền nào trong hệ thống.',
	],
	[
		'question' => 'Làm thế nào để mua trả góp 0%?',
		'answer'   => 'Bạn chỉ cần CMND/CCCD và bằng lái (nếu có). Nhân viên sẽ hỗ trợ làm hồ sơ trả góp ngay tại cửa hàng, duyệt trong 15 phút. Trả góp 0% lãi suất, kỳ hạn 6–24 tháng tùy chương trình.',
	],
	[
		'question' => 'Tôi muốn đổi/trả xe sau khi mua thì sao?',
		'answer'   => 'Chúng tôi hỗ trợ đổi trả trong vòng 7 ngày nếu xe có lỗi kỹ thuật từ nhà sản xuất. Sản phẩm phải còn nguyên tem, chưa đăng ký biển số. Liên hệ Hotline hoặc đến cửa hàng gần nhất để được hỗ trợ.',
	],
	[
		'question' => 'Tôi muốn trở thành đại lý/đối tác, liên hệ ở đâu?',
		'answer'   => 'Vui lòng gửi yêu cầu qua form trên trang này hoặc liên hệ email info@dailyxedien.vn với chủ đề "Hợp tác đại lý". Bộ phận kinh doanh sẽ liên hệ lại trong 24 giờ làm việc.',
	],
];
?>
<?php if ( ! empty( $faqs ) ) : ?>
<section class="mb-14 md:mb-20">
	<div class="container">
		<div class="flex items-center gap-3 mb-6">
			<span class="w-1.5 h-6 bg-primary-500 rounded-full"></span>
			<h2 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
		</div>

		<div class="space-y-4 max-w-4xl mx-auto">
			<?php foreach ( $faqs as $index => $faq ) : ?>
				<div class="faq-item bg-white border border-slate-100 rounded-2xl overflow-hidden transition-all shadow-premium">
					<button class="w-full flex items-center justify-between p-5 text-left font-bold text-slate-800 hover:text-primary-500 transition-colors gap-4" onclick="toggleFaq(this)">
						<span class="text-sm md:text-base"><?php echo esc_html( $faq['question'] ?? '' ); ?></span>
						<svg class="w-4 h-4 text-slate-400 transition-transform duration-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
					</button>
					<div class="faq-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
						<div class="p-5 pt-0 text-sm text-slate-600 border-t border-slate-50 leading-relaxed">
							<?php echo esc_html( $faq['answer'] ?? '' ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<script>
	function toggleFaq(button) {
		const item = button.closest('.faq-item');
		const content = item.querySelector('.faq-content');
		const icon = button.querySelector('svg');

		// Close other items
		document.querySelectorAll('.faq-item').forEach(otherItem => {
			if (otherItem !== item) {
				const otherContent = otherItem.querySelector('.faq-content');
				if (otherContent) otherContent.style.maxHeight = null;
				const otherIcon = otherItem.querySelector('svg');
				if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
			}
		});

		if (content.style.maxHeight) {
			content.style.maxHeight = null;
			icon.style.transform = 'rotate(0deg)';
		} else {
			content.style.maxHeight = content.scrollHeight + "px";
			icon.style.transform = 'rotate(180deg)';
		}
	}
</script>
<?php endif; ?>
