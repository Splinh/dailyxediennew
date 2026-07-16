<?php
/**
 * About — CTA section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data        = $args ?? [];
$title       = $data['title'] ?? 'Sẵn sàng đồng hành cùng bạn';
$desc        = $data['description'] ?? 'Bạn đang tìm xe điện cho bản thân hoặc gia đình? Hãy liên hệ ngay để được tư vấn miễn phí, chọn đúng xe phù hợp với ngân sách và nhu cầu sử dụng.';
$btn_primary = ! empty( $data['btn_primary']['url'] ) ? $data['btn_primary'] : [
	'url'   => home_url( '/lien-he/' ),
	'title' => 'Đăng ký tư vấn miễn phí',
];
$btn_outline = ! empty( $data['btn_outline']['url'] ) ? $data['btn_outline'] : [
	'url'   => 'tel:0933505222',
	'title' => 'Gọi 0933 505 222',
];
?>
<section class="py-12 md:py-16 bg-white">
	<div class="max-w-7xl mx-auto px-4">
		<div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-3xl p-8 md:p-12 text-white relative overflow-hidden reveal">
			<div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(255,255,255,0.1),transparent_50%)] pointer-events-none"></div>
			<div class="relative z-10 max-w-2xl mx-auto text-center">
				<div class="w-16 h-16 bg-white/15 rounded-2xl flex items-center justify-center mx-auto mb-6">
					<svg class="w-8 h-8 text-white fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m22 2-7.5 7.5M16 11l-2.5 2.5"/></svg>
				</div>
				<?php if ( $title ) : ?>
					<h2 class="text-2xl md:text-3xl font-black mb-4 tracking-tight text-white"><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>
				<?php if ( $desc ) : ?>
					<p class="text-sm text-white/80 leading-relaxed mb-8"><?php echo esc_html( $desc ); ?></p>
				<?php endif; ?>
				<div class="flex flex-col sm:flex-row gap-4 justify-center">
					<?php if ( ! empty( $btn_primary['url'] ) ) : ?>
						<a href="<?php echo esc_url( $btn_primary['url'] ); ?>" class="inline-flex items-center justify-center gap-2 bg-white text-primary-600 px-8 py-4 rounded-xl font-bold text-sm hover:bg-slate-50 transition-all shadow-lg transform hover:scale-105" <?php echo ! empty( $btn_primary['target'] ) ? 'target="_blank" rel="noopener"' : ''; ?>>
							<svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
							<?php echo esc_html( $btn_primary['title'] ?? 'Đăng ký tư vấn miễn phí' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( ! empty( $btn_outline['url'] ) ) : ?>
						<a href="<?php echo esc_url( $btn_outline['url'] ); ?>" class="inline-flex items-center justify-center gap-2 bg-white/15 border border-white/30 text-white px-8 py-4 rounded-xl font-bold text-sm hover:bg-white/25 transition-all" <?php echo ! empty( $btn_outline['target'] ) ? 'target="_blank" rel="noopener"' : ''; ?>>
							<svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
							<?php echo esc_html( $btn_outline['title'] ?? 'Gọi 0933 505 222' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>

