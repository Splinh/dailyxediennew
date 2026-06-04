<?php
/**
 * Trait for handling taxonomy order customization.
 *
 * @package HDAddons\Modules\CustomSorting
 */

namespace HDAddons\Modules\CustomSorting;

\defined( 'ABSPATH' ) || exit;

trait TaxonomyOrderTrait {

	/**
	 * Filter terms query arguments to native term_order.
	 *
	 * @param array $args Query args.
	 * @param array $taxonomies Array of taxonomies being queried.
	 *
	 * @return array Modified query args.
	 */
	public function customOrderGetTermsArgs( array $args, array $taxonomies ): array {
		// Ignore if user is manually sorting by a column in admin
		if ( isset( $_GET['orderby'] ) && is_admin() && ! wp_doing_ajax() ) {
			return $args;
		}

		if ( empty( $taxonomies ) ) {
			return $args;
		}

		// Check if any tracked taxonomy is being queried
		$intersect = array_intersect( $taxonomies, $this->orderTaxonomy );
		if ( empty( $intersect ) ) {
			return $args;
		}

		$args['orderby'] = 'term_order';
		return $args;
	}
}
