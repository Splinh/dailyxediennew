<?php
/**
 * The template for displaying the header — dailyxedien.vn.
 *
 * Top utility bar + sticky main header + mobile drawer + primary nav bar.
 * Converted from htmlmau/index.html (Tailwind v4). Brand tokens: docs/brand-guide.md.
 * Icons: inline SVG (Lucide-style, currentColor) — nhẹ, không dùng FontAwesome.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

// ── Inline SVG icon helper (dùng chung header/footer/parts) ──
if ( ! function_exists( 'spl_icon' ) ) {
	/**
	 * Render an inline SVG icon (Lucide-style, 24x24, currentColor).
	 *
	 * @param string $name  Icon key.
	 * @param string $class CSS classes for the <svg>.
	 * @return string SVG markup (safe, static paths).
	 */
	function spl_icon( string $name, string $class = 'w-5 h-5' ): string {
		static $icons = [
			'menu'         => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
			'search'       => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
			'cart'         => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
			'user'         => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'phone'        => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
			'close'        => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
			'chevron-right'=> '<polyline points="9 18 15 12 9 6"/>',
			'chevron-left' => '<polyline points="15 18 9 12 15 6"/>',
			'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
			'bolt'         => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
			'bicycle'      => '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>',
			'motorcycle'   => '<circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M5.5 17.5h7l3.5-6H20"/><path d="M9 11.5h6"/><path d="M14 8h3l1.5 3.5"/>',
			'truck'        => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
			'map-pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
			'mail'         => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		];

		$inner = $icons[ $name ] ?? '';

		return sprintf(
			'<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
			esc_attr( $class ),
			$inner
		);
	}
}

// ── ACF options (fallback an toàn nếu ACF chưa cấu hình) ──
$hotline       = Helper::getField( 'hotline', 'option' ) ?: '0933 505 222';
$hotline_label = Helper::getField( 'hotline_label', 'option' ) ?: __( 'Hotline tư vấn 24/7', 'spl' );
$logo_tagline  = Helper::getField( 'logo_tagline', 'option' ) ?: ( get_bloginfo( 'description' ) ?: __( 'Hệ thống xe điện lớn nhất Việt Nam', 'spl' ) );
$address       = Helper::getField( 'address', 'option' ) ?: __( '466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP.HCM', 'spl' );

$hotline_display = is_array( $hotline ) ? ( $hotline['title'] ?? $hotline['url'] ?? '0933 505 222' ) : $hotline;
$hotline_url     = is_array( $hotline ) ? ( $hotline['url'] ?? 'tel:' . preg_replace( '/[^0-9+]/', '', $hotline_display ) ) : 'tel:' . preg_replace( '/[^0-9+]/', '', $hotline );

// Top bar links (ACF repeater 'topbar_links' → field: link {title,url}). Fallback set.
$topbar_links = Helper::getField( 'topbar_links', 'option' );
if ( ! $topbar_links ) {
	$topbar_links = [
		[ 'link' => [ 'title' => __( 'Sứ Mệnh', 'spl' ), 'url' => home_url( '/su-menh/' ) ] ],
		[ 'link' => [ 'title' => __( 'Cơ Hội Hợp Tác', 'spl' ), 'url' => home_url( '/co-hoi-hop-tac/' ) ] ],
		[ 'link' => [ 'title' => __( 'Hệ Thống Cửa Hàng', 'spl' ), 'url' => home_url( '/he-thong-cua-hang/' ) ] ],
		[ 'link' => [ 'title' => __( 'Tin Tức', 'spl' ), 'url' => home_url( '/tin-tuc/' ) ] ],
	];
}

$cart_count = ( Helper::isWoocommerceActive() && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$cart_url   = Helper::isWoocommerceActive() ? wc_get_cart_url() : home_url( '/gio-hang/' );

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="format-detection" content="telephone=no,email=no,address=no" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'text-slate-800 antialiased overflow-x-hidden' ); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main"><?php esc_html_e( 'Bỏ qua tới nội dung', 'spl' ); ?></a>

<?php
/** Hook: spl_header_before_action. */
do_action( 'spl_header_before_action' );
?>

<!-- ===== TOP UTILITY BAR (ẩn trên mobile) ===== -->
<div class="bg-navy text-slate-300 text-xs py-2.5 px-4 border-b border-white/10 relative z-50 hidden md:block">
	<div class="max-w-7xl mx-auto flex flex-row justify-between items-center gap-2">
		<div class="flex flex-wrap items-center gap-5">
			<?php foreach ( $topbar_links as $row ) :
				$lk = $row['link'] ?? null;
				if ( ! $lk || empty( $lk['url'] ) ) { continue; }
				?>
				<a href="<?php echo esc_url( $lk['url'] ); ?>" class="hover:text-white transition-colors flex items-center gap-1.5">
					<?php echo spl_icon( 'chevron-right', 'w-3 h-3 text-primary-300' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $lk['title'] ?? $lk['url'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<div class="flex items-center gap-5">
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="hover:text-white transition-colors flex items-center gap-1.5"><?php echo spl_icon( 'user', 'w-3.5 h-3.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Đăng nhập / Đăng ký', 'spl' ); ?></a>
			<span class="text-white/20">|</span>
			<a href="<?php echo esc_url( $cart_url ); ?>" data-cart-open class="hover:text-white transition-colors flex items-center gap-1.5 font-medium relative">
				<?php echo spl_icon( 'cart', 'w-3.5 h-3.5 text-accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Giỏ hàng', 'spl' ); ?>
				<span class="bg-sale text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full absolute -top-2.5 -right-4 shadow-sm" data-cart-count><?php echo esc_html( (string) $cart_count ); ?></span>
			</a>
		</div>
	</div>
</div>

<!-- ===== MAIN HEADER (sticky) ===== -->
<header class="bg-white py-3 md:py-4 px-4 sticky top-0 z-50 border-b border-slate-100 shadow-sm" id="header">
	<div class="max-w-7xl mx-auto flex items-center justify-between gap-4">

		<!-- Hamburger (mobile) -->
		<button data-drawer-open class="md:hidden text-slate-700 hover:text-primary p-2 focus:outline-none" aria-label="<?php esc_attr_e( 'Mở menu', 'spl' ); ?>">
			<?php echo spl_icon( 'menu', 'w-6 h-6' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>

		<!-- Logo -->
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2 md:gap-3 shrink-0" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php 
			$logo_acf = Helper::getField( 'logo', 'option' );
			$logo_url = '';
			if ( $logo_acf ) {
				$logo_url = is_array( $logo_acf ) ? ( $logo_acf['url'] ?? '' ) : ( is_numeric( $logo_acf ) ? wp_get_attachment_url( $logo_acf ) : $logo_acf );
			}
			if ( $logo_url ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="h-10 md:h-12 w-auto object-contain" />
			<?php else : ?>
				<div class="bg-gradient-to-r from-primary to-primary-600 text-white font-black p-2 md:p-2.5 rounded-xl text-lg md:text-xl shadow-lg shadow-primary/20 tracking-wider">D<span class="text-accent">XD</span></div>
				<div>
					<span class="text-lg md:text-2xl font-extrabold tracking-tight text-slate-900">dailyxedien<span class="text-primary">.vn</span></span>
					<p class="text-[8px] md:text-[10px] tracking-widest text-slate-400 uppercase font-bold hidden sm:block"><?php echo esc_html( $logo_tagline ); ?></p>
				</div>
			<?php endif; ?>
		</a>

		<!-- Search (desktop) -->
		<div class="w-full md:max-w-xl relative hidden md:block" role="search">
			<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
				<div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
					<?php echo spl_icon( 'search', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<label for="header-search" class="sr-only"><?php esc_html_e( 'Tìm kiếm sản phẩm', 'spl' ); ?></label>
				<input id="header-search" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Bạn cần tìm xe điện, xe 50cc hay phụ kiện gì hôm nay?', 'spl' ); ?>" autocomplete="off" class="w-full pl-11 pr-24 py-3 bg-slate-50 border border-slate-200 focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary-100 rounded-xl outline-none transition-all text-sm" />
				<?php if ( Helper::isWoocommerceActive() ) : ?>
					<input type="hidden" name="post_type" value="product" />
				<?php endif; ?>
				<button type="submit" class="absolute right-1.5 top-1.5 bottom-1.5 bg-primary hover:bg-primary-hover text-white px-5 rounded-lg text-xs font-semibold transition-colors"><?php esc_html_e( 'Tìm kiếm', 'spl' ); ?></button>
			</form>
		</div>

		<!-- Actions -->
		<div class="flex items-center gap-1 md:gap-3.5">
			<button data-drawer-open data-focus-search class="md:hidden text-slate-700 hover:text-primary p-2" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
				<?php echo spl_icon( 'search', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<button type="button" data-cart-open class="md:hidden text-slate-700 hover:text-primary p-2 relative" aria-label="<?php esc_attr_e( 'Giỏ hàng', 'spl' ); ?>">
				<?php echo spl_icon( 'cart', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="bg-sale text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full absolute top-0 right-0 shadow-sm" data-cart-count><?php echo esc_html( (string) $cart_count ); ?></span>
			</button>
			<div class="hidden sm:flex items-center gap-3.5">
				<div class="w-11 h-11 rounded-full bg-primary-50 flex items-center justify-center text-primary shadow-sm shrink-0">
					<?php echo spl_icon( 'phone', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="text-right md:text-left">
					<span class="text-xs text-slate-400 font-medium"><?php echo esc_html( $hotline_label ); ?></span>
					<a href="<?php echo esc_url( $hotline_url ); ?>" class="block text-base font-bold text-slate-900 tracking-tight hover:text-primary transition-colors"><?php echo esc_html( $hotline_display ); ?></a>
				</div>
			</div>
		</div>
	</div>
</header>

<!-- ===== MOBILE DRAWER ===== -->
<div data-drawer-overlay class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden transition-opacity duration-300 opacity-0"></div>
<div data-drawer class="fixed inset-y-0 left-0 w-80 max-w-[85vw] bg-white z-[110] shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between">
	<div class="overflow-y-auto grow p-5 space-y-6">
		<div class="flex items-center justify-between border-b border-slate-100 pb-4">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
				<div class="bg-primary text-white font-black p-1.5 rounded-lg text-sm">D<span class="text-accent">XD</span></div>
				<span class="font-bold text-slate-800 text-base"><?php bloginfo( 'name' ); ?></span>
			</a>
			<button data-drawer-close class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors" aria-label="<?php esc_attr_e( 'Đóng menu', 'spl' ); ?>">
				<?php echo spl_icon( 'close', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="relative">
			<label for="drawer-search" class="sr-only"><?php esc_html_e( 'Tìm kiếm', 'spl' ); ?></label>
			<input id="drawer-search" data-drawer-search type="search" name="s" placeholder="<?php esc_attr_e( 'Tìm kiếm xe, phụ kiện...', 'spl' ); ?>" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-sm focus:bg-white focus:border-primary transition-all" />
			<?php if ( Helper::isWoocommerceActive() ) : ?><input type="hidden" name="post_type" value="product" /><?php endif; ?>
			<span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><?php echo spl_icon( 'search', 'w-3.5 h-3.5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</form>

		<!-- Nav links (mobile) -->
		<nav class="dxd-mobilemenu space-y-1 text-sm font-semibold text-slate-700" aria-label="<?php esc_attr_e( 'Mobile navigation', 'spl' ); ?>">
			<?php
			wp_nav_menu( [
				'theme_location' => 'mobile-nav',
				'container'      => false,
				'menu_class'     => 'space-y-1',
				'items_wrap'     => '%3$s',
				'fallback_cb'    => function () {
					wp_nav_menu( [
						'theme_location' => 'main-nav',
						'container'      => false,
						'items_wrap'     => '%3$s',
						'fallback_cb'    => 'spl_main_nav_fallback',
						'depth'          => 1,
					] );
				},
				'depth'          => 1,
			] );
			?>
		</nav>

		<!-- Accordion danh mục sản phẩm -->
		<?php
		$mobile_cats = Helper::isWoocommerceActive() ? get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'parent'     => 0,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
			'number'     => 20,
		] ) : [];
		if ( ! is_wp_error( $mobile_cats ) && ! empty( $mobile_cats ) ) :
			?>
			<div class="border-t border-slate-100 pt-5">
				<h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 px-3"><?php esc_html_e( 'Danh Mục Sản Phẩm', 'spl' ); ?></h4>
				<div class="space-y-2">
					<?php foreach ( $mobile_cats as $cat ) :
						$cat_link = get_term_link( $cat );
						if ( is_wp_error( $cat_link ) ) { continue; }
						?>
						<a href="<?php echo esc_url( $cat_link ); ?>" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 font-semibold text-xs text-slate-700 transition-colors">
							<span class="flex items-center gap-2"><?php echo spl_icon( 'chevron-right', 'w-3.5 h-3.5 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $cat->name ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="p-5 border-t border-slate-100 bg-slate-50 space-y-3">
		<p class="text-xs text-slate-500 flex items-center gap-2"><?php echo spl_icon( 'phone', 'w-3.5 h-3.5 text-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <a href="<?php echo esc_url( $hotline_url ); ?>"><strong><?php echo esc_html( $hotline_display ); ?></strong></a></p>
		<p class="text-[11px] text-slate-400 leading-snug"><?php echo esc_html( $address ); ?></p>
	</div>
</div>

<!-- ===== PRIMARY NAV BAR (ẩn mobile) ===== -->
<nav class="bg-primary text-white shadow-md relative z-40 hidden md:block" aria-label="<?php esc_attr_e( 'Main navigation', 'spl' ); ?>">
	<div class="max-w-7xl mx-auto flex items-center">

		<!-- Category trigger + dropdown -->
		<div class="relative group" data-cat-menu>
			<button class="bg-primary-700 hover:bg-primary-800 px-6 py-4 flex items-center gap-3 cursor-pointer transition-colors font-semibold text-sm select-none" data-cat-trigger aria-expanded="false">
				<?php echo spl_icon( 'menu', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'DANH MỤC SẢN PHẨM', 'spl' ); ?></span>
				<?php echo spl_icon( 'chevron-down', 'w-3.5 h-3.5 ml-1 transition-transform group-hover:rotate-180' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<?php
			$nav_cats = Helper::isWoocommerceActive() ? get_terms( [
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => 0,
				'orderby'    => 'menu_order',
				'order'      => 'ASC',
				'number'     => 15,
			] ) : [];
			if ( ! is_wp_error( $nav_cats ) && ! empty( $nav_cats ) ) :
				?>
				<div class="absolute top-full left-0 w-64 bg-white border border-slate-100 rounded-b-2xl shadow-premium overflow-hidden p-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-300 z-50" role="menu">
					<div class="space-y-1">
						<?php foreach ( $nav_cats as $cat ) :
							$cat_link = get_term_link( $cat );
							if ( is_wp_error( $cat_link ) ) { continue; }
							?>
							<a href="<?php echo esc_url( $cat_link ); ?>" role="menuitem" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-primary font-medium transition-all group/item">
								<span class="flex items-center gap-3">
									<span class="w-8 h-8 rounded-lg bg-primary-50 text-primary flex items-center justify-center"><?php echo spl_icon( 'bolt', 'w-4 h-4' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php echo esc_html( $cat->name ); ?>
								</span>
								<?php echo spl_icon( 'chevron-right', 'w-3 h-3 opacity-50 group-hover/item:opacity-100 group-hover/item:translate-x-1 transition-all' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Main nav links -->
		<div class="dxd-mainmenu flex items-center gap-1 px-4 text-sm font-medium overflow-x-auto no-scrollbar">
			<?php
			wp_nav_menu( [
				'theme_location' => 'main-nav',
				'container'      => false,
				'items_wrap'     => '%3$s',
				'fallback_cb'    => 'spl_main_nav_fallback',
				'depth'          => 1,
			] );
			?>
		</div>
	</div>
</nav>

<?php
/** Hook: spl_header_after_action. */
do_action( 'spl_header_after_action' );
?>

<main id="main">
