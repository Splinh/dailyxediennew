<?php
/**
 * String, Array, URL, Validation and Generator utility trait.
 *
 * Provides string/array manipulation, URL helpers, validation,
 * and random generation utilities.
 *
 * Merged from: Str, Arr, Generator, Url, Validation
 *
 * @package SPL\Traits
 */

namespace SPL\Traits;

defined( 'ABSPATH' ) || exit;

trait Str {

	// --------------------------------------------------
	// STRING UTILITIES
	// --------------------------------------------------

	/**
	 * Remove empty <p> tags from content.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function removeEmptyP( string $content ): string {
		return preg_replace( '/<p(?:\s+[^>]*)?\>\s*(?:&nbsp;|\xC2\xA0|\s)*<\/p>/i', '', $content ) ?? $content;
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public static function camelCase( string $value ): string {
		$value = trim( $value );
		if ( ! $value ) {
			return '';
		}

		$value = str_replace( [ '-', '_' ], ' ', $value );
		$value = mb_convert_case( $value, MB_CASE_TITLE, 'UTF-8' );

		return lcfirst( str_replace( ' ', '', $value ) );
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public static function snakeCase( string $value ): string {
		$value = trim( $value );
		if ( ! $value ) {
			return '';
		}

		$value = str_replace( '-', '_', $value );
		$value = preg_replace( '/(?<!^)([A-Z])/u', '_$1', $value );

		return mb_strtolower( $value, 'UTF-8' );
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public static function dashCase( string $value ): string {
		return str_replace( '_', '-', self::snakeCase( $value ) );
	}

	// --------------------------------------------------

	/**
	 * @param string $haystack
	 * @param string|array $needles
	 *
	 * @return bool
	 */
	public static function startsWith( string $haystack, string|array $needles ): bool {
		foreach ( (array) $needles as $needle ) {
			if ( $needle !== '' && str_starts_with( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param string $haystack
	 * @param string|array $needles
	 *
	 * @return bool
	 */
	public static function endsWith( string $haystack, string|array $needles ): bool {
		foreach ( (array) $needles as $needle ) {
			if ( $needle !== '' && str_ends_with( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 * @param string $prefix
	 *
	 * @return string
	 */
	public static function removePrefix( string $value, string $prefix ): string {
		if ( ! $prefix ) {
			return $value;
		}

		return self::startsWith( $value, $prefix )
			? mb_substr( $value, mb_strlen( $prefix, 'UTF-8' ), null, 'UTF-8' )
			: $value;
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 * @param string $prefix
	 * @param string|null $trim
	 *
	 * @return string
	 */
	public static function prefix( string $value, string $prefix, ?string $trim = null ): string {
		$value = trim( $value );
		if ( ! $value ) {
			return '';
		}

		return $prefix . self::removePrefix( $value, $trim ?? $prefix );
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 * @param string $suffix
	 *
	 * @return string
	 */
	public static function suffix( string $value, string $suffix ): string {
		$value = trim( $value );

		if ( ! $suffix || ! $value ) {
			return $value;
		}

		return self::endsWith( $value, $suffix ) ? $value : $value . $suffix;
	}

	// --------------------------------------------------

	/**
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function replaceFirst( string $search, string $replace, string $subject ): string {
		if ( ! $search || ! $subject ) {
			return $subject;
		}

		$pos = mb_strpos( $subject, $search, 0, 'UTF-8' );
		if ( $pos === false ) {
			return $subject;
		}

		return mb_substr( $subject, 0, $pos, 'UTF-8' )
				. $replace
				. mb_substr( $subject, $pos + mb_strlen( $search, 'UTF-8' ), null, 'UTF-8' );
	}

	// --------------------------------------------------

	/**
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function replaceLast( string $search, string $replace, string $subject ): string {
		if ( ! $search || ! $subject ) {
			return $subject;
		}

		$pos = mb_strrpos( $subject, $search, 0, 'UTF-8' );
		if ( $pos === false ) {
			return $subject;
		}

		return mb_substr( $subject, 0, $pos, 'UTF-8' )
				. $replace
				. mb_substr( $subject, $pos + mb_strlen( $search, 'UTF-8' ), null, 'UTF-8' );
	}

	// --------------------------------------------------

	/**
	 * @param string $str
	 *
	 * @return string
	 */
	public static function sanitizeKeywords( string $str ): string {
		$str = wp_strip_all_tags( $str );
		$str = preg_replace(
			[ '/[\s\v]+/u', '/\s*,\s*/u', '/\s+/u' ],
			[ ' ', ',', ',' ],
			trim( $str )
		);

		$keywords = array_unique(
			array_filter(
				array_map(
					static fn( $word ) => preg_replace(
						'/[^a-z0-9áàảãạăắằẳẵặâấầẩẫậđéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵ\s\-]/u',
						'',
						mb_strtolower( trim( $word ), 'UTF-8' )
					),
					explode( ',', $str )
				)
			)
		);

		return implode( ', ', $keywords );
	}

	// --------------------------------------------------

	/**
	 * @param string $value
	 * @param int $length
	 * @param string $end
	 *
	 * @return string
	 */
	public static function truncate( string $value, int $length, string $end = '' ): string {
		if ( $length <= 0 ) {
			return '';
		}

		$value = trim( $value );
		if ( mb_strlen( $value, 'UTF-8' ) <= $length ) {
			return $value;
		}

		$adjusted  = max( 0, $length - mb_strlen( $end, 'UTF-8' ) );
		$truncated = mb_substr( $value, 0, $adjusted, 'UTF-8' );

		return rtrim( $truncated ) . $end;
	}

	// --------------------------------------------------

	/**
	 * @param string|null $text
	 * @param string|array|null $allowedTags
	 * @param bool $removeJs
	 * @param bool $flatten
	 *
	 * @return string
	 */
	public static function stripAllTags( ?string $text, string|array|null $allowedTags = null, bool $removeJs = true, bool $flatten = true ): string {
		if ( ! $text ) {
			return '';
		}

		if ( is_array( $allowedTags ) ) {
			$allowedTags = implode( '', array_map( static fn( $tag ) => "<{$tag}>", $allowedTags ) );
		}

		if ( $removeJs ) {
			$text = preg_replace( '/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $text ) ?? '';
		}

		$text = strip_tags( $text, $allowedTags );

		if ( $flatten ) {
			$text = preg_replace( '/\s+/u', ' ', $text ) ?? '';
		}

		return trim( $text );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $value
	 * @param bool $stripTags
	 * @param string $replace
	 *
	 * @return string
	 */
	public static function stripSpace( ?string $value, bool $stripTags = true, string $replace = '' ): string {
		if ( $value === null || trim( $value ) === '' ) {
			return '';
		}

		if ( $stripTags ) {
			$value = wp_strip_all_tags( $value );
		}

		return trim( preg_replace( '/[\p{Z}\s]+/u', $replace, $value ) ?? '' );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $value
	 *
	 * @return string
	 */
	public static function escAttr( ?string $value ): string {
		return $value === null ? '' : esc_attr( wp_strip_all_tags( $value ) );
	}

	// --------------------------------------------------
	// ARRAY UTILITIES (merged from Arr)
	// --------------------------------------------------

	/**
	 * Convert a scalar (comma-separated string) or array into a filtered re-indexed array.
	 *
	 * @param mixed $value Value to convert.
	 * @param callable|null $callback Filter callback.
	 * @param string $separator String separator.
	 *
	 * @return array
	 */
	public static function convertFromString( mixed $value, ?callable $callback = null, string $separator = ',' ): array {
		if ( is_scalar( $value ) ) {
			$value = (string) $value;
			if ( trim( $value ) === '' ) {
				return [];
			}

			$value = array_map( 'trim', explode( $separator, $value ) );
		}

		$arr = (array) $value;

		$arr = $callback !== null
			? array_filter( $arr, $callback )
			: array_filter( $arr, static fn( $v ) => $v !== '' && $v !== null );

		return array_values( $arr );
	}

	// --------------------------------------------------

	/**
	 * Check whether array is a flat, indexed list.
	 *
	 * @param mixed $items Items to check.
	 *
	 * @return bool
	 */
	public static function isIndexedAndFlat( mixed $items ): bool {
		if ( ! is_array( $items ) ) {
			return false;
		}

		foreach ( $items as $v ) {
			if ( is_array( $v ) ) {
				return false;
			}
		}

		return array_is_list( $items );
	}

	// --------------------------------------------------

	/**
	 * Insert array after a given key.
	 *
	 * @param string|null $key Key to insert after.
	 * @param array $arr Original array.
	 * @param array $insertArray Array to insert.
	 *
	 * @return array
	 */
	public static function insertAfter( ?string $key, array $arr, array $insertArray ): array {
		return self::insert( $arr, $insertArray, $key, 'after' );
	}

	// --------------------------------------------------

	/**
	 * Insert array before a given key.
	 *
	 * @param string|null $key Key to insert before.
	 * @param array $arr Original array.
	 * @param array $insertArray Array to insert.
	 *
	 * @return array
	 */
	public static function insertBefore( ?string $key, array $arr, array $insertArray ): array {
		return self::insert( $arr, $insertArray, $key );
	}

	// --------------------------------------------------

	/**
	 * Insert an array before/after a specific key.
	 *
	 * @param array $arr Original array.
	 * @param array $insertArray Array to insert.
	 * @param string|null $key Key to insert at.
	 * @param string $position 'before' or 'after'.
	 *
	 * @return array
	 */
	public static function insert( array $arr, array $insertArray, ?string $key, string $position = 'before' ): array {
		if ( $key === null ) {
			return [ ...$arr, ...$insertArray ];
		}

		$keys = array_keys( $arr );
		$pos  = array_search( $key, $keys, true );

		if ( $pos === false ) {
			return [ ...$arr, ...$insertArray ];
		}

		if ( $position === 'after' ) {
			++$pos;
		}

		$left  = array_slice( $arr, 0, $pos, true );
		$right = array_slice( $arr, $pos, null, true );

		return $left + $insertArray + $right;
	}

	// --------------------------------------------------

	/**
	 * Prepend a value to an array.
	 *
	 * @param array $arr Original array.
	 * @param mixed $value Value to prepend.
	 * @param int|string|null $key Optional key.
	 *
	 * @return array
	 */
	public static function prepend( array $arr, mixed $value, int|string|null $key = null ): array {
		if ( $key !== null ) {
			return [
				$key => $value,
				...$arr,
			];
		}

		array_unshift( $arr, $value );

		return $arr;
	}

	// --------------------------------------------------
	// TYPE CASTING UTILITIES (from Arr)
	// --------------------------------------------------

	/**
	 * @param mixed $value
	 * @param bool $explode
	 *
	 * @return array
	 */
	public static function toArray( mixed $value, bool $explode = true ): array {
		if ( $value === null ) {
			return [];
		}

		if ( is_bool( $value ) ) {
			return [ $value ];
		}

		if ( is_array( $value ) ) {
			return $value;
		}

		if ( is_scalar( $value ) && $explode ) {
			return self::convertFromString( (string) $value );
		}

		if ( is_object( $value ) ) {
			return method_exists( $value, 'toArray' )
				? $value->toArray()
				: get_object_vars( $value );
		}

		return [];
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 * @param bool $strict
	 *
	 * @return string
	 */
	public static function toString( mixed $value, bool $strict = true ): string {
		// int, float, string, bool
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		// Object with __toString method
		if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
			return (string) $value;
		}

		// Null, empty array, or other "empty" values
		if ( self::isEmpty( $value ) ) {
			return '';
		}

		// Indexed flat arrays
		if ( self::isIndexedAndFlat( $value ) ) {
			return implode( ', ', $value );
		}

		// Resource or Closure: cannot cast to string
		// Note: Using instanceof \Closure instead of is_callable() to avoid false positives
		// is_callable('strlen') returns true, but 'strlen' is a valid string
		if ( is_resource( $value ) || $value instanceof \Closure ) {
			return $strict ? '' : '[unsupported type]';
		}

		// Other types (associative array, object without __toString)
		return $strict ? '' : maybe_serialize( $value );
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function toBool( mixed $value ): bool {
		return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 *
	 * @return object
	 */
	public static function toObject( mixed $value ): object {
		return is_object( $value ) ? $value : (object) self::toArray( $value );
	}

	// --------------------------------------------------
	// GENERATOR UTILITIES (merged from Generator)
	// --------------------------------------------------

	/**
	 * @param int $length
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function makeUsername( int $length = 8 ): string {
		if ( $length < 1 ) {
			return '';
		}

		$letters       = 'abcdefghijklmnopqrstuvwxyz';
		$lettersDigits = 'abcdefghijklmnopqrstuvwxyz0123456789';

		$username = $letters[ random_int( 0, strlen( $letters ) - 1 ) ];

		for ( $i = 1; $i < $length; $i++ ) {
			$username .= $lettersDigits[ random_int( 0, strlen( $lettersDigits ) - 1 ) ];
		}

		return $username;
	}

	// --------------------------------------------------

	/**
	 * Generate a unique slug with desired length.
	 *
	 * @param int $length Total desired slug length
	 * @param string $prefix
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function makeUnique( int $length = 32, string $prefix = '' ): string {
		$time        = microtime( true );
		$timeEncoded = base_convert( sprintf( '%.0f', $time * 1e6 ), 10, 36 );
		$pidEncoded  = base_convert( (string) getmypid(), 10, 36 );
		$uniqEncoded = base_convert( str_replace( '.', '', uniqid( '', true ) ), 16, 36 );

		$base = $timeEncoded . $pidEncoded . $uniqEncoded;

		$bytes  = random_bytes( (int) ceil( $length * 0.75 ) );
		$random = substr( base_convert( bin2hex( $bytes ), 16, 36 ), 0, $length );

		return $prefix . substr( $base . $random, 0, $length );
	}

	// --------------------------------------------------
	// URL UTILITIES (merged from Url)
	// --------------------------------------------------

	/**
	 * @param string $uri
	 * @param bool $permanent
	 *
	 * @return void
	 */
	public static function redirect( string $uri = '', bool $permanent = true ): void {
		$uri = esc_url_raw( $uri );
		if ( ! $uri ) {
			return;
		}

		$status = $permanent ? 301 : 302;

		if ( ! headers_sent() && ! wp_doing_ajax() && ! wp_is_json_request() ) {
			wp_safe_redirect( $uri, $status );
			exit;
		}

		// Fallback for already sent headers
		if ( ! wp_doing_ajax() && ! wp_is_json_request() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_js/esc_attr applied to $uri.
			echo '<script>window.location.href="' . esc_js( $uri ) . '";</script>';
			echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $uri ) . '" /></noscript>';
		}
	}

	// --------------------------------------------------

	/**
	 * Get the real client IP address.
	 *
	 * Only trusts proxy headers (X-Forwarded-For, CF-Connecting-IP, etc.)
	 * when REMOTE_ADDR is in the configured trusted proxy list.
	 * Default: empty list (trust no proxies — always returns REMOTE_ADDR).
	 *
	 * Configure trusted proxies via the 'hd_trusted_proxies' filter:
	 *   add_filter( 'hd_trusted_proxies', fn() => ['173.245.48.0/20', '10.0.0.1'] );
	 *
	 * @return string Client IP address
	 */
	public static function ipAddress(): string {
		$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

		if ( ! filter_var( $remoteAddr, FILTER_VALIDATE_IP ) ) {
			return '0.0.0.0';
		}

		/**
		 * Trusted proxy IPs/CIDRs. Only when REMOTE_ADDR matches one of these
		 * will proxy headers (X-Forwarded-For, etc.) be consulted.
		 *
		 * @param string[] $proxies Array of trusted IPs or CIDR ranges.
		 */
		$trustedProxies = (array) apply_filters( 'hd_trusted_proxies', [] );

		if ( $trustedProxies && self::ipInRanges( $remoteAddr, $trustedProxies ) ) {
			// Headers to check in priority order
			$headers = [
				'HTTP_CF_CONNECTING_IP', // CloudFlare
				'HTTP_X_FORWARDED_FOR',  // Standard proxy header
				'HTTP_X_REAL_IP',        // Nginx
				'HTTP_CLIENT_IP',        // Some proxies
			];

			foreach ( $headers as $header ) {
				if ( empty( $_SERVER[ $header ] ) ) {
					continue;
				}

				// X-Forwarded-For can contain multiple IPs: client, proxy1, proxy2
				// The first (leftmost) is the original client.
				$ip = trim( explode( ',', $_SERVER[ $header ] )[0] );

				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return apply_filters( 'hd_client_ip_filter', $ip, $remoteAddr );
				}
			}
		}

		return apply_filters( 'hd_client_ip_filter', $remoteAddr, $remoteAddr );
	}

	// --------------------------------------------------

	/**
	 * Check if an IP matches any CIDR range or exact IP in the list.
	 *
	 * @param string   $ip     IP address to check.
	 * @param string[] $ranges Array of IPs or CIDR ranges (e.g., '10.0.0.1', '173.245.48.0/20').
	 *
	 * @return bool
	 */
	private static function ipInRanges( string $ip, array $ranges ): bool {
		$ipLong = ip2long( $ip );
		if ( false === $ipLong ) {
			return false;
		}

		foreach ( $ranges as $range ) {
			if ( str_contains( $range, '/' ) ) {
				[ $subnet, $bits ] = explode( '/', $range, 2 );
				$subnetLong        = ip2long( $subnet );
				$mask              = -1 << ( 32 - (int) $bits );

				if ( false !== $subnetLong && ( $ipLong & $mask ) === ( $subnetLong & $mask ) ) {
					return true;
				}
			} elseif ( $ip === $range ) {
				return true;
			}
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 * @param string|null $scheme
	 *
	 * @return string
	 */
	public static function home( string $path = '', ?string $scheme = null ): string {
		return apply_filters( 'hd_home_url_filter', esc_url( home_url( $path, $scheme ) ), $path );
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 * @param string|null $scheme
	 *
	 * @return string
	 */
	public static function siteURL( string $path = '', ?string $scheme = null ): string {
		return apply_filters( 'hd_site_url_filter', esc_url( site_url( $path, $scheme ) ), $path );
	}

	// --------------------------------------------------

	/**
	 * @param bool $nopaging
	 * @param bool $getVars
	 *
	 * @return string
	 */
	public static function current( bool $nopaging = true, bool $getVars = true ): string {
		global $wp;

		$currentUrl = self::home( $wp->request );

		// Remove pagination segment (e.g., /page/2/) from URL.
		if ( $nopaging ) {
			$currentUrl = preg_replace( '#/page/\d+/?$#', '/', $currentUrl ) ?? $currentUrl;
		}

		if ( $getVars ) {
			$queryString = http_build_query(
				array_map(
					static fn( $v ) => is_array( $v )
					? array_map( 'sanitize_text_field', $v )
					: sanitize_text_field( $v ),
					wp_unslash( $_GET )
				)
			);

			if ( $queryString ) {
				$currentUrl .= ( str_contains( $currentUrl, '?' ) ? '&' : '?' ) . $queryString;
			}
		}

		return $currentUrl;
	}

	// --------------------------------------------------
	// VALIDATION UTILITIES (merged from Validation)
	// --------------------------------------------------

	/**
	 * @param mixed $phone
	 *
	 * @return bool
	 */
	public static function isValidPhone( mixed $phone ): bool {
		if ( ! is_string( $phone ) || trim( $phone ) === '' ) {
			return false;
		}

		$pattern = '/^\(?\+?(0|84)\)?[\s.\-]?(3[2-9]|5[689]|7[06-9]|(?:8[0-689]|87)|9[0-46-9])(\d{7}|\d[\s.\-]?\d{3}[\s.\-]?\d{3})$/';

		return preg_match( $pattern, $phone ) === 1;
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 * @param int $min
	 * @param int $max
	 *
	 * @return bool
	 */
	public static function inRange( mixed $value, int $min, int $max ): bool {
		return filter_var(
			$value,
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => $min,
					'max_range' => $max,
				],
			]
		) !== false;
	}

	// --------------------------------------------------

	/**
	 * @param array $arrayA
	 * @param array $arrayB
	 *
	 * @return bool
	 */
	public static function checkValuesNotInRanges( array $arrayA, array $arrayB ): bool {
		if ( empty( $arrayA ) || empty( $arrayB ) ) {
			return true;
		}

		foreach ( $arrayA as $range ) {
			if ( count( $range ) !== 2 || ! is_numeric( $range[0] ) || ! is_numeric( $range[1] ) ) {
				continue;
			}

			$start = min( $range );
			$end   = max( $range );

			foreach ( $arrayB as $value ) {
				if ( $value >= $start && $value < $end ) {
					return false;
				}
			}

			if ( min( $arrayB ) <= $start && max( $arrayB ) >= $end ) {
				return false;
			}
		}

		return true;
	}
}
