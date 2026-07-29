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

// Brand-style social icons (inline SVG, filled).
$footer_socials = [
	'facebook' => [ 'url' => $facebook_url, 'label' => 'Facebook', 'svg' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>' ],
	'youtube'  => [ 'url' => $youtube_url, 'label' => 'YouTube', 'svg' => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>' ],
	'tiktok'   => [ 'url' => $tiktok_url, 'label' => 'TikTok', 'svg' => '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>' ],
	'zalo'     => [ 'url' => $zalo_url, 'label' => 'Zalo', 'svg' => '<path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/>' ],
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
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-3">
				<?php if ( has_custom_logo() ) :
					echo get_custom_logo();
				else : ?>
					<div class="bg-primary text-white font-black p-2 rounded-xl text-lg">D<span class="text-accent">XD</span></div>
					<span class="text-xl font-extrabold text-slate-800"><?php bloginfo( 'name' ); ?></span>
				<?php endif; ?>
			</a>
			<p class="text-xs leading-relaxed text-slate-500"><?php echo esc_html( $footer_desc ); ?></p>
			<div class="flex items-center gap-3 pt-2">
				<?php foreach ( $footer_socials as $key => $social ) :
					if ( empty( $social['url'] ) || '#' === $social['url'] ) { continue; }
					if ( 'zalo' === $key ) : ?>
						<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" aria-label="Zalo" class="w-8 h-8 rounded-full bg-[#0068ff] hover:bg-[#0054d1] text-white flex items-center justify-center transition-all shadow-sm font-black text-[10px] tracking-tight">
							Zalo
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $social['label'] ); ?>" class="w-8 h-8 rounded-full bg-slate-200/60 hover:bg-primary hover:text-white flex items-center justify-center transition-colors text-slate-500">
							<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $social['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></svg>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Policy -->
		<div class="space-y-4">
			<h4 class="text-slate-800 font-bold text-sm tracking-wide"><?php esc_html_e( 'CHÍNH SÁCH CHUNG', 'spl' ); ?></h4>
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
			<h4 class="text-slate-800 font-bold text-sm tracking-wide"><?php esc_html_e( 'HỖ TRỢ KHÁCH HÀNG', 'spl' ); ?></h4>
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
			<h4 class="text-slate-800 font-bold text-sm tracking-wide"><?php esc_html_e( 'LIÊN HỆ VỚI CHÚNG TÔI', 'spl' ); ?></h4>
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
				<img src="<?php echo esc_url( get_theme_file_uri( 'resources/img/DaThongBao.png' ) ); ?>" alt="<?php esc_attr_e( 'Đã thông báo Bộ Công Thương', 'spl' ); ?>" class="h-8.5 w-auto" style="height: 34px;" />
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
$cart_count_footer = ( class_exists( 'WooCommerce' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$is_home           = is_front_page() || is_home();
$is_shop           = function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() || is_product() );
$is_dealer         = is_page( 'he-thong-cua-hang' ) || is_post_type_archive( 'local_store' ) || is_singular( 'local_store' );
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
		<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
		<span><?php esc_html_e( 'Liên hệ', 'spl' ); ?></span>
	</button>
	<button type="button" data-cart-open class="relative">
		<?php echo spl_icon( 'cart', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'Giỏ hàng', 'spl' ); ?></span>
		<?php if ( $cart_count_footer > 0 ) : ?>
			<span class="dxd-bottom-nav__badge" data-cart-count><?php echo esc_html( (string) $cart_count_footer ); ?></span>
		<?php endif; ?>
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
										$img_url = get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ) ?: wc_placeholder_img_src();
										?>
										<a href="<?php the_permalink(); ?>" class="cat-product-row flex items-center gap-3 p-2 rounded-lg bg-slate-50 hover:bg-white border border-slate-100 transition-all">
											<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php the_title_attribute(); ?>" class="w-12 h-12 object-contain rounded bg-white p-1 border border-slate-100 shrink-0">
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
							<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title( $post->ID ) ); ?>">
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
									<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title( $post->ID ) ); ?>">
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
									<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $s['t'] ); ?>">
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
<div id="contact-panel">
	<div class="contact-header">
		<h3><?php esc_html_e( 'Liên Hệ & Hỗ Trợ', 'spl' ); ?></h3>
		<button type="button" data-contact-panel-close aria-label="<?php esc_attr_e( 'Đóng', 'spl' ); ?>">
			<?php echo spl_icon( 'close', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<div class="contact-content-layout-simple">
		<a href="<?php echo esc_url( $hotline_url ); ?>" class="contact-option-row">
			<div class="contact-option-icon hotline">
				<?php echo spl_icon( 'phone', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="contact-option-info">
				<h5><?php esc_html_e( 'Gọi Hotline mua hàng', 'spl' ); ?></h5>
				<p><?php echo esc_html( $hotline_display ); ?></p>
			</div>
		</a>
		<a href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" class="contact-option-row">
			<div class="contact-option-icon zalo">
				<svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
			</div>
			<div class="contact-option-info">
				<h5><?php esc_html_e( 'Trò chuyện qua Zalo', 'spl' ); ?></h5>
				<p><?php esc_html_e( 'Giải đáp thắc mắc & mua hàng nhanh', 'spl' ); ?></p>
			</div>
		</a>
		<a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>" class="contact-option-row">
			<div class="contact-option-icon email">
				<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
			</div>
			<div class="contact-option-info">
				<h5><?php esc_html_e( 'Gửi Form Liên Hệ', 'spl' ); ?></h5>
				<p><?php esc_html_e( 'Góp ý hoặc yêu cầu hỗ trợ trực tuyến', 'spl' ); ?></p>
			</div>
		</a>
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
