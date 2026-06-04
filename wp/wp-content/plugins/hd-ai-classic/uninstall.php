<?php
/**
 * HD AI Classic uninstall handler.
 *
 * @package HDAC
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Clean up plugin options.
delete_option( 'hdac_settings' );
