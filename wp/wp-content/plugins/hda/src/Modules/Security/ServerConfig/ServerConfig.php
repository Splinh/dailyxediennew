<?php
/**
 * Server Configuration Manager - manages .htaccess and Nginx config blocks.
 *
 * Supports adding/removing marker-based config blocks for both Apache (.htaccess)
 * and Nginx (standalone .conf file). Uses WordPress `insert_with_markers()` for
 * .htaccess and direct file I/O for Nginx.
 *
 * @author HD
 */

namespace HDAddons\Modules\Security\ServerConfig;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class ServerConfig {

	/**
	 * Default marker name for config blocks.
	 */
	public const MARKER        = 'HDA';
	public const XMLRPC_MARKER = 'HDA-XMLRPC';
	public const OPML_MARKER   = 'HDA-OPML';

	/**
	 * Templates directory path (relative to this file).
	 */
	private const TEMPLATES_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;

	// --------------------------------------------------
	// Internal Helpers
	// --------------------------------------------------

	/**
	 * Cached home path.
	 */
	private static ?string $homePath = null;

	/**
	 * Ensure get_home_path() is available and return the home path (cached).
	 * WP admin file.php is required for get_home_path() in AJAX/CLI contexts.
	 *
	 * @return string
	 */
	private static function ensureHomePath(): string {
		if ( null !== self::$homePath ) {
			return self::$homePath;
		}

		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		self::$homePath = get_home_path();

		return self::$homePath;
	}

	/**
	 * Strip a marker block (# BEGIN X ... # END X) from file content.
	 *
	 * @param string $content Full file content.
	 * @param string $marker  Marker name.
	 *
	 * @return string Content with the marker block removed.
	 */
	private static function stripMarkerBlock( string $content, string $marker ): string {
		$pattern = '/# BEGIN ' . preg_quote( $marker, '/' ) . '\r?\n.*?# END ' . preg_quote( $marker, '/' ) . '\r?\n*/s';

		return preg_replace( $pattern, '', $content ) ?? $content;
	}

	// --------------------------------------------------
	// Server Detection
	// --------------------------------------------------

	/**
	 * Detect the web server type.
	 *
	 * @return string 'apache', 'nginx', 'litespeed', or 'unknown'.
	 */
	public static function detectServerType(): string {
		$software = strtolower( $_SERVER['SERVER_SOFTWARE'] ?? '' );

		if ( str_contains( $software, 'apache' ) ) {
			return 'apache';
		}

		if ( str_contains( $software, 'litespeed' ) ) {
			return 'litespeed';
		}

		if ( str_contains( $software, 'nginx' ) ) {
			return 'nginx';
		}

		return 'unknown';
	}

	/**
	 * Check if server is Apache (or compatible, e.g. LiteSpeed).
	 *
	 * @return bool
	 */
	public static function isApache(): bool {
		return in_array( self::detectServerType(), [ 'apache', 'litespeed' ], true );
	}

	/**
	 * Check if server is Nginx.
	 *
	 * @return bool
	 */
	public static function isNginx(): bool {
		return self::detectServerType() === 'nginx';
	}

	/**
	 * Get human-readable server type label.
	 *
	 * @return string
	 */
	public static function getServerLabel(): string {
		return match ( self::detectServerType() ) {
			'apache'    => 'Apache',
			'litespeed' => 'LiteSpeed',
			'nginx'     => 'Nginx',
			default     => __( 'Unknown', 'hda' ),
		};
	}

	// --------------------------------------------------
	// File Paths
	// --------------------------------------------------

	/**
	 * Get the .htaccess file path.
	 * Follows WP convention: same directory as index.php.
	 *
	 * @return string
	 */
	public static function getHtaccessPath(): string {
		return self::ensureHomePath() . '.htaccess';
	}

	/**
	 * Get the Nginx conf file path.
	 * Written to home path for user to include in their server block.
	 *
	 * @return string
	 */
	public static function getNginxConfPath(): string {
		return self::ensureHomePath() . 'nginx-theme.conf';
	}

	// --------------------------------------------------
	// Template Loading
	// --------------------------------------------------

	/**
	 * Load template content from file.
	 *
	 * @param string $templateName Template filename (e.g. 'htaccess.tpl', 'nginx.conf').
	 *
	 * @return string Template content, or empty string on failure.
	 */
	public static function getTemplateContent( string $templateName ): string {
		$file = self::TEMPLATES_DIR . $templateName;

		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			Helper::errorLog( sprintf( '[HDA] ServerConfig: template not found: %s', $templateName ) );

			return '';
		}

		$content = Helper::readFile( $file );

		return $content !== false ? $content : '';
	}

	// --------------------------------------------------
	// Block Management (Apache / .htaccess)
	// --------------------------------------------------

	/**
	 * Add or update the config block in .htaccess.
	 * Always places the block at the TOP of the file (before all other rules).
	 *
	 * @param string $marker       Marker name (default: 'HDA').
	 * @param string $templateName Template file to load (default: 'htaccess.tpl').
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function addHtaccessBlock( string $marker = self::MARKER, string $templateName = 'htaccess.tpl' ): bool|string {
		$htaccessPath = self::getHtaccessPath();

		// Check directory is writable.
		$dir = dirname( $htaccessPath );
		if ( ! is_writable( $dir ) ) {
			return sprintf(
				/* translators: %s: directory path */
				__( 'Directory is not writable: %s', 'hda' ),
				$dir
			);
		}

		// Auto-unlock if file is locked.
		$wasLocked = file_exists( $htaccessPath ) && self::isFileLocked( $htaccessPath );
		if ( $wasLocked ) {
			self::unlockFile( $htaccessPath );
			clearstatcache( true, $htaccessPath );
		}

		$template = self::getTemplateContent( $templateName );
		if ( empty( $template ) ) {
			if ( $wasLocked ) {
				self::lockFile( $htaccessPath );
			}

			return sprintf(
				/* translators: %s: template name */
				__( 'Failed to load template: %s', 'hda' ),
				$templateName
			);
		}

		// Read existing content (or empty if file doesn't exist yet).
		$existing = file_exists( $htaccessPath ) ? ( Helper::readFile( $htaccessPath ) ?: '' ) : '';

		// Strip any existing block with the same marker.
		$existing = self::stripMarkerBlock( $existing, $marker );

		// Build the new block.
		$block = "# BEGIN {$marker}\n{$template}\n# END {$marker}\n";

		// Prepend block at the top.
		$newContent = $block . "\n" . ltrim( $existing );

		// Write atomically.
		$result = Helper::writeFile( $htaccessPath, $newContent );

		// Re-lock if it was locked before.
		if ( $wasLocked ) {
			self::lockFile( $htaccessPath );
		}

		if ( false === $result ) {
			return __( 'Failed to write to .htaccess file.', 'hda' );
		}

		return true;
	}

	/**
	 * Remove the config block from .htaccess.
	 *
	 * @param string $marker Marker name (default: 'HDA').
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function removeHtaccessBlock( string $marker = self::MARKER ): bool|string {
		$htaccessPath = self::getHtaccessPath();

		if ( ! file_exists( $htaccessPath ) ) {
			return true; // Nothing to remove.
		}

		// Auto-unlock if file is locked.
		$wasLocked = self::isFileLocked( $htaccessPath );
		if ( $wasLocked ) {
			self::unlockFile( $htaccessPath );
			clearstatcache( true, $htaccessPath );
		}

		$content = Helper::readFile( $htaccessPath );
		if ( false === $content ) {
			if ( $wasLocked ) {
				self::lockFile( $htaccessPath );
			}

			return __( 'Failed to read .htaccess file.', 'hda' );
		}

		// Strip the marker block.
		$newContent = self::stripMarkerBlock( $content, $marker );

		// Only write if content actually changed.
		if ( $newContent === $content ) {
			if ( $wasLocked ) {
				self::lockFile( $htaccessPath );
			}

			return true; // Block not found, nothing to do.
		}

		$result = Helper::writeFile( $htaccessPath, ltrim( $newContent ) );

		// Re-lock if it was locked before.
		if ( $wasLocked ) {
			self::lockFile( $htaccessPath );
		}

		if ( false === $result ) {
			return __( 'Failed to update .htaccess file.', 'hda' );
		}

		return true;
	}

	/**
	 * Check if a marker block exists in .htaccess.
	 *
	 * @param string $marker Marker name (default: 'HDA').
	 *
	 * @return bool
	 */
	public static function hasHtaccessBlock( string $marker = self::MARKER ): bool {
		$htaccessPath = self::getHtaccessPath();

		if ( ! file_exists( $htaccessPath ) || ! is_readable( $htaccessPath ) ) {
			return false;
		}

		$content = Helper::readFile( $htaccessPath );
		if ( false === $content ) {
			return false;
		}

		$beginMarker = "# BEGIN {$marker}";
		$endMarker   = "# END {$marker}";

		return str_contains( $content, $beginMarker ) && str_contains( $content, $endMarker );
	}

	// --------------------------------------------------
	// Block Management (Nginx)
	// --------------------------------------------------

	/**
	 * Add or update a marker-based block in the Nginx config file.
	 * Creates the file if it doesn't exist.
	 *
	 * @param string $marker       Marker name (default: 'HDA').
	 * @param string $templateName Template file to load (default: 'nginx.conf').
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function addNginxBlock( string $marker = self::MARKER, string $templateName = 'nginx.conf' ): bool|string {
		$confPath = self::getNginxConfPath();

		$dir = dirname( $confPath );
		if ( ! is_writable( $dir ) ) {
			return sprintf(
				/* translators: %s: directory path */
				__( 'Directory is not writable: %s', 'hda' ),
				$dir
			);
		}

		$template = self::getTemplateContent( $templateName );
		if ( empty( $template ) ) {
			return sprintf(
				/* translators: %s: template name */
				__( 'Failed to load template: %s', 'hda' ),
				$templateName
			);
		}

		// Read existing content (or empty if file doesn't exist yet).
		$existing = file_exists( $confPath ) ? ( Helper::readFile( $confPath ) ?: '' ) : '';

		// Strip any existing block with the same marker.
		$existing = self::stripMarkerBlock( $existing, $marker );

		// Build the new block.
		$block = "# BEGIN {$marker}\n{$template}\n# END {$marker}\n";

		// Append block at the end.
		$newContent = rtrim( $existing ) . "\n\n" . $block;
		$newContent = ltrim( $newContent );

		$result = Helper::writeFile( $confPath, $newContent );

		if ( false === $result ) {
			return sprintf(
				/* translators: %s: file path */
				__( 'Failed to write Nginx config file: %s', 'hda' ),
				$confPath
			);
		}

		return true;
	}

	/**
	 * Remove a marker-based block from the Nginx config file.
	 * Deletes the file if it becomes empty.
	 *
	 * @param string $marker Marker name (default: 'HDA').
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function removeNginxBlock( string $marker = self::MARKER ): bool|string {
		$confPath = self::getNginxConfPath();

		if ( ! file_exists( $confPath ) ) {
			return true; // Nothing to remove.
		}

		if ( ! is_writable( $confPath ) ) {
			return sprintf(
				/* translators: %s: file path */
				__( 'File is not writable: %s', 'hda' ),
				$confPath
			);
		}

		$content = Helper::readFile( $confPath );
		if ( false === $content ) {
			return __( 'Failed to read Nginx config file.', 'hda' );
		}

		$newContent = self::stripMarkerBlock( $content, $marker );

		// If file would be empty, delete it entirely.
		if ( '' === trim( $newContent ) ) {
			wp_delete_file( $confPath );

			if ( file_exists( $confPath ) ) {
				return sprintf(
					/* translators: %s: file path */
					__( 'Failed to delete Nginx config file: %s', 'hda' ),
					$confPath
				);
			}

			return true;
		}

		// Only write if content actually changed.
		if ( $newContent === $content ) {
			return true;
		}

		$result = Helper::writeFile( $confPath, ltrim( $newContent ) );

		if ( false === $result ) {
			return __( 'Failed to update Nginx config file.', 'hda' );
		}

		return true;
	}

	/**
	 * Check if the Nginx config file exists (and optionally if a specific marker block exists).
	 *
	 * @param string|null $marker Optional marker to check for. If null, checks file existence.
	 *
	 * @return bool
	 */
	public static function hasNginxBlock( ?string $marker = null ): bool {
		$confPath = self::getNginxConfPath();

		if ( ! file_exists( $confPath ) ) {
			return false;
		}

		if ( null === $marker ) {
			return true;
		}

		$content = Helper::readFile( $confPath );
		if ( false === $content ) {
			return false;
		}

		return str_contains( $content, "# BEGIN {$marker}" )
			&& str_contains( $content, "# END {$marker}" );
	}

	// --------------------------------------------------
	// Unified API
	// --------------------------------------------------

	/**
	 * Add server config block (auto-detects server type).
	 *
	 * @param string $marker       Marker name.
	 * @param string $htaccessTpl  Htaccess template filename.
	 * @param string $nginxTpl     Nginx template filename.
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function addBlock(
		string $marker = self::MARKER,
		string $htaccessTpl = 'htaccess.tpl',
		string $nginxTpl = 'nginx.conf'
	): bool|string {
		if ( self::isApache() ) {
			return self::addHtaccessBlock( $marker, $htaccessTpl );
		}

		if ( self::isNginx() ) {
			return self::addNginxBlock( $marker, $nginxTpl );
		}

		return __( 'Unsupported server type. Only Apache and Nginx are supported.', 'hda' );
	}

	/**
	 * Remove server config block (auto-detects server type).
	 *
	 * @param string $marker Marker name.
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function removeBlock( string $marker = self::MARKER ): bool|string {
		if ( self::isApache() ) {
			return self::removeHtaccessBlock( $marker );
		}

		if ( self::isNginx() ) {
			return self::removeNginxBlock( $marker );
		}

		return __( 'Unsupported server type.', 'hda' );
	}

	/**
	 * Check if the server config block exists (auto-detects server type).
	 *
	 * @param string $marker Marker name.
	 *
	 * @return bool
	 */
	public static function hasBlock( string $marker = self::MARKER ): bool {
		if ( self::isApache() ) {
			return self::hasHtaccessBlock( $marker );
		}

		if ( self::isNginx() ) {
			return self::hasNginxBlock( $marker );
		}

		return false;
	}

	// --------------------------------------------------
	// Dynamic Content API
	// --------------------------------------------------

	/**
	 * Add or update a config block in .htaccess using raw content string.
	 * For modules that generate rules dynamically (e.g. CountryBlock).
	 *
	 * @param string $marker  Marker name.
	 * @param string $content Raw content to write inside the marker block.
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function addHtaccessBlockContent( string $marker, string $content ): bool|string {
		$htaccessPath = self::getHtaccessPath();

		$dir = dirname( $htaccessPath );
		if ( ! is_writable( $dir ) ) {
			return sprintf(
				/* translators: %s: directory path */
				__( 'Directory is not writable: %s', 'hda' ),
				$dir
			);
		}

		if ( empty( trim( $content ) ) ) {
			return self::removeHtaccessBlock( $marker );
		}

		// Auto-unlock if file is locked.
		$wasLocked = file_exists( $htaccessPath ) && self::isFileLocked( $htaccessPath );
		if ( $wasLocked ) {
			self::unlockFile( $htaccessPath );
			clearstatcache( true, $htaccessPath );
		}

		// Read existing content.
		$existing = file_exists( $htaccessPath ) ? ( Helper::readFile( $htaccessPath ) ?: '' ) : '';

		// Strip any existing block with the same marker.
		$existing = self::stripMarkerBlock( $existing, $marker );

		// Build the new block.
		$block = "# BEGIN {$marker}\n{$content}\n# END {$marker}\n";

		// Prepend block at the top.
		$newContent = $block . "\n" . ltrim( $existing );

		$result = Helper::writeFile( $htaccessPath, $newContent );

		if ( $wasLocked ) {
			self::lockFile( $htaccessPath );
		}

		if ( false === $result ) {
			return __( 'Failed to write to .htaccess file.', 'hda' );
		}

		return true;
	}

	/**
	 * Add or update a marker-based block in the Nginx config using raw content string.
	 *
	 * @param string $marker  Marker name.
	 * @param string $content Raw content to write inside the marker block.
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function addNginxBlockContent( string $marker, string $content ): bool|string {
		$confPath = self::getNginxConfPath();

		$dir = dirname( $confPath );
		if ( ! is_writable( $dir ) ) {
			return sprintf(
				/* translators: %s: directory path */
				__( 'Directory is not writable: %s', 'hda' ),
				$dir
			);
		}

		if ( empty( trim( $content ) ) ) {
			return self::removeNginxBlock( $marker );
		}

		// Read existing content.
		$existing = file_exists( $confPath ) ? ( Helper::readFile( $confPath ) ?: '' ) : '';

		// Strip any existing block with the same marker.
		$existing = self::stripMarkerBlock( $existing, $marker );

		// Build the new block.
		$block = "# BEGIN {$marker}\n{$content}\n# END {$marker}\n";

		// Append block at the end.
		$newContent = rtrim( $existing ) . "\n\n" . $block;
		$newContent = ltrim( $newContent );

		$result = Helper::writeFile( $confPath, $newContent );

		if ( false === $result ) {
			return sprintf(
				/* translators: %s: file path */
				__( 'Failed to write Nginx config file: %s', 'hda' ),
				$confPath
			);
		}

		return true;
	}

	/**
	 * Add server config block with dynamic content (auto-detects server type).
	 * Use this when generating rules at runtime (e.g. country blocking).
	 *
	 * @param string $marker          Marker name.
	 * @param string $htaccessContent Content for .htaccess.
	 * @param string $nginxContent    Content for nginx conf.
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function addBlockContent(
		string $marker,
		string $htaccessContent,
		string $nginxContent
	): bool|string {
		if ( self::isApache() ) {
			return self::addHtaccessBlockContent( $marker, $htaccessContent );
		}

		if ( self::isNginx() ) {
			return self::addNginxBlockContent( $marker, $nginxContent );
		}

		return __( 'Unsupported server type. Only Apache and Nginx are supported.', 'hda' );
	}

	// --------------------------------------------------
	// File Permission Locking
	// --------------------------------------------------

	/**
	 * Read-only permission (no write for owner/group/others).
	 */
	private const PERM_LOCKED = 0444;

	/**
	 * Normal permission (owner read-write, group/others read-only).
	 */
	private const PERM_UNLOCKED = 0644;

	/**
	 * Get the list of critical file paths to protect.
	 *
	 * @return array<string, string> Label => absolute path.
	 */
	public static function getCriticalFiles(): array {
		$homePath = self::ensureHomePath();

		$files = [
			'.htaccess'          => $homePath . '.htaccess',
			'index.php'          => ABSPATH . 'index.php',
			'wp-config.php'      => self::getWpConfigPath(),
			'wp-settings.php'    => ABSPATH . 'wp-settings.php',
			'wp-load.php'        => ABSPATH . 'wp-load.php',
			'wp-blog-header.php' => ABSPATH . 'wp-blog-header.php',
			'wp-login.php'       => ABSPATH . 'wp-login.php',
		];

		// Only include files that actually exist.
		return array_filter( $files, 'file_exists' );
	}

	/**
	 * Find wp-config.php path (ABSPATH or one level up).
	 *
	 * @return string
	 */
	public static function getWpConfigPath(): string {
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			return ABSPATH . 'wp-config.php';
		}

		// WP also checks one level up.
		$parent = dirname( ABSPATH ) . DIRECTORY_SEPARATOR . 'wp-config.php';
		if ( file_exists( $parent ) ) {
			return $parent;
		}

		return ABSPATH . 'wp-config.php'; // fallback
	}

	/**
	 * Check if a file is locked (read-only).
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return bool
	 */
	public static function isFileLocked( string $path ): bool {
		if ( ! file_exists( $path ) ) {
			return false;
		}

		$perms = fileperms( $path ) & 0777;

		return ( $perms & 0222 ) === 0; // No write bits set.
	}

	/**
	 * Lock a single file (set read-only).
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function lockFile( string $path ): bool|string {
		if ( ! file_exists( $path ) ) {
			return sprintf(
				/* translators: %s: file path */
				__( 'File not found: %s', 'hda' ),
				$path
			);
		}

		clearstatcache( true, $path );

		if ( self::isFileLocked( $path ) ) {
			return true; // Already locked.
		}

		if ( ! @chmod( $path, self::PERM_LOCKED ) ) {
			return sprintf(
				/* translators: %s: file path */
				__( 'Failed to lock file: %s', 'hda' ),
				$path
			);
		}

		return true;
	}

	/**
	 * Unlock a single file (restore normal permissions).
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public static function unlockFile( string $path ): bool|string {
		if ( ! file_exists( $path ) ) {
			return true; // Nothing to unlock.
		}

		clearstatcache( true, $path );

		if ( ! self::isFileLocked( $path ) ) {
			return true; // Already unlocked.
		}

		if ( ! @chmod( $path, self::PERM_UNLOCKED ) ) {
			return sprintf(
				/* translators: %s: file path */
				__( 'Failed to unlock file: %s', 'hda' ),
				$path
			);
		}

		return true;
	}

	/**
	 * Lock all critical files.
	 *
	 * @return array<string, bool|string> Results per file label.
	 */
	public static function lockFiles(): array {
		$results = [];

		foreach ( self::getCriticalFiles() as $label => $path ) {
			$results[ $label ] = self::lockFile( $path );
		}

		return $results;
	}

	/**
	 * Unlock all critical files.
	 *
	 * @return array<string, bool|string> Results per file label.
	 */
	public static function unlockFiles(): array {
		$results = [];

		foreach ( self::getCriticalFiles() as $label => $path ) {
			$results[ $label ] = self::unlockFile( $path );
		}

		return $results;
	}

	/**
	 * Get lock status for all critical files.
	 *
	 * @return array<string, array{path: string, locked: bool, perms: string}> Status per file.
	 */
	public static function getFileLockStatus(): array {
		$status = [];

		foreach ( self::getCriticalFiles() as $label => $path ) {
			$perms            = file_exists( $path ) ? substr( sprintf( '%o', fileperms( $path ) ), -4 ) : '----';
			$status[ $label ] = [
				'path'   => $path,
				'locked' => self::isFileLocked( $path ),
				'perms'  => $perms,
			];
		}

		return $status;
	}
}
