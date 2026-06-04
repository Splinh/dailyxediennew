<?php
/**
 * Theme functions and definitions.
 *
 * Initializes the HD Theme, loads dependencies via Composer autoload,
 * defines constants, and ensures compatibility with PHP 8.1 or newer.
 *
 * Directory Structure:
 * - src/           PSR-4 autoloaded classes (namespace: HD\)
 *   - Contracts/   Interfaces and abstract base classes (Bootable, Feature, ModuleInterface)
 *   - Core/        Infrastructure services (Asset, Cache, DB, ModuleRegistry)
 *   - Features/    Native theme features (Admin, Customizer, Optimizer, etc.)
 *   - Modules/     Auto-discovered project modules (plugin integrations)
 *   - Traits/      Helper traits used by Helper and Query classes
 *   - Support/     NavWalkers, Libraries, Shortcode infrastructure
 *   - Bootstrap.php, Theme.php
 * - config/        Non-class files: settings data, helpers, hooks
 *
 * @package HD
 */

use HD\Bootstrap;

/**
 * Display error message in admin and frontend.
 *
 * @param string $error_message
 *
 * @return void
 */
function hd_static_error( string $error_message ): void {
	add_action(
		'admin_notices',
		static fn() => printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $error_message )
		)
	);

	if ( ! is_admin() ) {
		get_template_part( 'template-parts/blocks/php-error', null, [ 'error_message' => $error_message ] );
		wp_die();
	}
}

// ── Guards (fail-fast) ──────────────────────────

// PHP version guard (8.1+).
if ( PHP_VERSION_ID < 80100 ) {
	hd_static_error( 'HD Theme: requires PHP 8.1 or newer.' );

	return;
}

// Autoload classes (PSR-4 via Composer) & local dependencies.
if ( is_file( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( Bootstrap::class ) ) {
	hd_static_error( 'HD Theme: missing vendor autoload. Run `composer install`.' );

	return;
}

// ── Constants ───────────────────────────────────

const HD_AUTHOR      = 'HD';
const HD_ASSETS_DIR  = 'assets';
const HD_RESOURCES   = 'resources';
const REST_NAMESPACE = 'hd/v1';

define( 'THEME_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'THEME_PATH', get_template_directory() . '/' );
define( 'THEME_URL', get_template_directory_uri() . '/' );

// ── Bootstrap ───────────────────────────────────

// Global aliases for commonly used classes.
class_alias( HD\Core\Helper::class, 'HD_Helper' );
class_alias( HD\Core\Query::class, 'HD_Query' );

// Bootstrap the theme.
Bootstrap::init();
