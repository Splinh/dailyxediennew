<?php
/**
 * Editor module options panel.
 *
 * 2 toggles:
 * - "Use Classic Editor" (KEY_CLASSIC) → disables Block Editor, Widgets, CSS, Site Editor
 * - "Disable Block Editor Extras" (KEY_EXTRAS_OFF) → disables Font Library, Patterns, Openverse
 *
 * @package HDAddons\Modules\Editor
 */

use HDAddons\Modules\Editor\EditorModule;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$editor_options   = Helper::getOption( EditorModule::optionKey(), [] );
$classic_editor   = ! empty( $editor_options[ EditorModule::KEY_CLASSIC ] );
$block_extras_off = ! empty( $editor_options[ EditorModule::KEY_EXTRAS_OFF ] );

?>
<div class="container mt-8">


	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Block Editor Settings', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-editor-code"></span>
				<?php esc_html_e( 'Control Gutenberg features. Disabling unused features improves load speed.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">

			<!-- Toggle 1: Use Classic Editor -->
			<div class="section section-checkbox">
				<label class="heading flex items-center" for="<?php echo esc_attr( EditorModule::KEY_CLASSIC ); ?>">
					<?php esc_html_e( 'Use Classic Editor', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Disables Block Editor and Widgets Block Editor for all post types, removes block CSS/styles from frontend, and hides Site Editor menu.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label>
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( EditorModule::optionKey() ); ?>[<?php echo esc_attr( EditorModule::KEY_CLASSIC ); ?>]" id="<?php echo esc_attr( EditorModule::KEY_CLASSIC ); ?>" <?php checked( $classic_editor ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Switch to Classic Editor for all content', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<!-- Toggle 2: Disable Block Editor Extras (hidden when Classic Editor is on) -->
			<div class="section section-checkbox block-editor-dependent<?php echo $classic_editor ? ' hidden' : ''; ?>">
				<label class="heading flex items-center" for="<?php echo esc_attr( EditorModule::KEY_EXTRAS_OFF ); ?>">
					<?php esc_html_e( 'Disable Block Editor Extras', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Disables Font Library (WP 6.5+), blocks remote patterns from WordPress.org, and hides Openverse in media inserter.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label>
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( EditorModule::optionKey() ); ?>[<?php echo esc_attr( EditorModule::KEY_EXTRAS_OFF ); ?>]" id="<?php echo esc_attr( EditorModule::KEY_EXTRAS_OFF ); ?>" <?php checked( $block_extras_off ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Remove Font Library, Remote Patterns, and Openverse', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<!-- WooCommerce Checkboxes -->
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<?php
					$woo_block_styles = ! empty( $editor_options[ EditorModule::KEY_WOO_BLOCK_STYLES ] );
					$woo_all_styles   = ! empty( $editor_options[ EditorModule::KEY_WOO_ALL_STYLES ] );
				?>
				<div class="section section-checkbox">
					<label class="heading flex items-center" for="<?php echo esc_attr( EditorModule::KEY_WOO_BLOCK_STYLES ); ?>">
						<?php esc_html_e( 'Remove WooCommerce Block Styles', 'hda' ); ?>
						<span class="hda-tip">
							<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
							<span class="hda-tip__body"><?php esc_html_e( 'Removes all default CSS/style from the frontend related to WooCommerce blocks.', 'hda' ); ?></span>
						</span>
					</label>
					<div class="option">
						<div class="controls">
							<label>
								<input type="checkbox" class="checkbox" name="<?php echo esc_attr( EditorModule::optionKey() ); ?>[<?php echo esc_attr( EditorModule::KEY_WOO_BLOCK_STYLES ); ?>]" id="<?php echo esc_attr( EditorModule::KEY_WOO_BLOCK_STYLES ); ?>" <?php checked( $woo_block_styles ); ?> value="1">
								<span class="font-medium text-sm"><?php esc_html_e( 'Disable WooCommerce Block CSS on Frontend', 'hda' ); ?></span>
							</label>
						</div>
					</div>
				</div>

				<div class="section section-checkbox">
					<label class="heading flex items-center" for="<?php echo esc_attr( EditorModule::KEY_WOO_ALL_STYLES ); ?>">
						<?php esc_html_e( 'Remove ALL WooCommerce Styles', 'hda' ); ?>
						<span class="hda-tip">
							<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
							<span class="hda-tip__body"><?php esc_html_e( 'Removes ALL WooCommerce CSS (core and blocks). Only keeps JS. Use this when applying a completely custom CSS structure from your theme.', 'hda' ); ?></span>
						</span>
					</label>
					<div class="option">
						<div class="controls">
							<label>
								<input type="checkbox" class="checkbox" name="<?php echo esc_attr( EditorModule::optionKey() ); ?>[<?php echo esc_attr( EditorModule::KEY_WOO_ALL_STYLES ); ?>]" id="<?php echo esc_attr( EditorModule::KEY_WOO_ALL_STYLES ); ?>" <?php checked( $woo_all_styles ); ?> value="1">
								<span class="font-medium text-sm"><?php esc_html_e( 'Disable ALL WooCommerce styles, keep only JS', 'hda' ); ?></span>
							</label>
						</div>
					</div>
				</div>
			<?php endif; ?>

		</div>
	</fieldset>
</div>

<!-- Toggle visibility: hide "Extras" when Classic Editor is enabled -->
<script>
(function () {
	const classicToggle = document.getElementById('<?php echo esc_js( EditorModule::KEY_CLASSIC ); ?>');
	const extrasCell    = document.querySelector('.block-editor-dependent');
	if (!classicToggle || !extrasCell) return;

	classicToggle.addEventListener('change', function () {
		extrasCell.classList.toggle('hidden', this.checked);
	});
})();
</script>
