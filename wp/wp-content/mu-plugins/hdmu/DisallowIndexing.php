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

		// Enforce X-Robots-Tag HTTP header on all responses
		add_action(
			'send_headers',
			static function (): void {
				if ( ! headers_sent() ) {
					header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
				}
			}
		);

		// Core WordPress robots meta filter
		add_filter(
			'wp_robots',
			static function ( array $robots ): array {
				$robots['noindex']   = true;
				$robots['nofollow']  = true;
				$robots['noarchive'] = true;
				unset( $robots['index'], $robots['follow'] );

				return $robots;
			},
			PHP_INT_MAX
		);

		// Rank Math robots meta filter
		add_filter(
			'rank_math/frontend/robots',
			static function ( array $robots ): array {
				$robots['index']     = 'noindex';
				$robots['follow']    = 'nofollow';
				$robots['noarchive'] = 'noarchive';

				return $robots;
			},
			PHP_INT_MAX
		);

		// Disable Rank Math XML sitemaps when indexing is disallowed
		add_filter( 'rank_math/sitemap/enable', '__return_false' );
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
