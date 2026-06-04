<?php
/**
 * Core Checker — verifies WordPress core file integrity.
 *
 * Fetches official checksums from the WP.org API and compares them
 * against local files to detect modifications, unknown files, and missing files.
 *
 * @package HDAddons\Modules\File\FileIntegrity
 * @author  HD
 */

namespace HDAddons\Modules\File\FileIntegrity;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class CoreChecker {

	/**
	 * WP.org checksums API endpoint.
	 */
	private const CHECKSUMS_API = 'https://api.wordpress.org/core/checksums/1.0/';

	/**
	 * Transient key for cached checksums.
	 */
	private const CACHE_KEY = 'hda_wp_core_checksums';

	/**
	 * Cache TTL in seconds (24 hours).
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Directories considered part of WP core (relative to ABSPATH).
	 */
	private const CORE_DIRS = [ 'wp-admin', 'wp-includes' ];

	// ══════════════════════════════════════════════════
	// Scanning
	// ══════════════════════════════════════════════════

	/**
	 * Run a full core integrity scan.
	 *
	 * @return array{
	 *     modified: array<string, array{expected: string, actual: string}>,
	 *     unknown: string[],
	 *     missing: string[],
	 *     checked: int,
	 *     wp_version: string,
	 *     scanned_at: string,
	 * }
	 */
	public function runScan(): array {
		$checksums = $this->getOfficialChecksums();

		if ( empty( $checksums ) ) {
			return [
				'modified'   => [],
				'unknown'    => [],
				'missing'    => [],
				'checked'    => 0,
				'wp_version' => $this->getWpVersion(),
				'scanned_at' => current_time( 'mysql' ),
				'error'      => 'Could not fetch checksums from WordPress.org API.',
			];
		}

		$modified = [];
		$missing  = [];
		$checked  = 0;

		// Check each file in the official checksums.
		foreach ( $checksums as $file => $expectedHash ) {
			$absolutePath = ABSPATH . $file;

			if ( ! file_exists( $absolutePath ) ) {
				// Skip deleted default themes and bundled plugins (commonly removed).
				if ( preg_match( '#^wp-content/themes/twenty#', $file )
					|| 'wp-content/plugins/hello.php' === $file
				) {
					continue;
				}

				$missing[] = $file;
				continue;
			}

			++$checked;
			$actualHash = md5_file( $absolutePath );

			if ( $actualHash !== $expectedHash ) {
				$modified[ $file ] = [
					'expected' => $expectedHash,
					'actual'   => $actualHash,
				];
			}
		}

		// Find unknown files in core directories.
		$unknown = $this->findUnknownFiles( $checksums );

		return [
			'modified'   => $modified,
			'unknown'    => $unknown,
			'missing'    => $missing,
			'checked'    => $checked,
			'wp_version' => $this->getWpVersion(),
			'scanned_at' => current_time( 'mysql' ),
		];
	}

	// ══════════════════════════════════════════════════
	// Checksums API
	// ══════════════════════════════════════════════════

	/**
	 * Fetch official checksums from WP.org API (cached for 24h).
	 *
	 * @return array<string, string> File path → MD5 hash.
	 */
	public function getOfficialChecksums(): array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$version = $this->getWpVersion();
		$locale  = get_locale();

		$url = add_query_arg(
			[
				'version' => $version,
				'locale'  => $locale,
			],
			self::CHECKSUMS_API
		);

		$response = wp_remote_get(
			$url,
			[
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			Helper::errorLog( '[HDA FileIntegrity] Failed to fetch checksums: ' . $response->get_error_message() );

			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return [];
		}

		try {
			$data = json_decode( $body, true, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $e ) {
			Helper::errorLog( '[HDA FileIntegrity] Invalid checksums JSON: ' . $e->getMessage() );

			return [];
		}

		$checksums = $data['checksums'] ?? [];

		if ( empty( $checksums ) ) {
			return [];
		}

		set_transient( self::CACHE_KEY, $checksums, self::CACHE_TTL );

		return $checksums;
	}

	// ══════════════════════════════════════════════════
	// Unknown file detection
	// ══════════════════════════════════════════════════

	/**
	 * Find files in core directories that are NOT in the official checksums.
	 *
	 * @param array<string, string> $checksums Official file list.
	 *
	 * @return string[] List of unknown file paths (relative to ABSPATH).
	 */
	private function findUnknownFiles( array $checksums ): array {
		$unknown  = [];
		$knownSet = array_flip( array_keys( $checksums ) );

		foreach ( self::CORE_DIRS as $dir ) {
			$dirPath = ABSPATH . $dir;
			if ( ! is_dir( $dirPath ) ) {
				continue;
			}

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dirPath, \FilesystemIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $iterator as $file ) {
				if ( ! $file->isFile() ) {
					continue;
				}

				$relativePath = $dir . '/' . $iterator->getSubPathName();
				$relativePath = str_replace( '\\', '/', $relativePath );

				if ( ! isset( $knownSet[ $relativePath ] ) ) {
					$unknown[] = $relativePath;
				}
			}
		}

		// Limit unknown files to prevent overwhelming results.
		if ( count( $unknown ) > 500 ) {
			$unknown   = array_slice( $unknown, 0, 500 );
			$unknown[] = '... (truncated, 500+ unknown files)';
		}

		return $unknown;
	}

	// --------------------------------------------------

	/**
	 * Get the current WordPress version.
	 *
	 * @return string
	 */
	private function getWpVersion(): string {
		return get_bloginfo( 'version' );
	}

	/**
	 * Clear the checksums cache (force re-fetch).
	 *
	 * @return void
	 */
	public static function clearCache(): void {
		delete_transient( self::CACHE_KEY );
	}
}
