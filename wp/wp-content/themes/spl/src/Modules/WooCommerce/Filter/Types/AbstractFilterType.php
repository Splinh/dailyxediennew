<?php
/**
 * Abstract Filter Type — shared logic for all filter types.
 *
 * Provides promoted readonly config, default implementations,
 * and shared rendering helpers (checkbox list, swatch list).
 *
 * @package SPL\Modules\WooCommerce\Filter\Types
 */

namespace SPL\Modules\WooCommerce\Filter\Types;

use SPL\Modules\WooCommerce\Filter\Enum\AdoptiveMode;

defined( 'ABSPATH' ) || exit;

abstract class AbstractFilterType implements FilterTypeInterface {

	/** Override in concrete class. */
	public const TYPE  = '';
	public const LABEL = '';

	public function __construct(
		protected readonly array $config = [],
	) {}

	/** Default: no counts. Override for countable filters. */
	public function getCounts( array $baseArgs ): array {
		return [];
	}

	/** Default: no admin fields. Override if needed. */
	public function adminFields(): array {
		return [];
	}

	// ── Shared Render Helpers ────────────────────────

	/**
	 * Render a checkbox/radio list.
	 *
	 * @param array<int|string, array{slug: string, name: string}> $options      Filter options.
	 * @param array<string>                                        $activeValues Currently selected.
	 * @param array<string, int>                                   $counts       [slug => count].
	 * @param string                                               $filterId     Filter instance ID.
	 *
	 * @return string HTML.
	 */
	protected function renderCheckboxList( array $options, array $activeValues, array $counts, string $filterId ): string {
		if ( empty( $options ) ) {
			return '';
		}

		$adoptive = $this->adoptiveMode();
		$html     = '<ul class="hd-filter__list">';

		foreach ( $options as $option ) {
			$slug  = $option['slug'] ?? '';
			$name  = $option['name'] ?? '';
			$count = $counts[ $slug ] ?? null;

			// Adoptive filtering: handle zero-count items
			if ( 0 === $count ) {
				if ( $adoptive->hidesEmpty() ) {
					continue;
				}
			}

			$isActive   = in_array( $slug, $activeValues, true );
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
				'</label></li>',
				esc_attr( $liClass ),
				esc_attr( $filterId ),
				esc_attr( $slug ),
				$isActive ? ' checked' : '',
				$isDisabled ? ' disabled' : '',
				esc_html( $name ),
				null !== $count ? '<span class="hd-filter__count">(' . absint( $count ) . ')</span>' : ''
			);
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Render a button/swatch list.
	 *
	 * @param array<int|string, array{slug: string, name: string, color?: string, image?: string}> $options Filter options.
	 * @param array<string>                                                                         $activeValues Currently selected.
	 * @param array<string, int>                                                                    $counts [slug => count].
	 * @param string                                                                                $filterId Filter instance ID.
	 *
	 * @return string HTML.
	 */
	protected function renderSwatchList( array $options, array $activeValues, array $counts, string $filterId ): string {
		if ( empty( $options ) ) {
			return '';
		}

		$adoptive = $this->adoptiveMode();
		$display  = $this->config['display'] ?? 'button';
		$html     = '<div class="hd-filter__swatches">';

		foreach ( $options as $option ) {
			$slug  = $option['slug'] ?? '';
			$name  = $option['name'] ?? '';
			$count = $counts[ $slug ] ?? null;

			if ( 0 === $count && $adoptive->hidesEmpty() ) {
				continue;
			}

			$isActive   = in_array( $slug, $activeValues, true );
			$isDisabled = ( 0 === $count && $adoptive->disablesEmpty() );

			$btnClass = 'hd-filter__swatch';
			if ( $isActive ) {
				$btnClass .= ' is-active';
			}
			if ( $isDisabled ) {
				$btnClass .= ' is-disabled';
			}

			$style = '';
			if ( 'color_swatch' === $display && ! empty( $option['color'] ) ) {
				$style = sprintf( ' style="--swatch-color: %s"', esc_attr( sanitize_hex_color( $option['color'] ) ) );
			}

			$html .= sprintf(
				'<button type="button" class="%s" data-filter="%s" data-value="%s"%s%s title="%s">' .
				'<span class="hd-filter__swatch-label">%s</span>' .
				'</button>',
				esc_attr( $btnClass ),
				esc_attr( $filterId ),
				esc_attr( $slug ),
				$style,
				$isDisabled ? ' disabled' : '',
				esc_attr( $name ),
				esc_html( $name )
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a search input field.
	 *
	 * @param string $filterId     Filter instance ID.
	 * @param string $activeValue  Current search value.
	 * @param string $placeholder  Input placeholder.
	 *
	 * @return string HTML.
	 */
	protected function renderSearchInput( string $filterId, string $activeValue = '', string $placeholder = '' ): string {
		return sprintf(
			'<div class="hd-filter__search">' .
			'<input type="search" name="hd_%s" value="%s" placeholder="%s" class="hd-filter__search-input" />' .
			'</div>',
			esc_attr( $filterId ),
			esc_attr( $activeValue ),
			esc_attr( $placeholder ?: __( 'Tìm kiếm sản phẩm...', 'SPL' ) )
		);
	}
	protected function adoptiveMode(): AdoptiveMode {
		return AdoptiveMode::fromConfig( $this->config['adoptive'] ?? AdoptiveMode::Show->value );
	}
}
