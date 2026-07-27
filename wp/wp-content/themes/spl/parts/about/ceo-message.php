<?php
/**
 * About — Message section ("Thông điệp từ Ban Giám Đốc" - VIP TALK CEO).
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data      = $args ?? [];
$title     = $data['title'] ?? 'VIP TALK — Thông Điệp Từ Ban Giám Đốc';
$subtitle  = $data['subtitle'] ?? 'Phỏng vấn Ông Vũ Trọng Thanh — CEO của AI EBike / Bluera Việt Nhật';
$ceo_name  = $data['ceo_name'] ?? 'ÔNG VŨ TRỌNG THANH';
$ceo_title = $data['ceo_title'] ?? 'TỔNG GIÁM ĐỐC / CEO AI EBIKE & BLUERA VIỆT NHẬT';
$video_url = $data['video_url'] ?? 'https://www.youtube.com/embed/HQSFNJ37aMo';
?>

<section class="about-brand py-14 md:py-20 bg-slate-50 border-y border-slate-200/80 overflow-hidden">
	<div class="max-w-7xl mx-auto px-4">
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
			
			<!-- Video Side (6 cols on Desktop - Left side) -->
			<div class="lg:col-span-6 relative group" data-aos="fade-right">
				<div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-900 aspect-video">
					<iframe class="w-full h-full" src="<?php echo esc_url( $video_url ); ?>" title="VIP TALK: Phỏng vấn Ông Vũ Trọng Thanh CEO của AI EBike" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen loading="lazy"></iframe>
				</div>

				<!-- Floating Experience Badge -->
				<div class="absolute -bottom-4 -right-4 md:-right-6 bg-white/95 backdrop-blur-md rounded-2xl shadow-xl p-4 border border-slate-100 hidden sm:flex items-center gap-3.5 z-10">
					<div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
						<?= spl_icon( 'shield-check', '', 22 ) ?>
					</div>
					<div>
						<p class="text-xs font-bold text-slate-800 uppercase tracking-wider">Khát Vọng Tiên Phong</p>
						<p class="text-[11px] text-slate-500">Đại Lý Xe Điện Bluera Việt Nhật</p>
					</div>
				</div>
			</div>

			<!-- CEO Content Letter Side (6 cols on Desktop - Right side) -->
			<div class="lg:col-span-6 space-y-6" data-aos="fade-left">
				<div class="block-content block-content-2">
					<div class="flex items-center gap-2.5 mb-2">
						<span class="w-2 h-6 bg-emerald-600 rounded-full"></span>
						<h2 class="text-sm md:text-base font-bold text-emerald-600 uppercase tracking-wider">
							<?php echo esc_html( $title ); ?>
						</h2>
					</div>
					<h3 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-tight mb-6">
						<?php echo esc_html( $subtitle ); ?>
					</h3>
					
					<div class="site-desc text-sm md:text-base text-slate-600 leading-relaxed space-y-4 italic border-t border-slate-200/80 pt-6">
						<p>“Chúng tôi bắt đầu hành trình từ khát khao mang đến cho người tiêu dùng Việt Nam những dòng xe điện chất lượng nhất, áp dụng công nghệ Nhật Bản tiên tiến và lắp ráp trực tiếp tại Việt Nam.”</p>
						<p>DailyXeDien.vn không ngừng nỗ lực phát triển mạng lưới phân phối và nâng cao chất lượng chăm sóc hậu mãi, nhằm đem tới sự an tâm tuyệt đối cho mọi gia đình trên toàn quốc.</p>

						<div class="pt-6 border-t border-slate-200 not-italic flex items-center justify-between">
							<div>
								<p class="font-black text-slate-900 text-base uppercase tracking-wider"><?php echo esc_html( $ceo_name ); ?></p>
								<p class="text-xs font-bold text-emerald-600 uppercase tracking-wide mt-0.5"><?php echo esc_html( $ceo_title ); ?></p>
							</div>
							<div>
								<span class="px-3.5 py-1.5 bg-slate-900 text-white font-black text-xs rounded-xl shadow-md">
									dailyxedien.vn
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</section>
