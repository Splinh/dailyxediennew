<?php
/**
 * Theme File
 *
 * Handles theme-specific frontend functionality.
 * Responsible for theme supports, widgets, assets, and template handling.
 * Services are loaded in Bootstrap.php for separation of concerns.
 *
 * @package SPL
 */

namespace SPL;

use SPL\Core\Asset;
use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class Theme {

	/* ---------- CONSTRUCT ---------------------------------------- */

	public function __construct() {
		// Theme setup hooks.
		add_action( 'after_setup_theme', $this->setupTheme( ... ) );

		// Frontend hooks.
		add_action( 'wp_enqueue_scripts', $this->enqueueAssets( ... ) );
		add_filter( 'template_include', $this->dynamicTemplateInclude( ... ), 20 );
	}

	/* ---------- PRIVATE ------------------------------------------ */

	/**
	 * Sets up theme defaults and register support for various WordPress features.
	 *
	 * @return void
	 */
	private function setupTheme(): void {
		load_theme_textdomain( 'spl', get_template_directory() . '/languages' );

		/** Add theme support for various features. */
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script' ] );

		add_theme_support( 'align-wide' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );

		/** Enable excerpt to page, page-attributes to post */
		add_post_type_support( 'page', [ 'excerpt' ] );
		add_post_type_support( 'post', [ 'page-attributes' ] );

		/** Add support for the core custom logo. */
		add_theme_support(
			'custom-logo',
			[
				'height'               => 240,
				'width'                => 240,
				'flex-height'          => true,
				'flex-width'           => true,
				'header-text'          => '',
				'unlink-homepage-logo' => true,
			]
		);
	}

	// --------------------------------------------------

	/**
	 * Enqueue scripts and styles.
	 *
	 * @return void
	 */
	private function enqueueAssets(): void {
		$l10n = [
			'ajaxUrl'    => admin_url( 'admin-ajax.php', 'relative' ),
			'baseUrl'    => Helper::siteURL( '/' ),
			'themeUrl'   => THEME_URL,
			'restApiUrl' => RESTAPI_URL,
			'csrfToken'  => wp_create_nonce( 'wp_csrf_token' ),
			'restToken'  => wp_create_nonce( 'wp_rest' ),
			'lg'         => Helper::currentLanguage(),
			'lang'       => spl_get_js_translations(),
		];

		if ( Helper::isWoocommerceActive() ) {
			$l10n['wcAjaxUrl'] = Helper::home( '/?wc-ajax=%%endpoint%%' );
			$l10n['lang']      = [ ...$l10n['lang'], ...spl_get_wc_translations() ];
		}

		/** Inline Js */
		Asset::localize( 'jquery-core', 'splConfig', $l10n );
		Asset::inlineScript( 'jquery-core', 'Object.assign(window,{ $:jQuery,jQuery });window.hdConfig=window.hdConfig||window.splConfig||{};' );

		/**
		 * CSS - Base (all pages)
		 *
		 * tailwind.css = Tailwind CSS v4 core (separate chunk - must load first)
		 * index.css    = auto-extracted from index.js (includes main.scss via Vite CSS code-splitting)
		 * vendor.css  = 3rd party CSS (loaded via vendor.js - no PHP enqueue needed)
		 */
		Asset::enqueueCSS( 'tailwind.css' );

		/**
		 * CSS - Conditional loading (reduce unused CSS per page type)
		 *
		 * share.scss = shared sections for home & landing pages
		 * page.scss  = inner pages (breadcrumbs, pagination, singular, etc.)
		 */
		$isHomeOrLanding = is_front_page() || Helper::isPageTemplate( '/^templates\/template-/' );
		$conditionalCss  = $isHomeOrLanding ? 'share.scss' : 'page.scss';

		Asset::enqueueCSS( $conditionalCss, [ Asset::handle( 'tailwind.css' ) ] );

		/** JS */
		Asset::enqueueJS( 'preflight.js', [], null, false );
		Asset::enqueueJS( 'index.js', [ 'jquery-core' ], null, true, [ 'module', 'defer' ] );



		/** Comments */
		if ( is_singular() && comments_open() && Helper::getOption( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		/** WooCommerce — lightweight CSS + JS entry (heavy modules are lazy-loaded by DOM selectors) */
		if ( Helper::isWoocommerceActive() ) {
			Asset::enqueueCSS( 'woocommerce.scss', [ Asset::handle( $conditionalCss ) ] );
			Asset::enqueueJS( 'woocommerce.js', [ Asset::handle( 'index.js' ) ], null, true, [ 'module', 'defer' ] );
		}
	}

	// --------------------------------------------------

	/**
	 * Dynamic template include for autoloading template-specific assets.
	 *
	 * @param string $template Template file path.
	 *
	 * @return string
	 */
	private function dynamicTemplateInclude( string $template ): string {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return $template;
		}

		static $enqueuedHooks = [];

		$info      = pathinfo( $template );
		$filename  = $info['filename'];
		$extension = strtolower( $info['extension'] ?? '' );

		if ( $extension !== 'php' || ! str_starts_with( $filename, 'template-' ) ) {
			return $template;
		}

		// Remove 'template-' prefix.
		$templateSlug = substr( $filename, strlen( 'template-' ) );
		$hookName     = 'enqueue_assets_template_' . sanitize_key( str_replace( '-', '_', $templateSlug ) );

		if ( in_array( $hookName, $enqueuedHooks, true ) ) {
			return $template;
		}

		// Dynamic hook - enqueue style/script.
		add_action(
			'wp_enqueue_scripts',
			static function () use ( $templateSlug, $hookName ): void {
				Asset::enqueueCSS( "components/{$templateSlug}.scss", [ Asset::handle( 'tailwind.css' ) ] );
				Asset::enqueueJS( "components/{$templateSlug}.js", [ Asset::handle( 'index.js' ) ], null, true, [ 'module', 'defer' ] );

				// Dynamic hooks for extension.
				do_action( 'enqueue_assets_template_extra' );
				do_action( $hookName );
			},
			31
		);

		$enqueuedHooks[] = $hookName;

		return $template;
	}
}
