<?php
/**
 * About — Hero section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$tag   = $data['tag'] ?? 'Về chúng tôi';
$title = $data['title'] ?? '';
$desc  = $data['description'] ?? '';
?>
<section class="about-hero">
	<div class="container">
		<div class="about-hero__content reveal">
			<span class="about-hero__tag">
				<svg class="icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				<?php echo esc_html( $tag ); ?>
			</span>
			<?php if ( $title ) : ?>
				<h1 class="about-hero__title"><?php echo wp_kses_post( $title ); ?></h1>
			<?php endif; ?>
			<?php if ( $desc ) : ?>
				<p class="about-hero__desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>
