<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Display "Thông Số Kỹ Thuật" (TSKT) specifications tab on WooCommerce
 * single product pages.
 *
 * Data source: ACF repeater field `tskt_specs` on each product,
 * registered via `acf-json/group_daily_tskt.json`.
 *
 * Tab renders a striped specification table matching the
 * htmlmau/chi-tiet-san-pham.html design.
 *
 * @package SPL
 */
final class TSKTDisplay {

	public static function register(): void {
		add_filter( 'woocommerce_product_tabs', [ self::class, 'addSpecsTab' ], 50 );
	}

	/**
	 * Add the TSKT tab if the product has specification rows.
	 *
	 * @param array<string, array<string, mixed>> $tabs Existing WC tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public static function addSpecsTab( array $tabs ): array {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return $tabs;
		}

		$specs = Helper::getField( 'tskt_rows', $product->get_id() );

		if ( empty( $specs ) || ! is_array( $specs ) ) {
			return $tabs;
		}

		// Filter out empty rows (both label and value blank).
		$valid = array_filter(
			$specs,
			static fn( array $row ): bool => '' !== trim( (string) ( $row['tskt_label'] ?? '' ) )
				|| '' !== trim( (string) ( $row['tskt_value'] ?? '' ) ),
		);

		if ( ! $valid ) {
			return $tabs;
		}

		$tabs['tskt_specs'] = [
			'title'    => __( 'Thông số kỹ thuật', 'spl' ),
			'priority' => 15,
			'callback' => [ self::class, 'renderSpecsTab' ],
		];

		return $tabs;
	}

	/**
	 * Render the specification table inside the tab panel.
	 *
	 * @return void
	 */
	public static function renderSpecsTab(): void {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$specs = Helper::getField( 'tskt_rows', $product->get_id() );

		if ( empty( $specs ) || ! is_array( $specs ) ) {
			return;
		}

		$product_name = $product->get_name();
		?>
		<h2 class="text-lg font-black text-slate-900 mb-5">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: product name */
					__( 'Thông số kỹ thuật %s', 'spl' ),
					$product_name,
				)
			);
			?>
		</h2>
		<div class="overflow-x-auto">
			<table class="w-full text-sm">
				<tbody>
					<?php
					$i = 0;
					foreach ( $specs as $row ) :
						$label = trim( (string) ( $row['tskt_label'] ?? '' ) );
						$value = trim( (string) ( $row['tskt_value'] ?? '' ) );

						if ( '' === $label && '' === $value ) {
							continue;
						}

						$stripe = ( 0 === $i % 2 ) ? '' : ' bg-slate-50/50';
						++$i;
						?>
						<tr class="border-b border-slate-100<?php echo esc_attr( $stripe ); ?>">
							<td class="py-3 pr-4 font-semibold text-slate-700 w-1/3">
								<?php echo esc_html( $label ); ?>
							</td>
							<td class="py-3 text-slate-600">
								<?php echo esc_html( $value ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
