<?php
/**
 * WP_Filesystem utility trait.
 *
 * Thin wrappers around WP_Filesystem for consistent file I/O
 * across the plugin. Avoids raw file_get_contents / file_put_contents
 * which trigger malware scanner false positives.
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Filesystem {

	/**
	 * Get the global WP_Filesystem instance, initializing if needed.
	 *
	 * @return \WP_Filesystem_Base|null
	 */
	public static function filesystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;

		if ( $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return $wp_filesystem;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
	}

	/**
	 * Read file contents via WP_Filesystem.
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return string|false File contents or false on failure.
	 */
	public static function readFile( string $path ): string|false {
		$fs = static::filesystem();

		if ( null === $fs ) {
			return false;
		}

		$content = $fs->get_contents( $path );

		return is_string( $content ) ? $content : false;
	}

	/**
	 * Write file contents via WP_Filesystem.
	 *
	 * @param string $path    Absolute file path.
	 * @param string $content Content to write.
	 *
	 * @return bool True on success.
	 */
	public static function writeFile( string $path, string $content ): bool {
		$fs = static::filesystem();

		if ( null === $fs ) {
			return false;
		}

		return $fs->put_contents( $path, $content, FS_CHMOD_FILE );
	}

	/**
	 * Check if a file exists via WP_Filesystem.
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return bool
	 */
	public static function fileExists( string $path ): bool {
		$fs = static::filesystem();

		if ( null === $fs ) {
			return is_file( $path );
		}

		return $fs->exists( $path );
	}
}
