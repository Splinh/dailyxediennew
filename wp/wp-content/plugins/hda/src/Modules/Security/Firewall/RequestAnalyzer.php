<?php
/**
 * Request Analyzer — parses and normalizes the incoming HTTP request.
 *
 * Extracts GET, POST, cookies, headers, and URI into a structured array
 * for consumption by ThreatDetector. All values are recursively decoded
 * and stripped of obfuscation (URL-encoding, null bytes, etc.).
 *
 * @package HDAddons\Modules\Security\Firewall
 * @author  HD
 */

namespace HDAddons\Modules\Security\Firewall;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class RequestAnalyzer {

	/**
	 * File extensions that bypass WAF scanning entirely.
	 * These never contain executable payloads and are served by the web server.
	 */
	private const STATIC_EXTENSIONS = [
		'css',
		'js',
		'jpg',
		'jpeg',
		'png',
		'gif',
		'svg',
		'webp',
		'avif',
		'ico',
		'woff',
		'woff2',
		'ttf',
		'eot',
		'otf',
		'mp4',
		'webm',
		'ogg',
		'mp3',
		'wav',
		'pdf',
		'zip',
		'gz',
		'map',
	];

	/**
	 * Maximum raw body size to read (bytes).
	 * Prevents memory exhaustion on large file uploads.
	 */
	private const MAX_BODY_SIZE = 65_536; // 64 KB

	// --------------------------------------------------

	/**
	 * Analyze the current request and return structured data.
	 *
	 * @return array{
	 *     method: string,
	 *     uri: string,
	 *     query_string: string,
	 *     get: array,
	 *     post: array,
	 *     cookies: array,
	 *     headers: array,
	 *     user_agent: string,
	 *     referer: string,
	 *     ip: string,
	 *     is_ajax: bool,
	 *     is_rest: bool,
	 *     is_xmlrpc: bool,
	 *     is_login: bool,
	 *     is_static: bool,
	 * }
	 */
	public function analyze(): array {
		$uri = $this->getUri();

		return [
			'method'       => $this->getMethod(),
			'uri'          => $uri,
			'query_string' => $this->getQueryString(),
			'get'          => $this->getParams( INPUT_GET ),
			'post'         => $this->getParams( INPUT_POST ),
			'cookies'      => $this->getCookies(),
			'headers'      => $this->getHeaders(),
			'user_agent'   => $this->getUserAgent(),
			'referer'      => $this->getReferer(),
			'ip'           => Helper::ipAddress(),
			'is_ajax'      => $this->isAjax(),
			'is_rest'      => $this->isRestRequest( $uri ),
			'is_xmlrpc'    => $this->isXmlRpc( $uri ),
			'is_login'     => $this->isLoginPage( $uri ),
			'is_static'    => $this->isStaticFile( $uri ),
		];
	}

	// ══════════════════════════════════════════════════
	// Request properties
	// ══════════════════════════════════════════════════

	/**
	 * @return string HTTP method (uppercase).
	 */
	public function getMethod(): string {
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		return strtoupper( sanitize_text_field( $method ) );
	}

	/**
	 * @return string Decoded, sanitized request URI.
	 */
	public function getUri(): string {
		$uri = $_SERVER['REQUEST_URI'] ?? '/';

		return $this->normalizeValue( $uri );
	}

	/**
	 * @return string Raw query string (for pattern matching).
	 */
	public function getQueryString(): string {
		$qs = $_SERVER['QUERY_STRING'] ?? '';

		return $this->normalizeValue( $qs );
	}

	/**
	 * @return string User-Agent header.
	 */
	public function getUserAgent(): string {
		return sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' );
	}

	/**
	 * @return string Referer header.
	 */
	public function getReferer(): string {
		return sanitize_text_field( $_SERVER['HTTP_REFERER'] ?? '' );
	}

	// ══════════════════════════════════════════════════
	// Parameter extraction
	// ══════════════════════════════════════════════════

	/**
	 * Get normalized GET or POST parameters.
	 *
	 * @param int $type INPUT_GET or INPUT_POST.
	 *
	 * @return array<string, string>
	 */
	public function getParams( int $type ): array {
		$raw = match ( $type ) {
			INPUT_GET  => $_GET,
			INPUT_POST => $_POST,
			default    => [],
		};

		return $this->normalizeArray( $raw );
	}

	/**
	 * Get cookies (excluding WordPress internal cookies).
	 *
	 * @return array<string, string>
	 */
	public function getCookies(): array {
		$cookies = [];

		foreach ( $_COOKIE as $name => $value ) {
			// Skip WordPress session/auth cookies — they're not attack vectors.
			if ( str_starts_with( $name, 'wordpress_' ) || str_starts_with( $name, 'wp-' ) ) {
				continue;
			}

			$cookies[ $name ] = $this->normalizeValue( $value );
		}

		return $cookies;
	}

	/**
	 * Get normalized request headers (lowercase keys).
	 *
	 * @return array<string, string>
	 */
	public function getHeaders(): array {
		$headers = [];

		foreach ( $_SERVER as $key => $value ) {
			if ( ! str_starts_with( $key, 'HTTP_' ) ) {
				continue;
			}

			// Skip common non-security-relevant headers.
			if ( in_array( $key, [ 'HTTP_HOST', 'HTTP_ACCEPT', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_ACCEPT_ENCODING', 'HTTP_CONNECTION' ], true ) ) {
				continue;
			}

			$headerName             = strtolower( str_replace( '_', '-', substr( $key, 5 ) ) );
			$headers[ $headerName ] = $this->normalizeValue( $value );
		}

		return $headers;
	}

	// ══════════════════════════════════════════════════
	// Request type detection
	// ══════════════════════════════════════════════════

	/**
	 * Check if this is an AJAX request.
	 *
	 * @return bool
	 */
	public function isAjax(): bool {
		return wp_doing_ajax()
			|| ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] )
				&& 'xmlhttprequest' === strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) );
	}

	/**
	 * Check if this is a REST API request.
	 *
	 * @param string $uri Normalized URI.
	 *
	 * @return bool
	 */
	public function isRestRequest( string $uri ): bool {
		$restPrefix = rest_get_url_prefix(); // Usually 'wp-json'

		return str_contains( $uri, "/{$restPrefix}/" )
			|| str_contains( $uri, "/{$restPrefix}" );
	}

	/**
	 * Check if this is an XML-RPC request.
	 *
	 * @param string $uri Normalized URI.
	 *
	 * @return bool
	 */
	public function isXmlRpc( string $uri ): bool {
		return str_contains( $uri, 'xmlrpc.php' );
	}

	/**
	 * Check if this is the login page.
	 *
	 * @param string $uri Normalized URI.
	 *
	 * @return bool
	 */
	public function isLoginPage( string $uri ): bool {
		return str_contains( $uri, 'wp-login.php' )
			|| str_contains( $uri, 'wp-signup.php' );
	}

	/**
	 * Check if the URI points to a static file (bypass WAF).
	 *
	 * @param string $uri Normalized URI.
	 *
	 * @return bool
	 */
	public function isStaticFile( string $uri ): bool {
		// Strip query strings for extension check.
		$path = strtok( $uri, '?' ) ?: $uri;
		$ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		return in_array( $ext, self::STATIC_EXTENSIONS, true );
	}

	// ══════════════════════════════════════════════════
	// Normalization
	// ══════════════════════════════════════════════════

	/**
	 * Normalize a value for pattern matching:
	 * - Strip SQL comments (prevents obfuscation bypass)
	 * - Decode HTML entities (prevents XSS bypass)
	 * - Multi-layer URL decode
	 * - Strip null bytes
	 * - Collapse whitespace
	 *
	 * @param mixed $value Input value.
	 *
	 * @return string Normalized string.
	 */
	public function normalizeValue( mixed $value ): string {
		if ( is_array( $value ) ) {
			return implode( ' ', array_map( $this->normalizeValue( ... ), $value ) );
		}

		$value = (string) $value;

		// Strip SQL comments to prevent obfuscation (UNION/**/SELECT, UNION--comment\nSELECT).
		$value = preg_replace( '/\/\*.*?\*\/|--[^\n]*|#[^\n]*/s', ' ', $value ) ?? $value;

		// Decode HTML entities to prevent XSS bypass (&lt;script&gt;).
		$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Multi-layer URL decode (attackers double/triple encode).
		$prev = '';
		$i    = 0;
		while ( $prev !== $value && $i < 3 ) {
			$prev  = $value;
			$value = rawurldecode( $value );
			++$i;
		}

		// Strip null bytes (classic evasion technique).
		$value = str_replace( [ "\0", '%00' ], '', $value );

		// Normalize unicode and whitespace.
		$value = preg_replace( '/\s+/', ' ', $value ) ?? $value;

		return trim( $value );
	}

	/**
	 * Recursively normalize an array of parameters.
	 *
	 * @param array $data Raw input array.
	 *
	 * @return array<string, string> Flattened key→value pairs.
	 */
	private function normalizeArray( array $data ): array {
		$result = [];

		foreach ( $data as $key => $value ) {
			$key = sanitize_text_field( (string) $key );

			if ( is_array( $value ) ) {
				// Flatten nested arrays with dot notation.
				foreach ( $this->normalizeArray( $value ) as $subKey => $subValue ) {
					$result[ "{$key}.{$subKey}" ] = $subValue;
				}
			} else {
				$result[ $key ] = $this->normalizeValue( $value );
			}
		}

		return $result;
	}
}
