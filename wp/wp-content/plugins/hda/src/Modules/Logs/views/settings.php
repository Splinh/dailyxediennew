<?php
/**
 * Logs module options panel.
 *
 * @package HDAddons\Modules\Logs
 */

use HDAddons\Modules\Logs\LogsModule;
use HDAddons\Modules\Logs\ActivityLog\ActivityLog;

\defined( 'ABSPATH' ) || exit;

$act_options        = LogsModule::getSubOptions( 'activity_log' );
$act_enabled        = ! empty( $act_options[ ActivityLog::KEY_ENABLED ] );
$act_retention_days = max( 7, min( ActivityLog::MAX_RETENTION_DAYS, (int) ( $act_options[ ActivityLog::KEY_RETENTION_DAYS ] ?? 30 ) ) );

?>
<div class="container mt-8">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- ACTIVITY LOG SETTINGS -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Activity Log Settings', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info mb-6">
			<p>
				<span class="dashicons dashicons-businessman"></span>
				<?php esc_html_e( 'Monitor user sessions, login failures, and authentication events.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( ActivityLog::KEY_ENABLED ); ?>">
					<?php esc_html_e( 'Enable Activity Log', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Logs successful logins, failed attempts, and logouts.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_logs[activity_log][<?php echo esc_attr( ActivityLog::KEY_ENABLED ); ?>]" id="<?php echo esc_attr( ActivityLog::KEY_ENABLED ); ?>" <?php checked( $act_enabled ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Activate user activity logging', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<div class="section section-text">
				<label class="heading" for="<?php echo esc_attr( ActivityLog::KEY_RETENTION_DAYS ); ?>">
					<?php esc_html_e( 'Log Retention', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Auto-purged via WP-Cron. IPs are anonymized before storage. Hard cap: 90 days and 10K entries.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls flex items-center gap-2">
						<input type="number" class="input w-32!" name="hda_logs[activity_log][<?php echo esc_attr( ActivityLog::KEY_RETENTION_DAYS ); ?>]" id="<?php echo esc_attr( ActivityLog::KEY_RETENTION_DAYS ); ?>" value="<?php echo absint( $act_retention_days ); ?>" min="7" max="<?php echo esc_attr( ActivityLog::MAX_RETENTION_DAYS ); ?>" step="1">
						<span class="text-sm font-medium text-slate-500 whitespace-nowrap"><?php esc_html_e( 'days', 'hda' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<?php if ( $act_enabled ) : ?>
		<div class="mt-4">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hda-activity-log' ) ); ?>" class="button button-primary inline-flex! items-center">
				<span class="dashicons dashicons-businessman align-middle mr-1"></span>
				<?php esc_html_e( 'View Activity Log', 'hda' ); ?>
			</a>
		</div>
		<?php endif; ?>
	</fieldset>
</div>

<?php
// Include submodules settings
require __DIR__ . '/../Monitor404/views/settings.php';
require __DIR__ . '/../TrafficMonitor/views/settings.php';
