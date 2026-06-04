<?php
/**
 * @package HDAT\Infrastructure\Crypto
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Crypto;

defined( 'ABSPATH' ) || exit;

/**
 * Authenticated encryption for credential API keys.
 *
 * Uses libsodium's XChaCha20-Poly1305 secret box (`sodium_crypto_secretbox`):
 * - Authenticated: MAC verification rejects tampered ciphertext (no manual HMAC).
 * - 192-bit nonce: random per message, no birthday-bound concerns.
 * - Memory-safe: `sodium_memzero()` wipes the derived key after each use.
 *
 * Key derivation matches `themes/hd` (`HD\Traits\Encryption`) and `plugins/hda`
 * (`HDAddons\Traits\Crypto`): keyed BLAKE2b over `SECRET_KEY` with an
 * application-specific context for domain separation. Reusing the same
 * derivation lets a future shared-secret rotation touch all three at once.
 *
 * Output layout: `base64( nonce(24) | ciphertext(plain+MAC) )`.
 *
 * SECRET_KEY MUST be defined (in `.env` → `wp-config.php`). We deliberately
 * fail loudly rather than fall back to WP salts — silent fallbacks were the
 * source of the previous round of decrypt-returns-empty bugs.
 */
final class KeyEncryptor {

	private const CONTEXT = 'hdat_credential_encryption_v2';

	public function encrypt( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}

		$key   = $this->deriveKey();
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		$cipher = sodium_crypto_secretbox( $plain, $nonce, $key );
		sodium_memzero( $key );

		return base64_encode( $nonce . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	public function decrypt( string $encoded ): string {
		if ( '' === $encoded ) {
			return '';
		}

		$raw = base64_decode( $encoded, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $raw ) {
			return '';
		}

		$minLen = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;
		if ( strlen( $raw ) <= $minLen ) {
			return '';
		}

		$key    = $this->deriveKey();
		$nonce  = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		$plain = sodium_crypto_secretbox_open( $cipher, $nonce, $key );
		sodium_memzero( $key );

		return false === $plain ? '' : $plain;
	}

	/**
	 * 8-char fingerprint for UI matching — never reveals the key itself.
	 */
	public function fingerprint( string $plain ): string {
		return substr( hash( 'sha256', $plain ), -8 );
	}

	private function deriveKey(): string {
		if ( ! defined( 'SECRET_KEY' ) || '' === \SECRET_KEY ) {
			throw new \RuntimeException(
				'SECRET_KEY is not configured. Define SECRET_KEY in your .env file (loaded via wp-config.php).'
			);
		}

		return sodium_crypto_generichash(
			\SECRET_KEY,
			self::CONTEXT,
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES
		);
	}
}
