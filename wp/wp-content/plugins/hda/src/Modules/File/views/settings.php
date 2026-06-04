<?php
/**
 * File module options panel.
 *
 * @package HDAddons\Modules\File
 */

use HDAddons\Modules\File\FileModule;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$upload_max_filesize    = ( ini_get( 'upload_max_filesize' ) !== false ) ? ini_get( 'upload_max_filesize' ) : '2M';
$upload_max_filesize_MB = Helper::convertToMB( $upload_max_filesize );
$file_options           = Helper::getOption( FileModule::optionKey(), [] );
$upload_size_limit      = $file_options[ FileModule::KEY_UPLOAD_SIZE_LIMIT ] ?? '';
$svgs                   = $file_options[ FileModule::KEY_SVGS ] ?? 'disable';
$svg_options            = [
	'disable'      => esc_html__( 'Disable SVG images', 'hda' ),
	'sanitized'    => esc_html__( 'Sanitized SVG images', 'hda' ),
	'unrestricted' => esc_html__( 'Unrestricted SVG images', 'hda' ),
];

?>
<div class="container mt-8">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FILE UPLOAD & SVG -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'File Upload & SVG', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-media-default"></span>
				<?php esc_html_e( 'Upload limits and SVG support. Server limits may override these settings.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 gap-3 md:gap-6">
			<div class="section section-text">
				<label class="heading flex items-center" for="upload_size_limit">
					<?php esc_html_e( 'Maximum upload file size', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php echo wp_kses_post( sprintf( __( 'Override upload limit (MB). Server max: <strong>%s MB</strong>', 'hda' ), esc_html( $upload_max_filesize_MB ) ) ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $upload_size_limit ); ?>" class="input" type="number" min="0" step="1" id="upload_size_limit" name="hda_file[upload_size_limit]">
					</div>
				</div>
			</div>
			<div class="section section-radio">
				<span class="heading"><?php esc_html_e( 'SVG Images', 'hda' ); ?></span>
				<div class="option inline-option">
					<div class="controls">
						<div class="inline-group">
							<?php foreach ( $svg_options as $key => $opt ) : ?>
							<label>
								<input type="radio" name="hda_file[svgs]" class="radio" id="svgs-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $svgs, $key ); ?> />
								<span><?php echo esc_html( $opt ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<div class="hda-notice hda-notice--warning">
					<p>
						<span class="dashicons dashicons-warning"></span>
						<strong><?php esc_html_e( 'Security:', 'hda' ); ?></strong>
						<?php esc_html_e( 'SVGs can contain XSS. Use "Sanitized" for safe uploads, or "Unrestricted" only with trusted uploaders.', 'hda' ); ?>
					</p>
				</div>
			</div>
		</div>
	</fieldset>

	<?php
	// ── Sub-module: File Integrity Scanner ──
	require __DIR__ . '/../FileIntegrity/views/settings.php';
	?>
</div>
