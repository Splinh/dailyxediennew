<?php
/**
 * Image Size Configuration
 *
 * Manages custom image sizes and disables unwanted default sizes.
 *
 * @package HD\Features\Optimizer
 * @author  HD
 */

namespace HD\Features\Optimizer;

use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class ImageSize {

	/**
	 * Configure image sizes.
	 *
	 * @return void
	 */
	public static function register(): void {
		self::configureDefaultSizes();
		self::configureMediaDefaults();
		self::addCustomSizes();
		self::disableUnwantedSizes();
	}

	/** ---------------------------------------- */

	/**
	 * Configure default WordPress image sizes (one-time).
	 *
	 * @return void
	 */
	private static function configureDefaultSizes(): void {
		if ( Helper::getOption( 'hd_image_sizes_configured' ) ) {
			return;
		}

		Helper::updateOption( 'hd_image_sizes_configured', true );

		// Default thumbnail: width 480, proportional height
		Helper::updateOption( 'thumbnail_size_w', 480 );
		Helper::updateOption( 'thumbnail_size_h', 0 );
		Helper::updateOption( 'thumbnail_crop', 0 );

		// Medium size: width 768, proportional height
		Helper::updateOption( 'medium_size_w', 768 );
		Helper::updateOption( 'medium_size_h', 0 );

		// Large size: width 1024, proportional height
		Helper::updateOption( 'large_size_w', 1024 );
		Helper::updateOption( 'large_size_h', 0 );
	}

	/** ---------------------------------------- */

	/**
	 * Set media upload defaults (one-time).
	 *
	 * @return void
	 */
	private static function configureMediaDefaults(): void {
		if ( Helper::getOption( 'hd_media_defaults_configured' ) ) {
			return;
		}

		Helper::updateOption( 'hd_media_defaults_configured', true );
		Helper::updateOption( 'image_default_align', 'center' );
		Helper::updateOption( 'image_default_size', 'large' );
	}

	/** ---------------------------------------- */

	/**
	 * Add custom image sizes.
	 *
	 * @return void
	 */
	private static function addCustomSizes(): void {
		// Use proper API for post-thumbnail (WP reserved size)
		set_post_thumbnail_size( 1200, 0 );

		$sizes = [
			'small-50'   => [ 50, 0 ],
			'small-100'  => [ 100, 0 ],
			'small-150'  => [ 150, 0 ],
			'small-300'  => [ 300, 0 ],
			'widescreen' => [ 1920, 0 ],
			'og-image'   => [ 1200, 0 ],
		];

		foreach ( $sizes as $name => $config ) {
			$width  = $config[0];
			$height = $config[1];
			$crop   = $config[2] ?? false;
			add_image_size( $name, $width, $height, $crop );
		}
	}

	/** ---------------------------------------- */

	/**
	 * Disable unwanted WordPress image sizes.
	 *
	 * @return void
	 */
	private static function disableUnwantedSizes(): void {
		// Disable unwanted sizes from generation
		add_filter(
			'intermediate_image_sizes_advanced',
			static function ( array $sizes ): array {
				unset( $sizes['medium_large'], $sizes['1536x1536'], $sizes['2048x2048'] );

				return $sizes;
			}
		);

		// Disable scaled images
		add_filter( 'big_image_size_threshold', '__return_false' );
	}
}
