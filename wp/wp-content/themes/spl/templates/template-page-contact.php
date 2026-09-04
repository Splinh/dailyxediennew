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

<?php
$page_id  = get_queried_object_id() ?: get_the_ID();
$sections = Helper::getField( 'contact_sections', $page_id );

if ( ! empty( $sections ) && is_array( $sections ) ) :
	foreach ( $sections as $section ) :
		// Skip disabled sections.
		if ( ! empty( $section['disable'] ) ) :
			continue;
		endif;

		$layout = $section['acf_fc_layout'] ?? '';

		switch ( $layout ) :
			case 'contact_hero':
				get_template_part( 'parts/contact/hero', null, $section );
				break;
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
	while ( have_posts() ) :
		the_post();
		if ( get_the_content() ) :
			?>
			<div class="container py-8">
				<div class="prose max-w-none">
					<?php the_content(); ?>
				</div>
			</div>
			<?php
		endif;
	endwhile;
endif;

get_footer();
