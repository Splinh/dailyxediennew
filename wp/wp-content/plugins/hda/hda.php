<?php
/**
 * Plugin Name: SPL Toolkit
 * Plugin URI: https://splworks.com
 * Version: 2.3.9
 * Requires PHP: 8.1
 * Author: SPL
 * Author URI: https://splworks.com
 * Description: Essential WordPress Toolkit: Security, Custom Assets & Admin utilities.
 * License: MIT
 */

use HDAddons\Activator;
use HDAddons\Plugin;

defined( 'ABSPATH' ) || exit;

// Prevent double loading.
if ( defined( 'HDA_VERSION' ) ) {
	return;
}

// ── Constants ───────────────────────────────────

const HDA_VERSION = '2.3.9';

define( 'HDA_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
define( 'HDA_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/' );
define( 'HDA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ── Guards (fail-fast, before any hook registration) ──

// PHP version guard (8.1+).
if ( PHP_VERSION_ID < 80100 ) {
	add_action(
		'admin_notices',
		static fn() => printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( 'SPL Toolkit requires PHP 8.1 or newer. Please upgrade your PHP version.' )
		)
	);

	return;
}

// Autoload guard.
$hda_autoload = __DIR__ . '/vendor/autoload.php';
if ( is_file( $hda_autoload ) ) {
	require_once $hda_autoload;
}

if ( ! class_exists( Plugin::class ) ) {
	add_action(
		'admin_notices',
		static fn() => printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( 'SPL Toolkit: missing vendor directory. Please run composer install.' )
		)
	);

	return;
}

// ── Activation hooks (must be before plugins_loaded) ──

register_activation_hook( __FILE__, [ Activator::class, 'activation' ] );
register_deactivation_hook( __FILE__, [ Activator::class, 'deactivation' ] );

// ── Bootstrap ───────────────────────────────────

add_action( 'plugins_loaded', [ Plugin::class, 'boot' ], 10 );

// Uninstall is handled via uninstall.php.
