<?php
/**
 * Plugin Name: SPL AI Classic
 * Plugin URI: https://splworks.com
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: SPL
 * Author URI: https://splworks.com
 * Description: AI-powered content generation for WordPress Classic Editor, powered by HDAT gateway.
 * License: MIT
 * Text Domain: hd-ai-classic
 */

use HDAC\Plugin;

defined( 'ABSPATH' ) || exit;

// Prevent double loading.
if ( defined( 'HDAC_VERSION' ) ) {
	return;
}

// Constants.

const HDAC_VERSION = '1.0.0';

define( 'HDAC_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
define( 'HDAC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/' );
define( 'HDAC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Guards.

// PHP 8.1+ guard.
if ( PHP_VERSION_ID < 80100 ) {
	add_action(
		'admin_notices',
		static fn() => printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( 'SPL AI Classic requires PHP 8.1 or newer.' )
		)
	);

	return;
}

// Autoload guard.
$hdac_autoload = __DIR__ . '/vendor/autoload.php';
if ( is_file( $hdac_autoload ) ) {
	require_once $hdac_autoload;
}

if ( ! class_exists( \HDAC\Plugin::class ) ) {
	add_action(
		'admin_notices',
		static fn() => printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( 'SPL AI Classic: missing vendor directory. Please run composer install.' )
		)
	);

	return;
}

// HDAT dependency guard; must load after HDAT (priority 12).
add_action(
	'plugins_loaded',
	static function () {

		// Check if HDAT plugin is active.
		if ( ! defined( 'HDAT_VERSION' ) ) {
			add_action(
				'admin_notices',
				static fn() => printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html( 'SPL AI Classic requires SPL AI Toolkit (HDAT) plugin. Please install and activate it.' )
				)
			);

			return;
		}

		Plugin::boot();
	},
	12
);
