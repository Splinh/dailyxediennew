<?php
/**
 * File Integrity module options panel.
 *
 * @package HDAddons\Modules\File\FileIntegrity
 */

use HDAddons\Modules\File\FileIntegrity\FileIntegrity;
use HDAddons\Modules\File\FileIntegrity\FileIntegrityAdmin;
use HDAddons\Helper;
use HDAddons\Modules\File\FileModule;

\defined( 'ABSPATH' ) || exit;

$options = FileModule::getSubOptions( FileIntegrity::SUB_KEY );

$enabled      = ! empty( $options[ FileIntegrity::KEY_ENABLED ] );
$core_scan    = ! empty( $options[ FileIntegrity::KEY_CORE_SCAN ] );
$malware_scan = ! empty( $options[ FileIntegrity::KEY_MALWARE_SCAN ] );
$vuln_scan    = ! empty( $options[ FileIntegrity::KEY_VULN_SCAN ] );
$email_alerts = ! empty( $options[ FileIntegrity::KEY_EMAIL_ALERTS ] );
$schedule     = $options[ FileIntegrity::KEY_SCHEDULE ] ?? 'weekly';

$vt_api_key = Helper::getOption( 'hda_virustotal_api_key', '' );

?>
<div class="container mt-8">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FIELDSET 1: FILE INTEGRITY SCANNER -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'File Integrity Scanner', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield-alt"></span>
				<?php esc_html_e( 'Detect modified core files, malware, and vulnerable plugins/themes. Results on File Integrity page.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<label class="heading flex items-center" for="<?php echo esc_attr( FileIntegrity::KEY_ENABLED ); ?>">
					<?php esc_html_e( 'Enable Automated Scanning', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Runs selected scans on schedule. Enable "Email Alerts" for issue reports.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label>
							<input type="checkbox" class="checkbox" name="hda_file[file_integrity][<?php echo esc_attr( FileIntegrity::KEY_ENABLED ); ?>]" id="<?php echo esc_attr( FileIntegrity::KEY_ENABLED ); ?>" <?php checked( $enabled ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Schedule periodic scans via WP-Cron', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
			<div class="section section-select">
				<label class="heading flex items-center" for="<?php echo esc_attr( FileIntegrity::KEY_SCHEDULE ); ?>">
					<?php esc_html_e( 'Scan Schedule', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Scan frequency. Weekly recommended for most sites.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<select class="select" name="hda_file[file_integrity][<?php echo esc_attr( FileIntegrity::KEY_SCHEDULE ); ?>]" id="<?php echo esc_attr( FileIntegrity::KEY_SCHEDULE ); ?>">
							<option value="daily" <?php selected( $schedule, 'daily' ); ?>><?php esc_html_e( 'Daily', 'hda' ); ?></option>
							<option value="weekly" <?php selected( $schedule, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'hda' ); ?></option>
							<option value="monthly" <?php selected( $schedule, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'hda' ); ?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FIELDSET 2: SCAN CONFIGURATION (toggled by Enable checkbox) -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset id="hda-scan-config" class="container-fieldset" <?php echo $enabled ? '' : 'style="display:none;"'; ?>>
		<legend class="section-legend"><?php esc_html_e( 'Scan Configuration', 'hda' ); ?></legend>

		<!-- Scan Types -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Scan Types', 'hda' ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-search"></span>
				<?php esc_html_e( 'Select scans for automated and manual runs.', 'hda' ); ?>
			</p>
		</div>
		<div class="container grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<label class="heading flex items-center" for="<?php echo esc_attr( FileIntegrity::KEY_CORE_SCAN ); ?>">
					<?php esc_html_e( 'Core Integrity Scan', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Compares local files against WordPress.org checksums. Detects modified, unknown, and missing files.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label>
							<input type="checkbox" class="checkbox" name="hda_file[file_integrity][<?php echo esc_attr( FileIntegrity::KEY_CORE_SCAN ); ?>]" id="<?php echo esc_attr( FileIntegrity::KEY_CORE_SCAN ); ?>" <?php checked( $core_scan ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Verify WP core files against official checksums', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
			<div class="section section-checkbox">
				<label class="heading flex items-center" for="<?php echo esc_attr( FileIntegrity::KEY_MALWARE_SCAN ); ?>">
					<?php esc_html_e( 'Malware Scan', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Detects backdoors, obfuscated code, crypto miners, and suspicious patterns in wp-content.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label>
							<input type="checkbox" class="checkbox" name="hda_file[file_integrity][<?php echo esc_attr( FileIntegrity::KEY_MALWARE_SCAN ); ?>]" id="<?php echo esc_attr( FileIntegrity::KEY_MALWARE_SCAN ); ?>" <?php checked( $malware_scan ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Scan for known malware signatures', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
			<div class="section section-checkbox">
				<label class="heading flex items-center" for="<?php echo esc_attr( FileIntegrity::KEY_VULN_SCAN ); ?>">
					<?php esc_html_e( 'Vulnerability Scan', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Checks plugins/themes against WordPress.org for outdated, closed, or abandoned software.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label>
							<input type="checkbox" class="checkbox" name="hda_file[file_integrity][<?php echo esc_attr( FileIntegrity::KEY_VULN_SCAN ); ?>]" id="<?php echo esc_attr( FileIntegrity::KEY_VULN_SCAN ); ?>" <?php checked( $vuln_scan ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Check plugins/themes for known CVEs', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<!-- Alerts -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Alerts', 'hda' ); ?></h4>
		<div class="container grid grid-cols-1 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<label class="heading flex items-center" for="<?php echo esc_attr( FileIntegrity::KEY_EMAIL_ALERTS ); ?>">
					<?php esc_html_e( 'Email Alerts', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body">
							<?php
							printf(
								/* translators: %s: admin email */
								esc_html__( 'Sends scan report to: %s', 'hda' ),
								'<code>' . esc_html( Helper::getOption( 'admin_email' ) ) . '</code>'
							);
							?>
						</span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label>
							<input type="checkbox" class="checkbox" name="hda_file[file_integrity][<?php echo esc_attr( FileIntegrity::KEY_EMAIL_ALERTS ); ?>]" id="<?php echo esc_attr( FileIntegrity::KEY_EMAIL_ALERTS ); ?>" <?php checked( $email_alerts ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Email admin when issues are found', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- API KEYS & MANUAL SCANS (always visible) -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'API Keys & Manual Scans', 'hda' ); ?></legend>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-text">
				<label class="heading flex items-center" for="hda_virustotal_api_key">
					<?php esc_html_e( 'VirusTotal API Key', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Optional. Cross-checks suspicious files via 70+ antivirus engines.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<input type="password" class="regular-text" name="hda_file[file_integrity][hda_virustotal_api_key]" id="hda_virustotal_api_key" value="<?php echo esc_attr( $vt_api_key ); ?>" autocomplete="off">
					</div>
				</div>
				<div class="desc">
					<details class="hda-details mt-3">
						<summary class="hda-details__summary cursor-pointer hover:text-slate-800 transition-colors select-none"><?php esc_html_e( 'How to get a free key?', 'hda' ); ?></summary>
						<div class="mt-1.5">
							<?php
							printf(
								/* translators: %s: link to VirusTotal */
								esc_html__( 'Get a free API key at %s.', 'hda' ),
								'<a href="https://www.virustotal.com/gui/join-us" target="_blank" rel="noopener">virustotal.com</a>'
							);
							?>
						</div>
					</details>
				</div>
			</div>
			<div class="section">
				<span class="heading flex items-center">
					<?php esc_html_e( 'Run Manual Scan', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Run all scans on-demand. Results cached 12 hours.', 'hda' ); ?></span>
					</span>
				</span>
				<div class="option" style="margin-top:8px;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . FileIntegrityAdmin::MENU_SLUG ) ); ?>" class="button button-primary">
						<span class="dashicons dashicons-search" style="vertical-align:middle;margin-right:4px;"></span>
						<?php esc_html_e( 'Go to File Integrity Page', 'hda' ); ?>
					</a>
				</div>
			</div>
		</div>
	</fieldset>
</div>

<!-- Toggle Fieldset 2 visibility based on Enable checkbox -->
<script>
(function () {
	const toggle = document.getElementById('<?php echo esc_js( FileIntegrity::KEY_ENABLED ); ?>');
	const target = document.getElementById('hda-scan-config');
	if (!toggle || !target) return;

	toggle.addEventListener('change', function () {
		target.style.display = this.checked ? '' : 'none';
	});
})();
</script>
