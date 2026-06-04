<?php
/**
 * Image Converter — Settings Tab Template.
 *
 * Included by GlobalSetting\options-content.php as a tab within the main settings page.
 * Uses the same layout pattern as other HDA tabs (container-fieldset, section-*, etc.).
 *
 * @package HDAddons\Modules\ImageConverter
 */

use HDAddons\Helper;
use HDAddons\Modules\ImageConverter\BatchProcessor;
use HDAddons\Modules\ImageConverter\CloudflareIntegration;
use HDAddons\Modules\ImageConverter\Converter;
use HDAddons\Modules\ImageConverter\ImageConverter;
use HDAddons\Modules\ImageConverter\ImageConverterModule;
use HDAddons\Modules\ImageConverter\ServerRules;

\defined( 'ABSPATH' ) || exit;

// ─── Initialize Variables ───────────────────────────
$options    = ImageConverter::getOptions();
$engineInfo = Converter::getEngineInfo();
$batchState = Helper::getOption( BatchProcessor::BATCH_OPTION, [] );
$format     = ImageConverter::getFormat();

$qualityJpg    = (int) ( $options[ ImageConverter::KEY_QUALITY_JPG ] ?? Converter::DEFAULT_QUALITY[ $format ]['jpg'] ?? 75 );
$qualityPng    = (int) ( $options[ ImageConverter::KEY_QUALITY_PNG ] ?? Converter::DEFAULT_QUALITY[ $format ]['png'] ?? 80 );
$isActiveBatch = ! empty( $batchState['batch_id'] );
$cfStatus      = []; // CF section hidden — skip API check
$rulesStatus   = ServerRules::getStatus();

// ─── Source directories ─────────────────────────────
$sourceDirs = BatchProcessor::getSourceDirectories( $format );
?>
<div class="container mt-8">

	<!-- ═══ Conversion Settings ═══ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Conversion Settings', 'hda' ); ?></legend>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-2 md:gap-4">
			<!-- Output Format -->
			<div class="section section-radio">
				<label class="heading"><?php esc_html_e( 'Output Format', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="inline-group">
							<label class="radio-label">
								<input type="radio"
										name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_FORMAT ); ?>]"
										value="none"
										<?php checked( $format, 'none' ); ?>>
								<span><?php esc_html_e( 'None', 'hda' ); ?></span>
							</label>
							<label class="radio-label">
								<input type="radio"
										name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_FORMAT ); ?>]"
										value="webp"
										<?php checked( $format, 'webp' ); ?>
										<?php disabled( ! $engineInfo['formats']['webp'] ); ?>>
								<span>WebP</span>
							</label>
							<label class="radio-label">
								<input type="radio"
										name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_FORMAT ); ?>]"
										value="avif"
										<?php checked( $format, 'avif' ); ?>
										<?php disabled( ! $engineInfo['formats']['avif'] ); ?>>
								<span>AVIF</span>
							</label>
						</div>
					</div>
				</div>
			</div>

			<!-- Auto-convert -->
			<div class="section section-checkbox">
				<label class="heading">
					<?php esc_html_e( 'Auto-convert on Upload', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Convert images uploaded via Media Library.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox"
									class="checkbox"
									name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_AUTO_CONVERT ); ?>]"
									value="1"
									<?php checked( ! empty( $options[ ImageConverter::KEY_AUTO_CONVERT ] ) ); ?>>
							<span class="font-medium text-sm"><?php esc_html_e( 'Enable automatic conversion on upload', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<!-- Quality: JPG -->
			<div class="section section-text">
				<label class="heading" for="imgconv_quality_jpg">
					<?php esc_html_e( 'Quality (JPG sources)', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Lower = smaller file, higher = closer to original.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls hda-imgconv-quality-row">
						<input type="range"
								id="imgconv_quality_jpg"
								name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_QUALITY_JPG ); ?>]"
								min="30" max="100" step="5"
								value="<?php echo esc_attr( $qualityJpg ); ?>">
						<input type="number"
								class="hda-imgconv-quality-input"
								id="imgconv_quality_jpg_num"
								min="30" max="100" step="5"
								value="<?php echo esc_attr( $qualityJpg ); ?>"
								aria-label="<?php esc_attr_e( 'Quality value', 'hda' ); ?>">
						<span class="hda-imgconv-quality-unit">%</span>
					</div>
				</div>
			</div>

			<!-- Quality: PNG -->
			<div class="section section-text">
				<label class="heading" for="imgconv_quality_png"><?php esc_html_e( 'Quality (PNG sources)', 'hda' ); ?></label>
				<div class="option">
					<div class="controls hda-imgconv-quality-row">
						<input type="range"
								id="imgconv_quality_png"
								name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_QUALITY_PNG ); ?>]"
								min="30" max="100" step="5"
								value="<?php echo esc_attr( $qualityPng ); ?>">
						<input type="number"
								class="hda-imgconv-quality-input"
								id="imgconv_quality_png_num"
								min="30" max="100" step="5"
								value="<?php echo esc_attr( $qualityPng ); ?>"
								aria-label="<?php esc_attr_e( 'Quality value', 'hda' ); ?>">
						<span class="hda-imgconv-quality-unit">%</span>
					</div>
				</div>
			</div>

			<!-- Exclude Keywords -->
			<div class="section section-text">
				<label class="heading" for="imgconv_exclude_keywords">
					<?php esc_html_e( 'Exclude Keywords', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Comma-separated. Images with filenames containing any keyword will be skipped.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<input type="text"
								id="imgconv_exclude_keywords"
								name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_EXCLUDE_KEYWORDS ); ?>]"
								class="input"
								style="max-width: 500px;"
								value="<?php echo esc_attr( $options[ ImageConverter::KEY_EXCLUDE_KEYWORDS ] ?? '' ); ?>"
								placeholder="logo, icon-, banner-hero">
					</div>
				</div>
			</div>
		</div>
	</fieldset>

	<?php
	/**
	 * Cloudflare Integration — hidden for now.
	 * Backend code (CloudflareIntegration.php) remains intact.
	 * Uncomment this block when CF features are needed on production.
	 */
	if ( false ) :
		?>
	<!-- ═══ Cloudflare Integration ═══ -->
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend">
			<?php esc_html_e( 'Cloudflare Integration', 'hda' ); ?>
			<?php if ( $cfStatus['is_cloudflare'] ) : ?>
				<span class="hda-badge hda-badge--success hda-badge--legend">☁️ <?php esc_html_e( 'Detected', 'hda' ); ?></span>
			<?php endif; ?>
		</legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-cloud"></span>
				<?php esc_html_e( 'Auto-purge Cloudflare cache after conversion so visitors see next-gen images immediately.', 'hda' ); ?>
			</p>
		</div>

		<?php if ( $cfStatus['has_credentials'] && $cfStatus['credentials_valid'] !== null ) : ?>
			<div class="hda-imgconv-cf-status hda-imgconv-cf-status--<?php echo esc_attr( $cfStatus['credentials_valid'] ? 'ok' : 'error' ); ?>">
				<?php if ( $cfStatus['credentials_valid'] ) : ?>
					✅ 
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %s: Cloudflare zone name */
							__( 'Connected to zone: %s', 'hda' ),
							'<strong>' . esc_html( $cfStatus['zone_name'] ?? '' ) . '</strong>'
						),
						[ 'strong' => [] ]
					);
					?>
				<?php else : ?>
					❌ <?php esc_html_e( 'Invalid credentials — check Zone ID and API Token.', 'hda' ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<!-- Zone ID -->
			<div class="section section-text">
				<label class="heading" for="imgconv_cf_zone_id">
					<?php esc_html_e( 'Zone ID', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Cloudflare Dashboard → Your site → Overview (right sidebar).', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<input type="text"
								id="imgconv_cf_zone_id"
								name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_CF_ZONE_ID ); ?>]"
								class="input"
								value="<?php echo esc_attr( $options[ ImageConverter::KEY_CF_ZONE_ID ] ?? '' ); ?>"
								placeholder="e.g. a1b2c3d4e5f6...">
					</div>
				</div>
			</div>

			<!-- API Token -->
			<div class="section section-text">
				<label class="heading" for="imgconv_cf_api_token"><?php esc_html_e( 'API Token', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input type="password"
								id="imgconv_cf_api_token"
								name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_CF_API_TOKEN ); ?>]"
								class="input"
								value="<?php echo esc_attr( $options[ ImageConverter::KEY_CF_API_TOKEN ] ?? '' ); ?>"
								placeholder="Bearer token">
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'How to get API Token?', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s: Cloudflare API Tokens link */
								__( 'Create at %s with "Zone.Cache Purge" permission.', 'hda' ),
								'<a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" rel="noopener">Cloudflare API Tokens</a>'
							),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						);
						?>
					</div>
				</details>
			</div>

			<!-- Auto-purge -->
			<div class="section section-checkbox">
				<label class="heading">
					<?php esc_html_e( 'Auto-purge Cache', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Purge cache when conversion completes. Batch: full purge. Upload: per-URL purge.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox"
									class="checkbox"
									name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_CF_AUTO_PURGE ); ?>]"
									value="1"
									<?php checked( ! empty( $options[ ImageConverter::KEY_CF_AUTO_PURGE ] ) ); ?>>
							<span class="font-medium text-sm"><?php esc_html_e( 'Purge cache automatically', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
	<?php endif; ?>

	<!-- ═══ Server Rewrite Rules ═══ -->
	<fieldset class="container-fieldset mt-6" id="hda-imgconv-server-rules">
		<legend class="section-legend">
			<?php esc_html_e( 'Server Rewrite Rules', 'hda' ); ?>
			<span class="hda-badge hda-badge--muted hda-badge--legend"><?php echo esc_html( $rulesStatus['server_label'] ); ?></span>
		</legend>

		<?php if ( $rulesStatus['supports_htaccess'] ) : ?>
			<div class="hda-notice hda-notice--info">
				<p>
					<span class="dashicons dashicons-admin-site-alt3"></span>
					<?php esc_html_e( 'Automatically serve converted images by adding rewrite rules to .htaccess in the uploads directory.', 'hda' ); ?>
				</p>
				<?php if ( $rulesStatus['is_active'] ) : ?>
					<p class="hda-notice__detail">
						✅ 
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s: .htaccess path */
								__( 'Rules active in: %s', 'hda' ),
								'<code>' . esc_html( $rulesStatus['htaccess_path'] ) . '</code>'
							),
							[ 'code' => [] ]
						);
						?>
					</p>
				<?php endif; ?>
			</div>

			<div class="container grid grid-cols-1 gap-2 md:gap-4">
				<div class="section section-checkbox">
					<label class="heading">
						<?php esc_html_e( 'Enable Rewrite Rules', 'hda' ); ?>
						<span class="hda-tip">
							<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
							<span class="hda-tip__body"><?php esc_html_e( 'Write .htaccess rewrite rules to serve AVIF/WebP images automatically. When a browser requests a JPEG/PNG, the server will serve the converted version if available.', 'hda' ); ?></span>
						</span>
					</label>
					<div class="option">
						<div class="controls">
							<label class="flex items-center gap-2 cursor-pointer">
								<input type="checkbox"
										class="checkbox"
										name="<?php echo esc_attr( ImageConverterModule::optionKey() ); ?>[<?php echo esc_attr( ImageConverter::KEY_SERVER_RULES ); ?>]"
										value="1"
										<?php checked( ! empty( $options[ ImageConverter::KEY_SERVER_RULES ] ) ); ?>>
								<span class="font-medium text-sm"><?php esc_html_e( 'Write rules to .htaccess', 'hda' ); ?></span>
							</label>
						</div>
					</div>
				</div>
			</div>

		<?php else : ?>

			<div class="hda-notice hda-notice--warning">
				<p>
					<span class="dashicons dashicons-warning"></span>
					<?php esc_html_e( 'Your server (Nginx) does not support .htaccess. Add the following to your Nginx config manually:', 'hda' ); ?>
				</p>
			</div>

			<?php if ( ! empty( $rulesStatus['nginx_snippet'] ) ) : ?>
				<pre class="hda-imgconv-code-snippet"><code><?php echo esc_html( $rulesStatus['nginx_snippet'] ); ?></code></pre>
			<?php endif; ?>

		<?php endif; ?>
	</fieldset>

	<!-- ═══ Engine Detection ═══ -->
	<fieldset class="container-fieldset mt-6" id="hda-imgconv-engine">
		<legend class="section-legend"><?php esc_html_e( 'Engine Detection', 'hda' ); ?></legend>

		<!-- Active engines summary at the top for quick scanning -->
		<div class="hda-imgconv-active-engines mb-4">
			<p>
				<strong><?php esc_html_e( 'Active WebP Engine:', 'hda' ); ?></strong>
				<?php if ( $engineInfo['active_engine_webp'] ) : ?>
					<span class="hda-badge hda-badge--success"><?php echo esc_html( $engineInfo['active_engine_webp'] ); ?></span>
				<?php else : ?>
					<span class="hda-badge hda-badge--muted"><?php esc_html_e( 'Not available', 'hda' ); ?></span>
				<?php endif; ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Active AVIF Engine:', 'hda' ); ?></strong>
				<?php if ( $engineInfo['active_engine_avif'] ) : ?>
					<span class="hda-badge hda-badge--success"><?php echo esc_html( $engineInfo['active_engine_avif'] ); ?></span>
				<?php else : ?>
					<span class="hda-badge hda-badge--muted"><?php esc_html_e( 'Not available', 'hda' ); ?></span>
				<?php endif; ?>
			</p>
		</div>

		<table class="hda-imgconv-engine-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Engine', 'hda' ); ?></th>
					<th class="text-center"><?php esc_html_e( 'Priority', 'hda' ); ?></th>
					<th class="text-center"><?php esc_html_e( 'Status', 'hda' ); ?></th>
					<th class="text-center">WebP</th>
					<th class="text-center">AVIF</th>
					<th><?php esc_html_e( 'Note', 'hda' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $engineInfo['engines'] as $engine ) : ?>
					<tr class="<?php echo esc_attr( $engine['available'] ? 'engine-available' : 'engine-unavailable' ); ?>">
						<td class="font-medium text-slate-700"><?php echo esc_html( $engine['label'] ); ?></td>
						<td class="text-center text-slate-500"><?php echo esc_html( $engine['priority'] ); ?></td>
						<td class="text-center">
							<?php if ( $engine['available'] ) : ?>
								<span class="hda-badge hda-badge--success flex items-center gap-1 justify-center mx-auto" style="width: fit-content;">
									<span class="dashicons dashicons-yes-alt text-[14px] leading-none mt-0.5"></span> Available
								</span>
							<?php else : ?>
								<span class="hda-badge hda-badge--muted flex items-center gap-1 justify-center mx-auto" style="width: fit-content;">
									<span class="dashicons dashicons-dismiss text-[14px] leading-none mt-0.5"></span> Unavailable
								</span>
							<?php endif; ?>
						</td>
						<td class="text-center">
							<?php if ( $engine['webp'] ) : ?>
								<span class="dashicons dashicons-saved text-green-600"></span>
							<?php else : ?>
								<span class="text-slate-300">—</span>
							<?php endif; ?>
						</td>
						<td class="text-center">
							<?php if ( $engine['avif'] ) : ?>
								<span class="dashicons dashicons-saved text-green-600"></span>
							<?php else : ?>
								<span class="text-slate-300">—</span>
							<?php endif; ?>
						</td>
						<td><span class="engine-note"><?php echo esc_html( $engine['note'] ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>

	<!-- ═══ Batch Conversion ═══ -->
	<fieldset class="container-fieldset mt-6" id="hda-imgconv-batch">
		<legend class="section-legend"><?php esc_html_e( 'Batch Conversion', 'hda' ); ?></legend>

		<div id="hda-imgconv-batch-form">

			<!-- Source Directories -->
			<div class="hda-imgconv-sources" id="hda-imgconv-sources">
				<h4 class="section-subtitle"><?php esc_html_e( 'Source Directories', 'hda' ); ?></h4>

				<?php foreach ( $sourceDirs as $dir ) : ?>
					<div class="hda-imgconv-source-item">
						<label>
							<input type="checkbox"
								class="checkbox hda-imgconv-source-checkbox"
								value="<?php echo esc_attr( $dir['relative'] ); ?>"
								data-total="<?php echo esc_attr( $dir['total'] ); ?>"
								data-remaining="<?php echo esc_attr( $dir['remaining'] ); ?>"
								checked>
							<span class="hda-imgconv-source-item__name"><?php echo esc_html( preg_replace( '#^wp/#', '', $dir['relative'] ) ); ?></span>
						</label>
						<span class="hda-imgconv-source-item__stats">
							<?php if ( $dir['converted'] > 0 ) : ?>
								<span class="hda-badge hda-badge--success"><?php echo esc_html( $dir['converted'] ); ?> converted</span>
							<?php endif; ?>
							<span class="hda-badge hda-badge--muted"><?php echo esc_html( $dir['remaining'] ); ?> remaining</span>
							<span class="hda-imgconv-source-item__total">(<?php echo esc_html( $dir['total'] ); ?> total)</span>
						</span>
					</div>
				<?php endforeach; ?>

				<?php if ( empty( $sourceDirs ) ) : ?>
					<p class="desc"><?php esc_html_e( 'No source directories found.', 'hda' ); ?></p>
				<?php endif; ?>

				<!-- Force re-convert option -->
				<div class="hda-imgconv-force-option">
					<label>
						<input type="checkbox"
							class="checkbox"
							id="hda-imgconv-force-reconvert"
							value="1">
						<span><?php esc_html_e( 'Force re-convert (overwrite existing converted files)', 'hda' ); ?></span>
					</label>
				</div>
			</div>

			<!-- Source dir (hidden, set by JS) -->
			<input type="hidden" id="imgconv_source_dir" value="wp-content/uploads">

			<!-- Actions -->
			<div class="hda-imgconv-batch-actions">
				<button type="button"
						class="button hda-imgconv-btn--start"
						id="hda-imgconv-start-btn"
						<?php disabled( $isActiveBatch ); ?>>
					<?php esc_html_e( 'Start Conversion', 'hda' ); ?>
				</button>
				<button type="button"
						class="button"
						id="hda-imgconv-cancel-btn"
						style="display: <?php echo $isActiveBatch ? 'inline-flex' : 'none'; ?>;">
					<?php esc_html_e( 'Cancel', 'hda' ); ?>
				</button>
			</div>
		</div>

		<!-- Progress Panel -->
		<div class="hda-imgconv-progress" id="hda-imgconv-progress" style="display: <?php echo $isActiveBatch ? 'block' : 'none'; ?>;">
			<div class="hda-imgconv-progress-bar-wrapper">
				<div class="hda-imgconv-progress-bar" id="progress-bar" style="width: 0%;"></div>
				<span id="progress-percent">0%</span>
			</div>
			<div class="hda-imgconv-progress-info">
				<span id="progress-text"><?php esc_html_e( 'Preparing...', 'hda' ); ?></span>
			</div>
			<div class="hda-imgconv-stats">
				<div class="hda-imgconv-stat">
					<span class="stat-icon">✅</span>
					<span class="stat-label"><?php esc_html_e( 'Converted', 'hda' ); ?></span>
					<span class="stat-value" id="stat-converted">0</span>
				</div>
				<div class="hda-imgconv-stat">
					<span class="stat-icon">⏭</span>
					<span class="stat-label"><?php esc_html_e( 'Skipped', 'hda' ); ?></span>
					<span class="stat-value" id="stat-skipped">0</span>
				</div>
				<div class="hda-imgconv-stat">
					<span class="stat-icon">❌</span>
					<span class="stat-label"><?php esc_html_e( 'Errors', 'hda' ); ?></span>
					<span class="stat-value" id="stat-errors">0</span>
				</div>
				<div class="hda-imgconv-stat">
					<span class="stat-icon">💾</span>
					<span class="stat-label"><?php esc_html_e( 'Saved', 'hda' ); ?></span>
					<span class="stat-value" id="stat-saved">0 KB</span>
				</div>
			</div>
			<div class="hda-imgconv-timing">
				<span>⏱ <span id="stat-elapsed">0s</span></span>
				<span>⏳ <?php esc_html_e( 'Est.', 'hda' ); ?> <span id="stat-eta">—</span></span>
			</div>
		</div>

		<!-- Completion Message -->
		<div class="hda-imgconv-complete" id="hda-imgconv-complete" style="display: none;">
			<div class="hda-imgconv-complete-icon">🎉</div>
			<h3><?php esc_html_e( 'Conversion Complete!', 'hda' ); ?></h3>
			<p id="complete-message"></p>
		</div>
	</fieldset>

	<!-- ═══ Cleanup / Reset ═══ -->
	<fieldset class="container-fieldset mt-6" id="hda-imgconv-cleanup">
		<legend class="section-legend"><?php esc_html_e( 'Cleanup / Reset', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--warning">
			<p>
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Delete all converted image directories (uploads_avif, uploads_webp, etc.), reset the queue, and remove server rewrite rules.', 'hda' ); ?>
			</p>
		</div>

		<div class="hda-imgconv-batch-actions">
			<button type="button"
					class="button hda-imgconv-btn--danger"
					id="hda-imgconv-cleanup-btn">
				<?php esc_html_e( 'Delete All & Reset', 'hda' ); ?>
			</button>
			<span id="hda-imgconv-cleanup-result" class="hda-imgconv-cleanup-result"></span>
		</div>
	</fieldset>

	<!-- Notice -->
	<div class="hda-notice hda-notice--info">
		<p>
			<span class="dashicons dashicons-info-outline"></span>
			<strong><?php esc_html_e( 'Auto-skip:', 'hda' ); ?></strong>
			<?php esc_html_e( 'Images where the converted output is larger than the original will be automatically skipped.', 'hda' ); ?>
		</p>
	</div>
</div>
