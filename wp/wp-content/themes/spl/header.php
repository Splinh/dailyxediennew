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
			'menu'           => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
			'search'         => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
			'cart'           => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
			'shopping-cart'  => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
			'user'           => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'phone'          => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
			'close'          => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
			'x'              => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
			'chevron-right'  => '<polyline points="9 18 15 12 9 6"/>',
			'chevron-left'   => '<polyline points="15 18 9 12 15 6"/>',
			'chevron-down'   => '<polyline points="6 9 12 15 18 9"/>',
			'bolt'           => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
			'bicycle'        => '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>',
			'motorcycle'     => '<circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M5.5 17.5h7l3.5-6H20"/><path d="M9 11.5h6"/><path d="M14 8h3l1.5 3.5"/>',
			'truck'          => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
			'map-pin'        => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
			'mail'           => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
			'trash-2'        => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>',
			'tag'            => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
			'arrow-left'     => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
			'check-circle'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
			'shield'         => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
			'refresh-cw'     => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
			'headphones'     => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
			'file-text'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
			'message-circle' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
			'store'          => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M7 14h10"/><path d="M9 18h6"/>',
			'clock'          => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
			'share'          => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>',
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

<!-- ===== TOP UTILITY BAR ===== -->
<div class="bg-navy text-slate-300 text-[11px] md:text-xs py-1.5 md:py-2.5 px-4 border-b border-white/10 relative z-50">
	<div class="max-w-7xl mx-auto flex flex-row justify-between items-center gap-2">
		<div class="flex items-center gap-3 md:gap-5 overflow-x-auto scrollbar-hide whitespace-nowrap -mx-1 px-1">
			<?php foreach ( $topbar_links as $row ) :
				$lk = $row['link'] ?? null;
				if ( ! $lk || empty( $lk['url'] ) ) { continue; }
				?>
				<a href="<?php echo esc_url( $lk['url'] ); ?>" class="hover:text-white transition-colors flex items-center gap-1 md:gap-1.5 shrink-0">
					<?php echo spl_icon( 'chevron-right', 'w-3 h-3 text-primary-300' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $lk['title'] ?? $lk['url'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<div class="hidden md:flex items-center gap-5">
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
<header class="sticky top-0 z-50 transition-all duration-300 shadow-md" id="header">
	<div class="bg-white py-2.5 md:py-3 px-4 border-b border-slate-100">
		<div class="max-w-7xl mx-auto flex items-center justify-between gap-4">

			<!-- Hamburger (mobile) -->
			<button data-drawer-open class="md:hidden text-slate-700 hover:text-primary p-2 focus:outline-none" aria-label="<?php esc_attr_e( 'Mở menu', 'spl' ); ?>">
				<?php echo spl_icon( 'menu', 'w-6 h-6' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>

			<!-- Logo -->
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2 md:gap-3 shrink-0" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php
				// Use theme's built-in Helper::siteLogo() — handles custom_logo, theme variants, Polylang.
				$site_logo = Helper::siteLogo( 'default', '' );
				if ( $site_logo ) :
					// siteLogo() returns <a><img></a>. We already have an <a> wrapper, so extract just <img>.
					preg_match( '/<img[^>]+>/i', $site_logo, $matches );
					if ( ! empty( $matches[0] ) ) :
						// Add Tailwind sizing classes to the extracted img.
						echo str_replace( '<img ', '<img class="h-10 md:h-12 w-auto object-contain" ', $matches[0] );
					else :
						echo wp_kses_post( $site_logo );
					endif;
				else : ?>
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
	</div>

	<!-- ===== PRIMARY NAV BAR (PC Mega Menu - Sticky) ===== -->
	<nav class="bg-primary text-white shadow-md relative z-40 hidden md:block" aria-label="<?php esc_attr_e( 'Main navigation', 'spl' ); ?>">
	<div class="max-w-7xl mx-auto flex items-center justify-between relative px-4">

		<!-- 1. Category trigger + MEGA MENU SẢN PHẨM (Full Container Width) -->
		<div class="group static" data-cat-menu>
			<button class="bg-primary-700 hover:bg-primary-800 px-6 py-4 flex items-center gap-3 cursor-pointer transition-colors font-bold text-sm select-none" data-cat-trigger aria-expanded="false">
				<?php echo spl_icon( 'menu', 'w-5 h-5' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'DANH MỤC SẢN PHẨM', 'spl' ); ?></span>
				<?php echo spl_icon( 'chevron-down', 'w-3.5 h-3.5 ml-1 transition-transform group-hover:rotate-180' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<?php
			$nav_cats       = spl_get_product_categories( 10 );
			$mega_products = spl_get_mega_menu_products( 3 );
			if ( ! empty( $nav_cats ) ) :
				?>
				<!-- MEGA MENU DROPDOWN PANEL (SẢN PHẨM - CONTAINER BOUNDED) -->
				<div class="absolute top-full left-4 right-4 bg-white text-slate-800 border border-slate-100 rounded-b-2xl shadow-2xl overflow-hidden p-6 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-300 z-40 group-hover:z-50 flex gap-6" role="menu">
					
					<!-- Left Column: Product Categories & Sub-categories -->
					<div class="w-80 shrink-0 border-r border-slate-100 pr-5 space-y-1.5 max-h-[440px] overflow-y-auto no-scrollbar">
						<h3 class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5 px-1"><?php esc_html_e( 'DÒNG XE & PHỤ KIỆN', 'spl' ); ?></h3>
						<?php foreach ( $nav_cats as $index => $cat ) :
							$cat_link  = get_term_link( $cat );
							if ( is_wp_error( $cat_link ) ) { continue; }
							$sub_cats  = spl_get_product_sub_categories( $cat->term_id );
							$thumb_id  = get_term_meta( $cat->term_id, 'thumbnail_id', true );
							$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
							$active_cls = $index === 0 ? 'bg-slate-100 text-primary border-primary-200' : 'text-slate-800 hover:bg-slate-50 hover:text-primary';
							?>
							<div class="group/cat rounded-xl transition-all">
								<a href="<?php echo esc_url( $cat_link ); ?>"
								   onmouseenter="switchMegaCat(<?php echo (int) $cat->term_id; ?>, this)"
								   class="mega-cat-item flex items-center justify-between p-2 rounded-xl border border-transparent font-bold text-xs transition-colors <?php echo esc_attr( $active_cls ); ?>">
									<span class="flex items-center gap-2.5">
										<?php if ( $thumb_url ) : ?>
											<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" class="w-6 h-6 object-contain shrink-0" />
										<?php else : ?>
											<span class="w-6 h-6 rounded-md bg-primary-50 text-primary flex items-center justify-center shrink-0"><?php echo spl_icon( 'bolt', 'w-3.5 h-3.5' ); ?></span>
										<?php endif; ?>
										<?php echo esc_html( $cat->name ); ?>
									</span>
									<span class="text-[10px] text-slate-400 font-semibold bg-slate-100 px-2 py-0.5 rounded-full"><?php echo (int) $cat->count; ?></span>
								</a>

								<?php if ( ! empty( $sub_cats ) ) : ?>
									<div class="pl-9 pr-2 py-1 space-y-1">
										<?php foreach ( $sub_cats as $scat ) :
											$scat_link = get_term_link( $scat );
											if ( is_wp_error( $scat_link ) ) { continue; }
											?>
											<a href="<?php echo esc_url( $scat_link ); ?>" class="block text-[11px] text-slate-500 hover:text-primary font-medium transition-colors">
												• <?php echo esc_html( $scat->name ); ?>
											</a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>

					<!-- Right Column: Dynamic Category Products Panels -->
					<div class="flex-1 space-y-4">
						<?php foreach ( $nav_cats as $index => $cat ) :
							$cat_products = spl_get_mega_menu_products_by_cat( $cat->term_id, 3 );
							$cat_link     = get_term_link( $cat );
							$cat_link_url = is_wp_error( $cat_link ) ? home_url( '/cua-hang/' ) : $cat_link;
							$panel_class  = $index === 0 ? 'block' : 'hidden';
							?>
							<div id="mega-cat-panel-<?php echo (int) $cat->term_id; ?>" class="mega-cat-panel <?php echo esc_attr( $panel_class ); ?> space-y-4">
								<div class="flex items-center justify-between border-b border-slate-100 pb-3">
									<h3 class="text-xs font-black text-slate-900 tracking-tight flex items-center gap-2">
										<span class="w-1.5 h-4 bg-primary rounded-full"></span>
										<?php echo esc_html( mb_strtoupper( $cat->name ) ); ?>
									</h3>
									<a href="<?php echo esc_url( $cat_link_url ); ?>" class="text-[11px] font-bold text-primary hover:underline flex items-center gap-1">
										<?php esc_html_e( 'Xem tất cả', 'spl' ); ?>
										<?php echo spl_icon( 'chevron-right', 'w-3 h-3' ); ?>
									</a>
								</div>

								<div class="grid grid-cols-3 gap-3.5">
									<?php if ( ! empty( $cat_products ) ) : ?>
										<?php foreach ( $cat_products as $p ) : ?>
											<div class="bg-slate-50/70 border border-slate-100 rounded-xl p-3 hover:border-primary/30 hover:bg-white hover:shadow-md transition-all duration-300 flex flex-col justify-between group/p relative">
												<?php if ( ! empty( $p['discount'] ) ) : ?>
													<span class="absolute top-2 left-2 bg-red-500 text-white font-extrabold text-[9px] px-1.5 py-0.5 rounded shadow-sm z-10"><?php echo esc_html( $p['discount'] ); ?></span>
												<?php endif; ?>
												<a href="<?php echo esc_url( $p['url'] ); ?>" class="block aspect-square overflow-hidden rounded-lg mb-2 bg-white flex items-center justify-center">
													<img src="<?php echo esc_url( $p['image'] ); ?>" alt="<?php echo esc_attr( $p['name'] ); ?>" class="max-h-full max-w-full object-contain group-hover/p:scale-105 transition-transform duration-300" />
												</a>
												<div>
													<h4 class="font-bold text-slate-800 text-xs line-clamp-2 leading-snug group-hover/p:text-primary transition-colors">
														<a href="<?php echo esc_url( $p['url'] ); ?>"><?php echo esc_html( $p['name'] ); ?></a>
													</h4>
													<div class="mt-2 flex items-baseline gap-1">
														<span class="text-xs font-black text-slate-900"><?php echo esc_html( number_format( $p['price'], 0, ',', '.' ) ); ?>đ</span>
														<?php if ( $p['regular_price'] > $p['price'] ) : ?>
															<span class="text-[10px] text-slate-400 line-through"><?php echo esc_html( number_format( $p['regular_price'] / 1000000, 1 ) ); ?>M</span>
														<?php endif; ?>
													</div>
												</div>
											</div>
										<?php endforeach; ?>
									<?php else : ?>
										<div class="col-span-3 py-8 text-center text-slate-400 text-xs">
											<?php esc_html_e( 'Chưa có sản phẩm thuộc danh mục này.', 'spl' ); ?>
										</div>
									<?php endif; ?>
								</div>

								<!-- Banner Highlight -->
								<div class="bg-gradient-to-r from-primary to-indigo-600 rounded-xl p-3.5 text-white flex items-center justify-between shadow-sm">
									<div class="flex items-center gap-2.5">
										<span class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0"><?php echo spl_icon( 'bolt', 'w-4 h-4 text-yellow-300' ); ?></span>
										<div>
											<h4 class="font-black text-xs"><?php esc_html_e( 'HỖ TRỢ TRẢ GÓP 0%', 'spl' ); ?></h4>
											<p class="text-[10px] text-blue-100"><?php esc_html_e( 'Xét duyệt nhanh 15 phút, không chứng minh thu nhập', 'spl' ); ?></p>
										</div>
									</div>
									<a href="#consult-form" class="bg-white text-primary hover:bg-slate-100 font-bold text-[10px] px-3.5 py-1.5 rounded-lg transition-colors shrink-0">
										<?php esc_html_e( 'ĐĂNG KÝ', 'spl' ); ?>
									</a>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

				</div>
			<?php endif; ?>
		</div>

		<!-- 2. Main menu links + MEGA MENU TIN TỨC (Full Container Width) -->
		<div class="dxd-mainmenu flex items-center gap-1 px-4 text-sm font-bold">
			<?php
			$shop_page_url = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/cua-hang/' );
			$news_page_url = home_url( '/tin-tuc/' );
			$store_page_url = home_url( '/he-thong-cua-hang/' );
			$coop_page_url  = home_url( '/co-hoi-hop-tac/' );
			$contact_page_url = home_url( '/lien-he/' );
			$about_page_url = home_url( '/gioi-thieu/' );
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="px-4 py-4 hover:bg-primary-700 transition-colors uppercase tracking-wider block"><?php esc_html_e( 'TRANG CHỦ', 'spl' ); ?></a>
			<a href="<?php echo esc_url( $shop_page_url ); ?>" class="px-4 py-4 hover:bg-primary-700 transition-colors uppercase tracking-wider block"><?php esc_html_e( 'SẢN PHẨM', 'spl' ); ?></a>
			
			<!-- MEGA MENU TIN TỨC ITEM (Full Container Bounded) -->
			<div class="group static" data-news-mega>
				<a href="<?php echo esc_url( $news_page_url ); ?>" class="px-4 py-4 hover:bg-primary-700 transition-colors uppercase tracking-wider flex items-center gap-1">
					<span><?php esc_html_e( 'TIN TỨC', 'spl' ); ?></span>
					<?php echo spl_icon( 'chevron-down', 'w-3.5 h-3.5 opacity-80 group-hover:rotate-180 transition-transform' ); ?>
				</a>

				<!-- MEGA MENU DROPDOWN PANEL (TIN TỨC - CONTAINER BOUNDED) -->
				<div class="absolute top-full left-4 right-4 bg-white text-slate-800 border border-slate-100 rounded-b-2xl shadow-2xl overflow-hidden p-6 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-300 z-40 group-hover:z-50 flex gap-6" role="menu">
					
					<!-- Left Column: News Categories -->
					<div class="w-72 shrink-0 border-r border-slate-100 pr-5 space-y-2 max-h-[380px] overflow-y-auto no-scrollbar">
						<h3 class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5 px-1"><?php esc_html_e( 'CHUYÊN MỤC TIN TỨC', 'spl' ); ?></h3>
						<?php
						$news_categories = get_categories( [ 'hide_empty' => false, 'number' => 8 ] );
						foreach ( $news_categories as $nc ) :
							$nc_link = get_category_link( $nc );
							?>
							<a href="<?php echo esc_url( $nc_link ); ?>" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 font-bold text-xs text-slate-800 hover:text-primary transition-colors">
								<span class="flex items-center gap-2">
									<span class="w-2 h-2 rounded-full bg-primary/40"></span>
									<?php echo esc_html( $nc->name ); ?>
								</span>
								<span class="text-[10px] text-slate-400 font-semibold bg-slate-100 px-2 py-0.5 rounded-full"><?php echo (int) $nc->count; ?></span>
							</a>
						<?php endforeach; ?>
					</div>

					<!-- Right Column: Featured Articles Grid -->
					<div class="flex-1 space-y-4">
						<div class="flex items-center justify-between border-b border-slate-100 pb-3">
							<h3 class="text-xs font-black text-slate-900 tracking-tight flex items-center gap-2">
								<span class="w-1.5 h-4 bg-primary rounded-full"></span>
								<?php esc_html_e( 'BÀI VIẾT NỔI BẬT', 'spl' ); ?>
							</h3>
							<a href="<?php echo esc_url( $news_page_url ); ?>" class="text-[11px] font-bold text-primary hover:underline flex items-center gap-1">
								<?php esc_html_e( 'Xem tất cả', 'spl' ); ?>
								<?php echo spl_icon( 'chevron-right', 'w-3 h-3' ); ?>
							</a>
						</div>

						<div class="grid grid-cols-3 gap-3.5">
							<?php
							$mega_posts = spl_get_mega_menu_posts( 3 );
							if ( ! empty( $mega_posts ) ) :
								foreach ( $mega_posts as $post_item ) :
									?>
									<article class="bg-slate-50/70 border border-slate-100 rounded-xl overflow-hidden hover:border-primary/30 hover:bg-white hover:shadow-md transition-all duration-300 flex flex-col justify-between group/post">
										<a href="<?php echo esc_url( $post_item['url'] ); ?>" class="block aspect-[4/3] overflow-hidden bg-slate-100 relative">
											<?php if ( $post_item['image'] ) : ?>
												<img src="<?php echo esc_url( $post_item['image'] ); ?>" alt="<?php echo esc_attr( $post_item['title'] ); ?>" class="w-full h-full object-cover group-hover/post:scale-105 transition-transform duration-300" />
											<?php else : ?>
												<div class="w-full h-full flex items-center justify-center text-slate-300">
													<?php echo spl_icon( 'file-text', 'w-6 h-6' ); ?>
												</div>
											<?php endif; ?>
											<span class="absolute top-2 left-2 bg-primary/90 text-white font-extrabold text-[9px] px-1.5 py-0.5 rounded uppercase"><?php echo esc_html( $post_item['category'] ); ?></span>
										</a>
										<div class="p-3">
											<h4 class="font-bold text-slate-800 text-xs line-clamp-2 leading-snug group-hover/post:text-primary transition-colors">
												<a href="<?php echo esc_url( $post_item['url'] ); ?>"><?php echo esc_html( $post_item['title'] ); ?></a>
											</h4>
											<span class="text-[10px] text-slate-400 mt-2 block font-medium"><?php echo esc_html( $post_item['date'] ); ?></span>
										</div>
									</article>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>

				</div>
			</div>

			<a href="<?php echo esc_url( $store_page_url ); ?>" class="px-4 py-4 hover:bg-primary-700 transition-colors uppercase tracking-wider block"><?php esc_html_e( 'CỬA HÀNG', 'spl' ); ?></a>
			<a href="<?php echo esc_url( $coop_page_url ); ?>" class="px-4 py-4 hover:bg-primary-700 transition-colors uppercase tracking-wider block"><?php esc_html_e( 'HỢP TÁC', 'spl' ); ?></a>
			<a href="<?php echo esc_url( $about_page_url ); ?>" class="px-4 py-4 hover:bg-primary-700 transition-colors uppercase tracking-wider block"><?php esc_html_e( 'GIỚI THIỆU', 'spl' ); ?></a>
			<a href="<?php echo esc_url( $contact_page_url ); ?>" class="px-4 py-4 hover:bg-primary-700 transition-colors uppercase tracking-wider block"><?php esc_html_e( 'LIÊN HỆ', 'spl' ); ?></a>
		</div>

	</div>
</nav>
</header>

<script>
function switchMegaCat(catId, element) {
	var container = element.closest('[role="menu"]');
	if (!container) return;
	
	// Hide all panels
	var panels = container.querySelectorAll('.mega-cat-panel');
	panels.forEach(function(p) {
		p.classList.add('hidden');
		p.classList.remove('block');
	});
	
	// Show target panel
	var target = container.querySelector('#mega-cat-panel-' + catId);
	if (target) {
		target.classList.remove('hidden');
		target.classList.add('block');
	}
	
	// Active category item styling
	var items = container.querySelectorAll('.mega-cat-item');
	items.forEach(function(i) {
		i.classList.remove('bg-slate-100', 'text-primary', 'border-primary-200');
		i.classList.add('text-slate-800');
	});
	element.classList.remove('text-slate-800');
	element.classList.add('bg-slate-100', 'text-primary', 'border-primary-200');
}
</script>

<?php
/** Hook: spl_header_after_action. */
do_action( 'spl_header_after_action' );
?>

<main id="main">
