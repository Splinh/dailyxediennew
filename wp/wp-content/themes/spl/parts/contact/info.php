<?php
/**
 * Contact — Info cards section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data  = $args ?? [];
$cards = $data['cards'] ?? [];

$fallback_icons = [
	'<svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
	'<svg class="icon" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
	'<svg class="icon" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
	'<svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
];
?>
<?php if ( ! empty( $cards ) ) : ?>
<section class="contact-info">
	<div class="container">
		<div class="contact-info__grid">
			<?php foreach ( $cards as $index => $card ) : ?>
				<div class="contact-card reveal">
					<div class="contact-card__icon">
						<?php
						$icon = trim( (string) ( $card['icon'] ?? '' ) );
						$icon = $icon ?: $fallback_icons[ $index % count( $fallback_icons ) ];
						echo wp_kses( $icon, Helper::ksesSVG() );
						?>
					</div>
					<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
					<p><?php echo wp_kses_post( $card['value'] ?? '' ); ?></p>
					<?php if ( ! empty( $card['note'] ) ) : ?>
						<span><?php echo esc_html( $card['note'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>
