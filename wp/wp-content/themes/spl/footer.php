<?php
/**
 * The template for displaying the footer — dailyxedien.vn.
 *
 * Footer 4 cột (navy) + copyright + nút nổi. Converted from htmlmau (Tailwind v4).
 * Icons: inline SVG (spl_icon helper, định nghĩa ở header.php).
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

// ── ACF options ──
$hotline      = Helper::getField( 'hotline', 'option' ) ?: '0933 505 222';
$email        = Helper::getField( 'email', 'option' ) ?: 'info@dailyxedien.vn';
$address      = Helper::getField( 'address', 'option' ) ?: '466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP.HCM';
$website_url  = Helper::getField( 'website_url', 'option' ) ?: 'https://www.dailyxedien.vn';
$footer_desc  = Helper::getField( 'footer_desc', 'option' ) ?: __( 'Dailyxedien.vn - Hệ thống phân phối xe điện, xe 50cc, xe máy điện chính hãng. Cam kết sản phẩm rõ nguồn gốc, chính sách giá minh bạch và hậu mãi dễ theo dõi.', 'spl' );

$hotline_display = is_array( $hotline ) ? ( $hotline['title'] ?? $hotline['url'] ?? '0933 505 222' ) : $hotline;
$hotline_url     = is_array( $hotline ) ? ( $hotline['url'] ?? 'tel:' . preg_replace( '/[^0-9+]/', '', $hotline_display ) ) : 'tel:' . preg_replace( '/[^0-9+]/', '', $hotline );

// Social links (ACF options → fallback brand-guide).
$facebook_url = Helper::getField( 'facebook_url', 'option' ) ?: 'https://www.facebook.com/DaiLyXeDien/';
$youtube_url  = Helper::getField( 'youtube_url', 'option' ) ?: 'https://www.youtube.com/@XeDien';
$tiktok_url   = Helper::getField( 'tiktok_url', 'option' ) ?: 'https://www.tiktok.com/@dailyxedienhcm';
$zalo_url     = Helper::getField( 'zalo_url', 'option' ) ?: 'https://zalo.me/0933505222';

// Brand-style social icons (inline SVG, filled).
$footer_socials = [
	'facebook' => [ 'url' => $facebook_url, 'label' => 'Facebook', 'svg' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>' ],
	'youtube'  => [ 'url' => $youtube_url, 'label' => 'YouTube', 'svg' => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>' ],
	'tiktok'   => [ 'url' => $tiktok_url, 'label' => 'TikTok', 'svg' => '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>' ],
	'zalo'     => [ 'url' => $zalo_url, 'label' => 'Zalo', 'svg' => '<path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/>' ],
];

?>
</main>

<?php
/** Hook: spl_footer_before_action. */
do_action( 'spl_footer_before_action' );

// Sitewide company activity gallery (above footer). Hidden when empty.
get_template_part( 'parts/global/company-activity' );
?>

<!-- ===== FOOTER ===== -->
<footer class="bg-navy text-slate-400 text-sm pt-16 pb-8 border-t border-white/10">
	<div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">

		<!-- Company + social -->
		<div class="space-y-4">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-3">
				<?php if ( has_custom_logo() ) :
					echo get_custom_logo();
				else : ?>
					<div class="bg-primary text-white font-black p-2 rounded-xl text-lg">D<span class="text-accent">XD</span></div>
					<span class="text-xl font-extrabold text-white"><?php bloginfo( 'name' ); ?></span>
				<?php endif; ?>
			</a>
			<p class="text-xs leading-relaxed text-slate-400"><?php echo esc_html( $footer_desc ); ?></p>
			<div class="flex items-center gap-3 pt-2">
				<?php foreach ( $footer_socials as $social ) :
					if ( empty( $social['url'] ) || '#' === $social['url'] ) { continue; }
					?>
					<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $social['label'] ); ?>" class="w-8 h-8 rounded-full bg-white/10 hover:bg-primary hover:text-white flex items-center justify-center transition-colors text-slate-300">
						<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $social['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></svg>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Policy -->
		<div class="space-y-4">
			<h4 class="text-white font-bold text-sm tracking-wide"><?php esc_html_e( 'CHÍNH SÁCH CHUNG', 'spl' ); ?></h4>
			<?php if ( has_nav_menu( 'policy-nav' ) ) : ?>
				<nav class="dxd-footermenu" aria-label="<?php esc_attr_e( 'Chính sách', 'spl' ); ?>">
					<?php wp_nav_menu( [ 'theme_location' => 'policy-nav', 'container' => false, 'items_wrap' => '<ul class="space-y-2 text-xs">%3$s</ul>', 'fallback_cb' => false, 'depth' => 1 ] ); ?>
				</nav>
			<?php else : ?>
				<ul class="space-y-2 text-xs">
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Chính sách bảo hành', 'spl' ); ?></a></li>
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Chính sách đổi trả trong 7 ngày', 'spl' ); ?></a></li>
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Chính sách vận chuyển & giao nhận', 'spl' ); ?></a></li>
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Chính sách thanh toán linh hoạt', 'spl' ); ?></a></li>
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Chính sách bảo mật thông tin', 'spl' ); ?></a></li>
				</ul>
			<?php endif; ?>
		</div>

		<!-- Support -->
		<div class="space-y-4">
			<h4 class="text-white font-bold text-sm tracking-wide"><?php esc_html_e( 'HỖ TRỢ KHÁCH HÀNG', 'spl' ); ?></h4>
			<?php if ( has_nav_menu( 'about-nav' ) ) : ?>
				<nav class="dxd-footermenu" aria-label="<?php esc_attr_e( 'Hỗ trợ', 'spl' ); ?>">
					<?php wp_nav_menu( [ 'theme_location' => 'about-nav', 'container' => false, 'items_wrap' => '<ul class="space-y-2 text-xs">%3$s</ul>', 'fallback_cb' => false, 'depth' => 1 ] ); ?>
				</nav>
			<?php else : ?>
				<ul class="space-y-2 text-xs">
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Hướng dẫn mua hàng trực tuyến', 'spl' ); ?></a></li>
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Hướng dẫn trả góp 0%', 'spl' ); ?></a></li>
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Tra cứu tiến độ đơn hàng', 'spl' ); ?></a></li>
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Câu hỏi thường gặp (FAQs)', 'spl' ); ?></a></li>
					<li><a href="#" class="hover:text-white transition-colors"><?php esc_html_e( 'Bản đồ hệ thống đại lý', 'spl' ); ?></a></li>
				</ul>
			<?php endif; ?>
		</div>

		<!-- Contact -->
		<div class="space-y-4">
			<h4 class="text-white font-bold text-sm tracking-wide"><?php esc_html_e( 'LIÊN HỆ VỚI CHÚNG TÔI', 'spl' ); ?></h4>
			<div class="space-y-3 text-xs">
				<p class="flex items-start gap-2.5 leading-relaxed">
					<span class="text-primary-300 mt-0.5 shrink-0"><?php echo spl_icon( 'map-pin', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span><?php echo esc_html( $address ); ?></span>
				</p>
				<p class="flex items-center gap-2.5">
					<span class="text-primary-300 shrink-0"><?php echo spl_icon( 'phone', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<a href="<?php echo esc_url( $hotline_url ); ?>" class="hover:text-white transition-colors"><?php echo esc_html( $hotline_display ); ?></a>
				</p>
				<p class="flex items-center gap-2.5">
					<span class="text-primary-300 shrink-0"><?php echo spl_icon( 'mail', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<a href="mailto:<?php echo esc_attr( $email ); ?>" class="hover:text-white transition-colors"><?php echo esc_html( $email ); ?></a>
				</p>
				<?php if ( $website_url ) : ?>
					<p class="flex items-center gap-2.5">
						<span class="text-primary-300 shrink-0"><?php echo spl_icon( 'bolt', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<a href="<?php echo esc_url( $website_url ); ?>" target="_blank" rel="noopener" class="hover:text-white transition-colors"><?php echo esc_html( preg_replace( '#^https?://#', '', untrailingslashit( $website_url ) ) ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Copyright -->
	<div class="border-t border-white/10 pt-8 text-center text-xs text-slate-500 max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
		<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Tất cả bản quyền được bảo lưu.', 'spl' ); ?></p>
		<div class="flex items-center gap-4">
			<a href="#" class="hover:text-slate-300 transition-colors"><?php esc_html_e( 'Bảo mật', 'spl' ); ?></a>
			<span>•</span>
			<a href="#" class="hover:text-slate-300 transition-colors"><?php esc_html_e( 'Điều khoản sử dụng', 'spl' ); ?></a>
		</div>
	</div>
</footer>

<!-- ===== NÚT NỔI ===== -->
<div class="fixed right-4 bottom-4 z-[90] flex flex-col gap-3">
	<a href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-[#0068ff] text-white flex items-center justify-center shadow-lg ring-pulse" aria-label="Chat Zalo" title="Chat Zalo">
		<span class="text-[11px] font-black">Zalo</span>
	</a>
	<a href="<?php echo esc_url( $hotline_url ); ?>" class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center shadow-lg" aria-label="<?php esc_attr_e( 'Gọi điện', 'spl' ); ?>" title="<?php esc_attr_e( 'Gọi điện', 'spl' ); ?>">
		<?php echo spl_icon( 'phone', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
	<button id="back-to-top" data-scroll-top class="w-12 h-12 rounded-full bg-slate-800 hover:bg-slate-900 text-white flex items-center justify-center shadow-lg" aria-label="<?php esc_attr_e( 'Lên đầu trang', 'spl' ); ?>">
		<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="18 15 12 9 6 15"/></svg>
	</button>
</div>

<?php
/** Hook: spl_footer_action. */
do_action( 'spl_footer_action' );

wp_footer();
?>
</body>
</html>
