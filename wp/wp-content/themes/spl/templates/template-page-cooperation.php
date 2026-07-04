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

// Breadcrumb
?>
<div class="breadcrumb-bar bg-white border-b border-slate-100">
	<div class="max-w-7xl mx-auto px-4 py-3">
		<nav class="breadcrumb flex items-center gap-2 text-xs text-slate-400" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-primary transition-colors"><?php esc_html_e( 'Trang chủ', 'spl' ); ?></a>
			<svg class="icon w-2 h-2" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
			<span class="text-slate-700 font-semibold"><?php the_title(); ?></span>
		</nav>
	</div>
</div>

<main id="partner-content" class="reveal">
	<?php
	$sections = Helper::getField( 'cooperation_sections' );

	if ( $sections ) :
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
