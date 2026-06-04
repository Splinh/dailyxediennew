<?php
/**
 * Email/SMS OTP Login Verification
 *
 * @author HD
 */

namespace HDAddons\Modules\LoginSecurity;

use HDAddons\Asset;
use HDAddons\Core\RateLimitStorage;
use HDAddons\Helper;
use HDAddons\Modules\LoginSecurity\Totp\TotpHandler;


\defined( 'ABSPATH' ) || exit;

final class LoginOtpVerification {
	/* ---------- TRANSIENT & META KEYS ----------------------------------- */

	public const KEY_OTP       = 'loginotp_%d';     // hash (OTP)
	public const KEY_ATTEMPT   = 'loginotp_try_%d'; // int
	public const META_LASTSEND = '_otp_last_send';  // timestamp
	private const META_TOKEN   = '_otp_dnc_token';  // random

	/* ---------- CONFIG -------------------------------------------------- */

	public const OTP_DIGITS      = 8; // Increased from 6 to 8 for better security
	public const OTP_LIFETIME    = 5 * MINUTE_IN_SECONDS; // 5 minutes (transient and form)
	public const RESEND_INTERVAL = 5 * MINUTE_IN_SECONDS; // 5 minutes (cool-down email)
	public const COOKIE_LIFETIME = DAY_IN_SECONDS; // 1 day
	public const MAX_ATTEMPTS    = 3; // Reduced from 5 to 3 to prevent brute force
	public const TOKEN_LENGTH    = 32; // 32 bytes = 64 hex chars
	public const ACTION_VALIDATE = '_otp_validate';

	/**
	 * Re-entry guard: prevents initOtp() from re-triggering
	 * after _loginUser() fires wp_login.
	 */
	private static bool $otpVerified = false;

	/** @var OtpSender Delivery logic (email/SMS/gateway) */
	private ?OtpSender $sender = null;

	private function _sender(): OtpSender {
		return $this->sender ??= new OtpSender();
	}


	/* ---------- UID SIGNING --------------------------------------------- */

	/**
	 * Sign a user ID with HMAC to prevent form tampering.
	 *
	 * @param int $userId User ID to sign.
	 *
	 * @return string Signed user ID in format "uid:signature".
	 */
	public static function signUid( int $userId ): string {
		$sig = hash_hmac( 'sha256', (string) $userId, AUTH_SALT . NONCE_SALT );

		return $userId . ':' . $sig;
	}

	/**
	 * Verify and extract user ID from a signed uid string.
	 *
	 * @param string $signedUid Signed uid in format "uid:signature".
	 *
	 * @return int|false User ID on success, false on failure.
	 */
	public static function verifySignedUid( string $signedUid ): int|false {
		if ( ! str_contains( $signedUid, ':' ) ) {
			return false;
		}

		[ $uidStr, $sig ] = explode( ':', $signedUid, 2 );

		$uid         = absint( $uidStr );
		$expectedSig = hash_hmac( 'sha256', (string) $uid, AUTH_SALT . NONCE_SALT );

		if ( ! hash_equals( $expectedSig, $sig ) ) {
			return false;
		}

		return $uid;
	}

	/* ---------- LIFECYCLE ----------------------------------------------- */

	public function __construct() {
		if ( ! $this->_isEnabled() ) {
			return;
		}

		// login / logout
		add_action( 'wp_login', $this->initOtp( ... ), 10, 2 ); // Fires after successful login
		add_action( 'wp_logout', $this->cleanupOtpOnLogout( ... ), 10, 1 );
		add_action( 'clear_auth_cookie', $this->cleanupOtpOnLogout( ... ), 10, 0 );

		// form + message
		add_filter( 'login_message', $this->otpFailMessage( ... ) );
		add_action( 'login_form_' . self::ACTION_VALIDATE, $this->validateOtpLogin( ... ) );
	}

	/* ---------- PUBLIC HOOKS -------------------------------------------- */


	/**
	 * Sends OTP, if not yet verified, fires after the user has successfully logged in.
	 *
	 * @param string $user_login Username.
	 * @param \WP_User $user WP_User object of the logged-in user.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function initOtp( string $user_login, \WP_User $user ): void {
		// Guard: skip if OTP was just verified (prevents re-entry from _loginUser)
		if ( self::$otpVerified ) {
			return;
		}

		// Only roles configured to use OTP
		if ( empty( array_intersect( $this->_otpUserRoles(), $user->roles ) ) ) {
			return;
		}

		// Already has valid OTP cookie → skip
		if ( $this->_checkOtpCookie( $user ) ) {
			return;
		}

		// Remove auth-cookie to pause the session
		wp_clear_auth_cookie();

		$mode = $this->_sender()->getMode();

		// ── TOTP mode ──────────────────────────────────────
		if ( $mode === 'totp' && ! TotpHandler::isUserSetup( $user->ID ) ) {
			// TOTP not setup → fallback to email OTP silently
			Helper::errorLog( 'HDA OTP: TOTP not setup for user #' . $user->ID . ', falling back to email.' );
		}

		// ── Email / SMS mode (or TOTP fallback) ──────────
		if ( $mode !== 'totp' || ! TotpHandler::isUserSetup( $user->ID ) ) {
			$result = $this->_sender()->send( $user );

			if ( $result === false ) {
				$this->_clearOtpData( $user->ID );
				$error_key = ( $mode === 'sms' ) ? 'sms' : 'email';
				wp_safe_redirect( add_query_arg( '_error', $error_key, LoginUrl::getLoginUrl() ) );
				exit;
			}
		}

		// Redirect to OTP validation page.
		// URL: wp-login.php?action=_otp_validate&uid=<signed>
		// This action is in _handleLogin() bypass list, so block() won't interfere.
		// Page refresh at this URL will re-show the OTP form.
		wp_safe_redirect(
			add_query_arg(
				[
					'action' => self::ACTION_VALIDATE,
					'uid'    => rawurlencode( self::signUid( $user->ID ) ),
				],
				wp_login_url()
			)
		);
		exit;
	}

	/**
	 * Remove all OTP artifacts when a user logs out (or cookie cleared)
	 *
	 * @param int $userId
	 *
	 * @return void
	 */
	public function cleanupOtpOnLogout( int $userId = 0 ): void {
		$userId = $userId ?: get_current_user_id();
		if ( $userId ) {
			$this->_clearOtpData( $userId );
		}
	}

	/**
	 * Handle OTP page and form submission (`wp-login.php?action=_otp_validate`)
	 *
	 * GET  → Show OTP form (initial load or page refresh)
	 * POST → Validate submitted OTP code
	 *
	 * @throws \Exception
	 */
	public function validateOtpLogin(): void {
		// ── GET: show OTP form (redirect from initOtp or page refresh) ──
		if ( 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			$signedUid = sanitize_text_field( wp_unslash( $_GET['uid'] ?? '' ) );
			$uid       = self::verifySignedUid( rawurldecode( $signedUid ) );

			if ( false === $uid || 0 === $uid ) {
				wp_safe_redirect( LoginUrl::getLoginUrl() );
				exit;
			}

			$user = get_user_by( 'id', $uid );
			if ( ! $user ) {
				wp_safe_redirect( LoginUrl::getLoginUrl() );
				exit;
			}

			$mode       = $this->_sender()->getMode();
			$formAction = esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) );

			// TOTP form
			if ( $mode === 'totp' && TotpHandler::isUserSetup( $uid ) ) {
				$this->_loadForm(
					[
						'action'   => $formAction,
						'template' => 'Totp/totp-login.php',
						'uid'      => self::signUid( $uid ),
						'error'    => '',
					]
				);

				return;
			}

			// Email / SMS form
			$this->_loadForm(
				[
					'action'         => $formAction,
					'template'       => 'views/recovery-login.php',
					'uid'            => self::signUid( $uid ),
					'send_at'        => (int) get_user_meta( $uid, self::META_LASTSEND, true ),
					'error'          => '',
					'channel'        => $this->_sender()->getChannelLabel( $user ),
					'recipient_hint' => $this->_sender()->maskRecipient( $user ),
				]
			);

			return;
		}

		// ── POST: validate OTP code ──────────────────────
		// Sanitize inputs
		$authcode   = sanitize_text_field( wp_unslash( $_POST['authcode'] ?? '' ) );
		$signedUid  = sanitize_text_field( wp_unslash( $_POST['uid'] ?? '' ) );
		$csrf_token = sanitize_text_field( wp_unslash( $_POST['_csrf_token'] ?? '' ) );

		// Verify CSRF token first
		if ( empty( $csrf_token ) || ! wp_verify_nonce( $csrf_token, 'otp_csrf_token' ) ) {
			wp_safe_redirect( add_query_arg( '_error', 'invalid_request', LoginUrl::getLoginUrl() ) );
			exit;
		}

		// Verify signed uid — prevents tampering with the hidden uid field
		$uid = self::verifySignedUid( $signedUid );
		if ( false === $uid || $uid === 0 ) {
			wp_safe_redirect( add_query_arg( '_error', 'invalid_request', LoginUrl::getLoginUrl() ) );
			exit;
		}

		// Empty authcode - show form again with error (re-sign uid)
		if ( empty( $authcode ) ) {
			$user = get_user_by( 'id', $uid );
			$this->_loadForm(
				[
					'action'         => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
					'template'       => 'views/recovery-login.php',
					'uid'            => self::signUid( $uid ),
					'send_at'        => (int) get_user_meta( $uid, self::META_LASTSEND, true ),
					'error'          => __( 'Please enter the verification code.', 'hda' ),
					'channel'        => $user ? $this->_sender()->getChannelLabel( $user ) : '',
					'recipient_hint' => $user ? $this->_sender()->maskRecipient( $user ) : '',
				]
			);

			return;
		}

		$userId  = $uid;
		$entered = preg_replace( '/\D/', '', $authcode );
		$user    = get_user_by( 'id', $userId );

		// ── TOTP validation ────────────────────────────────
		if ( $this->_sender()->getMode() === 'totp' && TotpHandler::isUserSetup( $userId ) ) {
			// Brute-force protection (shared with email/sms)
			$attempts = (int) get_transient( sprintf( self::KEY_ATTEMPT, $userId ) );

			$lastUsed  = TotpHandler::getLastUsed( $userId );
			$secret    = TotpHandler::getUserSecret( $userId );
			$timeSlice = TotpHandler::verify( $secret, $entered, TotpHandler::WINDOW, $lastUsed );

			if ( $timeSlice === false ) {
				++$attempts;
				set_transient( sprintf( self::KEY_ATTEMPT, $userId ), $attempts, self::OTP_LIFETIME );

				// Log failed TOTP attempt
				do_action( 'hda_log_event', $userId, $user ? $user->user_login : '', 'otp_failed' );

				if ( $attempts >= self::MAX_ATTEMPTS ) {
					delete_transient( sprintf( self::KEY_ATTEMPT, $userId ) );
					wp_safe_redirect( add_query_arg( '_error', 'max_attempts', LoginUrl::getLoginUrl() ) );
					exit;
				}

				$this->_loadForm(
					[
						'action'   => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
						'template' => 'Totp/totp-login.php',
						'uid'      => self::signUid( $userId ),
						'error'    => sprintf( __( 'Invalid code. You have %1$d of %2$d attempts left.', 'hda' ), self::MAX_ATTEMPTS - $attempts, self::MAX_ATTEMPTS ),
					]
				);

				return;
			}

			// TOTP success — update replay protection
			TotpHandler::setLastUsed( $userId, $timeSlice );
			delete_transient( sprintf( self::KEY_ATTEMPT, $userId ) );

			$rememberme = ! empty( $_POST['rememberme'] );
			$this->_loginUser( $userId, $rememberme );
			$this->_interimCheck();

			$redirect = ! empty( $_POST['redirect_to'] ) ? sanitize_url( wp_unslash( $_POST['redirect_to'] ) ) : get_admin_url();
			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit;
		}

		// ── Email / SMS validation ────────────────────────
		// Transient data
		$hash     = get_transient( sprintf( self::KEY_OTP, $userId ) );
		$attempts = (int) get_transient( sprintf( self::KEY_ATTEMPT, $userId ) );

		if ( false === $hash ) {
			$this->_loadForm(
				[
					'action'         => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
					'template'       => 'views/recovery-login.php',
					'uid'            => self::signUid( $userId ),
					'send_at'        => (int) get_user_meta( $userId, self::META_LASTSEND, true ),
					'error'          => __( 'Verification code expired - please request a new code.', 'hda' ),
					'channel'        => $user ? $this->_sender()->getChannelLabel( $user ) : '',
					'recipient_hint' => $user ? $this->_sender()->maskRecipient( $user ) : '',
				]
			);

			return;
		}

		// Compare using secure hash
		if ( ! hash_equals( $hash, $this->_sender()->hashOtp( $entered ) ) ) {

			// Exponential backoff delay
			$this->_enforceBackoffDelay( $attempts );

			// +1 failed attempt
			++$attempts;
			set_transient( sprintf( self::KEY_ATTEMPT, $userId ), $attempts, self::OTP_LIFETIME );

			// Log failed attempt
			do_action( 'hda_log_event', $userId, $user ? $user->user_login : '', 'otp_failed' );

			// IP-based rate limiting
			if ( ! $this->_checkIpRateLimit() ) {
				$this->_clearOtpData( $userId );
				wp_safe_redirect( add_query_arg( '_error', 'rate_limit', LoginUrl::getLoginUrl() ) );
				exit;
			}

			// Too many attempts?
			if ( $attempts >= self::MAX_ATTEMPTS ) {
				$this->_clearOtpData( $userId );
				wp_safe_redirect( add_query_arg( '_error', 'max_attempts', LoginUrl::getLoginUrl() ) );
				exit;
			}

			$this->_loadForm(
				[
					'action'         => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
					'template'       => 'views/recovery-login.php',
					'uid'            => self::signUid( $userId ),
					'send_at'        => (int) get_user_meta( $userId, self::META_LASTSEND, true ),
					'error'          => sprintf( __( 'Invalid code. You have %1$d of %2$d attempts left.', 'hda' ), self::MAX_ATTEMPTS - $attempts, self::MAX_ATTEMPTS ),
					'channel'        => $user ? $this->_sender()->getChannelLabel( $user ) : '',
					'recipient_hint' => $user ? $this->_sender()->maskRecipient( $user ) : '',
				]
			);

			return;
		}

		// Success + log the user in again and redirect
		$rememberme = ! empty( $_POST['rememberme'] );
		$this->_loginUser( $userId, $rememberme );
		$this->_interimCheck();

		$redirect = ! empty( $_POST['redirect_to'] ) ? sanitize_url( wp_unslash( $_POST['redirect_to'] ) ) : get_admin_url();
		wp_safe_redirect( esc_url_raw( $redirect ) );
		exit;
	}

	/**
	 * Replace default login messages with OTP-specific errors
	 *
	 * @param string $message
	 *
	 * @return string
	 */
	public function otpFailMessage( string $message ): string {
		$error = sanitize_key( $_GET['_error'] ?? '' );
		if ( empty( $error ) ) {
			return $message;
		}

		return match ( $error ) {
			'email'           => '<div id="login_error" class="notice notice-error"><p><strong>' . esc_html__( 'Error', 'hda' ) . '</strong>: ' . esc_html__( 'Unable to send OTP e-mail.', 'hda' ) . '</p></div>',
			'sms'             => '<div id="login_error" class="notice notice-error"><p><strong>' . esc_html__( 'Error', 'hda' ) . '</strong>: ' . esc_html__( 'Unable to send OTP via SMS/Messaging.', 'hda' ) . '</p></div>',
			'max_attempts'    => '<div id="login_error" class="notice notice-error"><p><strong>' . esc_html__( 'Error', 'hda' ) . '</strong>: ' . esc_html__( 'Too many attempts.', 'hda' ) . '</p></div>',
			'invalid_request' => '<div id="login_error" class="notice notice-error"><p><strong>' . esc_html__( 'Error', 'hda' ) . '</strong>: ' . esc_html__( 'Invalid request. Please try again.', 'hda' ) . '</p></div>',
			default           => $message,
		};
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * Log user in again & set OTP cookie
	 *
	 * @param int $userId
	 *
	 * @param bool $rememberme Whether to remember the user.
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function _loginUser( int $userId = 0, bool $rememberme = false ): void {
		self::$otpVerified = true;

		wp_set_auth_cookie( $userId, $rememberme );
		wp_set_current_user( $userId );

		$this->_clearOtpData( $userId );
		$this->_setOtpCookie( $userId );

		// Fire wp_login so ActivityLog and other hooks can capture the event.
		// The $otpVerified flag prevents initOtp() from re-triggering.
		$user = get_user_by( 'id', $userId );
		if ( $user ) {
			/** This action is documented in wp-includes/user.php */
			do_action( 'wp_login', $user->user_login, $user );
		}
	}

	/**
	 * Show success page for interim-login iframe.
	 * WordPress uses interim-login for AJAX session refresh when session expires mid-work.
	 *
	 * @return void
	 */
	private function _interimCheck(): void {
		$interim_login = sanitize_text_field( wp_unslash( $_REQUEST['interim-login'] ?? '' ) );

		// Only proceed if this is actually an interim login request
		if ( ! in_array( $interim_login, [ '1', 'success' ], true ) ) {
			return;
		}

		$GLOBALS['interim_login'] = 'success';

		login_header( '', '<p class="message">' . __( 'You have logged in successfully.', 'hda' ) . '</p>' );
		echo '</div>';
		do_action( 'login_footer' );
		echo '</body></html>';
		exit;
	}
	/**
	 * Display the OTP authentication forms.
	 *
	 * @param $args
	 *
	 * @return void
	 */
	private function _loadForm( $args ): void {
		if ( empty( $args['template'] ) ) {
			return;
		}

		// Path to the form template.
		$path = __DIR__ . '/' . $args['template'];
		if ( ! is_file( $path ) ) {
			return;
		}

		$args = array_merge(
			$args,
			[
				'otp_digits'      => self::OTP_DIGITS,
				'resend_interval' => self::RESEND_INTERVAL,
				'interim_login'   => ( isset( $_REQUEST['interim-login'] ) ) ? filter_var( wp_unslash( $_REQUEST['interim-login'] ), FILTER_VALIDATE_BOOLEAN ) : false,
				'redirect_to'     => esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ?? admin_url() ) ),
			]
		);

		// Include the login header if the function doesn't exist.
		if ( ! function_exists( 'login_header' ) ) {
			include_once ABSPATH . 'wp-login.php';
		}

		// Include the template.php if the function doesn't exist.
		if ( ! function_exists( 'submit_button' ) ) {
			require_once ABSPATH . '/wp-admin/includes/template.php';
		}

		login_header();
		include_once $path;
		login_footer();
		exit;
	}

	/**
	 * Create a secure cookie for device-not-challenge
	 *
	 * @param int $userId
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function _setOtpCookie( int $userId = 0 ): void {
		$token     = bin2hex( random_bytes( self::TOKEN_LENGTH ) );
		$ip        = Helper::ipAddress();
		$expiresAt = time() + self::COOKIE_LIFETIME;

		update_user_meta(
			$userId,
			self::META_TOKEN,
			[
				'token'      => $token,
				'ip'         => $ip,
				'expires_at' => $expiresAt,
			]
		);

		setcookie(
			'_otp_dnc_cookie',
			$userId . '|' . $token,
			[
				'expires'  => $expiresAt,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * Validate OTP cookie for device-not-challenge.
	 *
	 * @param \WP_User $user The user to check cookie for.
	 *
	 * @return bool True if cookie is valid, false otherwise.
	 */
	private function _checkOtpCookie( \WP_User $user ): bool {
		if ( empty( $_COOKIE['_otp_dnc_cookie'] ) ) {
			return false;
		}

		$cookie = sanitize_text_field( wp_unslash( $_COOKIE['_otp_dnc_cookie'] ) );

		if ( ! str_contains( $cookie, '|' ) ) {
			return false;
		}

		$parts = explode( '|', $cookie, 2 );

		if ( count( $parts ) !== 2 ) {
			return false;
		}

		[ $uid, $token ] = $parts;

		if ( ! is_numeric( $uid ) || empty( $token ) ) {
			return false;
		}

		$storedData = get_user_meta( $user->ID, self::META_TOKEN, true );

		if ( is_array( $storedData ) ) {
			$storedToken = $storedData['token'] ?? '';
			$storedIp    = $storedData['ip'] ?? '';
			$expiresAt   = $storedData['expires_at'] ?? 0;

			if ( $expiresAt > 0 && time() > $expiresAt ) {
				$this->_clearOtpData( $user->ID );
				return false;
			}
		} else {
			$storedToken = $storedData;
			$storedIp    = get_user_meta( $user->ID, self::META_TOKEN . '_ip', true );
		}

		if (
			(int) $uid !== $user->ID ||
			empty( $storedToken ) ||
			! hash_equals( $storedToken, $token )
		) {
			return false;
		}

		if ( ! empty( $storedIp ) && $this->_isIpBindingEnabled() && $storedIp !== Helper::ipAddress() ) {
			return false;
		}

		return true;
	}

	/**
	 * @param int $userId
	 *
	 * @return void
	 */
	private function _clearOtpData( int $userId = 0 ): void {
		delete_transient( sprintf( self::KEY_OTP, $userId ) );
		delete_transient( sprintf( self::KEY_ATTEMPT, $userId ) );
		delete_user_meta( $userId, self::META_LASTSEND );
		delete_user_meta( $userId, self::META_TOKEN );
		delete_user_meta( $userId, self::META_TOKEN . '_ip' );
	}

	/**
	 * Enforce exponential backoff delay after failed OTP attempts.
	 *
	 * @param int $attempts Number of failed attempts.
	 * @return void
	 */
	private function _enforceBackoffDelay( int $attempts ): void {
		$delay = match ( $attempts ) {
			0       => 0,
			1       => 2,
			2       => 5,
			default => 15,
		};

		if ( $delay > 0 ) {
			sleep( $delay );
		}
	}

	/**
	 * Check IP-based rate limiting (10 failures per hour across all users).
	 *
	 * Uses RateLimitStorage for efficient hybrid storage (Redis/Memcached or MySQL).
	 * Prevents DDoS attacks from bloating wp_options table.
	 *
	 * @return bool True if within limit, false if rate limit exceeded.
	 */
	private function _checkIpRateLimit(): bool {
		$ip = Helper::ipAddress();

		// Get current failure count for this IP
		$failures = RateLimitStorage::get( $ip, 'otp_failed' );

		if ( $failures >= 10 ) {
			return false; // Rate limit exceeded
		}

		// Increment failure count (atomic operation)
		RateLimitStorage::increment( $ip, 'otp_failed', HOUR_IN_SECONDS );

		return true;
	}

	private function _isEnabled(): bool {
		$opt = LoginSecurityModule::getCachedOptions();

		return isset( $opt[ LoginSecurityModule::KEY_OTP_MODE ] ) && $opt[ LoginSecurityModule::KEY_OTP_MODE ] !== 'disabled';
	}

	/**
	 * Roles that should be forced to use Email-OTP.
	 *
	 * @return array
	 */
	private function _otpUserRoles(): array {
		$opt   = LoginSecurityModule::getCachedOptions();
		$roles = ! empty( $opt[ LoginSecurityModule::KEY_OTP_USER_ROLES ] ) ? (array) $opt[ LoginSecurityModule::KEY_OTP_USER_ROLES ] : [ 'editor', 'administrator' ];

		return apply_filters( 'loginotp_user_roles', $roles );
	}

	/**
	 * Check if IP binding is enabled for OTP cookie.
	 *
	 * @return bool
	 */
	private function _isIpBindingEnabled(): bool {
		$opt = LoginSecurityModule::getCachedOptions();

		return ! empty( $opt[ LoginSecurityModule::KEY_OTP_IP_BINDING ] );
	}
}
