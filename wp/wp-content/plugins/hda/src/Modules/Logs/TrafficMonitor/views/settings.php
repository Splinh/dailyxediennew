<?php
/**
 * Traffic Monitor module options panel.
 *
 * @package HDAddons\Modules\Logs\TrafficMonitor
 */

use HDAddons\Modules\Logs\LogsModule;
use HDAddons\Modules\Logs\TrafficMonitor\TrafficMonitor;

\defined( 'ABSPATH' ) || exit;

$options = LogsModule::getSubOptions( 'traffic_monitor' );

$enabled        = ! empty( $options[ TrafficMonitor::KEY_ENABLED ] );
$retention_days = $options[ TrafficMonitor::KEY_RETENTION_DAYS ] ?? 30;

?>
<div class="container mt-8">

	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Traffic Monitor Settings', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info mb-6">
			<p>
				<span class="dashicons dashicons-chart-area"></span>
				<?php esc_html_e( 'Unified log for Firewall, Login Security, and 404 Monitor events. Enable those modules for data to appear.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( TrafficMonitor::KEY_ENABLED ); ?>">
					<?php esc_html_e( 'Enable Traffic Monitor', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Logs blocked requests, firewall alerts, brute-force attempts, and 404 floods.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_logs[traffic_monitor][<?php echo esc_attr( TrafficMonitor::KEY_ENABLED ); ?>]" id="<?php echo esc_attr( TrafficMonitor::KEY_ENABLED ); ?>" <?php checked( $enabled ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Activate security event logging', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
			<div class="section section-text">
				<label class="heading" for="<?php echo esc_attr( TrafficMonitor::KEY_RETENTION_DAYS ); ?>">
					<?php esc_html_e( 'Log Retention', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Auto-purged weekly via WP-Cron. Hard cap: 100K entries.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls flex items-center gap-2">
						<input type="number" class="input w-32!" name="hda_logs[traffic_monitor][<?php echo esc_attr( TrafficMonitor::KEY_RETENTION_DAYS ); ?>]" id="<?php echo esc_attr( TrafficMonitor::KEY_RETENTION_DAYS ); ?>" value="<?php echo absint( $retention_days ); ?>" min="7" max="365" step="1">
						<span class="text-sm font-medium text-slate-500 whitespace-nowrap"><?php esc_html_e( 'days', 'hda' ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php if ( $enabled ) : ?>
		<div class="mt-4">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hda-traffic-monitor' ) ); ?>" class="button button-primary inline-flex! items-center">
				<span class="dashicons dashicons-chart-area align-middle mr-1"></span>
				<?php esc_html_e( 'View Traffic Log', 'hda' ); ?>
			</a>
		</div>
		<?php endif; ?>
	</fieldset>
</div>
