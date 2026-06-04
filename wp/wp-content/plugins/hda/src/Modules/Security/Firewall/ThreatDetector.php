<?php
/**
 * Threat Detector — orchestrates detection of attacks across all request data.
 *
 * Iterates over GET, POST, cookies, headers, URI, and user-agent,
 * checking each value against the RuleMatcher for known attack patterns.
 * Includes WordPress-specific whitelisting (nonces, admin AJAX, etc.).
 *
 * @package HDAddons\Modules\Security\Firewall
 * @author  HD
 */

namespace HDAddons\Modules\Security\Firewall;

\defined( 'ABSPATH' ) || exit;

final class ThreatDetector {

	private readonly RuleMatcher $matcher;

	// --------------------------------------------------

	public function __construct( ?RuleMatcher $matcher = null ) {
		$this->matcher = $matcher ?? new RuleMatcher();
	}

	// ══════════════════════════════════════════════════
	// Main detection entry point
	// ══════════════════════════════════════════════════

	/**
	 * Run all detectors against the analyzed request data.
	 *
	 * Returns the FIRST (highest priority) threat found,
	 * or null if the request is clean.
	 *
	 * @param array    $request      Output from RequestAnalyzer::analyze().
	 * @param string[] $enabledTypes Enabled attack types (sqli, xss, rce, lfi, bad_bot).
	 *
	 * @return ThreatResult|null
	 */
	public function detect( array $request, array $enabledTypes = [] ): ?ThreatResult {
		// WordPress internal requests are never attacks.
		if ( $this->isWordPressInternal( $request ) ) {
			return null;
		}

		// Bad Bot detection (fastest — user-agent only)
		if ( in_array( 'bad_bot', $enabledTypes, true ) ) {
			$threat = $this->detectBadBots( $request );
			if ( $threat ) {
				return $threat;
			}
		}

		// Author enumeration scan (always on — lightweight)
		$threat = $this->detectAuthorScan( $request );
		if ( $threat ) {
			return $threat;
		}

		// Scan all parameters for attack patterns
		$threat = $this->detectPatterns( $request, $enabledTypes );
		if ( $threat ) {
			return $threat;
		}

		// Scan URI/query string
		$threat = $this->detectUriThreats( $request, $enabledTypes );
		if ( $threat ) {
			return $threat;
		}

		return null;
	}

	// ══════════════════════════════════════════════════
	// Individual detectors
	// ══════════════════════════════════════════════════

	/**
	 * Detect malicious user agents (scanners, exploit tools, bots).
	 *
	 * @param array $request Analyzed request data.
	 *
	 * @return ThreatResult|null
	 */
	private function detectBadBots( array $request ): ?ThreatResult {
		$ua = $request['user_agent'] ?? '';

		if ( '' === $ua ) {
			return null;
		}

		$rule = $this->matcher->match( $ua, 'bad_bot', 'user_agent' );

		if ( $rule ) {
			return new ThreatResult(
				ruleId: $rule['id'],
				attackType: 'bad_bot',
				severity: $rule['severity'],
				matchedValue: $this->truncate( $ua ),
				context: 'user_agent',
				description: $rule['name'] ?? 'Bad bot detected',
			);
		}

		return null;
	}

	/**
	 * Detect WordPress author enumeration (?author=N).
	 *
	 * @param array $request Analyzed request data.
	 *
	 * @return ThreatResult|null
	 */
	private function detectAuthorScan( array $request ): ?ThreatResult {
		$getParams = $request['get'] ?? [];

		if ( isset( $getParams['author'] ) && is_numeric( $getParams['author'] ) ) {
			return new ThreatResult(
				ruleId: 'builtin_author_scan',
				attackType: 'author_scan',
				severity: 'low',
				matchedValue: '?author=' . $getParams['author'],
				context: 'get',
				description: 'Author enumeration attempt',
			);
		}

		return null;
	}

	/**
	 * Detect attack patterns in GET, POST, and cookie parameters.
	 *
	 * @param array    $request      Analyzed request data.
	 * @param string[] $enabledTypes Enabled attack types.
	 *
	 * @return ThreatResult|null
	 */
	private function detectPatterns( array $request, array $enabledTypes = [] ): ?ThreatResult {
		// Map context key → request data key.
		$sources = [
			'get'     => $request['get'] ?? [],
			'post'    => $request['post'] ?? [],
			'cookies' => $request['cookies'] ?? [],
			'headers' => $request['headers'] ?? [],
		];

		// Only scan enabled attack types, ordered by impact (critical first).
		$attackTypes = array_values(
			array_intersect(
				[ 'sqli', 'xss', 'rce', 'lfi' ],
				$enabledTypes,
			)
		);

		foreach ( $sources as $context => $params ) {
			if ( empty( $params ) ) {
				continue;
			}

			foreach ( $params as $key => $value ) {
				if ( '' === $value ) {
					continue;
				}

				// Skip WordPress nonce fields — they contain harmless strings.
				if ( $this->isWordPressField( $key ) ) {
					continue;
				}

				foreach ( $attackTypes as $type ) {
					$rule = $this->matcher->match( $value, $type, $context );
					if ( $rule ) {
						return new ThreatResult(
							ruleId: $rule['id'],
							attackType: $type,
							severity: $rule['severity'],
							matchedValue: $this->truncate( "{$key}={$value}" ),
							context: $context,
							description: $rule['name'] ?? $type,
						);
					}
				}
			}
		}

		return null;
	}

	/**
	 * Detect attack patterns in the URI and query string.
	 *
	 * @param array    $request      Analyzed request data.
	 * @param string[] $enabledTypes Enabled attack types.
	 *
	 * @return ThreatResult|null
	 */
	private function detectUriThreats( array $request, array $enabledTypes = [] ): ?ThreatResult {
		$uri         = $request['uri'] ?? '';
		$queryString = $request['query_string'] ?? '';

		$targets = array_filter( [ $uri, $queryString ] );
		if ( empty( $targets ) ) {
			return null;
		}

		// Only scan enabled attack types.
		$attackTypes = array_values(
			array_intersect(
				[ 'sqli', 'xss', 'rce', 'lfi' ],
				$enabledTypes,
			)
		);

		foreach ( $targets as $value ) {
			foreach ( $attackTypes as $type ) {
				$rule = $this->matcher->match( $value, $type, 'uri' );
				if ( $rule ) {
					return new ThreatResult(
						ruleId: $rule['id'],
						attackType: $type,
						severity: $rule['severity'],
						matchedValue: $this->truncate( $value ),
						context: 'uri',
						description: $rule['name'] ?? $type,
					);
				}
			}
		}

		return null;
	}

	// ══════════════════════════════════════════════════
	// Whitelisting
	// ══════════════════════════════════════════════════

	/**
	 * Check if the request is a WordPress internal operation that should bypass WAF.
	 *
	 * Note: wp-cron and admin checks are already handled upstream in Firewall::run().
	 * This only covers AJAX heartbeat which passes through the pipeline.
	 *
	 * @param array $request Analyzed request data.
	 *
	 * @return bool
	 */
	private function isWordPressInternal( array $request ): bool {
		// WP Heartbeat and other harmless admin AJAX actions.
		if ( $request['is_ajax'] && isset( $request['post']['action'] ) ) {
			$action = $request['post']['action'];
			if ( in_array( $action, [ 'heartbeat', 'wp-remove-post-lock', 'closed-postboxes' ], true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a parameter key is a WordPress internal field (skip scanning).
	 *
	 * @param string $key Parameter key.
	 *
	 * @return bool
	 */
	private function isWordPressField( string $key ): bool {
		// Nonce and WP internal fields.
		$wpFields = [
			'_wpnonce',
			'_wp_http_referer',
			'_ajax_nonce',
			'wp_customize',
			'action',
			'redirect_to',
			'log',
			'pwd',
		];

		if ( in_array( $key, $wpFields, true ) ) {
			return true;
		}

		// WordPress meta key patterns.
		if ( str_starts_with( $key, '_wp' ) || str_starts_with( $key, 'wp_' ) ) {
			return true;
		}

		return false;
	}

	// --------------------------------------------------
	// Helpers
	// --------------------------------------------------

	/**
	 * Truncate a matched value for safe logging.
	 *
	 * @param string $value Original value.
	 * @param int    $max   Max length.
	 *
	 * @return string Truncated value.
	 */
	private function truncate( string $value, int $max = 200 ): string {
		if ( mb_strlen( $value ) <= $max ) {
			return $value;
		}

		return mb_substr( $value, 0, $max ) . '…';
	}
}
