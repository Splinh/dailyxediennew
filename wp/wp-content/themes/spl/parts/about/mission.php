<?php
/**
 * About — Mission section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data     = $args ?? [];
$missions = ! empty( $data['missions'] ) ? $data['missions'] : [
	[
		'title' => 'Đổi mới công nghệ & Nhận thức giao thông',
		'desc'  => 'Đại Lý Xe Điện nỗ lực không ngừng nghỉ với mong muốn đem đến cho người tiêu dùng nhiều sản phẩm xe điện mang công nghệ tiên tiến. Đó chính là khát khao đem đến cho Việt Nam một môi trường sống tốt hơn, hạn chế khói bụi, tiếng ồn và quan trọng hơn cả, thay đổi nhận thức của người dân Việt trong việc lựa chọn phương tiện di chuyển hằng ngày.',
		'icon'  => '<svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
		'class' => 'from-primary-600 to-primary-800',
	],
	[
		'title' => 'Đóng góp cho xã hội & Môi trường',
		'desc'  => 'Đại Lý Xe Điện mong muốn những sản phẩm xe điện mà chúng tôi cung cấp sẽ có thể đóng góp cho xã hội, giúp cho Việt Nam trở thành một trong những quốc gia tích cực nhất trong việc bảo vệ môi trường, và từ đó có thể giúp người dân Việt Nam có một cuộc sống tốt đẹp hơn.',
		'icon'  => '<svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>',
		'class' => 'from-emerald-600 to-emerald-800',
	],
	[
		'title' => 'Gieo mầm xanh trên mọi cung đường',
		'desc'  => 'Chúng tôi nuôi hy vọng và mong chờ sản phẩm của mình sẽ trải một màu xanh đẹp đẽ trên mọi cung đường của đất nước, cũng như có thể gieo trồng được mầm xanh vào suy nghĩ, tư tưởng của tất cả người dân Việt.',
		'icon'  => '<svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v8"/><path d="m4.93 10.93 4.24 4.24"/><path d="M2 18h20"/></svg>',
		'class' => 'from-teal-600 to-teal-800',
	],
];

$fallback_icons = [
	'<svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
	'<svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>',
	'<svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
];

$fallback_classes = [
	'from-primary-600 to-primary-800',
	'from-emerald-600 to-emerald-800',
	'from-teal-600 to-teal-800',
];
?>
<?php if ( ! empty( $missions ) ) : ?>
<section class="py-12 md:py-16 bg-white">
	<div class="max-w-7xl mx-auto px-4">
		<!-- Section Header -->
		<div class="text-center mb-10 reveal">
			<div class="flex items-center gap-3 justify-center mb-2">
				<span class="w-1.5 h-6 bg-emerald-500 rounded-full"></span>
				<h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">SỨ MỆNH VÀ KHÁT VỌNG CỦA ĐẠI LÝ XE ĐIỆN</h2>
			</div>
			<p class="text-base font-bold text-emerald-600 italic max-w-xl mx-auto">“Sự thay đổi sẽ đem đến những điều tốt đẹp hơn”</p>
		</div>

		<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
			<?php foreach ( $missions as $index => $item ) : ?>
				<?php
				$bg_class   = ! empty( $item['class'] ) ? $item['class'] : $fallback_classes[ $index % count( $fallback_classes ) ];
				$icon_class = ! empty( $item['icon'] ) ? $item['icon'] : $fallback_icons[ $index % count( $fallback_icons ) ];
				?>
				<div class="bg-gradient-to-br <?php echo esc_attr( $bg_class ); ?> rounded-2xl p-6 md:p-8 text-white relative overflow-hidden group hover:shadow-hover-card transition-all reveal">
					<div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
					<div class="relative z-10">
						<div class="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
							<?php if ( strpos( $icon_class, '<svg' ) !== false ) : ?>
								<?php echo wp_kses( $icon_class, Helper::ksesSVG() ); ?>
							<?php else : ?>
								<i class="<?php echo esc_attr( $icon_class ); ?>"></i>
							<?php endif; ?>
						</div>
						<h3 class="text-lg font-bold mb-3"><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
						<p class="text-sm text-white/80 leading-relaxed"><?php echo esc_html( $item['desc'] ?? '' ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

