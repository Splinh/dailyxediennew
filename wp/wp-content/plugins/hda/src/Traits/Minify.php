<?php
/**
 * Minification utility trait.
 *
 * @author HD
 */

namespace HDAddons\Traits;

use MatthiasMullie\Minify as MinifyLib;

\defined( 'ABSPATH' ) || exit;

trait Minify {

	/** Max input size for extraction methods (5MB). */
	private static int $maxExtractSize = 5_242_880;

	// --------------------------------------------------

	/**
	 * Extract and filter JavaScript content with size limits.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function extractJS( string $content ): string {
		// Limit input size to prevent ReDoS (5MB max).
		if ( strlen( $content ) > self::$maxExtractSize ) {
			self::errorLog( '[extractJS] Content too large, truncated to 5MB' );
			$content = substr( $content, 0, self::$maxExtractSize );
		}

		$maliciousPatterns = [
			'/\beval\s*\(/i',
			'/\bdocument\.write\s*\(/i',
			'/;base64,/i',
		];

		// Single-pass: keep each script in its original position,
		// remove only those that match malicious patterns.
		return preg_replace_callback(
			'/<script\b[^>]*>(.*?)<\/script>/is',
			static function ( array $match ) use ( $maliciousPatterns ): string {
				$scriptContent = trim( $match[1] ?? '' );
				$hasSrc        = str_contains( $match[0], 'src=' );

				// External scripts (with src) are always kept.
				if ( $hasSrc || $scriptContent === '' ) {
					return $match[0];
				}

				foreach ( $maliciousPatterns as $pattern ) {
					if ( preg_match( $pattern, $scriptContent ) ) {
						return '';
					}
				}

				return $match[0];
			},
			$content
		) ?? $content;
	}

	// --------------------------------------------------

	/**
	 * Extract and sanitize CSS content with size limits.
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	public static function extractCss( string $css ): string {
		if ( ! $css ) {
			return '';
		}

		// Limit input size to prevent memory exhaustion (5MB max)
		if ( strlen( $css ) > self::$maxExtractSize ) {
			self::errorLog( '[extractCss] CSS content too large, truncated to 5MB' );
			$css = substr( $css, 0, self::$maxExtractSize );
		}

		// Convert encoding to UTF-8 if needed
		if ( mb_detect_encoding( $css, 'UTF-8', true ) !== 'UTF-8' ) {
			$css = mb_convert_encoding( $css, 'UTF-8', 'auto' );
		}

		// Log if dangerous content is detected
		if ( str_contains( $css, '<script' ) ) {
			self::errorLog( 'Warning: `<script>` tag detected inside CSS' );
		}

		return trim(
			preg_replace(
				[
					'/<script\b[^>]*>.*?(?:<\/script>|$)/is',
					'/<style\b[^>]*>(.*?)<\/style>/is',
					'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
					'/\bexpression\s*\([^)]*\)/i',
					'/\bjavascript:/i',
					'/[^\S\r\n\t]+/',
				],
				[ '', '$1', '', '', '', ' ' ],
				$css
			) ?? ''
		);
	}

	// --------------------------------------------------

	/**
	 * Minify JavaScript content.
	 *
	 * @param string|null $js
	 * @param bool $respectDebug
	 *
	 * @return string|null
	 */
	public static function jsMinify( ?string $js, bool $respectDebug = true ): ?string {
		if ( ! $js ) {
			return null;
		}

		if ( $respectDebug && self::development() ) {
			return $js;
		}

		return class_exists( MinifyLib\JS::class )
			? ( new MinifyLib\JS() )->add( $js )->minify()
			: $js;
	}

	// --------------------------------------------------

	/**
	 * Minify CSS content.
	 *
	 * @param string|null $css
	 * @param bool $respectDebug
	 *
	 * @return string|null
	 */
	public static function cssMinify( ?string $css, bool $respectDebug = true ): ?string {
		if ( ! $css ) {
			return null;
		}

		if ( $respectDebug && self::development() ) {
			return $css;
		}

		return class_exists( MinifyLib\CSS::class )
			? ( new MinifyLib\CSS() )->add( $css )->minify()
			: $css;
	}
}
