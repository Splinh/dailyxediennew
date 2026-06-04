<?php
/**
 * ACF — Per-Field Translation Settings.
 *
 * Adds a "Translations" dropdown to each non-layout ACF field,
 * letting users control copy/sync/translate behavior per field.
 *
 * @package HD\Modules\PLL\ACF
 */

namespace HD\Modules\PLL\ACF;

defined( 'ABSPATH' ) || exit;

final class FieldSettings {

	/**
	 * Register the settings hook for each non-layout field type.
	 * Called from ACFIntegration::onAcfInit().
	 */
	public function onAcfInit(): void {
		foreach ( acf_get_field_types() as $type ) {
			if ( 'layout' !== $type->category ) {
				add_action( "acf/render_field_settings/type={$type->name}", [ $this, 'renderFieldSettings' ] );
			}
		}
	}

	/**
	 * Render the "Translations" dropdown setting for a field.
	 *
	 * @param array $field ACF field definition.
	 */
	public function renderFieldSettings( array $field ): void {
		// Hide when field group uses Language location rule.
		$fieldGroup = LocationLanguage::getFieldGroupFromField( $field );
		if ( ! empty( $fieldGroup ) && LocationLanguage::hasLanguageLocationRule( $fieldGroup ) ) {
			return;
		}

		$choices = [
			'ignore'    => __( 'Ignore', 'hd' ),
			'copy_once' => __( 'Copy once', 'hd' ),
			'sync'      => __( 'Synchronize', 'hd' ),
		];

		$default = null;

		switch ( $field['type'] ) {
			case 'text':
			case 'textarea':
			case 'wysiwyg':
				// Intentional fall-through.

			case 'email':
			case 'oembed':
			case 'url':
				$choices = array_merge(
					array_slice( $choices, 0, 2 ),
					[
						'translate'      => __( 'Translate', 'hd' ),
						'translate_once' => __( 'Translate once', 'hd' ),
					],
					array_slice( $choices, -1 )
				);

				$default = 'translate';
				break;
		}

		// Non-text fields: default to first available choice.
		$default ??= array_key_first( $choices );

		$instructions =
			'<details>' .
				'<summary style="cursor: pointer; outline: none; margin-bottom: 5px;">' . esc_html__( 'View translation rules', 'hd' ) . '</summary>' .
				'<div style="line-height: 1.6;">' .
				'<strong>' . esc_html__( 'Ignore:', 'hd' ) . '</strong> ' . esc_html__( 'Fields are completely independent. No data is copied or synced.', 'hd' ) . '<br>' .
				'<strong>' . esc_html__( 'Copy once:', 'hd' ) . '</strong> ' . esc_html__( 'Value is copied only once when creating a new translation.', 'hd' ) . '<br>' .
				'<strong>' . esc_html__( 'Synchronize:', 'hd' ) . '</strong> ' . esc_html__( 'Value is continuously kept identical across all translations.', 'hd' ) . '<br>' .
				'<strong>' . esc_html__( 'Translate:', 'hd' ) . '</strong> ' . esc_html__( 'Value can be translated. Structural fields (e.g., Repeater rows) are synchronized.', 'hd' ) . '<br>' .
				'<strong>' . esc_html__( 'Translate once:', 'hd' ) . '</strong> ' . esc_html__( 'Value is copied initially as a template, then translated independently.', 'hd' ) .
				'</div>' .
			'</details>';

		acf_render_field_setting(
			$field,
			[
				'label'         => __( 'Translations', 'hd' ),
				'instructions'  => $instructions,
				'name'          => 'translations',
				'type'          => 'select',
				'choices'       => $choices,
				'default_value' => $default,
			],
			false
		);
	}
}
