<?php
/**
 * Theme fonts & core CSS setup.
 *
 * Self-hosts Be Vietnam Pro woff2 (Vietnamese + Latin subsets).
 * Font files: static/fonts/ → assets/fonts/ (via Vite publicDir).
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'spl_enqueue_fonts', 5 );

/**
 * Enqueue Google Fonts Inter & Be Vietnam Pro for flawless Vietnamese typography.
 *
 * Inter font provides superior kerning and precise diacritic mark positioning for
 * Vietnamese character combinations like iệ, iế, iện without mark collision.
 */
function spl_enqueue_fonts(): void {
	wp_enqueue_style(
		'google-font-inter-vietnam',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap',
		[],
		null
	);
}

if ( ! function_exists( 'spl_theme_asset_version' ) ) {
	/**
	 * Return a cache-busting version for theme assets.
	 */
	function spl_theme_asset_version( string $relative_path ): string {
		$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );

		return is_file( $path ) ? (string) filemtime( $path ) : (string) THEME_VERSION;
	}
}

add_action( 'wp_head', 'spl_preload_assets', 1 );

/**
 * Output preload and preconnect resource hints in wp_head.
 */
function spl_preload_assets(): void {
	// 1. Preconnect to font and tracking domains.
	$preconnect_domains = [
		'https://fonts.googleapis.com',
		'https://fonts.gstatic.com',
		'https://www.googletagmanager.com',
		'https://www.google-analytics.com',
		'https://connect.facebook.net',
	];
	foreach ( $preconnect_domains as $domain ) {
		echo '<link rel="preconnect" href="' . esc_url( $domain ) . '" crossorigin>' . "\n";
		echo '<link rel="dns-prefetch" href="' . esc_url( $domain ) . '">' . "\n";
	}

	// 2. Preload first hero slide LCP image for Mobile & Desktop on homepage.
	if ( is_front_page() || is_home() ) {
		$hero_desktop = '';
		$hero_mobile  = '';

		$page_id = (int) get_option( 'page_on_front' );
		if ( $page_id && function_exists( 'get_field' ) ) {
			$slides = get_field( 'hero_slides', $page_id );
			if ( ! empty( $slides[0]['bg_image'] ) ) {
				$raw = $slides[0]['bg_image'];
				if ( is_numeric( $raw ) ) {
					$hero_desktop = wp_get_attachment_image_url( (int) $raw, 'full' ) ?: '';
				} elseif ( is_array( $raw ) && ! empty( $raw['url'] ) ) {
					$hero_desktop = $raw['url'];
				} elseif ( is_string( $raw ) ) {
					$hero_desktop = $raw;
				}
			}
			if ( ! empty( $slides[0]['bg_image_mobile'] ) ) {
				$m_raw = $slides[0]['bg_image_mobile'];
				if ( is_numeric( $m_raw ) ) {
					$hero_mobile = wp_get_attachment_image_url( (int) $m_raw, 'large' ) ?: '';
				} elseif ( is_array( $m_raw ) && ! empty( $m_raw['url'] ) ) {
					$hero_mobile = $m_raw['url'];
				} elseif ( is_string( $m_raw ) ) {
					$hero_mobile = $m_raw;
				}
			}
		}

		if ( empty( $hero_desktop ) ) {
			$hero_desktop = content_url( '/uploads/2026/06/banner-he-sang-chanh.jpg' );
		}

		// Fall back mobile image to desktop image if mobile image is not configured.
		if ( empty( $hero_mobile ) ) {
			$hero_mobile = $hero_desktop;
		}

		if ( $hero_mobile === $hero_desktop ) {
			if ( $hero_desktop ) {
				echo '<link rel="preload" href="' . esc_url( $hero_desktop ) . '" as="image" fetchpriority="high">' . "\n";
			}
		} else {
			if ( $hero_mobile ) {
				echo '<link rel="preload" href="' . esc_url( $hero_mobile ) . '" as="image" media="(max-width: 767px)" fetchpriority="high">' . "\n";
			}
			if ( $hero_desktop ) {
				echo '<link rel="preload" href="' . esc_url( $hero_desktop ) . '" as="image" media="(min-width: 768px)" fetchpriority="high">' . "\n";
			}
		}
	}
}
