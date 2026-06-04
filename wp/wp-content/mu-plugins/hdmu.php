<?php
/**
 * Plugin Name: HDMU
 * Description: Must-use plugins
 * Version: 2.0.0
 * Requires PHP: 8.1
 * Author: HD
 * Author URI: https://webhd.vn
 * License: MIT
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

const HDMU_PATH = __DIR__ . '/hdmu/';

// Load modules
if ( is_blog_installed() ) {
	require_once HDMU_PATH . 'DisallowIndexing.php';
	require_once HDMU_PATH . 'PluginDisabler.php';

	add_action(
		'muplugins_loaded',
		static function (): void {
			HDMU\DisallowIndexing::init();
			HDMU\PluginDisabler::init();
		},
		0
	);
}
