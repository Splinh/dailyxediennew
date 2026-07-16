<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

/**
 * SEO & Robots.txt Customizations.
 *
 * Configures search engine crawl rules dynamically, bypassing database
 * dependencies. Consolidates crawler behaviors for Rank Math.
 *
 * @package SPL
 */
final class SEO {

	public static function register(): void {
		add_filter( 'robots_txt', [ self::class, 'customRobotsRules' ], 99, 2 );
	}

	/**
	 * Programmatically define crawling rules for the dynamic robots.txt file.
	 *
	 * @param string $output Existing robots.txt contents.
	 * @param bool   $public True if the site is public, false otherwise.
	 * @return string Modified robots.txt contents.
	 */
	public static function customRobotsRules( string $output, bool $public ): string {
		if ( ! $public ) {
			return $output;
		}

		$rules = [
			'User-agent: *',
			'Disallow: /wp-admin/',
			'Allow: /wp-admin/admin-ajax.php',
			'Disallow: /checkout/',
			'Disallow: /cart/',
			'Disallow: /my-account/',
			'Disallow: /*?add-to-cart=*',
			'Disallow: /*?nocache=*',
			'Disallow: /*?s=*',
			'Disallow: /search/',
			'',
			'Sitemap: ' . esc_url( home_url( '/sitemap_index.xml' ) ),
		];

		return implode( "\n", $rules ) . "\n";
	}
}
