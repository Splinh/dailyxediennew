<?php
/**
 * Sort Filter — sorting dropdown for product ordering.
 *
 * When active, removes WC's native catalog ordering to prevent duplicate ORDER BY.
 * Uses WC's native ordering system (wc_product_meta_lookup) for correct, indexed queries.
 *
 * @package HD\Modules\WooCommerce\Filter\Types
 */

namespace HD\Modules\WooCommerce\Filter\Types;

defined( 'ABSPATH' ) || exit;

final class SortFilter extends AbstractFilterType {

	public const TYPE  = 'sort';
	public const LABEL = 'Sort';

	/**
	 * Map our sort keys to WC's native orderby values.
	 *
	 * @return array<string, string>
	 */
	private const WC_ORDERBY_MAP = [
		'price_asc'  => 'price',
		'price_desc' => 'price-desc',
		'name_asc'   => 'title',
		'newest'     => 'date',
		'popularity' => 'popularity',
		'rating'     => 'rating',
	];

	/**
	 * Get translated options.
	 *
	 * @return array<string, string>
	 */
	private function getOptions(): array {
		return [
			'default'    => __( 'Default', 'hd' ),
			'price_asc'  => __( 'Price: Low to High', 'hd' ),
			'price_desc' => __( 'Price: High to Low', 'hd' ),
			'name_asc'   => __( 'Name: A to Z', 'hd' ),
			'newest'     => __( 'Newest', 'hd' ),
			'popularity' => __( 'Popularity', 'hd' ),
			'rating'     => __( 'Average Rating', 'hd' ),
		];
	}

	/** @inheritDoc */
	public function render( array $activeValues, array $counts ): string {
		// Remove native WC sorting dropdown whenever this filter type is rendered.
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

		$current = $activeValues[0] ?? 'default';

		$html = '<select class="hd-filter__sort-select" name="hd_' . esc_attr( $this->config['id'] ?? 'sort_1' ) . '">';
		foreach ( $this->getOptions() as $value => $label ) {
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $label )
			);
		}
		$html .= '</select>';

		return $html;
	}

	/** @inheritDoc */
	public function applyToQuery( array &$args, mixed $value ): void {
		$sortKey = is_array( $value ) ? ( $value[0] ?? 'default' ) : $value;

		// Remove native WC sorting dropdown to avoid duplicate UI
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

		if ( 'default' === $sortKey || ! isset( self::WC_ORDERBY_MAP[ $sortKey ] ) ) {
			return;
		}

		// Use WC's native ordering system — leverages wc_product_meta_lookup indexed table
		$wcOrderby = self::WC_ORDERBY_MAP[ $sortKey ];
		$ordering  = WC()->query->get_catalog_ordering_args( $wcOrderby );

		$args['orderby'] = $ordering['orderby'];
		$args['order']   = $ordering['order'];

		if ( ! empty( $ordering['meta_key'] ) ) {
			$args['meta_key'] = $ordering['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}
	}

	/** Sort has no counts. */
	public function getCounts( array $baseArgs ): array {
		return [];
	}
}
