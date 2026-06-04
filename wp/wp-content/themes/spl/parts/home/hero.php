<?php
/**
 * Home page — Hero section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

// ACF fields or fallback defaults.
$badge_text  = $data['badge_text'] ?? __( 'Thảo Dược Thiên Nhiên 100%', 'spl' );
$title       = $data['title'] ?? __( 'Chăm Sóc Sức Khỏe Bằng <span>Thảo Dược</span> Thiên Nhiên', 'spl' );
$description = $data['description'] ?? __( 'Chuyên cung cấp thảo dược khô, trà túi lọc, bột nguyên chất, tinh dầu thiên nhiên với chất lượng cao nhất.', 'spl' );
$btn_primary = $data['btn_primary'] ?? [ 'title' => __( 'Mua Ngay', 'spl' ), 'url' => '#products' ];
$btn_outline = $data['btn_outline'] ?? [ 'title' => __( 'Tìm Hiểu Thêm', 'spl' ), 'url' => '#about' ];

// Background image.
$bg_image = '';
if ( ! empty( $data['bg_image'] ) ) {
	$bg_image = is_array( $data['bg_image'] ) ? ( $data['bg_image']['url'] ?? '' ) : wp_get_attachment_image_url( $data['bg_image'], 'full' );
}
if ( empty( $bg_image ) ) {
	$bg_image = get_theme_file_uri( 'resources/img/hero-banner.png' );
}

// Product image.
$product_image = '';
if ( ! empty( $data['product_image'] ) ) {
	$product_image = is_array( $data['product_image'] ) ? ( $data['product_image']['url'] ?? '' ) : wp_get_attachment_image_url( $data['product_image'], 'large' );
}
if ( empty( $product_image ) ) {
	$product_image = get_theme_file_uri( 'resources/img/products-collection.png' );
}

?>
<section class="hero">
	<div class="hero__bg"><img src="<?php echo esc_url( $bg_image ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" loading="eager" /></div>
	<div class="hero__overlay"></div>
	<div class="hero__particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
	<div class="hero__content">
		<div class="container hero__grid">
			<div class="hero__text">
				<div class="hero__badge">
					<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>
					<?php echo esc_html( $badge_text ); ?>
				</div>
				<h1 class="hero__title"><?php echo wp_kses_post( $title ); ?></h1>
				<p class="hero__desc"><?php echo esc_html( $description ); ?></p>
				<div class="hero__cta">
					<?php if ( $btn_primary ) : ?>
						<a href="<?php echo esc_url( is_array( $btn_primary ) ? $btn_primary['url'] : '#products' ); ?>" class="btn btn--primary">
							<svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
							<?php echo esc_html( is_array( $btn_primary ) ? $btn_primary['title'] : $btn_primary ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $btn_outline ) : ?>
						<a href="<?php echo esc_url( is_array( $btn_outline ) ? $btn_outline['url'] : '#about' ); ?>" class="btn btn--outline">
							<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
							<?php echo esc_html( is_array( $btn_outline ) ? $btn_outline['title'] : $btn_outline ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
			<div class="hero__image">
				<img src="<?php echo esc_url( $product_image ); ?>" alt="<?php esc_attr_e( 'Sản phẩm thảo dược Thaphaco', 'spl' ); ?>" loading="eager" />
			</div>
		</div>
	</div>
</section>
