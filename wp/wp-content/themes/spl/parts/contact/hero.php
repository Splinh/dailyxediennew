<?php
/**
 * Contact — Hero section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$title = $data['title'] ?? 'Liên hệ <span class="text-emerald-400">&</span> Góp ý';
$desc  = $data['description'] ?? 'Chúng tôi luôn lắng nghe! Hãy liên hệ để được tư vấn, hỗ trợ hoặc gửi góp ý giúp dailyxedien.vn phục vụ bạn tốt hơn.';
?>
<section class="relative w-full bg-gradient-to-br from-[#0B2545] via-[#13315C] to-[#0A192F] overflow-hidden py-12 md:py-16">
	<div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_80%,rgba(245,158,11,0.15),transparent_50%)]"></div>
	<div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(14,165,233,0.15),transparent_50%)]"></div>

	<div class="relative z-10 max-w-7xl mx-auto px-4">
		<!-- Breadcrumb -->
		<nav class="flex items-center gap-2 text-xs text-white/60 mb-5" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-white transition-colors">Trang chủ</a>
			<svg class="w-2 h-2 text-white/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
			<span class="text-amber-400 font-semibold">Liên hệ & Góp ý</span>
		</nav>

		<h1 class="text-2xl md:text-3xl lg:text-4xl font-black text-white leading-tight tracking-tight">
			<?php echo wp_kses_post( str_replace( 'text-emerald-400', 'text-amber-400', $title ) ); ?>
		</h1>
		<p class="text-sm md:text-base text-slate-200/90 mt-3 max-w-2xl leading-relaxed">
			<?php echo esc_html( $desc ); ?>
		</p>
	</div>
</section>
