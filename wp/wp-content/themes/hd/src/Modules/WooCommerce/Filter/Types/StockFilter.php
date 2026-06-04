<?php
/**
 * Stock Filter — filter by stock status.
 *
 * Applies meta_query on `_stock_status`.
 * Simple checkbox: "In Stock" / "Out of Stock" / "On Backorder".
 *
 * @package HD\Modules\WooCommerce\Filter\Types
 */

namespace HD\Modules\WooCommerce\Filter\Types;

use HD\Core\DB;

defined( 'ABSPATH' ) || exit;

final class StockFilter extends AbstractFilterType {

	public const TYPE  = 'stock';
	public const LABEL = 'Stock Status';

	/**
	 * @inheritDoc
	 */
	public function render( array $activeValues, array $counts ): string {
		$filterId = $this->config['id'] ?? '';
		$options  = [
			[
				'slug' => 'instock',
				'name' => __( 'Còn hàng', 'hd' ),
			],
			[
				'slug' => 'outofstock',
				'name' => __( 'Hết hàng', 'hd' ),
			],
			[
				'slug' => 'onbackorder',
				'name' => __( 'Đặt trước', 'hd' ),
			],
		];

		return $this->renderCheckboxList( $options, $activeValues, $counts, $filterId );
	}

	/**
	 * @inheritDoc
	 */
	public function applyToQuery( array &$args, mixed $value ): void {
		$statuses = array_intersect(
			array_map( 'sanitize_text_field', (array) $value ),
			[ 'instock', 'outofstock', 'onbackorder' ]
		);

		if ( empty( $statuses ) ) {
			return;
		}

		$args['meta_query'][] = [
			'key'     => '_stock_status',
			'value'   => $statuses,
			'compare' => 'IN',
		];
	}

	/**
	 * Get counts via single GROUP BY query.
	 *
	 * @inheritDoc
	 */
	public function getCounts( array $baseArgs ): array {
		$countArgs                   = $baseArgs;
		$countArgs['posts_per_page'] = -1;
		$countArgs['fields']         = 'ids';
		$countArgs['no_found_rows']  = true;

		// Remove stock meta_query
		if ( ! empty( $countArgs['meta_query'] ) ) {
			$countArgs['meta_query'] = array_filter(
				$countArgs['meta_query'],
				static fn( $clause ) => ! is_array( $clause ) || ( $clause['key'] ?? '' ) !== '_stock_status'
			);
		}

		$query      = new \WP_Query( $countArgs );
		$productIds = $query->posts;

		if ( empty( $productIds ) ) {
			return [];
		}

		// Single GROUP BY query
		$db           = DB::db();
		$placeholders = implode( ',', array_fill( 0, count( $productIds ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $db->get_results(
			$db->prepare(
				"SELECT pm.meta_value AS status, COUNT(*) AS cnt
				FROM {$db->postmeta} pm
				WHERE pm.meta_key = '_stock_status' AND pm.post_id IN ($placeholders)
				GROUP BY pm.meta_value",
				...$productIds
			)
		);

		$counts = [
			'instock'     => 0,
			'outofstock'  => 0,
			'onbackorder' => 0,
		];
		foreach ( $results as $row ) {
			if ( isset( $counts[ $row->status ] ) ) {
				$counts[ $row->status ] = (int) $row->cnt;
			}
		}

		return $counts;
	}
}
