<?php
/**
 * Contact — FAQ section.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$badge = $data['badge'] ?? 'Câu Hỏi Thường Gặp';
$title = $data['title'] ?? '';
$faqs  = $data['faqs'] ?? [];
?>
<?php if ( ! empty( $faqs ) ) : ?>
<section class="contact-faq">
	<div class="container">
		<div class="section-title reveal">
			<div class="section-title__label">
				<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
				<?php echo esc_html( $badge ); ?>
			</div>
			<?php if ( $title ) : ?>
				<h2 class="section-title__heading"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>
			<div class="section-title__line"></div>
		</div>

		<div class="faq-list">
			<?php foreach ( $faqs as $index => $faq ) : ?>
				<details class="faq-item reveal" <?php echo 0 === $index ? 'open' : ''; ?>>
					<summary>
						<span><?php echo esc_html( $faq['question'] ?? '' ); ?></span>
						<svg class="icon faq-icon" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
					</summary>
					<p><?php echo esc_html( $faq['answer'] ?? '' ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>
