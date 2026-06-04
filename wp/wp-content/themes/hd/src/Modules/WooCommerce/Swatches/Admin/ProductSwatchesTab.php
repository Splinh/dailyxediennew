<?php
/**
 * Product Swatches Tab — per-product swatch settings override.
 *
 * Adds a "Swatches" tab to WooCommerce Product Data panel for variable products.
 * Allows per-product configuration of:
 * - Archive display: which attributes to show on archive/shop cards
 * - Per-term overrides: override global swatch type/data for specific terms
 *
 * @package HD\Modules\WooCommerce\Swatches\Admin
 */

namespace HD\Modules\WooCommerce\Swatches\Admin;

use HD\Core\Helper;
use HD\Modules\WooCommerce\Swatches\SwatchMeta;
use WC_Product_Variable;

defined( 'ABSPATH' ) || exit;

final class ProductSwatchesTab {

	private const META_KEY = '_hd_swatches_product';

	/**
	 * Register product data tab hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'addTab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'renderPanel' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueProductAssets' ] );
	}

	/**
	 * Add "Swatches" tab to product data.
	 *
	 * @param array $tabs Existing tabs.
	 *
	 * @return array
	 */
	public function addTab( array $tabs ): array {
		$tabs['hd_swatches'] = [
			'label'    => __( 'Swatches', 'hd' ),
			'target'   => 'hd_swatches_product_data',
			'class'    => [ 'show_if_variable' ],
			'priority' => 70,
		];

		return $tabs;
	}

	/**
	 * Enqueue admin assets on product edit screen.
	 *
	 * @param string $hookSuffix Current admin page hook suffix.
	 */
	public function enqueueProductAssets( string $hookSuffix ): void {
		if ( ! in_array( $hookSuffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_script( 'wp-color-picker' );
	}

	// ── Panel Rendering ──────────────────────────────

	/**
	 * Render the swatches panel content.
	 */
	public function renderPanel(): void {
		global $post;

		$product = wc_get_product( $post->ID );
		if ( ! $product instanceof WC_Product_Variable ) {
			return;
		}

		$settings     = get_post_meta( $post->ID, self::META_KEY, true ) ?: [];
		$archiveAttrs = $settings['archive_attributes'] ?? [];
		$overrides    = $settings['overrides'] ?? [];
		$attributes   = $product->get_variation_attributes();

		// Pre-fetch terms once per taxonomy (avoid duplicate wc_get_product_terms calls).
		$termCache = [];
		foreach ( $attributes as $taxonomy => $values ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$termCache[ $taxonomy ] = wc_get_product_terms( $product->get_id(), $taxonomy, [ 'fields' => 'all' ] );
			}
		}

		echo '<div id="hd_swatches_product_data" class="panel woocommerce_options_panel">';

		// ── Archive Attribute Picker ──
		echo '<div class="options_group">';
		echo '<p class="form-field"><label>' . esc_html__( 'Archive Display', 'hd' ) . '</label>';
		echo '<span class="description">'
			. esc_html__( 'Select which attributes to show as swatches on shop pages.', 'hd' )
			. '</span></p>';

		echo '<div class="hd-pst-group">';
		echo '<input type="hidden" name="hd_swatches_archive_configured" value="1">';
		echo '<table class="hd-pst-table"><thead><tr>';
		echo '<th class="hd-pst-table__col-check"></th>';
		echo '<th>' . esc_html__( 'Attribute', 'hd' ) . '</th>';
		echo '<th class="hd-pst-table__col-badge">' . esc_html__( 'Type', 'hd' ) . '</th>';
		echo '<th class="hd-pst-table__col-badge">' . esc_html__( 'Terms', 'hd' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $termCache as $taxonomy => $terms ) {
			$label      = wc_attribute_label( $taxonomy );
			$checked    = in_array( $taxonomy, $archiveAttrs, true ) ? 'checked' : '';
			$termCount  = count( $terms );
			$swatchType = $termCount ? ( SwatchMeta::getData( $terms[0]->term_id )['type'] ?: 'select' ) : 'select';

			echo '<tr>';
			echo '<td class="hd-pst-table__col-check"><input type="checkbox" name="hd_swatches_archive_attrs[]" value="'
				. esc_attr( $taxonomy ) . '" ' . $checked . '></td>';
			echo '<td><strong>' . esc_html( $label ) . '</strong></td>';
			echo '<td><span class="hd-pst-badge">' . esc_html( $swatchType ) . '</span></td>';
			echo '<td class="hd-pst-table__col-count">' . esc_html( $termCount ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table></div></div>';

		// ── Per-Term Overrides ──
		echo '<div class="options_group">';
		echo '<p class="form-field"><label>' . esc_html__( 'Term Overrides', 'hd' ) . '</label>';
		echo '<span class="description">'
			. esc_html__( 'Customize swatch appearance for individual terms on this product.', 'hd' )
			. '</span></p>';

		foreach ( $termCache as $taxonomy => $terms ) {
			$label = wc_attribute_label( $taxonomy );

			echo '<details class="hd-pst-group" open>';
			echo '<summary class="hd-pst-group__title">' . esc_html( $label ) . '</summary>';
			echo '<table class="hd-pst-table"><thead><tr>';
			echo '<th class="hd-pst-table__col-term">' . esc_html__( 'Term', 'hd' ) . '</th>';
			echo '<th class="hd-pst-table__col-type">' . esc_html__( 'Type', 'hd' ) . '</th>';
			echo '<th class="hd-pst-table__col-value">' . esc_html__( 'Value', 'hd' ) . '</th>';
			echo '</tr></thead><tbody>';

			foreach ( $terms as $term ) {
				$this->renderTermRow( $taxonomy, $term, $overrides[ $taxonomy ][ $term->slug ] ?? [] );
			}

			echo '</tbody></table></details>';
		}
		echo '</div>';

		$this->printPanelStyles();
		$this->printPanelScript();

		echo '</div>';
	}

	/**
	 * Render a single term override row.
	 *
	 * @param string   $taxonomy     Attribute taxonomy name.
	 * @param \WP_Term $term         Term object.
	 * @param array    $termOverride Saved override data for this term.
	 */
	private function renderTermRow( string $taxonomy, \WP_Term $term, array $termOverride ): void {
		$globalData  = SwatchMeta::getData( $term->term_id );
		$currentType = $termOverride['type'] ?? '';
		$globalLabel = $globalData['type'] ?: '—';
		$prefix      = "hd_swatches_overrides[{$taxonomy}][{$term->slug}]";

		echo '<tr class="hd-pst-row" data-term="' . esc_attr( $term->slug ) . '">';

		// Column 1: Term name + global info.
		echo '<td class="hd-pst-row__term">';
		echo '<strong>' . esc_html( $term->name ) . '</strong>';
		echo '<span class="hd-pst-row__global">' . esc_html__( 'Global', 'hd' ) . ': ';
		$this->renderGlobalPreview( $globalData );
		echo ' ' . esc_html( $globalLabel ) . '</span></td>';

		// Column 2: Type select.
		echo '<td class="hd-pst-row__type">';
		echo '<select name="' . esc_attr( $prefix . '[type]' ) . '" class="hd-pst-type-select">';
		echo '<option value="">' . esc_html__( '— Use global —', 'hd' ) . '</option>';
		foreach ( [
			'color' => 'Color',
			'image' => 'Image',
			'label' => 'Label',
			'radio' => 'Radio',
		] as $val => $lbl ) {
			echo '<option value="' . esc_attr( $val ) . '"' . selected( $currentType, $val, false ) . '>'
				. esc_html( $lbl ) . '</option>';
		}
		echo '</select></td>';

		// Column 3: Value fields (conditional).
		echo '<td class="hd-pst-row__value">';
		$this->renderValueFields( $prefix, $termOverride, $currentType );
		echo '</td></tr>';
	}

	/**
	 * Render conditional value fields (color, image, or hint text).
	 *
	 * @param string $prefix      Field name prefix.
	 * @param array  $termOverride Saved override data.
	 * @param string $currentType  Currently selected type.
	 */
	private function renderValueFields( string $prefix, array $termOverride, string $currentType ): void {
		// Color field.
		printf(
			'<div class="hd-pst-field hd-pst-field--color"%s>'
			. '<input type="text" class="hd-color-picker" name="%s" value="%s"></div>',
			'color' !== $currentType ? ' style="display:none"' : '',
			esc_attr( $prefix . '[color]' ),
			esc_attr( $termOverride['color'] ?? '' )
		);

		// Image field.
		$imageId = absint( $termOverride['image'] ?? 0 );
		printf( '<div class="hd-pst-field hd-pst-field--image"%s>', 'image' !== $currentType ? ' style="display:none"' : '' );
		echo '<div class="hd-pst-image-field">';
		printf( '<input type="hidden" class="hd-pst-image-id" name="%s" value="%s">', esc_attr( $prefix . '[image]' ), esc_attr( $imageId ) );
		echo '<div class="hd-pst-image-preview">';
		if ( $imageId ) {
			echo Helper::attachmentImageHTML( $imageId, [ 40, 40 ] );
		}
		echo '</div>';
		echo '<button type="button" class="button button-small hd-pst-image-upload">' . esc_html__( 'Upload', 'hd' ) . '</button>';
		printf(
			'<button type="button" class="button button-small hd-pst-image-remove"%s>%s</button>',
			! $imageId ? ' style="display:none"' : '',
			esc_html__( 'Remove', 'hd' )
		);
		echo '</div></div>';

		// Label/Radio hint.
		printf(
			'<div class="hd-pst-field hd-pst-field--none"%s><em class="hd-pst-field__hint">%s</em></div>',
			! in_array( $currentType, [ 'label', 'radio' ], true ) ? ' style="display:none"' : '',
			esc_html__( 'No additional data required.', 'hd' )
		);

		// Global hint.
		printf(
			'<div class="hd-pst-field hd-pst-field--global"%s><em class="hd-pst-field__hint">%s</em></div>',
			'' !== $currentType ? ' style="display:none"' : '',
			esc_html__( 'Using global settings.', 'hd' )
		);
	}

	/**
	 * Render global swatch preview (color circle / image thumb).
	 *
	 * @param array $data Global swatch data from SwatchMeta::getData().
	 */
	private function renderGlobalPreview( array $data ): void {
		match ( $data['type'] ) {
			'color' => $data['is_dual']
				? printf(
					'<span class="hd-pst-preview hd-pst-preview--color" style="background:linear-gradient(135deg,%s 50%%,%s 50%%)"></span>',
					esc_attr( sanitize_hex_color( $data['secondary_color'] ) ),
					esc_attr( sanitize_hex_color( $data['color'] ) )
				)
				: printf( '<span class="hd-pst-preview hd-pst-preview--color" style="background:%s"></span>', esc_attr( sanitize_hex_color( $data['color'] ) ) ),
			'image' => printf( '<span class="hd-pst-preview hd-pst-preview--image">%s</span>', Helper::attachmentImageHTML( $data['image'], [ 16, 16 ] ) ),
			default => null,
		};
	}

	// ── Inline Admin Assets ──────────────────────────

	/**
	 * Print inline CSS for the product swatches panel.
	 */
	private function printPanelStyles(): void {
		?>
		<style>
			#hd_swatches_product_data{padding:6px 0 0}
			.hd-pst-group{margin:4px 12px 12px;border:1px solid #eee;border-radius:4px;background:#fff;overflow:hidden}
			.hd-pst-group__title{cursor:pointer;padding:10px 14px;font-weight:600;font-size:13px;color:#1d2327;background:#fafafa;border-bottom:1px solid #eee;user-select:none;list-style:none}
			.hd-pst-group__title::-webkit-details-marker{display:none}
			.hd-pst-group__title::before{content:'\25B6';font-size:9px;margin-right:8px;color:#999;display:inline-block;transition:transform .15s}
			.hd-pst-group[open]>.hd-pst-group__title::before{transform:rotate(90deg)}
			.hd-pst-group__title:hover{background:#f0f0f1}
			.hd-pst-group:not([open])>.hd-pst-group__title{border-bottom:none}
			.hd-pst-table{width:100%;border:none;border-spacing:0;border-collapse:collapse;margin:0}
			.hd-pst-table thead th{background:#fafafa;padding:7px 12px;font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #eee;text-align:left}
			.hd-pst-table__col-term{width:30%}.hd-pst-table__col-type{width:25%}.hd-pst-table__col-value{width:45%}
			.hd-pst-table__col-check{width:36px;text-align:center}
			.hd-pst-table__col-badge{width:80px}
			.hd-pst-table__col-count{color:#999;text-align:center}
			.hd-pst-table tbody tr{border-bottom:1px solid #f5f5f5;transition:background .1s}
			.hd-pst-table tbody tr:last-child{border-bottom:none}
			.hd-pst-table tbody tr:hover{background:#f9f9fb}
			.hd-pst-table tbody td{padding:10px 12px;vertical-align:middle}
			.hd-pst-row__term strong{display:block;font-size:13px;color:#1d2327}
			.hd-pst-row__global{display:inline-flex;align-items:center;gap:4px;font-size:11px;color:#b4b9be;margin-top:2px}
			.hd-pst-preview--color{display:inline-block;width:12px;height:12px;border-radius:50%;border:1px solid rgba(0,0,0,.08);vertical-align:middle}
			.hd-pst-preview--image{display:inline-flex;vertical-align:middle}
			.hd-pst-preview--image img{width:12px;height:12px;border-radius:2px;object-fit:cover}
			.hd-pst-type-select{width:100%;max-width:200px}
			.hd-pst-field__hint{color:#b4b9be;font-size:12px;font-style:italic}
			.hd-pst-image-field{display:flex;align-items:center;gap:8px}
			.hd-pst-image-preview{min-width:36px;min-height:36px}
			.hd-pst-image-preview img{border:1px solid #eee;border-radius:4px;width:36px;height:36px;object-fit:cover}
			.hd-pst-field--color .wp-picker-container{display:flex;align-items:center}
			.hd-pst-field--color .wp-picker-container .wp-color-result{margin:0 6px 0 0}
			.hd-pst-badge{font-size:11px;padding:2px 8px;border-radius:10px;background:#f0f0f1;color:#646970;text-transform:capitalize}
		</style>
		<?php
	}

	/**
	 * Print inline JS for type toggle, color picker init, and image upload.
	 */
	private function printPanelScript(): void {
		?>
		<script>
			jQuery(function($){
				var P=$('#hd_swatches_product_data');
				function toggle(r){var t=r.find('.hd-pst-type-select').val();r.find('.hd-pst-field').hide();
					if('color'===t){r.find('.hd-pst-field--color').show();var i=r.find('.hd-pst-field--color .hd-color-picker');i.length&&!i.closest('.wp-picker-container').length&&i.wpColorPicker()}
					else if('image'===t)r.find('.hd-pst-field--image').show();
					else if('label'===t||'radio'===t)r.find('.hd-pst-field--none').show();
					else r.find('.hd-pst-field--global').show()}
				P.find('.hd-pst-row').each(function(){toggle($(this))});
				P.on('change','.hd-pst-type-select',function(){toggle($(this).closest('.hd-pst-row'))});
				P.on('click','.hd-pst-image-upload',function(e){e.preventDefault();var f=$(this).closest('.hd-pst-image-field'),
					m=wp.media({title:'<?php echo esc_js( __( 'Select Swatch Image', 'hd' ) ); ?>',button:{text:'<?php echo esc_js( __( 'Use Image', 'hd' ) ); ?>'},multiple:false,library:{type:'image'}});
					m.on('select',function(){var a=m.state().get('selection').first().toJSON();f.find('.hd-pst-image-id').val(a.id);
						f.find('.hd-pst-image-preview').html('<img src="'+(a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail.url:a.url)+'" />');f.find('.hd-pst-image-remove').show()});m.open()});
				P.on('click','.hd-pst-image-remove',function(e){e.preventDefault();var f=$(this).closest('.hd-pst-image-field');f.find('.hd-pst-image-id').val('');f.find('.hd-pst-image-preview').html('');$(this).hide()});
			});
		</script>
		<?php
	}

	// ── Save ─────────────────────────────────────────

	/**
	 * Save product-level swatch settings.
	 *
	 * @param int $postId Product (post) ID.
	 */
	public function save( int $postId ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC handles nonce for product meta save
		$tabSubmitted = ! empty( $_POST['hd_swatches_archive_configured'] );
		$archiveAttrs = array_map( 'sanitize_text_field', wp_unslash( $_POST['hd_swatches_archive_attrs'] ?? [] ) );
		$rawOverrides = wp_unslash( $_POST['hd_swatches_overrides'] ?? [] );
		$overrides    = [];

		foreach ( $rawOverrides as $taxonomy => $terms ) {
			$taxonomy = sanitize_text_field( $taxonomy );
			foreach ( $terms as $slug => $data ) {
				$slug = sanitize_title( $slug );
				$type = sanitize_text_field( $data['type'] ?? '' );
				if ( ! $type ) {
					continue;
				}

				$overrides[ $taxonomy ][ $slug ] = match ( $type ) {
					'color' => [
						'type'  => 'color',
						'color' => sanitize_hex_color( $data['color'] ?? '' ),
					],
					'image' => [
						'type'  => 'image',
						'image' => absint( $data['image'] ?? 0 ),
					],
					'label' => [ 'type' => 'label' ],
					'radio' => [ 'type' => 'radio' ],
					default => [],
				};
			}
		}

		// P1 fix: Only mark as "configured" when user actually selected attributes.
		// Empty checkboxes + tab submitted = auto-detect mode (not "show nothing").
		$archiveConfigured = $tabSubmitted && ! empty( $archiveAttrs );

		$settings = [
			'archive_configured' => $archiveConfigured,
			'archive_attributes' => $archiveAttrs,
			'overrides'          => $overrides,
		];

		if ( ! $archiveConfigured && empty( $overrides ) ) {
			delete_post_meta( $postId, self::META_KEY );
		} else {
			update_post_meta( $postId, self::META_KEY, $settings );
		}
		// phpcs:enable
	}

	/**
	 * Read product-level settings (static helper for Frontend).
	 *
	 * @param int $productId Product ID.
	 *
	 * @return array
	 */
	public static function getSettings( int $productId ): array {
		return get_post_meta( $productId, self::META_KEY, true ) ?: [];
	}
}
