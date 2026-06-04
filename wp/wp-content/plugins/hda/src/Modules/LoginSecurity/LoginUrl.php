<?php
/**
 * Custom Login Url
 *
 * @author HD
 */

namespace HDAddons\Modules\LoginSecurity;

use HDAddons\Core\RateLimitStorage;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class LoginUrl {
	/* ---------- CONFIG -------------------------------------------------- */

	private const CLU_TOKEN        = '_nonce';
	private const TOKEN_PREFIX     = 'hda_clu_';
	private const TOKEN_TTL        = 600; // 10 minutes
	private const TOKEN_LENGTH     = 48;  // 48 bytes = 96 hex chars
	private const SLUG_RATE_LIMIT  = 15;   // Max token generations per IP per minute
	private const SLUG_RATE_WINDOW = 60;  // Rate limit window in seconds

	private array $options = [];

	/* ---------- STATIC HELPERS ------------------------------------------- */

	/**
	 * Get the proper login URL, respecting custom login slug.
	 *
	 * Use this instead of wp_login_url() in sibling modules (OTP, Magic Link)
	 * so error redirects go through the custom slug flow (which issues tokens).
	 *
	 * @return string Custom slug URL when active, otherwise wp_login_url().
	 */
	public static function getLoginUrl(): string {
		$opt        = LoginSecurityModule::getCachedOptions();
		$custom_uri = $opt[ LoginSecurityModule::KEY_CUSTOM_LOGIN_URI ] ?? '';

		if (
			! empty( $custom_uri ) &&
			! in_array( $custom_uri, [ 'wp-login.php', 'wp-admin' ], true ) &&
			! ( defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY )
		) {
			return home_url( $custom_uri );
		}

		return wp_login_url();
	}

	/* ---------- CONSTRUCT ----------------------------------------------- */

	public function __construct() {
		if ( ! $this->_isEnabled() ) {
			return;
		}

		add_action( 'plugins_loaded', $this->handleRequest( ... ), 1000 );
		add_action( 'wp_authenticate_user', $this->maybeBlockCustomLogin( ... ), 10, 1 );
		add_filter( 'wp_logout', $this->logout( ... ) );
		add_filter( 'logout_redirect', $this->logoutRedirect( ... ), 10, 3 );
	}

	/* ---------- PUBLIC -------------------------------------------------- */

	/**
	 * Handle user logout.
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function logout( int $user_id ): void {
		$cookie_name = self::CLU_TOKEN . '-login-' . COOKIEHASH;

		// Always clear the access cookie on logout.
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			$this->_removeCookie( 'login' );
		}
	}

	/**
	 * Redirect logout through custom login slug.
	 *
	 * Forces the user back through the custom URL flow instead of
	 * granting direct wp-login.php access with a token.
	 *
	 * @param string $redirect_to       Default redirect URL.
	 * @param string $requested_redirect_to Requested redirect URL.
	 * @param \WP_User $user            Logged-out user.
	 *
	 * @return string Custom slug URL with preserved query params.
	 */
	public function logoutRedirect( $redirect_to, $requested_redirect_to, $user ): string {
		$query = wp_parse_url( $redirect_to, PHP_URL_QUERY );
		$url   = home_url( $this->options['new_slug'] );

		if ( $query ) {
			// Preserve loggedout=true, wp_lang, etc. so wp-login.php shows correct message.
			$url .= '?' . $query;
		}

		return $url;
	}

	/**
	 * Handle request paths.
	 *
	 * @return void
	 */
	public function handleRequest(): void {
		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		$path        = $this->_relativePath( $request_uri, false );

		if ( $path === $this->options['new_slug'] ) {
			if ( is_user_logged_in() ) {
				wp_safe_redirect( admin_url() );
				exit;
			}

			// Always issue a fresh token redirect — even if cookie exists.
			// This ensures wp-login.php URL always contains _nonce for
			// referer-based validation during form submission.
			$this->_redirectToken( 'login', 'wp-login.php' );
		}

		if ( str_contains( $path, 'wp-login' ) || str_contains( $path, 'wp-login.php' ) ) {
			$this->_handleLogin();
		}

		if ( $path === $this->options['register'] ) {
			$this->_handleRegistration();
		}
	}

	/**
	 * Block login when submitted without valid proof of custom URL access.
	 *
	 * Cookie = persistent proof the user entered via the custom login slug.
	 * Set by _redirectToken() when visiting the custom slug.
	 *
	 * @param \WP_User $user
	 *
	 * @return \WP_Error|\WP_User
	 */
	public function maybeBlockCustomLogin( \WP_User $user ): \WP_Error|\WP_User {
		$cookie_name = self::CLU_TOKEN . '-login-' . COOKIEHASH;

		if ( isset( $_COOKIE[ $cookie_name ] ) && $_COOKIE[ $cookie_name ] === $this->options['new_slug'] ) {
			return $user;
		}

		return new \WP_Error(
			'authentication_failed',
			__( '<strong>Error</strong>: Invalid login credentials.', 'hda' )
		);
	}

	/**
	 * Block page access to wp-login.php.
	 *
	 * Security model (two layers):
	 *  - GET  → Token in URL required (page view / cold access)
	 *  - POST → Cookie accepted (form submission from custom URL session)
	 *
	 * Cookie alone does NOT grant GET access — prevents typing
	 * wp-login.php directly while cookie is still alive.
	 * WordPress login form POSTs to bare wp-login.php (no query params),
	 * so POST requests must rely on cookie for proof.
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public function block( string $type = 'login' ): void {
		if ( is_user_logged_in() ) {
			return;
		}

		// Allow other modules (e.g., Magic Link) to bypass the block
		// when they have their own valid authentication token.
		if ( apply_filters( 'hda_login_url_allow_access', false, $type ) ) {
			return;
		}

		// GET requests: require token in URL (cold access protection).
		// Token is validated but NOT consumed — it auto-expires via TTL.
		if ( $this->_hasValidToken() ) {
			return;
		}

		// POST requests: accept cookie as proof of custom URL session.
		// WP login form POSTs to bare wp-login.php (no _nonce in form action),
		// so cookie is the only proof available during form submission.
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$cookie_name = self::CLU_TOKEN . '-' . $type . '-' . COOKIEHASH;
			if ( isset( $_COOKIE[ $cookie_name ] ) && $_COOKIE[ $cookie_name ] === $this->options['new_slug'] ) {
				return;
			}
		}

		// No valid access — block.
		$user_ip = Helper::ipAddress();
		Helper::errorLog( 'Restricted login page: access currently not permitted - ' . $user_ip );
		wp_die(
			esc_html__( "You don't have access to this page. Please contact the administrator of this website for further assistance.", 'hda' ),
			esc_html__( 'Restricted access', 'hda' ),
			[
				'hda_error'     => true,
				'response'      => 403,
				'blocked_login' => true,
			]
		);

		// Redirect to configured page (may be external, use wp_redirect).
		wp_safe_redirect( esc_url_raw( $this->options['redirect'] ), 302 );
		exit;
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * Handle login.
	 *
	 * @return void
	 */
	private function _handleLogin(): void {
		$action = sanitize_key( $_GET['action'] ?? '' );

		if ( in_array( $action, [ 'rp', 'resetpass', 'postpass', 'lostpassword', LoginOtpVerification::ACTION_VALIDATE ] ) ) {
			return;
		}

		if ( 'register' === $action ) {
			if ( 'wp-signup.php' !== $this->options['register'] ) {
				$this->block( 'register' );
			}

			return;
		}

		// Jetpack
		if (
			'jetpack_json_api_authorization' === $action &&
			has_filter( 'login_form_jetpack_json_api_authorization' )
		) {
			return;
		}

		// Jetpack SSO
		if (
			'jetpack-sso' === $action &&
			has_filter( 'login_form_jetpack-sso' )
		) {
			add_action( 'login_form_jetpack-sso', $this->block( ... ) );

			return;
		}

		$this->block( 'login' );
	}

	/**
	 * Handle registration request.
	 *
	 * @return void
	 */
	private function _handleRegistration(): void {
		if (
			1 !== (int) Helper::getOption( 'users_can_register', 0 ) ||
			empty( Helper::getOption( 'users_can_register' ) )
		) {
			return;
		}

		$this->_setPermissionsCookie( 'login' );

		if ( is_multisite() ) {
			$this->_redirectToken( 'register', 'wp-signup.php' );
		}

		$this->_redirectToken( 'register', 'wp-login.php?action=register' );
	}

	/**
	 * Adds a token and redirect to the url.
	 *
	 * @param $type
	 * @param $path
	 *
	 * @return void
	 */
	private function _redirectToken( $type, $path ): void {
		// ── Rate-limit token generation to prevent transient flooding ──
		// Each token creates a row in wp_options (transient).
		// Without this guard, a bot hammering the custom slug would
		// bloat wp_options with thousands of orphaned transient rows.
		$ip   = Helper::ipAddress();
		$hits = RateLimitStorage::increment( $ip, 'clu_slug', self::SLUG_RATE_WINDOW );

		if ( $hits > self::SLUG_RATE_LIMIT ) {
			wp_die(
				esc_html__( 'Too many requests. Please try again later.', 'hda' ),
				esc_html__( 'Rate Limited', 'hda' ),
				[
					'response'  => 429,
					'hda_error' => true,
				]
			);
		}

		$this->_setPermissionsCookie( $type );

		// Preserve existing query vars and add access token query arg.
		$query_vars                    = array_map( 'sanitize_text_field', array_filter( $_GET, 'is_string' ) );
		$query_vars[ self::CLU_TOKEN ] = rawurlencode( $this->_generateSecureToken() );

		$url = add_query_arg( $query_vars, rtrim( $this->_siteUrl( $path ), '/' ) );

		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Generate a cryptographically secure random token.
	 * Token is stored in transient with short TTL for one-time use.
	 *
	 * @return string 64-character hex token
	 */
	private function _generateSecureToken(): string {
		try {
			$token = bin2hex( random_bytes( self::TOKEN_LENGTH ) );
		} catch ( \Exception $e ) {
			// Fallback for older systems (should never happen in PHP 7+)
			$token = wp_generate_password( self::TOKEN_LENGTH * 2, false );
		}

		// Create a hash of the token to use as transient key (don't store raw token)
		$tokenHash = $this->_hashToken( $token );

		// Store token hash in transient with short TTL
		set_transient(
			self::TOKEN_PREFIX . $tokenHash,
			[
				'slug'    => $this->options['new_slug'],
				'created' => time(),
				'ip'      => Helper::ipAddress(),
			],
			self::TOKEN_TTL
		);

		return $token;
	}

	/**
	 * Create a secure hash of the token for storage.
	 *
	 * @param string $token Raw token.
	 *
	 * @return string Hashed token.
	 */
	private function _hashToken( string $token ): string {
		return hash_hmac( 'sha256', $token, AUTH_SALT . SECURE_AUTH_SALT );
	}

	/**
	 * Set a cookie which will be used to check if the user has permissions to view a page.
	 *
	 * @param string $type
	 */
	private function _setPermissionsCookie( string $type = '' ): void {
		$url_parts = wp_parse_url( $this->_siteUrl() );
		$home_path = trailingslashit( $url_parts['path'] );

		if ( ! empty( $type ) ) {
			setcookie(
				self::CLU_TOKEN . '-' . $type . '-' . COOKIEHASH,
				$this->options['new_slug'],
				[
					'expires'  => time() + 3600,
					'path'     => $home_path,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				]
			);
		}
	}

	/**
	 * @param string $type
	 *
	 * @return void
	 */
	private function _removeCookie( string $type = 'login' ): void {
		$url_parts = wp_parse_url( $this->_siteUrl() );
		$home_path = trailingslashit( $url_parts['path'] );

		setcookie(
			self::CLU_TOKEN . '-' . $type . '-' . COOKIEHASH,
			'',
			[
				'expires'  => time() - 3600,
				'path'     => $home_path,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * Validate token from URL without consuming it.
	 *
	 * Used by block() to gate page access. Token auto-expires via TTL.
	 * NOT consumed here — keeps URL functional for page reloads
	 * during the token's short lifetime.
	 *
	 * @return bool True if a valid token is present in the request.
	 */
	private function _hasValidToken(): bool {
		if ( ! isset( $_REQUEST[ self::CLU_TOKEN ] ) ) {
			return false;
		}

		$token = rawurldecode( sanitize_text_field( wp_unslash( $_REQUEST[ self::CLU_TOKEN ] ) ) );

		if ( empty( $token ) || strlen( $token ) !== self::TOKEN_LENGTH * 2 ) {
			return false;
		}

		$tokenHash     = $this->_hashToken( $token );
		$transientKey  = self::TOKEN_PREFIX . $tokenHash;
		$transientData = get_transient( $transientKey );

		if ( false === $transientData ) {
			return false;
		}

		if (
			! isset( $transientData['slug'] ) ||
			$transientData['slug'] !== $this->options['new_slug']
		) {
			return false;
		}

		if (
			! empty( $this->options['ip_check'] ) &&
			isset( $transientData['ip'] ) &&
			$transientData['ip'] !== Helper::ipAddress()
		) {
			return false;
		}

		return true;
	}

	/**
	 * Get the path without a home URL path.
	 *
	 * @param string $url
	 * @param bool $queryString
	 *
	 * @return string The URL path.
	 */
	private function _relativePath( string $url, bool $queryString = false ): string {
		$url_parts = wp_parse_url( $this->_homeUrl() );
		$home_path = ! empty( $url_parts['path'] ) ? trailingslashit( $url_parts['path'] ) : '/';

		$_temp_url = explode( '?', wp_make_link_relative( $url ) );
		$path      = wp_parse_url( $_temp_url[0], PHP_URL_PATH );

		if ( $queryString && ! empty( $_temp_url[1] ) ) {
			$path .= '?' . $_temp_url[1];
		}

		return $path ? trim( str_replace( $home_path, '', $path ), '/' ) : '';
	}

	/**
	 * Get site URL with proper scheme.
	 *
	 * @param string $path Optional path to append.
	 *
	 * @return string The site URL.
	 */
	private function _siteUrl( string $path = '' ): string {
		$url       = Helper::getOption( 'siteurl' );
		$urlParsed = wp_parse_url( $url );
		$scheme    = is_ssl() ? 'https' : ( $urlParsed['scheme'] ?? 'http' );
		$url       = set_url_scheme( $url, $scheme );

		if ( $path ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		return trailingslashit( $url );
	}

	/**
	 * Get home URL with proper scheme.
	 *
	 * @param string $path Optional path to append.
	 *
	 * @return string The home URL.
	 */
	private function _homeUrl( string $path = '' ): string {
		$url       = Helper::getOption( 'home' );
		$urlParsed = wp_parse_url( $url );
		$scheme    = is_ssl() ? 'https' : ( $urlParsed['scheme'] ?? 'http' );
		$url       = set_url_scheme( $url, $scheme );

		if ( $path ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		return trailingslashit( $url );
	}

	/**
	 * True if plugin enabled via option.
	 *
	 * @return bool
	 */
	private function _isEnabled(): bool {

		$opt              = LoginSecurityModule::getCachedOptions();
		$custom_login_uri = ! empty( $opt[ LoginSecurityModule::KEY_CUSTOM_LOGIN_URI ] ) ? $opt[ LoginSecurityModule::KEY_CUSTOM_LOGIN_URI ] : 'wp-login.php';

		// Set the required options.
		$this->options = [
			'new_slug' => $custom_login_uri,
			'redirect' => apply_filters( 'clu_login_redirect', $this->_homeUrl() ),
			'register' => apply_filters( 'clu_login_register', 'register' ),
			'ip_check' => ! empty( $opt[ LoginSecurityModule::KEY_LOGIN_TOKEN_IP_CHECK ] ),
		];

		if ( empty( $custom_login_uri ) || in_array( $custom_login_uri, [ 'wp-login.php', 'wp-admin' ] ) ) {
			return false;
		}

		// Warn about conflicting plugins (but don't disable — that would be a security hole).
		if ( is_admin() ) {
			$this->_warnConflictingPlugins();
		}

		return true;
	}

	/**
	 * Show admin notice if a conflicting plugin is active.
	 * Does NOT disable the feature — only warns the admin.
	 *
	 * @return void
	 */
	private function _warnConflictingPlugins(): void {
		$conflicting_plugins = [
			'wps-hide-login/wps-hide-login.php'         => 'WPS Hide Login',
			'perfmatters/perfmatters.php'               => 'Perfmatters',
			'loginizer/loginizer.php'                   => 'Loginizer',
			'better-wp-security/better-wp-security.php' => 'Solid Security',
			'hide-my-wp/index.php'                      => 'Hide My WP Ghost',
			'wp-simple-firewall/icwp-wpsf.php'          => 'Shield Security',
		];

		$active = [];
		foreach ( $conflicting_plugins as $plugin => $name ) {
			if ( Helper::checkPluginActive( $plugin ) ) {
				$active[] = $name;
			}
		}

		if ( empty( $active ) ) {
			return;
		}

		add_action(
			'admin_notices',
			static function () use ( $active ) {
				printf(
					'<div class="notice notice-warning"><p><strong>HDA:</strong> %s</p></div>',
					sprintf(
					/* translators: %s: list of conflicting plugin names */
						esc_html__( 'The following plugin(s) may conflict with the Custom Login URL feature: %s. Please deactivate them to avoid unexpected behavior.', 'hda' ),
						'<strong>' . esc_html( implode( ', ', $active ) ) . '</strong>'
					)
				);
			}
		);
	}
}
