<?php
/**
 * About — CTA section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data        = $args ?? [];
$title       = $data['title'] ?? '';
$desc        = $data['description'] ?? '';
$btn_primary = $data['btn_primary'] ?? [];
$btn_outline = $data['btn_outline'] ?? [];
?>
<section class="about-cta">
	<div class="container">
		<div class="about-cta__content reveal">
			<?php if ( $title ) : ?>
				<h2><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>
			<?php if ( $desc ) : ?>
				<p><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
			<div class="about-cta__btns">
				<?php if ( ! empty( $btn_primary['url'] ) ) : ?>
					<a href="<?php echo esc_url( $btn_primary['url'] ); ?>" class="btn btn--primary btn--lg" <?php echo ! empty( $btn_primary['target'] ) ? 'target="_blank" rel="noopener"' : ''; ?>>
						<?php echo esc_html( $btn_primary['title'] ?? __( 'Xem Sản Phẩm', 'spl' ) ); ?>
					</a>
				<?php endif; ?>
				<?php if ( ! empty( $btn_outline['url'] ) ) : ?>
					<a href="<?php echo esc_url( $btn_outline['url'] ); ?>" class="btn btn--outline btn--lg" <?php echo ! empty( $btn_outline['target'] ) ? 'target="_blank" rel="noopener"' : ''; ?>>
						<?php echo esc_html( $btn_outline['title'] ?? __( 'Liên Hệ Ngay', 'spl' ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
