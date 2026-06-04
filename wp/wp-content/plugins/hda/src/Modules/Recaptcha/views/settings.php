<?php
/**
 * CAPTCHA module options panel.
 *
 * Supports Google reCAPTCHA v2 and Cloudflare Turnstile.
 * Simplified: single "Protect All WP Forms" toggle instead of 4 individual form checkboxes.
 *
 * @package HDAddons\Modules\Recaptcha
 */

use HDAddons\Modules\Recaptcha\RecaptchaModule;

\defined( 'ABSPATH' ) || exit;

$recaptcha_options = RecaptchaModule::getOptions();

// Google reCAPTCHA v2.
$recaptcha_v2_site_key   = $recaptcha_options[ RecaptchaModule::KEY_V2_SITE_KEY ] ?? '';
$recaptcha_v2_secret_key = $recaptcha_options[ RecaptchaModule::KEY_V2_SECRET_KEY ] ?? '';

// Cloudflare Turnstile.
$turnstile_site_key   = $recaptcha_options[ RecaptchaModule::KEY_TURNSTILE_SITE_KEY ] ?? '';
$turnstile_secret_key = $recaptcha_options[ RecaptchaModule::KEY_TURNSTILE_SECRET_KEY ] ?? '';

// General.
$recaptcha_allowlist_ips = $recaptcha_options[ RecaptchaModule::KEY_ALLOWLIST_IPS ] ?? [];

// Provider & form protection.
$captcha_provider = $recaptcha_options[ RecaptchaModule::KEY_CAPTCHA_PROVIDER ] ?? '';
$protect_forms    = ! empty( $recaptcha_options[ RecaptchaModule::KEY_PROTECT_FORMS ] );

$hasRecaptchaKeys = ! empty( $recaptcha_v2_site_key ) && ! empty( $recaptcha_v2_secret_key );
$hasTurnstileKeys = ! empty( $turnstile_site_key ) && ! empty( $turnstile_secret_key );

?>
<div class="container">

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- API KEYS -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'API Keys', 'hda' ); ?></legend>

		<!-- Google reCAPTCHA v2 -->
		<h4 class="section-subtitle"><?php esc_html_e( 'Google reCAPTCHA v2', 'hda' ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'reCAPTCHA v2 (Checkbox) for form protection.', 'hda' ); ?>
				<a target="_blank" href="https://www.google.com/recaptcha/admin"><?php esc_html_e( 'Get keys →', 'hda' ); ?></a>
			</p>
		</div>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-text">
				<label class="heading" for="recaptcha_v2_site_key"><?php esc_html_e( 'Site Key', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $recaptcha_v2_site_key ); ?>" class="input" type="text" id="recaptcha_v2_site_key" name="hda_recaptcha[recaptcha_v2_site_key]">
					</div>
				</div>
			</div>

			<div class="section section-text">
				<label class="heading inline-heading" for="recaptcha_v2_secret_key"><?php esc_html_e( 'Secret Key', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $recaptcha_v2_secret_key ); ?>" class="input" type="text" id="recaptcha_v2_secret_key" name="hda_recaptcha[recaptcha_v2_secret_key]">
					</div>
				</div>
			</div>
		</div>

		<!-- Cloudflare Turnstile -->
		<h4 class="section-subtitle mt-6"><?php esc_html_e( 'Cloudflare Turnstile', 'hda' ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-cloud"></span>
				<?php esc_html_e( 'Free, privacy-friendly CAPTCHA by Cloudflare. No puzzles.', 'hda' ); ?>
				<a target="_blank" href="https://dash.cloudflare.com/?to=/:account/turnstile"><?php esc_html_e( 'Get keys →', 'hda' ); ?></a>
			</p>
		</div>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-text">
				<label class="heading" for="turnstile_site_key"><?php esc_html_e( 'Site Key', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $turnstile_site_key ); ?>" class="input" type="text" id="turnstile_site_key" name="hda_recaptcha[turnstile_site_key]" placeholder="0x4AAAAAAA...">
					</div>
				</div>
			</div>

			<div class="section section-text">
				<label class="heading inline-heading" for="turnstile_secret_key"><?php esc_html_e( 'Secret Key', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<input value="<?php echo esc_attr( $turnstile_secret_key ); ?>" class="input" type="text" id="turnstile_secret_key" name="hda_recaptcha[turnstile_secret_key]" placeholder="0x4AAAAAAA...">
					</div>
				</div>
			</div>
		</div>
	</fieldset>

	<!-- ══════════════════════════════════════════════════════════════════ -->
	<!-- FORM PROTECTION -->
	<!-- ══════════════════════════════════════════════════════════════════ -->
	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Form Protection', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-forms"></span>
				<?php esc_html_e( 'Select provider and enable form protection. Configure API keys above first.', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-select">
				<label class="heading" for="captcha_provider">
					<?php esc_html_e( 'CAPTCHA Provider', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Active CAPTCHA provider for form protection.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<div class="controls">
						<div class="select_wrapper">
							<select class="select" name="hda_recaptcha[captcha_provider]" id="captcha_provider">
								<option value="" <?php selected( $captcha_provider, '' ); ?>><?php esc_html_e( 'Disabled', 'hda' ); ?></option>
								<option value="recaptcha_v2" <?php selected( $captcha_provider, 'recaptcha_v2' ); ?> <?php disabled( ! $hasRecaptchaKeys ); ?>>
									<?php esc_html_e( 'Google reCAPTCHA v2', 'hda' ); ?>
									<?php if ( ! $hasRecaptchaKeys ) : ?>
										— <?php esc_html_e( '(keys not configured)', 'hda' ); ?>
									<?php endif; ?>
								</option>
								<option value="turnstile" <?php selected( $captcha_provider, 'turnstile' ); ?> <?php disabled( ! $hasTurnstileKeys ); ?>>
									<?php esc_html_e( 'Cloudflare Turnstile', 'hda' ); ?>
									<?php if ( ! $hasTurnstileKeys ) : ?>
										— <?php esc_html_e( '(keys not configured)', 'hda' ); ?>
									<?php endif; ?>
								</option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<!-- Protect All WP Forms (single toggle) -->
			<div class="section section-checkbox">
				<label class="heading" for="protect_forms"><?php esc_html_e( 'Protect All WP Forms', 'hda' ); ?></label>
				<div class="option">
					<div class="controls">
						<label class="flex items-center gap-2 cursor-pointer">
							<input type="checkbox" class="checkbox" name="hda_recaptcha[protect_forms]" id="protect_forms" <?php checked( $protect_forms ); ?> value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Add CAPTCHA to all WordPress forms', 'hda' ); ?></span>
						</label>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Protected Forms', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p><?php esc_html_e( 'Protects all of the following:', 'hda' ); ?></p>
						<ul class="pl-6 list-disc mt-2">
							<li><?php esc_html_e( 'Login Form', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Registration Form', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Lost Password Form', 'hda' ); ?></li>
							<li><?php esc_html_e( 'Comment Form', 'hda' ); ?></li>
						</ul>
					</div>
				</details>
			</div>
		</div>

		<!-- IP Allowlist -->
		<h4 class="section-subtitle mt-6"><?php esc_html_e( 'IP Allowlist', 'hda' ); ?></h4>
		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Listed IPs skip CAPTCHA — for dev, staging, or trusted networks.', 'hda' ); ?>
			</p>
		</div>
		<div class="section section-select">
			<label class="heading" for="recaptcha_allowlist_ips"><?php esc_html_e( 'Bypass CAPTCHA for IPs', 'hda' ); ?></label>
			<div class="option">
				<div class="controls">
					<div class="select_wrapper">
						<select multiple class="select select2-ips !w[100%]" name="hda_recaptcha[recaptcha_allowlist_ips][]" id="recaptcha_allowlist_ips">
							<?php foreach ( (array) $recaptcha_allowlist_ips as $ip ) : ?>
							<option selected value="<?php echo esc_attr( $ip ); ?>"><?php echo esc_html( $ip ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
			<details class="hda-details mt-2">
				<summary class="hda-details__summary">
					<span class="dashicons dashicons-editor-help"></span>
					<?php esc_html_e( 'Accepted Formats', 'hda' ); ?>
				</summary>
				<div class="hda-details__content">
					<p><?php esc_html_e( 'Bypass CAPTCHA for these IPs. Formats:', 'hda' ); ?></p>
					<ul class="pl-6 list-disc mt-2">
						<li><?php echo wp_kses_post( __( 'Single IPv4: <code>192.168.1.1</code>', 'hda' ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'Single IPv6: <code>2001:db8::1</code>', 'hda' ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'CIDR: <code>192.168.1.0/24</code>, <code>2001:db8::/32</code>', 'hda' ) ); ?></li>
						<li><?php echo wp_kses_post( __( 'Dash range: <code>192.168.1.1-100</code>', 'hda' ) ); ?></li>
					</ul>
				</div>
			</details>
		</div>
	</fieldset>
</div>
