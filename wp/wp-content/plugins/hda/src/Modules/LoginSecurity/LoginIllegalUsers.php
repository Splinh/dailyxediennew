<?php

namespace HDAddons\Modules\LoginSecurity;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class LoginIllegalUsers {

	/**
	 * Common usernames targeted by brute-force bots.
	 * Blocks registration/creation of accounts with these names.
	 *
	 * @var string[]
	 */
	private const COMMON_USERNAMES = [
		'admin',
		'admin1',
		'administrator',
		'root',
		'test',
		'test1',
		'user',
		'user1',
		'guest',
		'demo',
		'info',
		'support',
		'manager',
		'webmaster',
		'sysadmin',
		'operator',
		'superadmin',
		'wordpress',
	];

	// --------------------------------------------------

	public function __construct() {
		// Block registration of accounts with common usernames.
		add_filter( 'illegal_user_logins', $this->getIllegalUsernames( ... ) );

		// Block login attempts with common usernames BEFORE database lookup.
		// Priority 1 = runs before wp_authenticate_username_password (priority 20).
		add_filter( 'authenticate', $this->blockLoginAttempt( ... ), 1, 2 );
	}

	// --------------------------------------------------

	/**
	 * Block login attempts using common/illegal usernames.
	 *
	 * Runs before WordPress checks the database, so:
	 * - No DB query for known-bad usernames (saves resources)
	 * - No timing difference (prevents username enumeration)
	 * - Returns generic error (same as wrong password)
	 *
	 * @param mixed  $user     The user object, WP_Error, or null.
	 * @param string $username The attempted username.
	 *
	 * @return mixed|\WP_Error
	 */
	public function blockLoginAttempt( mixed $user, string $username ): mixed {
		// Skip if already resolved or empty username.
		if ( $user instanceof \WP_User || empty( $username ) ) {
			return $user;
		}

		$blocked = $this->getIllegalUsernames();

		if ( in_array( strtolower( $username ), $blocked, true ) ) {
			// Unhook WP Core authenticate to prevent the database lookup
			// and prevent core from overriding our WP_Error.
			remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
			remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );

			// Log blocked attempt via decoupled hook (Logs module listens).
			do_action( 'hda_log_event', 0, $username, 'blocked_username' );

			// Generic error message — intentionally identical to "wrong password"
			// to avoid revealing that this username is specifically blocked.
			return new \WP_Error(
				'authentication_failed',
				__( '<strong>Error</strong>: Invalid username or password.', 'hda' )
			);
		}

		return $user;
	}

	// --------------------------------------------------

	/**
	 * Get list of illegal usernames.
	 *
	 * @param string[] $usernames Additional usernames to block.
	 *
	 * @return string[] List of illegal usernames (lowercase).
	 */
	public function getIllegalUsernames( array $usernames = [] ): array {
		/** @var string[] $illegal_usernames */
		$illegal_usernames = apply_filters( '_illegal_users', $usernames );

		return array_map(
			'strtolower',
			array_merge(
				$illegal_usernames,
				self::COMMON_USERNAMES
			)
		);
	}

	// --------------------------------------------------

	/**
	 * Get common usernames list.
	 *
	 * @return string[]
	 */
	public static function getCommonUsernames(): array {
		return self::COMMON_USERNAMES;
	}
}
