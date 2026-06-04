<?php
/**
 * Template Hooks — Project-Level Frontend Hooks.
 *
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  CUSTOMIZE THIS FILE PER PROJECT                             ║
 * ║                                                              ║
 * ║  This is the primary entry point for registering             ║
 * ║  project-specific frontend hooks:                            ║
 * ║  - Navigation menus                                          ║
 * ║  - <head> meta tags (viewport, theme-color)                  ║
 * ║  - Asset preloading (JS modulepreload)                       ║
 * ║  - External fonts (preconnect + enqueue)                     ║
 * ║                                                              ║
 * ║  Other Features (Admin, Customizer, Optimizer, Shortcode)    ║
 * ║  are stable and rarely need changes across projects.         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * @package HD\Features
 * @author  HD
 */

namespace HD\Features;

use HD\Contracts\Feature;
use HD\Core\Asset;
use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class TemplateHooks extends Feature {

	// ==================================================================
	// BOOT — Register all frontend hooks
	// ==================================================================

	public function boot(): void {

		// Navigation menus (called directly — boot() already runs inside after_setup_theme)
		$this->registerMenus();

		// <head> meta tags
		add_action( 'wp_head', $this->wpHeadMeta( ... ), 1 );
		add_action( 'wp_head', $this->wpHeadAssets( ... ), 97 );

		// SEO Meta
		add_filter( 'wp_robots', $this->wpRobotsPaged( ... ) );

		// External fonts & preconnect
		add_action( 'wp_enqueue_scripts', $this->enqueueExternalFonts( ... ), 999 );

		// STARTER — remove when deploying real project
		add_action( 'hd_header_action', $this->starterHeader( ... ), 10 );
		add_action( 'hd_footer_action', $this->starterFooter( ... ), 10 );
	}

	// ==================================================================
	// NAVIGATION MENUS
	// ==================================================================

	/**
	 * Register theme navigation menus.
	 *
	 * Menu locations are defined in config/settings.php ('menus' key).
	 *
	 * @return void
	 */
	public function registerMenus(): void {
		$menus = Helper::filterSettingOptions( 'menus', [] );
		if ( ! empty( $menus ) ) {
			register_nav_menus( $menus );
		}
	}

	// ==================================================================
	// HEAD META TAGS
	// ==================================================================

	/**
	 * Output base meta tags in <head>.
	 *
	 * @return void
	 */
	public function wpHeadMeta(): void {
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
		echo '<meta name="format-detection" content="telephone=no,email=no,address=no" />';
	}

	/**
	 * Output asset preloads and theme-color meta.
	 *
	 * @return void
	 */
	public function wpHeadAssets(): void {
		// Theme Color (from Customizer)
		$themeColor = Helper::getThemeMod( 'theme_color_setting' );
		if ( $themeColor ) {
			printf( '<meta name="theme-color" content="%s" />', Helper::escAttr( $themeColor ) );
		}

		// Preload JS imports (modulepreload)
		Asset::preload( 'index.js' );
	}

	/**
	 * Prevent indexing of paginated pages.
	 *
	 * @param array $robots Associative array of robots directives.
	 * @return array
	 */
	public function wpRobotsPaged( array $robots ): array {
		if ( is_paged() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}

		return $robots;
	}

	// ==================================================================
	// EXTERNAL FONTS
	// ==================================================================

	/**
	 * Register font preconnect and enqueue external fonts.
	 *
	 * Uncomment/add font enqueue calls per project.
	 *
	 * @return void
	 */
	public function enqueueExternalFonts(): void {
		// Preconnect to Google Fonts file origin
		add_filter(
			'wp_resource_hints',
			static function ( array $urls, string $relationType ): array {
				if ( 'preconnect' === $relationType ) {
					$urls[] = [
						'href'        => 'https://fonts.gstatic.com',
						'crossorigin' => 'anonymous',
					];
				}

				return $urls;
			},
			10,
			2
		);

		// ── PROJECT FONTS ──────────────────────────────────────
		// Uncomment and customize per project:
		//
		// Asset::enqueueStyle(
		//     [
		//         'handle' => 'google-fonts',
		//         'src'    => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
		//     ]
		// );
	}

	// ==================================================================
	// STARTER — Remove this entire section when deploying real project
	// ==================================================================

	/**
	 * Render starter header bar.
	 *
	 * @return void
	 */
	public function starterHeader(): void {
		get_template_part( 'template-parts/starter/header-bar' );
	}

	/**
	 * Render starter footer bar.
	 *
	 * @return void
	 */
	public function starterFooter(): void {
		get_template_part( 'template-parts/starter/footer-bar' );
	}
}
