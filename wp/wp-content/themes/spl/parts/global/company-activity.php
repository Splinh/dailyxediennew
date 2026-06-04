<?php
/**
 * Sitewide — Company Activity image gallery.
 *
 * Rendered above the footer on every page. Photos come from the ACF
 * options gallery field `activity_gallery`. Hidden when empty.
 * Clicking a photo opens the sitewide activity lightbox.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

$gallery = Helper::getField( 'activity_gallery', 'option' );

if ( empty( $gallery ) || ! is_array( $gallery ) ) {
	return;
}

$title    = Helper::getField( 'activity_title', 'option' ) ?: __( 'Hình Ảnh Hoạt Động Công Ty', 'spl' );
$subtitle = Helper::getField( 'activity_subtitle', 'option' );

?>
<section class="section-compact company-activity">
	<div class="container">
		<div class="section-title reveal">
			<div class="section-title__label">
				<svg class="icon" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
				<?php echo esc_html( $subtitle ?: __( 'Lạc Huy', 'spl' ) ); ?>
			</div>
			<h2 class="section-title__heading">
				<svg class="section-title__icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
				<?php echo esc_html( $title ); ?>
			</h2>
			<div class="section-title__line"></div>
		</div>

		<div class="activity-grid reveal" data-activity-lightbox>
			<?php
			foreach ( $gallery as $image ) :
				if ( empty( $image['url'] ) ) {
					continue;
				}

				$full  = $image['url'];
				$thumb = $image['sizes']['large'] ?? $image['sizes']['medium_large'] ?? $image['sizes']['medium'] ?? $full;
				$alt   = $image['alt'] ?? ( $image['title'] ?? '' );
				$w     = absint( $image['width'] ?? 0 );
				$h     = absint( $image['height'] ?? 0 );
				$cap   = $image['caption'] ?? $alt;
				?>
				<a href="<?php echo esc_url( $full ); ?>"
					class="activity-card"
					<?php if ( $w && $h ) : ?>data-pswp-width="<?php echo esc_attr( $w ); ?>" data-pswp-height="<?php echo esc_attr( $h ); ?>"<?php endif; ?>
					data-caption="<?php echo esc_attr( $cap ); ?>">
					<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" />
				</a>
				<?php
			endforeach;
			?>
		</div>
	</div>
</section>
