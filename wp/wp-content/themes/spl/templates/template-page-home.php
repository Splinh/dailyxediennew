<?php
/**
 * Template Name: Trang Chủ
 *
 * Home page template with ACF flexible content.
 * Renders sections from the htmlmau mockup.
 *
 * @package SPL
 */

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

get_header();

$home_id  = (int) get_option( 'page_on_front' );
$page_id  = get_queried_object_id() ?: ( $home_id ?: get_the_ID() );
$sections = Helper::getField( 'home_sections', $page_id );

if ( ! empty( $sections ) && is_array( $sections ) ) :
	foreach ( $sections as $section ) :
		// Skip disabled sections.
		if ( ! empty( $section['disable'] ) ) :
			continue;
		endif;

		$layout = $section['acf_fc_layout'] ?? '';

		switch ( $layout ) :
			case 'hero_slider':
				get_template_part( 'parts/home/hero-slider', null, $section );
				break;

			case 'usp_bar':
				get_template_part( 'parts/home/usp-bar', null, $section );
				break;

			case 'categories':
				get_template_part( 'parts/home/categories', null, $section );
				break;

			case 'best_sellers':
				get_template_part( 'parts/home/best-sellers', null, $section );
				break;

			case 'tech_spotlight':
				get_template_part( 'parts/home/tech-spotlight', null, $section );
				break;

			case 'promo_banners':
				get_template_part( 'parts/home/promo-banners', null, $section );
				break;

			case 'media_reviews':
				get_template_part( 'parts/home/media-reviews', null, $section );
				break;

			case 'portfolio_gallery':
				get_template_part( 'parts/home/portfolio-gallery', null, $section );
				break;

			case 'store_locator':
				get_template_part( 'parts/home/store-locator', null, $section );
				break;

			case 'brands':
				get_template_part( 'parts/home/brands', null, $section );
				break;

			case 'news':
				get_template_part( 'parts/home/news', null, $section );
				break;

			case 'consult_form':
				get_template_part( 'parts/home/consult-form', null, $section );
				break;
		endswitch;
	endforeach;
else :
	// When no ACF sections are configured, render default page content if available.
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
