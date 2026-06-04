<?php
/**
 * Custom CSS — outputs user-defined CSS to frontend.
 *
 * Utility class for CustomCodeModule. Not a module itself.
 *
 * @package HDAddons\Modules\CustomCode
 */

namespace HDAddons\Modules\CustomCode;

use HDAddons\Asset;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class CustomCss {

	// ─── Option Keys (single source of truth) ───────────

	public const OPTION_NAME = 'hda_css';

	/** HTML form field name used by the handler */
	public const KEY_FORM_CSS = 'html_custom_css';

	/**
	 * Default style handle for inline CSS.
	 */
	private const DEFAULT_STYLE_HANDLE = 'index-css';

	// ------------------------------------------------------

	/**
	 * Initialize custom CSS output.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', $this->enqueueInlineCustomCss( ... ), 99 );
	}

	// ------------------------------------------------------

	/**
	 * Enqueue minified custom CSS as inline style.
	 *
	 * @return void
	 */
	public function enqueueInlineCustomCss(): void {
		if ( Helper::development() ) {
			$css = Helper::getStoredOptionContent( self::OPTION_NAME );
		} else {
			$css = Helper::getStoredOptionContent( self::OPTION_NAME . '_minified' );

			if ( empty( $css ) ) {
				// Fallback if minified version is missing
				$raw = Helper::getStoredOptionContent( self::OPTION_NAME );
				$css = Helper::cssMinify( $raw, false );
			}
		}

		if ( empty( $css ) ) {
			return;
		}

		/**
		 * Filter the style handle for custom CSS.
		 *
		 * @param string $handle The style handle to attach inline CSS to.
		 */
		$handle = apply_filters( 'hda_custom_css_handle', self::DEFAULT_STYLE_HANDLE );

		Asset::inlineStyle( $handle, $css );
	}

	// ── HasSettings ──────────────────────────────────────


	public static function saveSettings( array $data ): void {
		$rawCss = $data[ self::KEY_FORM_CSS ] ?? '';
		Helper::updateStoredOption( self::OPTION_NAME, $rawCss, 'text/css' );

		$minified = Helper::cssMinify( $rawCss, false );
		Helper::updateStoredOption( self::OPTION_NAME . '_minified', (string) $minified, 'text/css' );
	}
}
