<?php
/**
 * @package HDAT\Updater
 */

declare(strict_types=1);

namespace HDAT\Updater;

use YahnisElsts\PluginUpdateChecker\v5p7\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p7\Vcs\GitHubApi;

defined( 'ABSPATH' ) || exit;

/**
 * GitHub-based plugin auto-updater via plugin-update-checker (PUC).
 *
 * The Personal Access Token is stored encrypted in wp_options under its own
 * key, or read from the HDAT_GITHUB_TOKEN constant in wp-config.php.
 *
 * Crypto: Sodium XChaCha20-Poly1305 (`sodium_crypto_secretbox`) — authenticated
 * encryption. Key derived via BLAKE2b from WP salts with an app-specific
 * context. CRYPTO_CONTEXT must never change or all stored tokens break.
 */
final class GitHubUpdater {

	private const REPO_URL = 'https://github.com/HD-Agency/hdat';

	public const TOKEN_OPTION = '_hdat_github_token';

	private const CRYPTO_CONTEXT = 'hdat_plugin_encryption_v1';

	public function __construct() {
		$this->initUpdateChecker();
	}

	private function initUpdateChecker(): void {
		try {
			$checker = PucFactory::buildUpdateChecker(
				self::REPO_URL,
				HDAT_DIR . 'hdat.php',
				'hdat'
			);
			$checker->setBranch( 'main' );

			$vcsApi = $checker->getVcsApi();
			if ( $vcsApi instanceof GitHubApi ) {
				$vcsApi->enableReleaseAssets();
			}

			$token = $this->getToken();
			if ( $token ) {
				$checker->setAuthentication( $token );
			}
		} catch ( \Throwable ) {
			return;
		}
	}

	// ── Token resolution ────────────────────────────────────────────────

	/**
	 * Priority: DB option → HDAT_GITHUB_TOKEN constant → null.
	 */
	private function getToken(): ?string {
		$stored = get_option( self::TOKEN_OPTION, '' );
		if ( ! empty( $stored ) ) {
			$decrypted = self::decryptToken( $stored );
			if ( '' !== $decrypted ) {
				return $decrypted;
			}
		}

		if ( defined( 'HDAT_GITHUB_TOKEN' ) && \HDAT_GITHUB_TOKEN ) {
			return \HDAT_GITHUB_TOKEN;
		}

		return null;
	}

	// ── Crypto (Sodium XChaCha20-Poly1305) ──────────────────────────────

	public static function encryptToken( string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		try {
			$key   = self::deriveKey();
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

			$cipher = sodium_crypto_secretbox( $value, $nonce, $key );
			sodium_memzero( $key );

			return base64_encode( $nonce . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		} catch ( \Throwable ) {
			return '';
		}
	}

	public static function decryptToken( string $stored ): string {
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

			$key    = self::deriveKey();
			$nonce  = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

			$plain = sodium_crypto_secretbox_open( $cipher, $nonce, $key );
			sodium_memzero( $key );

			return false !== $plain ? $plain : '';
		} catch ( \Throwable ) {
			return '';
		}
	}

	public static function hasToken(): bool {
		return 'none' !== self::tokenSource();
	}

	/**
	 * Token source for status reporting.
	 *
	 * @return 'db'|'constant'|'none'
	 */
	public static function tokenSource(): string {
		$stored = get_option( self::TOKEN_OPTION, '' );
		if ( ! empty( $stored ) && '' !== self::decryptToken( $stored ) ) {
			return 'db';
		}

		if ( defined( 'HDAT_GITHUB_TOKEN' ) && \HDAT_GITHUB_TOKEN ) {
			return 'constant';
		}

		return 'none';
	}

	/**
	 * Derive a 32-byte key from WP salts via BLAKE2b.
	 *
	 * @throws \SodiumException
	 */
	private static function deriveKey(): string {
		$salt = ( defined( 'SECURE_AUTH_KEY' ) ? \SECURE_AUTH_KEY : '' )
			. ( defined( 'AUTH_SALT' ) ? \AUTH_SALT : '' );

		return sodium_crypto_generichash(
			$salt,
			self::CRYPTO_CONTEXT,
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES
		);
	}
}
