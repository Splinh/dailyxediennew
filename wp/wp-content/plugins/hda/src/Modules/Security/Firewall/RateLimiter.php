<?php
/**
 * Rate Limiter — hybrid request rate limiting via RateLimitStorage.
 *
 * Tracks request counts per IP + endpoint type.
 * When an IP exceeds the threshold, the request is flagged for blocking.
 *
 * Storage is transparently handled by RateLimitStorage:
 * - Redis/Memcached → WP Transients (fastest)
 * - No object cache → lightweight custom VARBINARY(16) MySQL table
 *
 * @package HDAddons\Modules\Security\Firewall
 * @author  HD
 */

namespace HDAddons\Modules\Security\Firewall;

use HDAddons\Core\RateLimitStorage;

\defined( 'ABSPATH' ) || exit;

final class RateLimiter {



	/**
	 * Default rate limits per endpoint type (requests per window).
	 *
	 * @var array<string, array{limit: int, window: int}>
	 */
	private const DEFAULTS = [
		'global' => [
			'limit'  => 300,
			'window' => 60,
		],    // 300 req/min
		// Login brute-force protection is handled by LoginSecurity\LoginAttempts
		// (counts actual failed logins with escalating ban: 1h → 1d → 1w).
		'xmlrpc' => [
			'limit'  => 5,
			'window' => 60,
		],    // 5 req/min
		'rest'   => [
			'limit'  => 120,
			'window' => 60,
		],     // 120 req/min
		'ajax'   => [
			'limit'  => 120,
			'window' => 60,
		],     // 120 req/min
	];

	/**
	 * Custom limits from settings.
	 *
	 * @var array<string, int>
	 */
	private array $customLimits;

	// --------------------------------------------------

	/**
	 * @param array $customLimits Optional custom limits, keyed by endpoint type.
	 *                            Example: ['global' => 500, 'login' => 5]
	 */
	public function __construct( array $customLimits = [] ) {
		$this->customLimits = $customLimits;
	}

	// --------------------------------------------------

	/**
	 * Check if the IP has exceeded the rate limit.
	 *
	 * Increments the counter and returns a reason string if exceeded, else null.
	 *
	 * @param string $ip          Client IP address.
	 * @param array  $requestData Analyzed request data (from RequestAnalyzer).
	 *
	 * @return ThreatResult|null Threat result if exceeded, null if within limits.
	 */
	public function check( string $ip, array $requestData ): ?ThreatResult {
		$endpoint = $this->resolveEndpoint( $requestData );

		// Always check the global counter first (all request types count toward it).
		$globalConfig = $this->getConfig( 'global' );
		$globalCount  = $this->increment( $ip, 'global', $globalConfig['window'] );

		if ( $globalCount > $globalConfig['limit'] ) {
			return new ThreatResult(
				ruleId: 'rate_limit_global',
				attackType: 'rate_limit',
				severity: 'high',
				matchedValue: "{$globalCount}/{$globalConfig['limit']} requests in {$globalConfig['window']}s (global)",
				context: 'global',
				description: 'Rate limit exceeded for global endpoint',
			);
		}

		// Then check the specific endpoint counter (if not already global).
		if ( 'global' !== $endpoint ) {
			$config = $this->getConfig( $endpoint );
			$count  = $this->increment( $ip, $endpoint, $config['window'] );

			if ( $count > $config['limit'] ) {
				return new ThreatResult(
					ruleId: 'rate_limit_' . $endpoint,
					attackType: 'rate_limit',
					severity: 'high',
					matchedValue: "{$count}/{$config['limit']} requests in {$config['window']}s ({$endpoint})",
					context: 'global',
					description: "Rate limit exceeded for {$endpoint} endpoint",
				);
			}
		}

		return null;
	}

	// --------------------------------------------------
	// Internal
	// --------------------------------------------------

	/**
	 * Determine the endpoint type for rate limiting.
	 *
	 * @param array $requestData Analyzed request data.
	 *
	 * @return string Endpoint type: xmlrpc, rest, ajax, or global.
	 */
	private function resolveEndpoint( array $requestData ): string {
		// Login requests fall through to 'global' rate limit.
		// Login-specific brute-force protection is in LoginSecurity\LoginAttempts.

		if ( ! empty( $requestData['is_xmlrpc'] ) ) {
			return 'xmlrpc';
		}

		if ( ! empty( $requestData['is_rest'] ) ) {
			return 'rest';
		}

		if ( ! empty( $requestData['is_ajax'] ) ) {
			return 'ajax';
		}

		return 'global';
	}

	/**
	 * Get rate config for an endpoint type.
	 *
	 * @param string $endpoint Endpoint type.
	 *
	 * @return array{limit: int, window: int}
	 */
	private function getConfig( string $endpoint ): array {
		$defaults = self::DEFAULTS[ $endpoint ] ?? self::DEFAULTS['global'];

		// Apply custom limit if configured.
		if ( isset( $this->customLimits[ $endpoint ] ) ) {
			$defaults['limit'] = max( 1, (int) $this->customLimits[ $endpoint ] );
		}

		return $defaults;
	}

	/**
	 * Increment the request counter for an IP + endpoint.
	 *
	 * Note: set_transient() resets the TTL on each call, creating a
	 * "sliding window" rather than a fixed window. This is acceptable
	 * for a WP plugin — true fixed-window needs Redis INCR + EXPIRE.
	 *
	 * @param string $ip       Client IP.
	 * @param string $endpoint Endpoint type.
	 * @param int    $window   Time window in seconds.
	 *
	 * @return int Current request count (after increment).
	 */
	private function increment( string $ip, string $endpoint, int $window ): int {
		return RateLimitStorage::increment( $ip, 'rl_' . $endpoint, $window );
	}
}
