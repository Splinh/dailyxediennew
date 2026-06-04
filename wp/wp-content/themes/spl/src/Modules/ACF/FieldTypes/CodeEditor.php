<?php
/**
 * ACF Code Editor Field Type.
 *
 * Provides a custom ACF field with a CodeMirror-powered HTML editor.
 * Designed for entering structured code such as JSON-LD Schema,
 * custom script tags, or inline HTML snippets.
 *
 * @package SPL\Modules\ACF
 * @author  HD
 */

namespace SPL\Modules\ACF\FieldTypes;

defined( 'ABSPATH' ) || exit;

class CodeEditor extends \acf_field {

	/**
	 * Supported CodeMirror modes mapped to MIME types.
	 */
	private const MIME_MAP = [
		'htmlmixed'  => 'text/html',
		'javascript' => 'application/javascript',
		'css'        => 'text/css',
		'xml'        => 'application/xml',
	];

	private static bool $inlineScriptAdded = false;

	// --------------------------------------------------

	/**
	 * Initialize the Code Editor field type.
	 */
	public function __construct() {
		$this->name     = 'code_editor';
		$this->label    = esc_html__( 'Code Editor', 'SPL' );
		$this->category = 'content';
		$this->defaults = [
			'default_value' => '',
			'mode'          => 'htmlmixed',
			'rows'          => 12,
			'placeholder'   => '',
		];

		parent::__construct();
	}

	// --------------------------------------------------

	/**
	 * Render field settings in ACF admin.
	 *
	 * @param array $field Field configuration.
	 */
	public function render_field_settings( $field ) {
		acf_render_field_setting(
			$field,
			[
				'label'        => esc_html__( 'Default Value', 'SPL' ),
				'instructions' => esc_html__( 'Appears when creating a new post', 'SPL' ),
				'type'         => 'textarea',
				'name'         => 'default_value',
				'rows'         => 4,
			]
		);

		acf_render_field_setting(
			$field,
			[
				'label'        => esc_html__( 'Editor Mode', 'SPL' ),
				'instructions' => esc_html__( 'Select the CodeMirror syntax highlighting mode', 'SPL' ),
				'type'         => 'select',
				'name'         => 'mode',
				'choices'      => [
					'htmlmixed'  => 'HTML',
					'javascript' => 'JavaScript',
					'css'        => 'CSS',
					'xml'        => 'XML',
				],
			]
		);

		acf_render_field_setting(
			$field,
			[
				'label'        => esc_html__( 'Rows', 'SPL' ),
				'instructions' => esc_html__( 'Sets the textarea height (number of visible rows)', 'SPL' ),
				'type'         => 'number',
				'name'         => 'rows',
				'min'          => 4,
				'max'          => 40,
			]
		);

		acf_render_field_setting(
			$field,
			[
				'label'        => esc_html__( 'Placeholder Text', 'SPL' ),
				'instructions' => esc_html__( 'Appears within the input when empty', 'SPL' ),
				'type'         => 'text',
				'name'         => 'placeholder',
			]
		);
	}

	// --------------------------------------------------

	/**
	 * Enqueue CodeMirror assets for the admin field.
	 */
	public function input_admin_enqueue_scripts(): void {
		$settings = wp_enqueue_code_editor( [ 'type' => 'text/html' ] );

		if ( false === $settings ) {
			return;
		}

		wp_enqueue_script( 'wp-theme-plugin-editor' );
		wp_enqueue_style( 'wp-codemirror' );

		if ( ! self::$inlineScriptAdded ) {
			wp_add_inline_script( 'wp-theme-plugin-editor', self::adminInlineScript() );
			self::$inlineScriptAdded = true;
		}
	}

	// --------------------------------------------------

	/**
	 * Render the field input in admin.
	 *
	 * @param array $field Field configuration.
	 */
	public function render_field( $field ) {
		$fieldId     = esc_attr( $field['id'] );
		$fieldName   = esc_attr( $field['name'] );
		$fieldClass  = esc_attr( $field['class'] );
		$rows        = absint( $field['rows'] ?: 12 );
		$mode        = $field['mode'] ?: 'htmlmixed';
		$placeholder = esc_attr( $field['placeholder'] ?? '' );
		$value       = $field['value'] ?? '';
		$mimeType    = self::MIME_MAP[ $mode ] ?? 'text/html';

		?>
		<div class="acf-code-editor-wrap" data-mode="<?php echo esc_attr( $mimeType ); ?>">
			<textarea
				id="<?php echo $fieldId; ?>"
				class="<?php echo $fieldClass; ?>"
				name="<?php echo $fieldName; ?>"
				rows="<?php echo $rows; ?>"
				placeholder="<?php echo $placeholder; ?>"
			><?php echo esc_textarea( $value ); ?></textarea>
		</div>
		<?php
	}

	private static function adminInlineScript(): string {
		return <<<'JS'
(function($){
	function initCodeEditor(scope) {
		if (typeof wp === 'undefined' || typeof wp.codeEditor === 'undefined') {
			return;
		}

		$(scope).find('.acf-code-editor-wrap textarea').addBack('.acf-code-editor-wrap textarea').each(function(){
			if (this.dataset.cmInit) {
				return;
			}

			var wrap = this.closest('.acf-code-editor-wrap');
			this.dataset.cmInit = '1';
			wp.codeEditor.initialize(this, {
				codemirror: {
					mode: wrap && wrap.dataset.mode ? wrap.dataset.mode : 'text/html',
					lineNumbers: true,
					lineWrapping: true,
					indentUnit: 2,
					tabSize: 2,
					indentWithTabs: true,
					autoCloseBrackets: true,
					autoCloseTags: true,
					matchBrackets: true,
					matchTags: { bothTags: true }
				}
			});
		});
	}

	$(function(){ initCodeEditor(document); });

	if (window.acf && acf.addAction) {
		acf.addAction('ready', initCodeEditor);
		acf.addAction('append', initCodeEditor);
	}
})(jQuery);
JS;
	}

	// --------------------------------------------------

	/**
	 * Sanitize the field value before saving.
	 *
	 * NOTE: This field intentionally stores raw HTML/JS/CSS content.
	 * It is designed for structured code input (JSON-LD Schema, inline
	 * scripts, custom HTML snippets). The ACF admin context already
	 * restricts this to users with `unfiltered_html` capability.
	 * Only whitespace trimming is applied — no wp_kses filtering.
	 *
	 * @param mixed      $value  The field value.
	 * @param int|string $postId The post ID or location identifier.
	 * @param array      $field  The field configuration.
	 *
	 * @return string Trimmed value (raw, unfiltered).
	 */
	public function update_value( $value, $postId, $field ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return trim( $value );
	}

	// --------------------------------------------------

	/**
	 * Format the field value for frontend output.
	 *
	 * @param mixed      $value  The field value.
	 * @param int|string $postId The post ID or location identifier.
	 * @param array      $field  The field configuration.
	 *
	 * @return string
	 */
	public function format_value( $value, $postId, $field ): string {
		if ( empty( $value ) || ! is_string( $value ) ) {
			return '';
		}

		return trim( $value );
	}
}
