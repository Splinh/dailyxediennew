<?php
/**
 * ACF Options Pages — Admin Orchestrator.
 *
 * Adds language translation UI to ACF Options Pages:
 * - Language switcher bar in the Publish metabox sidebar
 * - Slide-over panel for editing translations
 * - AJAX endpoints for form rendering, saving, and copying
 *
 * @package SPL\Modules\PLL\ACF\Options
 */

namespace SPL\Modules\PLL\ACF\Options;

use PLL_Language;

defined( 'ABSPATH' ) || exit;

final class OptionsAdmin {

	private const NONCE_ACTION = 'pll_acf_options_translate';

	/**
	 * Current options page definition (set on admin_load).
	 *
	 * @var array|null
	 */
	private ?array $currentPage = null;

	/**
	 * Register hooks. Called from ACFIntegration::onAcfInit().
	 */
	public function boot(): void {
		if ( ! is_admin() ) {
			return;
		}

		// AJAX endpoints (always register, regardless of current page).
		add_action( 'wp_ajax_hd_pll_acf_options_form', [ $this, 'ajaxRenderForm' ] );
		add_action( 'wp_ajax_hd_pll_acf_options_save', [ $this, 'ajaxSave' ] );
		add_action( 'wp_ajax_hd_pll_acf_options_copy', [ $this, 'ajaxCopy' ] );
		add_action( 'wp_ajax_hd_pll_acf_options_remove', [ $this, 'ajaxRemove' ] );

		// Detect current options page and inject UI.
		add_action( 'acf/options_page/submitbox_before_major_actions', [ $this, 'renderLanguageSwitcher' ] );
		add_action( 'admin_footer', [ $this, 'maybeRenderSlideOver' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybeEnqueueAssets' ], 20 );
	}

	/* ---------- UI Rendering ---------------------------------------- */

	/**
	 * Render language switcher inside the Publish metabox.
	 *
	 * @param array $page Current options page definition.
	 */
	public function renderLanguageSwitcher( array $page ): void {
		$languages = $this->getLanguages();
		if ( count( $languages ) < 2 ) {
			return;
		}

		$this->currentPage = $page;
		$defaultSlug       = pll_default_language();
		$postId            = $page['post_id'];
		$statusMap         = $this->buildStatusMap( $postId, $languages );

		// Context hint: show which language this native page applies to.
		$currentLang = PLL()->model->get_language( pll_current_language() );
		if ( $currentLang instanceof PLL_Language ) {
			printf(
				'<p class="misc-pub-section" style="color:#646970;font-size:12px;">%s</p>',
				esc_html(
					sprintf(
						/* translators: %s: current language name */
						__( 'You are editing options for: %s', 'SPL' ),
						$currentLang->name
					)
				)
			);
		}

		include __DIR__ . '/views/language-switcher.php';
	}

	/**
	 * Render slide-over panel shell in admin_footer (only on options pages).
	 */
	public function maybeRenderSlideOver(): void {
		if ( empty( $this->currentPage ) ) {
			return;
		}

		$defaultLang     = $this->getDefaultLanguage();
		$defaultLangName = $defaultLang ? $defaultLang->name : '';

		include __DIR__ . '/views/slide-over.php';
	}

	/**
	 * Enqueue JS/CSS on options pages.
	 */
	public function maybeEnqueueAssets(): void {
		if ( ! $this->isOptionsPageScreen() ) {
			return;
		}

		// Ensure acf-input is available (it should be on options pages).
		if ( ! wp_script_is( 'acf-input', 'registered' ) ) {
			return;
		}

		$assetDir = __DIR__ . '/assets';

		// Inline CSS — read from file, print directly (avoids handle dependency issues).
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read.
		$css = file_get_contents( $assetDir . '/options-translate.css' );
		if ( $css ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted local file.
			printf( '<style id="pll-acf-options-translate-css">%s</style>', $css );
		}

		// Inline JS — register empty handle for localization, inject file content.
		wp_register_script( 'pll-acf-options-translate', '', [ 'acf-input', 'jquery', 'wp-util' ], THEME_VERSION, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read.
		$js = file_get_contents( $assetDir . '/options-translate.js' );
		if ( $js ) {
			wp_add_inline_script( 'pll-acf-options-translate', $js );
		}
		wp_enqueue_script( 'pll-acf-options-translate' );

		wp_localize_script(
			'pll-acf-options-translate',
			'pllAcfOptions',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'i18n'    => [
					'translateTo'    => __( 'Translate to %s', 'SPL' ),
					'saving'         => __( 'Saving…', 'SPL' ),
					'copying'        => __( 'Copying…', 'SPL' ),
					'removing'       => __( 'Removing…', 'SPL' ),
					'saved'          => __( 'Saved!', 'SPL' ),
					'copied'         => __( 'Copied!', 'SPL' ),
					'removed'        => __( 'Removed!', 'SPL' ),
					'confirmRemove'  => __( 'Remove this translation? Fields will fall back to the default language.', 'SPL' ),
					'hasTranslation' => __( 'Has translation', 'SPL' ),
					'noTranslation'  => __( 'No translation', 'SPL' ),
					'error'          => __( 'An error occurred. Please try again.', 'SPL' ),
				],
			]
		);
	}

	/* ---------- AJAX Endpoints --------------------------------------- */

	/**
	 * AJAX: Render ACF form fields for target language.
	 */
	public function ajaxRenderForm(): void {
		$this->verifyAjaxNonce();

		[ $postId, $lang, $menuSlug ] = $this->getAjaxParams();

		// Build language-specific post_id.
		$defaultLang = pll_default_language();
		$langPostId  = ( $lang === $defaultLang ) ? $postId : "{$postId}_{$lang}";

		// Get field groups assigned to this options page.
		$fieldGroups = acf_get_field_groups( [ 'options_page' => $menuSlug ] );
		if ( empty( $fieldGroups ) ) {
			wp_send_json_error( [ 'message' => __( 'No field groups found.', 'SPL' ) ] );
		}

		// Render fields to buffer.
		ob_start();

		// ACF form data (hidden inputs for save context).
		acf_form_data(
			[
				'screen'  => 'options',
				'post_id' => $langPostId,
			]
		);

		foreach ( $fieldGroups as $fieldGroup ) {
			$fields = acf_get_fields( $fieldGroup );
			if ( empty( $fields ) ) {
				continue;
			}

			echo '<div class="acf-postbox" style="margin-bottom: 16px;">';
			echo '<h3 class="hndle" style="padding: 10px 12px; margin: 0; border-bottom: 1px solid #dcdcde; font-size: 14px;">';
			echo acf_esc_html( acf_get_field_group_title( $fieldGroup ) );
			echo '</h3>';
			echo '<div class="inside acf-fields" style="padding: 0;">';
			acf_render_fields( $fields, $langPostId, 'div', $fieldGroup['instruction_placement'] );
			echo '</div></div>';
		}

		$html = ob_get_clean();

		wp_send_json_success( [ 'html' => $html ] );
	}

	/**
	 * AJAX: Save ACF form data for target language.
	 */
	public function ajaxSave(): void {
		$this->verifyAjaxNonce();

		[ $postId, $lang, $menuSlug ] = $this->getAjaxParams();

		$defaultLang = pll_default_language();
		$langPostId  = ( $lang === $defaultLang ) ? $postId : "{$postId}_{$lang}";

		// Get the options page config for autoload setting.
		$page = function_exists( 'acf_get_options_page' ) ? acf_get_options_page( $menuSlug ) : null;
		if ( $page && isset( $page['autoload'] ) ) {
			acf_update_setting( 'autoload', $page['autoload'] );
		}

		// Validate and save.
		if ( acf_validate_save_post( true ) ) {
			// Disable Polylang "Copy" sync — our popup handles translation independently.
			add_filter( 'acf/load_field', [ $this, 'neutralizePolylangSync' ] );
			acf_save_post( $langPostId );
			remove_filter( 'acf/load_field', [ $this, 'neutralizePolylangSync' ] );

			wp_send_json_success( [ 'message' => __( 'Translation saved.', 'SPL' ) ] );
		}

		wp_send_json_error( [ 'message' => __( 'Validation failed.', 'SPL' ) ] );
	}

	/**
	 * AJAX: Copy all field values from default language to target.
	 */
	public function ajaxCopy(): void {
		$this->verifyAjaxNonce();

		[ $postId, $lang, $menuSlug ] = $this->getAjaxParams();

		// Prevent copying default to default.
		if ( $lang === pll_default_language() ) {
			wp_send_json_error( [ 'message' => __( 'Cannot copy to the default language.', 'SPL' ) ] );
		}

		$langPostId = "{$postId}_{$lang}";

		// Get all fields from default post_id.
		$fields = get_fields( $postId );
		if ( empty( $fields ) ) {
			wp_send_json_error( [ 'message' => __( 'No fields to copy.', 'SPL' ) ] );
		}

		// Disable Polylang "Copy" sync — our popup handles translation independently.
		add_filter( 'acf/load_field', [ $this, 'neutralizePolylangSync' ] );

		// Copy each field value to the target post_id.
		foreach ( $fields as $fieldName => $value ) {
			update_field( $fieldName, $value, $langPostId );
		}

		remove_filter( 'acf/load_field', [ $this, 'neutralizePolylangSync' ] );

		wp_send_json_success( [ 'message' => __( 'Fields copied.', 'SPL' ) ] );
	}

	/**
	 * AJAX: Remove all stored field values for a translated options page.
	 */
	public function ajaxRemove(): void {
		$this->verifyAjaxNonce();

		[ $postId, $lang, $menuSlug ] = $this->getAjaxParams();

		if ( $lang === pll_default_language() ) {
			wp_send_json_error( [ 'message' => __( 'Cannot remove the default language.', 'SPL' ) ] );
		}

		if ( ! PLL()->model->get_language( $lang ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid language.', 'SPL' ) ] );
		}

		$page = function_exists( 'acf_get_options_page' ) ? acf_get_options_page( $menuSlug ) : null;
		if ( empty( $page ) || ( $page['post_id'] ?? '' ) !== $postId ) {
			wp_send_json_error( [ 'message' => __( 'Invalid options page.', 'SPL' ) ] );
		}

		$langPostId = "{$postId}_{$lang}";
		$meta       = (array) acf_get_meta( $langPostId );

		foreach ( array_keys( $meta ) as $key ) {
			if ( str_starts_with( $key, '_' ) ) {
				acf_delete_metadata( $langPostId, substr( $key, 1 ), true );
				continue;
			}

			acf_delete_metadata( $langPostId, $key, false );
		}

		wp_send_json_success( [ 'message' => __( 'Translation removed.', 'SPL' ) ] );
	}

	/* ---------- Polylang Sync Guard --------------------------------- */

	/**
	 * Override Polylang's "Copy" preference during our save operations.
	 *
	 * Prevents Polylang from duplicating/overwriting data across languages
	 * when saving via the translation popup. Our frontend fallback mechanism
	 * handles missing translations automatically.
	 *
	 * @param array $field ACF field settings.
	 *
	 * @return array
	 */
	public function neutralizePolylangSync( array $field ): array {
		if ( ! empty( $field['pll_preference'] ) ) {
			$field['pll_preference'] = '';
		}

		return $field;
	}

	/* ---------- Helpers ---------------------------------------------- */

	/**
	 * Verify AJAX nonce and capability.
	 */
	private function verifyAjaxNonce(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'SPL' ) ], 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'SPL' ) ], 403 );
		}
	}

	/**
	 * Extract and validate common AJAX parameters.
	 *
	 * @return array{0: string, 1: string, 2: string} [postId, lang, menuSlug]
	 */
	private function getAjaxParams(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verifyAjaxNonce().
		$postId = sanitize_text_field( wp_unslash( $_POST['post_id'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verifyAjaxNonce().
		$lang = sanitize_key( wp_unslash( $_POST['lang'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verifyAjaxNonce().
		$menuSlug = sanitize_key( wp_unslash( $_POST['menu_slug'] ?? '' ) );

		if ( empty( $postId ) || empty( $lang ) || empty( $menuSlug ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing required parameters.', 'SPL' ) ] );
		}

		return [ $postId, $lang, $menuSlug ];
	}

	/**
	 * Get all PLL languages.
	 *
	 * @return PLL_Language[]
	 */
	private function getLanguages(): array {
		return PLL()->model->get_languages_list();
	}

	/**
	 * Get the default PLL language object.
	 */
	private function getDefaultLanguage(): ?PLL_Language {
		return PLL()->model->get_language( pll_default_language() ) ?: null;
	}

	/**
	 * Build a status map: ['lang_slug' => bool] indicating if the language has data.
	 *
	 * @param string         $postId    Base options page post_id.
	 * @param PLL_Language[] $languages PLL languages.
	 *
	 * @return array<string, bool>
	 */
	private function buildStatusMap( string $postId, array $languages ): array {
		$defaultSlug = pll_default_language();
		$map         = [];

		foreach ( $languages as $lang ) {
			$checkId            = ( $lang->slug === $defaultSlug ) ? $postId : "{$postId}_{$lang->slug}";
			$map[ $lang->slug ] = ! empty( acf_get_meta( $checkId ) );
		}

		return $map;
	}

	/**
	 * Check if the current admin screen is an ACF options page.
	 */
	private function isOptionsPageScreen(): bool {
		if ( ! function_exists( 'acf_get_options_pages' ) ) {
			return false;
		}

		$pages = acf_get_options_pages();
		if ( empty( $pages ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		// ACF options page screen IDs follow patterns like:
		// "toplevel_page_{slug}" or "{parent}_page_{slug}".
		foreach ( $pages as $page ) {
			$slug = $page['menu_slug'] ?? '';
			if ( ! empty( $slug ) && str_contains( $screen->id, $slug ) ) {
				return true;
			}
		}

		return false;
	}
}
