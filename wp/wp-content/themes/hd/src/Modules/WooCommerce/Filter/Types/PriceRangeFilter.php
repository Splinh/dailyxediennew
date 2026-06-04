<?php
/**
 * Price Range Filter — slider or custom price ranges.
 *
 * Applies meta_query on `_price` for BETWEEN comparison.
 * Supports slider mode and predefined custom ranges.
 *
 * @package HD\Modules\WooCommerce\Filter\Types
 */

namespace HD\Modules\WooCommerce\Filter\Types;

use HD\Core\DB;

defined( 'ABSPATH' ) || exit;

final class PriceRangeFilter extends AbstractFilterType {

	public const TYPE  = 'price_range';
	public const LABEL = 'Price Range';

	/**
	 * @inheritDoc
	 */
	public function render( array $activeValues, array $counts ): string {
		$filterId = $this->config['id'] ?? '';

		return match ( $this->config['mode'] ?? 'custom_ranges' ) {
			'slider' => $this->renderSlider( $activeValues, $filterId ),
			default  => $this->renderCustomRanges( $activeValues, $counts, $filterId ),
		};
	}

	/**
	 * @inheritDoc
	 */
	public function applyToQuery( array &$args, mixed $value ): void {

		// Slider mode submits an associative min/max array.
		if ( is_array( $value ) && ( array_key_exists( 'min', $value ) || array_key_exists( 'max', $value ) ) ) {
			$min = absint( $value['min'] ?? 0 );
			$max = absint( $value['max'] ?? PHP_INT_MAX );
		} elseif ( is_string( $value ) && str_contains( $value, '-' ) ) {
			// Custom ranges mode: value is range index or "min-max" string.
			[ $min, $max ] = array_map( 'absint', explode( '-', $value, 2 ) );
		} elseif ( is_array( $value ) ) {
			// Handle array of selected ranges
			$metaQueries = [];
			foreach ( (array) $value as $range ) {
				if ( is_string( $range ) && str_contains( $range, '-' ) ) {
					[ $rangeMin, $rangeMax ] = array_map( 'absint', explode( '-', $range, 2 ) );

					$clause = [
						'key'  => '_price',
						'type' => 'NUMERIC',
					];

					if ( 0 === $rangeMax ) {
						// "Above X" range
						$clause['value']   = $rangeMin;
						$clause['compare'] = '>=';
					} else {
						$clause['value']   = [ $rangeMin, $rangeMax ];
						$clause['compare'] = 'BETWEEN';
					}

					$metaQueries[] = $clause;
				}
			}

			if ( ! empty( $metaQueries ) ) {
				$args['meta_query'][] = [
					'relation' => 'OR',
					...$metaQueries,
				];
			}

			return;
		} else {
			return;
		}

		if ( isset( $min, $max ) ) {
			$clause = [
				'key'  => '_price',
				'type' => 'NUMERIC',
			];

			if ( 0 === $max ) {
				$clause['value']   = $min;
				$clause['compare'] = '>=';
			} else {
				$clause['value']   = [ $min, $max ];
				$clause['compare'] = 'BETWEEN';
			}

			$args['meta_query'][] = $clause;
		}
	}

	/**
	 * Get counts via single meta query + PHP-side bucketing.
	 *
	 * @inheritDoc
	 */
	public function getCounts( array $baseArgs ): array {
		$ranges = $this->config['ranges'] ?? [];
		if ( empty( $ranges ) ) {
			return [];
		}

		$countArgs                   = $baseArgs;
		$countArgs['posts_per_page'] = -1;
		$countArgs['fields']         = 'ids';
		$countArgs['no_found_rows']  = true;

		// Remove price meta_query from base args
		if ( ! empty( $countArgs['meta_query'] ) ) {
			$countArgs['meta_query'] = array_filter(
				$countArgs['meta_query'],
				static fn( $clause ) => ! is_array( $clause ) || ( $clause['key'] ?? '' ) !== '_price'
			);
		}

		$query      = new \WP_Query( $countArgs );
		$productIds = $query->posts;

		if ( empty( $productIds ) ) {
			return array_fill_keys(
				array_map( static fn( $r ) => absint( $r['min'] ?? 0 ) . '-' . absint( $r['max'] ?? 0 ), $ranges ),
				0
			);
		}

		$counts  = [];
		$selects = [];
		$values  = [];

		foreach ( $ranges as $index => $range ) {
			$min = absint( $range['min'] ?? 0 );
			$max = absint( $range['max'] ?? 0 );
			$key = $min . '-' . $max;

			$counts[ $key ] = 0;
			if ( 0 === $max ) {
				$selects[] = "SUM(CASE WHEN price >= %d THEN 1 ELSE 0 END) AS `range_$index`";
				$values[]  = $min;
			} else {
				$selects[] = "SUM(CASE WHEN price >= %d AND price <= %d THEN 1 ELSE 0 END) AS `range_$index`";
				$values[]  = $min;
				$values[]  = $max;
			}
		}

		$db           = DB::db();
		$placeholders = implode( ',', array_fill( 0, count( $productIds ), '%d' ) );
		$sql          = 'SELECT ' . implode( ', ', $selects ) . "
			FROM (
				SELECT pm.post_id, MAX(CAST(pm.meta_value AS DECIMAL(20,6))) AS price
				FROM {$db->postmeta} pm
				WHERE pm.meta_key = '_price' AND pm.post_id IN ($placeholders)
				GROUP BY pm.post_id
			) price_rows";
		$row          = (array) $db->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic CASE columns and prepared IN values are built above.
			$db->prepare( $sql, ...array_merge( $values, array_map( 'absint', $productIds ) ) ),
			ARRAY_A
		);

		foreach ( array_keys( $counts ) as $index => $key ) {
			$counts[ $key ] = (int) ( $row[ "range_$index" ] ?? 0 );
		}

		return $counts;
	}

	/** @inheritDoc */
	public function adminFields(): array {
		return [
			'mode'   => [
				'type'    => 'select',
				'options' => [ 'slider', 'custom_ranges' ],
			],
			'ranges' => [
				'type'   => 'repeater',
				'fields' => [ 'min', 'max', 'label' ],
			],
		];
	}

	// ── Private Renderers ───────────────────────────

	/**
	 * Render slider UI.
	 */
	private function renderSlider( array $activeValues, string $filterId ): string {
		$min  = absint( $this->config['min'] ?? 0 );
		$max  = absint( $this->config['max'] ?? 10000000 );
		$step = absint( $this->config['step'] ?? 100000 );

		$activeMin = absint( $activeValues['min'] ?? $min );
		$activeMax = absint( $activeValues['max'] ?? $max );

		return sprintf(
			'<div class="hd-filter__slider" data-filter="%s" data-min="%d" data-max="%d" data-step="%d">' .
			'<input type="range" name="hd_%s_min" min="%d" max="%d" step="%d" value="%d" class="hd-filter__range hd-filter__range--min" />' .
			'<input type="range" name="hd_%s_max" min="%d" max="%d" step="%d" value="%d" class="hd-filter__range hd-filter__range--max" />' .
			'<div class="hd-filter__slider-display">' .
			'<span class="hd-filter__slider-min">%s</span>' .
			'<span class="hd-filter__slider-sep">–</span>' .
			'<span class="hd-filter__slider-max">%s</span>' .
			'</div></div>',
			esc_attr( $filterId ),
			$min,
			$max,
			$step,
			esc_attr( $filterId ),
			$min,
			$max,
			$step,
			$activeMin,
			esc_attr( $filterId ),
			$min,
			$max,
			$step,
			$activeMax,
			number_format( $activeMin ) . '₫',
			number_format( $activeMax ) . '₫'
		);
	}

	/**
	 * Render custom price range checkboxes.
	 */
	private function renderCustomRanges( array $activeValues, array $counts, string $filterId ): string {
		$ranges = $this->config['ranges'] ?? [];
		if ( empty( $ranges ) ) {
			return '';
		}

		$options = [];
		foreach ( $ranges as $range ) {
			$min  = absint( $range['min'] ?? 0 );
			$max  = absint( $range['max'] ?? 0 );
			$slug = $min . '-' . $max;

			$options[] = [
				'slug' => $slug,
				'name' => $range['label'] ?? ( number_format( $min ) . '₫ – ' . number_format( $max ) . '₫' ),
			];
		}

		return $this->renderCheckboxList( $options, $activeValues, $counts, $filterId );
	}
}
