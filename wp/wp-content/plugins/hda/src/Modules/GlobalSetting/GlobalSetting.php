<?php
/**
 * GlobalSetting — Admin menu and module toggles.
 *
 * Central hub for HDA admin UI. Uses ModuleRegistry for auto-discovery.
 * AJAX settings save is handled by Core\SettingsManager.
 *
 * @package HDAddons\Modules\GlobalSetting
 */

namespace HDAddons\Modules\GlobalSetting;

use HDAddons\Core\ModuleRegistry;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class GlobalSetting {

	// ─── Option Keys (single source of truth) ───────────

	public const OPTION_NAME = 'hda_config';

	/**
	 * Sub-key within hda_config for module enable/disable toggles.
	 */
	public const KEY_MODULES = 'modules';

	/**
	 * Sub-key within hda_config for clean uninstall toggle.
	 */
	public const KEY_CLEAN_UNINSTALL = 'clean_uninstall';

	/**
	 * Option key for encrypted GitHub Personal Access Token.
	 */
	public const KEY_GITHUB_TOKEN = '_hda_github_token';

	/** Menu position constant. */
	private const MENU_POSITION = 80;

	/**
	 * Modules that use StoredOption (custom post) instead of wp_options.
	 */
	public const STORED_OPTION_MODULES = [ 'custom_code', 'redirect' ];

	/**
	 * Cached group definitions from config/groups.php.
	 */
	private static ?array $groupsCache = null;

	// --------------------------------------------------

	/**
	 * Initialize admin menu (AJAX is handled by SettingsManager).
	 */
	public function __construct() {
		add_action( 'admin_menu', $this->adminMenu( ... ) );
		add_action( 'admin_menu', $this->renameFirstSubmenu( ... ), 999 );
	}

	// --------------------------------------------------
	// Config — delegated to ModuleRegistry
	// --------------------------------------------------

	/**
	 * Get module configuration from ModuleRegistry.
	 *
	 * Backward-compatible replacement for config.php reads.
	 * Returns only non-alwaysActive modules (toggleable).
	 *
	 * @return array<string, array{title: string, description: string, group: string}>
	 */
	public static function getConfig(): array {
		return ModuleRegistry::getInstance()->getConfig();
	}

	/**
	 * Group labels for settings sidebar.
	 *
	 * Reads from config/groups.php — single source of truth.
	 *
	 * @return array<string, array{label: string, icon: string}>
	 */
	public static function getGroupLabels(): array {
		if ( null === self::$groupsCache ) {
			$groupsFile        = HDA_PATH . 'config/groups.php';
			self::$groupsCache = is_file( $groupsFile ) ? (array) include $groupsFile : [];
		}

		return self::$groupsCache;
	}

	/**
	 * Get modules grouped by their 'group' key.
	 *
	 * @return array<string, array<string, array>> Group slug => [module slug => module config]
	 */
	public static function getGroupedConfig(): array {
		$config      = self::getConfig();
		$groupLabels = self::getGroupLabels();
		$grouped     = [];

		foreach ( $config as $slug => $value ) {
			$group = $value['group'] ?? 'tools';

			if ( ! isset( $grouped[ $group ] ) ) {
				$grouped[ $group ] = [];
			}

			$grouped[ $group ][ $slug ] = $value;
		}

		// Ensure groups are in the order defined by config/groups.php.
		$ordered = [];
		foreach ( array_keys( $groupLabels ) as $groupKey ) {
			if ( isset( $grouped[ $groupKey ] ) ) {
				$ordered[ $groupKey ] = $grouped[ $groupKey ];
			}
		}

		return $ordered;
	}

	// --------------------------------------------------

	/**
	 * Register admin menu pages.
	 */
	private function adminMenu(): void {
		add_menu_page(
			__( 'SPL Settings', 'hda' ),
			__( 'SPL', 'hda' ),
			Plugin::CAPABILITY,
			'hda-settings',
			$this->menuCallback( ... ),
			'dashicons-admin-settings',
			self::MENU_POSITION
		);
	}

	/**
	 * Rename the auto-generated first submenu item.
	 */
	private function renameFirstSubmenu(): void {
		global $submenu;

		if ( ! empty( $submenu['hda-settings'] ) ) {
			$submenu['hda-settings'][0][0] = __( 'Settings', 'hda' );
		}
	}

	// --------------------------------------------------

	/**
	 * Main settings page callback — single layout entry.
	 */
	private function menuCallback(): void {
		include __DIR__ . '/views/settings.php';
	}
}
