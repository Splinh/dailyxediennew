<?php
/**
 * Featured Filter — boolean toggle showing only featured products.
 *
 * Uses post__in intersection pattern to avoid overwriting other boolean filters.
 *
 * @package SPL\Modules\WooCommerce\Filter\Types
 */

namespace SPL\Modules\WooCommerce\Filter\Types;

defined( 'ABSPATH' ) || exit;

final class FeaturedFilter extends AbstractFilterType {

	public const TYPE  = 'featured';
	public const LABEL = 'Featured';

	/** @inheritDoc */
	public function render( array $activeValues, array $counts ): string {
		$checked  = ! empty( $activeValues );
		$filterId = $this->config['id'] ?? 'featured_1';

		return sprintf(
			'<label class="hd-filter__label">'
			. '<input type="checkbox" class="hd-filter__input" name="hd_%s[]" value="1"%s>'
			. '<span class="hd-filter__text">%s</span>'
			. '</label>',
			esc_attr( $filterId ),
			checked( $checked, true, false ),
			esc_html__( 'Featured', 'SPL' )
		);
	}

	/** @inheritDoc */
	public function applyToQuery( array &$args, mixed $value ): void {
		$isActive = is_array( $value ) ? ! empty( $value[0] ) : ! empty( $value );
		if ( ! $isActive ) {
			return;
		}

		$featuredIds = wc_get_featured_product_ids();
		if ( empty( $featuredIds ) ) {
			$args['post__in'] = [ 0 ];
			return;
		}

		// Intersection pattern: merge with existing post__in
		if ( ! empty( $args['post__in'] ) ) {
			$args['post__in'] = array_values( array_intersect( $args['post__in'], $featuredIds ) );
			if ( empty( $args['post__in'] ) ) {
				$args['post__in'] = [ 0 ];
			}
		} else {
			$args['post__in'] = $featuredIds;
		}
	}

	/** Boolean filter has no per-value counts. */
	public function getCounts( array $baseArgs ): array {
		return [];
	}
}
