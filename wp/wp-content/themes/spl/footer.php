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

// Floating Action options (default to true)
$show_zalo_float  = Helper::getField( 'show_zalo_float', 'option' );
$show_zalo_float  = ( null === $show_zalo_float ) ? true : (bool) $show_zalo_float;

$show_phone_float = Helper::getField( 'show_phone_float', 'option' );
$show_phone_float = ( null === $show_phone_float ) ? true : (bool) $show_phone_float;

$show_back_to_top = Helper::getField( 'show_back_to_top', 'option' );
$show_back_to_top = ( null === $show_back_to_top ) ? true : (bool) $show_back_to_top;

$hotline_display = is_array( $hotline ) ? ( $hotline['title'] ?? $hotline['url'] ?? '0933 505 222' ) : $hotline;
$hotline_url     = is_array( $hotline ) ? ( $hotline['url'] ?? 'tel:' . preg_replace( '/[^0-9+]/', '', $hotline_display ) ) : 'tel:' . preg_replace( '/[^0-9+]/', '', $hotline );

// Social links (ACF options → fallback brand-guide).
$facebook_url = Helper::getField( 'facebook_url', 'option' ) ?: 'https://www.facebook.com/DaiLyXeDien/';
$youtube_url  = Helper::getField( 'youtube_url', 'option' ) ?: 'https://www.youtube.com/@XeDien';
$tiktok_url   = Helper::getField( 'tiktok_url', 'option' ) ?: 'https://www.tiktok.com/@dailyxedienhcm';
$zalo_url     = Helper::getField( 'zalo_url', 'option' ) ?: 'https://zalo.me/0933505222';

$messenger_url = Helper::getField( 'messenger_url', 'option' );
if ( empty( $messenger_url ) ) {
	$fb_clean      = untrailingslashit( (string) $facebook_url );
	$fb_path       = preg_replace( '#^https?://(www\.)?(facebook\.com|fb\.com)/#i', '', $fb_clean );
	$messenger_url = ! empty( $fb_path ) ? 'https://m.me/' . $fb_path : 'https://m.me/DaiLyXeDien';
}
$working_hours = Helper::getField( 'working_hours', 'option' ) ?: '08:00 - 21:00';
$working_days  = Helper::getField( 'working_days', 'option' ) ?: __( 'Tất cả các ngày trong tuần', 'spl' );

// Brand-style social icons (official brand colors & vector SVGs).
$footer_socials = [
	'facebook' => [ 'url' => $facebook_url, 'label' => 'Facebook', 'bg' => 'bg-[#1877f2] hover:bg-[#1567d3]', 'svg' => '<path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>' ],
	'youtube'  => [ 'url' => $youtube_url, 'label' => 'YouTube', 'bg' => 'bg-[#ff0000] hover:bg-[#cc0000]', 'svg' => '<path fill="currentColor" d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>' ],
	'tiktok'   => [ 'url' => $tiktok_url, 'label' => 'TikTok', 'bg' => 'bg-[#111111] hover:bg-[#000000]', 'svg' => '<path fill="currentColor" d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.29 0 .56.04.82.12V9.4a6.27 6.27 0 0 0-1-.08 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V9.05a8.27 8.27 0 0 0 4.97 1.62V7.22a4.84 4.84 0 0 1-1.21-.53z"/>' ],
	'zalo'     => [ 'url' => $zalo_url, 'label' => 'Zalo', 'bg' => 'bg-[#0068ff] hover:bg-[#0054d1]', 'svg' => '<path fill="currentColor" d="M12 2C6.477 2 2 6.477 2 12c0 2.213.72 4.257 1.94 5.923L2.5 22l4.24-1.396C8.324 21.433 10.103 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm-3.5 12h-2v-4h2v4zm1.5 0h-1v-4h1v4zm4.5 0h-1.5l-1.5-2.5V14h-1v-4h1.5l1.5 2.5V10h1v4z"/>' ],
];

// Query store provinces for Mobile Dealer slide-up panel
$stores    = [];
$prov_list = [];
if ( function_exists( 'dxd_dealer_get_stores' ) ) {
	$stores = dxd_dealer_get_stores();
	$prov_counts = [];
	foreach ( $stores as $s ) {
		if ( ! empty( $s['p'] ) ) {
			$prov = $s['p'];
			if ( ! isset( $prov_counts[ $prov ] ) ) {
				$prov_counts[ $prov ] = 0;
			}
			$prov_counts[ $prov ]++;
		}
	}
	// Sort by store count descending
	arsort( $prov_counts );
	$prov_list = array_keys( $prov_counts );
}

?>
</main>

<?php
/** Hook: spl_footer_before_action. */
do_action( 'spl_footer_before_action' );

// Sitewide company activity gallery (above footer). Hidden when empty.
get_template_part( 'parts/global/company-activity' );
?>

<!-- ===== FOOTER ===== -->
<footer class="bg-slate-50 text-slate-600 text-sm pt-8 md:pt-16 pb-8 border-t border-slate-200">
	<div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">

		<!-- Company + social -->
		<div class="space-y-4">
			<?php if ( has_custom_logo() ) :
				the_custom_logo();
			else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-3" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<div class="bg-primary text-white font-black p-2 rounded-xl text-lg">D<span class="text-accent">XD</span></div>
					<span class="text-xl font-extrabold text-slate-800"><?php bloginfo( 'name' ); ?></span>
				</a>
			<?php endif; ?>
			<p class="text-xs leading-relaxed text-slate-500"><?php echo esc_html( $footer_desc ); ?></p>
			<div class="flex items-center gap-3 pt-2">
				<?php foreach ( $footer_socials as $key => $social ) :
					if ( empty( $social['url'] ) || '#' === $social['url'] ) { continue; }
					if ( 'zalo' === $key ) : ?>
						<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" aria-label="Zalo" class="w-8 h-8 rounded-full bg-[#0068ff] hover:bg-[#0054d1] text-white flex items-center justify-center transition-all shadow-sm font-black text-[9px] tracking-tighter leading-none select-none px-1">
							Zalo
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $social['label'] ); ?>" class="w-8 h-8 rounded-full <?php echo esc_attr( $social['bg'] ); ?> text-white flex items-center justify-center transition-all shadow-sm">
							<svg class="w-4 h-4 text-white fill-white" viewBox="0 0 24 24" aria-hidden="true"><?php echo $social['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></svg>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Policy -->
		<div class="space-y-4">
			<h3 class="text-slate-800 font-bold text-sm tracking-wide"><?php esc_html_e( 'CHÍNH SÁCH CHUNG', 'spl' ); ?></h3>
			<?php if ( has_nav_menu( 'policy-nav' ) ) : ?>
				<nav class="dxd-footermenu" aria-label="<?php esc_attr_e( 'Chính sách', 'spl' ); ?>">
					<?php wp_nav_menu( [ 'theme_location' => 'policy-nav', 'container' => false, 'items_wrap' => '<ul class="space-y-2 text-xs">%3$s</ul>', 'fallback_cb' => false, 'depth' => 1 ] ); ?>
				</nav>
			<?php else : ?>
				<ul class="space-y-2 text-xs">
					<li><a href="<?php echo esc_url( home_url( '/chinh-sach-bao-hanh/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Chính sách bảo hành', 'spl' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/chinh-sach-doi-tra-hang/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Chính sách đổi trả trong 7 ngày', 'spl' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/giao-hang-va-lap-dat/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Chính sách vận chuyển & giao nhận', 'spl' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/phuong-thuc-thanh-toan/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Chính sách thanh toán linh hoạt', 'spl' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/bao-mat-thong-tin-khach-hang/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Chính sách bảo mật thông tin', 'spl' ); ?></a></li>
				</ul>
			<?php endif; ?>
		</div>

		<!-- Support -->
		<div class="space-y-4">
			<h3 class="text-slate-800 font-bold text-sm tracking-wide"><?php esc_html_e( 'HỖ TRỢ KHÁCH HÀNG', 'spl' ); ?></h3>
			<?php if ( has_nav_menu( 'about-nav' ) ) : ?>
				<nav class="dxd-footermenu" aria-label="<?php esc_attr_e( 'Hỗ trợ', 'spl' ); ?>">
					<?php wp_nav_menu( [ 'theme_location' => 'about-nav', 'container' => false, 'items_wrap' => '<ul class="space-y-2 text-xs">%3$s</ul>', 'fallback_cb' => false, 'depth' => 1 ] ); ?>
				</nav>
			<?php else : ?>
				<ul class="space-y-2 text-xs">
					<li><a href="<?php echo esc_url( home_url( '/huong-dan-mua-hang/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Hướng dẫn mua hàng trực tuyến', 'spl' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/chinh-sach-ban-hang-dailyxedien-vn/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Chính sách bán hàng Dailyxedien.vn', 'spl' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/he-thong-cua-hang/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Hệ thống cửa hàng & đại lý', 'spl' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Thông tin liên hệ', 'spl' ); ?></a></li>
				</ul>
			<?php endif; ?>
		</div>

		<!-- Contact -->
		<div class="space-y-4">
			<h3 class="text-slate-800 font-bold text-sm tracking-wide"><?php esc_html_e( 'LIÊN HỆ VỚI CHÚNG TÔI', 'spl' ); ?></h3>
			<div class="space-y-3 text-xs">
				<p class="flex items-start gap-2.5 leading-relaxed">
					<span class="text-primary mt-0.5 shrink-0"><?php echo spl_icon( 'map-pin', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span><?php echo esc_html( $address ); ?></span>
				</p>
				<p class="flex items-center gap-2.5">
					<span class="text-primary shrink-0"><?php echo spl_icon( 'phone', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<a href="<?php echo esc_url( $hotline_url ); ?>" class="hover:text-primary transition-colors"><?php echo esc_html( $hotline_display ); ?></a>
				</p>
				<p class="flex items-center gap-2.5">
					<span class="text-primary shrink-0"><?php echo spl_icon( 'mail', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<a href="mailto:<?php echo esc_attr( $email ); ?>" class="hover:text-primary transition-colors"><?php echo esc_html( $email ); ?></a>
				</p>
				<?php if ( $website_url ) : ?>
					<p class="flex items-center gap-2.5">
						<span class="text-primary shrink-0"><?php echo spl_icon( 'bolt', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<a href="<?php echo esc_url( $website_url ); ?>" target="_blank" rel="noopener" class="hover:text-primary transition-colors"><?php echo esc_html( preg_replace( '#^https?://#', '', untrailingslashit( $website_url ) ) ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Copyright -->
	<div class="border-t border-slate-200 pt-4 md:pt-8 text-center text-xs text-slate-500 max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-2 md:gap-4">
		<p class="m-0">© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Tất cả bản quyền được bảo lưu.', 'spl' ); ?></p>
		<div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-6">
			<div class="flex items-center gap-3">
				<a href="<?php echo esc_url( home_url( '/bao-mat-thong-tin-khach-hang/' ) ); ?>" class="hover:text-slate-800 transition-colors"><?php esc_html_e( 'Bảo mật', 'spl' ); ?></a>
				<span>•</span>
				<a href="<?php echo esc_url( home_url( '/chinh-sach-ban-hang-dailyxedien-vn/' ) ); ?>" class="hover:text-slate-800 transition-colors"><?php esc_html_e( 'Điều khoản sử dụng', 'spl' ); ?></a>
			</div>
			<a href="http://online.gov.vn/nen-tang/d7eeaccf-92c4-4c57-9c19-cc749c427728" target="_blank" rel="noopener" class="inline-block transition-opacity hover:opacity-90 mt-0.5 sm:mt-0">
				<img src="<?php echo esc_url( get_theme_file_uri( 'resources/img/DaThongBao.png' ) ); ?>" alt="<?php esc_attr_e( 'Đã thông báo Bộ Công Thương', 'spl' ); ?>" width="120" height="34" class="h-8.5 w-auto" style="height: 34px;" />
			</a>
		</div>
	</div>
</footer>

<!-- ===== NÚT NỔI ===== -->
<?php if ( $show_zalo_float || $show_phone_float || $show_back_to_top ) : ?>
	<div class="fixed right-4 bottom-4 z-[90] flex flex-col gap-3" id="floating-btns">
		<?php if ( $show_zalo_float ) : ?>
			<a href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-[#0068ff] text-white flex items-center justify-center shadow-lg ring-pulse" aria-label="Chat Zalo" title="Chat Zalo">
				<span class="text-[11px] font-black">Zalo</span>
			</a>
		<?php endif; ?>
		<?php if ( $show_phone_float ) : ?>
			<a href="<?php echo esc_url( $hotline_url ); ?>" class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center shadow-lg" aria-label="<?php esc_attr_e( 'Gọi điện', 'spl' ); ?>" title="<?php esc_attr_e( 'Gọi điện', 'spl' ); ?>">
				<?php echo spl_icon( 'phone', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		<?php endif; ?>
		<?php if ( $show_back_to_top ) : ?>
			<button id="back-to-top" data-scroll-top class="w-12 h-12 rounded-full bg-slate-800 hover:bg-slate-900 text-white flex items-center justify-center shadow-lg" aria-label="<?php esc_attr_e( 'Lên đầu trang', 'spl' ); ?>">
				<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="18 15 12 9 6 15"/></svg>
			</button>
		<?php endif; ?>
	</div>
<?php endif; ?>

<!-- ===== MOBILE BOTTOM NAV ===== -->
<?php
$is_home   = is_front_page() || is_home();
$is_shop   = function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() || is_product() );
$is_dealer = is_page( 'he-thong-cua-hang' ) || is_post_type_archive( 'local_store' ) || is_singular( 'local_store' );
?>
<nav id="mobile-bottom-nav" aria-label="<?php esc_attr_e( 'Menu di động', 'spl' ); ?>">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo $is_home ? ' class="active"' : ''; ?>>
		<?php echo spl_icon( 'bolt', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'Trang chủ', 'spl' ); ?></span>
	</a>
	<button type="button" data-cat-panel-open<?php echo $is_shop ? ' class="active"' : ''; ?>>
		<?php echo spl_icon( 'menu', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'Danh mục', 'spl' ); ?></span>
	</button>
	<button type="button" data-news-panel-open>
		<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M16 8h2"/><path d="M16 12h2"/><path d="M16 16h2"/><path d="M6 8h6v8H6z"/></svg>
		<span><?php esc_html_e( 'Tin tức', 'spl' ); ?></span>
	</button>
	<button type="button" data-dealer-panel-open<?php echo $is_dealer ? ' class="active"' : ''; ?>>
		<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
		<span><?php esc_html_e( 'Đại lý', 'spl' ); ?></span>
	</button>
	<button type="button" data-contact-panel-open>
		<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
		<span><?php esc_html_e( 'Liên hệ', 'spl' ); ?></span>
	</button>
</nav>

<!-- ===== CATEGORY SLIDE-UP PANEL (Mobile) ===== -->
<div id="category-panel-overlay" data-cat-panel-close></div>
<div id="category-panel">
	<div class="cat-header">
		<h3><?php esc_html_e( 'Danh mục sản phẩm', 'spl' ); ?></h3>
		<button type="button" data-cat-panel-close aria-label="<?php esc_attr_e( 'Đóng', 'spl' ); ?>">
			<?php echo spl_icon( 'close', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	
	<?php
	// Cache the entire category panel — 10+ WP_Query calls take ~3-5s uncached.
	$cat_panel_cache_key = 'spl_footer_cat_panel_v1';
	$cat_panel_html      = get_transient( $cat_panel_cache_key );

	if ( false !== $cat_panel_html ) {
		echo $cat_panel_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — pre-escaped HTML.
	} else {
		ob_start();
	?>
	<?php
	$all_parent_cats = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'orderby'    => 'meta_value_num',
		'meta_key'   => 'order',
		'order'      => 'ASC',
	] );

	// Priority category slugs (Image 2 & User requirements)
	$priority_slugs = [
		'xe-dap-dien',
		'xe-dap-tro-luc',
		'xe-ba-gac-dien',
		'xe-may-dien',
		'xe-may-50cc',
		'xe-dap',
		'xe-tre-em',
		'phu-tung-xe-dien',
	];

	$parent_cats = [];
	if ( ! is_wp_error( $all_parent_cats ) && ! empty( $all_parent_cats ) ) {
		foreach ( $priority_slugs as $pslug ) {
			foreach ( $all_parent_cats as $idx => $cat ) {
				if ( $cat->slug === $pslug && $cat->count > 0 ) {
					$parent_cats[] = $cat;
					unset( $all_parent_cats[ $idx ] );
					break;
				}
			}
		}
		foreach ( $all_parent_cats as $cat ) {
			if ( $cat->count > 0 ) {
				$parent_cats[] = $cat;
			}
		}
	}

	if ( ! empty( $parent_cats ) ) :
		?>
		<div class="cat-content-layout">
			<!-- Cột trái: Danh mục cha (Chỉ lấy danh mục có sản phẩm) -->
			<div class="cat-sidebar-left">
				<?php foreach ( $parent_cats as $i => $cat ) : ?>
					<button class="cat-tab-item<?php echo $i === 0 ? ' active' : ''; ?>" onclick="switchCategoryTab(event, '<?php echo esc_attr( $cat->slug ); ?>')">
						<?php echo esc_html( $cat->name ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			
			<!-- Cột phải: Chi tiết từng danh mục -->
			<div class="cat-products-right">
				<?php foreach ( $parent_cats as $i => $cat ) : ?>
					<div id="cat-tab-panel-<?php echo esc_attr( $cat->slug ); ?>" class="cat-tab-panel<?php echo $i === 0 ? ' active' : ''; ?>">
						<?php
						$subcats = get_terms( [
							'taxonomy'   => 'product_cat',
							'hide_empty' => true,
							'parent'     => $cat->term_id,
							'orderby'    => 'meta_value_num',
							'meta_key'   => 'order',
							'order'      => 'ASC',
						] );

						// Priority subcategories (AIE BIKE, Bluesuda...)
						$priority_sub_slugs = [ 'xe-dap-dien-ai-ebike', 'xe-dap-tro-luc-bluesuda' ];
						if ( ! is_wp_error( $subcats ) && ! empty( $subcats ) ) :
							usort( $subcats, function( $a, $b ) use ( $priority_sub_slugs ) {
								$posA = array_search( $a->slug, $priority_sub_slugs, true );
								$posB = array_search( $b->slug, $priority_sub_slugs, true );
								if ( false !== $posA && false !== $posB ) return $posA <=> $posB;
								if ( false !== $posA ) return -1;
								if ( false !== $posB ) return 1;
								return 0;
							} );
							?>
							<div class="cat-subcats-section">
								<h4><?php esc_html_e( 'Danh mục con', 'spl' ); ?></h4>
								<div class="cat-subcats-grid">
									<?php foreach ( $subcats as $subcat ) : ?>
										<a href="<?php echo esc_url( get_term_link( $subcat ) ); ?>" class="cat-subcat-card flex items-center justify-between">
											<span><?php echo esc_html( $subcat->name ); ?></span>
											<span class="text-[10px] text-slate-400"> (<?php echo (int) $subcat->count; ?>)</span>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
						
						<?php
						$cat_term_ids = [ (int) $cat->term_id ];
						$child_ids    = get_term_children( $cat->term_id, 'product_cat' );
						if ( ! is_wp_error( $child_ids ) && ! empty( $child_ids ) ) {
							$cat_term_ids = array_merge( $cat_term_ids, $child_ids );
						}

						$prod_query = new WP_Query( [
							'post_type'      => 'product',
							'post_status'    => 'publish',
							'posts_per_page' => 4,
							'orderby'        => 'menu_order title',
							'order'          => 'ASC',
							'tax_query'      => [
								[
									'taxonomy'         => 'product_cat',
									'field'            => 'term_id',
									'terms'            => $cat_term_ids,
									'include_children' => true,
								],
							],
						] );
						if ( $prod_query->have_posts() ) :
							?>
							<div class="cat-popular-section">
								<h4><?php esc_html_e( 'Sản phẩm tiêu biểu', 'spl' ); ?></h4>
								<div class="cat-popular-list">
									<?php
									while ( $prod_query->have_posts() ) :
										$prod_query->the_post();
										$product = wc_get_product( get_the_ID() );
										$img_url = get_the_post_thumbnail_url( get_the_ID(), 'woocommerce_gallery_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' );
										?>
										<a href="<?php the_permalink(); ?>" class="cat-product-row flex items-center gap-3 p-2 rounded-lg bg-slate-50 hover:bg-white border border-slate-100 transition-all">
											<img loading="lazy" decoding="async" src="<?php echo esc_url( $img_url ); ?>" alt="<?php the_title_attribute(); ?>" width="48" height="48" class="w-12 h-12 object-contain rounded bg-white p-1 border border-slate-100 shrink-0">
											<div class="cat-product-info flex-1">
												<h5 class="text-xs font-bold text-slate-800 line-clamp-1"><?php the_title(); ?></h5>
												<span class="cat-product-price text-xs font-black text-red-600"><?php echo $product ? $product->get_price_html() : ''; ?></span>
											</div>
										</a>
									<?php endwhile; wp_reset_postdata(); ?>
								</div>
							</div>
						<?php endif; ?>
						<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="cat-see-more-link font-bold text-xs text-primary flex items-center justify-center gap-1.5 p-2.5 bg-primary-50 rounded-xl mt-3 hover:bg-primary hover:text-white transition-colors">
							<?php printf( esc_html__( 'Xem tất cả %s', 'spl' ), $cat->name ); ?> <i class="fa-solid fa-chevron-right text-[10px]"></i>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
<?php
	// End category panel transient cache.
	$cat_panel_html = ob_get_clean();
	set_transient( $cat_panel_cache_key, $cat_panel_html, HOUR_IN_SECONDS );
	echo $cat_panel_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} // end else (cache miss)
?>

<!-- ===== NEWS SLIDE-UP PANEL (Mobile) ===== -->
<div id="news-panel-overlay" data-news-panel-close></div>
<div id="news-panel">
	<div class="news-header">
		<h3><?php esc_html_e( 'Tin tức & Tư vấn', 'spl' ); ?></h3>
		<button type="button" data-news-panel-close aria-label="<?php esc_attr_e( 'Đóng', 'spl' ); ?>">
			<?php echo spl_icon( 'close', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<div class="news-content-layout">
		<div class="news-sidebar-left">
			<button class="news-tab-item active" onclick="switchNewsTab(event, 'all')">
				<?php esc_html_e( 'Tất cả', 'spl' ); ?>
			</button>
			<?php
			$news_cats = Helper::getField( 'bottom_nav_news_categories', 'option' );
			if ( empty( $news_cats ) ) {
				$news_cats = get_terms( [
					'taxonomy'   => 'category',
					'hide_empty' => true,
				] );
			}
			if ( ! is_wp_error( $news_cats ) && ! empty( $news_cats ) ) :
				foreach ( $news_cats as $cat ) :
					?>
					<button class="news-tab-item" onclick="switchNewsTab(event, '<?php echo esc_attr( $cat->slug ); ?>')">
						<?php echo esc_html( $cat->name ); ?>
					</button>
					<?php
				endforeach;
			endif;
			?>
		</div>
		
		<div class="news-articles-right">
			<div id="news-tab-panel-all" class="news-tab-panel active">
				<div class="news-list-vertical">
					<?php
					$all_posts = get_posts( [
						'numberposts' => 4,
						'post_status' => 'publish',
					] );
					foreach ( $all_posts as $post ) :
						setup_postdata( $post );
						$img = get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 140"><rect fill="#f1f5f9" width="200" height="140"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#94a3b8" font-size="14">DXD</text></svg>');
						?>
						<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" class="news-row-item">
							<img loading="lazy" decoding="async" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title( $post->ID ) ); ?>">
							<div class="news-row-info">
								<h5><?php echo esc_html( get_the_title( $post->ID ) ); ?></h5>
								<span><?php echo get_the_date( '', $post->ID ); ?></span>
							</div>
						</a>
					<?php endforeach; wp_reset_postdata(); ?>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ); ?>" class="news-see-more-link">
						<?php esc_html_e( 'Xem thêm tin tức', 'spl' ); ?> <i class="fa-solid fa-chevron-right"></i>
					</a>
				</div>
			</div>
			
			<?php
			if ( ! is_wp_error( $news_cats ) && ! empty( $news_cats ) ) :
				foreach ( $news_cats as $cat ) :
					?>
					<div id="news-tab-panel-<?php echo esc_attr( $cat->slug ); ?>" class="news-tab-panel">
						<div class="news-list-vertical">
							<?php
							$cat_posts = get_posts( [
								'numberposts' => 4,
								'category'    => $cat->term_id,
								'post_status' => 'publish',
							] );
							foreach ( $cat_posts as $post ) :
								setup_postdata( $post );
								$img = get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 140"><rect fill="#f1f5f9" width="200" height="140"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#94a3b8" font-size="14">DXD</text></svg>');
								?>
								<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" class="news-row-item">
									<img loading="lazy" decoding="async" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title( $post->ID ) ); ?>">
									<div class="news-row-info">
										<h5><?php echo esc_html( get_the_title( $post->ID ) ); ?></h5>
										<span><?php echo get_the_date( '', $post->ID ); ?></span>
									</div>
								</a>
							<?php endforeach; wp_reset_postdata(); ?>
							<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="news-see-more-link">
								<?php printf( esc_html__( 'Xem tất cả %s', 'spl' ), $cat->name ); ?> <i class="fa-solid fa-chevron-right"></i>
							</a>
						</div>
					</div>
					<?php
				endforeach;
			endif;
			?>
		</div>
	</div>
</div>

<!-- ===== DEALER SLIDE-UP PANEL (Mobile) ===== -->
<div id="dealer-panel-overlay" data-dealer-panel-close></div>
<div id="dealer-panel">
	<div class="dealer-header">
		<h3><?php esc_html_e( 'Hệ Thống Đại Lý', 'spl' ); ?></h3>
		<button type="button" data-dealer-panel-close aria-label="<?php esc_attr_e( 'Đóng', 'spl' ); ?>">
			<?php echo spl_icon( 'close', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<?php if ( ! empty( $prov_list ) ) : ?>
		<div class="dealer-content-layout">
			<!-- Cột trái: Tỉnh thành -->
			<div class="dealer-sidebar-left">
				<?php foreach ( $prov_list as $i => $prov ) : ?>
					<button class="dealer-tab-item<?php echo $i === 0 ? ' active' : ''; ?>" onclick="switchDealerTab(event, '<?php echo esc_attr( sanitize_title( $prov ) ); ?>')">
						<?php echo esc_html( mb_strtoupper( $prov ) ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			<!-- Cột phải: Hệ thống cửa hàng -->
			<div class="dealer-stores-right">
				<?php foreach ( $prov_list as $i => $prov ) : ?>
					<div id="dealer-tab-panel-<?php echo esc_attr( sanitize_title( $prov ) ); ?>" class="dealer-tab-panel<?php echo $i === 0 ? ' active' : ''; ?>">
						<div class="dealer-list-vertical">
							<?php
							foreach ( $stores as $s ) :
								if ( $s['p'] !== $prov ) { continue; }
								$img = $s['img'] ?: 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 140"><rect fill="#f1f5f9" width="200" height="140"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#94a3b8" font-size="14">DXD</text></svg>');
								?>
								<a href="<?php echo esc_url( $s['u'] ); ?>" class="dealer-row-item">
									<img loading="lazy" decoding="async" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $s['t'] ); ?>">
									<div class="dealer-row-info">
										<h5><?php echo esc_html( $s['t'] ); ?></h5>
										<p class="dealer-address"><svg class="w-3 h-3 text-emerald-600 shrink-0 inline mr-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><?php echo esc_html( $s['a'] ); ?></p>
										<?php if ( ! empty( $s['ph'] ) ) : ?>
											<span class="dealer-phone"><svg class="w-3.5 h-3.5 text-blue-500 shrink-0 inline mr-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg><?php echo esc_html( $s['ph'] ); ?></span>
										<?php endif; ?>
									</div>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- ===== CONTACT SLIDE-UP PANEL (Mobile) ===== -->
<div id="contact-panel-overlay" data-contact-panel-close></div>
<div id="contact-panel" class="contact-sheet-panel">
	<div class="contact-drag-indicator"></div>
	<div class="contact-header">
		<div class="contact-title-wrap">
			<span class="contact-title-icon">
				<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
				</svg>
			</span>
			<h3><?php printf( esc_html__( 'Liên hệ %s', 'spl' ), esc_html( get_bloginfo( 'name' ) ) ); ?></h3>
		</div>
		<button type="button" data-contact-panel-close aria-label="<?php esc_attr_e( 'Đóng', 'spl' ); ?>">
			<?php echo spl_icon( 'close', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<div class="contact-content-layout-simple">
		<p class="contact-sheet-desc"><?php esc_html_e( 'Chúng tôi luôn sẵn sàng hỗ trợ & tư vấn cho bạn mọi lúc mọi nơi!', 'spl' ); ?></p>
		
		<div class="contact-sheet-cards-grid">
			<!-- 1. Hotline -->
			<a href="<?php echo esc_url( $hotline_url ); ?>" class="contact-sheet-card contact-sheet-card--hotline">
				<div class="contact-sheet-card__icon contact-sheet-card__icon--hotline">
					<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
					</svg>
				</div>
				<div class="contact-sheet-card__info">
					<div class="contact-sheet-card__label"><?php esc_html_e( 'Hotline tư vấn (Miễn phí)', 'spl' ); ?></div>
					<div class="contact-sheet-card__value"><?php echo esc_html( $hotline_display ); ?></div>
				</div>
				<span class="contact-sheet-card__action contact-sheet-card__action--hotline"><?php esc_html_e( 'Gọi ngay', 'spl' ); ?></span>
			</a>

			<!-- 2. Chat Zalo Official -->
			<a href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" rel="noopener noreferrer" class="contact-sheet-card contact-sheet-card--zalo">
				<div class="contact-sheet-card__icon contact-sheet-card__icon--zalo">
					<svg class="w-10 h-10" viewBox="0 0 48 48" fill="none" aria-hidden="true">
						<circle cx="24" cy="24" r="24" fill="#0068FF"/>
						<text x="50%" y="58%" dominant-baseline="middle" text-anchor="middle" font-size="22" font-weight="900" fill="white" font-family="system-ui, -apple-system, sans-serif">Z</text>
					</svg>
				</div>
				<div class="contact-sheet-card__info">
					<div class="contact-sheet-card__label"><?php esc_html_e( 'Chat Zalo Official', 'spl' ); ?></div>
					<div class="contact-sheet-card__value"><?php esc_html_e( 'Tư vấn trực tiếp 24/7', 'spl' ); ?></div>
				</div>
				<span class="contact-sheet-card__action contact-sheet-card__action--zalo"><?php esc_html_e( 'Nhắn Zalo', 'spl' ); ?></span>
			</a>

			<!-- 3. Facebook Messenger -->
			<a href="<?php echo esc_url( $messenger_url ); ?>" target="_blank" rel="noopener noreferrer" class="contact-sheet-card contact-sheet-card--messenger">
				<div class="contact-sheet-card__icon contact-sheet-card__icon--messenger">
					<svg class="w-10 h-10" viewBox="0 0 48 48" fill="none" aria-hidden="true">
						<circle cx="24" cy="24" r="24" fill="#0084FF"/>
						<path d="M24 10C16.27 10 10 15.9 10 23.18c0 4.22 2.1 7.98 5.38 10.46V38l4.9-2.7c1.31.36 2.7.56 4.13.56 7.73 0 14-5.9 14-13.18C38 15.9 31.73 10 24 10zm1.4 17.74l-3.56-3.8-6.95 3.8 7.64-8.11 3.65 3.8 6.86-3.8-7.64 8.11z" fill="white"/>
					</svg>
				</div>
				<div class="contact-sheet-card__info">
					<div class="contact-sheet-card__label"><?php esc_html_e( 'Facebook Messenger', 'spl' ); ?></div>
					<div class="contact-sheet-card__value"><?php esc_html_e( 'Hỗ trợ qua Fanpage', 'spl' ); ?></div>
				</div>
				<span class="contact-sheet-card__action contact-sheet-card__action--messenger"><?php esc_html_e( 'Chat ngay', 'spl' ); ?></span>
			</a>

			<!-- 4. Gửi Email hỗ trợ -->
			<a href="mailto:<?php echo esc_attr( $email ); ?>" class="contact-sheet-card contact-sheet-card--email">
				<div class="contact-sheet-card__icon contact-sheet-card__icon--email">
					<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
					</svg>
				</div>
				<div class="contact-sheet-card__info">
					<div class="contact-sheet-card__label"><?php esc_html_e( 'Gửi Email hỗ trợ', 'spl' ); ?></div>
					<div class="contact-sheet-card__value"><?php echo esc_html( $email ); ?></div>
				</div>
				<span class="contact-sheet-card__action contact-sheet-card__action--email"><?php esc_html_e( 'Gửi mail', 'spl' ); ?></span>
			</a>
		</div>

		<!-- Footer work time info -->
		<div class="contact-sheet-footer">
			<div class="contact-work-time">
				<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
				</svg>
				<span><?php printf( esc_html__( 'Thời gian làm việc: %s (%s)', 'spl' ), '<strong>' . esc_html( $working_hours ) . '</strong>', esc_html( $working_days ) ); ?></span>
			</div>
		</div>
	</div>
</div>

<?php
// Mobile Navigation Drawer Component
get_template_part( 'parts/global/mobile-drawer' );

/** Hook: spl_footer_action. */
do_action( 'spl_footer_action' );

wp_footer();
?>
</body>
</html>
