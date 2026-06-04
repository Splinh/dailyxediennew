<?php
/**
 * Uninstall HDAT.
 *
 * Default: preserve data. Only delete tables/options when user opted in via
 * `clean_uninstall` setting.
 *
 * @package HDAT
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$opts = (array) get_option( 'hdat_settings', [] );
if ( empty( $opts['clean_uninstall'] ) ) {
	return;
}

global $wpdb;

$tables = [
	'hdat_ai_keys',
	'hdat_consumer_tokens',
	'hdat_usage_ledger',
	'hdat_response_cache',
	'hdat_route_state',
	'hdat_quota_windows',
	'hdat_sticky_routes',
];

foreach ( $tables as $t ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $t ) );
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE option_name LIKE %s', $wpdb->options, 'hdat\_%' ) );
