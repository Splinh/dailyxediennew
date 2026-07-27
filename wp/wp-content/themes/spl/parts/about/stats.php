<?php
/**
 * About — Stats section ("Những con số biết nói" & "Lời hứa của DailyXeDien.vn").
 *
 * Light mode design with brand primary colors:
 * - Light background (#f8fafc) matching timeline section
 * - Clean high-contrast cards & text
 * - Removed unbranded logo image beneath stats
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data     = $args ?? [];
$title    = $data['title'] ?? 'Những con số biết nói';
$subtitle = $data['subtitle'] ?? 'LỜI HỨA CỦA DAILYXEDIEN.VN';

$stats = ! empty( $data['stats'] ) ? $data['stats'] : [
	[
		'number' => '0%',
		'label'  => 'Tỉ lệ hàng giả, hàng nhái & linh kiện không rõ nguồn gốc',
	],
	[
		'number' => '100%',
		'label'  => 'Xe điện chính hãng, đầy đủ kiểm định CO/CQ',
	],
	[
		'number' => '20+',
		'label'  => 'Showroom & trung tâm bảo hành ủy quyền toàn quốc',
	],
	[
		'number' => '10,000+',
		'label'  => 'Khách hàng tin dùng và đánh giá hài lòng 5 sao',
	],
];

$promises = ! empty( $data['promises'] ) ? $data['promises'] : [
	[
		'title' => 'Chất lượng bền vững',
		'desc'  => 'Mỗi chiếc xe xuất kho đều trải qua quy trình kiểm tra 18 bước khắt khe. Chúng tôi cam kết 100% khung sườn, động cơ và ắc quy/pin Lithium đạt tiêu chuẩn an toàn tuyệt đối trước khi bàn giao cho khách hàng.',
	],
	[
		'title' => 'Năng lực chuyên môn sâu',
		'desc'  => 'Đội ngũ kỹ sư và kỹ thuật viên được đào tạo trực tiếp từ các hãng sản xuất tên tuổi như Bluera, VinFast, Yadea. Xử lý sự cố chính xác, hỗ trợ sửa chữa lưu động và bảo dưỡng định kỳ tận tâm.',
	],
	[
		'title' => 'Sự minh bạch tuyệt đối',
		'desc'  => 'Chúng tôi công khai 100% giá niêm yết, chính sách bảo hành bằng văn bản rõ ràng và cam kết không có bất kỳ chi phí ẩn nào. Khách hàng luôn biết trước toàn bộ chi phí trước khi xuống tiền mua xe.',
	],
	[
		'title' => 'Tâm thế đồng hành lâu dài',
		'desc'  => 'DailyXeDien.vn không dừng lại ở khâu bán xe, chúng tôi đồng hành cùng khách hàng trên mọi nẻo đường. Dịch vụ cứu hộ xe điện 24/7 và ứng dụng tra cứu lịch sử bảo hành giúp bạn hoàn toàn an tâm sử dụng.',
	],
];
?>

<section class="about-4-section py-12 md:py-20 bg-[#f8fafc] border-b border-slate-200/80 relative" id="about-stats-section">
	<div class="max-w-7xl mx-auto px-4 relative z-10">
		
		<!-- Main Title: Những con số biết nói -->
		<h2 class="site-title text-2xl md:text-3xl lg:text-4xl font-black text-center text-slate-900 tracking-tight uppercase mb-10 md:mb-12">
			<?php echo esc_html( $title ); ?>
		</h2>

		<!-- Stats Grid Showcase (Light Card Container) -->
		<div class="about-4-wrap bg-white border border-slate-200/90 rounded-3xl p-6 md:p-10 shadow-lg">
			<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-8">
				<?php foreach ( $stats as $stat ) : ?>
					<div class="about-4-item text-center p-5 md:p-6 bg-slate-50/90 rounded-2xl border border-slate-200/80 hover:border-primary/50 hover:shadow-md transition-all duration-300 group">
						<div class="caption">
							<h3 class="text-3xl md:text-4xl lg:text-5xl font-black text-primary group-hover:scale-105 transition-transform duration-300 mb-2">
								<?php echo esc_html( $stat['number'] ?? '' ); ?>
							</h3>
							<div class="text-xs md:text-sm text-slate-700 leading-relaxed font-semibold">
								<?php echo esc_html( $stat['label'] ?? '' ); ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Sub-section: LỜI HỨA CỦA DAILYXEDIEN.VN -->
		<div class="about-42 mt-14 md:mt-20">
			<h2 class="site-title text-2xl md:text-3xl lg:text-4xl font-black text-center text-slate-900 tracking-tight uppercase mb-8 md:mb-10">
				<?php echo esc_html( $subtitle ); ?>
			</h2>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
				<?php foreach ( $promises as $index => $item ) : ?>
					<div class="about-42-item bg-white border border-slate-200/90 rounded-2xl p-6 md:p-8 hover:border-primary/50 transition-all duration-300 relative group shadow-md">
						<div class="arrow absolute top-6 right-6 text-slate-400 group-hover:text-primary group-hover:translate-x-1 transition-all duration-300">
							<?= spl_icon( 'arrow-right', '', 22 ) ?>
						</div>
						<div class="caption pr-8">
							<h3 class="text-lg md:text-xl font-bold text-slate-900 mb-3 group-hover:text-primary transition-colors">
								<?php echo esc_html( $item['title'] ?? '' ); ?>
							</h3>
							<div class="text-xs md:text-sm text-slate-600 leading-relaxed">
								<?php echo esc_html( $item['desc'] ?? '' ); ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

	</div>
</section>
