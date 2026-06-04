<?php
/**
 * The home page template file.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @package HD
 * @author  HD
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'blog' );

$object = get_queried_object();

/**/

// footer
get_footer( 'blog' );
