<?php
/**
 * Custom Sorting module options panel.
 *
 * @package HDAddons\Modules\CustomSorting
 */

use HDAddons\Modules\CustomSorting\CustomSortingModule;
use HDAddons\Helper;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

$custom_sorting_options = Helper::getOption( CustomSortingModule::optionKey(), [] );
$order_post_type        = $custom_sorting_options[ CustomSortingModule::KEY_ORDER_POST_TYPE ] ?? [];
$order_taxonomy         = $custom_sorting_options[ CustomSortingModule::KEY_ORDER_TAXONOMY ] ?? [];

?>
<div class="container mt-8">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Custom Sorting', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-sort"></span>
				<?php esc_html_e( 'Drag-and-drop sorting for posts and taxonomy terms. Reorder items on their list pages after enabling.', 'hda' ); ?>
			</p>
			<p class="hda-notice__detail">
				<?php esc_html_e( 'Auto-applied on frontend (menu_order for posts, term_order for taxonomies).', 'hda' ); ?>
			</p>
		</div>

		<div class="container grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-6">
			<div class="section section-checkbox">
				<span class="heading flex items-center">
					<?php esc_html_e( 'Check to Sort Post Types', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php echo wp_kses_post( __( 'Adds drag-and-drop sorter in admin. Saved to <code>menu_order</code>.', 'hda' ) ); ?></span>
					</span>
				</span>
		<?php
		$post_types        = get_post_types( [ 'show_ui' => true ], 'objects' );
		$exclude_post_type = [
			'attachment',
			'wp_navigation',
			'product',
		];

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			array_push( $exclude_post_type, 'acf-taxonomy', 'acf-post-type', 'acf-ui-options-page', 'acf-field-group' );
		}

		foreach ( $post_types as $post_type ) :
			if ( in_array( $post_type->name, $exclude_post_type, true ) ) {
				continue;
			}

			$label = esc_html( $post_type->label );
			if ( str_starts_with( $post_type->name, 'shop_' ) ) {
				$label = 'WooCommerce ' . $label;
			}
			if ( str_starts_with( $post_type->name, 'acf-' ) ) {
				$label = 'ACF ' . $label;
			}
			$label .= ' <span>(' . esc_html( $post_type->name ) . ')</span>';
			?>
		<div class="option my-3">
			<div class="controls">
				<label>
					<input type="checkbox" class="checkbox" name="hda_custom_sorting[order_post_type][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php Helper::inArrayChecked( $order_post_type, $post_type->name ); ?>>
					<span class="font-medium text-sm"><?php echo wp_kses_post( $label ); ?></span>
				</label>
			</div>
		</div>
		<?php endforeach; ?>
			</div>

			<div class="section section-checkbox">
				<span class="heading flex items-center">
					<?php esc_html_e( 'Check to Sort Taxonomies', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php echo wp_kses_post( __( 'Adds drag-and-drop sorter on term list. Saved to <code>term_order</code>.', 'hda' ) ); ?></span>
					</span>
				</span>
		<?php
		$taxonomies       = get_taxonomies( [ 'show_ui' => true ], 'objects' );
		$exclude_taxonomy = [
			'link_category',
			'wp_pattern_category',
			'product_cat',
			'product_brand',
		];

		foreach ( $taxonomies as $taxonomy ) :
			if ( in_array( $taxonomy->name, $exclude_taxonomy, true ) ) {
				continue;
			}

			$label  = esc_html( $taxonomy->label );
			$label .= ' <span>(' . esc_html( $taxonomy->name ) . ')</span>';
			?>
		<div class="option my-3">
			<div class="controls">
				<label>
					<input type="checkbox" class="checkbox" name="hda_custom_sorting[order_taxonomy][]" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php Helper::inArrayChecked( $order_taxonomy, $taxonomy->name ); ?>>
					<span class="font-medium text-sm"><?php echo wp_kses_post( $label ); ?></span>
				</label>
			</div>
		</div>
		<?php endforeach; ?>
			</div>

			<div class="section section-checkbox col-span-full">
				<span class="heading"><?php esc_html_e( 'Check to reset order', 'hda' ); ?></span>
				<div class="option">
					<div class="controls">
						<label>
							<input type="checkbox" class="checkbox" name="hda_custom_sorting[order_reset]" id="order_reset" value="1">
							<span class="font-medium text-sm"><?php esc_html_e( 'Reset all', 'hda' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
</div>
