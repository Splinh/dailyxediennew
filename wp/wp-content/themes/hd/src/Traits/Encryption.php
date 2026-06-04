<?php
/**
 * Secure encryption using PHP Sodium (libsodium).
 *
 * Uses XChaCha20-Poly1305 which is:
 * - More secure than AES-CBC
 * - Built-in since PHP 7.2+
 * - Includes automatic authentication (no separate HMAC needed)
 * - Memory-safe with automatic key zeroing
 *
 * @author HD
 */

namespace HD\Traits;

defined( 'ABSPATH' ) || exit;

trait Encryption {

	/**
	 * Application-specific salt for key derivation.
	 * This provides domain separation to prevent key reuse across different applications.
	 */
	private static string $keyContext = 'hd_theme_encryption_v1';

	/**
	 * Derive a 32-byte encryption key from SECRET_KEY.
	 * Uses BLAKE2b hash (faster and more secure than SHA-256).
	 *
	 * SECRET_KEY should be defined in wp-config.php (loaded from .env).
	 *
	 * @return string 32-byte key for Sodium secret box
	 * @throws \RuntimeException If SECRET_KEY is not configured
	 * @throws \SodiumException
	 */
	private static function getKey(): string {
		if ( ! defined( 'SECRET_KEY' ) || \SECRET_KEY === '' ) {
			throw new \RuntimeException(
				'SECRET_KEY is not configured. Please define SECRET_KEY in your .env file.'
			);
		}

		// Keyed BLAKE2b: SECRET_KEY as message, application context as BLAKE2b key
		// This provides domain separation - same SECRET_KEY won't produce same derived keys in different apps
		return sodium_crypto_generichash(
			\SECRET_KEY,
			self::$keyContext,
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES
		);
	}

	// --------------------------------------------------

	/**
	 * Encrypt data using Sodium XChaCha20-Poly1305.
	 *
	 * Output format: base64( nonce + ciphertext )
	 * - Nonce: 24 bytes (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)
	 * - Ciphertext: original length + 16 bytes MAC
	 *
	 * @param string|null $data Plain text to encrypt
	 *
	 * @return string|null Base64 encoded encrypted data, or null if input is null/empty
	 * @throws \SodiumException
	 * @throws \Exception
	 */
	public static function encode( ?string $data ): ?string {
		if ( $data === null || $data === '' ) {
			return null;
		}

		$key   = self::getKey();
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ); // 24 bytes

		$ciphertext = sodium_crypto_secretbox( $data, $nonce, $key );

		// Zero out key from memory for security
		sodium_memzero( $key );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required to store binary nonce and ciphertext as text.
		return base64_encode( $nonce . $ciphertext );
	}

	// --------------------------------------------------

	/**
	 * Decrypt data encrypted with encode().
	 *
	 * Automatically verifies authenticity (MAC check built into Sodium).
	 * Returns null if decryption fails (invalid data or tampering detected).
	 *
	 * @param string|null $encoded Base64 encoded encrypted data
	 *
	 * @return string|null Decrypted plain text, or null on failure
	 * @throws \SodiumException
	 */
	public static function decode( ?string $encoded ): ?string {
		if ( $encoded === null || $encoded === '' ) {
			return null;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Required to read binary nonce and ciphertext from text storage.
		$decoded = base64_decode( $encoded, true );
		if ( $decoded === false ) {
			return null;
		}

		// Minimum length: nonce (24) + MAC (16) = 40 bytes
		if ( strlen( $decoded ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
			return null;
		}

		$key        = self::getKey();
		$nonce      = substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

		// Zero out key from memory for security
		sodium_memzero( $key );

		// sodium_crypto_secretbox_open returns false if MAC verification fails
		return $plaintext === false ? null : $plaintext;
	}
}
