<?php
/**
 * Taxonomy Filter — product_cat, product_tag, any custom taxonomy.
 *
 * Applies tax_query to filter products by taxonomy terms.
 * Supports list, dropdown, and hierarchy display modes.
 *
 * @package HD\Modules\WooCommerce\Filter\Types
 */

namespace HD\Modules\WooCommerce\Filter\Types;

use HD\Core\DB;

defined( 'ABSPATH' ) || exit;

final class TaxonomyFilter extends AbstractFilterType {

	public const TYPE  = 'taxonomy';
	public const LABEL = 'Taxonomy';

	/**
	 * @inheritDoc
	 */
	public function render( array $activeValues, array $counts ): string {
		$taxonomy = sanitize_key( $this->config['taxonomy'] ?? '' );
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}

		$display = $this->config['display'] ?? 'list';

		// Orderby support (Step 9.1)
		$orderby                   = $this->config['orderby'] ?? 'name_asc';
		[ $sortField, $sortOrder ] = match ( $orderby ) {
			'name_desc'  => [ 'name', 'DESC' ],
			'count_desc' => [ 'count', 'DESC' ],
			'menu_order' => [ 'menu_order', 'ASC' ],
			default      => [ 'name', 'ASC' ],
		};

		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => $sortField,
			'order'      => $sortOrder,
		];

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		// Exclude/include terms (Step 9.2)
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

		if ( 'hierarchy' === $display ) {
			$childrenByParent = $this->groupTermsByParent( $terms );

			return $this->renderHierarchy( $childrenByParent[0] ?? [], $childrenByParent, $activeValues, $counts, $filterId );
		}

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
		$taxonomy = sanitize_key( $this->config['taxonomy'] ?? '' );
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$terms = array_map( 'sanitize_text_field', (array) $value );
		if ( empty( $terms ) ) {
			return;
		}

		$args['tax_query'][] = [
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => $terms,
		];
	}

	/**
	 * Get counts via single GROUP BY query instead of N get_objects_in_term() calls.
	 *
	 * @inheritDoc
	 */
	public function getCounts( array $baseArgs ): array {
		$taxonomy = sanitize_key( $this->config['taxonomy'] ?? '' );
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		// Remove pagination for count query
		$countArgs                   = $baseArgs;
		$countArgs['posts_per_page'] = -1;
		$countArgs['fields']         = 'ids';
		$countArgs['no_found_rows']  = true;

		// Remove this filter's own tax_query to get unbiased counts
		if ( ! empty( $countArgs['tax_query'] ) ) {
			$countArgs['tax_query'] = array_filter(
				$countArgs['tax_query'],
				static fn( $clause ) => ! is_array( $clause ) || ( $clause['taxonomy'] ?? '' ) !== $taxonomy
			);
		}

		$query      = new \WP_Query( $countArgs );
		$productIds = $query->posts;

		if ( empty( $productIds ) ) {
			return [];
		}

		// Single GROUP BY query: term_slug → count
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
				$taxonomy,
				...$productIds
			)
		);

		$adoptive = $this->adoptiveMode();
		$counts   = [];

		foreach ( $results as $row ) {
			$counts[ $row->slug ] = (int) $row->cnt;
		}

		// Include zero-count terms when adoptive !== 'hide'
		if ( ! $adoptive->hidesEmpty() ) {
			$allTerms = get_terms(
				[
					'taxonomy'   => $taxonomy,
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
			'taxonomy' => [
				'type'  => 'select',
				'label' => 'Taxonomy',
			],
			'display'  => [
				'type'    => 'select',
				'options' => [ 'list', 'dropdown', 'hierarchy' ],
			],
		];
	}

	// ── Private Helpers ─────────────────────────────

	/**
	 * Render hierarchical taxonomy tree.
	 *
	 * @param array<\WP_Term>        $terms            Terms for this level.
	 * @param array<int, \WP_Term[]> $childrenByParent Parent term ID to child terms map.
	 * @param array<string>          $activeValues     Active slugs.
	 * @param array<string, int>     $counts           [slug => count].
	 * @param string                 $filterId         Filter instance ID.
	 * @param int                    $depth            Current depth (max 5).
	 *
	 * @return string HTML.
	 */
	private function renderHierarchy( array $terms, array $childrenByParent, array $activeValues, array $counts, string $filterId, int $depth = 0 ): string {
		if ( $depth >= 5 ) {
			return '';
		}

		$html = '<ul class="hd-filter__list hd-filter__list--hierarchy">';

		foreach ( $terms as $term ) {
			$count = $counts[ $term->slug ] ?? null;

			$adoptive = $this->adoptiveMode();
			if ( 0 === $count && $adoptive->hidesEmpty() ) {
				continue;
			}

			$isActive   = in_array( $term->slug, $activeValues, true );
			$isDisabled = ( 0 === $count && $adoptive->disablesEmpty() );

			$liClass = 'hd-filter__item';
			if ( $isActive ) {
				$liClass .= ' is-active';
			}
			if ( $isDisabled ) {
				$liClass .= ' is-disabled';
			}

			$html .= sprintf(
				'<li class="%s">' .
				'<label class="hd-filter__label">' .
				'<input type="checkbox" name="hd_%s[]" value="%s"%s%s class="hd-filter__input" />' .
				'<span class="hd-filter__text">%s</span>' .
				'%s' .
				'</label>',
				esc_attr( $liClass ),
				esc_attr( $filterId ),
				esc_attr( $term->slug ),
				$isActive ? ' checked' : '',
				$isDisabled ? ' disabled' : '',
				esc_html( $term->name ),
				null !== $count ? '<span class="hd-filter__count">(' . absint( $count ) . ')</span>' : ''
			);

			$children = $childrenByParent[ (int) $term->term_id ] ?? [];
			if ( ! empty( $children ) ) {
				$html .= $this->renderHierarchy( $children, $childrenByParent, $activeValues, $counts, $filterId, $depth + 1 );
			}

			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Group flat term results by parent ID.
	 *
	 * @param array<\WP_Term> $terms Flat term list.
	 *
	 * @return array<int, \WP_Term[]>
	 */
	private function groupTermsByParent( array $terms ): array {
		$childrenByParent = [];

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$childrenByParent[ (int) $term->parent ][] = $term;
		}

		return $childrenByParent;
	}
}
