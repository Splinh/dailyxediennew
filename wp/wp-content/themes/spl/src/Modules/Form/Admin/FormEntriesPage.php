<?php
/**
 * Form Entries Page
 *
 * @package SPL\Modules\Form\Admin
 */

namespace SPL\Modules\Form\Admin;

use SPL\Modules\Form\Repository\FormEntryRepository;

defined( 'ABSPATH' ) || exit;

class FormEntriesPage {

	/**
	 * Register admin menu and styles.
	 */
	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'addMenuPage' ], 30 );
		add_action( 'admin_head', [ self::class, 'printAdminStyles' ] );
	}

	/**
	 * Print scoped CSS for form-entries admin pages.
	 */
	public static function printAdminStyles(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! str_contains( $screen->id, 'hd-form-entries' ) ) {
			return;
		}

		?>
		<style>
			/* Wider metadata sidebar */
			.hd-form-entries #post-body.columns-2 { margin-right: 360px; }
			.hd-form-entries #postbox-container-1 { width: 340px; margin-right: -360px; }

			/* Status badges */
			.hd-entry-status { display:inline-block; padding:4px 10px; border-radius:99px; font-size:11px; font-weight:600; line-height:1.4; }
			.hd-entry-status--new { background:#d63638; color:#fff; }
			.hd-entry-status--read { background:#f0f0f1; color:#3c434a; }
			.hd-entry-status--starred { background:#2271b1; color:#fff; }
			.hd-entry-status--spam { background:#dba617; color:#fff; }
			.hd-entry-status--trash { background:#787c82; color:#fff; }

			/* UTM separator */
			.hd-meta-separator { margin:12px 0; border:0; border-top:1px solid #dcdcde; }
		</style>
		<?php
	}

	public static function addMenuPage(): void {
		$unread = get_transient( 'hd_form_unread_count' );
		if ( false === $unread ) {
			$repo   = new FormEntryRepository();
			$unread = $repo->countAll( [ 'status' => 'new' ] );
			set_transient( 'hd_form_unread_count', $unread, 5 * MINUTE_IN_SECONDS );
		}

		$badge = $unread > 0 ? sprintf( ' <span class="update-plugins count-%d"><span class="plugin-count">%d</span></span>', $unread, $unread ) : '';

		add_menu_page(
			__( 'Form Entries', 'SPL' ),
			__( 'Form Entries', 'SPL' ) . $badge,
			'manage_options',
			'hd-form-entries',
			[ self::class, 'renderPage' ],
			'dashicons-feedback',
			30
		);

		add_submenu_page(
			'hd-form-entries',
			__( 'All Entries', 'SPL' ),
			__( 'All Entries', 'SPL' ),
			'manage_options',
			'hd-form-entries',
			''
		);
	}

	public static function renderPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'SPL' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		if ( 'view' === $action && ! empty( $_GET['entry'] ) ) {
			self::renderViewPage( absint( wp_unslash( $_GET['entry'] ) ) );
			return;
		}

		$listTable = new FormEntriesListTable();
		$listTable->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Form Entries', 'SPL' ) . '</h1>';

		// Export buttons — carry current filters.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$exportParams = [
			'action'   => 'hd_export_form_entries',
			'_wpnonce' => wp_create_nonce( 'hd_export_entries' ),
		];
		if ( ! empty( $_REQUEST['status'] ) ) {
			$exportParams['status'] = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );
		}
		if ( ! empty( $_REQUEST['form_type'] ) ) {
			$exportParams['form_type'] = sanitize_text_field( wp_unslash( $_REQUEST['form_type'] ) );
		}
		if ( ! empty( $_REQUEST['s'] ) ) {
			$exportParams['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
		}

		$xlsxUrl = add_query_arg( array_merge( $exportParams, [ 'export_format' => 'xlsx' ] ), admin_url( 'admin-post.php' ) );
		$csvUrl  = add_query_arg( array_merge( $exportParams, [ 'export_format' => 'csv' ] ), admin_url( 'admin-post.php' ) );

		echo '<a href="' . esc_url( $xlsxUrl ) . '" class="page-title-action">' . esc_html__( 'Export XLSX', 'SPL' ) . '</a>';
		echo '<a href="' . esc_url( $csvUrl ) . '" class="page-title-action">' . esc_html__( 'Export CSV', 'SPL' ) . '</a>';
		echo '<hr class="wp-header-end" />';

		$listTable->views();

		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '' ) . '">';
		// phpcs:enable

		$listTable->search_box( __( 'Search', 'SPL' ), 'search_id' );
		$listTable->display();

		echo '</form>';
		echo '</div>';
	}

	private static function renderViewPage( int $id ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'SPL' ) );
		}

		$repo  = new FormEntryRepository();
		$entry = $repo->findById( $id );

		if ( ! $entry ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Entry not found.', 'SPL' ) . '</p></div>';
			return;
		}

		// Handle notes save.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' )
			&& ! empty( $_POST['hd_entry_notes_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hd_entry_notes_nonce'] ) ), 'hd_save_entry_notes_' . $id )
		) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to edit this entry.', 'SPL' ) );
			}

			$notes = sanitize_textarea_field( wp_unslash( $_POST['entry_notes'] ?? '' ) );
			$repo->updateNotes( $id, $notes );
			$entry['notes'] = $notes;

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Notes saved.', 'SPL' ) . '</p></div>';
		}

		if ( 'new' === $entry['status'] ) {
			$repo->updateStatus( $id, 'read' );
			$entry['status'] = 'read';

			delete_transient( 'hd_form_unread_count' );
		}

		echo '<div class="wrap hd-form-entries">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Entry Details', 'SPL' ) . '</h1>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=hd-form-entries' ) ) . '" class="page-title-action">' . esc_html__( 'Back', 'SPL' ) . '</a>';
		echo '<hr class="wp-header-end">';

		echo '<div id="poststuff">';
		echo '<div id="post-body" class="metabox-holder columns-2">';

		// -- Form Data (main content area).
		echo '<div id="post-body-content">';
		echo '<div class="postbox">';
		echo '<h2 class="hndle"><span>' . esc_html__( 'Form Data', 'SPL' ) . '</span></h2>';
		echo '<div class="inside">';
		echo '<table class="form-table">';

		// Core fields.
		$coreFields = [
			__( 'Name', 'SPL' )  => $entry['name'],
			__( 'Email', 'SPL' ) => $entry['email'],
			__( 'Phone', 'SPL' ) => $entry['phone'],
		];

		// Extra fields with __labels mapping.
		$data   = $entry['data'] ?? [];
		$labels = $data['__labels'] ?? [];
		unset( $data['__labels'], $data['__files'], $data['__geo'] );

		$allFields = $coreFields;
		foreach ( $data as $key => $value ) {
			$label               = $labels[ $key ] ?? ucfirst( str_replace( [ '_', '-' ], ' ', $key ) );
			$allFields[ $label ] = $value;
		}

		foreach ( $allFields as $label => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			echo '<tr>';
			echo '<th scope="row">' . esc_html( $label ) . '</th>';
			echo '<td>' . self::renderFieldValue( $value ) . '</td>';
			echo '</tr>';
		}

		// File attachments.
		$files = $entry['data']['__files'] ?? [];
		if ( ! empty( $files ) ) {
			$attachmentLinks = [];
			foreach ( $files as $fileName => $fileUrl ) {
				if ( ! self::isUploadAttachmentUrl( (string) $fileUrl ) ) {
					continue;
				}

				$attachmentLinks[] = '<a href="' . esc_url( (string) $fileUrl ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( (string) $fileName ) . '</a>';
			}

			if ( ! empty( $attachmentLinks ) ) {
				echo '<tr>';
				echo '<th scope="row">' . esc_html__( 'Attachments', 'SPL' ) . '</th>';
				echo '<td>' . implode( '<br>', $attachmentLinks ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</table>';
		echo '</div></div></div>';

		// -- Metadata sidebar.
		echo '<div id="postbox-container-1" class="postbox-container">';
		echo '<div class="postbox">';
		echo '<h2 class="hndle"><span>' . esc_html__( 'Metadata', 'SPL' ) . '</span></h2>';
		echo '<div class="inside">';
		echo '<p><strong>' . esc_html__( 'ID', 'SPL' ) . ':</strong> ' . esc_html( $entry['id'] ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Form Type', 'SPL' ) . ':</strong> ' . esc_html( $entry['form_type'] ) . '</p>';

		$statusLabel = match ( $entry['status'] ) {
			'new'     => __( 'New', 'SPL' ),
			'read'    => __( 'Read', 'SPL' ),
			'spam'    => __( 'Spam', 'SPL' ),
			'starred' => __( 'Starred', 'SPL' ),
			default   => ucfirst( $entry['status'] ),
		};
		echo '<p><strong>' . esc_html__( 'Status', 'SPL' ) . ':</strong> ' . esc_html( $statusLabel ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Date', 'SPL' ) . ':</strong> ' . esc_html( $entry['created_at'] ) . '</p>';
		echo '<p><strong>' . esc_html__( 'IP', 'SPL' ) . ':</strong> ' . esc_html( $entry['ip_address'] ) . '</p>';
		echo '<hr class="hd-meta-separator" />';
		echo '<p><strong>' . esc_html__( 'Source', 'SPL' ) . ':</strong> ' . esc_html( $entry['utm_source'] ?: '-' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Medium', 'SPL' ) . ':</strong> ' . esc_html( $entry['utm_medium'] ?: '-' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Campaign', 'SPL' ) . ':</strong> ' . esc_html( $entry['utm_campaign'] ?: '-' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Referer', 'SPL' ) . ':</strong> ' . ( $entry['referer_url'] ? '<a href="' . esc_url( $entry['referer_url'] ) . '" target="_blank">' . esc_html__( 'Link', 'SPL' ) . '</a>' : '-' ) . '</p>';
		echo '</div></div>';
		// ↑ closes .inside + .postbox (Metadata)

		// -- Admin Notes postbox.
		echo '<div class="postbox">';
		echo '<h2 class="hndle"><span>' . esc_html__( 'Admin Notes', 'SPL' ) . '</span></h2>';
		echo '<div class="inside">';
		echo '<form method="post">';
		wp_nonce_field( 'hd_save_entry_notes_' . $id, 'hd_entry_notes_nonce' );
		echo '<textarea name="entry_notes" rows="5" style="width:100%;">' . esc_textarea( $entry['notes'] ?? '' ) . '</textarea>';
		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Save Notes', 'SPL' ) . '</button></p>';
		echo '</form>';
		echo '</div></div>';
		// ↑ closes .inside + .postbox (Notes)

		echo '</div>';
		// ↑ closes #postbox-container-1

		echo '</div></div></div>';
		// ↑ closes #post-body + #poststuff + .wrap
	}
	/**
	 * Render a field value for admin display.
	 */
	private static function renderFieldValue( mixed $value ): string {
		if ( is_array( $value ) ) {
			$value = implode(
				"\n",
				array_map(
					static fn( mixed $item ): string => is_scalar( $item ) || null === $item ? (string) $item : ( wp_json_encode( $item ) ?: '' ),
					$value
				)
			);
		}

		if ( ! is_scalar( $value ) && null !== $value ) {
			$value = wp_json_encode( $value ) ?: '';
		}

		return nl2br( esc_html( (string) $value ) );
	}

	/**
	 * Only display attachment links that point to the WordPress uploads base URL.
	 */
	private static function isUploadAttachmentUrl( string $url ): bool {
		$uploads = wp_upload_dir();
		$baseUrl = isset( $uploads['baseurl'] ) ? rtrim( str_replace( '\\', '/', (string) $uploads['baseurl'] ), '/' ) . '/' : '';
		$url     = str_replace( '\\', '/', esc_url_raw( $url ) );
		$path    = wp_parse_url( $url, PHP_URL_PATH );

		return '' !== $baseUrl
			&& ! ( is_string( $path ) && preg_match( '#(?:^|/)\.\.(?:/|$)#', $path ) )
			&& str_starts_with( $url, $baseUrl );
	}
}
