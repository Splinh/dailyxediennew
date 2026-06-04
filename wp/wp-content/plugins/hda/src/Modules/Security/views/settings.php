<?php
/**
 * Security module options panel.
 *
 * Simplified WP Hardening: 2 toggles instead of 7 checkboxes.
 * - "Disable Comments" (comments_off) — kept separate due to major UX impact
 * - "WP Security Hardening" (wp_hardening) — merges XMLRPC, WP version, OPML, RSS, readme, App Passwords
 *
 * @package HDAddons\Modules\Security
 */

use HDAddons\Helper;
use HDAddons\Modules\Security\SecurityModule;
use HDAddons\Modules\Security\Countries;
use HDAddons\Modules\Security\AccessControl;
use HDAddons\Modules\Security\ServerConfig\ServerConfig;

\defined( 'ABSPATH' ) || exit;

$security_options = Helper::getOption( SecurityModule::optionKey(), [] );
$comments_off     = ! empty( $security_options[ SecurityModule::KEY_COMMENTS_OFF ] );
$wp_hardening     = ! empty( $security_options[ SecurityModule::KEY_WP_HARDENING ] );
$server_config    = ! empty( $security_options[ SecurityModule::KEY_SERVER_CONFIG ] );
$lock_files       = ! empty( $security_options[ SecurityModule::KEY_LOCK_FILES ] );

// WAF options (stored as sub-key of hda_security).
$waf_options   = SecurityModule::getSubOptions( AccessControl::SUB_KEY );
$blocked       = $waf_options[ AccessControl::KEY_BLOCKED_COUNTRIES ] ?? [];
$country_mode  = $waf_options[ AccessControl::KEY_COUNTRY_MODE ] ?? 'block_selected';
$block_unknown = ! empty( $waf_options[ AccessControl::KEY_BLOCK_UNKNOWN ] );
$blocked_ips   = $waf_options[ AccessControl::KEY_BLOCKED_IPS ] ?? [];
$countries     = Countries::getAll();
$is_cf         = AccessControl::isCloudflare();

// Server config detection.
$server_type  = ServerConfig::detectServerType();
$server_label = ServerConfig::getServerLabel();
$is_apache    = ServerConfig::isApache();
$is_nginx     = ServerConfig::isNginx();
$has_block    = ServerConfig::hasBlock();
$config_file  = $is_apache ? ServerConfig::getHtaccessPath() : ( $is_nginx ? ServerConfig::getNginxConfPath() : '' );

// File lock status.
$file_lock_status = ServerConfig::getFileLockStatus();

?>
<div class="container">

	<?php if ( ! $is_cf ) : ?>
	<div class="hda-notice hda-notice--warning">
		<p>
			<span class="dashicons dashicons-cloud"></span>
			<strong><?php esc_html_e( 'Cloudflare Recommended', 'hda' ); ?></strong> —
			<?php esc_html_e( 'Your site is not behind Cloudflare. Consider using their free WAF for edge-level protection.', 'hda' ); ?>
			<a href="https://www.cloudflare.com/waf/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more →', 'hda' ); ?></a>
		</p>
	</div>
	<?php endif; ?>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- WordPress SECURITY HARDENING -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'WordPress Security Hardening', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info mb-6">
			<p>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Disable unnecessary WP features to reduce attack surface. Changes take effect immediately.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">

			<!-- Toggle 1: Disable Comments -->
			<div class="section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( SecurityModule::KEY_COMMENTS_OFF ); ?>">
					<?php esc_html_e( 'Disable Comments', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Disables comments, pingbacks, trackbacks, and removes all related UI site-wide.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( SecurityModule::optionKey() ); ?>[<?php echo esc_attr( SecurityModule::KEY_COMMENTS_OFF ); ?>]" id="<?php echo esc_attr( SecurityModule::KEY_COMMENTS_OFF ); ?>" <?php checked( $comments_off ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Remove all comment features site-wide', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<!-- Toggle 2: WP Security Hardening -->
			<div class="section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( SecurityModule::KEY_WP_HARDENING ); ?>"><?php esc_html_e( 'WP Security Hardening', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( SecurityModule::optionKey() ); ?>[<?php echo esc_attr( SecurityModule::KEY_WP_HARDENING ); ?>]" id="<?php echo esc_attr( SecurityModule::KEY_WP_HARDENING ); ?>" <?php checked( $wp_hardening ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Harden WordPress core against common attacks', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Includes 6 Hardening Rules', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<ul class="pl-6 list-disc">
							<li><?php esc_html_e( 'Disable XML-RPC (brute-force & DDoS)', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Hide WP version from meta & admin footer', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Block wp-links-opml.php (exposes metadata)', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Disable RSS/Atom feeds', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Auto-delete readme.html after updates', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Disable Application Passwords (WP 5.6+)', 'hda' ); ?></li>
						</ul>
					</div>
				</details>
			</div>

		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- ACCESS CONTROL (Country / IP / Range blocking) -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Access Control', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info mb-6">
			<p>
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<?php
				printf(
					/* translators: %s: server type */
					esc_html__( 'Server: %s. IP blocking uses native server rules. Country blocking uses GeoLite2 at PHP level.', 'hda' ),
					'<strong>' . esc_html( $server_label ) . '</strong>'
				);
				?>
			</p>
		</div>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
			<!-- Country Blocking Mode -->
			<div class="section section-radio">
				<span class="heading block">
					<?php esc_html_e( 'Country Blocking Mode', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Block Selected: only chosen countries are blocked. Allow Selected: all countries are blocked except chosen ones.', 'hda' ); ?></span>
					</span>
				</span>
				<div class="option">
					<div class="controls flex items-center flex-wrap gap-4 pt-1 border-b-none">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="radio" name="hda_security[waf][country_mode]" value="block_selected" class="radio" <?php checked( $country_mode, 'block_selected' ); ?>>
							<span class="font-medium text-sm"><?php esc_html_e( 'Block Selected Countries', 'hda' ); ?></span>
						</label>
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="radio" name="hda_security[waf][country_mode]" value="allow_selected" class="radio" <?php checked( $country_mode, 'allow_selected' ); ?>>
							<span class="font-medium text-sm"><?php esc_html_e( 'Allow Selected Only', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<!-- Block Unknown Countries -->
			<div class="section section-checkbox">
				<label class="heading" for="block_unknown_countries">
					<?php esc_html_e( 'Block Unknown Countries', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Block when GeoIP cannot determine the country (private VPN, invalid IP).', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_security[waf][block_unknown_countries]" id="block_unknown_countries" <?php checked( $block_unknown ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Block when country cannot be determined', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<!-- Country Blocking -->
		<div class="section section-select mt-6">
			<label class="heading" for="hda-country-select" id="hda-country-heading">
				<?php
				if ( 'allow_selected' === $country_mode ) {
					esc_html_e( 'Allowed Countries', 'hda' );
				} else {
					esc_html_e( 'Blocked Countries', 'hda' );
				}
				?>
				<span class="hda-tip">
					<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
					<span class="hda-tip__body"><?php esc_html_e( 'GeoLite2 .mmdb file location: resources/geoip/ or wp-content/uploads/hda/.', 'hda' ); ?></span>
				</span>
			</label>
			<div class="option">
				<div class="controls">
					<div class="hda-country-selector">
						<select id="hda-country-select" class="select">
							<option value="" id="hda-country-placeholder">
								<?php
								if ( 'allow_selected' === $country_mode ) {
									esc_html_e( 'Select a country to allow...', 'hda' );
								} else {
									esc_html_e( 'Select a country to block...', 'hda' );
								}
								?>
							</option>
							<?php foreach ( $countries as $code => $name ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php disabled( in_array( $code, $blocked, true ) ); ?>>
									<?php echo esc_html( $name . ' (' . $code . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="button button-primary hda-add-country-btn" id="hda-add-country-btn">
							<span class="dashicons dashicons-shield"></span>
							<span id="hda-country-btn-text">
							<?php
							if ( 'allow_selected' === $country_mode ) {
								esc_html_e( 'Add to Allowlist', 'hda' );
							} else {
								esc_html_e( 'Add to Blocklist', 'hda' );
							}
							?>
							</span>
						</button>
					</div>

					<div class="hda-blocked-list-wrap">
						<ul id="hda-blocked-list" class="hda-blocked-list">
							<?php if ( empty( $blocked ) ) : ?>
								<li class="empty-msg">
									<?php
									if ( 'allow_selected' === $country_mode ) {
										esc_html_e( 'No countries in allowlist. All traffic will be blocked!', 'hda' );
									} else {
										esc_html_e( 'No countries blocked.', 'hda' );
									}
									?>
								</li>
							<?php else : ?>
								<?php foreach ( $blocked as $code ) : ?>
									<?php $name = $countries[ $code ] ?? $code; ?>
									<li class="blocked-item">
										<img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $code ) ); ?>.png" width="16" height="12" alt="">
										<span><?php echo esc_html( $name ); ?></span>
										<input type="hidden" name="hda_security[waf][blocked_countries][]" value="<?php echo esc_attr( $code ); ?>">
										<button type="button" class="remove-country" aria-label="<?php esc_attr_e( 'Remove', 'hda' ); ?>">&times;</button>
									</li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<!-- IP Blocklist -->
		<div class="section section-select mt-6">
			<label class="heading" for="waf_blocked_ips">
				<?php esc_html_e( 'IP Blocklist', 'hda' ); ?>
			</label>
			<div class="option">
				<div class="controls">
					<div class="select_wrapper">
						<select multiple
							placeholder="<?php esc_attr_e( 'Type an IP address or range and press Enter', 'hda' ); ?>"
							class="select select2-ips w-full"
							name="hda_security[waf][waf_blocked_ips][]"
							id="waf_blocked_ips"
						>
							<?php foreach ( $blocked_ips as $entry ) : ?>
								<option selected value="<?php echo esc_attr( $entry ); ?>"><?php echo esc_html( $entry ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Accepted IP formats', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p class="mb-2"><?php esc_html_e( 'Native server deny rules (Apache/Nginx). Valid formats:', 'hda' ); ?></p>
						<ul class="pl-6 list-disc text-sm text-slate-600">
							<li>IPv4: <code>203.0.113.50</code></li>
							<li>IPv6: <code>2001:db8::1</code></li>
							<li>CIDR: <code>192.168.1.0/24</code></li>
							<li>Range: <code>10.0.0.1-100</code></li>
						</ul>
					</div>
				</details>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- SERVER & FILE PROTECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Server & File Protection', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info mb-6">
			<p>
				<span class="dashicons dashicons-admin-tools"></span>
				<?php
				printf(
					/* translators: %s: server type label */
					esc_html__( 'Server: %s. Manages security headers, file protection, bot blocking, and caching rules in server config.', 'hda' ),
					'<strong>' . esc_html( $server_label ) . '</strong>'
				);
				?>
			</p>
			<?php if ( $is_nginx ) : ?>
				<p class="hda-notice__subtitle">
					<?php
					printf(
						/* translators: %s: nginx config file path */
						esc_html__( 'Nginx: Config generated at %s. Include in server block and reload manually.', 'hda' ),
						'<code>' . esc_html( $config_file ) . '</code>'
					);
					?>
				</p>
			<?php endif; ?>
			<?php if ( $is_apache && $has_block ) : ?>
				<p class="hda-notice__success">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php
					printf(
						/* translators: %s: config file path */
						esc_html__( 'Config block is active in: %s', 'hda' ),
						'<code>' . esc_html( $config_file ) . '</code>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">

			<!-- Toggle 1: Server Config Rules -->
			<div class="section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( SecurityModule::KEY_SERVER_CONFIG ); ?>">
					<?php esc_html_e( 'Enable Server Config Rules', 'hda' ); ?>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( SecurityModule::optionKey() ); ?>[<?php echo esc_attr( SecurityModule::KEY_SERVER_CONFIG ); ?>]" id="<?php echo esc_attr( SecurityModule::KEY_SERVER_CONFIG ); ?>" <?php checked( $server_config ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Manage server-level security rules', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Active Protections', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p>
							<?php if ( $is_apache ) : ?>
								<?php esc_html_e( 'Manages the # BEGIN HDA block in .htaccess. Includes:', 'hda' ); ?>
							<?php elseif ( $is_nginx ) : ?>
								<?php esc_html_e( 'Generates nginx-theme.conf file. Includes:', 'hda' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Server-level security rules:', 'hda' ); ?>
							<?php endif; ?>
						</p>
						<ul class="pl-6 list-disc mt-2">
							<li><?php esc_html_e( 'Security headers (HSTS, X-Frame, CSP)', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Sensitive file protection (.env, wp-config)', 'hda' ); ?></li>

							<li><?php esc_html_e( 'GZIP compression & static caching', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Block PHP in uploads & VCS folders', 'hda' ); ?></li>
						</ul>
					</div>
				</details>
			</div>

			<!-- Toggle 2: Lock Critical Files -->
			<div class="section section-checkbox">
				<label class="heading" for="<?php echo esc_attr( SecurityModule::KEY_LOCK_FILES ); ?>">
					<?php esc_html_e( 'Lock Critical Files', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Sets critical WP files to read-only (chmod 0444). Auto-unlocks .htaccess when writing server config rules.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( SecurityModule::optionKey() ); ?>[<?php echo esc_attr( SecurityModule::KEY_LOCK_FILES ); ?>]" id="<?php echo esc_attr( SecurityModule::KEY_LOCK_FILES ); ?>" <?php checked( $lock_files ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Set critical WP files to read-only (chmod 0444)', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'File Lock Status', 'hda' ); ?>
					</summary>
					<div class="hda-details__content pt-1">
						<table class="widefat striped mt-1 max-w-lg">
							<thead>
								<tr>
									<th><?php esc_html_e( 'File', 'hda' ); ?></th>
									<th><?php esc_html_e( 'Permissions', 'hda' ); ?></th>
									<th><?php esc_html_e( 'Status', 'hda' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $file_lock_status as $label => $info ) : ?>
									<tr>
										<td><code><?php echo esc_html( $label ); ?></code></td>
										<td><code><?php echo esc_html( $info['perms'] ); ?></code></td>
										<td>
											<?php if ( $info['locked'] ) : ?>
												<span class="font-medium text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-600"><span class="dashicons dashicons-lock" style="font-size:12px;width:12px;height:12px;"></span> <?php esc_html_e( 'Locked', 'hda' ); ?></span>
											<?php else : ?>
												<span class="font-medium text-xs px-2 py-0.5 rounded bg-red-50 text-red-600"><span class="dashicons dashicons-unlock" style="font-size:12px;width:12px;height:12px;"></span> <?php esc_html_e( 'Unlocked', 'hda' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</details>
			</div>

		</div>
	</fieldset>
</div>
<?php

// ── Sub-module options (WAF Firewall) ──
require __DIR__ . '/../Firewall/views/settings.php';
