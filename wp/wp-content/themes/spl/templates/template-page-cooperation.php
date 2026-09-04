<?php
/**
 * Template Name: Cơ Hội Hợp Tác
 *
 * Cooperation page template — lists partnership info, packages, and signup form.
 * Matches htmlmau/hop-tac.html layout.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="partner-content" class="reveal">
	<?php
	$page_id  = get_queried_object_id() ?: get_the_ID();
	$sections = Helper::getField( 'cooperation_sections', $page_id );

	if ( ! empty( $sections ) && is_array( $sections ) ) :
		foreach ( $sections as $section ) :
			if ( ! empty( $section['disable'] ) ) :
				continue;
			endif;

			$layout = $section['acf_fc_layout'] ?? '';

			switch ( $layout ) :
				case 'cooperation_hero':
					get_template_part( 'parts/cooperation/hero', null, $section );
					break;
				case 'cooperation_benefits':
					get_template_part( 'parts/cooperation/benefits', null, $section );
					break;
				case 'cooperation_packages':
					get_template_part( 'parts/cooperation/packages', null, $section );
					break;
				case 'cooperation_process':
					get_template_part( 'parts/cooperation/process', null, $section );
					break;
				case 'cooperation_form':
					get_template_part( 'parts/cooperation/register-form', null, $section );
					break;
			endswitch;
		endforeach;
	else :
		// Fallbacks when ACF not configured.
		get_template_part( 'parts/cooperation/hero' );
		get_template_part( 'parts/cooperation/benefits' );
		get_template_part( 'parts/cooperation/packages' );
		get_template_part( 'parts/cooperation/process' );
		get_template_part( 'parts/cooperation/register-form' );
	endif;
	?>
</main>

<?php
get_footer();
