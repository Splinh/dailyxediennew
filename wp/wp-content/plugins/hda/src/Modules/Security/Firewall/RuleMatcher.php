<?php
/**
 * Rule Matcher — loads JSON rules and matches input against regex patterns.
 *
 * Rules are loaded from JSON files in the `rules/` directory, cached per-request
 * in a static property (OPcache-friendly). External code can extend rules via
 * the `hda_firewall_rules` filter.
 *
 * @package HDAddons\Modules\Security\Firewall
 * @author  HD
 */

namespace HDAddons\Modules\Security\Firewall;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class RuleMatcher {

	/**
	 * Per-request rule cache, keyed by attack type.
	 *
	 * @var array<string, array>|null
	 */
	private static ?array $allRules = null;

	/**
	 * Path to the rules directory.
	 */
	private const RULES_DIR = __DIR__ . '/rules/';

	/**
	 * Map of attack type → JSON filename (without extension).
	 */
	private const RULE_FILES = [
		'sqli'    => 'sqli',
		'xss'     => 'xss',
		'rce'     => 'rce',
		'lfi'     => 'lfi',
		'bad_bot' => 'bad-bots',
	];

	// --------------------------------------------------

	/**
	 * Load all rule sets into memory (once per request).
	 *
	 * @return void
	 */
	private function ensureLoaded(): void {
		if ( null !== self::$allRules ) {
			return;
		}

		self::$allRules = [];

		foreach ( self::RULE_FILES as $type => $filename ) {
			$path = self::RULES_DIR . $filename . '.json';

			if ( ! is_file( $path ) ) {
				Helper::errorLog( "[HDA Firewall] Missing rules file: {$filename}.json" );
				self::$allRules[ $type ] = [];
				continue;
			}

			$content = Helper::readFile( $path );
			if ( empty( $content ) ) {
				self::$allRules[ $type ] = [];
				continue;
			}

			try {
				$decoded = json_decode( $content, true, 512, JSON_THROW_ON_ERROR );
				$rules   = $decoded['rules'] ?? [];
			} catch ( \JsonException $e ) {
				Helper::errorLog( "[HDA Firewall] Invalid JSON in {$filename}.json: " . $e->getMessage() );
				$rules = [];
			}

			// Filter: allow themes/plugins to add or modify rules.
			$rules = apply_filters( 'hda_firewall_rules', $rules, $type );

			// Keep only enabled rules.
			self::$allRules[ $type ] = array_filter(
				$rules,
				static fn( array $rule ): bool => ! empty( $rule['enabled'] ),
			);
		}
	}

	// --------------------------------------------------

	/**
	 * Get all rules for a specific attack type.
	 *
	 * @param string $type Attack type (sqli, xss, rce, lfi, bad_bot).
	 *
	 * @return array List of rule arrays.
	 */
	public function getRules( string $type ): array {
		$this->ensureLoaded();

		return self::$allRules[ $type ] ?? [];
	}

	/**
	 * Get all loaded rule types.
	 *
	 * @return string[]
	 */
	public function getTypes(): array {
		return array_keys( self::RULE_FILES );
	}

	// --------------------------------------------------

	/**
	 * Match a single value against all rules of a given type.
	 *
	 * Returns the first matching rule or null.
	 *
	 * @param string $value   The string to test.
	 * @param string $type    Attack type (sqli, xss, rce, lfi, bad_bot).
	 * @param string $context Where the value came from: get, post, cookies, headers, uri, user_agent.
	 *
	 * @return array|null Matching rule array or null.
	 */
	public function match( string $value, string $type, string $context = 'any' ): ?array {
		if ( '' === $value ) {
			return null;
		}

		foreach ( $this->getRules( $type ) as $rule ) {
			// Context filtering: rule contexts=['any'] matches everything.
			if ( ! $this->contextMatches( $rule, $context ) ) {
				continue;
			}

			if ( $this->testPattern( $value, $rule['pattern'] ) ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * Match a value against ALL attack types.
	 *
	 * Returns an array of all matching rules (may be empty).
	 *
	 * @param string $value   The string to test.
	 * @param string $context Where the value came from.
	 *
	 * @return array<array{type: string, rule: array}> Matched rules with their types.
	 */
	public function matchAll( string $value, string $context = 'any' ): array {
		if ( '' === $value ) {
			return [];
		}

		$matches = [];

		foreach ( $this->getTypes() as $type ) {
			$rule = $this->match( $value, $type, $context );
			if ( $rule ) {
				$matches[] = [
					'type' => $type,
					'rule' => $rule,
				];
			}
		}

		return $matches;
	}

	// --------------------------------------------------
	// Internal helpers
	// --------------------------------------------------

	/**
	 * Check if a rule's context list includes the given context.
	 *
	 * @param array  $rule    Rule definition.
	 * @param string $context Current context.
	 *
	 * @return bool
	 */
	private function contextMatches( array $rule, string $context ): bool {
		$ruleContexts = $rule['contexts'] ?? [ 'any' ];

		if ( in_array( 'any', $ruleContexts, true ) ) {
			return true;
		}

		return in_array( $context, $ruleContexts, true );
	}

	/**
	 * Test a value against a regex pattern (case-insensitive).
	 *
	 * @param string $value   The string to test.
	 * @param string $pattern Regex pattern (without delimiters).
	 *
	 * @return bool
	 */
	private function testPattern( string $value, string $pattern ): bool {
		// Validate pattern before running.
		$regex = '#' . $pattern . '#is';

		// Suppress warnings from invalid user-supplied patterns.
		$result = @preg_match( $regex, $value );

		if ( false === $result ) {
			Helper::errorLog( "[HDA Firewall] Invalid regex pattern: {$pattern}" );

			return false;
		}

		return $result === 1;
	}

	// --------------------------------------------------

	/**
	 * Clear the static rule cache (useful for testing).
	 *
	 * @return void
	 */
	public static function clearCache(): void {
		self::$allRules = null;
	}
}
