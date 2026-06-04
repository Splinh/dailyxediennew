<?php
/**
 * Settings manager.
 *
 * @package HDAC
 */

namespace HDAC;

defined( 'ABSPATH' ) || exit;

final class Settings {

	/**
	 * Option name in wp_options.
	 */
	private const OPTION_NAME = 'hdac_settings';

	/**
	 * Context string for Sodium key derivation.
	 */
	private const CRYPTO_CONTEXT = 'hdac_plugin_encryption_v1';

	/**
	 * Default settings.
	 */
	private const DEFAULTS = [
		'consumer_token'     => '',
		'temperature'        => 0.7,
		'max_tokens_title'   => 256,
		'max_tokens_excerpt' => 512,
		'max_tokens_content' => 2048,
		'max_tokens_image'   => 1024,
		'content_format'     => 'html',
		'features_enabled'   => [ 'title', 'excerpt', 'term-description', 'content', 'image-prompt' ],
	];

	/**
	 * Get a specific setting value.
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$all = self::all();
		return $all[ $key ] ?? $default;
	}

	/**
	 * Get all settings with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		return array_merge( self::DEFAULTS, $stored );
	}

	/**
	 * Save settings.
	 *
	 * @param array<string, mixed> $data Raw settings data from request.
	 *
	 * @return bool
	 */
	public static function save( array $data ): bool {
		$current = self::all();
		$new     = [];

		// 1. Connection settings (Consumer Token)
		if ( isset( $data['consumer_token'] ) ) {
			$token = sanitize_text_field( $data['consumer_token'] );
			// If it's masked, keep current token.
			if ( self::isMasked( $token ) ) {
				$new['consumer_token'] = $current['consumer_token'];
			} elseif ( '' === $token ) {
				$new['consumer_token'] = '';
			} else {
				$new['consumer_token'] = self::encryptToken( $token );
			}
		} else {
			$new['consumer_token'] = $current['consumer_token'];
		}

		// 2. Generation Settings
		if ( isset( $data['temperature'] ) ) {
			$temp               = (float) $data['temperature'];
			$new['temperature'] = min( 2.0, max( 0.0, $temp ) );
		} else {
			$new['temperature'] = $current['temperature'];
		}

		$new['max_tokens_title']   = isset( $data['max_tokens_title'] ) ? absint( $data['max_tokens_title'] ) : $current['max_tokens_title'];
		$new['max_tokens_excerpt'] = isset( $data['max_tokens_excerpt'] ) ? absint( $data['max_tokens_excerpt'] ) : $current['max_tokens_excerpt'];
		$new['max_tokens_content'] = isset( $data['max_tokens_content'] ) ? absint( $data['max_tokens_content'] ) : $current['max_tokens_content'];
		$new['max_tokens_image']   = isset( $data['max_tokens_image'] ) ? absint( $data['max_tokens_image'] ) : $current['max_tokens_image'];

		if ( isset( $data['content_format'] ) ) {
			$format                = sanitize_text_field( $data['content_format'] );
			$new['content_format'] = in_array( $format, [ 'html', 'plain' ], true ) ? $format : $current['content_format'];
		} else {
			$new['content_format'] = $current['content_format'];
		}

		// 3. Features enabled
		if ( isset( $data['features_enabled'] ) && is_array( $data['features_enabled'] ) ) {
			$allowed                 = [ 'title', 'excerpt', 'term-description', 'content', 'long-content', 'image-prompt' ];
			$new['features_enabled'] = array_values(
				array_filter(
					array_map( 'sanitize_key', $data['features_enabled'] ),
					static fn( $feat ) => in_array( $feat, $allowed, true )
				)
			);
		} else {
			$new['features_enabled'] = $current['features_enabled'];
		}

		return update_option( self::OPTION_NAME, $new );
	}

	/**
	 * Get decrypted consumer token.
	 */
	public static function consumerToken(): string {
		$stored = self::get( 'consumer_token', '' );
		if ( '' === $stored ) {
			return '';
		}

		return self::decryptToken( $stored );
	}

	/**
	 * Check if a feature toggle is enabled.
	 */
	public static function isFeatureEnabled( string $feature ): bool {
		$enabled = self::get( 'features_enabled', self::DEFAULTS['features_enabled'] );
		return is_array( $enabled ) && in_array( $feature, $enabled, true );
	}

	// ------------------------------------------------------------------
	// Encryption & Utility Helpers
	// ------------------------------------------------------------------

	/**
	 * Check if the input token is masked.
	 */
	public static function isMasked( string $token ): bool {
		return str_contains( $token, '****' );
	}

	/**
	 * Encrypt a plaintext token using Sodium.
	 */
	private static function encryptToken( string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		try {
			if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
				return base64_encode( $value ); // Fallback if sodium is not available
			}

			$key        = self::deriveKey();
			$nonce      = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ciphertext = sodium_crypto_secretbox( $value, $nonce, $key );
			sodium_memzero( $key );

			return base64_encode( $nonce . $ciphertext );
		} catch ( \Throwable ) {
			return '';
		}
	}

	/**
	 * Decrypt stored token.
	 */
	private static function decryptToken( string $stored ): string {
		if ( '' === $stored ) {
			return '';
		}

		try {
			$raw = base64_decode( $stored, true );
			if ( false === $raw ) {
				return '';
			}

			if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
				return $raw; // Fallback
			}

			$minLen = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;
			if ( strlen( $raw ) <= $minLen ) {
				return '';
			}

			$key        = self::deriveKey();
			$nonce      = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ciphertext = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

			$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );
			sodium_memzero( $key );

			return false !== $plaintext ? $plaintext : '';
		} catch ( \Throwable ) {
			return '';
		}
	}

	/**
	 * Derive 32-byte key from WP salts.
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
