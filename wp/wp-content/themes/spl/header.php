<?php
/**
 * The template for displaying the header.
 *
 * Contains the top-bar, header, nav-bar, and mobile-nav
 * converted from the HTML mockup.
 *
 * @package SPL
 */

use SPL\Core\Helper;
use SPL\Core\Asset;

defined( 'ABSPATH' ) || exit;

// ACF options data (safe fallback if ACF not active).
$hotline = Helper::getField( 'hotline', 'option' ) ?: '098 750 33 60';
$email   = Helper::getField( 'email', 'option' ) ?: 'Lachuyhddt@gmail.com';
$address = Helper::getField( 'address', 'option' ) ?: 'TP. Hồ Chí Minh, Việt Nam';

// Hotline display & URL.
$hotline_display = is_array( $hotline ) ? ( $hotline['title'] ?? $hotline['url'] ?? '098 750 33 60' ) : $hotline;
$hotline_url     = is_array( $hotline ) ? ( $hotline['url'] ?? 'tel:' . preg_replace( '/\s+/', '', $hotline_display ) ) : 'tel:' . preg_replace( '/\s+/', '', $hotline );

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="format-detection" content="telephone=no,email=no,address=no" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
/** Hook: spl_header_before_action. */
do_action( 'spl_header_before_action' );
?>

<!-- ===== TOP BAR ===== -->
<div class="top-bar">
	<div class="container">
		<div class="top-bar__brand"><em><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></em></div>
		<div class="top-bar__links">
			<?php if ( Helper::isWoocommerceActive() ) : ?>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
					<span><?php esc_html_e( 'Giỏ hàng', 'spl' ); ?></span>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">
				<svg class="icon" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
				<span><?php esc_html_e( 'Liên Hệ', 'spl' ); ?></span>
			</a>
			<a href="mailto:<?php echo esc_attr( $email ); ?>">
				<svg class="icon" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
				<span>Email</span>
			</a>
			<a href="#">
				<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
				<span>08:00 - 17:30</span>
			</a>
			<a href="<?php echo esc_url( $hotline_url ); ?>">
				<svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
				<span><?php echo esc_html( $hotline_display ); ?></span>
			</a>
		</div>
		<div class="top-bar__social">
			<?php echo Helper::doShortcode( 'social_menu', [ 'class' => 'top-bar__social-links' ] ); ?>
		</div>
	</div>
</div>

<!-- ===== HEADER ===== -->
<header class="header" id="header">
	<div class="container">
		<?php
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) :
			echo Helper::siteTitleOrLogo( false );
		else :
		?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo" aria-label="<?php esc_attr_e( 'Trang chủ', 'spl' ); ?>">
			<div class="logo__icon">
				<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M11.7 11.2a5.18 5.18 0 0 1 3.3-2.2c2.5-.4 4-1 4-1s-.3 2.3-2 4c-1.7 1.7-3.3 2.5-3.3 2.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
			</div>
			<div class="logo__text">
				<span class="logo__name"><?php bloginfo( 'name' ); ?></span>
				<span class="logo__tagline"><?php bloginfo( 'description' ); ?></span>
			</div>
		</a>
		<?php endif; ?>

		<div class="search-bar" role="search">
			<div class="search-bar__wrapper" data-search>
				<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
					<label for="search-input" class="sr-only"><?php esc_html_e( 'Tìm kiếm sản phẩm', 'spl' ); ?></label>
					<input id="search-input" type="search" class="search-bar__input" name="s" placeholder="<?php esc_attr_e( 'Tìm sản phẩm thảo dược...', 'spl' ); ?>" autocomplete="off" value="<?php echo get_search_query(); ?>" data-search-input />
					<?php if ( Helper::isWoocommerceActive() ) : ?>
						<input type="hidden" name="post_type" value="product" />
					<?php endif; ?>
					<button type="submit" class="search-bar__btn" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
						<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					</button>
				</form>
				<div class="search-results" data-search-results hidden></div>
			</div>
		</div>

		<div class="header-actions">
			<em class="header-actions__slogan">"<?php echo esc_html( get_bloginfo( 'description' ) ?: 'Tất Cả Vì Sức Khỏe Cộng Đồng' ); ?>"</em>
			<button class="btn-icon btn-icon--mobile-search" id="mobile-search-btn" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
				<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
			</button>
			<?php if ( Helper::isWoocommerceActive() ) : ?>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="btn-icon" data-mini-cart-open aria-label="<?php esc_attr_e( 'Giỏ hàng', 'spl' ); ?>">
					<svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
					<span class="btn-icon__badge" id="cart-badge"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span>
				</a>
			<?php endif; ?>
			<button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="<?php esc_attr_e( 'Mở menu', 'spl' ); ?>" aria-expanded="false">
				<span></span><span></span><span></span>
			</button>
		</div>
	</div>
</header>

<!-- ===== MOBILE SEARCH BAR (compact) ===== -->
<div class="mobile-search-bar" id="mobile-search-bar">
	<div class="container">
		<div class="search-bar__wrapper" data-search>
			<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
				<label for="search-input-mobile-bar" class="sr-only"><?php esc_html_e( 'Tìm kiếm sản phẩm', 'spl' ); ?></label>
				<input id="search-input-mobile-bar" type="search" class="search-bar__input" name="s" placeholder="<?php esc_attr_e( 'Tìm sản phẩm thảo dược...', 'spl' ); ?>" autocomplete="off" data-search-input />
				<?php if ( Helper::isWoocommerceActive() ) : ?>
					<input type="hidden" name="post_type" value="product" />
				<?php endif; ?>
				<button type="submit" class="search-bar__btn" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
					<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
				</button>
			</form>
			<div class="search-results" data-search-results hidden></div>
		</div>
	</div>
</div>

<!-- ===== NAV BAR ===== -->
<nav class="nav-bar" aria-label="<?php esc_attr_e( 'Main navigation', 'spl' ); ?>">
	<div class="container" style="position: relative;">
		<button class="nav-category-btn" id="category-toggle" aria-expanded="false" aria-controls="category-dropdown">
			<svg class="icon" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
			<?php esc_html_e( 'Danh mục sản phẩm', 'spl' ); ?>
		</button>

		<div class="category-dropdown" id="category-dropdown" role="menu">
			<?php
			if ( Helper::isWoocommerceActive() ) :
				$product_cats = get_terms( [
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'parent'     => 0,
					'orderby'    => 'menu_order',
					'order'      => 'ASC',
				] );

				if ( ! is_wp_error( $product_cats ) && ! empty( $product_cats ) ) :
					foreach ( $product_cats as $cat ) :
						$cat_link = get_term_link( $cat );
						if ( is_wp_error( $cat_link ) ) {
							continue;
						}
						?>
						<a href="<?php echo esc_url( $cat_link ); ?>" role="menuitem">
							<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
							<?php echo esc_html( $cat->name ); ?>
						</a>
						<?php
					endforeach;
				endif;
			endif;
			?>
		</div>

		<div class="nav-links">
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

<!-- ===== MOBILE NAV ===== -->
<div class="mobile-overlay" id="mobile-overlay"></div>
<nav class="mobile-nav" id="mobile-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'spl' ); ?>">
	<button class="mobile-nav__close" id="mobile-nav-close" aria-label="<?php esc_attr_e( 'Đóng menu', 'spl' ); ?>">
		<svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
	</button>
	<div style="margin-bottom: 24px; margin-top: 8px;">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
			<div class="logo__icon">
				<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
			</div>
			<div class="logo__text">
				<span class="logo__name"><?php bloginfo( 'name' ); ?></span>
				<span class="logo__tagline"><?php bloginfo( 'description' ); ?></span>
			</div>
		</a>
	</div>

	<!-- Mobile search -->
	<div class="search-bar search-bar--mobile" role="search">
		<div class="search-bar__wrapper" data-search>
			<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
				<label for="search-input-mobile" class="sr-only"><?php esc_html_e( 'Tìm kiếm sản phẩm', 'spl' ); ?></label>
				<input id="search-input-mobile" type="search" class="search-bar__input" name="s" placeholder="<?php esc_attr_e( 'Tìm sản phẩm thảo dược...', 'spl' ); ?>" autocomplete="off" data-search-input />
				<?php if ( Helper::isWoocommerceActive() ) : ?>
					<input type="hidden" name="post_type" value="product" />
				<?php endif; ?>
				<button type="submit" class="search-bar__btn" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'spl' ); ?>">
					<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
				</button>
			</form>
			<div class="search-results" data-search-results hidden></div>
		</div>
	</div>

	<div class="mobile-nav__links">
		<?php
		wp_nav_menu( [
			'theme_location' => 'mobile-nav',
			'container'      => false,
			'items_wrap'     => '%3$s',
			'fallback_cb'    => function() {
				wp_nav_menu( [
					'theme_location' => 'main-nav',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'fallback_cb'    => false,
					'depth'          => 1,
				] );
			},
			'depth'          => 1,
		] );
		?>
	</div>
</nav>

<?php
/** Hook: spl_header_after_action. */
do_action( 'spl_header_after_action' );
?>

<main id="main">
