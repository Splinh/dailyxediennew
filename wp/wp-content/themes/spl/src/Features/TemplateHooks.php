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
 * @package SPL\Features
 * @author  HD
 */

namespace SPL\Features;

use SPL\Contracts\Feature;
use SPL\Core\Asset;
use SPL\Core\Helper;

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
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n";
		echo '<meta name="format-detection" content="telephone=no,email=no,address=no" />' . "\n";

		// Output meta description for SEO score
		if ( is_front_page() || is_home() ) {
			$desc = get_bloginfo( 'description' ) ?: 'Đại Lý Xe Điện - Hệ thống phân phối xe đạp điện, xe máy điện chính hãng hàng đầu Việt Nam.';
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );
		}
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
			printf( '<meta name="theme-color" content="%s" />' . "\n", Helper::escAttr( $themeColor ) );
		}

		// Preload JS imports (modulepreload)
		Asset::preload( 'index.js' );

		// Preload LCP hero banner image on homepage for 90+ Mobile PageSpeed score.
		if ( is_front_page() || is_home() ) {
			self::preloadHeroBanner();
		}
	}

	/**
	 * Preload the first slide LCP image for desktop & mobile viewports.
	 *
	 * @return void
	 */
	private static function preloadHeroBanner(): void {
		$home_id    = (int) get_option( 'page_on_front' );
		$sections   = $home_id ? get_field( 'home_sections', $home_id ) : null;
		$hero_slide = null;

		if ( is_array( $sections ) ) {
			foreach ( $sections as $s ) {
				if ( ( $s['acf_fc_layout'] ?? '' ) === 'hero_slider' && ! empty( $s['slides'][0] ) ) {
					$hero_slide = $s['slides'][0];
					break;
				}
			}
		}

		$img_raw    = $hero_slide['bg_image'] ?? 0;
		$mobile_raw = $hero_slide['bg_image_mobile'] ?? 0;

		$desktop_url = '';
		if ( is_numeric( $img_raw ) && (int) $img_raw > 0 ) {
			$desktop_url = wp_get_attachment_image_url( (int) $img_raw, 'full' );
		} elseif ( is_array( $img_raw ) && ! empty( $img_raw['url'] ) ) {
			$desktop_url = $img_raw['url'];
		} elseif ( is_string( $img_raw ) && ! empty( $img_raw ) ) {
			$desktop_url = $img_raw;
		}

		if ( empty( $desktop_url ) ) {
			$desktop_url = content_url( '/uploads/2026/06/banner-he-sang-chanh.jpg' );
		}

		$mobile_url = '';
		if ( is_numeric( $mobile_raw ) && (int) $mobile_raw > 0 ) {
			$mobile_url = wp_get_attachment_image_url( (int) $mobile_raw, 'large' );
		} elseif ( is_array( $mobile_raw ) && ! empty( $mobile_raw['url'] ) ) {
			$mobile_url = $mobile_raw['url'];
		} elseif ( is_string( $mobile_raw ) && ! empty( $mobile_raw ) ) {
			$mobile_url = $mobile_raw;
		}

		if ( empty( $mobile_url ) ) {
			if ( is_numeric( $img_raw ) && (int) $img_raw > 0 ) {
				$mobile_url = wp_get_attachment_image_url( (int) $img_raw, 'medium_large' );
			} elseif ( is_array( $img_raw ) && ! empty( $img_raw['sizes']['medium_large'] ) ) {
				$mobile_url = $img_raw['sizes']['medium_large'];
			} else {
				$mobile_url = $desktop_url;
			}
		}

		if ( $mobile_url ) {
			printf(
				'<link rel="preload" as="image" href="%s" media="(max-width: 767px)" fetchpriority="high" />' . "\n",
				esc_url( $mobile_url )
			);
		}
		if ( $desktop_url ) {
			printf(
				'<link rel="preload" as="image" href="%s" media="(min-width: 768px)" fetchpriority="high" />' . "\n",
				esc_url( $desktop_url )
			);
		}
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
