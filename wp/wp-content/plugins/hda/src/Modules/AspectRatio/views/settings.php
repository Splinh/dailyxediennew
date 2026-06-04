<?php
/**
 * Aspect Ratio Settings Options
 *
 * @package HDAddons\Modules\AspectRatio
 */

use HDAddons\Modules\AspectRatio\AspectRatioModule;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$aspect_ratio_settings = Helper::filterSettingOptions( AspectRatioModule::SETTINGS_FILTER );
$aspect_ratio_options  = Helper::getOption( AspectRatioModule::optionKey(), [] );
$post_type_terms       = $aspect_ratio_settings[ AspectRatioModule::SETTING_POST_TYPE_TERM ] ?? [];

?>
<div class="container mt-8">
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Aspect Ratio Settings', 'hda' ); ?></legend>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
			<?php
			if ( empty( $post_type_terms ) ) {
				printf(
					'<div class="col-span-full"><h3>%s</h3></div>',
					esc_html__( 'No data available or configuration not set.', 'hda' )
				);
			} else {
				foreach ( $post_type_terms as $postType ) :
					if ( empty( $postType ) ) {
						continue;
					}

					$width  = $aspect_ratio_options[ "as-{$postType}-width" ] ?? '';
					$height = $aspect_ratio_options[ "as-{$postType}-height" ] ?? '';

					// Get label from post type or taxonomy with null safety
					$postTypeObj = get_post_type_object( $postType );
					$taxonomyObj = get_taxonomy( $postType );

					$title = $postType; // Default fallback
					if ( $postTypeObj instanceof WP_Post_Type && ! empty( $postTypeObj->labels->singular_name ) ) {
						$title = $postTypeObj->labels->singular_name;
					} elseif ( $taxonomyObj instanceof WP_Taxonomy && ! empty( $taxonomyObj->labels->singular_name ) ) {
						$title = $taxonomyObj->labels->singular_name;
					}

					?>
					<div class="section section-text">
						<span class="heading flex items-center">
							<?php echo esc_html( $title ) . ' ( ' . esc_html( $postType ) . ' )'; ?>
							<span class="hda-tip">
								<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
								<span class="hda-tip__body">
									<?php
									printf(
									/* translators: %s: Post type name */
										esc_html__( 'Fixed aspect ratio for %s featured images. Empty = default.', 'hda' ),
										esc_html( ucfirst( $postType ) )
									);
									?>
								</span>
							</span>
						</span>
						<div class="option inline-option">
							<div class="controls">
								<div class="inline-group">
									<label>
										<span><?php esc_html_e( 'Width:', 'hda' ); ?></span>
										<input
											class="input"
											name="<?php echo esc_attr( AspectRatioModule::optionKey() ); ?>[<?php echo esc_attr( $postType ); ?>-width]"
											type="number"
											inputmode="numeric"
											size="3"
											min="0"
											max="100"
											value="<?php echo esc_attr( $width ); ?>">
									</label>
									<span>x</span>
									<label>
										<span><?php esc_html_e( 'Height:', 'hda' ); ?></span>
										<input
											class="input"
											name="<?php echo esc_attr( AspectRatioModule::optionKey() ); ?>[<?php echo esc_attr( $postType ); ?>-height]"
											type="number"
											inputmode="numeric"
											size="3"
											min="0"
											max="100"
											value="<?php echo esc_attr( $height ); ?>">
									</label>
								</div>
							</div>
						</div>
					</div>
					<?php
				endforeach;
			}
			?>
		</div>
	</fieldset>
</div>
