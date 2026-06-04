<?php
/**
 * Magic Link Login Controller
 *
 * Intercepts the WordPress login page to show a magic link form,
 * handles email submission, and processes token-based login.
 *
 * @package HDAddons\Modules\LoginSecurity\MagicLink
 * @author  HD
 */

namespace HDAddons\Modules\LoginSecurity\MagicLink;

use HDAddons\Helper;

use HDAddons\Modules\LoginSecurity\LoginSecurityModule;
use HDAddons\Modules\LoginSecurity\LoginUrl;

\defined( 'ABSPATH' ) || exit;

final class LoginMagicLink {

	private const ACTION_MAGIC_LINK = '_magic_link';

	/**
	 * Cached token validation result to avoid duplicate DB queries
	 * when both allowMagicLinkAccess() and handleTokenLogin() validate the same token.
	 *
	 * @var array{token: string, row: array|null}|null
	 */
	private static ?array $cachedValidation = null;

	public function __construct() {
		if ( ! $this->isEnabled() ) {
			return;
		}

		// Intercept login page to show magic link form
		add_action( 'login_init', $this->interceptLoginPage( ... ), 5 );

		// Handle magic link token validation
		add_action( 'login_form_' . self::ACTION_MAGIC_LINK, $this->handleTokenLogin( ... ) );

		// Allow magic link token login to bypass Custom Login URL block.
		// Without this, clicking a magic link from email would be blocked
		// because the user doesn't have the LoginUrl permissions cookie.
		add_filter( 'hda_login_url_allow_access', $this->allowMagicLinkAccess( ... ), 10, 2 );
	}

	// ══════════════════════════════════════════════════════
	//  CUSTOM LOGIN URL INTEGRATION
	// ══════════════════════════════════════════════════════

	/**
	 * Allow magic link token to bypass Custom Login URL.
	 *
	 * Validates the magic link token (read-only, non-consuming)
	 * before letting the request through to handleTokenLogin().
	 *
	 * @param bool   $allow Current allow status.
	 * @param string $type  Block type (login, register).
	 *
	 * @return bool
	 */
	public function allowMagicLinkAccess( bool $allow, string $type ): bool {
		if ( $allow || $type !== 'login' ) {
			return $allow;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( $_GET['action'] ?? '' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );

		// Only allow for magic link action with a valid token
		if ( $action !== self::ACTION_MAGIC_LINK || empty( $token ) ) {
			return false;
		}

		// Validate token exists and is valid (read-only — consumption happens in handleTokenLogin)
		self::$cachedValidation = [
			'token' => $token,
			'row'   => MagicLinkHandler::validateToken( $token ),
		];

		return self::$cachedValidation['row'] !== null;
	}

	// ══════════════════════════════════════════════════════
	//  LOGIN PAGE INTERCEPT
	// ══════════════════════════════════════════════════════

	/**
	 * Replace the default login form with magic link email form.
	 *
	 * Only intercepts the default login action. Other actions
	 * (logout, register, lostpassword, etc.) are left untouched.
	 */
	public function interceptLoginPage(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( $_REQUEST['action'] ?? 'login' );

		// Only intercept the default login page
		if ( ! in_array( $action, [ 'login', '' ], true ) ) {
			return;
		}

		// Allow ?force_password=1 to bypass magic link form
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['force_password'] ) ) {
			return;
		}

		// Handle POST: email submission
		if ( LoginSecurityModule::isPostRequest() && ! empty( $_POST['magic_link_request'] ) ) {
			$this->handleEmailSubmission();

			return; // handleEmailSubmission calls loadForm + exit
		}

		// Show magic link form (GET)
		$this->loadForm(
			[
				'action'                 => $this->getLoginActionUrl(),
				'success'                => false,
				'error'                  => '',
				'email'                  => '',
				'show_password_fallback' => true,
				'password_login_url'     => LoginUrl::getLoginUrl(),
			]
		);
	}

	// ══════════════════════════════════════════════════════
	//  EMAIL SUBMISSION
	// ══════════════════════════════════════════════════════

	/**
	 * Process the magic link email form submission.
	 */
	private function handleEmailSubmission(): void {
		// Verify CSRF token
		$csrfToken = sanitize_text_field( wp_unslash( $_POST['_csrf_token'] ?? '' ) );
		if ( empty( $csrfToken ) || ! wp_verify_nonce( $csrfToken, 'magic_link_csrf' ) ) {
			$this->loadForm(
				[
					'action' => $this->getLoginActionUrl(),
					'error'  => __( 'Security token expired. Please try again.', 'hda' ),
				]
			);

			return;
		}

		// Sanitize email
		$email = sanitize_email( wp_unslash( $_POST['magic_link_email'] ?? '' ) );

		if ( empty( $email ) || ! is_email( $email ) ) {
			$this->loadForm(
				[
					'action' => $this->getLoginActionUrl(),
					'error'  => __( 'Please enter a valid email address.', 'hda' ),
					'email'  => $email,
				]
			);

			return;
		}

		// Lookup user
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			// Don't reveal if email exists — show generic success
			$this->loadForm(
				[
					'action'  => $this->getLoginActionUrl(),
					'success' => true,
					'email'   => $email,
				]
			);

			return;
		}

		// Check if user's role is eligible
		if ( ! $this->isUserEligible( $user ) ) {
			$this->loadForm(
				[
					'action'                 => $this->getLoginActionUrl(),
					'error'                  => __( 'Your account is not eligible for magic link login. Please use password login.', 'hda' ),
					'email'                  => $email,
					'show_password_fallback' => true,
					'password_login_url'     => LoginUrl::getLoginUrl(),
				]
			);

			return;
		}

		// Rate limit check
		if ( ! MagicLinkHandler::canRequest( $user->ID ) ) {
			$this->loadForm(
				[
					'action' => $this->getLoginActionUrl(),
					'error'  => __( 'Too many requests. Please wait a few minutes before trying again.', 'hda' ),
					'email'  => $email,
				]
			);

			return;
		}

		// Generate token and send email
		$token = MagicLinkHandler::generateToken( $user->ID, Helper::ipAddress() );

		if ( empty( $token ) ) {
			$this->loadForm(
				[
					'action' => $this->getLoginActionUrl(),
					'error'  => __( 'Failed to generate login link. Please try again.', 'hda' ),
					'email'  => $email,
				]
			);

			return;
		}

		// Build magic link URL — always use wp_login_url() (wp-login.php)
		// to avoid exposing the custom login slug in the email.
		// The hda_login_url_allow_access filter bypasses LoginUrl block
		// when a valid magic link token is present.
		$magicLink = add_query_arg(
			[
				'action' => self::ACTION_MAGIC_LINK,
				'token'  => $token,
			],
			wp_login_url()
		);

		// Preserve redirect_to if present
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_REQUEST['redirect_to'] ) ) {
			$magicLink = add_query_arg( 'redirect_to', rawurlencode( esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) ), $magicLink );
		}

		$sent = MagicLinkHandler::sendEmail( $user, $magicLink );

		if ( $sent ) {
			MagicLinkHandler::incrementRate( $user->ID );

			// Log the event via decoupled hook.
			do_action( 'hda_log_event', $user->ID, $user->user_login, 'magic_link_sent' );
		}

		// Always show success (don't reveal if email exists)
		$this->loadForm(
			[
				'action'  => $this->getLoginActionUrl(),
				'success' => true,
				'email'   => $email,
			]
		);
	}

	// ══════════════════════════════════════════════════════
	//  TOKEN LOGIN
	// ══════════════════════════════════════════════════════

	/**
	 * Handle magic link token validation (login_form__magic_link action).
	 */
	public function handleTokenLogin(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );

		if ( empty( $token ) ) {
			$this->showTokenError( __( 'Invalid login link.', 'hda' ) );

			return;
		}

		// Use cached result from allowMagicLinkAccess() if available
		if ( self::$cachedValidation !== null && self::$cachedValidation['token'] === $token ) {
			$row                    = self::$cachedValidation['row'];
			self::$cachedValidation = null; // Clear cache after use
		} else {
			$row = MagicLinkHandler::validateToken( $token );
		}

		if ( ! $row ) {
			$this->showTokenError( __( 'This login link has expired or has already been used. Please request a new one.', 'hda' ) );

			return;
		}

		$userId = (int) $row['user_id'];
		$user   = get_user_by( 'id', $userId );

		if ( ! $user ) {
			$this->showTokenError( __( 'User account not found.', 'hda' ) );

			return;
		}

		// Verify role eligibility (may have changed since link was sent)
		if ( ! $this->isUserEligible( $user ) ) {
			// Consume token anyway to prevent reuse
			MagicLinkHandler::consumeToken( $token );
			$this->showTokenError( __( 'Your account is no longer eligible for magic link login.', 'hda' ) );

			return;
		}

		// Consume the token (single-use)
		MagicLinkHandler::consumeToken( $token );

		// Cleanup expired tokens opportunistically
		MagicLinkHandler::cleanupExpired();

		// Log the user in
		wp_set_auth_cookie( $userId, false );
		wp_set_current_user( $userId );

		// Log the event via decoupled hook.
		do_action( 'hda_log_event', $userId, $user->user_login, 'magic_link_login' );

		/** This action is documented in wp-includes/user.php */
		do_action( 'wp_login', $user->user_login, $user );

		// Redirect
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$redirectTo = ! empty( $_GET['redirect_to'] )
			? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) )
			: admin_url();

		wp_safe_redirect( $redirectTo );
		exit;
	}

	/**
	 * Show error for invalid/expired magic link token.
	 *
	 * @param string $message Error message.
	 */
	private function showTokenError( string $message ): void {
		$this->loadForm(
			[
				'action'                 => $this->getLoginActionUrl(),
				'error'                  => $message,
				'success'                => false,
				'email'                  => '',
				'show_password_fallback' => true,
				'password_login_url'     => LoginUrl::getLoginUrl(),
			]
		);
	}

	// ══════════════════════════════════════════════════════
	//  FORM RENDERING
	// ══════════════════════════════════════════════════════

	/**
	 * Load the magic link login form.
	 *
	 * @param array $args Template arguments.
	 */
	private function loadForm( array $args ): void {
		$path = __DIR__ . '/magic-link-login.php';

		if ( ! is_file( $path ) ) {
			return;
		}

		$args = array_merge(
			[
				'success'                => false,
				'error'                  => '',
				'email'                  => '',
				'show_password_fallback' => true,
				'password_login_url'     => LoginUrl::getLoginUrl(),
				'expiry_minutes'         => (int) ceil( MagicLinkHandler::TOKEN_EXPIRY / 60 ),
				'redirect_to'            => esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ?? admin_url() ) ),
			],
			$args
		);

		// Include login header/footer if not yet available
		if ( ! function_exists( 'login_header' ) ) {
			include_once ABSPATH . 'wp-login.php';
		}

		if ( ! function_exists( 'submit_button' ) ) {
			require_once ABSPATH . '/wp-admin/includes/template.php';
		}

		login_header();
		include $path;
		login_footer();
		exit;
	}

	// ══════════════════════════════════════════════════════
	//  HELPERS
	// ══════════════════════════════════════════════════════

	/**
	 * Check if magic link mode is enabled.
	 */
	private function isEnabled(): bool {
		$opt = LoginSecurityModule::getCachedOptions();

		return ( $opt[ LoginSecurityModule::KEY_OTP_MODE ] ?? 'disabled' ) === 'magic_link';
	}

	/**
	 * Check if user's role is eligible for magic link.
	 *
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	private function isUserEligible( \WP_User $user ): bool {
		$roles = $this->getRequiredRoles();

		return ! empty( array_intersect( $roles, $user->roles ) );
	}

	/**
	 * Get roles that require magic link login.
	 *
	 * @return array
	 */
	private function getRequiredRoles(): array {
		$opt   = LoginSecurityModule::getCachedOptions();
		$roles = ! empty( $opt[ LoginSecurityModule::KEY_OTP_USER_ROLES ] )
			? (array) $opt[ LoginSecurityModule::KEY_OTP_USER_ROLES ]
			: [ 'editor', 'administrator' ];

		return apply_filters( 'hda_magic_link_user_roles', $roles );
	}



	/**
	 * Get the form action URL.
	 *
	 * Always uses wp_login_url() (wp-login.php) — NOT the custom slug.
	 * Posting to the custom slug would cause LoginUrl to redirect (302),
	 * losing POST data. The user already has the permissions cookie.
	 *
	 * @return string
	 */
	private function getLoginActionUrl(): string {
		return esc_url( wp_login_url() );
	}
}
