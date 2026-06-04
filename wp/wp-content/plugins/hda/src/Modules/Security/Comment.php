<?php
/**
 * Comment disabling functionality.
 *
 * Completely disables WordPress comments across all post types.
 *
 * @author HD
 */

namespace HDAddons\Modules\Security;

\defined( 'ABSPATH' ) || exit;

final class Comment {

	// --------------------------------------------------

	/**
	 * Disable all comment functionality.
	 *
	 * @return void
	 */
	public function disable(): void {
		add_action( 'admin_init', $this->disableCommentsPostTypesSupport( ... ) );
		add_action( 'admin_init', $this->disableCommentsAdminMenuRedirect( ... ) );
		add_filter( 'comments_open', '__return_false', 20, 2 );
		add_filter( 'pings_open', '__return_false', 20, 2 );
		add_action( 'admin_menu', $this->disableCommentsAdminMenu( ... ) );
		add_action( 'wp_dashboard_setup', $this->disableCommentsDashboard( ... ) );
		add_action( 'wp_before_admin_bar_render', $this->removeCommentsAdminBar( ... ), 60 );
		add_filter( 'comments_template', '__return_empty_string' );
		add_action( 'pre_comment_on_post', $this->blockCommentsSubmission( ... ) );
		add_filter( 'rest_endpoints', $this->disableCommentsRestApi( ... ) );
	}

	// --------------------------------------------------

	/**
	 * Remove comments and trackbacks support from all post types.
	 *
	 * @return void
	 */
	public function disableCommentsPostTypesSupport(): void {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	// --------------------------------------------------

	/**
	 * Redirect from comments page to admin dashboard.
	 *
	 * @return void
	 */
	public function disableCommentsAdminMenuRedirect(): void {
		global $pagenow;

		if ( 'edit-comments.php' === $pagenow ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	// --------------------------------------------------

	/**
	 * Remove comments menu from admin.
	 *
	 * @return void
	 */
	public function disableCommentsAdminMenu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	// --------------------------------------------------

	/**
	 * Remove recent comments widget from dashboard.
	 *
	 * @return void
	 */
	public function disableCommentsDashboard(): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	// --------------------------------------------------

	/**
	 * Remove comments from admin bar.
	 *
	 * @return void
	 */
	public function removeCommentsAdminBar(): void {
		global $wp_admin_bar;

		if ( is_admin_bar_showing() ) {
			$wp_admin_bar->remove_menu( 'comments' );
		}
	}

	// --------------------------------------------------

	/**
	 * Block comment form submissions.
	 *
	 * @return void
	 */
	public function blockCommentsSubmission(): void {
		wp_die( __( 'Comments are closed.', 'hda' ) );
	}

	// --------------------------------------------------

	/**
	 * Disable comments REST API endpoint.
	 *
	 * @param array $endpoints REST API endpoints.
	 *
	 * @return array Modified endpoints.
	 */
	public function disableCommentsRestApi( array $endpoints ): array {
		unset( $endpoints['/wp/v2/comments'] );

		return $endpoints;
	}
}
