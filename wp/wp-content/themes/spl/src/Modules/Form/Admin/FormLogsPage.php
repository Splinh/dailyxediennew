<?php
/**
 * Form Logs Page
 *
 * @package SPL\Modules\Form\Admin
 */

namespace SPL\Modules\Form\Admin;

defined( 'ABSPATH' ) || exit;

class FormLogsPage {

	/**
	 * Register admin submenu.
	 */
	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'addMenuPage' ], 40 );
	}

	public static function addMenuPage(): void {
		add_submenu_page(
			'hd-form-entries',
			__( 'Form Logs', 'SPL' ),
			__( 'Logs', 'SPL' ),
			'manage_options',
			'hd-form-logs',
			[ self::class, 'renderPage' ]
		);
	}

	public static function renderPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'SPL' ) );
		}

		$listTable = new FormLogsListTable();
		$listTable->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Form Logs', 'SPL' ) . '</h1>';
		echo '<form method="get">';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<input type="hidden" name="page" value="' . esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '' ) . '">';
		$listTable->display();
		echo '</form>';
		echo '</div>';
	}
}
