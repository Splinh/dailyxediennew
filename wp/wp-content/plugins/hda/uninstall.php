<?php
/**
 * Plugin uninstall entrypoint.
 *
 * @package HDAddons
 */

use HDAddons\Activator;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

defined( 'HDA_PATH' ) || define( 'HDA_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
defined( 'HDA_URL' ) || define( 'HDA_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/' );

$hda_autoload = __DIR__ . '/vendor/autoload.php';

if ( is_file( $hda_autoload ) ) {
	require_once $hda_autoload;

	Activator::uninstall();
}
