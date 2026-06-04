<?php
/**
 * Displays navigation mobile
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

$txt_logo = \SPL_Helper::getOption( 'blogname' );
$img_logo = \SPL_Helper::getThemeMod( 'custom_logo' );

if ( ! $img_logo ) :
	$html = sprintf(
		'<a href="%1$s" class="mobile-logo-link" rel="home" aria-label="%2$s">%3$s</a>',
		\SPL_Helper::home(),
		\SPL_Helper::escAttr( $txt_logo ),
		$txt_logo
	);
else :
	$image = \SPL_Helper::iconImageHTML( $img_logo, 'medium', [ 'loading' => 'eager' ] );
	$html  = sprintf(
		'<a href="%1$s" class="mobile-logo-link" rel="home" aria-label="%2$s">%3$s</a>',
		\SPL_Helper::home(),
		\SPL_Helper::escAttr( $txt_logo ),
		$image
	);
endif;

$position = \SPL_Helper::getThemeMod( 'offcanvas_menu_setting' );
if ( ! in_array( $position, [ 'left', 'right', 'top', 'bottom' ], true ) ) {
	$position = 'left';
}

?>
<aside class="off-canvas invisible will-change-transform backface-hidden fixed bg-(--bg-color) is-transition-overlap position-<?php echo $position; ?>" id="offCanvasMenu" data-fx-off-canvas data-content-scroll="true" role="complementary" aria-label="<?php echo esc_attr__( 'Menu di động', 'spl' ); ?>">
	<div class="menu-heading-outer">
		<button class="menu-lines absolute top-4 right-4 block opacity-0 p-0 w-6 h-6" aria-label="<?php echo esc_attr__( 'Đóng menu', 'spl' ); ?>" type="button" data-close>
			<span class="line line-1 block w-6 h-0.5 rounded-none"></span>
			<span class="line line-2 block w-6 h-0.5 rounded-none -mt-0.5"></span>
		</button>
		<div class="title-bar-title relative my-5 mx-4 w-42.5 max-w-[70%]"><?php echo $html; ?></div>
	</div>
	<div class="menu-outer">
		<?php
		echo \SPL_Helper::doShortcode( 'inline_search', [ 'class' => 'p-4' ] );
		echo \SPL_Helper::doShortcode( 'vertical_menu', [ 'extra_class' => 'relative h-full overflow-hidden p-5 gap-5 flex flex-col flex-nowrap' ] );
		?>
	</div>
</aside>
