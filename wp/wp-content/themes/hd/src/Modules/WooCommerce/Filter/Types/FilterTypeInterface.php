<?php
/**
 * FilterType Interface — Strategy pattern for AJAX product filters.
 *
 * Each filter type implements 3 methods: render, applyToQuery, getCounts.
 * Metadata (TYPE, LABEL) lives in class constants — zero boilerplate.
 *
 * @package HD\Modules\WooCommerce\Filter\Types
 */

namespace HD\Modules\WooCommerce\Filter\Types;

defined( 'ABSPATH' ) || exit;

interface FilterTypeInterface {

	/**
	 * Constructor.
	 *
	 * @param array{
	 *     id?: string,
	 *     type?: string,
	 *     label?: string,
	 *     taxonomy?: string,
	 *     display?: string,
	 *     adoptive?: 'show'|'hide'|'disable'|string,
	 *     orderby?: string,
	 *     exclude_terms?: list<string>,
	 *     include_mode?: bool,
	 *     searchable?: bool,
	 *     collapse?: bool
	 * } $config Filter configuration.
	 */
	public function __construct( array $config = [] );

	/**
	 * Render filter widget HTML.
	 *
	 * @param array<string>      $activeValues Currently selected values.
	 * @param array<string, int> $counts       Option counts [value => count].
	 *
	 * @return string Rendered HTML.
	 */
	public function render( array $activeValues, array $counts ): string;

	/**
	 * Modify WP_Query args based on active filter values.
	 *
	 * @param array<string, mixed> $args  WP_Query args (passed by reference).
	 * @param mixed                $value Active filter value(s).
	 */
	public function applyToQuery( array &$args, mixed $value ): void;

	/**
	 * Get counts for each option.
	 *
	 * @param array<string, mixed> $baseArgs Base WP_Query args (without this filter applied).
	 *
	 * @return array<string, int> [value => count].
	 */
	public function getCounts( array $baseArgs ): array;
}
