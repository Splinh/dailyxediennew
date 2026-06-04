<?php
/**
 * Cron Manager module options panel.
 *
 * @package HDAddons\Modules\CronManager
 */

use HDAddons\Modules\CronManager\CronManagerModule;

\defined( 'ABSPATH' ) || exit;

$events     = CronManagerModule::getEvents();
$stats      = CronManagerModule::getStats( $events );
$cronStatus = CronManagerModule::getCronStatus();
$schedules  = CronManagerModule::getSchedules();

?>
<div class="container mt-8">

	<!-- CRON STATUS -->
	<fieldset class="container-fieldset">
		<legend class="section-legend flex items-center">
			<?php esc_html_e( 'WP-Cron Status', 'hda' ); ?>
		</legend>

		<?php if ( $cronStatus['disabled_constant'] ) : ?>
			<div class="hda-notice hda-notice--warning">
				<p>
					<span class="dashicons dashicons-warning"></span>
					<?php echo wp_kses_post( __( '<code>DISABLE_WP_CRON</code> is <b>true</b> — WP-Cron disabled. Ensure a system <code>crontab</code> calls <code>wp-cron.php</code>.', 'hda' ) ); ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( $cronStatus['alternate_cron'] ) : ?>
			<div class="hda-notice hda-notice--info">
				<p>
					<span class="dashicons dashicons-info"></span>
					<?php echo wp_kses_post( __( '<code>ALTERNATE_WP_CRON</code> is active — using redirect-based cron execution.', 'hda' ) ); ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="container grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-2">
			<div class="text-center py-5 px-4 bg-gray-50 border border-gray-200 rounded-md transition-shadow hover:shadow-md">
				<div class="text-3xl font-bold leading-tight text-wp-primary"><?php echo esc_html( $stats['total'] ); ?></div>
				<div class="text-xs text-gray-500 mt-1.5 uppercase tracking-wide font-medium"><?php esc_html_e( 'Total Events', 'hda' ); ?></div>
			</div>
			<div class="text-center py-5 px-4 bg-gray-50 border border-gray-200 rounded-md transition-shadow hover:shadow-md">
				<div class="text-3xl font-bold leading-tight <?php echo $stats['overdue'] > 0 ? 'text-wp-error' : 'text-wp-success'; ?>">
					<?php echo esc_html( $stats['overdue'] ); ?>
				</div>
				<div class="text-xs text-gray-500 mt-1.5 uppercase tracking-wide font-medium"><?php esc_html_e( 'Overdue', 'hda' ); ?></div>
			</div>
			<div class="text-center py-5 px-4 bg-gray-50 border border-gray-200 rounded-md transition-shadow hover:shadow-md">
				<div class="text-3xl font-bold leading-tight text-wp-accent"><?php echo esc_html( $stats['recurring'] ); ?></div>
				<div class="text-xs text-gray-500 mt-1.5 uppercase tracking-wide font-medium"><?php esc_html_e( 'Recurring', 'hda' ); ?></div>
			</div>
			<div class="text-center py-5 px-4 bg-gray-50 border border-gray-200 rounded-md transition-shadow hover:shadow-md">
				<div class="text-3xl font-bold leading-tight text-amber-500"><?php echo esc_html( $stats['one_time'] ); ?></div>
				<div class="text-xs text-gray-500 mt-1.5 uppercase tracking-wide font-medium"><?php esc_html_e( 'One-time', 'hda' ); ?></div>
			</div>
		</div>
	</fieldset>

	<!-- CRON EVENTS TABLE -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Scheduled Events', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p><?php esc_html_e( 'Read-only view — use action buttons to run or remove events. This panel does not save settings.', 'hda' ); ?></p>
			<div class="flex flex-wrap gap-x-5 gap-y-2 mt-3 text-xs text-slate-600">
				<span class="inline-flex items-center gap-1.5"><span class="inline-block px-2.5 py-0.5 rounded text-xs font-semibold bg-wp-accent-bg text-wp-accent">🔁 Recurring</span> — runs on a fixed schedule</span>
				<span class="inline-flex items-center gap-1.5"><span class="inline-block px-2.5 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-600">⏱ One-time</span> — runs once, then removed</span>
				<span class="inline-flex items-center gap-1.5"><span class="inline-block px-2.5 py-0.5 rounded text-xs font-semibold bg-red-50 text-wp-error">⚠ Overdue</span> — past due >60s, cron may not be firing</span>
			</div>
		</div>

		<?php if ( empty( $events ) ) : ?>
			<div class="hda-notice hda-notice--info">
				<p><?php esc_html_e( 'No scheduled cron events found.', 'hda' ); ?></p>
			</div>
		<?php else : ?>
			<div class="overflow-x-auto mt-1">
				<table class="widefat striped hda-cron-table" id="hda-cron-table">
					<thead>
						<tr>
							<th class="hda-cron-table__row-num">#</th>
							<th><?php esc_html_e( 'Hook', 'hda' ); ?></th>
							<th><?php esc_html_e( 'Arguments', 'hda' ); ?></th>
							<th><?php esc_html_e( 'Next Run', 'hda' ); ?></th>
							<th><?php esc_html_e( 'Schedule', 'hda' ); ?></th>
							<th><?php esc_html_e( 'Interval', 'hda' ); ?></th>
							<th><?php esc_html_e( 'Type', 'hda' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'hda' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php $row_num = 0; ?>
						<?php
						foreach ( $events as $event ) :
							++$row_num;
							?>
							<tr class="hda-cron-row" data-hook="<?php echo esc_attr( $event['hook'] ); ?>" data-timestamp="<?php echo esc_attr( $event['timestamp'] ); ?>" data-sig="<?php echo esc_attr( $event['args_key'] ); ?>">
								<td class="hda-cron-table__row-num"><?php echo esc_html( $row_num ); ?></td>
								<td>
									<code class="text-xs break-all">
										<?php echo esc_html( $event['hook'] ); ?>
									</code>
								</td>
								<td>
									<?php if ( ! empty( $event['args'] ) ) : ?>
										<pre class="text-xs bg-white border border-gray-200 p-1.5 rounded text-gray-600 m-0 w-max max-w-xs overflow-x-auto"><?php echo esc_html( wp_json_encode( $event['args'] ) ); ?></pre>
									<?php else : ?>
										<span class="text-gray-400 text-xs">—</span>
									<?php endif; ?>
								</td>
								<td>
									<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $event['timestamp'] ) ); ?>
								</td>
								<td>
									<?php if ( ! empty( $event['schedule'] ) ) : ?>
										<span class="text-wp-accent font-medium">
											<?php echo esc_html( $schedules[ $event['schedule'] ]['display'] ?? $event['schedule'] ); ?>
										</span>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $event['interval'] ) : ?>
										<?php echo esc_html( human_time_diff( 0, $event['interval'] ) ); ?>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $event['overdue'] ) : ?>
										<span class="inline-block px-2.5 py-0.5 rounded text-xs font-semibold bg-red-50 text-wp-error" title="<?php esc_attr_e( 'This event is overdue — cron may not be running', 'hda' ); ?>">⚠ Overdue</span>
									<?php elseif ( ! empty( $event['schedule'] ) ) : ?>
										<span class="inline-block px-2.5 py-0.5 rounded text-xs font-semibold bg-wp-accent-bg text-wp-accent">🔁 Recurring</span>
									<?php else : ?>
										<span class="inline-block px-2.5 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-600">⏱ One-time</span>
									<?php endif; ?>
								</td>
								<td>
									<button type="button" class="button button-small hda-cron-run" title="<?php esc_attr_e( 'Run now', 'hda' ); ?>">
										<span class="dashicons dashicons-controls-play"></span>
									</button>
									<button type="button" class="button button-small hda-cron-delete" title="<?php esc_attr_e( 'Delete', 'hda' ); ?>">
										<span class="dashicons dashicons-trash"></span>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<div id="hda-cron-status" class="mt-4 text-sm text-gray-500 min-h-5"></div>
	</fieldset>

	<!-- REGISTERED SCHEDULES -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Registered Schedules', 'hda' ); ?></legend>
		
		<div class="hda-notice hda-notice--info">
			<p><?php esc_html_e( 'All recurrence intervals for WP-Cron. Plugins may add custom schedules.', 'hda' ); ?></p>
		</div>
		<div class="overflow-x-auto mt-1">
			<table class="widefat striped hda-cron-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Slug', 'hda' ); ?></th>
						<th><?php esc_html_e( 'Display Name', 'hda' ); ?></th>
						<th><?php esc_html_e( 'Interval', 'hda' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $schedules as $slug => $info ) : ?>
						<tr>
							<td><code class="text-xs break-all"><?php echo esc_html( $slug ); ?></code></td>
							<td><?php echo esc_html( $info['display'] ); ?></td>
							<td>
								<?php echo esc_html( human_time_diff( 0, $info['interval'] ) ); ?>
								<span class="text-gray-400 text-xs ml-0.5">(<?php echo esc_html( number_format_i18n( $info['interval'] ) ); ?>s)</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</fieldset>
</div>
