<?php

declare( strict_types=1 );

namespace HDMU;

/**
 * Disallow search engine indexing based on DISALLOW_INDEXING constant
 */
final class DisallowIndexing {

	public static function init(): void {
		if ( ! defined( 'DISALLOW_INDEXING' ) || ! \DISALLOW_INDEXING ) {
			return;
		}

		add_filter( 'pre_option_blog_public', '__return_zero' );
		add_action( 'admin_init', self::registerAdminNotice( ... ) );
	}

	private static function registerAdminNotice(): void {
		if ( ! apply_filters( 'hdmu_disallow_indexing_notice', true ) ) {
			return;
		}

		add_action(
			'admin_notices',
			static function (): void {
				wp_admin_notice(
					esc_html__( 'Search engine indexing has been discouraged.', 'hdmu' ),
					[
						'type'               => 'warning',
						'additional_classes' => [ 'hdmu-notice' ],
					]
				);
			}
		);
	}
}
