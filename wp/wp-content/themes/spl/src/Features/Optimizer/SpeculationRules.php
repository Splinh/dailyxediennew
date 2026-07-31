<?php
/**
 * Speculation Rules — Instant page prerender/prefetch via browser API.
 *
 * Injects a `<script type="speculationrules">` into `<head>` that tells
 * Chromium 121+ to prerender internal links on hover/pointerdown.
 * Browsers without support silently ignore the unknown script type.
 *
 * Works synergistically with PageCache: prerendered pages hit the
 * static HTML cache (~50 ms TTFB), so the background tab loads instantly.
 *
 * @package SPL\Features\Optimizer
 * @see     https://developer.chrome.com/docs/web-platform/speculation-rules-api
 */

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

final class SpeculationRules {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		// Skip admin, AJAX, REST, CLI — speculation rules are frontend-only.
		if (
			is_admin()
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( defined( 'WP_CLI' ) && WP_CLI )
		) {
			return;
		}

		add_action( 'wp_head', [ self::class, 'render' ], 99 );
	}

	/**
	 * Output the speculation rules JSON block in <head>.
	 *
	 * Only renders for non-logged-in users to avoid prerendering
	 * personalised pages (cart, account, admin bar, etc.).
	 */
	public static function render(): void {
		if ( is_user_logged_in() ) {
			return;
		}

		$rules = [
			'prerender' => [
				[
					'where' => [
						'and' => [
							[ 'href_matches' => '/*' ],
							[
								'not' => [
									'or' => self::exclusions(),
								],
							],
						],
					],
					'eagerness' => 'moderate',
				],
			],
		];

		printf(
			'<script type="speculationrules">%s</script>' . "\n",
			wp_json_encode( $rules, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}

	/**
	 * URL patterns to exclude from prerender/prefetch.
	 *
	 * @return array<array{href_matches: string}>
	 */
	private static function exclusions(): array {
		$patterns = [
			'/wp-admin/*',
			'/wp-login.php',
			'/cart/*',
			'/checkout/*',
			'/my-account/*',
			'/*?*(add-to-cart|removed_item)=*',
			'/*?*action=logout*',
		];

		// File extensions that should never be prerendered.
		$file_exts = [ 'pdf', 'zip', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'csv' ];
		foreach ( $file_exts as $ext ) {
			$patterns[] = '/*.' . $ext;
		}

		return array_map(
			static fn( string $p ): array => [ 'href_matches' => $p ],
			$patterns
		);
	}
}
