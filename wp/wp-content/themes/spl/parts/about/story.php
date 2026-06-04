<?php
/**
 * About — Story section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data         = $args ?? [];
$title        = $data['title'] ?? '';
$content      = $data['content'] ?? '';
$image_id     = $data['image'] ?? 0;
$badge_number = $data['badge_number'] ?? '10+';
$badge_label  = $data['badge_label'] ?? 'Năm kinh nghiệm';

$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : get_theme_file_uri( 'resources/img/products-collection.png' );
?>
<section class="about-story">
	<div class="container">
		<div class="about-story__grid">
			<div class="about-story__text reveal">
				<?php if ( $title ) : ?>
					<h2><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>
				<?php echo wp_kses_post( wpautop( $content ) ); ?>
			</div>
			<div class="about-story__image reveal">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
				<div class="about-story__badge">
					<strong><?php echo esc_html( $badge_number ); ?></strong>
					<span><?php echo esc_html( $badge_label ); ?></span>
				</div>
			</div>
		</div>
	</div>
</section>
