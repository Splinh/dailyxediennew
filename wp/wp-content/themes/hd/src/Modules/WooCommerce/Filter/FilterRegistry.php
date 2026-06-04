<?php
/**
 * Filter Registry — factory for filter type instances.
 *
 * CONST array of built-in types, extensible via `hd_wc_filter_types` filter.
 * Same pattern as WooCommerceModule::FEATURES — explicit, zero overhead.
 *
 * @package HD\Modules\WooCommerce\Filter
 */

namespace HD\Modules\WooCommerce\Filter;

use HD\Modules\WooCommerce\Filter\Types\AttributeFilter;
use HD\Modules\WooCommerce\Filter\Types\FeaturedFilter;
use HD\Modules\WooCommerce\Filter\Types\FilterTypeInterface;
use HD\Modules\WooCommerce\Filter\Types\OnSaleFilter;
use HD\Modules\WooCommerce\Filter\Types\PriceRangeFilter;
use HD\Modules\WooCommerce\Filter\Types\RatingFilter;
use HD\Modules\WooCommerce\Filter\Types\SearchFilter;
use HD\Modules\WooCommerce\Filter\Types\SortFilter;
use HD\Modules\WooCommerce\Filter\Types\StockFilter;
use HD\Modules\WooCommerce\Filter\Types\TaxonomyFilter;

defined( 'ABSPATH' ) || exit;

final class FilterRegistry {

	/**
	 * Built-in filter types.
	 *
	 * @var array<string, class-string<FilterTypeInterface>>
	 */
	private const DEFAULTS = [
		'taxonomy'    => TaxonomyFilter::class,
		'attribute'   => AttributeFilter::class,
		'price_range' => PriceRangeFilter::class,
		'rating'      => RatingFilter::class,
		'stock'       => StockFilter::class,
		'search'      => SearchFilter::class,
		'sort'        => SortFilter::class,
		'on_sale'     => OnSaleFilter::class,
		'featured'    => FeaturedFilter::class,
	];

	/** @var array<string, class-string<FilterTypeInterface>> */
	private static array $types = [];

	/** @var bool */
	private static bool $booted = false;

	/**
	 * Initialize registry. Called once from FilterManager::register().
	 */
	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}

		self::$types = self::DEFAULTS;

		/** @var array<string, class-string<FilterTypeInterface>> $types */
		self::$types  = apply_filters( 'hd_wc_filter_types', self::$types );
		self::$booted = true;
	}

	/**
	 * Create a filter type instance.
	 *
	 * @param string               $type   Type key (e.g., 'taxonomy', 'attribute').
	 * @param array<string, mixed> $config Filter instance config.
	 *
	 * @return FilterTypeInterface|null
	 */
	public static function make( string $type, array $config = [] ): ?FilterTypeInterface {
		$class = self::$types[ $type ] ?? null;

		return $class ? new $class( config: $config ) : null;
	}

	/**
	 * Get all registered filter types.
	 *
	 * @return array<string, class-string<FilterTypeInterface>>
	 */
	public static function all(): array {
		return self::$types;
	}
}
