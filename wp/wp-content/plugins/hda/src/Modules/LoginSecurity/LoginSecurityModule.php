<?php
/**
 * Login Security module — Custom login URL, CSRF, OTP, 2FA, activity logging.
 *
 * @package HDAddons\Modules\LoginSecurity
 */

namespace HDAddons\Modules\LoginSecurity;

use HDAddons\Asset;
use HDAddons\Contracts\HasDatabaseSchema;
use HDAddons\Contracts\HasSettings;
use HDAddons\CSS;
use HDAddons\Helper;

use HDAddons\Modules\LoginSecurity\MagicLink\LoginMagicLink;
use HDAddons\Modules\LoginSecurity\MagicLink\MagicLinkHandler;
use HDAddons\Modules\LoginSecurity\Totp\TotpHandler;
use HDAddons\Modules\LoginSecurity\LoginAttempts;
use HDAddons\Modules\LoginSecurity\LoginIllegalUsers;
use HDAddons\Modules\LoginSecurity\LoginOtpVerification;
use HDAddons\Modules\LoginSecurity\LoginRestricted;
use HDAddons\Modules\LoginSecurity\LoginUrl;
use HDAddons\Modules\LoginSecurity\UserOtpProfile;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class LoginSecurityModule extends AbstractModule implements HasSettings, HasDatabaseSchema {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'login_security';
	}

	public static function title(): string {
		return 'Login Security';
	}

	public static function description(): string {
		return 'Custom login URL, 2FA, and brute-force protection.';
	}

	public static function group(): string {
		return 'security';
	}

	public static function defaults(): array {
		return [
			self::KEY_CUSTOM_LOGIN_URI     => '',
			self::KEY_LOGIN_TOKEN_IP_CHECK => '',
			self::KEY_OTP_MODE             => 'disabled',
			self::KEY_OTP_GATEWAY          => 'telegram',
			self::KEY_OTP_GATEWAY_CONFIG   => [],
			self::KEY_OTP_USER_ROLES       => [ 'editor', 'administrator' ],
			self::KEY_OTP_IP_BINDING       => '',
			self::KEY_LOGIN_IPS_ACCESS     => [],
			self::KEY_BASIC_PROTECTION     => '',
			self::KEY_LIMIT_LOGIN_ATTEMPTS => 0,
		];
	}

	// ── Constants ───────────────────────────────────


	// Login URL
	public const KEY_CUSTOM_LOGIN_URI     = 'custom_login_uri';
	public const KEY_LOGIN_TOKEN_IP_CHECK = 'login_token_ip_check';

	// OTP
	public const KEY_OTP_MODE           = 'otp_mode';
	public const KEY_OTP_GATEWAY        = 'otp_gateway';
	public const KEY_OTP_GATEWAY_CONFIG = 'otp_gateway_config';
	public const KEY_OTP_USER_ROLES     = 'otp_user_roles';
	public const KEY_OTP_IP_BINDING     = 'otp_ip_binding';

	// Protection
	public const KEY_LOGIN_IPS_ACCESS     = 'login_ips_access';
	public const KEY_BASIC_PROTECTION     = 'basic_protection';
	public const KEY_LIMIT_LOGIN_ATTEMPTS = 'limit_login_attempts';

	// ── HasDatabaseSchema ──────────────────────────

	/** @inheritDoc */
	public static function databaseSchemas(): array {
		return [
			TotpHandler::TABLE_NAME      => <<<'SQL'
			user_id bigint unsigned NOT NULL,
			secret text NOT NULL,
			enabled tinyint(1) NOT NULL DEFAULT 0,
			last_used int unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (user_id)
			SQL,

			MagicLinkHandler::TABLE_NAME => <<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint unsigned NOT NULL,
			token varchar(64) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at datetime NOT NULL,
			used_at datetime NULL DEFAULT NULL,
			ip_address varchar(45) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			UNIQUE KEY idx_token (token),
			KEY idx_user_expires (user_id, expires_at)
			SQL,
		];
	}

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		add_action( 'login_enqueue_scripts', $this->loginEnqueueAssets( ... ), 31 );
		add_filter( 'login_headertext', $this->loginHeadertext( ... ) );
		add_filter( 'login_headerurl', $this->loginHeaderurl( ... ) );

		// CSRF login-form
		add_action( 'login_form', $this->addCsrfLoginForm( ... ) );
		add_filter( 'authenticate', $this->verifyCsrfLogin( ... ), 30, 3 );

		// CSRF lost-password
		add_action( 'lostpassword_form', $this->addCsrfLostpasswordForm( ... ) );
		add_action( 'lostpassword_post', $this->verifyCsrfLostpasswordPost( ... ), 30, 2 );

		// Initialize submodules with lazy loading.
		$this->initSubModules();
	}


	/**
	 * Check if current request method is POST.
	 */
	public static function isPostRequest(): bool {
		return isset( $_SERVER['REQUEST_METHOD'] )
			&& strtoupper( sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) ) === 'POST';
	}

	// ── SubModules ──────────────────────────────────

	/**
	 * Initialize submodules with lazy loading.
	 * Only instantiate modules that are actually enabled.
	 */
	private function initSubModules(): void {
		// ── Emergency bypass (single check for ALL submodules) ──
		if ( defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY ) {
			return;
		}

		$options    = self::getOptions();
		$otpMode    = $options[ self::KEY_OTP_MODE ];
		$otpEnabled = ! ( defined( 'HDA_DISABLE_OTP' ) && \HDA_DISABLE_OTP );
		// LoginRestricted - check if any restriction is configured.
		$hasRestrictions       = ! empty( $options[ self::KEY_LOGIN_IPS_ACCESS ] );
		$themeSecurityDefaults = Helper::filterSettingOptions( 'security', false );
		$hasThemeRestrictions  = ! empty( $themeSecurityDefaults['allowlist_ips_login_access'] );

		if ( $hasRestrictions || $hasThemeRestrictions ) {
			( new LoginRestricted() );
		}

		// Basic Protection - illegal usernames.
		if ( ! empty( $options[ self::KEY_BASIC_PROTECTION ] ) ) {
			( new LoginIllegalUsers() );
		}

		// LoginAttempts - check if limit is set.
		if (
			! empty( $options[ self::KEY_LIMIT_LOGIN_ATTEMPTS ] )
			&& (int) $options[ self::KEY_LIMIT_LOGIN_ATTEMPTS ] > 0
		) {
			( new LoginAttempts() );
		}

		// LoginOtpVerification - check if OTP mode is enabled.
		if ( $otpEnabled && in_array( $otpMode, [ 'email', 'sms', 'totp' ], true ) ) {
			( new LoginOtpVerification() );
			( new UserOtpProfile() );
		}

		// LoginMagicLink - passwordless login via email link.
		if ( $otpEnabled && 'magic_link' === $otpMode ) {
			( new LoginMagicLink() );
		}

		// LoginUrl - check if custom login URL is set.
		if ( ! empty( $options[ self::KEY_CUSTOM_LOGIN_URI ] ) ) {
			( new LoginUrl() );
		}
	}

	// ── Login Assets ────────────────────────────────

	public function loginEnqueueAssets(): void {
		Asset::enqueueJS( 'login.js', [ 'jquery' ], null, true, [ 'module', 'defer' ] );

		// Tell JS whether to hide the "Remember Me" checkbox.
		// Only hide when OTP is active — OTP forms handle rememberme separately.
		$options      = self::getCachedOptions();
		$otpMode      = $options[ self::KEY_OTP_MODE ] ?? 'disabled';
		$hasCustomUrl = ! empty( $options[ self::KEY_CUSTOM_LOGIN_URI ] )
			&& ! in_array( $options[ self::KEY_CUSTOM_LOGIN_URI ], [ 'wp-login.php', 'wp-admin' ], true );

		$hideRememberMe = $hasCustomUrl || ( 'disabled' !== $otpMode );

		$handle = Asset::handle( 'login.js' );
		if ( $handle ) {
			Asset::localize(
				$handle,
				'hdaLogin',
				[
					'hideRememberMe' => $hideRememberMe ? '1' : '',
				]
			);
		}

		$default_logo = HDA_URL . 'assets/img/logo.png';
		$default_bg   = HDA_URL . 'assets/img/login-bg.jpg';

		$logo     = esc_url_raw( Helper::getThemeMod( 'login_page_logo_setting' ) ?: $default_logo );
		$bg_img   = esc_url_raw( Helper::getThemeMod( 'login_page_bgimage_setting' ) ?: $default_bg );
		$bg_color = sanitize_hex_color( Helper::getThemeMod( 'login_page_bgcolor_setting' ) );

		$css = new CSS();

		if ( $bg_img ) {
			$css->setSelector( 'body.login' )
				->addProperty( 'background-image', "url({$bg_img})" );
		}

		if ( $bg_color ) {
			$css->setSelector( 'body.login' )
				->addProperty( 'background-color', $bg_color )
				->setSelector( 'body.login:before' )
				->addProperty( 'background', 'none' )
				->addProperty( 'opacity', 1 );
		}

		if ( $logo ) {
			$css->setSelector( 'body.login #login h1 a' )
				->addProperty( 'background-image', "url({$logo})" );
		}

		$inline = $css->cssOutput();
		if ( $inline ) {
			// Handle derived from JS entry: hda-login-js → hda-login-css
			Asset::inlineStyle( 'hda-login-css', $inline );
		}
	}

	// ── Login Branding ──────────────────────────────

	public function loginHeadertext(): mixed {
		return Helper::getThemeMod( 'login_page_headertext_setting' ) ?: get_bloginfo( 'name' );
	}

	public function loginHeaderurl(): mixed {
		return Helper::getThemeMod( 'login_page_headerurl_setting' ) ?: site_url( '/' );
	}

	// ── CSRF Login ──────────────────────────────────

	public function addCsrfLoginForm(): void {
		echo Helper::CSRFToken( 'login_csrf_token' );
	}

	/**
	 * Verify CSRF token on login form submit.
	 */
	public function verifyCsrfLogin( $user, $username, $password ): mixed {
		if ( empty( $username ) || ! self::isPostRequest() ) {
			return $user;
		}

		$csrf_token = sanitize_text_field( wp_unslash( $_POST['_csrf_token'] ?? '' ) );
		if ( empty( $csrf_token ) || ! wp_verify_nonce( $csrf_token, 'login_csrf_token' ) ) {
			return new \WP_Error( 'csrf_error', __( 'Invalid CSRF token. Please try again.', 'hda' ) );
		}

		return $user;
	}

	// ── CSRF Lost Password ──────────────────────────

	public function addCsrfLostpasswordForm(): void {
		echo Helper::CSRFToken( 'lostpassword_csrf_token' );
	}

	/**
	 * Verify CSRF token on lost password form submit.
	 */
	public function verifyCsrfLostpasswordPost( \WP_Error $errors, $user_data ): void {
		if ( ! self::isPostRequest() ) {
			return;
		}

		$csrf_token = sanitize_text_field( wp_unslash( $_POST['_csrf_token'] ?? '' ) );
		if ( empty( $csrf_token ) || ! wp_verify_nonce( $csrf_token, 'lostpassword_csrf_token' ) ) {
			$errors->add( 'csrf_error', __( 'Invalid CSRF token, please try again.', 'hda' ) );
		}
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		$fields = [
			self::KEY_CUSTOM_LOGIN_URI,
			self::KEY_LOGIN_TOKEN_IP_CHECK,
			self::KEY_OTP_MODE,
			self::KEY_OTP_GATEWAY,
			self::KEY_OTP_GATEWAY_CONFIG,
			self::KEY_OTP_USER_ROLES,
			self::KEY_OTP_IP_BINDING,
			self::KEY_LOGIN_IPS_ACCESS,
			self::KEY_BASIC_PROTECTION,
			self::KEY_LIMIT_LOGIN_ATTEMPTS,
		];

		$options  = self::extractFields( $data, $fields );
		$existing = Helper::getOption( self::optionKey(), [] );

		// ── Preserve settings for hidden sections ──────────────
		$preserveKeys = [
			self::KEY_OTP_GATEWAY,
			self::KEY_OTP_GATEWAY_CONFIG,
			self::KEY_OTP_USER_ROLES,
			self::KEY_OTP_IP_BINDING,
		];

		foreach ( $preserveKeys as $key ) {
			if ( ! isset( $options[ $key ] ) && isset( $existing[ $key ] ) ) {
				$options[ $key ] = $existing[ $key ];
			}
		}

		// Validate gateway config when SMS mode is enabled.
		if ( ( $options[ self::KEY_OTP_MODE ] ?? 'disabled' ) === 'sms' ) {
			$gatewayName   = $options[ self::KEY_OTP_GATEWAY ] ?? 'telegram';
			$gatewayConfig = $options[ self::KEY_OTP_GATEWAY_CONFIG ][ $gatewayName ] ?? [];

			$validationResult = self::validateGatewayConfig( $gatewayName, $gatewayConfig );

			if ( ! $validationResult['valid'] ) {
				add_action(
					'admin_notices',
					static function () use ( $validationResult ) {
						echo '<div class="notice notice-warning is-dismissible">';
						echo '<p><strong>' . esc_html__( 'OTP Gateway Warning:', 'hda' ) . '</strong> ';
						echo esc_html( $validationResult['message'] );
						echo ' ' . esc_html__( 'SMS mode is enabled but may fall back to email if gateway is not properly configured.', 'hda' );
						echo '</p></div>';
					}
				);
			}
		}

		// Check privileged user permissions.
		$defaults            = Helper::filterSettingOptions( 'security', false );
		$privileged_user_ids = $defaults['privileged_user_ids'] ?? [];
		$user_id             = get_current_user_id();

		// Non-privileged users cannot modify certain security settings.
		// Fallback to existing values, which are already defaults-merged via getOptions().
		$existingMerged = self::getOptions();

		if ( ! in_array( $user_id, $privileged_user_ids, true ) ) {
			$options[ self::KEY_CUSTOM_LOGIN_URI ]     = $existingMerged[ self::KEY_CUSTOM_LOGIN_URI ];
			$options[ self::KEY_LOGIN_TOKEN_IP_CHECK ] = $existingMerged[ self::KEY_LOGIN_TOKEN_IP_CHECK ];
			$options[ self::KEY_OTP_MODE ]             = $existingMerged[ self::KEY_OTP_MODE ];
			$options[ self::KEY_OTP_GATEWAY ]          = $existingMerged[ self::KEY_OTP_GATEWAY ];
			$options[ self::KEY_OTP_GATEWAY_CONFIG ]   = $existingMerged[ self::KEY_OTP_GATEWAY_CONFIG ];
			$options[ self::KEY_OTP_USER_ROLES ]       = $existingMerged[ self::KEY_OTP_USER_ROLES ];
			$options[ self::KEY_OTP_IP_BINDING ]       = $existingMerged[ self::KEY_OTP_IP_BINDING ];
			$options[ self::KEY_LOGIN_IPS_ACCESS ]     = $existingMerged[ self::KEY_LOGIN_IPS_ACCESS ];
		}

		// ── Self-lockout prevention ─────────────────────────
		$allowlist_ips = (array) ( $options[ self::KEY_LOGIN_IPS_ACCESS ] ?? [] );
		if ( ! empty( $allowlist_ips ) ) {
			$current_ip = Helper::ipAddress();

			if ( $current_ip && ! Helper::ipMatchesAny( $current_ip, $allowlist_ips ) ) {
				$allowlist_ips[]                       = $current_ip;
				$options[ self::KEY_LOGIN_IPS_ACCESS ] = $allowlist_ips;

				add_action(
					'admin_notices',
					static function () use ( $current_ip ) {
						echo '<div class="notice notice-info is-dismissible">';
						echo '<p><strong>' . esc_html__( 'Login Security:', 'hda' ) . '</strong> ';
						printf(
							esc_html__( 'Your current IP (%s) was automatically added to the allowlist to prevent self-lockout.', 'hda' ),
							esc_html( $current_ip )
						);
						echo '</p></div>';
					}
				);
			}
		}

		Helper::updateOption( self::optionKey(), $options );
	}

	/**
	 * Validate gateway configuration.
	 */
	private static function validateGatewayConfig( string $gatewayName, array $config ): array {
		$requiredFields = match ( $gatewayName ) {
			'telegram' => [ 'bot_token' => __( 'Bot Token', 'hda' ) ],
			'zalo'     => [ 'bot_token' => __( 'Bot Token', 'hda' ) ],
			'whatsapp' => [
				'phone_number_id' => __( 'Phone Number ID', 'hda' ),
				'access_token'    => __( 'Access Token', 'hda' ),
			],
			'smsgate' => [
				'username' => __( 'Username', 'hda' ),
				'password' => __( 'Password', 'hda' ),
			],
			'viber'   => [ 'auth_token' => __( 'Auth Token', 'hda' ) ],
			'line'    => [ 'channel_access_token' => __( 'Channel Access Token', 'hda' ) ],
			'discord' => [ 'bot_token' => __( 'Bot Token', 'hda' ) ],
			default   => [],
		};

		$missingFields = [];
		foreach ( $requiredFields as $field => $label ) {
			if ( empty( $config[ $field ] ) ) {
				$missingFields[] = $label;
			}
		}

		if ( ! empty( $missingFields ) ) {
			return [
				'valid'   => false,
				'message' => sprintf(
					__( 'Missing required fields: %s.', 'hda' ),
					implode( ', ', $missingFields )
				),
			];
		}

		return [
			'valid'   => true,
			'message' => '',
		];
	}
}
