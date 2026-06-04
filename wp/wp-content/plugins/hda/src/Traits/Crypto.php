<?php
/**
 * Crypto utility trait.
 *
 * Symmetric authenticated encryption using PHP Sodium (libsodium).
 * Uses XChaCha20-Poly1305 which provides:
 * - Built-in authentication (no separate HMAC needed)
 * - Memory-safe with automatic key zeroing
 * - Available on all PHP 7.2+ installations
 *
 * Key is derived from WP security salts (SECURE_AUTH_KEY + AUTH_SALT)
 * via BLAKE2b — unique per site, never hardcoded in source.
 *
 * Usage: Helper::encryptValue( $plaintext )
 *        Helper::decryptValue( $ciphertext )
 *
 * @package HDAddons\Traits
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Crypto {

	/**
	 * Application-specific context for key derivation (domain separation).
	 */
	private static string $cryptoContext = 'hda_plugin_encryption_v1';

	// --------------------------------------------------

	/**
	 * Encrypt a plaintext string for storage in wp_options.
	 *
	 * Output format: base64( nonce(24) + ciphertext + MAC(16) )
	 *
	 * @param string $value Plaintext value to encrypt.
	 *
	 * @return string Encrypted value (base64) or empty string on failure.
	 */
	public static function encryptValue( string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		try {
			$key   = self::deriveEncryptionKey();
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

			$ciphertext = sodium_crypto_secretbox( $value, $nonce, $key );

			sodium_memzero( $key );

			return base64_encode( $nonce . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		} catch ( \Throwable ) {
			return '';
		}
	}

	// --------------------------------------------------

	/**
	 * Decrypt an encrypted string from wp_options.
	 *
	 * Returns empty string if MAC verification fails (tampered data).
	 *
	 * @param string $stored Encrypted value (base64) from DB.
	 *
	 * @return string Plaintext value or empty string on failure.
	 */
	public static function decryptValue( string $stored ): string {
		if ( '' === $stored ) {
			return '';
		}

		try {
			$raw = base64_decode( $stored, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			if ( false === $raw ) {
				return '';
			}

			$minLen = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;
			if ( strlen( $raw ) <= $minLen ) {
				return '';
			}

			$key        = self::deriveEncryptionKey();
			$nonce      = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ciphertext = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

			$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

			sodium_memzero( $key );

			return false !== $plaintext ? $plaintext : '';
		} catch ( \Throwable ) {
			return '';
		}
	}

	// --------------------------------------------------

	/**
	 * Derive a 32-byte encryption key from SECRET_KEY (fallback to WP salts).
	 *
	 * Uses BLAKE2b (faster than SHA-256) with application-specific context
	 * for domain separation.
	 *
	 * @return string 32-byte raw key (SODIUM_CRYPTO_SECRETBOX_KEYBYTES).
	 * @throws \SodiumException
	 */
	private static function deriveEncryptionKey(): string {
		$secret = defined( 'SECRET_KEY' ) && \SECRET_KEY !== ''
			? \SECRET_KEY
			: ( defined( 'SECURE_AUTH_KEY' ) ? \SECURE_AUTH_KEY : '' ) . ( defined( 'AUTH_SALT' ) ? \AUTH_SALT : '' );

		return sodium_crypto_generichash(
			$secret,
			self::$cryptoContext,
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES
		);
	}
}
