<?php
/**
 * Raw SQL expression marker for DB::upsert().
 *
 * This value bypasses placeholder escaping inside DB::upsert() and is intended
 * only for trusted, hardcoded SQL fragments such as LAST_INSERT_ID() counters.
 * Never wrap user input, request data, option values, or dynamically assembled
 * identifiers in this class.
 *
 * @internal Only use via DB::raw() with trusted, hardcoded expressions.
 *
 * @author HD
 */

namespace SPL\Core;

defined( 'ABSPATH' ) || exit;

final class RawExpression {
	public function __construct(
		public readonly string $expression,
	) {}
}
