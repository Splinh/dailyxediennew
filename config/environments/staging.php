<?php
/**
 * Configuration overrides for WP_ENV === 'staging'
 *
 * @package HD
 */

use Roots\WPConfig\Config;

/**
 * You should try to keep staging as close to production as possible. However,
 * should you need to, you can always override production configuration values
 * with `Config::define`.
 *
 * Example: `Config::define('WP_DEBUG', true);`
 * Example: `Config::define('DISALLOW_FILE_MODS', false);`
 */

Config::define( 'DISALLOW_INDEXING', false );
Config::define( 'WP_DEBUG', true );
Config::define( 'WP_DEBUG_DISPLAY', false );
Config::define( 'WP_DEBUG_LOG', true );

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
