<?php
/**
 * Page Cache — file-based static HTML cache.
 *
 * Stores rendered HTML as static files. On cache hit, serves the file
 * via an early ob_start() callback and exits before WordPress fully loads.
 *
 * - Caches only GET requests for logged-out visitors.
 * - Skips WooCommerce dynamic pages (cart, checkout, account).
 * - Auto-purges on post/product save and WooCommerce events.
 * - TTL: 12 hours (configurable via SPL_CACHE_TTL constant).
 *
 * @package SPL\Features\Optimizer
 */

namespace SPL\Features\Optimizer;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class PageCache {

	/** Cache directory inside uploads. */
	private const DIR = WP_CONTENT_DIR . '/cache/spl-pages';

	/** Default TTL in seconds (12 hours). */
	private const TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Register cache hooks.
	 */
	public static function register(): void {
		// Skip in development, admin, CLI, or logged-in users.
		if (
			Helper::development()
			|| is_admin()
			|| ( defined( 'WP_CLI' ) && WP_CLI )
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			|| ( defined( 'DOING_CRON' ) && DOING_CRON )
		) {
			return;
		}

		// Try serving cached page as early as possible.
		add_action( 'template_redirect', [ self::class, 'serveCached' ], 0 );

		// Start output buffering to capture the page.
		add_action( 'template_redirect', [ self::class, 'startBuffer' ], 1 );

		// Purge hooks.
		add_action( 'save_post', [ self::class, 'purgeAll' ] );
		add_action( 'woocommerce_update_product', [ self::class, 'purgeAll' ] );
		add_action( 'woocommerce_new_product', [ self::class, 'purgeAll' ] );
		add_action( 'switch_theme', [ self::class, 'purgeAll' ] );
		add_action( 'customize_save_after', [ self::class, 'purgeAll' ] );
	}

	/**
	 * Serve cached HTML if available and fresh.
	 */
	public static function serveCached(): void {
		if ( ! self::isCacheable() ) {
			return;
		}

		$file = self::cacheFile();
		if ( ! is_file( $file ) ) {
			return;
		}

		$ttl = defined( 'SPL_CACHE_TTL' ) ? (int) SPL_CACHE_TTL : self::TTL;

		// Expired?
		if ( ( time() - filemtime( $file ) ) > $ttl ) {
			@unlink( $file );
			return;
		}

		// Serve static file and exit.
		header( 'X-SPL-Cache: HIT' );
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * Start output buffering to capture the page HTML.
	 */
	public static function startBuffer(): void {
		if ( ! self::isCacheable() ) {
			return;
		}

		ob_start( [ self::class, 'saveBuffer' ] );
	}

	/**
	 * OB callback — save captured HTML to cache file.
	 *
	 * @param string $html Page HTML.
	 *
	 * @return string Unmodified HTML.
	 */
	public static function saveBuffer( string $html ): string {
		// Don't cache empty or error pages.
		if ( strlen( $html ) < 255 || http_response_code() !== 200 ) {
			return $html;
		}

		$file = self::cacheFile();
		$dir  = dirname( $file );

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Add cache timestamp comment.
		$stamp = '<!-- SPL Cache: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC -->';
		$html_with_stamp = $html . "\n" . $stamp;

		@file_put_contents( $file, $html_with_stamp, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		header( 'X-SPL-Cache: MISS' );

		return $html;
	}

	/**
	 * Purge all cached pages.
	 */
	public static function purgeAll(): void {
		$dir = self::DIR;
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $files as $file ) {
			if ( $file->isFile() ) {
				@unlink( $file->getPathname() );
			} elseif ( $file->isDir() ) {
				@rmdir( $file->getPathname() );
			}
		}
	}

	/**
	 * Check if the current request is cacheable.
	 */
	private static function isCacheable(): bool {
		// Only GET requests.
		if ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
			return false;
		}

		// Skip logged-in users.
		if ( is_user_logged_in() ) {
			return false;
		}

		// Skip if query string has cart/checkout params.
		if ( ! empty( $_GET['add-to-cart'] ) || ! empty( $_GET['removed_item'] ) ) {
			return false;
		}

		// Skip WooCommerce dynamic pages.
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			return false;
		}

		// Skip search and 404.
		if ( is_search() || is_404() ) {
			return false;
		}

		// Skip if cookie indicates a WC session.
		foreach ( array_keys( $_COOKIE ) as $cookie ) {
			if ( str_starts_with( $cookie, 'woocommerce_' ) || str_starts_with( $cookie, 'wp_woocommerce_' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the cache file path for the current URL.
	 */
	private static function cacheFile(): string {
		$host = sanitize_file_name( $_SERVER['HTTP_HOST'] ?? 'default' );
		$uri  = sanitize_file_name( trim( $_SERVER['REQUEST_URI'] ?? '/', '/' ) ) ?: 'index';

		return self::DIR . '/' . $host . '/' . $uri . '.html';
	}
}
