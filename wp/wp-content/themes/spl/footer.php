<?php
/**
 * The template for displaying the footer.
 *
 * Contains the footer grid, copyright bar, and fixed buttons
 * converted from the HTML mockup.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

// ACF options data.
$hotline = Helper::getField( 'hotline', 'option' ) ?: '098 750 33 60';
$email   = Helper::getField( 'email', 'option' ) ?: 'Lachuyhddt@gmail.com';
$address = Helper::getField( 'address', 'option' ) ?: 'TP. Hồ Chí Minh, Việt Nam';

// Company / footer details (option page, blank = hide row).
$company_name    = Helper::getField( 'company_name', 'option' );
$company_intl    = Helper::getField( 'company_intl_name', 'option' );
$company_tax     = Helper::getField( 'company_tax', 'option' );
$complaint_phone = Helper::getField( 'complaint_phone', 'option' );
$addr_showroom   = Helper::getField( 'addr_showroom', 'option' );
$addr_farm       = Helper::getField( 'addr_farm', 'option' );
$addr_factory    = Helper::getField( 'addr_factory', 'option' );
$bank_account    = Helper::getField( 'bank_account', 'option' );
$website_url     = Helper::getField( 'website_url', 'option' );
$payment_image   = Helper::getField( 'payment_image', 'option' );
$gov_badge_url   = Helper::getField( 'gov_badge_url', 'option' );

$hotline_display = is_array( $hotline ) ? ( $hotline['title'] ?? $hotline['url'] ?? '098 750 33 60' ) : $hotline;
$hotline_url     = is_array( $hotline ) ? ( $hotline['url'] ?? 'tel:' . preg_replace( '/\s+/', '', $hotline_display ) ) : 'tel:' . preg_replace( '/\s+/', '', $hotline );

// Social link options.
$social_options = class_exists( '\Addons\Helper' )
	? \Addons\Helper::getOption( 'social_link__options', [] )
	: get_option( 'social_link__options', [] );

$zalo_url      = $social_options['zalo']['url'] ?? 'https://zalo.me/0987503360';
$messenger_url = $social_options['messenger']['url'] ?? 'https://zalo.me/0987503360';

// Social icons for the footer brand block. Uses configured URLs when present,
// otherwise renders the reference set so the block is never empty.
$footer_socials = [
	'facebook'  => [
		'url'   => $social_options['facebook']['url'] ?? '#',
		'label' => 'Facebook',
		'svg'   => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
	],
	'youtube'   => [
		'url'   => $social_options['youtube']['url'] ?? '#',
		'label' => 'Youtube',
		'svg'   => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>',
	],
	'tiktok'    => [
		'url'   => $social_options['tiktok']['url'] ?? '#',
		'label' => 'TikTok',
		'svg'   => '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>',
	],
	'zalo'      => [
		'url'   => $zalo_url,
		'label' => 'Zalo',
		'svg'   => '<path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/>',
	],
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
<footer class="footer">
	<div class="container">
		<div class="footer-grid">
			<!-- Company / Brand -->
			<div class="footer__brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer__brand-logo">
					<?php
					$footer_logo_id = get_theme_mod( 'custom_logo' );
					if ( $footer_logo_id ) :
						echo wp_get_attachment_image( $footer_logo_id, 'medium', false, [
							'class'   => 'footer__brand-img',
							'loading' => 'lazy',
							'alt'     => esc_attr( get_bloginfo( 'name' ) ),
						] );
					else :
					?>
						<svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M11.7 11.2a5.18 5.18 0 0 1 3.3-2.2c2.5-.4 4-1 4-1s-.3 2.3-2 4c-1.7 1.7-3.3 2.5-3.3 2.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
						<span class="footer__brand-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
					<?php endif; ?>
				</a>
				<p class="footer__brand-desc"><?php echo esc_html( Helper::getField( 'footer_desc', 'option' ) ?: __( 'Chuyên cung cấp thảo dược thiên nhiên, trà túi lọc, bột nguyên chất, tinh dầu thiên nhiên. Tất cả vì sức khỏe cộng đồng.', 'spl' ) ); ?></p>

				<ul class="footer__company">
					<?php if ( $company_intl ) : ?>
						<li><span class="footer__company-label"><?php esc_html_e( 'Tên quốc tế:', 'spl' ); ?></span> <?php echo esc_html( $company_intl ); ?></li>
					<?php endif; ?>
					<?php if ( $company_tax ) : ?>
						<li><span class="footer__company-label"><?php esc_html_e( 'MST:', 'spl' ); ?></span> <?php echo esc_html( $company_tax ); ?></li>
					<?php endif; ?>
					<?php if ( $addr_showroom ) : ?>
						<li><span class="footer__company-label"><?php esc_html_e( 'Showroom:', 'spl' ); ?></span> <?php echo esc_html( $addr_showroom ); ?></li>
					<?php endif; ?>
					<?php if ( $addr_farm ) : ?>
						<li><span class="footer__company-label"><?php esc_html_e( 'Vùng trồng:', 'spl' ); ?></span> <?php echo esc_html( $addr_farm ); ?></li>
					<?php endif; ?>
					<?php if ( $addr_factory ) : ?>
						<li><span class="footer__company-label"><?php esc_html_e( 'Nhà máy:', 'spl' ); ?></span> <?php echo esc_html( $addr_factory ); ?></li>
					<?php endif; ?>
					<?php if ( $bank_account ) : ?>
						<li><span class="footer__company-label"><?php esc_html_e( 'STK:', 'spl' ); ?></span> <?php echo esc_html( $bank_account ); ?></li>
					<?php endif; ?>
				</ul>

				<div class="footer__social">
					<?php
					$social_html = trim( Helper::doShortcode( 'social_menu', [ 'class' => 'footer__social-links' ] ) );
					if ( $social_html ) {
						echo $social_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
					} else {
						foreach ( $footer_socials as $social ) :
							?>
							<a href="<?php echo esc_url( $social['url'] ); ?>" aria-label="<?php echo esc_attr( $social['label'] ); ?>"<?php echo ( '#' !== $social['url'] ) ? ' target="_blank" rel="noopener"' : ''; ?>>
								<svg class="icon" viewBox="0 0 24 24"><?php echo $social['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></svg>
							</a>
							<?php
						endforeach;
					}
					?>
				</div>
			</div>

			<!-- About us -->
			<div>
				<h4 class="footer__heading"><?php esc_html_e( 'Về Chúng Tôi', 'spl' ); ?></h4>
				<ul class="footer__links">
					<?php
					if ( has_nav_menu( 'about-nav' ) ) :
						wp_nav_menu( [
							'theme_location' => 'about-nav',
							'container'      => false,
							'items_wrap'     => '%3$s',
							'fallback_cb'    => false,
							'depth'          => 1,
						] );
					else :
					?>
						<li><a href="<?php echo esc_url( home_url( '/gioi-thieu/' ) ); ?>"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Giới thiệu</a></li>
						<li><a href="#"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Triết lý kinh doanh</a></li>
						<li><a href="#"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Tầm nhìn &amp; Sứ mệnh</a></li>
						<li><a href="#"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Giá trị cốt lõi</a></li>
						<li><a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Liên hệ</a></li>
					<?php endif; ?>
				</ul>
			</div>

			<!-- Policies -->
			<div>
				<h4 class="footer__heading"><?php esc_html_e( 'Chính Sách', 'spl' ); ?></h4>
				<ul class="footer__links">
					<?php
					if ( has_nav_menu( 'policy-nav' ) ) :
						wp_nav_menu( [
							'theme_location' => 'policy-nav',
							'container'      => false,
							'items_wrap'     => '%3$s',
							'fallback_cb'    => false,
							'depth'          => 1,
						] );
					else :
					?>
						<li><a href="#"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Hướng dẫn mua hàng</a></li>
						<li><a href="#"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Chính sách thanh toán</a></li>
						<li><a href="#"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Chính sách vận chuyển</a></li>
						<li><a href="#"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Chính sách đổi trả</a></li>
						<li><a href="#"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg> Chính sách bảo mật</a></li>
					<?php endif; ?>
				</ul>
			</div>

			<!-- Contact & Payment -->
			<div>
				<h4 class="footer__heading"><?php esc_html_e( 'Liên Hệ', 'spl' ); ?></h4>
				<div class="footer__contact-item">
					<svg class="icon" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
					<span><?php echo esc_html( $address ); ?></span>
				</div>
				<div class="footer__contact-item">
					<svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
					<span><a href="<?php echo esc_url( $hotline_url ); ?>" style="color:inherit;"><?php echo esc_html( $hotline_display ); ?></a></span>
				</div>
				<?php if ( $complaint_phone ) : ?>
					<div class="footer__contact-item">
						<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
						<span><?php esc_html_e( 'Khiếu nại:', 'spl' ); ?> <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $complaint_phone ) ); ?>" style="color:inherit;"><?php echo esc_html( $complaint_phone ); ?></a></span>
					</div>
				<?php endif; ?>
				<div class="footer__contact-item">
					<svg class="icon" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
					<span><a href="mailto:<?php echo esc_attr( $email ); ?>" style="color:inherit;"><?php echo esc_html( $email ); ?></a></span>
				</div>
				<?php if ( $website_url ) : ?>
					<div class="footer__contact-item">
						<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
						<span><a href="<?php echo esc_url( $website_url ); ?>" target="_blank" rel="noopener" style="color:inherit;"><?php echo esc_html( preg_replace( '#^https?://#', '', untrailingslashit( $website_url ) ) ); ?></a></span>
					</div>
				<?php endif; ?>

				<?php if ( $payment_image || $gov_badge_url ) : ?>
					<div class="footer__payment">
						<?php if ( $payment_image ) : ?>
							<img class="footer__payment-img" src="<?php echo esc_url( is_array( $payment_image ) ? ( $payment_image['url'] ?? '' ) : $payment_image ); ?>" alt="<?php esc_attr_e( 'Phương thức thanh toán', 'spl' ); ?>" loading="lazy" />
						<?php endif; ?>
						<?php if ( $gov_badge_url ) : ?>
							<a class="footer__gov" href="<?php echo esc_url( $gov_badge_url ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Đã thông báo Bộ Công Thương', 'spl' ); ?>">
								<img src="https://www.online.gov.vn/Content/EndUser/LogoCCDVDA/logoSaleNoti.png" alt="<?php esc_attr_e( 'Bộ Công Thương', 'spl' ); ?>" loading="lazy" />
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="footer-bottom">
			<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</span>
			<span><?php esc_html_e( 'Designed with', 'spl' ); ?> <svg viewBox="0 0 24 24" width="13" height="13" fill="#ef4444" stroke="#ef4444" stroke-width="1" style="vertical-align:-2px;" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg> <?php esc_html_e( 'for Health', 'spl' ); ?></span>
		</div>
	</div>
</footer>

<!-- FIXED BUTTONS -->
<div class="fixed-buttons fixed-buttons--right">
	<a href="<?php echo esc_url( $zalo_url ); ?>" class="fixed-btn fixed-btn--zalo" target="_blank" rel="noopener" aria-label="Chat Zalo" title="Chat Zalo">
		<svg class="fill-current" width="77" height="28" viewBox="0 0 77 28" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_3008_2882)"><path d="M22.0532 25.2978V22.8502H6.44851L20.2322 5.55324C20.4196 5.30146 20.8177 4.83888 20.9993 4.64565L21.0871 4.53439C21.7699 3.54464 22.1055 2.35654 22.0415 1.15581V0.5H0.622355V5.09066H15.4834L0.803874 23.2776C0.230916 24.0588-0.0507921 25.0158 0.00753473 25.9828V27.1539H20.2146C20.4574 27.1532 20.6975 27.1045 20.9214 27.0107 21.1453 26.917 21.3484 26.7799 21.5192 26.6075 21.69 26.4351 21.8251 26.2306 21.9168 26.0059 22.0084 25.7811 22.0548 25.5405 22.0532 25.2978Z" fill="#0068FF"/><path d="M49.2929 27.1364H52.3553V0.5H47.7646V25.6081C47.7646 26.0134 47.9257 26.4021 48.2123 26.6888 48.4989 26.9754 48.8876 27.1364 49.2929 27.1364Z" fill="#0068FF"/><path d="M39.8246 8.41661C38.2641 7.27375 36.4176 6.5855 34.4898 6.42823 32.562 6.27095 30.6283 6.65079 28.9032 7.52561 27.1781 8.40043 25.7291 9.73603 24.717 11.3843 23.7048 13.0325 23.1689 14.9289 23.1689 16.8631 23.1689 18.7973 23.7048 20.6937 24.717 22.3419 25.7291 23.9901 27.1781 25.3257 28.9032 26.2005 30.6283 27.0754 32.562 27.4552 34.4898 27.2979 36.4176 27.1406 38.2641 26.4524 39.8246 25.3095 39.8277 25.7941 40.0219 26.2579 40.3651 26.6 40.7083 26.9421 41.1727 27.1349 41.6573 27.1364H44.1166V6.98203H39.8246V8.41661ZM33.6295 23.0201C32.4095 23.0212 31.2166 22.66 30.202 21.9823 29.1875 21.3046 28.3971 20.3408 27.931 19.2133 27.465 18.0858 27.3442 16.8453 27.5841 15.649 27.824 14.4528 28.4137 13.3547 29.2784 12.4941 30.1432 11.6334 31.2441 11.0489 32.4415 10.8148 33.6388 10.5806 34.8788 10.7073 36.0041 11.1787 37.1293 11.6502 38.0893 12.4452 38.7622 13.4629 39.435 14.4806 39.7905 15.6752 39.7836 16.8953 39.7743 18.5219 39.122 20.0788 37.9691 21.2262 36.8162 22.3737 35.2562 23.0185 33.6295 23.0201Z" fill="#0068FF"/><path d="M65.9338 6.27344C63.8493 6.27344 61.8115 6.89159 60.0782 8.04971 58.345 9.20784 56.9941 10.8539 56.1963 12.7798 55.3986 14.7057 55.1899 16.8249 55.5966 18.8694 56.0032 20.9139 57.0071 22.792 58.4811 24.266 59.9551 25.74 61.8331 26.7438 63.8776 27.1505 65.9221 27.5572 68.0413 27.3484 69.9672 26.5507 71.8931 25.753 73.5392 24.4021 74.6973 22.6688 75.8555 20.9355 76.4736 18.8978 76.4736 16.8132 76.4736 14.0179 75.3632 11.3371 73.3866 9.36047 71.41 7.38388 68.7292 6.27344 65.9338 6.27344ZM65.9338 23.0083C64.7072 23.0094 63.5077 22.6467 62.4872 21.9661 61.4667 21.2854 60.671 20.3174 60.2008 19.1844 59.7305 18.0514 59.6069 16.8045 59.8455 15.6012 60.0841 14.398 60.6742 13.2925 61.5412 12.4247 62.4082 11.5569 63.5131 10.9658 64.7161 10.726 65.9191 10.4863 67.1662 10.6088 68.2996 11.0779 69.433 11.5471 70.4018 12.3419 71.0834 13.3617 71.7651 14.3816 72.1289 15.5807 72.1289 16.8074 72.1289 18.4509 71.4764 20.0273 70.3148 21.19 69.1531 22.3527 67.5774 23.0067 65.9338 23.0083Z" fill="#0068FF"/></g><defs><rect width="77" height="27" fill="white" transform="translate(0 0.5)"/></defs></svg>
	</a>
	<a href="<?php echo esc_url( $messenger_url ); ?>" class="fixed-btn fixed-btn--messenger" target="_blank" rel="noopener" aria-label="Messenger" title="Chat Messenger">
		<svg viewBox="0 0 48 48" width="28" height="28" fill="none"><defs><linearGradient id="msg-grad" x1="24" y1="2" x2="24" y2="46" gradientUnits="userSpaceOnUse"><stop stop-color="#00B2FF"/><stop offset="1" stop-color="#006AFF"/></linearGradient></defs><circle cx="24" cy="24" r="24" fill="url(#msg-grad)"/><path d="M24 9C15.72 9 9 15.08 9 22.64c0 4.26 2.12 8.06 5.44 10.54V38l4.58-2.52c1.22.34 2.52.52 3.86.52.12 0 .24 0 .36-.01 8.16-.12 14.76-6.24 14.76-13.35C38 15.08 32.28 9 24 9zm1.52 18l-3.76-4.02L14.24 27l7.28-7.72 3.76 4.02L32.76 19l-7.24 8z" fill="white"/></svg>
	</a>
	<a href="<?php echo esc_url( $hotline_url ); ?>" class="fixed-btn fixed-btn--phone" aria-label="<?php esc_attr_e( 'Gọi điện', 'spl' ); ?>">
		<svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
	</a>
	<button class="fixed-btn fixed-btn--scroll-top" id="scroll-top-btn" aria-label="<?php esc_attr_e( 'Lên đầu trang', 'spl' ); ?>">
		<svg class="icon" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
	</button>
</div>

<?php
/** Hook: spl_footer_action. */
do_action( 'spl_footer_action' );

wp_footer();
?>
</body>
</html>
