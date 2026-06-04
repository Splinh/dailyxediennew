<?php
/**
 * Filter Meta — centralized meta key constants for filter presets.
 *
 * Single source of truth for all filter-preset-related postmeta keys.
 * All filter classes reference these constants instead of hardcoded strings.
 *
 * @package SPL\Modules\WooCommerce\Filter
 */

namespace SPL\Modules\WooCommerce\Filter;

defined( 'ABSPATH' ) || exit;

final class FilterMeta {

	// ── CPT ───────────────────────────────────────────
	public const POST_TYPE = 'hd_filter_preset';

	// ── Post Meta Keys ───────────────────────────────
	public const CONFIG  = '_hd_filter_config';  // JSON array of filter items
	public const LAYOUT  = '_hd_filter_layout';  // vertical|horizontal
	public const TRIGGER = '_hd_filter_trigger'; // auto|manual|hybrid
	public const ENABLED = '_hd_filter_enabled'; // 1|0

	// ── Per-Filter Item Defaults ─────────────────────

	/**
	 * Default values for a single filter item config.
	 * Used by FilterManager::normalizeItemConfig().
	 *
	 * @var array{
	 *     id: string,
	 *     type: string,
	 *     label: string,
	 *     taxonomy: string,
	 *     display: string,
	 *     adoptive: 'show'|'hide'|'disable',
	 *     orderby: string,
	 *     more_less: bool,
	 *     more_less_count: int,
	 *     searchable: bool,
	 *     exclude_terms: list<string>,
	 *     include_mode: bool,
	 *     show_chips: bool,
	 *     collapse: bool,
	 *     enabled: bool,
	 *     position: int,
	 *     mode: string,
	 *     ranges: list<array{min: float|int, max: float|int, label: string}>,
	 *     step: int,
	 *     min: int,
	 *     max: int
	 * }
	 */
	public const ITEM_DEFAULTS = [
		'id'              => '',
		'type'            => 'taxonomy',
		'label'           => '',
		'taxonomy'        => '',
		'display'         => 'checkbox',
		'adoptive'        => 'show',
		'orderby'         => 'name_asc',
		'more_less'       => false,
		'more_less_count' => 5,
		'searchable'      => false,
		'exclude_terms'   => [],
		'include_mode'    => false,
		'show_chips'      => true,
		'collapse'        => false,
		'enabled'         => true,
		'position'        => 0,
		// price_range-specific
		'mode'            => 'custom_ranges',
		'ranges'          => [],
		'step'            => 100000,
		'min'             => 0,
		'max'             => 10000000,
	];

	/**
	 * All postmeta keys (for bulk operations).
	 *
	 * @return string[]
	 */
	public static function allKeys(): array {
		return [
			self::CONFIG,
			self::LAYOUT,
			self::TRIGGER,
			self::ENABLED,
		];
	}
}
