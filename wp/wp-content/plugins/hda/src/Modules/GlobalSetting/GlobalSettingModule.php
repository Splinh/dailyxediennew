<?php
/**
 * GlobalSetting Module — Admin menu, module toggles, and settings UI.
 *
 * This is an always-active module that provides the HDA admin menu,
 * module enable/disable toggles, and the AJAX settings save handler.
 * It cannot be disabled by the user.
 *
 * @package HDAddons\Modules\GlobalSetting
 */

namespace HDAddons\Modules\GlobalSetting;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\GlobalSetting\GlobalSetting;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class GlobalSettingModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'global_setting';
	}

	public static function title(): string {
		return 'Global Setting';
	}

	public static function description(): string {
		return 'Enable or disable plugin modules.';
	}

	public static function group(): string {
		return 'general';
	}

	public static function alwaysActive(): bool {
		return true;
	}

	public static function optionKeys(): array {
		return [ GlobalSetting::OPTION_NAME, GlobalSetting::KEY_GITHUB_TOKEN ];
	}

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		// Delegate to existing GlobalSetting constructor which handles:
		// - Admin menu registration
		// - AJAX settings save handler
		// - Orphan module cleanup
		new GlobalSetting();
	}

	// ── HasSettings ─────────────────────────────────

	/**
	 * Save GitHub token (encrypted) from Global Setting form.
	 *
	 * Token is encrypted before storage — never saved as plaintext.
	 * Empty submission clears the stored token.
	 */
	public static function saveSettings( array $data ): void {
		$raw = trim( $data[ GlobalSetting::KEY_GITHUB_TOKEN ] ?? '' );

		if ( '' === $raw ) {
			// Clear token if field left empty.
			Helper::removeOption( GlobalSetting::KEY_GITHUB_TOKEN );
			return;
		}

		// Skip re-encrypting the masked placeholder (user didn't change the token).
		if ( str_starts_with( $raw, '***' ) ) {
			return;
		}

		$encrypted = Helper::encryptValue( $raw );
		if ( '' !== $encrypted ) {
			Helper::updateOption( GlobalSetting::KEY_GITHUB_TOKEN, $encrypted, 0, false );
		}
	}
}
