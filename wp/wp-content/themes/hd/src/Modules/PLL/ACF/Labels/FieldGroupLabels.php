<?php
/**
 * ACF Labels — Field Group Labels.
 *
 * Registers all ACF field group titles and per-field labels
 * (label, instructions, default_value, placeholder, choices, etc.)
 * for translation via pll_register_string().
 *
 * Translates them at render via acf/load_field and acf/load_field_groups.
 *
 * @package HD\Modules\PLL\ACF\Labels
 */

namespace HD\Modules\PLL\ACF\Labels;

use PLL_Format_Util;
use PLL_Settings;

defined( 'ABSPATH' ) || exit;

final class FieldGroupLabels {

	/**
	 * Cached label map.
	 *
	 * @var array|null
	 */
	private ?array $labels = null;

	/**
	 * Register field groups and hook translation filters.
	 * Called from ACFIntegration::onAcfInit().
	 */
	public function onAcfInit(): void {
		$this->registerFieldGroups();

		if ( ! $this->canTranslateLabels() ) {
			return;
		}

		add_filter( 'acf/load_field', [ $this, 'translateFieldLabels' ], 20 );
		add_filter( 'acf/load_field_groups', [ $this, 'translateFieldGroupsLabels' ], 25 );
	}

	/**
	 * Register all field group titles and field labels for string translation.
	 */
	public function registerFieldGroups(): void {
		acf_disable_filter( 'clone' );

		foreach ( acf_get_field_groups() as $fieldGroup ) {
			pll_register_string( 'Title', $fieldGroup['title'], 'ACF' );
			$this->registerFields( acf_get_fields( $fieldGroup ) );
		}

		acf_enable_filter( 'clone' );
	}

	/**
	 * Translate field labels at render.
	 *
	 * @param array $field ACF field array.
	 *
	 * @return array
	 */
	public function translateFieldLabels( array $field ): array {
		if ( ! isset( $field['type'] ) ) {
			return $field;
		}

		$matcher = new PLL_Format_Util();

		foreach ( $this->getFieldLabelsToTranslate() as $fieldType => $fieldLabels ) {
			if ( ! $matcher->matches( $field['type'], $fieldType ) ) {
				continue;
			}

			$field = $this->translateLabelsRecursive( $fieldLabels, $field );
		}

		return $field;
	}

	/**
	 * Translate field group titles.
	 *
	 * @param array[] $posts Field group arrays.
	 *
	 * @return array[]
	 */
	public function translateFieldGroupsLabels( array $posts ): array {
		foreach ( $posts as $key => $post ) {
			$posts[ $key ]['title'] = pll__( $post['title'] );
		}

		return $posts;
	}

	/* ---------- Private helpers ---------------------------------- */

	/**
	 * Check if labels should be translated in the current context.
	 */
	private function canTranslateLabels(): bool {
		global $pagenow;

		if ( \PLL() instanceof PLL_Settings ) {
			return false;
		}

		$acfPostTypes = [ 'acf-field-group', 'acf-post-type', 'acf-taxonomy', 'acf-ui-options-page' ];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], $acfPostTypes, true ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'post.php' === $pagenow && isset( $_GET['post'] ) && in_array( get_post_type( (int) $_GET['post'] ), $acfPostTypes, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Recursively register fields for string translation.
	 *
	 * @param array $fields Array of ACF field definitions.
	 */
	private function registerFields( array $fields ): void {
		$matcher = new PLL_Format_Util();

		foreach ( $fields as $field ) {
			// Recurse into nested structures.
			if ( in_array( $field['type'], [ 'group', 'repeater' ], true ) ) {
				$this->registerFields( $field['sub_fields'] ?? [] );
			} elseif ( 'flexible_content' === $field['type'] && ! empty( $field['layouts'] ) ) {
				foreach ( $field['layouts'] as $layout ) {
					$this->registerFields( $layout['sub_fields'] ?? [] );
				}
			}

			foreach ( $this->getFieldLabelsToTranslate() as $fieldType => $fieldLabels ) {
				if ( ! is_string( $field['type'] ) || ! $matcher->matches( $field['type'], (string) $fieldType ) ) {
					continue;
				}

				$this->registerField( $fieldLabels, $field );
			}

			acf_flush_field_cache( $field );
		}
	}

	/**
	 * Register individual field labels.
	 *
	 * @param array $labels Label keys and names.
	 * @param array $field  ACF field definition.
	 */
	private function registerField( array $labels, array $field ): void {
		$matcher = new PLL_Format_Util();

		foreach ( $labels as $fieldKey => $label ) {
			$filtered = $matcher->filter_list( $field, $fieldKey );

			foreach ( $filtered as $fieldValue ) {
				if ( is_array( $label ) ) {
					if ( is_array( $fieldValue ) ) {
						$this->registerField( $label, $fieldValue );
					}
				} else {
					pll_register_string( $label, $fieldValue, 'ACF', true );
				}
			}
		}
	}

	/**
	 * Recursively translate field labels.
	 *
	 * @param array $labels Label map.
	 * @param array $field  ACF field definition.
	 *
	 * @return array Translated field.
	 */
	private function translateLabelsRecursive( array $labels, array $field ): array {
		$matcher = new PLL_Format_Util();

		if ( isset( $field['default_value'] ) ) {
			$field['pll_default_value'] = $field['default_value'];
		}

		foreach ( $labels as $fieldKey => $subLabels ) {
			$filtered = $matcher->filter_list( $field, $fieldKey );

			foreach ( $filtered as $key => $fieldValue ) {
				if ( is_array( $subLabels ) ) {
					if ( is_array( $fieldValue ) ) {
						$field[ $key ] = $this->translateLabelsRecursive( $subLabels, $fieldValue );
					}
				} elseif ( '' !== $fieldValue ) {
					$field[ $key ] = pll__( $fieldValue );
				}
			}
		}

		return $field;
	}

	/**
	 * Get the map of field labels to translate, by field type.
	 * Supports wildcards (*) for field types and label keys.
	 *
	 * @return array
	 */
	private function getFieldLabelsToTranslate(): array {
		if ( is_array( $this->labels ) ) {
			return $this->labels;
		}

		$labels = [
			'default_value' => _x( 'Default value', 'ACF field setting label', 'hd' ),
			'placeholder'   => _x( 'Placeholder', 'ACF field setting label', 'hd' ),
			'prepend'       => _x( 'Prefix', 'ACF field setting label', 'hd' ),
			'append'        => _x( 'Suffix', 'ACF field setting label', 'hd' ),
			'message'       => _x( 'Message', 'ACF field setting label', 'hd' ),
			'ui_on_text'    => _x( 'ON text', 'ACF field setting label', 'hd' ),
			'ui_off_text'   => _x( 'OFF text', 'ACF field setting label', 'hd' ),
			'choice'        => _x( 'Choice', 'ACF field setting label', 'hd' ),
			'label'         => _x( 'Label', 'ACF field setting label', 'hd' ),
		];

		$this->labels = [
			'*'                => [
				'label'        => $labels['label'],
				'instructions' => _x( 'Instructions', 'ACF field setting label', 'hd' ),
			],
			'button_group'     => [ 'choices' => [ '*' => $labels['choice'] ] ],
			'checkbox'         => [ 'choices' => [ '*' => $labels['choice'] ] ],
			'email'            => [
				'default_value' => $labels['default_value'],
				'placeholder'   => $labels['placeholder'],
				'prepend'       => $labels['prepend'],
				'append'        => $labels['append'],
			],
			'flexible_content' => [
				'layouts' => [ '*' => [ 'label' => $labels['label'] ] ],
			],
			'number'           => [
				'placeholder' => $labels['placeholder'],
				'prepend'     => $labels['prepend'],
				'append'      => $labels['append'],
			],
			'message'          => [ 'message' => $labels['message'] ],
			'password'         => [
				'placeholder' => $labels['placeholder'],
				'prepend'     => $labels['prepend'],
				'append'      => $labels['append'],
			],
			'radio'            => [ 'choices' => [ '*' => $labels['choice'] ] ],
			'range'            => [
				'prepend' => $labels['prepend'],
				'append'  => $labels['append'],
			],
			'select'           => [ 'choices' => [ '*' => $labels['choice'] ] ],
			'text'             => [
				'default_value' => $labels['default_value'],
				'placeholder'   => $labels['placeholder'],
				'prepend'       => $labels['prepend'],
				'append'        => $labels['append'],
			],
			'textarea'         => [
				'default_value' => $labels['default_value'],
				'placeholder'   => $labels['placeholder'],
			],
			'true_false'       => [
				'message'     => $labels['message'],
				'ui_on_text'  => $labels['ui_on_text'],
				'ui_off_text' => $labels['ui_off_text'],
			],
			'url'              => [
				'default_value' => $labels['default_value'],
				'placeholder'   => $labels['placeholder'],
			],
			'wysiwyg'          => [
				'default_value' => $labels['default_value'],
			],
		];

		/** @var array $filtered */
		$filtered = apply_filters( 'pll_acf_field_labels_to_translate', $this->labels );
		if ( is_array( $filtered ) ) {
			$this->labels = $filtered;
		}

		return $this->labels;
	}
}
