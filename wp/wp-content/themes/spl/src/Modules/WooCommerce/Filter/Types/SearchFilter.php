<?php
/**
 * Search Filter — text search within filtered results.
 *
 * Applies WP_Query `s` parameter for keyword search.
 *
 * @package SPL\Modules\WooCommerce\Filter\Types
 */

namespace SPL\Modules\WooCommerce\Filter\Types;

defined( 'ABSPATH' ) || exit;

final class SearchFilter extends AbstractFilterType {

	public const TYPE  = 'search';
	public const LABEL = 'Search';

	/**
	 * @inheritDoc
	 */
	public function render( array $activeValues, array $counts ): string {
		$filterId    = $this->config['id'] ?? '';
		$activeValue = is_array( $activeValues ) ? ( $activeValues[0] ?? '' ) : (string) $activeValues;
		$placeholder = $this->config['placeholder'] ?? '';

		return $this->renderSearchInput( $filterId, $activeValue, $placeholder );
	}

	/**
	 * @inheritDoc
	 */
	public function applyToQuery( array &$args, mixed $value ): void {
		$search = sanitize_text_field( is_array( $value ) ? ( $value[0] ?? '' ) : (string) $value );
		if ( '' === $search ) {
			return;
		}

		$args['s'] = $search;
	}
}
