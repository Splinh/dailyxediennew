<?php
/**
 * Magic Link Handler — Passwordless login via email link.
 *
 * Manages token generation, validation, consumption, email sending,
 * rate limiting and expired token cleanup.
 *
 * @package HDAddons\Modules\LoginSecurity\MagicLink
 * @author  HD
 */

namespace HDAddons\Modules\LoginSecurity\MagicLink;

use HDAddons\DB;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class MagicLinkHandler {

	// ─── Configuration ────────────────────────────────────
	public const TOKEN_EXPIRY = 300;  // 5 minutes in seconds.
	public const MAX_REQUESTS = 3;    // Max requests per rate window.
	public const RATE_WINDOW  = 600;  // Rate limit window (10 min).
	public const TOKEN_LENGTH = 32;   // Random bytes → 64 hex chars.

	// ─── Database ─────────────────────────────────────────
	public const TABLE_NAME = 'hda_magic_links';

	private const TABLE_SCHEMA = <<<'SQL'
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		token VARCHAR(64) NOT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		expires_at DATETIME NOT NULL,
		used_at DATETIME NULL DEFAULT NULL,
		ip_address VARCHAR(45) NOT NULL DEFAULT '',
		PRIMARY KEY (id),
		UNIQUE KEY idx_token (token),
		KEY idx_user_expires (user_id, expires_at)
	SQL;

	/**
	 * Cached table existence flag.
	 */
	private static ?bool $tableExistsCache = null;

	// ══════════════════════════════════════════════════════
	//  TABLE MANAGEMENT
	// ══════════════════════════════════════════════════════

	/**
	 * Check if table exists (cached per request).
	 */
	public static function tableExists(): bool {
		return self::$tableExistsCache ??= DB::tableExists( self::TABLE_NAME );
	}

	// ══════════════════════════════════════════════════════
	//  TOKEN OPERATIONS
	// ══════════════════════════════════════════════════════

	/**
	 * Generate a magic link token for a user.
	 *
	 * Invalidates any previous unused tokens for the same user.
	 *
	 * @param int    $userId    WordPress user ID.
	 * @param string $ipAddress Client IP address.
	 *
	 * @return string The generated 64-character hex token.
	 */
	public static function generateToken( int $userId, string $ipAddress = '' ): string {
		if ( ! self::tableExists() ) {
			return '';
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		// Invalidate previous unused tokens for this user
		$db->query(
			$db->prepare(
				"UPDATE {$table} SET used_at = %s WHERE user_id = %d AND used_at IS NULL",
				gmdate( 'Y-m-d H:i:s' ),
				$userId
			)
		);

		// Generate cryptographically secure token
		$token     = bin2hex( random_bytes( self::TOKEN_LENGTH ) );
		$tokenHash = hash( 'sha256', $token );
		$now       = gmdate( 'Y-m-d H:i:s' );
		$expiresAt = gmdate( 'Y-m-d H:i:s', time() + self::TOKEN_EXPIRY );

		$db->insert(
			$table,
			[
				'user_id'    => $userId,
				'token'      => $tokenHash,
				'created_at' => $now,
				'expires_at' => $expiresAt,
				'ip_address' => $ipAddress,
			],
			[ '%d', '%s', '%s', '%s', '%s' ]
		);

		return $token;
	}

	/**
	 * Validate a magic link token.
	 *
	 * @param string $token The token to validate.
	 *
	 * @return array|null Row data on success, null on failure.
	 */
	public static function validateToken( string $token ): ?array {
		if ( ! self::tableExists() || empty( $token ) ) {
			return null;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$tokenHash = hash( 'sha256', $token );

		$row = $db->get_row(
			$db->prepare(
				"SELECT * FROM {$table} WHERE token = %s LIMIT 1",
				$tokenHash
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		// Check if already used
		if ( $row['used_at'] !== null ) {
			return null;
		}

		// Check if expired (expires_at is stored as UTC via gmdate)
		if ( strtotime( $row['expires_at'] . ' UTC' ) < time() ) {
			return null;
		}

		return $row;
	}

	/**
	 * Consume (mark as used) a magic link token.
	 *
	 * @param string $token The token to consume.
	 */
	public static function consumeToken( string $token ): void {
		if ( ! self::tableExists() ) {
			return;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$tokenHash = hash( 'sha256', $token );

		$db->update(
			$table,
			[ 'used_at' => gmdate( 'Y-m-d H:i:s' ) ],
			[ 'token' => $tokenHash ],
			[ '%s' ],
			[ '%s' ]
		);
	}

	// ══════════════════════════════════════════════════════
	//  EMAIL
	// ══════════════════════════════════════════════════════

	/**
	 * Send a magic link email to the user.
	 *
	 * @param \WP_User $user     The user object.
	 * @param string   $loginUrl The magic link URL.
	 *
	 * @return bool True on success.
	 */
	public static function sendEmail( \WP_User $user, string $loginUrl ): bool {
		$siteName = wp_specialchars_decode( Helper::getOption( 'blogname' ), ENT_QUOTES );
		$expiry   = (int) ceil( self::TOKEN_EXPIRY / 60 );

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Your Magic Login Link', 'hda' ),
			$siteName
		);

		$message = sprintf(
			/* translators: 1: user login, 2: magic link URL, 3: expiry minutes, 4: site name */
			__(
				"Hello %1\$s,\n\n" .
				"Click the link below to log in to %4\$s:\n\n" .
				"%2\$s\n\n" .
				"This link will expire in %3\$d minutes and can only be used once.\n\n" .
				"If you didn't request this, please ignore this email.",
				'hda'
			),
			$user->user_login,
			$loginUrl,
			$expiry,
			$siteName
		);

		return wp_mail( $user->user_email, $subject, $message );
	}

	// ══════════════════════════════════════════════════════
	//  RATE LIMITING
	// ══════════════════════════════════════════════════════

	/**
	 * Check if the user is rate-limited.
	 *
	 * @param int $userId WordPress user ID.
	 *
	 * @return bool True if the user can request a new link.
	 */
	public static function canRequest( int $userId ): bool {
		$key   = 'hda_ml_rate_' . $userId;
		$count = (int) get_transient( $key );

		return $count < self::MAX_REQUESTS;
	}

	/**
	 * Increment the rate limit counter.
	 *
	 * @param int $userId WordPress user ID.
	 */
	public static function incrementRate( int $userId ): void {
		$key   = 'hda_ml_rate_' . $userId;
		$count = (int) get_transient( $key );

		set_transient( $key, $count + 1, self::RATE_WINDOW );
	}

	// ══════════════════════════════════════════════════════
	//  CLEANUP
	// ══════════════════════════════════════════════════════

	/**
	 * Delete expired and used tokens older than 24 hours.
	 */
	public static function cleanupExpired(): void {
		if ( ! self::tableExists() ) {
			return;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$db->query(
			$db->prepare(
				"DELETE FROM {$table} WHERE expires_at < %s OR (used_at IS NOT NULL AND used_at < %s)",
				gmdate( 'Y-m-d H:i:s' ),
				gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS )
			)
		);
	}
}
