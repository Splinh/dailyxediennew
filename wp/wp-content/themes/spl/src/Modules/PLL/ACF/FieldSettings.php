<?php
/**
 * ACF — Per-Field Translation Settings.
 *
 * Adds a "Translations" dropdown to each non-layout ACF field,
 * letting users control copy/sync/translate behavior per field.
 *
 * @package SPL\Modules\PLL\ACF
 */

namespace SPL\Modules\PLL\ACF;

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
			'ignore'    => __( 'Ignore', 'SPL' ),
			'copy_once' => __( 'Copy once', 'SPL' ),
			'sync'      => __( 'Synchronize', 'SPL' ),
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
						'translate'      => __( 'Translate', 'SPL' ),
						'translate_once' => __( 'Translate once', 'SPL' ),
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
				'<summary style="cursor: pointer; outline: none; margin-bottom: 5px;">' . esc_html__( 'View translation rules', 'SPL' ) . '</summary>' .
				'<div style="line-height: 1.6;">' .
				'<strong>' . esc_html__( 'Ignore:', 'SPL' ) . '</strong> ' . esc_html__( 'Fields are completely independent. No data is copied or synced.', 'SPL' ) . '<br>' .
				'<strong>' . esc_html__( 'Copy once:', 'SPL' ) . '</strong> ' . esc_html__( 'Value is copied only once when creating a new translation.', 'SPL' ) . '<br>' .
				'<strong>' . esc_html__( 'Synchronize:', 'SPL' ) . '</strong> ' . esc_html__( 'Value is continuously kept identical across all translations.', 'SPL' ) . '<br>' .
				'<strong>' . esc_html__( 'Translate:', 'SPL' ) . '</strong> ' . esc_html__( 'Value can be translated. Structural fields (e.g., Repeater rows) are synchronized.', 'SPL' ) . '<br>' .
				'<strong>' . esc_html__( 'Translate once:', 'SPL' ) . '</strong> ' . esc_html__( 'Value is copied initially as a template, then translated independently.', 'SPL' ) .
				'</div>' .
			'</details>';

		acf_render_field_setting(
			$field,
			[
				'label'         => __( 'Translations', 'SPL' ),
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
