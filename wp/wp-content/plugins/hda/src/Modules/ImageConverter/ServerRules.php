<?php
/**
 * Server Rewrite Rules for Image Converter.
 *
 * Handles Apache/LiteSpeed .htaccess and Nginx config generation
 * for serving AVIF/WebP images instead of originals when:
 * 1) Browser supports the format (Accept header)
 * 2) The converted file exists in the output directory
 *
 * Uses the root .htaccess (via ServerConfig) for Apache/LiteSpeed,
 * matching the approach used by webp-converter-for-media.
 *
 * @package HDAddons\Modules\ImageConverter
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter;

use HDAddons\Helper;
use HDAddons\Modules\Security\ServerConfig\ServerConfig;

\defined( 'ABSPATH' ) || exit;

final class ServerRules {

	/**
	 * Marker for the config block.
	 */
	private const MARKER = 'HDA-IMGCONV';

	// ─── Public API ─────────────────────────────────────

	/**
	 * Apply or remove rewrite rules based on settings.
	 *
	 * Called on settings save and batch completion.
	 *
	 * @param bool $enabled Whether server rules are enabled.
	 *
	 * @return void
	 */
	public static function apply( bool $enabled ): void {
		try {
			if ( $enabled ) {
				self::addRules();
			} else {
				self::removeRules();
			}
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA ImgConv] ServerRules error: ' . $e->getMessage() );
		}
	}

	/**
	 * Get the current status of server rules.
	 *
	 * @return array{
	 *     server_type: string,
	 *     server_label: string,
	 *     supports_htaccess: bool,
	 *     is_active: bool,
	 *     htaccess_path: string,
	 *     nginx_snippet: string,
	 * }
	 */
	public static function getStatus(): array {
		$serverType       = ServerConfig::detectServerType();
		$supportsHtaccess = \in_array( $serverType, [ 'apache', 'litespeed' ], true );

		return [
			'server_type'       => $serverType,
			'server_label'      => ServerConfig::getServerLabel(),
			'supports_htaccess' => $supportsHtaccess,
			'is_active'         => ServerConfig::hasBlock( self::MARKER ),
			'htaccess_path'     => ServerConfig::getHtaccessPath(),
			'nginx_snippet'     => ! $supportsHtaccess ? self::generateNginxSnippet() : '',
		];
	}

	// ─── Add / Remove rules ─────────────────────────────

	/**
	 * Add rewrite rules using the unified ServerConfig API.
	 *
	 * @return void
	 */
	private static function addRules(): void {
		$htaccessContent = self::generateHtaccessRules();
		$nginxContent    = self::generateNginxSnippet();

		$result = ServerConfig::addBlockContent( self::MARKER, $htaccessContent, $nginxContent );

		if ( is_string( $result ) ) {
			Helper::errorLog( '[HDA ImgConv] ServerRules: ' . $result );
		}
	}

	/**
	 * Remove rewrite rules.
	 *
	 * @return void
	 */
	private static function removeRules(): void {
		$result = ServerConfig::removeBlock( self::MARKER );

		if ( is_string( $result ) ) {
			Helper::errorLog( '[HDA ImgConv] ServerRules remove: ' . $result );
		}
	}

	// ─── Apache .htaccess ───────────────────────────────

	/**
	 * Generate Apache/LiteSpeed .htaccess rewrite rules.
	 *
	 * Placed in root .htaccess via ServerConfig.
	 * Two-layer content negotiation:
	 *
	 * 1) In-place: theme/plugin assets where .avif/.webp sits alongside
	 *    the original (e.g., assets/img/bg.png + bg.png.avif).
	 *    Works for CSS background-image and <img> src without code changes.
	 *
	 * 2) Uploads: converted files in a sibling _avif/_webp directory
	 *    (e.g., uploads/2026/04/photo.jpg → uploads_avif/2026/04/photo.jpg.avif).
	 *
	 * Priority: AVIF > WebP > Original (when both formats are available).
	 * No conflict: uploads don't have in-place files, assets don't have _avif dirs.
	 *
	 * @return string
	 */
	private static function generateHtaccessRules(): string {
		$format = ImageConverter::getFormat();

		if ( $format === Converter::FORMAT_NONE ) {
			return '';
		}

		$htaccessPath = self::getUploadsRelative( true );
		$docRootPath  = self::getUploadsRelative( false );

		$mimeType = $format === 'avif' ? 'image/avif' : 'image/webp';
		$ext      = $format;

		$rules = [];

		// ── MIME types ──
		$rules[] = '<IfModule mod_mime.c>';
		$rules[] = 'AddType image/avif .avif';
		$rules[] = 'AddType image/webp .webp';
		$rules[] = '</IfModule>';
		$rules[] = '';

		$rules[] = '<IfModule mod_rewrite.c>';
		$rules[] = 'RewriteEngine On';
		$rules[] = '';

		// ── Layer 1: In-place (theme/plugin assets) ──
		// Checks if original.avif or original.webp exists alongside the original file.
		$rules[] = '# In-place: serve .' . $ext . ' when file exists alongside original (theme/plugin assets)';
		$rules[] = 'RewriteCond %{REQUEST_URI} \\.(jpe?g|png|gif)$ [NC]';
		$rules[] = 'RewriteCond %{HTTP_ACCEPT} ' . $mimeType;
		$rules[] = 'RewriteCond %{REQUEST_FILENAME}.' . $ext . ' -f';
		$rules[] = 'RewriteRule ^(.+)\\.(jpe?g|png|gif)$ $1.$2.' . $ext . ' [T=' . $mimeType . ',E=IMGCONV:1,L]';
		$rules[] = '';

		// ── Layer 2: Uploads (separate directory) ──
		$rules[] = '# Uploads: serve from _' . $ext . '/ sibling directory';
		$rules[] = 'RewriteCond %{HTTP_ACCEPT} ' . $mimeType;
		$rules[] = 'RewriteCond %{DOCUMENT_ROOT}/' . $docRootPath . '_' . $ext . '/$1.$2.' . $ext . ' -f';
		$rules[] = 'RewriteRule ^/?' . preg_quote( $htaccessPath, '/' ) . '/(.*)\\.(jpe?g|png|gif|bmp|tiff?)$ /' . $docRootPath . '_' . $ext . '/$1.$2.' . $ext . ' [T=' . $mimeType . ',E=IMGCONV:1,L]';
		$rules[] = '</IfModule>';
		$rules[] = '';

		// ── Vary header for proper CDN/proxy caching ──
		$rules[] = '<IfModule mod_headers.c>';
		$rules[] = '<FilesMatch "\\.(jpe?g|png|gif)$">';
		$rules[] = 'Header append Vary Accept';
		$rules[] = '</FilesMatch>';
		$rules[] = 'Header append Vary Accept env=IMGCONV';
		$rules[] = '</IfModule>';

		return implode( "\n", $rules );
	}

	/**
	 * Get uploads path relative to .htaccess or document root.
	 *
	 * @param bool $relativeToHtaccess True to return path relative to .htaccess, false for DOCUMENT_ROOT.
	 *
	 * @return string e.g., "wp/wp-content/uploads" or "wp-content/uploads"
	 */
	private static function getUploadsRelative( bool $relativeToHtaccess = false ): string {
		$uploadDir = wp_upload_dir();
		$basedir   = wp_normalize_path( $uploadDir['basedir'] );

		if ( $relativeToHtaccess ) {
			if ( ! function_exists( 'get_home_path' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$basePath = wp_normalize_path( get_home_path() );
		} else {
			$basePath = wp_normalize_path( $_SERVER['DOCUMENT_ROOT'] ?? ABSPATH );
		}

		$basePath = rtrim( $basePath, '/' );
		$relative = str_replace( $basePath, '', $basedir );

		return ltrim( $relative, '/' );
	}

	// ─── Nginx Snippet ──────────────────────────────────

	/**
	 * Generate Nginx config snippet for user to add manually.
	 *
	 * @return string
	 */
	private static function generateNginxSnippet(): string {
		$format = ImageConverter::getFormat();

		if ( $format === Converter::FORMAT_NONE ) {
			return '';
		}

		$uploadsRelative = self::getUploadsRelative( false );
		$ext             = $format;
		$mimeType        = $format === 'avif' ? 'image/avif' : 'image/webp';

		$snippet = [];

		// ── Layer 1: In-place (theme/plugin assets) ──
		$snippet[] = '# HDA Image Converter — In-place ' . strtoupper( $ext ) . ' (theme/plugin assets)';
		$snippet[] = 'map $http_accept $hda_' . $ext . '_suffix {';
		$snippet[] = '    default "";';
		$snippet[] = '    "~' . $mimeType . '" ".' . $ext . '";';
		$snippet[] = '}';
		$snippet[] = 'location ~* \.(jpe?g|png|gif)$ {';
		$snippet[] = '    add_header Vary Accept always;';
		$snippet[] = '    try_files $uri$hda_' . $ext . '_suffix $uri =404;';
		$snippet[] = '}';
		$snippet[] = '';

		// ── Layer 2: Uploads (separate directory) ──
		$snippet[] = '# HDA Image Converter — Uploads ' . strtoupper( $ext ) . ' rewrite';
		$snippet[] = 'location ~* /' . $uploadsRelative . '/(.+)\.(jpe?g|png|gif|bmp|tiff?)$ {';
		$snippet[] = '    set $hda_imgconv "";';
		$snippet[] = '';
		$snippet[] = '    if ($http_accept ~* "' . $mimeType . '") {';
		$snippet[] = '        set $hda_imgconv "A";';
		$snippet[] = '    }';
		$snippet[] = '    if (-f $document_root/' . $uploadsRelative . '_' . $ext . '/$1.$2.' . $ext . ') {';
		$snippet[] = '        set $hda_imgconv "${hda_imgconv}B";';
		$snippet[] = '    }';
		$snippet[] = '    if ($hda_imgconv = "AB") {';
		$snippet[] = '        rewrite ^/' . $uploadsRelative . '/(.+\\.(?:jpe?g|png|gif|bmp|tiff?))$ /' . $uploadsRelative . '_' . $ext . '/$1.' . $ext . ' last;';
		$snippet[] = '    }';
		$snippet[] = '}';
		$snippet[] = '';
		$snippet[] = '# Serve converted files with correct Content-Type and caching';
		$snippet[] = 'location ~* /' . $uploadsRelative . '_' . $ext . '/ {';
		$snippet[] = '    add_header Content-Type ' . $mimeType . ' always;';
		$snippet[] = '    add_header Vary Accept always;';
		$snippet[] = '    expires max;';
		$snippet[] = '    access_log off;';
		$snippet[] = '}';

		return implode( "\n", $snippet );
	}
}
