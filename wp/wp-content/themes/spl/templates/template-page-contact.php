<?php
/**
 * Template Name: Liên Hệ
 *
 * Contact page template with ACF flexible content.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();
?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
	<div class="container">
		<nav class="breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<svg class="icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
				<?php esc_html_e( 'Trang chủ', 'spl' ); ?>
			</a>
			<svg class="icon breadcrumb__sep" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
			<span class="breadcrumb__current"><?php the_title(); ?></span>
		</nav>
	</div>
</div>

<?php
$sections = Helper::getField( 'contact_sections' );

if ( $sections ) :
	foreach ( $sections as $section ) :
		// Skip disabled sections.
		if ( ! empty( $section['disable'] ) ) :
			continue;
		endif;

		$layout = $section['acf_fc_layout'] ?? '';

		switch ( $layout ) :
			case 'contact_info':
				get_template_part( 'parts/contact/info', null, $section );
				break;
			case 'contact_form':
				get_template_part( 'parts/contact/form', null, $section );
				break;
			case 'contact_faq':
				get_template_part( 'parts/contact/faq', null, $section );
				break;
		endswitch;
	endforeach;

else :
	// Fallback when ACF not configured.
	get_template_part( 'parts/contact/info' );
	get_template_part( 'parts/contact/form' );
endif;

get_footer();
