<?php
/**
 * Filter Preset Editor — repeater-style builder for filter preset configuration.
 *
 * Hooks into CPT edit screen to render layout/trigger selectors and
 * a repeater section for individual filter items. Saves config as JSON
 * in postmeta via FilterMeta::CONFIG.
 *
 * @package SPL\Modules\WooCommerce\Filter\Admin
 */

namespace SPL\Modules\WooCommerce\Filter\Admin;

use SPL\Modules\WooCommerce\Filter\Enum\AdoptiveMode;
use SPL\Modules\WooCommerce\Filter\FilterMeta;
use SPL\Modules\WooCommerce\Filter\FilterRegistry;

defined( 'ABSPATH' ) || exit;

final class FilterPresetEditor {

	private const NONCE_ACTION = 'hd_filter_preset_save';
	private const NONCE_FIELD  = '_hd_filter_preset_nonce';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'addMetaBoxes' ] );
		add_action( 'save_post_' . FilterMeta::POST_TYPE, [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
	}

	/**
	 * Register meta boxes.
	 */
	public function addMetaBoxes(): void {
		add_meta_box(
			'hd_filter_preset_config',
			__( 'Filter Configuration', 'SPL' ),
			[ $this, 'renderConfigBox' ],
			FilterMeta::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the configuration meta box.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function renderConfigBox( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$layout  = get_post_meta( $post->ID, FilterMeta::LAYOUT, true ) ?: 'vertical';
		$trigger = get_post_meta( $post->ID, FilterMeta::TRIGGER, true ) ?: 'hybrid';
		$raw     = get_post_meta( $post->ID, FilterMeta::CONFIG, true );
		$items   = is_array( $raw ) ? $raw : ( json_decode( (string) $raw, true ) ?: [] );

		// Available filter types from registry
		$filterTypes = FilterRegistry::all();

		// Split taxonomies: non-attribute vs attribute (pa_*)
		$allTaxonomies     = get_taxonomies( [ 'object_type' => [ 'product' ] ], 'objects' );
		$attributeSlugs    = array_map(
			static fn( $a ) => wc_attribute_taxonomy_name( $a->attribute_name ),
			wc_get_attribute_taxonomies()
		);
		$productAttributes = array_intersect_key( $allTaxonomies, array_flip( $attributeSlugs ) );
		$productTaxonomies = array_diff_key( $allTaxonomies, $productAttributes );
		?>
		<div class="hd-preset-editor">

			<?php // ── Global Preset Settings ── ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="hd_preset_layout"><?php esc_html_e( 'Default Layout', 'SPL' ); ?></label></th>
					<td>
						<select id="hd_preset_layout" name="hd_preset_layout">
							<option value="vertical" <?php selected( $layout, 'vertical' ); ?>><?php esc_html_e( 'Vertical (Sidebar Accordion)', 'SPL' ); ?></option>
							<option value="horizontal" <?php selected( $layout, 'horizontal' ); ?>><?php esc_html_e( 'Horizontal (Top Bar Popover)', 'SPL' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hd_preset_trigger"><?php esc_html_e( 'Trigger Mode', 'SPL' ); ?></label></th>
					<td>
						<select id="hd_preset_trigger" name="hd_preset_trigger">
							<option value="auto" <?php selected( $trigger, 'auto' ); ?>><?php esc_html_e( 'Auto (instant AJAX)', 'SPL' ); ?></option>
							<option value="manual" <?php selected( $trigger, 'manual' ); ?>><?php esc_html_e( 'Manual (Apply button)', 'SPL' ); ?></option>
							<option value="hybrid" <?php selected( $trigger, 'hybrid' ); ?>><?php esc_html_e( 'Hybrid (auto desktop, manual mobile)', 'SPL' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<?php // ── Repeater ── ?>
			<h3><?php esc_html_e( 'Filter Items', 'SPL' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Add, configure, and reorder filter items. Drag to reorder.', 'SPL' ); ?></p>

			<div id="hd-filter-repeater" class="hd-filter-repeater">
				<?php
				if ( ! empty( $items ) ) {
					foreach ( $items as $index => $item ) {
						$item = array_merge( FilterMeta::ITEM_DEFAULTS, $item );
						$this->renderRow( $index, $item, $filterTypes, $productTaxonomies, $productAttributes );
					}
				}
				?>
			</div>

			<button type="button" class="button" id="hd-filter-add-row">
				<?php esc_html_e( '+ Add Filter', 'SPL' ); ?>
			</button>

			<?php // ── Template row (hidden, cloned by JS) ── ?>
			<script type="text/html" id="tmpl-hd-filter-row">
				<?php $this->renderRow( '__INDEX__', FilterMeta::ITEM_DEFAULTS, $filterTypes, $productTaxonomies, $productAttributes ); ?>
			</script>

			<script type="text/html" id="tmpl-hd-price-row">
				<?php
				self::renderPriceRow(
					'hd_filters[__INDEX__]',
					'__RIDX__',
					[
						'label' => '',
						'min'   => '',
						'max'   => '',
					]
				);
				?>
			</script>
		</div>
		<?php
	}

	/**
	 * Render a single repeater row.
	 *
	 * @param int|string             $index              Row index.
	 * @param array<string, mixed>   $item               Config values.
	 * @param array<string, string>  $filterTypes        Registered type => class map.
	 * @param array<string, object>  $productTaxonomies  Non-attribute product taxonomies.
	 * @param array<string, object>  $productAttributes  Attribute taxonomies (pa_*).
	 */
	private function renderRow( int|string $index, array $item, array $filterTypes, array $productTaxonomies, array $productAttributes ): void {
		$prefix = "hd_filters[{$index}]";
		?>
		<div class="hd-filter-row" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<div class="hd-filter-row__header">
				<span class="hd-filter-row__drag dashicons dashicons-move" title="<?php esc_attr_e( 'Drag to reorder', 'SPL' ); ?>"></span>
				<strong class="hd-filter-row__title"><?php echo esc_html( $item['label'] ?: __( 'New Filter', 'SPL' ) ); ?></strong>
				<span class="hd-filter-row__type-badge"><?php echo esc_html( $item['type'] ); ?></span>
				<label class="hd-filter-row__enabled">
					<input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[enabled]" value="1" <?php checked( $item['enabled'] ); ?>>
					<?php esc_html_e( 'Enabled', 'SPL' ); ?>
				</label>
				<button type="button" class="hd-filter-row__toggle dashicons dashicons-arrow-down-alt2" title="<?php esc_attr_e( 'Toggle', 'SPL' ); ?>"></button>
				<button type="button" class="hd-filter-row__remove dashicons dashicons-trash" title="<?php esc_attr_e( 'Remove', 'SPL' ); ?>"></button>
			</div>

			<div class="hd-filter-row__body" style="display:none;">
				<table class="form-table">
					<?php // ── Core Fields ── ?>
					<tr>
						<th><label><?php esc_html_e( 'Type', 'SPL' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[type]" class="hd-filter-type-select">
								<?php foreach ( $filterTypes as $typeKey => $typeClass ) : ?>
									<option value="<?php echo esc_attr( $typeKey ); ?>" <?php selected( $item['type'], $typeKey ); ?>>
										<?php echo esc_html( ucwords( str_replace( '_', ' ', $typeKey ) ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Label', 'SPL' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( $item['label'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'ID (slug)', 'SPL' ); ?></label></th>
						<td>
							<input type="text" name="<?php echo esc_attr( $prefix ); ?>[id]" value="<?php echo esc_attr( $item['id'] ); ?>" class="regular-text hd-filter-id-input" required>
							<p class="description"><?php esc_html_e( 'Identifier for URL parameters (e.g., product_cat, pa_color). Auto-generated from taxonomy or type if empty.', 'SPL' ); ?></p>
						</td>
					</tr>

					<?php // ── Taxonomy Selector (non-attribute) ── ?>
					<tr class="hd-cond-taxonomy-only">
						<th><label><?php esc_html_e( 'Taxonomy', 'SPL' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[taxonomy]" class="hd-taxonomy-select">
								<option value=""><?php esc_html_e( '— Select —', 'SPL' ); ?></option>
								<?php
								// Group by type: categories, tags, custom.
								$groups     = [
									__( 'Categories', 'SPL' ) => [ 'product_cat' ],
									__( 'Tags', 'SPL' ) => [ 'product_tag' ],
								];
								$usedKeys   = [ 'product_cat', 'product_tag' ];
								$customKeys = array_keys( array_diff_key( $productTaxonomies, array_flip( $usedKeys ) ) );
								if ( $customKeys ) {
									$groups[ __( 'Custom', 'SPL' ) ] = $customKeys;
								}
								foreach ( $groups as $groupLabel => $keys ) :
									$inGroup = array_intersect_key( $productTaxonomies, array_flip( $keys ) );
									if ( empty( $inGroup ) ) {
										continue;
									}
									?>
									<optgroup label="<?php echo esc_attr( $groupLabel ); ?>">
										<?php foreach ( $inGroup as $tax ) : ?>
											<option value="<?php echo esc_attr( $tax->name ); ?>" <?php selected( $item['taxonomy'], $tax->name ); ?>>
												<?php echo esc_html( $tax->labels->name ); ?>
											</option>
										<?php endforeach; ?>
									</optgroup>
									<?php
								endforeach;
								?>
							</select>
						</td>
					</tr>

					<?php // ── Attribute Selector (pa_*) ── ?>
					<tr class="hd-cond-attribute-only">
						<th><label><?php esc_html_e( 'Attribute', 'SPL' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[taxonomy]" class="hd-attribute-select">
								<option value=""><?php esc_html_e( '— Select —', 'SPL' ); ?></option>
								<?php foreach ( $productAttributes as $tax ) : ?>
									<option value="<?php echo esc_attr( $tax->name ); ?>" <?php selected( $item['taxonomy'], $tax->name ); ?>>
										<?php echo esc_html( $tax->labels->name . ' (' . $tax->name . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<?php // ── Display Type ── ?>
					<tr class="hd-cond-display">
						<th><label><?php esc_html_e( 'Display', 'SPL' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[display]">
								<?php foreach ( [ 'checkbox', 'radio', 'swatch', 'button', 'dropdown', 'hierarchy', 'color_swatch' ] as $d ) : ?>
									<option value="<?php echo esc_attr( $d ); ?>" <?php selected( $item['display'], $d ); ?>>
										<?php echo esc_html( ucwords( str_replace( '_', ' ', $d ) ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<?php // ── Adoptive ── ?>
					<tr>
						<th><label><?php esc_html_e( 'Zero-Count Behavior', 'SPL' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[adoptive]">
								<option value="show" <?php selected( $item['adoptive'], 'show' ); ?>><?php esc_html_e( 'Show', 'SPL' ); ?></option>
								<option value="hide" <?php selected( $item['adoptive'], 'hide' ); ?>><?php esc_html_e( 'Hide', 'SPL' ); ?></option>
								<option value="disable" <?php selected( $item['adoptive'], 'disable' ); ?>><?php esc_html_e( 'Disable', 'SPL' ); ?></option>
							</select>
						</td>
					</tr>

					<?php // ── Orderby ── ?>
					<tr class="hd-cond-has-taxonomy">
						<th><label><?php esc_html_e( 'Order By', 'SPL' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[orderby]">
								<option value="name_asc" <?php selected( $item['orderby'], 'name_asc' ); ?>><?php esc_html_e( 'Name A→Z', 'SPL' ); ?></option>
								<option value="name_desc" <?php selected( $item['orderby'], 'name_desc' ); ?>><?php esc_html_e( 'Name Z→A', 'SPL' ); ?></option>
								<option value="count_desc" <?php selected( $item['orderby'], 'count_desc' ); ?>><?php esc_html_e( 'Count (most first)', 'SPL' ); ?></option>
								<option value="menu_order" <?php selected( $item['orderby'], 'menu_order' ); ?>><?php esc_html_e( 'Menu Order', 'SPL' ); ?></option>
							</select>
						</td>
					</tr>

					<?php // ── UX Options ── ?>
					<tr>
						<th><?php esc_html_e( 'Options', 'SPL' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[more_less]" value="1" <?php checked( $item['more_less'] ); ?>> <?php esc_html_e( 'Show More/Less toggle', 'SPL' ); ?></label>
							<input type="number" name="<?php echo esc_attr( $prefix ); ?>[more_less_count]" value="<?php echo absint( $item['more_less_count'] ); ?>" min="1" max="100" class="small-text">
							<br>
							<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[searchable]" value="1" <?php checked( $item['searchable'] ); ?>> <?php esc_html_e( 'Searchable (inline term search)', 'SPL' ); ?></label>
							<br>
							<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[show_chips]" value="1" <?php checked( $item['show_chips'] ); ?>> <?php esc_html_e( 'Show in active filter chips', 'SPL' ); ?></label>
							<br>
							<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[collapse]" value="1" <?php checked( $item['collapse'] ); ?>> <?php esc_html_e( 'Collapsed by default (vertical)', 'SPL' ); ?></label>
						</td>
					</tr>

					<?php // ── Exclude/Include Terms ── ?>
					<tr class="hd-cond-has-taxonomy">
						<th><label><?php esc_html_e( 'Exclude Terms', 'SPL' ); ?></label></th>
						<td>
							<?php
							$savedSlugs = (array) ( $item['exclude_terms'] ?? [] );
							$savedTax   = $item['taxonomy'] ?? '';
							?>
							<select name="<?php echo esc_attr( $prefix ); ?>[exclude_terms][]" multiple
								class="hd-exclude-terms-select"
								data-taxonomy="<?php echo esc_attr( $savedTax ); ?>"
								data-placeholder="<?php esc_attr_e( 'Select terms to exclude…', 'SPL' ); ?>">
								<?php
								foreach ( $savedSlugs as $slug ) :
									if ( ! $slug ) {
										continue;
									}
									$term  = $savedTax ? get_term_by( 'slug', $slug, $savedTax ) : false;
									$label = $term ? $term->name . ' (' . $slug . ')' : $slug;
									?>
									<option value="<?php echo esc_attr( $slug ); ?>" selected><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<br>
							<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[include_mode]" value="1" <?php checked( $item['include_mode'] ?? false ); ?>> <?php esc_html_e( 'Invert: use as whitelist (include only these terms)', 'SPL' ); ?></label>
						</td>
					</tr>

					<?php // ── Price Range Config ── ?>
					<tr class="hd-cond-price">
						<th><label><?php esc_html_e( 'Price Mode', 'SPL' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[mode]">
								<option value="custom_ranges" <?php selected( $item['mode'], 'custom_ranges' ); ?>><?php esc_html_e( 'Custom Ranges', 'SPL' ); ?></option>
								<option value="slider" <?php selected( $item['mode'], 'slider' ); ?>><?php esc_html_e( 'Slider', 'SPL' ); ?></option>
							</select>
						</td>
					</tr>
					<tr class="hd-cond-slider">
						<th><label><?php esc_html_e( 'Slider Config', 'SPL' ); ?></label></th>
						<td>
							<div style="display: flex; gap: 15px; align-items: center;">
								<label><?php esc_html_e( 'Min Price', 'SPL' ); ?><br>
									<input type="number" name="<?php echo esc_attr( $prefix ); ?>[min]" value="<?php echo absint( $item['min'] ?? 0 ); ?>" min="0" step="10000" style="width: 120px;">
								</label>
								<label><?php esc_html_e( 'Max Price', 'SPL' ); ?><br>
									<input type="number" name="<?php echo esc_attr( $prefix ); ?>[max]" value="<?php echo absint( $item['max'] ?? 10000000 ); ?>" min="0" step="10000" style="width: 120px;">
								</label>
								<label><?php esc_html_e( 'Step', 'SPL' ); ?><br>
									<input type="number" name="<?php echo esc_attr( $prefix ); ?>[step]" value="<?php echo absint( $item['step'] ?? 100000 ); ?>" min="1000" step="1000" style="width: 120px;">
								</label>
							</div>
							<p class="description"><?php esc_html_e( 'Define the fixed price bounds for the slider (e.g., 0 to 10000000).', 'SPL' ); ?></p>
						</td>
					</tr>
					<tr class="hd-cond-price">
						<th><label><?php esc_html_e( 'Price Ranges', 'SPL' ); ?></label></th>
						<td>
							<div class="hd-price-ranges" data-range-count="<?php echo count( $item['ranges'] ); ?>">
								<?php if ( ! empty( $item['ranges'] ) ) : ?>
									<?php foreach ( $item['ranges'] as $ri => $range ) : ?>
										<?php self::renderPriceRow( $prefix, $ri, $range ); ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
							<button type="button" class="button button-small hd-price-range-add">
								<?php esc_html_e( '+ Thêm khoảng giá', 'SPL' ); ?>
							</button>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single price range sub-repeater row.
	 *
	 * @param string     $prefix Parent input prefix (e.g. hd_filters[0]).
	 * @param int|string $ri     Range row index or '__RIDX__' placeholder.
	 * @param array      $range  Range data {label, min, max}.
	 */
	private static function renderPriceRow( string $prefix, int|string $ri, array $range ): void {
		$rPrefix = esc_attr( "{$prefix}[ranges][{$ri}]" );
		$min     = '' !== ( $range['min'] ?? '' ) ? (float) $range['min'] : '';
		$max     = '' !== ( $range['max'] ?? '' ) ? (float) $range['max'] : '';
		?>
		<div class="hd-price-range-row">
			<input type="text" name="<?php echo $rPrefix; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[label]" value="<?php echo esc_attr( $range['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Tên hiển thị', 'SPL' ); ?>" class="hd-price-range-label">
			<input type="number" name="<?php echo $rPrefix; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[min]" value="<?php echo esc_attr( $min ); ?>" placeholder="<?php esc_attr_e( 'Giá từ', 'SPL' ); ?>" min="0" step="1000" class="hd-price-range-min">
			<span class="hd-price-range-sep">—</span>
			<input type="number" name="<?php echo $rPrefix; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>[max]" value="<?php echo esc_attr( $max ); ?>" placeholder="<?php esc_attr_e( 'Giá đến', 'SPL' ); ?>" min="0" step="1000" class="hd-price-range-max">
			<button type="button" class="hd-price-range-remove dashicons dashicons-no-alt" title="<?php esc_attr_e( 'Xoá', 'SPL' ); ?>"></button>
		</div>
		<?php
	}

	/**
	 * Save preset meta on post save.
	 *
	 * @param int $postId Post ID.
	 */
	public function save( int $postId ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $postId ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! wp_verify_nonce( $_POST[ self::NONCE_FIELD ] ?? '', self::NONCE_ACTION ) ) {
			return;
		}

		// Layout + Trigger
		$layout  = sanitize_key( $_POST['hd_preset_layout'] ?? 'vertical' );
		$trigger = sanitize_key( $_POST['hd_preset_trigger'] ?? 'hybrid' );
		update_post_meta( $postId, FilterMeta::LAYOUT, in_array( $layout, [ 'vertical', 'horizontal' ], true ) ? $layout : 'vertical' );
		update_post_meta( $postId, FilterMeta::TRIGGER, in_array( $trigger, [ 'auto', 'manual', 'hybrid' ], true ) ? $trigger : 'hybrid' );

		$rawItems = $_POST['hd_filters'] ?? [];
		$items    = [];
		$seenIds  = [];

		if ( is_array( $rawItems ) ) {
			foreach ( $rawItems as $rawItem ) {
				if ( ! is_array( $rawItem ) || empty( $rawItem['id'] ) ) {
					continue;
				}

				$baseId = sanitize_key( $rawItem['id'] );
				$id     = $baseId;
				$suffix = 1;

				// Prevent duplicate IDs server-side.
				while ( isset( $seenIds[ $id ] ) ) {
					++$suffix;
					$id = $baseId . '_' . $suffix;
				}
				$seenIds[ $id ] = true;

				$item = [
					'id'              => $id,
					'type'            => sanitize_key( $rawItem['type'] ?? 'taxonomy' ),
					'label'           => sanitize_text_field( $rawItem['label'] ?? '' ),
					'taxonomy'        => sanitize_key( $rawItem['taxonomy'] ?? '' ),
					'display'         => sanitize_key( $rawItem['display'] ?? 'checkbox' ),
					'adoptive'        => AdoptiveMode::fromConfig( $rawItem['adoptive'] ?? AdoptiveMode::Show->value )->value,
					'orderby'         => sanitize_key( $rawItem['orderby'] ?? 'name_asc' ),
					'more_less'       => ! empty( $rawItem['more_less'] ),
					'more_less_count' => absint( $rawItem['more_less_count'] ?? 5 ),
					'searchable'      => ! empty( $rawItem['searchable'] ),
					'show_chips'      => ! empty( $rawItem['show_chips'] ),
					'collapse'        => ! empty( $rawItem['collapse'] ),
					'enabled'         => ! empty( $rawItem['enabled'] ),
					'mode'            => sanitize_key( $rawItem['mode'] ?? 'custom_ranges' ),
					'min'             => absint( $rawItem['min'] ?? 0 ),
					'max'             => absint( $rawItem['max'] ?? 10000000 ),
					'step'            => absint( $rawItem['step'] ?? 100000 ),
					'ranges'          => [],
					'include_mode'    => ! empty( $rawItem['include_mode'] ),
					'exclude_terms'   => [],
				];

				// Parse exclude_terms: array from select-multiple or legacy comma-string.
				if ( ! empty( $rawItem['exclude_terms'] ) ) {
					$rawEx = $rawItem['exclude_terms'];
					if ( is_array( $rawEx ) ) {
						$item['exclude_terms'] = array_values( array_filter( array_map( 'sanitize_key', $rawEx ) ) );
					} elseif ( is_string( $rawEx ) ) {
						// Legacy textarea fallback.
						$item['exclude_terms'] = array_values( array_filter( array_map( 'sanitize_key', explode( ',', $rawEx ) ) ) );
					}
				}

				// Parse price ranges from sub-repeater
				if ( ! empty( $rawItem['ranges'] ) && is_array( $rawItem['ranges'] ) ) {
					foreach ( $rawItem['ranges'] as $r ) {
						if ( ! is_array( $r ) || '' === trim( $r['label'] ?? '' ) ) {
							continue;
						}
						$item['ranges'][] = [
							'min'   => (float) ( $r['min'] ?? 0 ),
							'max'   => (float) ( $r['max'] ?? 0 ),
							'label' => sanitize_text_field( $r['label'] ),
						];
					}
				}

				// Validate: ensure min <= max (max=0 means unlimited, skip)
				foreach ( $item['ranges'] as &$range ) {
					if ( $range['max'] > 0 && $range['min'] > $range['max'] ) {
						[ $range['min'], $range['max'] ] = [ $range['max'], $range['min'] ];
					}
				}
				unset( $range );

				$items[] = $item;
			}
		}

		update_post_meta( $postId, FilterMeta::CONFIG, $items );
	}

	/**
	 * Enqueue admin CSS/JS for the preset editor.
	 */
	public function enqueueAssets(): void {
		$screen = get_current_screen();
		if ( ! $screen || FilterMeta::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$assetDir = __DIR__ . '/assets';

		// WC's bundled Select2 (already registered by WooCommerce)
		wp_enqueue_style( 'select2' );
		wp_enqueue_script( 'select2' );

		// Inline CSS
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read.
		$css = file_get_contents( $assetDir . '/editor.css' );
		if ( $css ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted local file.
			printf( '<style id="hd-filter-preset-editor-css">%s</style>', $css );
		}

		// Inline JS (depends on select2 + sortable)
		wp_register_script( 'hd-filter-preset-editor', '', [ 'jquery-ui-sortable', 'select2' ], THEME_VERSION, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read.
		$js = file_get_contents( $assetDir . '/editor.js' );
		if ( $js ) {
			wp_add_inline_script( 'hd-filter-preset-editor', $js );
		}
		wp_enqueue_script( 'hd-filter-preset-editor' );
	}
}
