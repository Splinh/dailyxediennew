<?php
/**
 * The Template for displaying all pages.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'page' );

if ( have_posts() ) {
	the_post();
}

the_content();

// footer
get_footer( 'page' );
