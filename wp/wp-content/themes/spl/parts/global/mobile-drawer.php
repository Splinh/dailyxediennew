<?php
/**
 * Mobile Drawer Navigation Panel (`parts/global/mobile-drawer.php`).
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$hotline       = Helper::getField( 'hotline', 'option' ) ?: '0933 505 222';
$hotline_url   = 'tel:' . preg_replace( '/[^0-9+]/', '', $hotline );
$zalo_url      = Helper::getField( 'zalo_url', 'option' ) ?: 'https://zalo.me/0933505222';
$address       = Helper::getField( 'address', 'option' ) ?: '466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP.HCM';
$logo_id       = get_theme_mod( 'custom_logo' );
$logo_url      = $logo_id ? wp_get_attachment_image_url( (int) $logo_id, 'medium' ) : '';
?>

<!-- Mobile Drawer Backdrop Overlay -->
<div data-drawer-overlay class="fixed inset-0 z-[9999] bg-slate-950/70 backdrop-blur-sm transition-opacity duration-300 hidden opacity-0"></div>

<!-- Mobile Drawer Side Panel (Left drawer) -->
<aside data-drawer class="fixed inset-y-0 left-0 z-[10000] w-80 max-w-[85vw] bg-white shadow-2xl transition-transform duration-300 -translate-x-full flex flex-col justify-between overflow-y-auto" role="dialog" aria-label="<?php esc_attr_e( 'Menu di động', 'spl' ); ?>">
	
	<!-- Top Bar: Logo & Close Button -->
	<div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/90 sticky top-0 z-10">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
			<?php if ( $logo_url ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="160" height="36" class="h-9 w-auto object-contain">
			<?php else : ?>
				<span class="font-extrabold text-slate-900 text-lg tracking-tight">dailyxedien<span class="text-primary-600">.vn</span></span>
			<?php endif; ?>
		</a>
		<button type="button" data-drawer-close class="w-9 h-9 rounded-full bg-slate-200/80 hover:bg-slate-300 text-slate-700 flex items-center justify-center transition-colors focus:outline-none" aria-label="<?php esc_attr_e( 'Đóng menu', 'spl' ); ?>">
			<?= spl_icon( 'x', 'w-5 h-5' ) ?>
		</button>
	</div>

	<!-- Main Body Section -->
	<div class="p-4 space-y-6 flex-1 overflow-y-auto">
		
		<!-- Mobile Search Bar -->
		<form action="<?php echo esc_url( home_url( '/', 'relative' ) ); ?>" method="get" class="relative">
			<input data-drawer-search type="search" name="s" placeholder="<?php esc_attr_e( 'Tìm xe điện, ắc quy, phụ kiện...', 'spl' ); ?>" autocomplete="off" class="w-full pl-10 pr-4 py-2.5 bg-slate-100 border border-slate-200 focus:bg-white focus:border-primary-600 rounded-xl text-sm outline-none transition-all">
			<span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
				<?= spl_icon( 'search', 'w-4 h-4' ) ?>
			</span>
			<?php if ( Helper::isWoocommerceActive() ) : ?>
				<input type="hidden" name="post_type" value="product" />
			<?php endif; ?>
		</form>

		<!-- Primary Mobile Navigation Links -->
		<nav class="space-y-1" aria-label="<?php esc_attr_e( 'Menu chính', 'spl' ); ?>">
			<?php if ( has_nav_menu( 'primary-nav' ) ) : ?>
				<?php
				wp_nav_menu( [
					'theme_location' => 'primary-nav',
					'container'      => false,
					'menu_class'     => 'space-y-1 font-semibold text-sm text-slate-800',
					'fallback_cb'    => false,
				] );
				?>
			<?php else : ?>
				<ul class="space-y-1 font-bold text-sm text-slate-800">
					<li>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center justify-between px-3.5 py-3 rounded-xl hover:bg-slate-100 hover:text-primary-600 transition-colors">
							<span>Trang Chủ</span>
							<?= spl_icon( 'chevron-right', 'w-4 h-4 text-slate-400' ) ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( home_url( '/gioi-thieu/' ) ); ?>" class="flex items-center justify-between px-3.5 py-3 rounded-xl hover:bg-slate-100 hover:text-primary-600 transition-colors">
							<span>Giới Thiệu</span>
							<?= spl_icon( 'chevron-right', 'w-4 h-4 text-slate-400' ) ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( home_url( '/danh-muc-san-pham/' ) ); ?>" class="flex items-center justify-between px-3.5 py-3 rounded-xl hover:bg-slate-100 hover:text-primary-600 transition-colors">
							<span>Sản Phẩm Xe Điện</span>
							<?= spl_icon( 'chevron-right', 'w-4 h-4 text-slate-400' ) ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( home_url( '/su-menh/' ) ); ?>" class="flex items-center justify-between px-3.5 py-3 rounded-xl hover:bg-slate-100 hover:text-primary-600 transition-colors">
							<span>Sứ Mệnh</span>
							<?= spl_icon( 'chevron-right', 'w-4 h-4 text-slate-400' ) ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( home_url( '/co-hoi-hop-tac/' ) ); ?>" class="flex items-center justify-between px-3.5 py-3 rounded-xl hover:bg-slate-100 hover:text-primary-600 transition-colors">
							<span>Cơ Hội Hợp Tác</span>
							<?= spl_icon( 'chevron-right', 'w-4 h-4 text-slate-400' ) ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( home_url( '/he-thong-cua-hang/' ) ); ?>" class="flex items-center justify-between px-3.5 py-3 rounded-xl hover:bg-slate-100 hover:text-primary-600 transition-colors">
							<span>Hệ Thống Cửa Hàng</span>
							<?= spl_icon( 'chevron-right', 'w-4 h-4 text-slate-400' ) ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( home_url( '/tin-tuc/' ) ); ?>" class="flex items-center justify-between px-3.5 py-3 rounded-xl hover:bg-slate-100 hover:text-primary-600 transition-colors">
							<span>Tin Tức & Sự Kiện</span>
							<?= spl_icon( 'chevron-right', 'w-4 h-4 text-slate-400' ) ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>" class="flex items-center justify-between px-3.5 py-3 rounded-xl hover:bg-slate-100 hover:text-primary-600 transition-colors">
							<span>Liên Hệ</span>
							<?= spl_icon( 'chevron-right', 'w-4 h-4 text-slate-400' ) ?>
						</a>
					</li>
				</ul>
			<?php endif; ?>
		</nav>

		<!-- Action Callouts inside Drawer -->
		<div class="pt-4 border-t border-slate-100 space-y-2.5">
			<a href="<?php echo esc_url( $hotline_url ); ?>" class="flex items-center justify-center gap-2.5 py-3 px-4 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold text-sm shadow-md transition-colors w-full">
				<?= spl_icon( 'phone', 'w-4.5 h-4.5 shrink-0' ) ?>
				<span>Hotline: <?php echo esc_html( $hotline ); ?></span>
			</a>
			<a href="<?php echo esc_url( $zalo_url ); ?>" target="_blank" class="flex items-center justify-center gap-2.5 py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-md transition-colors w-full">
				<?= spl_icon( 'zalo', 'w-4.5 h-4.5 shrink-0' ) ?>
				<span>Chat Zalo Tư Vấn</span>
			</a>
		</div>

	</div>

	<!-- Drawer Footer Info -->
	<div class="p-4 border-t border-slate-100 bg-slate-50 text-xs text-slate-500 space-y-1">
		<p class="font-bold text-slate-800">DailyXeDien.vn</p>
		<p class="truncate"><?php echo esc_html( $address ); ?></p>
	</div>

</aside>
