<?php
/**
 * Rating Filter — filter by average product rating.
 *
 * Applies meta_query on `_wc_average_rating`.
 * Renders star-based checkbox list (4+, 3+, 2+, 1+).
 *
 * @package HD\Modules\WooCommerce\Filter\Types
 */

namespace HD\Modules\WooCommerce\Filter\Types;

use HD\Core\DB;

defined( 'ABSPATH' ) || exit;

final class RatingFilter extends AbstractFilterType {

	public const TYPE  = 'rating';
	public const LABEL = 'Rating';

	/** Rating levels: "4 stars and up", "3 stars and up", etc. */
	private const LEVELS = [ 4, 3, 2, 1 ];

	/**
	 * @inheritDoc
	 */
	public function render( array $activeValues, array $counts ): string {
		$filterId = $this->config['id'] ?? '';
		$options  = [];

		foreach ( self::LEVELS as $level ) {
			$options[] = [
				'slug' => (string) $level,
				'name' => str_repeat( '★', $level ) . str_repeat( '☆', 5 - $level ) . ' ' .
							sprintf( __( '%d sao trở lên', 'hd' ), $level ),
			];
		}

		return $this->renderCheckboxList( $options, $activeValues, $counts, $filterId );
	}

	/**
	 * @inheritDoc
	 */
	public function applyToQuery( array &$args, mixed $value ): void {
		$ratings = array_map( 'absint', (array) $value );
		$ratings = array_filter( $ratings, static fn( int $r ): bool => $r >= 1 && $r <= 5 );

		if ( empty( $ratings ) ) {
			return;
		}

		// Use the minimum selected rating for "X stars and above"
		$minRating = min( $ratings );

		$args['meta_query'][] = [
			'key'     => '_wc_average_rating',
			'value'   => $minRating,
			'type'    => 'DECIMAL(3,2)',
			'compare' => '>=',
		];
	}

	/**
	 * Get counts via single meta fetch + PHP-side bucketing.
	 *
	 * @inheritDoc
	 */
	public function getCounts( array $baseArgs ): array {
		$countArgs                   = $baseArgs;
		$countArgs['posts_per_page'] = -1;
		$countArgs['fields']         = 'ids';
		$countArgs['no_found_rows']  = true;

		// Remove rating meta_query
		if ( ! empty( $countArgs['meta_query'] ) ) {
			$countArgs['meta_query'] = array_filter(
				$countArgs['meta_query'],
				static fn( $clause ) => ! is_array( $clause ) || ( $clause['key'] ?? '' ) !== '_wc_average_rating'
			);
		}

		$query      = new \WP_Query( $countArgs );
		$productIds = $query->posts;

		if ( empty( $productIds ) ) {
			return [];
		}

		// Fetch all ratings in 1 query, bucket in PHP
		$db           = DB::db();
		$placeholders = implode( ',', array_fill( 0, count( $productIds ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ratings = array_map(
			'floatval',
			$db->get_col(
				$db->prepare(
					"SELECT pm.meta_value FROM {$db->postmeta} pm
				WHERE pm.meta_key = '_wc_average_rating' AND pm.post_id IN ($placeholders)",
					...$productIds
				)
			)
		);

		$counts = array_fill_keys( array_map( 'strval', self::LEVELS ), 0 );
		foreach ( $ratings as $rating ) {
			foreach ( self::LEVELS as $level ) {
				if ( $rating >= $level ) {
					++$counts[ (string) $level ];
				}
			}
		}

		return $counts;
	}
}
