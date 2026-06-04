<?php
/**
 * Template Name: Trang Chủ
 *
 * Home page template with ACF flexible content.
 * Renders sections: hero, features, flash_sale, categories, products, about, blog.
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
			case 'hero':
				get_template_part( 'parts/home/hero', null, $section );
				break;

			case 'features':
				get_template_part( 'parts/home/features', null, $section );
				break;

			case 'flash_sale':
				get_template_part( 'parts/home/flash-sale', null, $section );
				break;

			case 'categories':
				get_template_part( 'parts/home/categories', null, $section );
				break;

			case 'products':
				get_template_part( 'parts/home/products', null, $section );
				break;

			case 'about':
				get_template_part( 'parts/home/about', null, $section );
				break;

			case 'blog':
				get_template_part( 'parts/home/blog', null, $section );
				break;
		endswitch;
	endforeach;

else :
	// Fallback: render all sections with default content when ACF is not configured.
	get_template_part( 'parts/home/hero' );
	get_template_part( 'parts/home/features' );
	get_template_part( 'parts/home/flash-sale' );
	get_template_part( 'parts/home/categories' );
	get_template_part( 'parts/home/products' );
	get_template_part( 'parts/home/about' );
	get_template_part( 'parts/home/blog' );
endif;

get_footer();
