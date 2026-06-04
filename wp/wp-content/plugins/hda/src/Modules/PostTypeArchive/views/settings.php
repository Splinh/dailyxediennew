<?php
/**
 * Post Type Archive module options panel.
 *
 * @package HDAddons\Modules\PostTypeArchive
 */

use HDAddons\Helper;
use HDAddons\Modules\PostTypeArchive\PostTypeArchiveModule;

\defined( 'ABSPATH' ) || exit;

$options       = Helper::getOption( PostTypeArchiveModule::optionKey(), [] );
$savedPages    = $options[ PostTypeArchiveModule::KEY_PTA_PAGES ] ?? [];
$eligibleTypes = PostTypeArchiveModule::getEligiblePostTypes();

?>
<div class="container">

	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Archive Page Assignment', 'hda' ); ?></legend>

		<?php if ( empty( $eligibleTypes ) ) : ?>
			<div class="hda-notice hda-notice--warning">
				<p>
					<span class="dashicons dashicons-warning"></span>
					<?php esc_html_e( 'No eligible custom post types found. Types must be public without a built-in archive.', 'hda' ); ?>
				</p>
			</div>
		<?php else : ?>
			<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
				<?php foreach ( $eligibleTypes as $slug => $postType ) : ?>
					<div class="section section-select">
						<label class="heading flex items-center flex-wrap" for="pta_page_<?php echo esc_attr( $slug ); ?>">
							<span class="mr-1"><?php echo esc_html( $postType->labels->name ?? ucfirst( $slug ) ); ?></span>
							<code class="text-[11px] text-slate-400"><?php echo esc_html( $slug ); ?></code>
							<?php if ( ! empty( $postType->description ) ) : ?>
								<span class="hda-tip ml-auto md:ml-1">
									<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
									<span class="hda-tip__body"><?php echo esc_html( $postType->description ); ?></span>
								</span>
							<?php endif; ?>
						</label>
						<div class="option">
							<div class="controls">
								<div class="select_wrapper">
									<?php
									wp_dropdown_pages(
										[
											'name'        => 'hda_post_type_archive[pta_pages][' . esc_attr( $slug ) . ']',
											'id'          => 'pta_page_' . esc_attr( $slug ),
											'selected'    => absint( $savedPages[ $slug ] ?? 0 ),
											'show_option_none' => __( '— None —', 'hda' ),
											'option_none_value' => '0',
											'class'       => 'select',
											'post_status' => 'publish',
										]
									);
									?>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="hda-notice hda-notice--info mt-6">
				<p>
					<span class="dashicons dashicons-admin-links"></span>
					<?php echo wp_kses_post( __( 'The selected page becomes the base archive URL (similar to the built-in "Posts page"). Permalinks will automatically flush when changes are saved.', 'hda' ) ); ?>
				</p>
			</div>
		<?php endif; ?>
	</fieldset>
</div>
