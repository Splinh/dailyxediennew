<?php
/**
 * The template for displaying `homepage`
 * Template Name: Trang chủ
 * Template Post Type: page
 *
 * @package HD
 * @author  HD
 */

defined( 'ABSPATH' ) || die;

get_header( 'home' );

if ( have_posts() ) {
	the_post();
}

// ── Hero Slider ───────────────────────────────────────────────────────────────
get_template_part( 'template-parts/starter/hero-slider' );

// ── Feature Cards ─────────────────────────────────────────────────────────────
get_template_part( 'template-parts/starter/feature-cards' );

// ── News Slider ───────────────────────────────────────────────────────────────
get_template_part( 'template-parts/starter/news-slider' );

get_footer( 'home' );
