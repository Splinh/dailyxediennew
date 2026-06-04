<?php
/**
 * Base32 Encoder/Decoder — RFC 4648.
 *
 * Ported from PHPGangsta_GoogleAuthenticator and Wordfence Login Security
 * for use without external Composer dependencies.
 *
 * @package HDAddons\Modules\LoginSecurity\Totp
 * @author  HD
 */

namespace HDAddons\Modules\LoginSecurity\Totp;

\defined( 'ABSPATH' ) || exit;

final class Base32 {

	/**
	 * RFC 4648 Base32 alphabet.
	 */
	private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * Encode raw binary data to Base32 string.
	 *
	 * @param string $data Raw binary data.
	 *
	 * @return string Base32 encoded string.
	 */
	public static function encode( string $data ): string {
		if ( $data === '' ) {
			return '';
		}

		$binary = '';
		foreach ( str_split( $data ) as $char ) {
			$binary .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
		}

		$result = '';
		$chunks = str_split( $binary, 5 );

		foreach ( $chunks as $chunk ) {
			// Pad the last chunk to 5 bits if needed.
			$chunk   = str_pad( $chunk, 5, '0', STR_PAD_RIGHT );
			$result .= self::ALPHABET[ bindec( $chunk ) ];
		}

		return $result;
	}

	/**
	 * Decode Base32 string to raw binary data.
	 *
	 * @param string $base32 Base32 encoded string.
	 *
	 * @return string Raw binary data.
	 */
	public static function decode( string $base32 ): string {
		if ( $base32 === '' ) {
			return '';
		}

		// Normalize: uppercase, strip padding.
		$base32 = rtrim( strtoupper( $base32 ), '=' );

		$binary = '';
		foreach ( str_split( $base32 ) as $char ) {
			$index = strpos( self::ALPHABET, $char );
			if ( $index === false ) {
				continue; // Skip invalid characters.
			}
			$binary .= str_pad( decbin( $index ), 5, '0', STR_PAD_LEFT );
		}

		$result = '';
		$octets = str_split( $binary, 8 );

		foreach ( $octets as $octet ) {
			if ( strlen( $octet ) < 8 ) {
				break; // Discard trailing bits.
			}
			$result .= chr( (int) bindec( $octet ) );
		}

		return $result;
	}
}
