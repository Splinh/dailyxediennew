<?php
/**
 * Optimize module options panel.
 *
 * Simplified Performance: 2 controls instead of 4.
 * - "Heartbeat Control" dropdown (heartbeat_preset: default/reduced/disabled)
 * - "Enable Core Cleanup" toggle (core_cleanup: embeds + wp_head cleanup)
 *
 * @package HDAddons\Modules\Optimize
 */

use HDAddons\Modules\Optimize\DatabaseOptimizer;
use HDAddons\Modules\Optimize\OptimizeModule;

\defined( 'ABSPATH' ) || exit;

$optimize_options = OptimizeModule::getOptions();

?>
<div class="container">


	<?php
	$heartbeat_preset = $optimize_options[ OptimizeModule::KEY_HEARTBEAT ] ?? 'default';
	$core_cleanup     = ! empty( $optimize_options[ OptimizeModule::KEY_CORE_CLEANUP ] );
	?>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- PERFORMANCE -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Performance', 'hda' ); ?></legend>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">

			<!-- Heartbeat Control (merged frequency + location) -->
			<div class="section section-select">
				<label class="heading" for="heartbeat_preset">
					<?php esc_html_e( 'Heartbeat Control', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Controls WordPress Heartbeat API. "Reduced" throttles to 30s and limits to post editor only. "Disabled" deregisters heartbeat entirely.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="hda_optimize[heartbeat_preset]" id="heartbeat_preset">
								<option value="default" <?php selected( $heartbeat_preset, 'default' ); ?>><?php esc_html_e( 'Default (15s, everywhere)', 'hda' ); ?></option>
								<option value="reduced" <?php selected( $heartbeat_preset, 'reduced' ); ?>><?php esc_html_e( 'Reduced (30s, post-edit only)', 'hda' ); ?></option>
								<option value="disabled" <?php selected( $heartbeat_preset, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'hda' ); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<!-- Enable Core Cleanup (embeds + wp_head cleanup) -->
			<div class="section section-checkbox">
				<label class="heading" for="core_cleanup"><?php esc_html_e( 'Enable Core Cleanup', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_optimize[core_cleanup]" id="core_cleanup" <?php checked( $core_cleanup ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Clean up unnecessary WP features', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'View Cleanup Features', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p><?php esc_html_e( 'Enables all of the following in one click:', 'hda' ); ?></p>
						<ul class="pl-6 list-disc mt-2">
							<li><?php esc_html_e( 'Strip emoji scripts and styles', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Remove RSD, WLW, shortlink, and generator tags from wp_head', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Remove REST API link headers', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Remove WP version from script/style URLs', 'hda' ); ?></li>
							<li><?php echo wp_kses( __( 'Disable oEmbed (removes discovery links, scripts, and <code>/wp-json/oembed/</code> endpoint)', 'hda' ), [ 'code' => [] ] ); ?></li>
						</ul>
					</div>
				</details>
			</div>

		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- DATABASE OPTIMIZER -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<?php
	$db_options      = DatabaseOptimizer::getOptions();
	$db_schedule     = $db_options[ DatabaseOptimizer::KEY_SCHEDULE ] ?? '';
	$cleanup_enabled = ! empty( $db_options[ DatabaseOptimizer::KEY_CLEANUP_ENABLED ] );
	$db_counts       = DatabaseOptimizer::getCounts();

	$db_task_labels = [
		'revisions'       => __( 'Post Revisions', 'hda' ),
		'auto_drafts'     => __( 'Auto-Drafts', 'hda' ),
		'trash_posts'     => __( 'Trashed Posts', 'hda' ),
		'spam_comments'   => __( 'Spam Comments', 'hda' ),
		'trash_comments'  => __( 'Trashed Comments', 'hda' ),
		'transients'      => __( 'Expired Transients', 'hda' ),
		'orphan_postmeta' => __( 'Orphaned Post Meta', 'hda' ),
		'orphan_termmeta' => __( 'Orphaned Term Meta', 'hda' ),
		'optimize_tables' => __( 'Optimize Tables', 'hda' ),
	];
	?>

	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Database Optimizer', 'hda' ); ?></legend>

		<!-- Schedule -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Schedule', 'hda' ); ?></h4>
		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-select">
				<label class="heading" for="db_schedule">
					<?php esc_html_e( 'Auto Cleanup', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Runs via WP-Cron. Cleanup tasks below are included in scheduled runs when enabled.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="hda_optimize[db_optimizer][schedule]" id="db_schedule">
								<option value="" <?php selected( $db_schedule, '' ); ?>><?php esc_html_e( 'Disabled', 'hda' ); ?></option>
								<option value="daily" <?php selected( $db_schedule, 'daily' ); ?>><?php esc_html_e( 'Daily', 'hda' ); ?></option>
								<option value="weekly" <?php selected( $db_schedule, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'hda' ); ?></option>
								<option value="monthly" <?php selected( $db_schedule, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'hda' ); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="section">
				<?php
				$next_db = wp_next_scheduled( 'hda_db_optimizer_cleanup' );
				if ( $next_db ) :
					?>
					<span class="heading"><?php esc_html_e( 'Next Run', 'hda' ); ?></span>
					<div class="option">
						<div class="controls">
							<code><?php echo esc_html( wp_date( 'Y-m-d H:i:s', $next_db ) ); ?></code>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Cleanup Tasks -->
		<h4 class="section-subtitle mt-6"><?php esc_html_e( 'Cleanup Tasks', 'hda' ); ?></h4>
		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<label class="heading" for="db_cleanup_enabled"><?php esc_html_e( 'Enable Cleanup', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_optimize[db_optimizer][cleanup_enabled]" id="db_cleanup_enabled" <?php checked( $cleanup_enabled ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Include in scheduled runs above', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Items to be Cleaned', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p><?php esc_html_e( 'Cleans the following items:', 'hda' ); ?></p>
						<ul class="pl-6 list-disc mt-2">
							<?php
							foreach ( $db_task_labels as $key => $label ) :
								$count      = $db_counts[ $key ] ?? null;
								$isOptimize = ( 'optimize_tables' === $key );
								?>
								<li class="mb-1.5 group">
									<span class="text-slate-700"><?php echo esc_html( $label ); ?></span>
									<?php if ( ! $isOptimize && $count !== null ) : ?>
										<strong class="font-semibold px-2 py-0.5 rounded text-[11px] uppercase ml-2 transition-colors duration-300 <?php echo $count > 0 ? 'bg-red-50 text-red-600 group-hover:bg-red-100' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100'; ?>">
											<?php echo esc_html( number_format_i18n( $count ) ); ?>
										</strong>
									<?php elseif ( $isOptimize ) : ?>
										<span class="text-slate-400 text-xs ml-2"><?php esc_html_e( 'Defragment DB', 'hda' ); ?></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</details>
			</div>
		</div>

		<div class="hda-db-actions mt-6">
			<button type="button" id="hda-db-optimize-btn" class="button button-primary inline-flex! items-center gap-2">
				<span class="dashicons dashicons-database-remove"></span>
				<?php esc_html_e( 'Run All Now', 'hda' ); ?>
			</button>
			<span id="hda-db-optimize-status" class="hda-db-status ml-3 text-sm font-medium"></span>
		</div>
	</fieldset>
</div>
