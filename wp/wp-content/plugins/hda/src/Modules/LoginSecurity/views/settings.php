<?php

use HDAddons\Helper;
use HDAddons\Modules\LoginSecurity\Gateway\GatewayFactory;
use HDAddons\Modules\LoginSecurity\LoginAttempts;
use HDAddons\Modules\LoginSecurity\LoginSecurityModule;
use HDAddons\Modules\LoginSecurity\Totp\TotpHandler;


\defined( 'ABSPATH' ) || exit;

// Options merged with defaults — no inline ?? needed.
$login_security_options = LoginSecurityModule::getOptions();

// Login URL section
$custom_login_uri     = $login_security_options[ LoginSecurityModule::KEY_CUSTOM_LOGIN_URI ];
$login_token_ip_check = $login_security_options[ LoginSecurityModule::KEY_LOGIN_TOKEN_IP_CHECK ];

// Login OTP section
$otp_mode           = $login_security_options[ LoginSecurityModule::KEY_OTP_MODE ];
$otp_gateway        = $login_security_options[ LoginSecurityModule::KEY_OTP_GATEWAY ];
$otp_gateway_config = $login_security_options[ LoginSecurityModule::KEY_OTP_GATEWAY_CONFIG ];
$otp_user_roles     = $login_security_options[ LoginSecurityModule::KEY_OTP_USER_ROLES ];
$otp_ip_binding     = $login_security_options[ LoginSecurityModule::KEY_OTP_IP_BINDING ];


// Login Protection section
$login_ips_access     = $login_security_options[ LoginSecurityModule::KEY_LOGIN_IPS_ACCESS ];
$limit_login_attempts = $login_security_options[ LoginSecurityModule::KEY_LIMIT_LOGIN_ATTEMPTS ];

// Privileged user check
$_options_default    = Helper::filterSettingOptions( 'security', false );
$privileged_user_ids = $_options_default['privileged_user_ids'] ?? [];
$user_id             = get_current_user_id();
$privileged          = in_array( $user_id, $privileged_user_ids, true );

// Available gateways (single source of truth)
$available_gateways = GatewayFactory::getAvailable();

// All roles
$all_roles = wp_roles()->get_names();

// ── Current user OTP setup status ─────────────────────────
$current_user_sms_verified = (bool) get_user_meta( $user_id, '_otp_contact_verified', true );
$current_user_totp_setup   = TotpHandler::isUserSetup( $user_id );

// Check if user is privileged
if ( empty( $privileged ) ) {
	echo '<h3>' . esc_html__( 'You do not have permission to access this page', 'hda' ) . '</h3>';
	return;
}

?>
<div class="container mt-8">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- LOGIN PROTECTION SECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="login-protection-fieldset container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Login Protection', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Protect login from brute-force, bots, and unauthorized access.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-select">
				<label class="heading" for="login_ips_access"><?php esc_html_e( 'Allowlist IPs Login Access', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select multiple placeholder="Enter IP addresses" class="select select2-ips !w[100%]" name="hda_login_security[login_ips_access]" id="login_ips_access">
								<?php
								if ( $login_ips_access ) {
									foreach ( (array) $login_ips_access as $ip ) {
										?>
										<option selected value="<?php echo esc_attr( $ip ); ?>"><?php echo esc_html( $ip ); ?></option>
										<?php
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'IP Address Formats', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p><?php esc_html_e( 'Only listed IPs can access login. Formats:', 'hda' ); ?></p>
						<ul class="pl-6 list-disc mt-2 mb-3">
							<li><?php echo wp_kses_post( __( 'Single IPv4: <code>192.168.1.1</code>', 'hda' ) ); ?></li>
							<li><?php echo wp_kses_post( __( 'Single IPv6: <code>2001:db8::1</code>', 'hda' ) ); ?></li>
							<li><?php echo wp_kses_post( __( 'CIDR: <code>192.168.1.0/24</code>, <code>2001:db8::/32</code>', 'hda' ) ); ?></li>
							<li><?php echo wp_kses_post( __( 'Dash range: <code>192.168.1.1-100</code>', 'hda' ) ); ?></li>
						</ul>
						<p>
							<?php
							printf(
								/* translators: %s: current IP address */
								wp_kses_post( __( '🌐 Your current IP: <code>%s</code>', 'hda' ) ),
								esc_html( Helper::ipAddress() )
							);
							?>
						</p>
						<div class="hda-notice hda-notice--info mt-2">
							<p><?php echo wp_kses_post( __( '💡 <b>Static IP only</b> (offices, fixed VPN). For dynamic IPs, use Custom Login URL or OTP instead.', 'hda' ) ); ?></p>
						</div>
					</div>
				</details>
			</div>

			<div class="section section-checkbox">
				<label class="heading" for="basic_protection">
					<?php esc_html_e( 'Basic Protection', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Block common usernames and log activity', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_login_security[basic_protection]" id="basic_protection" <?php checked( ! empty( $login_security_options[ LoginSecurityModule::KEY_BASIC_PROTECTION ] ) ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Enable basic protection features', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'What does this do?', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p><?php esc_html_e( 'Enables all of the following in one click:', 'hda' ); ?></p>
						<ul class="pl-6 list-disc mt-2">
							<li><?php echo wp_kses_post( __( 'Block login/registration with common usernames (<b>admin</b>, <b>root</b>, <b>test</b>, etc.) — rejected before DB check', 'hda' ) ); ?></li>
							<li><?php echo wp_kses_post( __( 'Record login, logout, and failed attempts with IP &amp; timestamp — auto-cleaned after <b>30 days</b>', 'hda' ) ); ?></li>
						</ul>
					</div>
				</details>
			</div>

			<div class="section section-select">
				<label class="heading" for="limit_login_attempts">
					<?php esc_html_e( 'Limit Login Attempts', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Locks IP after N failed passwords. Escalating ban: 1h → 24h → 7d. For DDoS rate limiting, use Firewall.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="hda_login_security[limit_login_attempts]" id="limit_login_attempts">
								<?php foreach ( LoginAttempts::$login_attempts_data as $key => $value ) { ?>
								<option value="<?php echo esc_attr( $key ); ?>"<?php echo selected( $limit_login_attempts, $key, false ); ?>><?php echo esc_html( $value ); ?></option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
			</div>

		</div>
	</fieldset>
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- LOGIN URL SECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="login-url-fieldset container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Login URL', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--warning">
			<p>
				<span class="dashicons dashicons-warning"></span>
				<?php _e( '<strong>Important:</strong> Remember your custom URL! If you forget it, please contact the site administrator or refer to the technical documentation.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-text">
				<label class="heading" for="custom_login_uri">
					<?php esc_html_e( 'Custom Admin Login URL', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php echo wp_kses_post( __( 'Replace /wp-login.php with a custom slug. Bots get a 404.', 'hda' ) ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls control-prefix" style="height: unset;">
						<div class="prefix">
							<span class="input-txt" title="<?php echo esc_attr( home_url( '/' ) ); ?>"><?php echo esc_html( home_url( '/' ) ); ?></span>
						</div>
						<?php $custom_login_uri = $custom_login_uri ?: 'wp-login.php'; ?>
						<input value="<?php echo esc_attr( $custom_login_uri ); ?>" class="input" type="text" id="custom_login_uri" name="hda_login_security[custom_login_uri]" placeholder="<?php echo esc_attr( $custom_login_uri ); ?>" style="max-width: 250px;">
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Security & Rate Limits', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p><?php echo wp_kses_post( __( 'To prevent database transient flooding from bots guessing the URL, this feature includes a strict system-level rate limit.', 'hda' ) ); ?></p>
						<ul class="pl-6 list-disc mt-2">
							<li><?php echo wp_kses_post( __( '<strong>Rate Limit:</strong> Maximum 10 hits per minute per IP.', 'hda' ) ); ?></li>
							<li><?php echo wp_kses_post( __( '<strong>Penalty:</strong> Excess requests return HTTP 429 and are blocked for 60 seconds.', 'hda' ) ); ?></li>
							<li><?php echo wp_kses_post( __( '<strong>Admin Safe:</strong> Real admins only consume 1 hit since successful login cookies bypass the limit.', 'hda' ) ); ?></li>
						</ul>
					</div>
				</details>
			</div>

			<div class="section section-checkbox">
				<label class="heading" for="login_token_ip_check"><?php esc_html_e( 'Strict IP Validation for Login Token', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_login_security[login_token_ip_check]" id="login_token_ip_check" <?php checked( $login_token_ip_check, 1 ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Bind authentication token to user IP', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'What does this do?', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p><?php echo wp_kses_post( __( 'Token bound to IP — prevents session hijacking.', 'hda' ) ); ?></p>
						<p class="mt-2 text-red-600 font-medium">⚠️ <?php esc_html_e( 'May break on mobile/VPN with changing IPs.', 'hda' ); ?></p>
					</div>
				</details>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- LOGIN VERIFICATION SECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="login-otp-fieldset container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Login Verification', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-lock"></span>
				<?php esc_html_e( '2FA via Email OTP, SMS/Messaging, TOTP, or Magic Link. Users set up in their profile.', 'hda' ); ?>
			</p>
		</div>

		<!-- OTP Mode -->
		<div class="section section-radio section-otp-mode">
			<span class="heading"><?php esc_html_e( 'Login Verification Mode', 'hda' ); ?></span>
			<div class="option inline-option">
				<div class="controls">
					<div class="inline-group">
						<label class="radio-label">
							<input type="radio" name="hda_login_security[otp_mode]" value="disabled" <?php checked( $otp_mode, 'disabled' ); ?>>
							<span><?php esc_html_e( 'Disabled', 'hda' ); ?></span>
						</label>
						<label class="radio-label">
							<input type="radio" name="hda_login_security[otp_mode]" value="email" <?php checked( $otp_mode, 'email' ); ?>>
							<span><?php esc_html_e( 'Email OTP', 'hda' ); ?></span>
						</label>
						<label class="radio-label">
							<input type="radio" name="hda_login_security[otp_mode]" value="sms" <?php checked( $otp_mode, 'sms' ); ?>>
							<span><?php esc_html_e( 'SMS / Messaging', 'hda' ); ?></span>
							<?php if ( $current_user_sms_verified ) : ?>
								<em class="otp-setup-note ok">✓ <?php esc_html_e( 'Your account is verified', 'hda' ); ?></em>
							<?php else : ?>
								<em class="otp-setup-note warning">⚠ <?php esc_html_e( 'Your account is not verified', 'hda' ); ?></em>
							<?php endif; ?>
						</label>
						<label class="radio-label">
							<input type="radio" name="hda_login_security[otp_mode]" value="totp" <?php checked( $otp_mode, 'totp' ); ?>>
							<span><?php esc_html_e( 'Authenticator App (TOTP)', 'hda' ); ?></span>
							<?php if ( $current_user_totp_setup ) : ?>
								<em class="otp-setup-note ok">✓ <?php esc_html_e( 'Your account is set up', 'hda' ); ?></em>
							<?php else : ?>
								<em class="otp-setup-note warning">⚠ <?php esc_html_e( 'Your account is not set up', 'hda' ); ?></em>
							<?php endif; ?>
						</label>
						<label class="radio-label">
							<input type="radio" name="hda_login_security[otp_mode]" value="magic_link" <?php checked( $otp_mode, 'magic_link' ); ?>>
							<span><?php esc_html_e( 'Magic Link (Passwordless)', 'hda' ); ?></span>
							<em class="otp-setup-note ok hidden!"><?php esc_html_e( '✉ Email-based, no setup needed', 'hda' ); ?></em>
						</label>
					</div>
				</div>
			</div>
			<details class="hda-details mt-2">
				<summary class="hda-details__summary">
					<span class="dashicons dashicons-editor-help"></span>
					<?php esc_html_e( 'Available Verification Modes', 'hda' ); ?>
				</summary>
				<div class="hda-details__content">
					<ul class="pl-6 list-disc mt-2">
						<li><strong>Email OTP</strong> — one-time code sent via email after password login</li>
						<li><strong>SMS / Messaging</strong> — OTP via Telegram, Zalo, WhatsApp, SMSGate, Viber, LINE, or Discord (requires gateway setup). <a href="<?php echo esc_url( get_edit_profile_url( $user_id ) . '#hda-otp-section' ); ?>"><?php _e( 'Configure your account →', 'hda' ); ?></a></li>
						<li><strong>TOTP</strong> — time-based code from authenticator apps (Google Authenticator, Authy, etc.). <a href="<?php echo esc_url( get_edit_profile_url( $user_id ) . '#hda-totp-section' ); ?>"><?php _e( 'Set up your account →', 'hda' ); ?></a></li>
						<li><strong>Magic Link</strong> — <em>replaces</em> the password form entirely with email-based login</li>
					</ul>
				</div>
			</details>
		</div>

		<!-- Gateway Selector (visible when SMS mode) -->
		<div class="section section-select otp-sms-only my-6" style="<?php echo $otp_mode !== 'sms' ? 'display:none;' : ''; ?>">
			<label class="heading" for="otp_gateway">
				<?php esc_html_e( 'SMS Gateway', 'hda' ); ?>
				<span class="hda-tip">
					<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
					<span class="hda-tip__body"><?php esc_html_e( 'Select the gateway to send OTP messages.', 'hda' ); ?></span>
				</span>
			</label>
			<div class="option">
				<div class="controls">
					<div class="select_wrapper">
						<select class="select" name="hda_login_security[otp_gateway]" id="otp_gateway">
							<?php foreach ( $available_gateways as $gateway_key => $gateway_label ) : ?>
								<option value="<?php echo esc_attr( $gateway_key ); ?>" <?php selected( $otp_gateway, $gateway_key ); ?>>
									<?php echo esc_html( $gateway_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<!-- Telegram Config -->
		<div class="section section-text otp-gateway-config gateway-telegram mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'telegram' ) ? 'display:none;' : ''; ?>">
			<label class="heading" for="otp_telegram_bot_token"><?php esc_html_e( 'Telegram Bot Token', 'hda' ); ?></label>
			<div class="option">
				<div class="controls">
					<input
						type="password"
						class="input !w[100%]"
						id="otp_telegram_bot_token"
						name="hda_login_security[otp_gateway_config][telegram][bot_token]"
						value="<?php echo esc_attr( $otp_gateway_config['telegram']['bot_token'] ?? '' ); ?>"
						placeholder="123456789:ABCdefGHIjklMNOpqrSTUvwxYZ"
					>
				</div>
			</div>
			<div class="mt-2 text-sm text-slate-500">
				<?php _e( 'Get token from <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer"><strong>@BotFather</strong></a>. Free and unlimited.', 'hda' ); ?>
				<details class="mt-1.5">
					<summary class="cursor-pointer text-wp-primary font-semibold"><?php _e( 'How to create a Telegram Bot', 'hda' ); ?></summary>
					<ol class="mt-2 pl-5 leading-relaxed">
						<li><?php _e( 'Open Telegram and search for <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer"><strong>@BotFather</strong></a> (or click the link).', 'hda' ); ?></li>
						<li><?php _e( 'Send the command <code>/newbot</code>.', 'hda' ); ?></li>
						<li><?php _e( 'Enter a <strong>display name</strong> for your bot (e.g., <em>My Site OTP</em>).', 'hda' ); ?></li>
						<li><?php _e( 'Enter a <strong>username</strong> ending with <code>bot</code> (e.g., <em>mysite_otp_bot</em>).', 'hda' ); ?></li>
						<li><?php _e( 'BotFather will reply with your <strong>Bot Token</strong> — copy and paste it into the field above.', 'hda' ); ?></li>
					</ol>
				</details>
			</div>
		</div>

		<!-- Zalo Config -->
		<div class="section section-text otp-gateway-config gateway-zalo mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'zalo' ) ? 'display:none;' : ''; ?>">
			<label class="heading" for="otp_zalo_bot_token"><?php esc_html_e( 'Zalo Bot Token', 'hda' ); ?></label>
			<div class="option">
				<div class="controls">
					<input
						type="password"
						class="input !w[100%]"
						id="otp_zalo_bot_token"
						name="hda_login_security[otp_gateway_config][zalo][bot_token]"
						value="<?php echo esc_attr( $otp_gateway_config['zalo']['bot_token'] ?? '' ); ?>"
						placeholder="123456789:ABCdefGHIjklMNOpqrSTUvwxYZ"
					>
				</div>
			</div>
			<div class="mt-2 text-sm text-slate-500">
				<?php _e( 'Token from <a href="https://bot.zapps.me" target="_blank" rel="noopener noreferrer"><strong>Zalo Bot Platform</strong></a>.', 'hda' ); ?>
				<details class="mt-1.5">
					<summary class="cursor-pointer text-wp-primary font-semibold"><?php _e( 'How to set up Zalo Bot', 'hda' ); ?></summary>
					<ol class="mt-2 pl-5 leading-relaxed">
						<li><?php _e( 'Go to <a href="https://bot.zapps.me" target="_blank" rel="noopener noreferrer"><strong>bot.zapps.me</strong></a> and log in with your Zalo account.', 'hda' ); ?></li>
						<li><?php _e( 'Click <strong>Create Bot</strong> and fill in the bot details.', 'hda' ); ?></li>
						<li><?php _e( 'Copy the <strong>Bot Token</strong> from the dashboard and paste it above.', 'hda' ); ?></li>
					</ol>
					<p class="mt-1.5">
						<a href="https://bot.zapps.me/docs/" target="_blank" rel="noopener noreferrer"><?php _e( 'Official documentation →', 'hda' ); ?></a>
					</p>
				</details>
			</div>
		</div>

		<!-- WhatsApp Config (Meta Cloud API) -->
		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-text otp-gateway-config gateway-whatsapp mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'whatsapp' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_whatsapp_phone_number_id"><?php esc_html_e( 'Phone Number ID', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="text"
							class="input !w[100%]"
							id="otp_whatsapp_phone_number_id"
							name="hda_login_security[otp_gateway_config][whatsapp][phone_number_id]"
							value="<?php echo esc_attr( $otp_gateway_config['whatsapp']['phone_number_id'] ?? '' ); ?>"
							placeholder="123456789012345"
						>
					</div>
				</div>
				<div class="mt-2 text-sm text-slate-500"><?php esc_html_e( 'Your WhatsApp Business Phone Number ID from Meta Business Suite.', 'hda' ); ?></div>
			</div>
			<div class="section section-text otp-gateway-config gateway-whatsapp mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'whatsapp' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_whatsapp_access_token"><?php esc_html_e( 'Access Token', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="password"
							class="input !w[100%]"
							id="otp_whatsapp_access_token"
							name="hda_login_security[otp_gateway_config][whatsapp][access_token]"
							value="<?php echo esc_attr( $otp_gateway_config['whatsapp']['access_token'] ?? '' ); ?>"
						>
					</div>
				</div>
				<div class="mt-2 text-sm text-slate-500">
					<details class="mt-1.5">
						<summary class="cursor-pointer text-wp-primary font-semibold"><?php _e( 'How to set up WhatsApp Cloud API', 'hda' ); ?></summary>
						<ol class="mt-2 pl-5 leading-relaxed">
							<li><?php _e( 'Go to <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener noreferrer"><strong>Meta for Developers</strong></a> and create or select an app.', 'hda' ); ?></li>
							<li><?php _e( 'Add the <strong>WhatsApp</strong> product to your app.', 'hda' ); ?></li>
							<li><?php _e( 'In <strong>WhatsApp → API Setup</strong>, copy the <strong>Phone Number ID</strong> and paste it above.', 'hda' ); ?></li>
							<li><?php _e( 'Generate a <strong>Permanent Access Token</strong> (System User → Generate Token with <code>whatsapp_business_messaging</code> permission).', 'hda' ); ?></li>
							<li><?php _e( 'Paste the token into the <strong>Access Token</strong> field above.', 'hda' ); ?></li>
						</ol>
						<p class="mt-1.5">
							<a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank" rel="noopener noreferrer"><?php _e( 'Official documentation →', 'hda' ); ?></a>
						</p>
					</details>
				</div>
			</div>
		</div>

		<!-- SMSGate Config (Android SMS Gateway) -->
		<div class="container grid grid-cols-1 lg:grid-cols-3 gap-3 md:gap-6">
			<div class="section section-text otp-gateway-config gateway-smsgate" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'smsgate' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_smsgate_username"><?php esc_html_e( 'Username', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="text"
							class="input !w[100%]"
							id="otp_smsgate_username"
							name="hda_login_security[otp_gateway_config][smsgate][username]"
							value="<?php echo esc_attr( $otp_gateway_config['smsgate']['username'] ?? '' ); ?>"
						>
					</div>
				</div>
			</div>
			<div class="section section-text otp-gateway-config gateway-smsgate" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'smsgate' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_smsgate_password"><?php esc_html_e( 'Password', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="password"
							class="input !w[100%]"
							id="otp_smsgate_password"
							name="hda_login_security[otp_gateway_config][smsgate][password]"
							value="<?php echo esc_attr( $otp_gateway_config['smsgate']['password'] ?? '' ); ?>"
						>
					</div>
				</div>
			</div>
			<div class="section section-text otp-gateway-config gateway-smsgate" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'smsgate' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_smsgate_server_url"><?php esc_html_e( 'Server URL', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="url"
							class="input !w[100%]"
							id="otp_smsgate_server_url"
							name="hda_login_security[otp_gateway_config][smsgate][server_url]"
							value="<?php echo esc_attr( $otp_gateway_config['smsgate']['server_url'] ?? '' ); ?>"
							placeholder="https://api.sms-gate.app"
						>
					</div>
				</div>
			</div>
			<div class="section otp-gateway-config gateway-smsgate" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'smsgate' ) ? 'display:none;' : ''; ?>">
				<div class="mt-2 text-sm text-slate-500">
					<?php _e( 'Turn your Android phone into an SMS gateway. <strong>Free & unlimited</strong>. Sends real SMS using your phone\'s SIM.', 'hda' ); ?>
					<details class="mt-1.5">
						<summary class="cursor-pointer text-wp-primary font-semibold"><?php _e( 'How to set up SMSGate', 'hda' ); ?></summary>
						<ol class="mt-2 pl-5 leading-relaxed">
							<li><?php _e( 'Install <a href="https://sms-gate.app" target="_blank" rel="noopener noreferrer"><strong>SMSGate</strong></a> app on an Android phone (5.0+).', 'hda' ); ?></li>
							<li><?php _e( 'Open the app → select <strong>Cloud Mode</strong> (recommended).', 'hda' ); ?></li>
							<li><?php _e( 'Copy the <strong>Username</strong> and <strong>Password</strong> from the app\'s Home screen.', 'hda' ); ?></li>
							<li><?php _e( 'Paste them into the fields above. Leave Server URL empty for Cloud mode.', 'hda' ); ?></li>
							<li><?php _e( 'Keep the phone <strong>always on</strong> with battery optimization disabled for SMSGate.', 'hda' ); ?></li>
						</ol>
						<p class="mt-1.5">
							<a href="https://docs.sms-gate.app/getting-started/" target="_blank" rel="noopener noreferrer"><?php _e( 'Official documentation →', 'hda' ); ?></a>
						</p>
					</details>
				</div>
			</div>
		</div>

		<!-- Viber Config -->
		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-text otp-gateway-config gateway-viber" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'viber' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_viber_auth_token"><?php esc_html_e( 'Bot Auth Token', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="password"
							class="input !w[100%]"
							id="otp_viber_auth_token"
							name="hda_login_security[otp_gateway_config][viber][auth_token]"
							value="<?php echo esc_attr( $otp_gateway_config['viber']['auth_token'] ?? '' ); ?>"
						>
					</div>
				</div>
			</div>
			<div class="section section-text otp-gateway-config gateway-viber" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'viber' ) ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_viber_sender_name"><?php esc_html_e( 'Sender Name', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input
							type="text"
							class="input !w[100%]"
							id="otp_viber_sender_name"
							name="hda_login_security[otp_gateway_config][viber][sender_name]"
							value="<?php echo esc_attr( $otp_gateway_config['viber']['sender_name'] ?? '' ); ?>"
							placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
						>
					</div>
				</div>
			</div>
			<div class="section otp-gateway-config gateway-viber" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'viber' ) ? 'display:none;' : ''; ?>">
				<div class="mt-2 text-sm text-slate-500">
					<?php _e( '1:1 messages via Viber Bot are <strong>free & unlimited</strong>. Users must send a message to the bot first.', 'hda' ); ?>
					<details class="mt-1.5">
						<summary class="cursor-pointer text-wp-primary font-semibold"><?php _e( 'How to set up Viber Bot', 'hda' ); ?></summary>
						<ol class="mt-2 pl-5 leading-relaxed">
							<li><?php _e( 'Go to <a href="https://partners.viber.com/account/create-bot-account" target="_blank" rel="noopener noreferrer"><strong>Viber Admin Panel</strong></a> and create a bot.', 'hda' ); ?></li>
							<li><?php _e( 'Copy the <strong>Auth Token</strong> from the bot settings.', 'hda' ); ?></li>
							<li><?php _e( 'Set up a <strong>webhook</strong> (your site URL + endpoint) to receive user subscriptions.', 'hda' ); ?></li>
							<li><?php _e( 'Each user must <strong>open the bot in Viber</strong> and send a message — this registers their Viber User ID.', 'hda' ); ?></li>
						</ol>
						<p class="mt-1.5">
							<a href="https://developers.viber.com/docs/api/rest-bot-api/" target="_blank" rel="noopener noreferrer"><?php _e( 'Official documentation →', 'hda' ); ?></a>
						</p>
					</details>
				</div>
			</div>
		</div>

		<!-- LINE Config -->
		<div class="section section-text otp-gateway-config gateway-line mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'line' ) ? 'display:none;' : ''; ?>">
			<label class="heading" for="otp_line_channel_access_token"><?php esc_html_e( 'Channel Access Token', 'hda' ); ?></label>
			<div class="option">
				<div class="controls">
					<input
						type="password"
						class="input !w[100%]"
						id="otp_line_channel_access_token"
						name="hda_login_security[otp_gateway_config][line][channel_access_token]"
						value="<?php echo esc_attr( $otp_gateway_config['line']['channel_access_token'] ?? '' ); ?>"
					>
				</div>
			</div>
			<div class="mt-2 text-sm text-slate-500">
				<?php _e( 'Free tier: <strong>500 messages/month</strong>. Users must add the bot as a friend.', 'hda' ); ?>
				<details class="mt-1.5">
					<summary class="cursor-pointer text-wp-primary font-semibold"><?php _e( 'How to set up LINE Bot', 'hda' ); ?></summary>
					<ol class="mt-2 pl-5 leading-relaxed">
						<li><?php _e( 'Go to <a href="https://developers.line.biz/console/" target="_blank" rel="noopener noreferrer"><strong>LINE Developers Console</strong></a> and create a provider.', 'hda' ); ?></li>
						<li><?php _e( 'Create a <strong>Messaging API</strong> channel.', 'hda' ); ?></li>
						<li><?php _e( 'In the channel settings, issue a <strong>Channel Access Token</strong> (long-lived).', 'hda' ); ?></li>
						<li><?php _e( 'Set up a <strong>webhook URL</strong> to capture user events and get LINE User IDs.', 'hda' ); ?></li>
						<li><?php _e( 'Each user must <strong>add the bot as a friend</strong> on LINE.', 'hda' ); ?></li>
					</ol>
					<p class="mt-1.5">
						<a href="https://developers.line.biz/en/docs/messaging-api/" target="_blank" rel="noopener noreferrer"><?php _e( 'Official documentation →', 'hda' ); ?></a>
					</p>
				</details>
			</div>
		</div>

		<!-- Discord Config -->
		<div class="section section-text otp-gateway-config gateway-discord mb-6!" style="<?php echo ( $otp_mode !== 'sms' || $otp_gateway !== 'discord' ) ? 'display:none;' : ''; ?>">
			<label class="heading" for="otp_discord_bot_token"><?php esc_html_e( 'Discord Bot Token', 'hda' ); ?></label>
			<div class="option">
				<div class="controls">
					<input
						type="password"
						class="input !w[100%]"
						id="otp_discord_bot_token"
						name="hda_login_security[otp_gateway_config][discord][bot_token]"
						value="<?php echo esc_attr( $otp_gateway_config['discord']['bot_token'] ?? '' ); ?>"
					>
				</div>
			</div>
			<div class="mt-2 text-sm text-slate-500">
				<?php _e( '<strong>Free & unlimited</strong>. Sends OTP as a Direct Message (DM). Users must share a server with the bot.', 'hda' ); ?>
				<details class="mt-1.5">
					<summary class="cursor-pointer text-wp-primary font-semibold"><?php _e( 'How to set up Discord Bot', 'hda' ); ?></summary>
					<ol class="mt-2 pl-5 leading-relaxed">
						<li><?php _e( 'Go to <a href="https://discord.com/developers/applications" target="_blank" rel="noopener noreferrer"><strong>Discord Developer Portal</strong></a> → <strong>New Application</strong>.', 'hda' ); ?></li>
						<li><?php _e( 'Go to <strong>Bot</strong> tab → click <strong>Add Bot</strong> → copy the <strong>Bot Token</strong>.', 'hda' ); ?></li>
						<li><?php _e( 'Under <strong>Privileged Gateway Intents</strong>, enable <strong>Server Members Intent</strong>.', 'hda' ); ?></li>
						<li><?php _e( 'Go to <strong>OAuth2 → URL Generator</strong>, select <code>bot</code> scope, then invite the bot to a shared server.', 'hda' ); ?></li>
						<li><?php _e( 'Users provide their <strong>Discord User ID</strong>: enable Developer Mode (Settings → Advanced), then right-click username → <strong>Copy User ID</strong>.', 'hda' ); ?></li>
					</ol>
					<p class="mt-1.5">
						<a href="https://discord.com/developers/docs/intro" target="_blank" rel="noopener noreferrer"><?php _e( 'Official documentation →', 'hda' ); ?></a>
					</p>
				</details>
			</div>
		</div>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">

			<!-- OTP User Roles (visible when not disabled) -->
			<div class="section section-select otp-enabled-only mt-6" style="<?php echo $otp_mode === 'disabled' ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_user_roles">
					<?php esc_html_e( 'Required for Roles', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php echo wp_kses_post( __( 'Only selected roles must verify. Default: Editor, Administrator.', 'hda' ) ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select multiple class="select select2 select2-multiple !w[100%]" name="hda_login_security[otp_user_roles]" id="otp_user_roles">
								<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
									<option value="<?php echo esc_attr( $role_key ); ?>" <?php echo in_array( $role_key, (array) $otp_user_roles, true ) ? 'selected' : ''; ?>>
										<?php echo esc_html( $role_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<!-- OTP IP Binding (visible when not disabled) -->
			<div class="section section-checkbox otp-enabled-only mt-6" style="<?php echo $otp_mode === 'disabled' ? 'display:none;' : ''; ?>">
				<label class="heading" for="otp_ip_binding"><?php esc_html_e( 'Device Trust with IP Binding', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_login_security[otp_ip_binding]" id="otp_ip_binding" <?php checked( $otp_ip_binding, 1 ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Bind trusted device sessions to IP', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'What does this do?', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p><?php echo wp_kses_post( __( 'Device trust bound to IP — IP change forces re-verification.', 'hda' ) ); ?></p>
						<p class="mt-2 text-red-600 font-medium">⚠️ <?php esc_html_e( 'Not for mobile/VPN users.', 'hda' ); ?></p>
					</div>
				</details>
			</div>
		</div>
	</fieldset>
</div>

