<?php
/**
 * Configuration overrides for WP_ENV === 'development'
 *
 * @package HD
 */

use Roots\WPConfig\Config;
use function Env\env;

Config::define( 'SAVEQUERIES', true );
Config::define( 'WP_DEBUG', true );
Config::define( 'WP_DEBUG_DISPLAY', false );
Config::define( 'WP_DEBUG_LOG', env( 'WP_DEBUG_LOG' ) ?? true );
Config::define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
Config::define( 'SCRIPT_DEBUG', true );
Config::define( 'DISALLOW_INDEXING', true );
Config::define( 'WP_ALLOW_REPAIR', true );

/** Enable plugin and theme updates and installation from the admin */
Config::define( 'DISALLOW_FILE_EDIT', false );
Config::define( 'DISALLOW_FILE_MODS', false );

/** DISABLED_PLUGINS */
Config::define(
	'DISABLED_PLUGINS',
	[
		'wp-rocket/wp-rocket.php',
		'flying-press/flying-press.php',
		'litespeed-cache/litespeed-cache.php',
		'hummingbird-performance/wp-hummingbird.php',
		'swift-performance/performance.php',
		'wp-super-cache/wp-cache.php',
		'w3-total-cache/w3-total-cache.php',
		'nitropack/main.php',
		'wp-asset-clean-up-pro/wpacu.php',
		'perfmatters/perfmatters.php',
	]
);
