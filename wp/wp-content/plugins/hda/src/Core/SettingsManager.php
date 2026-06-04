<?php
/**
 * AJAX settings handler.
 *
 * Extracted from GlobalSetting to separate concerns:
 * - GlobalSetting: admin menu, UI rendering, module toggles
 * - SettingsManager: AJAX form processing, sanitization, delegation
 *
 * Uses ModuleRegistry::processSettingsSave() to delegate
 * save logic to each module implementing HasSettings.
 *
 * @package HDAddons\Core
 */

namespace HDAddons\Core;

use HDAddons\Helper;
use HDAddons\Modules\GlobalSetting\GlobalSetting;
use HDAddons\Plugin;

defined( 'ABSPATH' ) || exit;

final class SettingsManager {

	/**
	 * Fields that contain raw HTML/JS/CSS content.
	 *
	 * These bypass sanitize_text_field() to preserve tags and newlines.
	 * Their module handlers apply content-type-specific sanitization.
	 *
	 * @var string[]
	 */
	private array $rawContentFields = [];

	public function __construct( array $rawContentFields = [] ) {
		$this->rawContentFields = $rawContentFields;
	}

	/**
	 * Register the AJAX handler.
	 */
	public function register(): void {
		add_action( 'wp_ajax_submit_settings', $this->ajaxSubmitSettings( ... ) );
	}

	/**
	 * Process form submission via AJAX.
	 */
	private function ajaxSubmitSettings(): void {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		// Security checks.
		check_ajax_referer( '_wpnonce_settings_form_' . get_current_user_id() );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			Helper::toastError( __( 'You do not have permission to perform this action.', 'hda' ), true );
		}

		try {
			// Sanitize input data.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizeData() handles per-field sanitization.
			$data = isset( $_POST['_data'] ) && is_array( $_POST['_data'] )
				? $this->sanitizeData( wp_unslash( $_POST['_data'] ) )
				: [];

			// Handle GlobalSetting toggles first.
			$this->handleGlobalSetting( $data );

			// Delegate to each HasSettings module via registry.
			$registry = ModuleRegistry::getInstance();
			$registry->processSettingsSave( $data );

			// Clear cache and send response.
			Helper::clearAllCache();
			Helper::toastSuccess( __( 'Your settings have been saved.', 'hda' ), true );
		} catch ( \Throwable $e ) {
			Helper::errorLog( 'HDA Settings Save Error: ' . $e->getMessage() );
			Helper::toastError( __( 'An error occurred while saving settings.', 'hda' ), true );
		}
	}

	/**
	 * Handle module enable/disable toggles and uninstall preference.
	 *
	 * @param array $data Form data.
	 */
	private function handleGlobalSetting( array $data ): void {
		$registry = ModuleRegistry::getInstance();
		$config   = $registry->getConfig();

		$enabledOptions = [];

		foreach ( $config as $slug => $value ) {
			if ( ! empty( $data[ $slug ] ) ) {
				$enabledOptions[ $slug ] = 1;
			}
		}

		// Build consolidated hda_config.
		$hdaConfig = [
			GlobalSetting::KEY_MODULES         => $enabledOptions,
			GlobalSetting::KEY_CLEAN_UNINSTALL => ! empty( $data[ GlobalSetting::KEY_CLEAN_UNINSTALL ] ) ? 1 : 0,
		];

		Helper::updateOption( GlobalSetting::OPTION_NAME, $hdaConfig, 0, false );
	}

	/**
	 * Sanitize data recursively with proper type handling.
	 *
	 * @param array $data Data to sanitize.
	 *
	 * @return array
	 */
	private function sanitizeData( array $data ): array {
		$sanitized = [];

		foreach ( $data as $key => $value ) {
			$sanitizedKey = is_numeric( $key ) ? $key : sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $sanitizedKey ] = $this->sanitizeData( $value );
			} elseif ( is_string( $value ) ) {
				if ( in_array( $sanitizedKey, $this->rawContentFields, true ) ) {
					$sanitized[ $sanitizedKey ] = $value;
				} else {
					$sanitized[ $sanitizedKey ] = sanitize_text_field( $value );
				}
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $sanitizedKey ] = $value;
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $sanitizedKey ] = $value;
			} else {
				$sanitized[ $sanitizedKey ] = $value;
			}
		}

		return $sanitized;
	}
}
