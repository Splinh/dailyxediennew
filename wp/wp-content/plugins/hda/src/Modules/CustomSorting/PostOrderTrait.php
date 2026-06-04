<?php
/**
 * Trait for handling post order customization.
 *
 * @package HDAddons\Modules\CustomSorting
 */

namespace HDAddons\Modules\CustomSorting;

\defined( 'ABSPATH' ) || exit;

trait PostOrderTrait {

	/**
	 * Filter previous post WHERE clause.
	 *
	 * @param string $where WHERE clause.
	 *
	 * @return string Modified WHERE clause.
	 */
	public function customOrderPreviousPostWhere( string $where ): string {
		global $post;

		if ( empty( $this->orderPostType ) || ! isset( $post->post_type ) ) {
			return $where;
		}

		if ( in_array( $post->post_type, $this->orderPostType, true ) ) {
			$menuOrder = absint( $post->menu_order );
			$where     = preg_replace( "/p.post_date < '[0-9\-\s\:]+'/i", "p.menu_order < '{$menuOrder}'", $where ) ?? $where;
		}

		return $where;
	}

	// ------------------------------------------------------

	/**
	 * Filter next post WHERE clause.
	 *
	 * @param string $where WHERE clause.
	 *
	 * @return string Modified WHERE clause.
	 */
	public function customOrderNextPostWhere( string $where ): string {
		global $post;

		if ( empty( $this->orderPostType ) || ! isset( $post->post_type ) ) {
			return $where;
		}

		if ( in_array( $post->post_type, $this->orderPostType, true ) ) {
			$menuOrder = absint( $post->menu_order );
			$where     = preg_replace( "/p.post_date > '[0-9\-\s\:]+'/i", "p.menu_order > '{$menuOrder}'", $where ) ?? $where;
		}

		return $where;
	}

	// ------------------------------------------------------

	/**
	 * Filter previous post ORDER clause.
	 *
	 * @param string $orderby ORDER clause.
	 *
	 * @return string Modified ORDER clause.
	 */
	public function customOrderPreviousPostSort( string $orderby ): string {
		global $post;

		if ( empty( $this->orderPostType ) || ! isset( $post->post_type ) ) {
			return $orderby;
		}

		if ( in_array( $post->post_type, $this->orderPostType, true ) ) {
			return 'ORDER BY p.menu_order DESC LIMIT 1';
		}

		return $orderby;
	}

	// ------------------------------------------------------

	/**
	 * Filter next post ORDER clause.
	 *
	 * @param string $orderby ORDER clause.
	 *
	 * @return string Modified ORDER clause.
	 */
	public function customOrderNextPostSort( string $orderby ): string {
		global $post;

		if ( empty( $this->orderPostType ) || ! isset( $post->post_type ) ) {
			return $orderby;
		}

		if ( in_array( $post->post_type, $this->orderPostType, true ) ) {
			return 'ORDER BY p.menu_order ASC LIMIT 1';
		}

		return $orderby;
	}

	// ------------------------------------------------------

	/**
	 * Modify pre_get_posts for custom ordering.
	 *
	 * @param \WP_Query $wp_query Query object.
	 *
	 * @return void
	 */
	public function customOrderPreGetPosts( \WP_Query $wp_query ): void {
		if ( empty( $this->orderPostType ) ) {
			return;
		}

		$orderbyParam = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? '' ) );

		if ( is_admin() && ! wp_doing_ajax() ) {
			$this->handleAdminOrdering( $wp_query, $orderbyParam );
		} else {
			$this->handleFrontendOrdering( $wp_query );
		}
	}

	// ------------------------------------------------------

	/**
	 * Handle admin post ordering.
	 *
	 * @param \WP_Query $wp_query Query object.
	 * @param string $orderbyParam User-requested orderby.
	 */
	private function handleAdminOrdering( \WP_Query $wp_query, string $orderbyParam ): void {
		$postType = $wp_query->query['post_type'] ?? '';

		if ( empty( $orderbyParam ) && $postType && in_array( $postType, $this->orderPostType, true ) ) {
			if ( ! $wp_query->get( 'orderby' ) ) {
				$wp_query->set( 'orderby', 'menu_order' );
			}
			if ( ! $wp_query->get( 'order' ) ) {
				$wp_query->set( 'order', 'ASC' );
			}
		}
	}

	// ------------------------------------------------------

	/**
	 * Handle frontend post ordering.
	 *
	 * @param \WP_Query $wp_query Query object.
	 */
	private function handleFrontendOrdering( \WP_Query $wp_query ): void {
		$queryPostType = $wp_query->query['post_type'] ?? '';

		// Determine if custom ordering should apply
		$shouldApply = false;

		if ( $queryPostType ) {
			if ( ! is_array( $queryPostType ) && in_array( $queryPostType, $this->orderPostType, true ) ) {
				$shouldApply = true;
			}
		} elseif ( in_array( 'post', $this->orderPostType, true ) ) {
			$shouldApply = true;
		}

		if ( ! $shouldApply ) {
			return;
		}

		if ( isset( $wp_query->query['suppress_filters'] ) ) {
			// Only override default ordering, not explicitly requested ones
			if ( ! isset( $wp_query->query['orderby'] ) ) {
				$wp_query->set( 'orderby', 'menu_order' );
				$wp_query->set( 'order', 'ASC' );
			}
		} else {
			if ( ! $wp_query->get( 'orderby' ) ) {
				$wp_query->set( 'orderby', 'menu_order' );
			}
			if ( ! $wp_query->get( 'order' ) ) {
				$wp_query->set( 'order', 'ASC' );
			}
		}
	}
}
