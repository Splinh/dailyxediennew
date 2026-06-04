<?php
/**
 * Swatches Admin — term meta form & column preview.
 *
 * Hooks into WC product attribute taxonomy edit screens to add:
 * - Swatch type selector (color / image / label)
 * - Color picker field + optional dual color (gradient)
 * - Image upload field (stores attachment ID)
 * - Tooltip settings (text / image / disabled)
 * - Admin column preview (color circle / gradient / thumbnail)
 *
 * Meta keys defined in SwatchMeta (single source of truth).
 *
 * @package HD\Modules\WooCommerce\Swatches\Admin
 */

namespace HD\Modules\WooCommerce\Swatches\Admin;

use HD\Core\Helper;
use HD\Modules\WooCommerce\Swatches\Enum\SwatchType;
use HD\Modules\WooCommerce\Swatches\SwatchMeta;

defined( 'ABSPATH' ) || exit;

final class SwatchesAdmin {
	private const ADMIN_SCRIPT_HANDLE = 'hd-wc-swatches-admin';

	/**
	 * Register admin hooks for all WC attribute taxonomies.
	 */
	public function register(): void {
		add_action( 'admin_init', [ $this, 'hookAttributeTaxonomies' ] );
	}

	/**
	 * Hook into each WC product attribute taxonomy.
	 */
	public function hookAttributeTaxonomies(): void {
		$taxonomies = wc_get_attribute_taxonomy_names();

		foreach ( $taxonomies as $taxonomy ) {
			// Term edit form fields
			add_action( "{$taxonomy}_add_form_fields", [ $this, 'addFormFields' ] );
			add_action( "{$taxonomy}_edit_form_fields", [ $this, 'editFormFields' ], 10, 2 );

			// Save term meta
			add_action( "created_{$taxonomy}", [ $this, 'saveMeta' ] );
			add_action( "edited_{$taxonomy}", [ $this, 'saveMeta' ] );

			// Admin column preview
			add_filter( "manage_edit-{$taxonomy}_columns", [ self::class, 'addPreviewColumn' ] );
			add_filter( "manage_{$taxonomy}_custom_column", [ $this, 'renderPreviewColumn' ], 10, 3 );
		}

		// Enqueue admin assets on term edit screens
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
	}

	// ── Form Fields ──────────────────────────────────

	/**
	 * Add form fields (new term screen).
	 */
	public function addFormFields(): void {
		$this->renderFormFields( 'add' );
	}

	/**
	 * Edit form fields (existing term screen).
	 *
	 * @param \WP_Term $term
	 */
	public function editFormFields( \WP_Term $term ): void {
		$this->renderFormFields( 'edit', SwatchMeta::getData( $term->term_id ) );
	}

	/**
	 * Render swatch form fields for both add/edit screens.
	 *
	 * Eliminates duplication between add (div-wrapped) and edit (tr-wrapped) layouts.
	 * WP uses different markup structures for each context.
	 *
	 * @param string                                $context 'add' or 'edit'.
	 * @param array{type: string, color: string, image: int, is_dual: bool, secondary_color: string, tooltip_type: string, tooltip_text: string, tooltip_image: int}|null $data Existing term data (edit only).
	 */
	private function renderFormFields( string $context, ?array $data = null ): void {
		$isEdit = 'edit' === $context;
		$data ??= [
			'type'            => '',
			'color'           => '',
			'image'           => 0,
			'is_dual'         => false,
			'secondary_color' => '',
			'tooltip_type'    => 'text',
			'tooltip_text'    => '',
			'tooltip_image'   => 0,
		];
		$type = SwatchType::fromRaw( $data['type'] ?? '' );

		// ── Type selector ──
		$typeOptions = SwatchType::labelOptions();
		$this->renderRow(
			$context,
			__( 'Swatch Type', 'hd' ),
			'',
			'',
			fn() => self::renderSelect( 'hd_swatch_type', $typeOptions, $type->value, 'hd-swatch-type' ),
		);

		// ── Color picker ──
		$colorHidden = SwatchType::Color !== $type ? 'display:none' : '';
		$this->renderRow(
			$context,
			__( 'Swatch Color', 'hd' ),
			'hd-swatch-field--color',
			$colorHidden,
			fn() => printf( '<input type="text" name="hd_swatch_color" class="hd-color-picker" value="%s" />', esc_attr( $data['color'] ) ),
		);

		// ── Dual color toggle ──
		$this->renderRow(
			$context,
			__( 'Dual Color', 'hd' ),
			'hd-swatch-field--color',
			$colorHidden,
			fn() => printf(
				'<label><input type="checkbox" name="hd_swatch_is_dual" value="1" class="hd-dual-toggle" %s /> %s</label>',
				checked( $data['is_dual'], true, false ),
				esc_html__( 'Enable gradient (2-tone)', 'hd' )
			),
		);

		// ── Secondary color ──
		$dualHidden = ( SwatchType::Color !== $type || ! $data['is_dual'] ) ? 'display:none' : '';
		$this->renderRow(
			$context,
			__( 'Secondary Color', 'hd' ),
			'hd-swatch-field--dual',
			$dualHidden,
			fn() => printf( '<input type="text" name="hd_swatch_secondary_color" class="hd-color-picker" value="%s" />', esc_attr( $data['secondary_color'] ) ),
		);

		// ── Image upload ──
		$imageHidden = SwatchType::Image !== $type ? 'display:none' : '';
		$this->renderRow(
			$context,
			__( 'Swatch Image', 'hd' ),
			'hd-swatch-field--image',
			$imageHidden,
			fn() => $this->renderImageField( 'hd_swatch_image', $data['image'] ),
		);

		// ── Tooltip type ──
		$tooltipHidden  = ! $type->isConfigured() ? 'display:none' : '';
		$tooltipOptions = [
			'text'  => __( 'Text (term name)', 'hd' ),
			'image' => __( 'Image', 'hd' ),
			'no'    => __( 'Disabled', 'hd' ),
		];
		$this->renderRow(
			$context,
			__( 'Tooltip', 'hd' ),
			'hd-swatch-field--tooltip',
			$tooltipHidden,
			fn() => self::renderSelect( 'hd_swatch_tooltip_type', $tooltipOptions, $data['tooltip_type'], 'hd-tooltip-type' ),
		);

		// ── Tooltip text (multi-statement — closure) ──
		$tooltipTextHidden = ( 'text' !== $data['tooltip_type'] || ! $type->isConfigured() ) ? 'display:none' : '';
		$this->renderRow(
			$context,
			__( 'Tooltip Text', 'hd' ),
			'hd-swatch-field--tooltip-text',
			$tooltipTextHidden,
			function () use ( $data ) {
				printf( '<input type="text" name="hd_swatch_tooltip_text" value="%s" />', esc_attr( $data['tooltip_text'] ) );
				echo '<p class="description">' . esc_html__( 'Leave empty to use the term name.', 'hd' ) . '</p>';
			},
		);

		// ── Tooltip image ──
		$tooltipImageHidden = ( 'image' !== $data['tooltip_type'] || ! $type->isConfigured() ) ? 'display:none' : '';
		$this->renderRow(
			$context,
			__( 'Tooltip Image', 'hd' ),
			'hd-swatch-field--tooltip-image',
			$tooltipImageHidden,
			fn() => $this->renderImageField( 'hd_swatch_tooltip_image', $data['tooltip_image'] ),
		);
	}

	/**
	 * Render a form row with correct WP markup for add/edit context.
	 *
	 * @param string   $context   'add' or 'edit'.
	 * @param string   $label     Field label text.
	 * @param string   $cssClass  Additional CSS class for conditional show/hide.
	 * @param string   $style     Inline style (e.g. 'display:none').
	 * @param callable $callback  Renders the field content.
	 */
	private function renderRow( string $context, string $label, string $cssClass, string $style, callable $callback ): void {
		$styleAttr = $style ? ' style="' . esc_attr( $style ) . '"' : '';

		if ( 'add' === $context ) {
			$classes = 'form-field' . ( $cssClass ? ' hd-swatch-field ' . $cssClass : '' );
			echo '<div class="' . esc_attr( $classes ) . '"' . $styleAttr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<label>' . esc_html( $label ) . '</label>';
			$callback();
			echo '</div>';
		} else {
			$classes = 'form-field' . ( $cssClass ? ' hd-swatch-field ' . $cssClass : '' );
			echo '<tr class="' . esc_attr( $classes ) . '"' . $styleAttr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<th><label>' . esc_html( $label ) . '</label></th>';
			echo '<td>';
			$callback();
			echo '</td></tr>';
		}
	}

	/**
	 * Render image upload field (shared between swatch image and tooltip image).
	 *
	 * @param string $inputName Input field name attribute.
	 * @param int    $imageId   Current attachment ID (0 if none).
	 */
	private function renderImageField( string $inputName, int $imageId ): void {
		?>
		<div class="hd-swatch-image-field">
			<input type="hidden" name="<?php echo esc_attr( $inputName ); ?>" class="hd-swatch-image-id" value="<?php echo esc_attr( $imageId ); ?>" />
			<div class="hd-swatch-image-preview">
				<?php
				if ( $imageId ) {
					echo Helper::attachmentImageHTML( $imageId, [ 60, 60 ] );
				}
				?>
			</div>
			<button type="button" class="button hd-swatch-image-upload"><?php esc_html_e( 'Upload Image', 'hd' ); ?></button>
			<button type="button" class="button hd-swatch-image-remove" <?php echo ! $imageId ? 'style="display:none"' : ''; ?>><?php esc_html_e( 'Remove', 'hd' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render a select dropdown (shared for swatch type and tooltip type).
	 *
	 * @param string               $name     Input name attribute.
	 * @param array<string,string> $options  value => label pairs.
	 * @param string               $current  Currently selected value.
	 * @param string               $cssClass CSS class for the select element.
	 */
	private static function renderSelect( string $name, array $options, string $current, string $cssClass = '' ): void {
		$classAttr = $cssClass ? ' class="' . esc_attr( $cssClass ) . '"' : '';
		$idAttr    = $cssClass ? ' id="' . esc_attr( $cssClass ) . '"' : '';

		echo '<select name="' . esc_attr( $name ) . '"' . $idAttr . $classAttr . '>';
		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	// ── Save ─────────────────────────────────────────

	/**
	 * Save swatch term meta.
	 *
	 * @param int $termId
	 */
	public function saveMeta( int $termId ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WP handles nonce for term edit
		$type = SwatchType::fromRaw( wp_unslash( $_POST['hd_swatch_type'] ?? '' ) );

		update_term_meta( $termId, SwatchMeta::TYPE, $type->value );

		match ( $type ) {
			SwatchType::Color => update_term_meta( $termId, SwatchMeta::COLOR, sanitize_hex_color( wp_unslash( $_POST['hd_swatch_color'] ?? '' ) ) ),
			SwatchType::Image => self::saveAttachmentMeta( $termId, SwatchMeta::IMAGE, self::sanitizeAttachmentId( $_POST['hd_swatch_image'] ?? 0 ) ),
			default => null,
		};

		// Dual color (only when type=color)
		if ( SwatchType::Color === $type ) {
			$isDual = ! empty( $_POST['hd_swatch_is_dual'] ) ? '1' : '';
			update_term_meta( $termId, SwatchMeta::IS_DUAL, $isDual );
			update_term_meta( $termId, SwatchMeta::SECONDARY_COLOR, sanitize_hex_color( wp_unslash( $_POST['hd_swatch_secondary_color'] ?? '' ) ) );
		} else {
			delete_term_meta( $termId, SwatchMeta::IS_DUAL );
			delete_term_meta( $termId, SwatchMeta::SECONDARY_COLOR );
		}

		// Tooltip (for any swatch type)
		if ( $type->isConfigured() ) {
			$tooltipType = sanitize_text_field( wp_unslash( $_POST['hd_swatch_tooltip_type'] ?? 'text' ) );
			update_term_meta( $termId, SwatchMeta::TOOLTIP_TYPE, $tooltipType );
			update_term_meta( $termId, SwatchMeta::TOOLTIP_TEXT, sanitize_text_field( wp_unslash( $_POST['hd_swatch_tooltip_text'] ?? '' ) ) );
			self::saveAttachmentMeta( $termId, SwatchMeta::TOOLTIP_IMAGE, self::sanitizeAttachmentId( $_POST['hd_swatch_tooltip_image'] ?? 0 ) );
		} else {
			delete_term_meta( $termId, SwatchMeta::TOOLTIP_TYPE );
			delete_term_meta( $termId, SwatchMeta::TOOLTIP_TEXT );
			delete_term_meta( $termId, SwatchMeta::TOOLTIP_IMAGE );
		}

		// Clean up unused meta
		if ( SwatchType::Color !== $type ) {
			delete_term_meta( $termId, SwatchMeta::COLOR );
		}
		if ( SwatchType::Image !== $type ) {
			delete_term_meta( $termId, SwatchMeta::IMAGE );
		}
		// phpcs:enable
	}

	private static function saveAttachmentMeta( int $termId, string $metaKey, int $attachmentId ): void {
		if ( $attachmentId > 0 ) {
			update_term_meta( $termId, $metaKey, $attachmentId );
			return;
		}

		delete_term_meta( $termId, $metaKey );
	}

	private static function sanitizeAttachmentId( mixed $value ): int {
		$attachmentId = absint( $value );
		if ( $attachmentId <= 0 || 'attachment' !== get_post_type( $attachmentId ) ) {
			return 0;
		}

		if ( function_exists( 'wp_attachment_is_image' ) && ! wp_attachment_is_image( $attachmentId ) ) {
			return 0;
		}

		return $attachmentId;
	}

	// ── Admin Column ────────────────────────────────

	/**
	 * Add swatch preview column to term list table.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public static function addPreviewColumn( array $columns ): array {
		return [
			'cb'                => $columns['cb'],
			'hd-swatch-preview' => '',
		] + array_diff_key( $columns, [ 'cb' => '' ] );
	}

	/**
	 * Render swatch preview in admin column.
	 *
	 * @param string $output
	 * @param string $column
	 * @param int    $termId
	 *
	 * @return string
	 */
	public function renderPreviewColumn( string $output, string $column, int $termId ): string {
		if ( 'hd-swatch-preview' !== $column ) {
			return $output;
		}

		$data = SwatchMeta::getData( $termId );
		$type = SwatchType::fromRaw( $data['type'] );

		if ( SwatchType::Color === $type ) {
			$primary = esc_attr( sanitize_hex_color( $data['color'] ) );

			if ( $data['is_dual'] ) {
				$secondary = esc_attr( sanitize_hex_color( $data['secondary_color'] ) );

				return sprintf(
					'<div class="hd-swatch-preview" style="background:linear-gradient(135deg,%s 50%%,%s 50%%)"></div>',
					$secondary,
					$primary
				);
			}

			return sprintf( '<div class="hd-swatch-preview" style="background:%s;"></div>', $primary );
		}

		return match ( $type ) {
			SwatchType::Image => Helper::attachmentImageHTML( $data['image'], [ 30, 30 ] ),
			SwatchType::Label => '<span class="hd-swatch-preview hd-swatch-preview--label">Aa</span>',
			default => '',
		};
	}

	// ── Admin Assets ────────────────────────────────

	/**
	 * Enqueue admin scripts for swatch management.
	 *
	 * @param string $hookSuffix
	 */
	public function enqueueAdminAssets( string $hookSuffix ): void {
		if ( ! in_array( $hookSuffix, [ 'edit-tags.php', 'term.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! str_starts_with( $screen->taxonomy ?? '', 'pa_' ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_script( 'wp-color-picker' );

		// Inline admin JS - small enough to not warrant a separate file.
		wp_register_script( self::ADMIN_SCRIPT_HANDLE, false, [ 'jquery', 'wp-color-picker' ], null, true );
		wp_enqueue_script( self::ADMIN_SCRIPT_HANDLE );
		wp_add_inline_script( self::ADMIN_SCRIPT_HANDLE, $this->adminScript() );
	}

	/**
	 * Inline admin script for swatch type toggle, color picker, and image upload.
	 */
	private function adminScript(): string {
		$selectTitle = wp_json_encode( __( 'Select Swatch Image', 'hd' ) ) ?: '""';
		$useImage    = wp_json_encode( __( 'Use Image', 'hd' ) ) ?: '""';

		return <<<JS
			jQuery(function($) {
				// ── Type selector toggle ──
				function toggleSwatchFields() {
					const type = $('#hd-swatch-type').val();
					$('.hd-swatch-field').hide();
					if (type) {
						$('.hd-swatch-field--' + type).show();
						$('.hd-swatch-field--tooltip').show();
						toggleTooltipFields();
						toggleDualColor();
					}
				}

				function toggleDualColor() {
					if ( $('.hd-dual-toggle').is(':checked') ) {
						$('.hd-swatch-field--dual').show();
					} else {
						$('.hd-swatch-field--dual').hide();
					}
				}

				function toggleTooltipFields() {
					const tt = $('.hd-tooltip-type').val();
					$('.hd-swatch-field--tooltip-text, .hd-swatch-field--tooltip-image').hide();
					if ('text' === tt) { $('.hd-swatch-field--tooltip-text').show(); }
					if ('image' === tt) { $('.hd-swatch-field--tooltip-image').show(); }
				}

				$(document).on('change', '#hd-swatch-type', toggleSwatchFields);
				$(document).on('change', '.hd-dual-toggle', toggleDualColor);
				$(document).on('change', '.hd-tooltip-type', toggleTooltipFields);
				toggleSwatchFields();

				// ── Color picker ──
				$('.hd-color-picker').wpColorPicker();

				// ── Image upload ──
				$(document).on('click', '.hd-swatch-image-upload', function(e) {
					e.preventDefault();
					const $field = $(this).closest('.hd-swatch-image-field');

					const frame = wp.media({
						title: {$selectTitle},
						button: { text: {$useImage} },
						multiple: false,
						library: { type: 'image' }
					});

					frame.on('select', function() {
						const attachment = frame.state().get('selection').first().toJSON();
						$field.find('.hd-swatch-image-id').val(attachment.id);
						$field.find('.hd-swatch-image-preview').html(
							'<img src="' + (attachment.sizes?.thumbnail?.url || attachment.url) + '" width="60" height="60" />'
						);
						$field.find('.hd-swatch-image-remove').show();
					});

					frame.open();
				});

				$(document).on('click', '.hd-swatch-image-remove', function(e) {
					e.preventDefault();
					const $field = $(this).closest('.hd-swatch-image-field');
					$field.find('.hd-swatch-image-id').val('');
					$field.find('.hd-swatch-image-preview').html('');
					$(this).hide();
				});

				// Re-init on "Add New Term" ajax success (WP resets the form)
				$(document).ajaxSuccess(function(event, xhr, settings) {
					if (settings.data && settings.data.indexOf('action=add-tag') !== -1) {
						$('#hd-swatch-type').val('');
						$('.hd-dual-toggle').prop('checked', false);
						$('.hd-tooltip-type').val('text');
						toggleSwatchFields();
						$('.hd-color-picker').val('').wpColorPicker('color', '');
						$('.hd-swatch-image-id').val('');
						$('.hd-swatch-image-preview').html('');
						$('.hd-swatch-image-remove').hide();
						$('input[name=hd_swatch_tooltip_text]').val('');
					}
				});
			});
JS;
	}
}
