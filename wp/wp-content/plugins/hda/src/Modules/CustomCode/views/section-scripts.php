<?php
/**
 * Custom Script module options panel.
 *
 * @package HDAddons\Modules\CustomCode\CustomScript
 */

use HDAddons\Modules\CustomCode\CustomScript;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$html_header      = Helper::getStoredOptionContent( CustomScript::KEY_HEADER );
$html_footer      = Helper::getStoredOptionContent( CustomScript::KEY_FOOTER );
$html_body_top    = Helper::getStoredOptionContent( CustomScript::KEY_BODY_TOP );
$html_body_bottom = Helper::getStoredOptionContent( CustomScript::KEY_BODY_BOTTOM );

?>
<fieldset class="container-fieldset">
	<legend class="section-legend"><?php esc_html_e( 'Custom Scripts', 'hda' ); ?></legend>

	<div class="hda-notice hda-notice--info">
		<p>
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Add custom HTML, JS, or tracking codes (GA, Facebook Pixel, etc.) to specific locations.', 'hda' ); ?>
		</p>
		<p class="hda-notice__detail">
			<?php esc_html_e( 'Output on all pages. Wrap JS in <script> tags.', 'hda' ); ?>
		</p>
	</div>

	<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
		<div class="section section-textarea">
			<label class="heading flex items-center" for="html_header">
				<?php esc_html_e( 'Header scripts', 'hda' ); ?>
				<span class="hda-tip">
					<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
					<span class="hda-tip__body"><?php echo wp_kses_post( __( 'Injected inside <code>&lt;head&gt;</code> via <code>wp_head</code>. Ideal for meta tags, analytics, and early-loading scripts.', 'hda' ) ); ?></span>
				</span>
			</label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_html" name="hda_custom_code[html_header]" id="html_header" rows="4"><?php echo esc_textarea( $html_header ); ?></textarea>
				</div>
			</div>
		</div>
		<div class="section section-textarea">
			<label class="heading flex items-center" for="html_footer">
				<?php esc_html_e( 'Footer scripts', 'hda' ); ?>
				<span class="hda-tip">
					<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
					<span class="hda-tip__body"><?php echo wp_kses_post( __( 'Injected before <code>&lt;/body&gt;</code> via <code>wp_footer</code>. Best for non-critical scripts.', 'hda' ) ); ?></span>
				</span>
			</label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_html" name="hda_custom_code[html_footer]" id="html_footer" rows="4"><?php echo esc_textarea( $html_footer ); ?></textarea>
				</div>
			</div>
		</div>
		<div class="section section-textarea">
			<label class="heading flex items-center" for="html_body_top">
				<?php esc_html_e( 'Body scripts - TOP', 'hda' ); ?>
				<span class="hda-tip">
					<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
					<span class="hda-tip__body"><?php echo wp_kses_post( __( 'Injected right after <code>&lt;body&gt;</code> opens via <code>wp_body_open</code>. Used by GTM and similar.', 'hda' ) ); ?></span>
				</span>
			</label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_html" name="hda_custom_code[html_body_top]" id="html_body_top" rows="4"><?php echo esc_textarea( $html_body_top ); ?></textarea>
				</div>
			</div>
		</div>
		<div class="section section-textarea">
			<label class="heading flex items-center" for="html_body_bottom">
				<?php esc_html_e( 'Body scripts - BOTTOM', 'hda' ); ?>
				<span class="hda-tip">
					<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
					<span class="hda-tip__body"><?php echo wp_kses_post( __( 'Before <code>&lt;/body&gt;</code>, after <code>wp_footer</code>. Last position in document.', 'hda' ) ); ?></span>
				</span>
			</label>
			<div class="option">
				<div class="controls">
					<textarea class="textarea codemirror_html" name="hda_custom_code[html_body_bottom]" id="html_body_bottom" rows="4"><?php echo esc_textarea( $html_body_bottom ); ?></textarea>
				</div>
			</div>
		</div>
	</div>
</fieldset>
