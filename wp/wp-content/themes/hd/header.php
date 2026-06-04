<?php
/**
 * The template for displaying the header.
 * This is the template that displays all the <head> section, opens the <body> tag and adds the site's header.
 *
 * @package HD
 * @author  HD
 */

use HD\Core\Helper;

\defined( 'ABSPATH' ) || die;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>"/>
		<?php

		/**
		 * HOOK: wp_head
		 *
		 * @see hd_wp_head_base() - 1
		 * @see hd_wp_head_other() - 97
		 * @see CustomScript::headerScripts() - 99
		 */
		wp_head();

		?>
	</head>
<body <?php body_class(); ?> <?php echo Helper::microdata( 'body' ); ?>>
<?php

/**
 * HOOK: wp_body_open
 *
 * @see CustomScript::bodyScriptsTop() - 99
 */
do_action( 'wp_body_open' );

/**
 * HOOK: hd_header_before_action
 */
do_action( 'hd_header_before_action' );

?>
<div class="relative site-wrapper">
	<?php

	/**
	 * HOOK: hd_top_header_action
	 */
	do_action( 'hd_top_header_action' );

	?>
	<header data-fx-sticky id="header" class="<?php echo esc_attr( apply_filters( 'hd_header_class_filter', 'site-header' ) ); ?>" <?php echo Helper::microdata( 'header' ); ?>>
		<?php

		/**
		 * HOOK: hd_header_action
		 *
		 * @see hd_construct_header() - 10
		 */
		do_action( 'hd_header_action' );

		?>
	</header><!-- #header -->
	<?php

	/**
	 * HOOK: hd_header_after_action
	 */
	do_action( 'hd_header_after_action' );

	?>
	<main class="main site-content" id="site-content">
		<?php

		/**
		 * HOOK: hd_site_content_before_action
		 */
		do_action( 'hd_site_content_before_action' );
