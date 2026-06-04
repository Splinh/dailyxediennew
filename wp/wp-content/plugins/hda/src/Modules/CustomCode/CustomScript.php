<?php
/**
 * Custom Script — outputs user-defined scripts to header/footer.
 *
 * Utility class for CustomCodeModule. Not a module itself.
 *
 * @package HDAddons\Modules\CustomCode
 */

namespace HDAddons\Modules\CustomCode;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class CustomScript {

	// ─── Option Keys (single source of truth) ───────────
	// Each key is both the storage key AND the form field name.

	public const KEY_HEADER      = 'html_header';
	public const KEY_FOOTER      = 'html_footer';
	public const KEY_BODY_TOP    = 'html_body_top';
	public const KEY_BODY_BOTTOM = 'html_body_bottom';

	/** All storage keys grouped for iteration */
	public const STORAGE_KEYS = [
		'header'      => self::KEY_HEADER,
		'footer'      => self::KEY_FOOTER,
		'body_top'    => self::KEY_BODY_TOP,
		'body_bottom' => self::KEY_BODY_BOTTOM,
	];

	private const CONTENT_TYPE_RAW_HTML = 'hda_raw_html';

	// ------------------------------------------------------

	/**
	 * Initialize custom script hooks.
	 */
	public function __construct() {
		// Header scripts (inside <head>)
		add_action( 'wp_head', $this->headerScripts( ... ), 99 );

		// Body scripts - right after <body> opens
		add_action( 'wp_body_open', $this->bodyScriptsTop( ... ), 99 );

		// Footer scripts - before </body>
		add_action( 'wp_footer', $this->footerScripts( ... ), 99 );
		add_action( 'wp_footer', $this->bodyScriptsBottom( ... ), PHP_INT_MAX );
	}

	// ------------------------------------------------------

	/**
	 * Output header scripts inside <head> tag.
	 *
	 * @return void
	 */
	public function headerScripts(): void {
		$this->outputScript( self::STORAGE_KEYS['header'] );
	}

	/**
	 * Output scripts right after <body> tag opens.
	 *
	 * @return void
	 */
	public function bodyScriptsTop(): void {
		$this->outputScript( self::STORAGE_KEYS['body_top'] );
	}

	/**
	 * Output footer scripts.
	 *
	 * @return void
	 */
	public function footerScripts(): void {
		$this->outputScript( self::STORAGE_KEYS['footer'] );
	}

	/**
	 * Output scripts just before </body> closes.
	 *
	 * @return void
	 */
	public function bodyScriptsBottom(): void {
		$this->outputScript( self::STORAGE_KEYS['body_bottom'] );
	}

	// ------------------------------------------------------

	/**
	 * Output script content if not running Lighthouse.
	 *
	 * Note: Content is not minified as it may contain mixed HTML/JS
	 * that shouldn't be processed by JS minifier.
	 *
	 * @param string $storageKey Storage key identifier.
	 *
	 * @return void
	 */
	private function outputScript( string $storageKey ): void {
		// Skip scripts during Lighthouse audits
		if ( Helper::lightHouse() ) {
			return;
		}

		$content = Helper::getStoredOptionContent( $storageKey );

		if ( empty( $content ) ) {
			return;
		}

		echo $content;
	}

	// ── HasSettings ──────────────────────────────────────


	public static function saveSettings( array $data ): void {
		foreach ( self::STORAGE_KEYS as $field ) {
			$value  = $data[ $field ] ?? '';
			$result = Helper::updateStoredOption( $field, $value, self::CONTENT_TYPE_RAW_HTML );

			if ( is_wp_error( $result ) ) {
				throw new \RuntimeException( $result->get_error_message() );
			}
		}
	}
}
