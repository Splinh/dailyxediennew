<?php
/**
 * Home page — About section.
 *
 * Markup matches website/index.html (#about) + inc/critical.css.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data = $args ?? [];

$label = $data['label'] ?? __( 'Về Chúng Tôi', 'spl' );
$title = $data['title'] ?? __( 'Công Ty TNHH Thảo Dược Thaphaco', 'spl' );

// Body: array of paragraphs, or single string from ACF.
$paragraphs = $data['paragraphs'] ?? null;
if ( empty( $paragraphs ) ) {
	$desc = $data['description'] ?? '';
	$paragraphs = $desc
		? [ $desc ]
		: [
			__( 'Thảo Dược Thaphaco là đơn vị chuyên cung cấp các sản phẩm thảo dược thiên nhiên chất lượng cao. Với hệ thống thu mua rộng khắp các tỉnh thành Việt Nam, chúng tôi mang đến cho quý khách hàng những sản phẩm từ thiên nhiên tốt nhất.', 'spl' ),
			__( 'Chúng tôi cam kết mang đến sản phẩm 100% từ thiên nhiên, được kiểm định chất lượng nghiêm ngặt. <strong>"Tất Cả Vì Sức Khỏe Cộng Đồng"</strong> là kim chỉ nam trong mọi hoạt động.', 'spl' ),
		];
}

$stats = $data['stats'] ?? [
	[ 'number' => '500+', 'label' => __( 'Sản phẩm', 'spl' ) ],
	[ 'number' => '10K+', 'label' => __( 'Khách hàng', 'spl' ) ],
	[ 'number' => '63', 'label' => __( 'Tỉnh thành', 'spl' ) ],
];

// Image (ACF or default).
$image_url = '';
if ( ! empty( $data['image'] ) ) {
	$image_url = is_array( $data['image'] ) ? ( $data['image']['url'] ?? '' ) : wp_get_attachment_image_url( $data['image'], 'large' );
}
if ( empty( $image_url ) ) {
	$image_url = get_theme_file_uri( 'resources/img/products-collection.png' );
}

?>
<section class="section-compact about-section section-alt" id="about">
	<div class="container">
		<div class="about__image reveal">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
		</div>
		<div class="about__content reveal">
			<div class="section-title__label">
				<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
				<?php echo esc_html( $label ); ?>
			</div>
			<h2 class="about__title"><?php echo esc_html( $title ); ?></h2>
			<?php foreach ( $paragraphs as $paragraph ) : ?>
				<p class="about__text"><?php echo wp_kses_post( $paragraph ); ?></p>
			<?php endforeach; ?>
			<div class="about__stats">
				<?php foreach ( $stats as $stat ) : ?>
					<div class="stat-item">
						<div class="stat-item__number"><?php echo esc_html( $stat['number'] ); ?></div>
						<div class="stat-item__label"><?php echo esc_html( $stat['label'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
