<?php
/**
 * About — Mission section.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$data     = $args ?? [];
$missions = $data['missions'] ?? [];

$fallback_icons = [
	'<svg class="icon icon-xl" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
	'<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
	'<svg class="icon icon-xl" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
];
?>
<?php if ( ! empty( $missions ) ) : ?>
<section class="about-mission">
	<div class="container">
		<div class="about-mission__grid">
			<?php foreach ( $missions as $index => $item ) : ?>
				<div class="about-mission__card reveal">
					<div class="about-mission__icon">
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
	</div>
</section>
<?php endif; ?>
