<?php
/**
 * TOTP Handler — RFC 6238 Time-Based One-Time Passwords.
 *
 * Self-contained TOTP engine with DB-backed secret storage.
 * Ported from Wordfence Login Security controller/totp.php
 * and PHPGangsta_GoogleAuthenticator.
 *
 * @package HDAddons\Modules\LoginSecurity\Totp
 * @author  HD
 */

namespace HDAddons\Modules\LoginSecurity\Totp;

use HDAddons\DB;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class TotpHandler {

	// ─── TOTP Parameters (Google Authenticator compatible) ────
	public const PERIOD     = 30;     // Time step in seconds.
	public const DIGITS     = 6;      // Code length.
	public const ALGO       = 'sha1'; // HMAC algorithm.
	public const SECRET_LEN = 20;     // Random bytes for secret.
	public const WINDOW     = 1;      // ±1 time step tolerance.

	// ─── Database ────────────────────────────────────────────
	public const TABLE_NAME = 'hda_totp_secrets';

	private const TABLE_SCHEMA = <<<'SQL'
		user_id BIGINT UNSIGNED NOT NULL,
		secret TEXT NOT NULL,
		enabled TINYINT(1) NOT NULL DEFAULT 0,
		last_used INT UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (user_id)
	SQL;

	/**
	 * Cached table existence flag.
	 */
	private static ?bool $tableExistsCache = null;

	/**
	 * Cached rows per user ID to avoid repeated DB queries
	 * within the same request (e.g. isUserSetup + getLastUsed + getUserSecret).
	 *
	 * @var array<int, array|null>
	 */
	private static array $rowCache = [];

	// ══════════════════════════════════════════════════════════
	//  CORE TOTP
	// ══════════════════════════════════════════════════════════

	/**
	 * Generate a new random Base32-encoded secret.
	 *
	 * @return string 32-character Base32 string.
	 */
	public static function generateSecret(): string {
		return Base32::encode( random_bytes( self::SECRET_LEN ) );
	}

	/**
	 * Calculate a TOTP code for the given secret and time slice.
	 *
	 * @param string   $secret    Base32-encoded secret.
	 * @param int|null $timeSlice Time slice (floor(time/PERIOD)). Null = current.
	 *
	 * @return string Zero-padded 6-digit code.
	 */
	public static function generateCode( string $secret, ?int $timeSlice = null ): string {
		if ( $timeSlice === null ) {
			$timeSlice = self::currentTimeSlice();
		}

		$secretKey = Base32::decode( $secret );

		// Pack time slice as 8-byte big-endian.
		$time = pack( 'N', 0 ) . pack( 'N*', $timeSlice );

		// HMAC-SHA1.
		$hash = hash_hmac( self::ALGO, $time, $secretKey, true );

		// Dynamic truncation (RFC 4226 §5.4).
		$offset = ord( $hash[ strlen( $hash ) - 1 ] ) & 0x0F;

		$code = (
			( ( ord( $hash[ $offset ] ) & 0x7F ) << 24 ) |
			( ( ord( $hash[ $offset + 1 ] ) & 0xFF ) << 16 ) |
			( ( ord( $hash[ $offset + 2 ] ) & 0xFF ) << 8 ) |
			( ord( $hash[ $offset + 3 ] ) & 0xFF )
		) % ( 10 ** self::DIGITS );

		return str_pad( (string) $code, self::DIGITS, '0', STR_PAD_LEFT );
	}

	/**
	 * Verify a user-provided code against the secret.
	 *
	 * Checks ±window time slices and enforces replay protection.
	 *
	 * @param string $secret   Base32-encoded secret.
	 * @param string $code     User-entered code.
	 * @param int    $window   Number of time steps to check on each side.
	 * @param int    $lastUsed Last verified time slice (0 = none).
	 *
	 * @return int|false The matching time slice on success, false on failure.
	 */
	public static function verify( string $secret, string $code, int $window = self::WINDOW, int $lastUsed = 0 ): int|false {
		$code         = preg_replace( '/\s+/', '', $code ); // Strip whitespace.
		$currentSlice = self::currentTimeSlice();

		for ( $i = -$window; $i <= $window; $i++ ) {
			$testSlice = $currentSlice + $i;

			// Replay protection: skip already-used time slices.
			if ( $testSlice <= $lastUsed ) {
				continue;
			}

			if ( hash_equals( self::generateCode( $secret, $testSlice ), $code ) ) {
				return $testSlice;
			}
		}

		return false;
	}

	/**
	 * Build an otpauth:// URI for QR code generation.
	 *
	 * @param string $secret Base32-encoded secret.
	 * @param string $login  User login name.
	 * @param string $issuer Site identifier.
	 *
	 * @return string otpauth:// URI.
	 */
	public static function getOtpauthUri( string $secret, string $login, string $issuer ): string {
		$label = rawurlencode( $issuer . ':' . $login );

		return sprintf(
			'otpauth://totp/%s?secret=%s&algorithm=SHA1&digits=%d&period=%d&issuer=%s',
			$label,
			$secret,
			self::DIGITS,
			self::PERIOD,
			rawurlencode( $issuer )
		);
	}

	/**
	 * Get the current time slice.
	 *
	 * @return int
	 */
	private static function currentTimeSlice(): int {
		return (int) floor( time() / self::PERIOD );
	}

	// ══════════════════════════════════════════════════════════
	//  ENCRYPTION — TOTP Secret Protection
	// ══════════════════════════════════════════════════════════

	/**
	 * Encrypt a TOTP secret before storing in database.
	 *
	 * Uses the plugin crypto facade, backed by Sodium secretbox.
	 *
	 * @param string $plaintext Base32-encoded TOTP secret.
	 *
	 * @return string Encrypted secret (base64-encoded ciphertext).
	 */
	private static function encryptSecret( string $plaintext ): string {
		return Helper::encryptValue( $plaintext );
	}

	/**
	 * Decrypt a TOTP secret after retrieving from database.
	 *
	 * Uses the plugin crypto facade, backed by Sodium secretbox.
	 *
	 * @param string $encrypted Encrypted secret (base64-encoded ciphertext).
	 *
	 * @return string|false Decrypted Base32 secret, or false on failure.
	 */
	private static function decryptSecret( string $encrypted ): string|false {
		$decrypted = Helper::decryptValue( $encrypted );

		return '' !== $decrypted ? $decrypted : false;
	}

	// ══════════════════════════════════════════════════════════
	//  DATABASE — TABLE MANAGEMENT
	// ══════════════════════════════════════════════════════════

	/**
	 * Check if table exists (cached per request).
	 */
	public static function tableExists(): bool {
		return self::$tableExistsCache ??= DB::tableExists( self::TABLE_NAME );
	}

	/**
	 * Encrypt existing plaintext TOTP secrets in-place.
	 *
	 * @return int Number of rows migrated.
	 */
	public static function migratePlaintextSecrets(): int {
		if ( ! self::tableExists() ) {
			return 0;
		}

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );
		$rows  = $db->get_results( "SELECT user_id, secret FROM {$table} WHERE secret <> ''", ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return 0;
		}

		$migrated = 0;

		foreach ( $rows as $row ) {
			$userId = (int) ( $row['user_id'] ?? 0 );
			$secret = (string) ( $row['secret'] ?? '' );

			if ( $userId <= 0 || ! self::isLegacyPlaintextSecret( $secret ) ) {
				continue;
			}

			$encrypted = self::encryptSecret( $secret );
			if ( '' === $encrypted || $encrypted === $secret ) {
				continue;
			}

			$updated = $db->update(
				$table,
				[ 'secret' => $encrypted ],
				[ 'user_id' => $userId ],
				[ '%s' ],
				[ '%d' ]
			);

			if ( false !== $updated ) {
				unset( self::$rowCache[ $userId ] );
				++$migrated;
			}
		}

		return $migrated;
	}

	/**
	 * Check whether a stored value looks like a legacy plaintext Base32 secret.
	 */
	private static function isLegacyPlaintextSecret( string $secret ): bool {
		if ( '' === $secret || false !== self::decryptSecret( $secret ) ) {
			return false;
		}

		$normalized = strtoupper( rtrim( $secret, '=' ) );

		return 1 === preg_match( '/^[A-Z2-7]{16,64}$/', $normalized );
	}

	// ══════════════════════════════════════════════════════════
	//  DATABASE — USER CRUD
	// ══════════════════════════════════════════════════════════

	/**
	 * Get the TOTP row for a user.
	 *
	 * @param int $userId WordPress user ID.
	 *
	 * @return array|null Row data or null.
	 */
	public static function getRow( int $userId ): ?array {
		if ( ! self::tableExists() ) {
			return null;
		}

		if ( ! array_key_exists( $userId, self::$rowCache ) ) {
			self::$rowCache[ $userId ] = DB::getOne( self::TABLE_NAME, 'user_id = %d', [ $userId ] );
		}

		return self::$rowCache[ $userId ];
	}

	/**
	 * Check if a user has completed TOTP setup.
	 *
	 * @param int $userId WordPress user ID.
	 *
	 * @return bool
	 */
	public static function isUserSetup( int $userId ): bool {
		$row = self::getRow( $userId );

		return $row !== null
			&& ! empty( $row['secret'] )
			&& (int) $row['enabled'] === 1;
	}

	/**
	 * Get the user's Base32 secret (decrypted).
	 *
	 * Handles backward compatibility: if decryption fails,
	 * assumes the secret is stored as plaintext (legacy format).
	 *
	 * @param int $userId WordPress user ID.
	 *
	 * @return string Decrypted Base32 secret, or empty string.
	 */
	public static function getUserSecret( int $userId ): string {
		$row = self::getRow( $userId );

		if ( empty( $row['secret'] ) ) {
			return '';
		}

		$encrypted = $row['secret'];

		// Attempt decryption
		$decrypted = self::decryptSecret( $encrypted );

		// Backward compatibility for secrets stored before encryption was implemented.
		if ( $decrypted === false ) {
			return self::isLegacyPlaintextSecret( $encrypted ) ? $encrypted : '';
		}

		return $decrypted;
	}

	/**
	 * Save (upsert) a secret for a user.
	 *
	 * Encrypts the secret before storing in database.
	 *
	 * @param int    $userId WordPress user ID.
	 * @param string $secret Base32-encoded TOTP secret (plaintext).
	 */
	public static function saveSecret( int $userId, string $secret ): void {
		if ( ! self::tableExists() ) {
			return;
		}

		// Encrypt secret before storing
		$encrypted = self::encryptSecret( $secret );

		$db    = DB::db();
		$table = DB::tableNameFull( self::TABLE_NAME );

		$db->query(
			$db->prepare(
				"INSERT INTO {$table} (user_id, secret, enabled, last_used, created_at)
				VALUES (%d, %s, 0, 0, %s)
				ON DUPLICATE KEY UPDATE secret = VALUES(secret), enabled = 0, last_used = 0",
				$userId,
				$encrypted,
				gmdate( 'Y-m-d H:i:s' )
			)
		);

		// Invalidate cache for this user
		unset( self::$rowCache[ $userId ] );
	}

	/**
	 * Mark TOTP as enabled (verified) for a user.
	 *
	 * @param int $userId WordPress user ID.
	 */
	public static function enableForUser( int $userId ): void {
		if ( ! self::tableExists() ) {
			return;
		}

		DB::updateOneRow( self::TABLE_NAME, $userId, [ 'enabled' => '1' ], 'user_id' );
		unset( self::$rowCache[ $userId ] );
	}

	/**
	 * Remove TOTP data for a user (full reset).
	 *
	 * @param int $userId WordPress user ID.
	 */
	public static function disableForUser( int $userId ): void {
		if ( ! self::tableExists() ) {
			return;
		}

		DB::deleteOneRow( self::TABLE_NAME, $userId, 'user_id' );
		unset( self::$rowCache[ $userId ] );
	}

	/**
	 * Get the last used time slice for replay protection.
	 *
	 * @param int $userId WordPress user ID.
	 *
	 * @return int Time slice number (0 = never used).
	 */
	public static function getLastUsed( int $userId ): int {
		$row = self::getRow( $userId );

		return (int) ( $row['last_used'] ?? 0 );
	}

	/**
	 * Update the last used time slice after successful verification.
	 *
	 * @param int $userId    WordPress user ID.
	 * @param int $timeSlice The verified time slice.
	 */
	public static function setLastUsed( int $userId, int $timeSlice ): void {
		if ( ! self::tableExists() ) {
			return;
		}

		DB::updateOneRow(
			self::TABLE_NAME,
			$userId,
			[ 'last_used' => (string) $timeSlice ],
			'user_id'
		);
		unset( self::$rowCache[ $userId ] );
	}
}
