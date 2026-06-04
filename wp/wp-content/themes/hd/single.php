<?php
/**
 * The Template for displaying all single posts.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @package HD
 * @author  HD
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'single' );

if ( have_posts() ) {
	the_post();
}

the_content();

// footer
get_footer( 'single' );
