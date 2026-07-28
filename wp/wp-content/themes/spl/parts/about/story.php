<?php
/**
 * About — Story section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data         = $args ?? [];
$title        = $data['title'] ?? 'XE ĐẠP ĐIỆN ĐÃ RA ĐỜI NHƯ THẾ NÀO?';
$content      = $data['content'] ?? 'Vấn đề ô nhiễm môi trường đã trở thành một vấn đề đáng báo động trên toàn cầu và đặc biệt là ở những quốc gia đang phát triển như Việt Nam, tình trạng ô nghiễm môi trường từ những hoạt động của con người đã trở thành nỗi lo sợ của mỗi người dân Việt.<br><br>Hàng ngày, chúng ta luôn phải đối mặt với những nguy cơ ô nhiễm làm ảnh hưởng nghiệm trọng tới chất lượng sống như ô nhiễm tiếng ồn, ô nhiễm khói bụi… Với mong muốn mang tới một giải pháp giao thông thân thiện với môi trường và an toàn cho người sử dụng, Đại Lý Xe Điện đã đưa ra thị trường nhiều sản phẩm xe điện – loại phương tiện di chuyển an toàn và thông minh, có khả năng cải thiện tình trạng môi trường, đem tới cho con người một bầu không khí trong lành.';
$image_id     = $data['image'] ?? 0;
$badge_number = $data['badge_number'] ?? 'Công nghệ tiên tiến';
$badge_label  = $data['badge_label'] ?? 'Lắp ráp trực tiếp tại Việt Nam';

$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : 'https://dailyxedien.vn/wp-content/uploads/2023/04/mau-xe-dap-dien-dep-nhat-hien-nay.jpg';
?>
<section class="py-12 md:py-16 bg-white overflow-hidden">
	<div class="max-w-7xl mx-auto px-4">
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-12 items-center">
			
			<!-- Image (Left on Desktop) -->
			<div class="relative group reveal">
				<div class="absolute -inset-4 bg-gradient-to-r from-primary-500/20 to-emerald-500/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
				<div class="relative rounded-2xl overflow-hidden shadow-hover-card">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="w-full h-72 md:h-96 object-cover" loading="lazy">
					<div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-slate-900/80 to-transparent p-6">
						<p class="text-white text-sm font-bold">Đội ngũ dailyxedien.vn</p>
						<p class="text-slate-300 text-xs mt-1">Tận tâm phục vụ khách hàng mỗi ngày</p>
					</div>
				</div>
				<!-- Floating badge -->
				<div class="absolute -bottom-4 -right-4 md:-right-6 bg-white rounded-2xl shadow-premium p-4 border border-slate-100">
					<div class="flex items-center gap-3">
						<div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
							<svg class="w-5 h-5 text-emerald-500 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
						</div>
						<div>
							<p class="text-sm font-bold text-slate-800"><?php echo esc_html( $badge_number ); ?></p>
							<p class="text-[10px] text-slate-400"><?php echo esc_html( $badge_label ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Text Content (Right on Desktop) -->
			<div class="space-y-6 reveal">
				<div>
					<div class="flex items-center gap-3 mb-4">
						<span class="w-1.5 h-6 bg-primary-500 rounded-full"></span>
						<h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight"><?php echo esc_html( $title ); ?></h2>
					</div>
					<div class="text-sm md:text-base text-slate-600 leading-relaxed story-text-content">
						<?php echo wp_kses_post( wpautop( $content ) ); ?>
					</div>
				</div>

				<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
					<div class="flex items-start gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100">
						<div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-500 flex items-center justify-center shrink-0 mt-0.5">
							<svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
						</div>
						<div>
							<p class="text-sm font-bold text-slate-800">100% chính hãng</p>
							<p class="text-xs text-slate-500 mt-0.5">Nhập trực tiếp từ nhà sản xuất</p>
						</div>
					</div>
					<div class="flex items-start gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100">
						<div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center shrink-0 mt-0.5">
							<svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
						</div>
						<div>
							<p class="text-sm font-bold text-slate-800">Trả góp 0%</p>
							<p class="text-xs text-slate-500 mt-0.5">Hỗ trợ hồ sơ nhanh chóng</p>
						</div>
					</div>
					<div class="flex items-start gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100">
						<div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center shrink-0 mt-0.5">
							<svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
						</div>
						<div>
							<p class="text-sm font-bold text-slate-800">Bảo hành chính hãng</p>
							<p class="text-xs text-slate-500 mt-0.5">Hệ thống bảo hành toàn quốc</p>
						</div>
					</div>
					<div class="flex items-start gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100">
						<div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center shrink-0 mt-0.5">
							<svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
						</div>
						<div>
							<p class="text-sm font-bold text-slate-800">Giao xe tận nơi</p>
							<p class="text-xs text-slate-500 mt-0.5">Miễn phí bán kính 10km</p>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</section>

