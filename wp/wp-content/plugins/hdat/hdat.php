<?php
/**
 * Plugin Name: SPL AI Toolkit
 * Plugin URI:  https://splworks.com
 * Description: AI Key Pool management with multi-provider rotation for WordPress.
 * Version:     2.0.0
 * Requires PHP: 8.1
 * Requires at least: 6.4
 * Author:      SPL
 * Author URI:  https://splworks.com
 * License:     MIT
 * Text Domain: hdat
 *
 * @package HDAT
 */

defined( 'ABSPATH' ) || exit;

defined( 'HDAT_VERSION' ) || define( 'HDAT_VERSION', '2.0.0' );
defined( 'HDAT_DIR' ) || define( 'HDAT_DIR', plugin_dir_path( __FILE__ ) );
defined( 'HDAT_URL' ) || define( 'HDAT_URL', plugin_dir_url( __FILE__ ) );
defined( 'HDAT_FILE' ) || define( 'HDAT_FILE', __FILE__ );

// Composer PSR-4: HDAT\ -> src/.
$autoload = HDAT_DIR . 'vendor/autoload.php';
if ( is_file( $autoload ) ) {
	require_once $autoload;
}

if ( ! class_exists( HDAT\Kernel\Plugin::class ) ) {
	return;
}

register_activation_hook( __FILE__, [ HDAT\Kernel\Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ HDAT\Kernel\Plugin::class, 'deactivate' ] );
add_action( 'plugins_loaded', [ HDAT\Kernel\Plugin::class, 'boot' ] );
