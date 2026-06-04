<?php
/**
 * Honeypot Guard - zero-friction bot detection.
 *
 * @package HD\Modules\Form\Security
 */

namespace HD\Modules\Form\Security;

defined( 'ABSPATH' ) || exit;

final class HoneypotGuard {
	private const LEGACY_FIELD_NAME = '_hp_field';
	private const FIELD_PREFIX      = '_hp_';
	private const META_NAME         = '_hp_name';
	private const META_TIMESTAMP    = '_hp_ts';
	private const META_SIGNATURE    = '_hp_sig';
	private const TOKEN_MAX_AGE     = 86400;

	/**
	 * Build the signed honeypot metadata exposed to frontend form JS.
	 *
	 * @return array{field: string, timestamp: int, signature: string}
	 */
	public static function payload( ?int $timestamp = null ): array {
		$timestamp = $timestamp ?? time();
		$fieldName = self::fieldName( $timestamp );

		return [
			'field'     => $fieldName,
			'timestamp' => $timestamp,
			'signature' => self::signature( $fieldName, $timestamp ),
		];
	}

	/**
	 * Check if the honeypot field was filled or tampered with.
	 *
	 * @param array<string, mixed> $input Raw request payload.
	 */
	public static function isBot( array $input ): bool {
		$fieldName = sanitize_key( (string) ( $input[ self::META_NAME ] ?? '' ) );
		$timestamp = (int) ( $input[ self::META_TIMESTAMP ] ?? 0 );
		$signature = (string) ( $input[ self::META_SIGNATURE ] ?? '' );

		if ( '' !== $fieldName || $timestamp > 0 || '' !== $signature ) {
			if ( ! self::isValidPayload( $fieldName, $timestamp, $signature ) ) {
				return true;
			}

			return '' !== trim( (string) ( $input[ $fieldName ] ?? '' ) );
		}

		$value = $input[ self::LEGACY_FIELD_NAME ] ?? '';

		return '' !== trim( (string) $value );
	}

	private static function isValidPayload( string $fieldName, int $timestamp, string $signature ): bool {
		if ( '' === $fieldName || $timestamp <= 0 || '' === $signature ) {
			return false;
		}

		if ( ! str_starts_with( $fieldName, self::FIELD_PREFIX ) ) {
			return false;
		}

		$age = time() - $timestamp;
		if ( $age < 0 || $age > self::TOKEN_MAX_AGE ) {
			return false;
		}

		$expected = self::payload( $timestamp );
		if ( $fieldName !== $expected['field'] ) {
			return false;
		}

		return hash_equals( $expected['signature'], $signature );
	}

	private static function fieldName( int $timestamp ): string {
		return self::FIELD_PREFIX . substr( wp_hash( 'honeypot_field|' . $timestamp, 'nonce' ), 0, 16 );
	}

	private static function signature( string $fieldName, int $timestamp ): string {
		return wp_hash( 'honeypot_signature|' . $fieldName . '|' . $timestamp, 'nonce' );
	}
}
