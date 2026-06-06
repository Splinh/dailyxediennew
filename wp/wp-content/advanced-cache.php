<?php
/**
 * SPL Advanced Cache — Drop-in that runs BEFORE WordPress loads.
 *
 * This file is loaded by wp-settings.php when WP_CACHE is true,
 * before themes and plugins are loaded. It serves cached HTML
 * files instantly, bypassing the entire WordPress stack.
 *
 * @package SPL
 */

// Safety checks — only serve cache for anonymous GET requests.
if (
	php_sapi_name() === 'cli'
	|| ( defined( 'WP_CLI' ) && WP_CLI )
	|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
	|| ( defined( 'DOING_CRON' ) && DOING_CRON )
	|| ( defined( 'WP_ADMIN' ) && WP_ADMIN )
	|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
	|| ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'GET'
) {
	return;
}

// Skip if logged-in cookie detected.
foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
	if (
		str_starts_with( $cookie_name, 'wordpress_logged_in_' )
		|| str_starts_with( $cookie_name, 'woocommerce_' )
		|| str_starts_with( $cookie_name, 'wp_woocommerce_' )
	) {
		return;
	}
}

// Skip cart/checkout query params.
if ( ! empty( $_GET['add-to-cart'] ) || ! empty( $_GET['removed_item'] ) || ! empty( $_GET['s'] ) ) {
	return;
}

// Build cache file path.
$spl_cache_dir  = WP_CONTENT_DIR . '/cache/spl-pages';
$spl_cache_host = preg_replace( '/[^a-zA-Z0-9._-]/', '', $_SERVER['HTTP_HOST'] ?? 'default' );
$spl_cache_uri  = trim( $_SERVER['REQUEST_URI'] ?? '/', '/' );
$spl_cache_uri  = preg_replace( '/[^a-zA-Z0-9._-]/', '', $spl_cache_uri ) ?: 'index';
$spl_cache_file = $spl_cache_dir . '/' . $spl_cache_host . '/' . $spl_cache_uri . '.html';

// Serve cached file if fresh.
if ( is_file( $spl_cache_file ) ) {
	$spl_cache_ttl = defined( 'SPL_CACHE_TTL' ) ? (int) SPL_CACHE_TTL : 43200; // 12 hours.
	$spl_cache_age = time() - filemtime( $spl_cache_file );

	if ( $spl_cache_age < $spl_cache_ttl ) {
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'X-SPL-Cache: HIT' );
		header( 'X-SPL-Cache-Age: ' . $spl_cache_age );
		header( 'Cache-Control: public, max-age=300' ); // Browser cache 5min.

		// Serve gzip if client supports it.
		$accept = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
		if ( str_contains( $accept, 'gzip' ) && function_exists( 'ob_gzhandler' ) ) {
			ob_start( 'ob_gzhandler' );
			readfile( $spl_cache_file );
			ob_end_flush();
		} else {
			readfile( $spl_cache_file );
		}
		exit;
	}

	// Expired — delete.
	@unlink( $spl_cache_file );
}
// Cache miss — WordPress continues loading. PageCache module handles OB + save.
