<?php
/**
 * Custom CSS module options panel.
 *
 * @package HDAddons\Modules\CustomCode\CustomCss
 */

use HDAddons\Modules\CustomCode\CustomCss;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$css = Helper::getStoredOptionContent( CustomCss::OPTION_NAME );

?>
<fieldset class="container-fieldset">
	<legend class="section-legend"><?php esc_html_e( 'Custom CSS', 'hda' ); ?></legend>

	<div class="hda-notice hda-notice--info">
		<p>
			<span class="dashicons dashicons-admin-appearance"></span>
			<?php esc_html_e( 'Custom CSS to override theme styles. Persists through updates, loaded last.', 'hda' ); ?>
		</p>
		<p class="hda-notice__detail">
			<?php esc_html_e( 'No <style> tags needed. Auto-minified in production.', 'hda' ); ?>
		</p>
	</div>

	<div class="container grid grid-cols-1 gap-3 md:gap-6">
		<div class="section section-textarea">
			<label class="heading flex items-center" for="html_custom_css">
				<?php esc_html_e( 'Custom CSS', 'hda' ); ?>
				<span class="hda-tip">
					<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
					<span class="hda-tip__body"><?php echo wp_kses_post( __( 'Inline <code>&lt;style&gt;</code> via <code>wp_add_inline_style</code> — loaded after all stylesheets.', 'hda' ) ); ?></span>
				</span>
			</label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_css" name="hda_custom_code[html_custom_css]" id="html_custom_css" rows="8"><?php echo esc_textarea( $css ); ?></textarea>
				</div>
			</div>
		</div>
	</div>
</fieldset>
