<?php
/**
 * The template for displaying archive post.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'archive' );

$object = get_queried_object();
//...

// footer
get_footer( 'archive' );
