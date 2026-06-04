<?php
/**
 * HD Forms Settings Page — Tabbed UI.
 *
 * Submenu under Form Entries → Settings.
 * Manages: CAPTCHA keys, email filters, notifications, spam, cleanup.
 *
 * @package HD\Modules\Form\Admin
 */

namespace HD\Modules\Form\Admin;

use HD\Modules\Form\FormConfig;

defined( 'ABSPATH' ) || exit;

final class FormSettingsPage {

	private const OPTION_KEY = 'hd_form_settings';
	private const MENU_SLUG  = 'hd-form-settings';

	/**
	 * Register admin hooks.
	 */
	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'addPage' ], 31 );
		add_action( 'admin_init', [ self::class, 'registerSettings' ] );
	}

	/**
	 * Add submenu page under Form Entries.
	 */
	public static function addPage(): void {
		$hook = add_submenu_page(
			'hd-form-entries',
			__( 'Settings', 'hd' ),
			__( 'Settings', 'hd' ),
			'manage_options',
			self::MENU_SLUG,
			[ self::class, 'renderPage' ]
		);

		add_action(
			'admin_print_styles-' . $hook,
			static function () {
				echo '<style>
				.hd-form-tab-content { display: none; }
				.hd-form-tab-content.active { display: block; }
				.hd-form-settings .nav-tab-wrapper { margin-bottom: 20px; }
				.hd-captcha-provider { border: 1px solid #c3c4c7; padding: 16px 20px; margin-bottom: 12px; border-radius: 4px; background: #f9f9f9; }
				.hd-captcha-provider h3 { margin: 0 0 10px; font-size: 14px; }
				.hd-captcha-provider .form-table { margin: 0; }
				.hd-captcha-provider .form-table th { padding: 8px 10px 8px 0; width: 140px; }
				.hd-captcha-provider .form-table td { padding: 8px 0; }
				.hd-channel-block { border: 1px solid #c3c4c7; padding: 16px 20px; margin-bottom: 12px; border-radius: 4px; background: #f9f9f9; }
				.hd-channel-block h3 { margin: 0 0 10px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
				.hd-channel-block .form-table { margin: 0; }
				.hd-channel-block .form-table th { padding: 8px 10px 8px 0; width: 140px; }
				.hd-channel-block .form-table td { padding: 8px 0; }
				.hd-channel-fields { margin-top: 8px; }
				.hd-email-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
				.hd-email-tags .hd-tag { background: #2271b1; color: #fff; padding: 4px 8px; border-radius: 3px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; }
				.hd-email-tags .hd-tag button { background: none; border: none; color: #fff; cursor: pointer; font-size: 14px; line-height: 1; padding: 0; }
			</style>';
			}
		);
	}

	/**
	 * Register the single option for all settings.
	 */
	public static function registerSettings(): void {
		register_setting(
			self::MENU_SLUG,
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ self::class, 'sanitize' ],
				'default'           => [],
			]
		);
	}

	/** ── Sanitize ─────────────────────────────────────────────── */

	/**
	 * Sanitize all settings on save.
	 *
	 * @param array $input Raw input.
	 *
	 * @return array Sanitized.
	 */
	public static function sanitize( array $input ): array {
		$clean    = [];
		$existing = get_option( self::OPTION_KEY, [] );
		$existing = is_array( $existing ) ? $existing : [];

		// General — Default email recipients.
		$clean['default_email_to'] = self::parseLines( $input['default_email_to'] ?? '' );
		$clean['default_email_to'] = array_filter( $clean['default_email_to'], 'is_email' );

		// General — Email domain filter.
		$clean['email_filter'] = [
			'deny_domains'  => self::sanitizeDomainList( (string) ( $input['email_deny_domains'] ?? '' ) ),
			'allow_domains' => self::sanitizeDomainList( (string) ( $input['email_allow_domains'] ?? '' ) ),
		];

		// CAPTCHA.
		$clean['captcha'] = [
			'default_provider'           => sanitize_key( $input['captcha_default_provider'] ?? 'none' ),
			'fail_open_on_network_error' => ! empty( $input['captcha_fail_open_on_network_error'] ),
			'recaptcha_v2'               => [
				'site_key'   => sanitize_text_field( $input['recaptcha_v2_site_key'] ?? '' ),
				'secret_key' => self::sanitizeSecret( $input, 'recaptcha_v2_secret_key', $existing['captcha']['recaptcha_v2']['secret_key'] ?? '' ),
			],
			'recaptcha_v3'               => [
				'site_key'        => sanitize_text_field( $input['recaptcha_v3_site_key'] ?? '' ),
				'secret_key'      => self::sanitizeSecret( $input, 'recaptcha_v3_secret_key', $existing['captcha']['recaptcha_v3']['secret_key'] ?? '' ),
				'score_threshold' => min( 1.0, max( 0.0, (float) ( $input['recaptcha_v3_score_threshold'] ?? 0.5 ) ) ),
			],
			'turnstile'                  => [
				'site_key'   => sanitize_text_field( $input['turnstile_site_key'] ?? '' ),
				'secret_key' => self::sanitizeSecret( $input, 'turnstile_secret_key', $existing['captcha']['turnstile']['secret_key'] ?? '' ),
			],
		];

		$existingEmailEnabled = (bool) ( $existing['notifications']['channels']['email']['enabled'] ?? true );

		// Notifications.
		$clean['notifications'] = [
			'channels' => [
				'email'    => [
					'enabled' => array_key_exists( 'notify_email', $input ) ? ! empty( $input['notify_email'] ) : $existingEmailEnabled,
				],
				'telegram' => [
					'enabled'   => ! empty( $input['notify_telegram'] ),
					'bot_token' => self::sanitizeSecret( $input, 'telegram_bot_token', $existing['notifications']['channels']['telegram']['bot_token'] ?? '' ),
					'chat_id'   => sanitize_text_field( $input['telegram_chat_id'] ?? '' ),
				],
				'viber'    => [
					'enabled'    => ! empty( $input['notify_viber'] ),
					'auth_token' => self::sanitizeSecret( $input, 'viber_auth_token', $existing['notifications']['channels']['viber']['auth_token'] ?? '' ),
					'receiver'   => sanitize_text_field( $input['viber_receiver'] ?? '' ),
					'sender'     => [
						'name'   => sanitize_text_field( $input['viber_sender_name'] ?? 'HD Notify' ),
						'avatar' => sanitize_url( $input['viber_sender_avatar'] ?? '' ),
					],
				],
				'zalo'     => [
					'enabled'   => ! empty( $input['notify_zalo'] ),
					'bot_token' => self::sanitizeSecret( $input, 'zalo_bot_token', $existing['notifications']['channels']['zalo']['bot_token'] ?? '' ),
					'chat_id'   => sanitize_text_field( $input['zalo_chat_id'] ?? '' ),
				],
			],
		];

		// Spam.
		$clean['spam_check']      = ! empty( $input['spam_check'] );
		$clean['min_submit_time'] = absint( $input['min_submit_time'] ?? 3 );
		$clean['max_render_age']  = min( 86400, absint( $input['max_render_age'] ?? 1800 ) );
		$clean['phone_vn_only']   = ! empty( $input['phone_vn_only'] );

		// Cleanup.
		$clean['cleanup'] = [
			'trash_days'      => max( 1, absint( $input['trash_days'] ?? 30 ) ),
			'mail_queue_days' => max( 1, absint( $input['mail_queue_days'] ?? 60 ) ),
			'log_days'        => max( 1, absint( $input['log_days'] ?? 180 ) ),
		];

		// Weekly Digest.
		$digestRecipients       = self::parseLines( $input['digest_recipients'] ?? '' );
		$clean['weekly_digest'] = [
			'enabled'    => ! empty( $input['digest_enabled'] ),
			'recipients' => array_filter( $digestRecipients, 'is_email' ),
			'day'        => self::sanitizeDigestDay( $input['digest_day'] ?? 'monday' ),
		];

		FormConfig::resetCache();

		return $clean;
	}

	/** ── Render ───────────────────────────────────────────────── */

	/**
	 * Render the tabbed settings page.
	 */
	public static function renderPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = get_option( self::OPTION_KEY, [] );

		$tabs = [
			'general'       => __( 'General', 'hd' ),
			'captcha'       => __( 'CAPTCHA', 'hd' ),
			'notifications' => __( 'Notifications', 'hd' ),
			'spam'          => __( 'Spam & Validation', 'hd' ),
			'cleanup'       => __( 'Cleanup', 'hd' ),
		];

		?>
		<div class="wrap hd-form-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php self::renderCaptchaAdminNotices( $options ); ?>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a href="#" class="nav-tab<?php echo 'general' === $slug ? ' nav-tab-active' : ''; ?>" data-tab="hd-tab-<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php">
				<?php settings_fields( self::MENU_SLUG ); ?>

				<?php self::renderGeneralTab( $options ); ?>
				<?php self::renderCaptchaTab( $options ); ?>
				<?php self::renderNotificationsTab( $options ); ?>
				<?php self::renderSpamTab( $options ); ?>
				<?php self::renderCleanupTab( $options ); ?>

				<?php submit_button(); ?>
			</form>
		</div>

		<script>
		(function() {
			const wrap = document.querySelector('.hd-form-settings');
			if (!wrap) return;
			wrap.querySelectorAll('.nav-tab').forEach(tab => {
				tab.addEventListener('click', e => {
					e.preventDefault();
					wrap.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
					wrap.querySelectorAll('.hd-form-tab-content').forEach(c => c.classList.remove('active'));
					tab.classList.add('nav-tab-active');
					const target = wrap.querySelector('#' + tab.dataset.tab);
					if (target) target.classList.add('active');
				});
			});
		})();
		</script>
		<?php
	}

	/** ── Tab: General ─────────────────────────────────────────── */

	private static function renderGeneralTab( array $options ): void {
		$emails       = implode( "\n", $options['default_email_to'] ?? [] );
		$denyDomains  = implode( "\n", $options['email_filter']['deny_domains'] ?? [] );
		$allowDomains = implode( "\n", $options['email_filter']['allow_domains'] ?? [] );
		$optKey       = self::OPTION_KEY;

		?>
		<div class="hd-form-tab-content active" id="hd-tab-general">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="hd-default-emails"><?php esc_html_e( 'Default Email Recipients', 'hd' ); ?></label></th>
					<td>
						<textarea name="<?php echo esc_attr( $optKey ); ?>[default_email_to]" id="hd-default-emails" rows="3" cols="50" class="large-text code" placeholder="admin@example.com&#10;sales@example.com"><?php echo esc_textarea( $emails ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Fallback recipients when a form type has none configured. One email per line.', 'hd' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Email Domain Filter', 'hd' ); ?></h2>
			<p><?php esc_html_e( 'Control which email domains are allowed or blocked. One domain per line.', 'hd' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Deny Domains', 'hd' ); ?></th>
					<td>
						<textarea name="<?php echo esc_attr( $optKey ); ?>[email_deny_domains]" rows="4" cols="50" class="large-text code" placeholder="mailinator.com&#10;guerrillamail.com"><?php echo esc_textarea( $denyDomains ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Submissions from these email domains will be rejected.', 'hd' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Allow Domains', 'hd' ); ?></th>
					<td>
						<textarea name="<?php echo esc_attr( $optKey ); ?>[email_allow_domains]" rows="4" cols="50" class="large-text code" placeholder="company.com&#10;partner.com"><?php echo esc_textarea( $allowDomains ); ?></textarea>
						<p class="description"><?php esc_html_e( 'If set, ONLY these domains are accepted. Leave empty to allow all (except deny list).', 'hd' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/** ── Tab: CAPTCHA ──────────────────────────────────────────── */

	private static function renderCaptchaTab( array $options ): void {
		$captcha  = $options['captcha'] ?? [];
		$provider = $captcha['default_provider'] ?? 'none';
		$optKey   = self::OPTION_KEY;

		$providers = [
			'none'         => __( 'None (disabled)', 'hd' ),
			'recaptcha_v2' => __( 'Google reCAPTCHA v2', 'hd' ),
			'recaptcha_v3' => __( 'Google reCAPTCHA v3', 'hd' ),
			'turnstile'    => __( 'Cloudflare Turnstile', 'hd' ),
		];

		?>
		<div class="hd-form-tab-content" id="hd-tab-captcha">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="hd-captcha-provider"><?php esc_html_e( 'Default Provider', 'hd' ); ?></label></th>
					<td>
						<select name="<?php echo esc_attr( $optKey ); ?>[captcha_default_provider]" id="hd-captcha-provider">
							<?php foreach ( $providers as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $provider, $slug ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Global default. Individual form types can override this in code config.', 'hd' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Network Error Policy', 'hd' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $optKey ); ?>[captcha_fail_open_on_network_error]" value="1" <?php checked( ! empty( $captcha['fail_open_on_network_error'] ) ); ?>>
							<?php esc_html_e( 'Allow submissions when CAPTCHA provider verification has a network error.', 'hd' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Default is fail-closed. Enable only when provider outages are a larger business risk than spam.', 'hd' ); ?></p>
					</td>
				</tr>
			</table>

			<?php
			self::renderCaptchaProvider( 'reCAPTCHA v2', 'recaptcha_v2', $captcha, $optKey );
			self::renderCaptchaProvider( 'reCAPTCHA v3', 'recaptcha_v3', $captcha, $optKey, true );
			self::renderCaptchaProvider( 'Cloudflare Turnstile', 'turnstile', $captcha, $optKey );
			?>
		</div>
		<?php
	}

	/**
	 * Render a CAPTCHA provider card.
	 */
	private static function renderCaptchaProvider( string $title, string $slug, array $captcha, string $optKey, bool $hasThreshold = false ): void {
		$siteKey = $captcha[ $slug ]['site_key'] ?? '';

		?>
		<div class="hd-captcha-provider">
			<h3><?php echo esc_html( $title ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Site Key', 'hd' ); ?></th>
					<td><input type="text" name="<?php echo esc_attr( $optKey ); ?>[<?php echo esc_attr( $slug ); ?>_site_key]" value="<?php echo esc_attr( $siteKey ); ?>" class="regular-text code"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Secret Key', 'hd' ); ?></th>
					<td>
						<input type="password" name="<?php echo esc_attr( $optKey ); ?>[<?php echo esc_attr( $slug ); ?>_secret_key]" value="" class="regular-text code" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to keep existing.', 'hd' ); ?>">
						<p class="description"><?php esc_html_e( 'Leave blank to keep the saved secret key.', 'hd' ); ?></p>
					</td>
				</tr>
				<?php if ( $hasThreshold ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Score Threshold', 'hd' ); ?></th>
					<td>
						<input type="number" name="<?php echo esc_attr( $optKey ); ?>[recaptcha_v3_score_threshold]" value="<?php echo esc_attr( $captcha['recaptcha_v3']['score_threshold'] ?? 0.5 ); ?>" min="0" max="1" step="0.1" class="small-text">
						<span class="description"><?php esc_html_e( '0.0 (allow all) → 1.0 (strictest)', 'hd' ); ?></span>
					</td>
				</tr>
				<?php endif; ?>
			</table>
		</div>
		<?php
	}

	/** ── Tab: Notifications ───────────────────────────────────── */

	private static function renderCaptchaAdminNotices( array $options ): void {
		foreach ( self::captchaProviderKeyWarnings( $options ) as $warning ) {
			echo '<div class="notice notice-warning"><p>' . esc_html( $warning ) . '</p></div>';
		}
	}

	/**
	 * @return array<int, string>
	 */
	private static function captchaProviderKeyWarnings( array $options ): array {
		$captcha  = $options['captcha'] ?? [];
		$provider = $captcha['default_provider'] ?? 'none';
		if ( 'none' === $provider || '' === $provider ) {
			return [];
		}

		$providerConfig = $captcha[ $provider ] ?? [];
		if ( ! empty( $providerConfig['site_key'] ) && ! empty( $providerConfig['secret_key'] ) ) {
			return [];
		}

		return [
			sprintf( 'CAPTCHA provider "%s" is configured but missing site key or secret key.', $provider ),
		];
	}

	private static function renderNotificationsTab( array $options ): void {
		$channels = $options['notifications']['channels'] ?? [];
		$digest   = $options['weekly_digest'] ?? [];
		$optKey   = self::OPTION_KEY;

		$emailEnabled    = $channels['email']['enabled'] ?? true;
		$telegramEnabled = ! empty( $channels['telegram']['enabled'] );
		$viberEnabled    = ! empty( $channels['viber']['enabled'] );
		$zaloEnabled     = ! empty( $channels['zalo']['enabled'] );

		?>
		<div class="hd-form-tab-content" id="hd-tab-notifications">
			<p><?php esc_html_e( 'Enable channels and configure credentials. Email is always available.', 'hd' ); ?></p>

			<!-- Email -->
			<div class="hd-channel-block">
				<h3>
					<input type="hidden" name="<?php echo esc_attr( $optKey ); ?>[notify_email]" value="0">
					<label><input type="checkbox" name="<?php echo esc_attr( $optKey ); ?>[notify_email]" value="1" <?php checked( $emailEnabled ); ?>> <?php esc_html_e( 'Email', 'hd' ); ?></label>
				</h3>
				<p class="description"><?php esc_html_e( 'Uses wp_mail(). Recipients are configured per form type or via Default Email Recipients in General tab.', 'hd' ); ?></p>
			</div>

			<!-- Telegram -->
			<div class="hd-channel-block">
				<h3>
					<label><input type="checkbox" name="<?php echo esc_attr( $optKey ); ?>[notify_telegram]" value="1" <?php checked( $telegramEnabled ); ?>> <?php esc_html_e( 'Telegram', 'hd' ); ?></label>
				</h3>
				<div class="hd-channel-fields">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Bot Token', 'hd' ); ?></th>
							<td>
								<input type="password" name="<?php echo esc_attr( $optKey ); ?>[telegram_bot_token]" value="" class="regular-text code" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to keep existing.', 'hd' ); ?>">
								<p class="description"><?php esc_html_e( 'Leave blank to keep the saved bot token.', 'hd' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Chat ID', 'hd' ); ?></th>
							<td><input type="text" name="<?php echo esc_attr( $optKey ); ?>[telegram_chat_id]" value="<?php echo esc_attr( $channels['telegram']['chat_id'] ?? '' ); ?>" class="regular-text code"></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Viber -->
			<div class="hd-channel-block">
				<h3>
					<label><input type="checkbox" name="<?php echo esc_attr( $optKey ); ?>[notify_viber]" value="1" <?php checked( $viberEnabled ); ?>> <?php esc_html_e( 'Viber', 'hd' ); ?></label>
				</h3>
				<div class="hd-channel-fields">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Auth Token', 'hd' ); ?></th>
							<td>
								<input type="password" name="<?php echo esc_attr( $optKey ); ?>[viber_auth_token]" value="" class="regular-text code" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to keep existing.', 'hd' ); ?>">
								<p class="description"><?php esc_html_e( 'Leave blank to keep the saved auth token.', 'hd' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Receiver', 'hd' ); ?></th>
							<td><input type="text" name="<?php echo esc_attr( $optKey ); ?>[viber_receiver]" value="<?php echo esc_attr( $channels['viber']['receiver'] ?? '' ); ?>" class="regular-text code"></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Sender Name', 'hd' ); ?></th>
							<td><input type="text" name="<?php echo esc_attr( $optKey ); ?>[viber_sender_name]" value="<?php echo esc_attr( $channels['viber']['sender']['name'] ?? 'HD Notify' ); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Sender Avatar URL', 'hd' ); ?></th>
							<td><input type="url" name="<?php echo esc_attr( $optKey ); ?>[viber_sender_avatar]" value="<?php echo esc_url( $channels['viber']['sender']['avatar'] ?? '' ); ?>" class="regular-text code"></td>
						</tr>
					</table>
				</div>
			</div>
			<!-- Zalo -->
			<div class="hd-channel-block">
				<h3>
					<label><input type="checkbox" name="<?php echo esc_attr( $optKey ); ?>[notify_zalo]" value="1" <?php checked( $zaloEnabled ); ?>> <?php esc_html_e( 'Zalo', 'hd' ); ?></label>
				</h3>
				<div class="hd-channel-fields">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Bot Token', 'hd' ); ?></th>
							<td>
								<input type="password" name="<?php echo esc_attr( $optKey ); ?>[zalo_bot_token]" value="" class="regular-text code" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to keep existing.', 'hd' ); ?>">
								<p class="description"><?php esc_html_e( 'Leave blank to keep the saved bot token.', 'hd' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Chat ID', 'hd' ); ?></th>
							<td><input type="text" name="<?php echo esc_attr( $optKey ); ?>[zalo_chat_id]" value="<?php echo esc_attr( $channels['zalo']['chat_id'] ?? '' ); ?>" class="regular-text code"></td>
						</tr>
					</table>
				</div>
			</div>
			<!-- Weekly Digest -->
			<div class="hd-channel-block">
				<h3>
					<label><input type="checkbox" name="<?php echo esc_attr( $optKey ); ?>[digest_enabled]" value="1" <?php checked( ! empty( $digest['enabled'] ) ); ?>> <?php esc_html_e( 'Weekly Email Summary', 'hd' ); ?></label>
				</h3>
				<div class="hd-channel-fields">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Recipients', 'hd' ); ?></th>
							<td>
								<textarea name="<?php echo esc_attr( $optKey ); ?>[digest_recipients]" rows="2" cols="50" class="large-text code" placeholder="admin@example.com"><?php echo esc_textarea( implode( "\n", $digest['recipients'] ?? [] ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'One email per line. Defaults to admin email if empty.', 'hd' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Send Day', 'hd' ); ?></th>
							<td>
								<select name="<?php echo esc_attr( $optKey ); ?>[digest_day]">
									<?php
									$days = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
									foreach ( $days as $d ) :
										?>
										<option value="<?php echo esc_attr( $d ); ?>" <?php selected( $digest['day'] ?? 'monday', $d ); ?>><?php echo esc_html( ucfirst( $d ) ); ?></option>
									<?php endforeach; ?>
								</select>
								<span class="description"><?php esc_html_e( 'at 8:00 AM', 'hd' ); ?></span>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/** ── Tab: Spam & Validation ────────────────────────────────── */

	private static function renderSpamTab( array $options ): void {
		$optKey = self::OPTION_KEY;

		?>
		<div class="hd-form-tab-content" id="hd-tab-spam">
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Spam Check', 'hd' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $optKey ); ?>[spam_check]" value="1" <?php checked( $options['spam_check'] ?? true ); ?>>
							<?php esc_html_e( 'Enable global spam detection', 'hd' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Checks submissions against Akismet (if active) and WordPress Disallowed Words list (Settings → Discussion). Individual form types can override this in their code config.', 'hd' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hd-min-submit"><?php esc_html_e( 'Minimum Submit Time (seconds)', 'hd' ); ?></label></th>
					<td>
						<input type="number" name="<?php echo esc_attr( $optKey ); ?>[min_submit_time]" id="hd-min-submit" value="<?php echo (int) ( $options['min_submit_time'] ?? 3 ); ?>" min="0" max="30" class="small-text">
						<p class="description"><?php esc_html_e( 'Reject submissions faster than this. Set 0 to disable.', 'hd' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hd-max-render-age"><?php esc_html_e( 'Maximum Render Age (seconds)', 'hd' ); ?></label></th>
					<td>
						<input type="number" name="<?php echo esc_attr( $optKey ); ?>[max_render_age]" id="hd-max-render-age" value="<?php echo (int) ( $options['max_render_age'] ?? 1800 ); ?>" min="0" max="86400" class="small-text">
						<p class="description"><?php esc_html_e( 'Reject stale submissions rendered before this age. Set 0 to disable.', 'hd' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Vietnam Phone Only', 'hd' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $optKey ); ?>[phone_vn_only]" value="1" <?php checked( ! empty( $options['phone_vn_only'] ) ); ?>>
							<?php esc_html_e( 'Only accept Vietnamese phone numbers (0xx / +84xx).', 'hd' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/** ── Tab: Cleanup ──────────────────────────────────────────── */

	private static function renderCleanupTab( array $options ): void {
		$cleanup = $options['cleanup'] ?? [];
		$optKey  = self::OPTION_KEY;

		?>
		<div class="hd-form-tab-content" id="hd-tab-cleanup">
			<p><?php esc_html_e( 'Automatic monthly cleanup of old data. Set retention period in days.', 'hd' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="hd-trash-days"><?php esc_html_e( 'Trashed Entries', 'hd' ); ?></label></th>
					<td>
						<input type="number" name="<?php echo esc_attr( $optKey ); ?>[trash_days]" id="hd-trash-days" value="<?php echo (int) ( $cleanup['trash_days'] ?? 30 ); ?>" min="1" max="365" class="small-text">
						<span class="description"><?php esc_html_e( 'days', 'hd' ); ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hd-mail-days"><?php esc_html_e( 'Mail Queue (sent/failed)', 'hd' ); ?></label></th>
					<td>
						<input type="number" name="<?php echo esc_attr( $optKey ); ?>[mail_queue_days]" id="hd-mail-days" value="<?php echo (int) ( $cleanup['mail_queue_days'] ?? 60 ); ?>" min="1" max="730" class="small-text">
						<span class="description"><?php esc_html_e( 'days', 'hd' ); ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hd-log-days"><?php esc_html_e( 'Form Logs', 'hd' ); ?></label></th>
					<td>
						<input type="number" name="<?php echo esc_attr( $optKey ); ?>[log_days]" id="hd-log-days" value="<?php echo (int) ( $cleanup['log_days'] ?? 180 ); ?>" min="1" max="730" class="small-text">
						<span class="description"><?php esc_html_e( 'days', 'hd' ); ?></span>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/** ── Helpers ───────────────────────────────────────────────── */

	/**
	 * Parse textarea lines to trimmed, non-empty array.
	 *
	 * @param string $text Raw textarea.
	 *
	 * @return array
	 */
	private static function parseLines( string $text ): array {
		$lines = explode( "\n", sanitize_textarea_field( $text ) );
		$lines = array_map( 'trim', $lines );

		return array_values( array_filter( $lines ) );
	}

	/**
	 * Sanitize a textarea domain list.
	 *
	 * @param string $text Raw textarea.
	 *
	 * @return array<int, string>
	 */
	private static function sanitizeDomainList( string $text ): array {
		$domains = [];

		foreach ( self::parseLines( $text ) as $line ) {
			$domain = self::sanitizeDomain( $line );
			if ( '' !== $domain ) {
				$domains[ $domain ] = $domain;
			}
		}

		return array_values( $domains );
	}

	private static function sanitizeDomain( string $domain ): string {
		$domain = strtolower( trim( $domain ) );
		$domain = ltrim( $domain, '@' );

		if ( str_contains( $domain, '://' ) ) {
			$host   = wp_parse_url( $domain, PHP_URL_HOST );
			$domain = is_string( $host ) ? $host : '';
		}

		$domain = preg_replace( '/:\d+$/', '', $domain );
		$domain = trim( (string) $domain, ". \t\n\r\0\x0B" );

		if ( '' === $domain || strlen( $domain ) > 253 || str_contains( $domain, '..' ) ) {
			return '';
		}

		return (bool) preg_match( '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])$/', $domain )
			? $domain
			: '';
	}

	/**
	 * Sanitize a secret field while preserving the existing value on blank input.
	 *
	 * @param array  $input    Raw settings input.
	 * @param string $key      Input key.
	 * @param mixed  $existing Existing saved secret.
	 */
	private static function sanitizeSecret( array $input, string $key, mixed $existing ): string {
		$value = isset( $input[ $key ] ) ? sanitize_text_field( (string) $input[ $key ] ) : '';

		return '' !== $value ? $value : sanitize_text_field( (string) $existing );
	}

	private static function sanitizeDigestDay( mixed $day ): string {
		$day     = sanitize_key( (string) $day );
		$allowed = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];

		return in_array( $day, $allowed, true ) ? $day : 'monday';
	}
}
