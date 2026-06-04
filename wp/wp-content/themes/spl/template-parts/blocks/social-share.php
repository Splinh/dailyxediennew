<?php
/**
 * The block for sharing functionality.
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

?>
<div class="sharing-toolbox flex items-center space-x-4">
	<span class="share-title text-[14px] font-medium uppercase"><?php echo __( 'Chia sẻ:', 'spl' ); ?></span>
	<div class="social-share" data-fx-share data-layout="h" data-intents="facebook,x,print,send-email,copy-link,web-share"></div>
</div>
