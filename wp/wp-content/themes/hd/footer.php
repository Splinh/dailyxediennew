<?php
/**
 * The template for displaying the footer.
 * Contains the body & HTML closing tags.
 *
 * @package HD
 * @author  HD
 */

use HD\Core\Helper;

\defined( 'ABSPATH' ) || die;

/**
 * HOOK: hd_site_content_after_action
 */
do_action( 'hd_site_content_after_action' );

?>
</main><!-- #site-content -->
<?php

/**
 * HOOK: hd_footer_before_action
 */
do_action( 'hd_footer_before_action' );

?>
<footer id="footer" class="<?php echo esc_attr( apply_filters( 'hd_footer_class_filter', 'site-footer' ) ); ?>" <?php echo Helper::microdata( 'footer' ); ?>>
	<?php

	/**
	 * HOOK: hd_footer_action
	 *
	 * @see hd_construct_footer() - 10
	 */
	do_action( 'hd_footer_action' );

	?>
</footer><!-- #footer -->
</div><!-- .site-wrapper -->
<?php

/**
 * HOOK: hd_footer_after_action
 */
do_action( 'hd_footer_after_action' );

/**
 * HOOK: wp_footer
 *
 * @see ContactLink::addThisContactLink() - 30
 * @see CustomScript::footerScripts() - 99
 * @see CustomScript::bodyScriptsBottom() - PHP_INT_MAX
 */
wp_footer();

?>
</body>
</html>
