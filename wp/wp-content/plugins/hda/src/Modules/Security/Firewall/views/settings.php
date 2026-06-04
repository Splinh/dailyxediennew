<?php
/**
 * Firewall module options panel.
 *
 * Simplified:
 * - 5 attack detectors → 1 toggle "Attack Detection"
 * - 2 threat intel options → 1 toggle "Threat Intelligence"
 * - Keep: Enable, Mode, Rate Limiting, IP Allowlist
 *
 * @package HDAddons\Modules\Security\Firewall
 */

use HDAddons\Modules\Security\SecurityModule;
use HDAddons\Modules\Security\Firewall\Firewall;

\defined( 'ABSPATH' ) || exit;

$options       = SecurityModule::getSubOptions( Firewall::SUB_KEY );
$enabled       = ! empty( $options[ Firewall::KEY_ENABLED ] );
$mode          = $options[ Firewall::KEY_MODE ] ?? 'learning';
$attack_detect = ! empty( $options[ Firewall::KEY_ATTACK_DETECT ] );
$threat_intel  = ! empty( $options[ Firewall::KEY_THREAT_INTEL ] );
$rate_limit    = ! empty( $options[ Firewall::KEY_RATE_LIMIT ] );
$rate_global   = $options[ Firewall::KEY_RATE_GLOBAL ] ?? 300;
$allowlist_ips = $options[ Firewall::KEY_ALLOWLIST_IPS ] ?? [];

?>
<div class="container mt-8">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FIELDSET 1: WAF ENGINE -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'WAF Engine', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info mb-6">
			<p>
				<span class="dashicons dashicons-shield-alt"></span>
				<?php esc_html_e( 'WAF scans all requests for SQLi, XSS, RCE, and other attacks in real-time.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_ENABLED ); ?>">
					<?php esc_html_e( 'Enable Firewall', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Scans every request for threats. All WAF features are inactive when disabled.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_security[firewall][<?php echo esc_attr( Firewall::KEY_ENABLED ); ?>]" id="<?php echo esc_attr( Firewall::KEY_ENABLED ); ?>" <?php checked( $enabled ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Activate the WAF pipeline', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
			<div class="section section-radio">
				<span class="heading block">
					<?php esc_html_e( 'Firewall Mode', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Learning = log only. Protecting = block threats. Start with Learning.', 'hda' ); ?></span>
					</span>
				</span>
				<div class="option">
					<div class="controls flex items-center flex-wrap gap-4 pt-1 border-b-none">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="radio" name="hda_security[firewall][<?php echo esc_attr( Firewall::KEY_MODE ); ?>]" value="learning" class="radio" <?php checked( $mode, 'learning' ); ?>>
							<span class="font-medium text-sm"><?php esc_html_e( 'Learning (log only)', 'hda' ); ?></span>
						</label>
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="radio" name="hda_security[firewall][<?php echo esc_attr( Firewall::KEY_MODE ); ?>]" value="protecting" class="radio" <?php checked( $mode, 'protecting' ); ?>>
							<span class="font-medium text-sm"><?php esc_html_e( 'Protecting (block threats)', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<!-- IP Allowlist -->
		<div class="container grid grid-cols-1 gap-3 md:gap-6 mt-6">
			<div class="section section-select">
				<label class="heading" for="firewall_allowlist_ips">
					<?php esc_html_e( 'IP Allowlist (bypass all checks)', 'hda' ); ?>
				</label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select multiple
								placeholder="<?php esc_attr_e( 'Type an IP and press Enter', 'hda' ); ?>"
								class="select select2-ips w-full!"
								name="hda_security[firewall][<?php echo esc_attr( Firewall::KEY_ALLOWLIST_IPS ); ?>][]"
								id="firewall_allowlist_ips"
							>
								<?php foreach ( $allowlist_ips as $ip ) : ?>
									<option selected value="<?php echo esc_attr( $ip ); ?>"><?php echo esc_html( $ip ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<details class="hda-details mt-2">
						<summary class="hda-details__summary">
							<span class="dashicons dashicons-editor-help"></span>
							<?php esc_html_e( 'Accepted formats', 'hda' ); ?>
						</summary>
						<div class="hda-details__content">
							<p class="mb-2"><?php esc_html_e( 'Bypass all WAF checks. Formats:', 'hda' ); ?></p>
							<ul class="pl-6 list-disc text-sm text-slate-600">
								<li><?php echo wp_kses_post( __( 'Single IPv4: <code>192.168.1.1</code>', 'hda' ) ); ?></li>
								<li><?php echo wp_kses_post( __( 'Single IPv6: <code>2001:db8::1</code>', 'hda' ) ); ?></li>
								<li><?php echo wp_kses_post( __( 'CIDR: <code>192.168.1.0/24</code>, <code>2001:db8::/32</code>', 'hda' ) ); ?></li>
								<li><?php echo wp_kses_post( __( 'Dash range: <code>192.168.1.1-100</code>', 'hda' ) ); ?></li>
							</ul>
						</div>
					</details>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FIELDSET 2: THREAT DETECTION & PROTECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset id="hda-firewall-detection" class="container-fieldset mt-6" <?php echo $enabled ? '' : 'style="display:none;"'; ?>>
		<legend class="section-legend"><?php esc_html_e( 'Threat Detection & Protection', 'hda' ); ?></legend>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">

			<!-- Toggle 1: Attack Detection (all 5 types) -->
			<div class="section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_ATTACK_DETECT ); ?>">
					<?php esc_html_e( 'Attack Detection', 'hda' ); ?>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_security[firewall][<?php echo esc_attr( Firewall::KEY_ATTACK_DETECT ); ?>]" id="<?php echo esc_attr( Firewall::KEY_ATTACK_DETECT ); ?>" <?php checked( $attack_detect ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Enable all attack detectors', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Scanned Attack Types', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<ul class="pl-6 list-disc text-sm">
							<li><?php esc_html_e( 'SQL Injection (UNION SELECT, OR 1=1, etc.)', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Cross-Site Scripting (injected JS, event handlers)', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Remote Code Execution (eval, exec, system)', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Local File Inclusion (../../etc/passwd, php://filter)', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Bad Bots (sqlmap, nikto, wpscan by User-Agent)', 'hda' ); ?></li>
						</ul>
					</div>
				</details>
			</div>

			<!-- Toggle 2: 404 Flood Protection -->
			<div class="section section-checkbox">
				<label class="heading flex items-center" for="<?php echo esc_attr( Firewall::KEY_404_FLOOD ); ?>">
					<?php esc_html_e( '404 Flood Protection', 'hda' ); ?>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_security[firewall][<?php echo esc_attr( Firewall::KEY_404_FLOOD ); ?>]" id="<?php echo esc_attr( Firewall::KEY_404_FLOOD ); ?>" <?php checked( ! empty( $options[ Firewall::KEY_404_FLOOD ] ) ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Block IPs that generate 10+ 404s per minute', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'How it works', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p class="mb-2 text-sm text-slate-600"><?php esc_html_e( 'Protects against automated scanners looking for vulnerable files.', 'hda' ); ?></p>
					</div>
				</details>
			</div>

			<!-- Toggle 3: Threat Intelligence (Crawler WL + IP Reputation) -->
			<div class="section section-checkbox">
				<label class="heading flex items-center" for="<?php echo esc_attr( Firewall::KEY_THREAT_INTEL ); ?>">
					<?php esc_html_e( 'Threat Intelligence', 'hda' ); ?>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_security[firewall][<?php echo esc_attr( Firewall::KEY_THREAT_INTEL ); ?>]" id="<?php echo esc_attr( Firewall::KEY_THREAT_INTEL ); ?>" <?php checked( $threat_intel ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Enable auto-whitelist and IP reputation (synced daily)', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Protections Managed', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<ul class="pl-6 list-disc text-sm">
							<li><?php esc_html_e( 'Auto-whitelist verified Googlebot, Bingbot, Cloudflare (prevents false blocks)', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Block IPs from Spamhaus DROP/EDROP and Emerging Threats lists', 'hda' ); ?></li>
						</ul>
					</div>
				</details>
			</div>

		</div>

		<!-- Rate Limiting -->
		<h4 class="section-subtitle mt-8!"><?php esc_html_e( 'Rate Limiting', 'hda' ); ?></h4>
		<div class="hda-notice hda-notice--info mb-6">
			<p>
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Login brute-force uses LoginSecurity → Limit Login Attempts (escalating ban: 1h → 1d → 1w).', 'hda' ); ?>
			</p>
		</div>
		<div class="container grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( Firewall::KEY_RATE_LIMIT ); ?>">
					<?php esc_html_e( 'Enable Rate Limiting', 'hda' ); ?>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_security[firewall][<?php echo esc_attr( Firewall::KEY_RATE_LIMIT ); ?>]" id="<?php echo esc_attr( Firewall::KEY_RATE_LIMIT ); ?>" <?php checked( $rate_limit ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Throttle excessive requests per IP', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
			<div class="section section-text justify-self-start">
				<span class="heading block"><?php esc_html_e( 'Global Limit', 'hda' ); ?></span>
				<div class="option">
					<div class="controls flex items-center gap-2">
						<input type="number" class="input w-32!" name="hda_security[firewall][<?php echo esc_attr( Firewall::KEY_RATE_GLOBAL ); ?>]" id="<?php echo esc_attr( Firewall::KEY_RATE_GLOBAL ); ?>" value="<?php echo absint( $rate_global ); ?>" min="10" max="1000" step="10">
						<span class="text-sm font-medium text-slate-500 whitespace-nowrap"><?php esc_html_e( 'requests / min', 'hda' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
</div>

<!-- Toggle Fieldset 2 visibility based on Enable Firewall checkbox -->
<script>
(function () {
	const toggle = document.getElementById('<?php echo esc_js( Firewall::KEY_ENABLED ); ?>');
	const target = document.getElementById('hda-firewall-detection');
	if (!toggle || !target) return;

	toggle.addEventListener('change', function () {
		target.style.display = this.checked ? '' : 'none';
	});
})();
</script>
