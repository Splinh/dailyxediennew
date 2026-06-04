<?php
/**
 * About — Values section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data   = $args ?? [];
$badge  = $data['badge'] ?? 'Giá Trị Cốt Lõi';
$title  = $data['title'] ?? '';
$values = $data['values'] ?? [];

$fallback_icons = [
	'<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>',
	'<svg class="icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
	'<svg class="icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
	'<svg class="icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
];
?>
<section class="about-values">
	<div class="container">
		<div class="section-title reveal">
			<div class="section-title__label">
				<svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
				<?php echo esc_html( $badge ); ?>
			</div>
			<?php if ( $title ) : ?>
				<h2 class="section-title__heading"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>
			<div class="section-title__line"></div>
		</div>

		<?php if ( ! empty( $values ) ) : ?>
			<div class="about-values__grid">
				<?php foreach ( $values as $index => $item ) : ?>
					<div class="about-value-card reveal">
						<div class="about-value-card__icon">
							<?php
							$icon = trim( (string) ( $item['icon'] ?? '' ) );
							$icon = $icon ?: $fallback_icons[ $index % count( $fallback_icons ) ];
							echo wp_kses( $icon, Helper::ksesSVG() );
							?>
						</div>
						<h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $item['desc'] ?? '' ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
