<?php
/**
 * 404 Monitor module options panel.
 *
 * @package HDAddons\Modules\Logs\Monitor404
 */

use HDAddons\Modules\Logs\Monitor404\Monitor404;

\defined( 'ABSPATH' ) || exit;

$options = Monitor404::getOptions();

?>
<div class="container mt-8">

	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Monitor Settings', 'hda' ); ?></legend>
		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<label class="heading" for="m404_enabled">
					<?php esc_html_e( 'Enable 404 Monitoring', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Logs 404 hits (URL, referrer, user-agent, IP) to find broken links.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_logs[monitor_404][m404_enabled]" id="m404_enabled" <?php checked( ! empty( $options[ Monitor404::KEY_ENABLED ] ) ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Activate logging', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
			<div class="section section-text">
				<label class="heading" for="m404_retention_days">
					<?php esc_html_e( 'Log Retention', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Auto-purged monthly via WP-Cron. Hard cap: 50,000 entries.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<input type="number" class="input w-32!" name="hda_logs[monitor_404][m404_retention_days]" id="m404_retention_days" value="<?php echo absint( $options[ Monitor404::KEY_RETENTION_DAYS ] ?? 90 ); ?>" min="7" max="365" step="1">
						<span class="text-sm text-slate-600 ml-1"><?php esc_html_e( 'days', 'hda' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
</div>
