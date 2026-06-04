<?php
/**
 * Theme functions and definitions.
 *
 * Initializes the SPL Theme, loads dependencies via Composer autoload,
 * defines constants, and ensures compatibility with PHP 8.1 or newer.
 *
 * Directory Structure:
 * - src/           PSR-4 autoloaded classes (namespace: SPL\)
 *   - Contracts/   Interfaces and abstract base classes (Bootable, Feature, ModuleInterface)
 *   - Core/        Infrastructure services (Asset, Cache, DB, ModuleRegistry)
 *   - Features/    Native theme features (Admin, Customizer, Optimizer, etc.)
 *   - Modules/     Auto-discovered project modules (plugin integrations)
 *   - Traits/      Helper traits used by Helper and Query classes
 *   - Support/     NavWalkers, Libraries, Shortcode infrastructure
 *   - Bootstrap.php, Theme.php
 * - config/        Non-class files: settings data, helpers, hooks
 *
 * @package SPL
 */

use SPL\Bootstrap;

/**
 * Display error message in admin and frontend.
 *
 * @param string $error_message
 *
 * @return void
 */
function spl_static_error( string $error_message ): void {
	add_action(
		'admin_notices',
		static fn() => printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $error_message )
		)
	);

	if ( ! is_admin() ) {
		wp_die(
			esc_html( $error_message ),
			esc_html__( 'Lỗi Theme', 'spl' ),
			[ 'response' => 500 ]
		);
	}
}

// ── Guards (fail-fast) ──────────────────────────────

// PHP version guard (8.1+).
if ( PHP_VERSION_ID < 80100 ) {
	spl_static_error( 'SPL Theme: requires PHP 8.1 or newer.' );

	return;
}

// Autoload classes (PSR-4 via Composer) & local dependencies.
if ( is_file( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( Bootstrap::class ) ) {
	spl_static_error( 'SPL Theme: missing vendor autoload. Run `composer install`.' );

	return;
}

// ── Constants ───────────────────────────────────────

const SPL_AUTHOR      = 'SPL';
const SPL_ASSETS_DIR  = 'assets';
const SPL_RESOURCES   = 'resources';
const REST_NAMESPACE  = 'spl/v1';

define( 'THEME_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'THEME_PATH', get_template_directory() . '/' );
define( 'THEME_URL', get_template_directory_uri() . '/' );

// ── Project includes ────────────────────────────────

require_once __DIR__ . '/inc/critical-css.php';
require_once __DIR__ . '/inc/inline-js.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/woocommerce-ui.php';

// ── Bootstrap ───────────────────────────────────────

// Global aliases for commonly used classes.
class_alias( SPL\Core\Helper::class, 'SPL_Helper' );
class_alias( SPL\Core\Query::class, 'SPL_Query' );

// Bootstrap the theme.
Bootstrap::init();
