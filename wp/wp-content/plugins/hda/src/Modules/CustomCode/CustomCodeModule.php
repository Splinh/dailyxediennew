<?php
/**
 * CustomCode Module — Inject custom CSS and JS snippets.
 *
 * Coordinates sub-modules: CustomScript (header/footer/body scripts)
 * and CustomCss (inline CSS output).
 *
 * Uses StoredOption (custom post type) for storage instead of wp_options.
 *
 * @package HDAddons\Modules\CustomCode
 */

namespace HDAddons\Modules\CustomCode;

use HDAddons\Contracts\HasSettings;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class CustomCodeModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'custom_code';
	}

	public static function title(): string {
		return 'Custom Code';
	}

	public static function description(): string {
		return 'Inject custom CSS and JS snippets.';
	}

	public static function group(): string {
		return 'tools';
	}

	// ── Option Keys ─────────────────────────────────

	/**
	 * All stored option keys for uninstall cleanup.
	 * CustomCode uses StoredOption (CPT) — these are CPT storage keys.
	 */
	public static function optionKeys(): array {
		return [
			CustomScript::KEY_HEADER,
			CustomScript::KEY_FOOTER,
			CustomScript::KEY_BODY_TOP,
			CustomScript::KEY_BODY_BOTTOM,
			CustomCss::OPTION_NAME,
			CustomCss::OPTION_NAME . '_minified',
		];
	}

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		// Sub-modules self-register WordPress hooks.
		new CustomScript();
		new CustomCss();
	}

	// ── HasSettings ─────────────────────────────────

	public static function saveSettings( array $data ): void {
		// Delegate script fields (header/footer/body) to CustomScript.
		CustomScript::saveSettings( $data );

		// Delegate CSS to CustomCss if present.
		if ( isset( $data[ CustomCss::KEY_FORM_CSS ] ) ) {
			CustomCss::saveSettings( $data );
		}
	}
}
