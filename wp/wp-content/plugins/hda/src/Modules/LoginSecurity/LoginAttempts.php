<?php

namespace HDAddons\Modules\LoginSecurity;

use HDAddons\Core\RateLimitStorage;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class LoginAttempts {
	/**
	 * The maximum allowed login attempts.
	 *
	 * @var int
	 */
	private int $limit_login_attempts = 0;

	/**
	 * Login attempts data for admin UI.
	 *
	 * @var array<int, string>
	 */
	public static array $login_attempts_data = [
		0  => 'OFF',
		3  => '3',
		5  => '5',
		10 => '10',
		15 => '15',
		20 => '20',
	];

	// --------------------------------------------------

	public function __construct() {

		$_options                   = LoginSecurityModule::getCachedOptions();
		$this->limit_login_attempts = (int) ( $_options[ LoginSecurityModule::KEY_LIMIT_LOGIN_ATTEMPTS ] ?? 0 );

		add_action( 'login_head', $this->maybeBlockLoginAccess( ... ), PHP_INT_MIN );
		add_filter( 'login_errors', $this->logLoginAttempt( ... ) );
		add_action( 'wp_login', $this->resetLoginAttempts( ... ) );
	}

	// --------------------------------------------------

	/**
	 * Block login access if IP has exceeded attempts limit.
	 *
	 * @return void
	 */
	public function maybeBlockLoginAccess(): void {
		$user_ip  = Helper::ipAddress();
		$attempts = RateLimitStorage::get( $user_ip, 'login_attempts' );

		// Bail if the user doesn't have attempts or hasn't reached the limit.
		if ( 0 === $attempts || $attempts < $this->limit_login_attempts ) {
			return;
		}

		// Hit limit. Block them.
		Helper::incrementCounter( '_security_total_blocked_logins' );
		Helper::errorLog( 'Too many incorrect login attempts. - ' . $user_ip );

		// Fire action for TrafficMonitor / Firewall integration.
		do_action( 'hda_login_blocked', $user_ip, $attempts );

		wp_die(
			esc_html__( 'Access to login page is currently restricted because of too many incorrect login attempts.', 'hda' ),
			esc_html__( 'Restricted access', 'hda' ),
			[
				'hda_error'     => true,
				'response'      => 403,
				'blocked_login' => true,
			]
		);
	}

	// --------------------------------------------------

	/**
	 * Add a login attempt for a specific IP address.
	 *
	 * @param string $error The error message.
	 *
	 * @return string The modified error message.
	 */
	public function logLoginAttempt( string $error ): string {
		global $errors;

		// Check for errors global since the custom login urls plugin is not always returning it.
		if ( empty( $errors ) ) {
			return $error;
		}

		// Skip for invalid/empty credentials (not actual login attempts).
		$skip_codes = [ 'empty_username', 'invalid_username', 'empty_password' ];
		if ( array_intersect( $skip_codes, $errors->get_error_codes() ) ) {
			return $error;
		}

		$user_ip = Helper::ipAddress();

		// Increase the attempt count, sliding window of 1 hour by default.
		$attempts = RateLimitStorage::increment( $user_ip, 'login_attempts', HOUR_IN_SECONDS );
		$limit    = $this->limit_login_attempts;

		$errors->add(
			'login_attempts',
			sprintf(
				'<strong>%s</strong> %s <strong>%d</strong> %s',
				esc_html__( 'Alert:', 'hda' ),
				esc_html__( 'You have entered the wrong credentials', 'hda' ),
				$attempts,
				esc_html__( 'times.', 'hda' )
			)
		);

		if (
			in_array( 'incorrect_password', $errors->get_error_codes(), true ) &&
			in_array( 'login_attempts', $errors->get_error_codes(), true )
		) {
			$error_message = $errors->get_error_messages( 'login_attempts' );
			$error        .= "\t" . $error_message[0] . "<br />\n";
		}

		// If threshold reached, escalate the ban by extending the TTL.
		if ( $attempts >= $limit ) {
			$banWindow = match ( true ) {
				$attempts >= $limit * 3 => WEEK_IN_SECONDS,
				$attempts >= $limit * 2 => DAY_IN_SECONDS,
				default                 => HOUR_IN_SECONDS,
			};

			RateLimitStorage::extendExpiration( $user_ip, 'login_attempts', $banWindow );
		}

		return $error;
	}

	// --------------------------------------------------

	/**
	 * Reset login attempts for current IP.
	 *
	 * @return void
	 */
	public function resetLoginAttempts(): void {
		$user_ip = Helper::ipAddress();
		RateLimitStorage::reset( $user_ip, 'login_attempts' );
	}
}
