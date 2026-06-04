<?php
/**
 * Immutable value object representing a detected threat.
 *
 * Replaces loosely-typed arrays for type safety and IDE support.
 *
 * @package HDAddons\Modules\Security\Firewall
 * @author  HD
 */

namespace HDAddons\Modules\Security\Firewall;

\defined( 'ABSPATH' ) || exit;

final readonly class ThreatResult {

	/**
	 * @param string $ruleId       Rule identifier (e.g. 'sqli_001').
	 * @param string $attackType   Attack category: sqli, xss, rce, lfi, bad_bot, author_scan, rate_limit.
	 * @param string $severity     Severity: low, medium, high, critical.
	 * @param string $matchedValue The request fragment that triggered the rule.
	 * @param string $context      Where the match occurred: get, post, cookie, header, uri, user_agent.
	 * @param string $description  Human-readable description of the rule.
	 */
	public function __construct(
		public string $ruleId,
		public string $attackType,
		public string $severity,
		public string $matchedValue,
		public string $context,
		public string $description = '',
	) {}

	// --------------------------------------------------

	/**
	 * Convert to array for logging/serialization.
	 *
	 * @return array<string, string>
	 */
	public function toArray(): array {
		return [
			'rule_id'       => $this->ruleId,
			'attack_type'   => $this->attackType,
			'severity'      => $this->severity,
			'matched_value' => $this->matchedValue,
			'context'       => $this->context,
			'description'   => $this->description,
		];
	}

	// --------------------------------------------------

	/**
	 * Check if this threat is critical or high severity.
	 *
	 * @return bool
	 */
	public function isSevere(): bool {
		return in_array( $this->severity, [ 'critical', 'high' ], true );
	}
}
