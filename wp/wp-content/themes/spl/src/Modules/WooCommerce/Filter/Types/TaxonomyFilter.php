<?php
/**
 * Taxonomy Filter — product_cat, product_tag, any custom taxonomy.
 *
 * Applies tax_query to filter products by taxonomy terms.
 * Supports list, dropdown, and hierarchy display modes.
 *
 * @package SPL\Modules\WooCommerce\Filter\Types
 */

namespace SPL\Modules\WooCommerce\Filter\Types;

use SPL\Core\DB;

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
			'orderby'    => 'product_cat' === $taxonomy ? 'meta_value_num' : $sortField,
			'meta_key'   => 'product_cat' === $taxonomy ? 'order' : '',
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

		if ( 'hierarchy' === $display || 'product_cat' === $taxonomy ) {
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

		$listClass = ( 0 === $depth )
			? 'hd-filter__list hd-filter__list--hierarchy space-y-1.5'
			: 'hd-filter__sublist hidden pl-3 ml-3 mt-1 space-y-1 border-l-2 border-slate-200';

		$html = sprintf( '<ul class="%s">', esc_attr( $listClass ) );

		foreach ( $terms as $term ) {
			$count = $counts[ $term->slug ] ?? null;

			$adoptive = $this->adoptiveMode();
			if ( 0 === $count && $adoptive->hidesEmpty() ) {
				continue;
			}

			$children    = $childrenByParent[ (int) $term->term_id ] ?? [];
			$hasChildren = ! empty( $children );

			$isActive   = in_array( $term->slug, $activeValues, true );
			$isDisabled = ( 0 === $count && $adoptive->disablesEmpty() );

			// Check if any child is active to auto-expand parent dropdown
			$hasActiveChild = false;
			if ( $hasChildren ) {
				foreach ( $children as $child ) {
					if ( in_array( $child->slug, $activeValues, true ) ) {
						$hasActiveChild = true;
						break;
					}
				}
			}

			$liClass = 'hd-filter__item';
			if ( $isActive ) {
				$liClass .= ' is-active';
			}
			if ( $isDisabled ) {
				$liClass .= ' is-disabled';
			}
			if ( $hasChildren ) {
				$liClass .= ' has-children';
			}

			$toggleBtn = '';
			if ( $hasChildren ) {
				$rotateClass = ( $isActive || $hasActiveChild ) ? ' rotate-180' : '';
				$toggleBtn   = sprintf(
					'<button type="button" class="hd-filter__toggle-btn p-1 text-slate-400 hover:text-[#1e73be] hover:bg-slate-200/50 rounded-md transition-transform duration-200%s" onclick="this.classList.toggle(\'rotate-180\'); var sub=this.closest(\'li\').querySelector(\'.hd-filter__sublist\'); if(sub) sub.classList.toggle(\'hidden\');" aria-label="%s">' .
					'<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>' .
					'</button>',
					$rotateClass,
					esc_attr__( 'Mở rộng danh mục', 'spl' )
				);
			}

			// Style parent (depth 0) differently from child (depth > 0)
			if ( 0 === $depth ) {
				$labelClass  = 'hd-filter__label flex-1 cursor-pointer flex items-center gap-2 text-xs py-1.5 px-2.5 rounded-xl bg-slate-50/90 hover:bg-slate-100/90 border border-slate-200/80 transition-all font-bold text-slate-900 shadow-2xs';
				$textStyle   = 'font-bold text-slate-900 text-xs sm:text-[13px]';
				$countMarkup = null !== $count ? '<span class="hd-filter__count text-[10px] font-bold text-slate-600 bg-slate-200/80 px-2 py-0.5 rounded-full ml-auto">(' . absint( $count ) . ')</span>' : '';
			} else {
				$labelClass  = 'hd-filter__label flex-1 cursor-pointer flex items-center gap-2 text-xs py-1.5 px-2 rounded-lg hover:bg-slate-50 hover:text-[#1e73be] transition-colors';
				$textStyle   = 'font-medium text-slate-600 text-xs';
				$countMarkup = null !== $count ? '<span class="hd-filter__count text-slate-400 text-[10px] font-normal ml-auto">(' . absint( $count ) . ')</span>' : '';
			}

			$html .= sprintf(
				'<li class="%s">' .
				'<div class="flex items-center justify-between gap-1 w-full">' .
				'<label class="%s">' .
				'<input type="checkbox" name="hd_%s[]" value="%s"%s%s class="hd-filter__input rounded border-slate-300 text-[#1e73be] focus:ring-[#1e73be]" />' .
				'<span class="hd-filter__text %s">%s</span>' .
				'%s' .
				'</label>' .
				'%s' .
				'</div>',
				esc_attr( $liClass ),
				esc_attr( $labelClass ),
				esc_attr( $filterId ),
				esc_attr( $term->slug ),
				$isActive ? ' checked' : '',
				$isDisabled ? ' disabled' : '',
				esc_attr( $textStyle ),
				esc_html( $term->name ),
				$countMarkup,
				$toggleBtn
			);

			if ( $hasChildren ) {
				$subHtml = $this->renderHierarchy( $children, $childrenByParent, $activeValues, $counts, $filterId, $depth + 1 );
				if ( $hasActiveChild ) {
					$subHtml = str_replace( 'hd-filter__sublist hidden', 'hd-filter__sublist', $subHtml );
				}
				$html .= $subHtml;
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
