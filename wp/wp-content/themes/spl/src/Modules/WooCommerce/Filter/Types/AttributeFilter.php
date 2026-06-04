<?php
/**
 * Attribute Filter — pa_* product attributes with swatch support.
 *
 * Applies tax_query on attribute taxonomies.
 * Supports list, button, color_swatch, image_swatch display modes.
 *
 * @package SPL\Modules\WooCommerce\Filter\Types
 */

namespace SPL\Modules\WooCommerce\Filter\Types;

use SPL\Core\DB;
use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class AttributeFilter extends AbstractFilterType {

	public const TYPE  = 'attribute';
	public const LABEL = 'Product Attribute';

	/**
	 * @inheritDoc
	 */
	public function render( array $activeValues, array $counts ): string {
		$attribute = sanitize_key( $this->config['taxonomy'] ?? '' );
		if ( ! $attribute || ! taxonomy_exists( $attribute ) ) {
			return '';
		}

		// Orderby support
		$orderby                   = $this->config['orderby'] ?? 'name_asc';
		[ $sortField, $sortOrder ] = match ( $orderby ) {
			'name_desc'  => [ 'name', 'DESC' ],
			'count_desc' => [ 'count', 'DESC' ],
			'menu_order' => [ 'menu_order', 'ASC' ],
			default      => [ 'name', 'ASC' ],
		};

		$terms = get_terms(
			[
				'taxonomy'   => $attribute,
				'hide_empty' => true,
				'orderby'    => $sortField,
				'order'      => $sortOrder,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		// Exclude/include terms
		$excludeTerms = $this->config['exclude_terms'] ?? [];
		if ( ! empty( $excludeTerms ) ) {
			$includeMode = ! empty( $this->config['include_mode'] );
			$terms       = array_filter(
				$terms,
				static fn( \WP_Term $t ): bool =>
				$includeMode
					? in_array( $t->slug, $excludeTerms, true )
					: ! in_array( $t->slug, $excludeTerms, true )
			);
		}

		$filterId = $this->config['id'] ?? '';
		$display  = $this->config['display'] ?? 'list';

		// Swatch/button display modes
		if ( in_array( $display, [ 'color_swatch', 'image_swatch', 'button' ], true ) ) {
			$options = array_map(
				static function ( \WP_Term $term ) use ( $display ): array {
					$option = [
						'slug' => $term->slug,
						'name' => $term->name,
					];

					if ( 'color_swatch' === $display ) {
						// Use literal key '_hd_swatch_color' to prevent fatal if Swatches module is removed.
						$option['color'] = get_term_meta( $term->term_id, '_hd_swatch_color', true );
					}

					if ( 'image_swatch' === $display ) {
						// Use literal key '_hd_swatch_image' to prevent fatal if Swatches module is removed.
						$imageId         = absint( get_term_meta( $term->term_id, '_hd_swatch_image', true ) );
						$option['image'] = $imageId ? Helper::attachmentImageSrc( $imageId, 'thumbnail' ) : '';
					}

					return $option;
				},
				$terms
			);

			return $this->renderSwatchList( $options, $activeValues, $counts, $filterId );
		}

		// Render as standard checkbox list when not a swatch or button.
		$options = array_map(
			static fn( \WP_Term $term ): array => [
				'slug' => $term->slug,
				'name' => $term->name,
			],
			$terms
		);

		return $this->renderCheckboxList( $options, $activeValues, $counts, $filterId );
	}

	/**
	 * @inheritDoc
	 */
	public function applyToQuery( array &$args, mixed $value ): void {
		$attribute = sanitize_key( $this->config['taxonomy'] ?? '' );
		if ( ! $attribute || ! taxonomy_exists( $attribute ) ) {
			return;
		}

		$terms = array_map( 'sanitize_text_field', (array) $value );
		if ( empty( $terms ) ) {
			return;
		}

		$args['tax_query'][] = [
			'taxonomy' => $attribute,
			'field'    => 'slug',
			'terms'    => $terms,
		];
	}

	/**
	 * Get counts via single GROUP BY query.
	 *
	 * @inheritDoc
	 */
	public function getCounts( array $baseArgs ): array {
		$attribute = sanitize_key( $this->config['taxonomy'] ?? '' );
		if ( ! $attribute || ! taxonomy_exists( $attribute ) ) {
			return [];
		}

		$countArgs                   = $baseArgs;
		$countArgs['posts_per_page'] = -1;
		$countArgs['fields']         = 'ids';
		$countArgs['no_found_rows']  = true;

		// Remove own tax_query
		if ( ! empty( $countArgs['tax_query'] ) ) {
			$countArgs['tax_query'] = array_filter(
				$countArgs['tax_query'],
				static fn( $clause ) => ! is_array( $clause ) || ( $clause['taxonomy'] ?? '' ) !== $attribute
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
				"SELECT t.slug, COUNT(DISTINCT tr.object_id) AS cnt
				FROM {$db->term_relationships} tr
				JOIN {$db->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				JOIN {$db->terms} t ON tt.term_id = t.term_id
				WHERE tt.taxonomy = %s AND tr.object_id IN ($placeholders)
				GROUP BY t.slug",
				$attribute,
				...$productIds
			)
		);

		$adoptive = $this->adoptiveMode();
		$counts   = [];

		foreach ( $results as $row ) {
			$counts[ $row->slug ] = (int) $row->cnt;
		}

		if ( ! $adoptive->hidesEmpty() ) {
			$allTerms = get_terms(
				[
					'taxonomy'   => $attribute,
					'hide_empty' => true,
				]
			);
			if ( ! is_wp_error( $allTerms ) ) {
				foreach ( $allTerms as $term ) {
					if ( ! isset( $counts[ $term->slug ] ) ) {
						$counts[ $term->slug ] = 0;
					}
				}
			}
		}

		return $counts;
	}

	/** @inheritDoc */
	public function adminFields(): array {
		return [
			'attribute' => [
				'type'  => 'select',
				'label' => 'Attribute',
			],
			'display'   => [
				'type'    => 'select',
				'options' => [ 'list', 'button', 'color_swatch', 'image_swatch' ],
			],
		];
	}
}
