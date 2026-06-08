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

// ACF Flexible Content field name.
$sections = Helper::getField( 'home_sections' );

if ( $sections ) :
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

			case 'event_gallery':
				get_template_part( 'parts/home/event-gallery', null, $section );
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
	// Fallback: render basic mock sections in correct order when ACF is not configured.
	get_template_part( 'parts/home/hero-slider' );
	get_template_part( 'parts/home/usp-bar' );
	get_template_part( 'parts/home/categories' );
	get_template_part( 'parts/home/best-sellers' );
	get_template_part( 'parts/home/tech-spotlight' );
	get_template_part( 'parts/home/promo-banners' );
	get_template_part( 'parts/home/media-reviews' );
	get_template_part( 'parts/home/event-gallery' );
	get_template_part( 'parts/home/store-locator' );
	get_template_part( 'parts/home/brands' );
	get_template_part( 'parts/home/news' );
	get_template_part( 'parts/home/consult-form' );
endif;

get_footer();
