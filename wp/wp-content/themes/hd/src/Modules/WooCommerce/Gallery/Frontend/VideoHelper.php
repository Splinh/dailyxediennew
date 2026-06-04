<?php
/**
 * Video Helper — video detection, thumbnail extraction, and slide building.
 *
 * Centralizes all video-related logic used by gallery rendering:
 * - Video type detection (YouTube, Vimeo, MP4)
 * - Video thumbnail URL resolution (admin poster → YouTube auto-extract)
 * - Product video slide data building
 * - Video injection into image arrays
 *
 * YouTube ID extraction and thumbnail generation delegate to Helper (Embed trait)
 * to avoid duplicating parsing logic.
 *
 * @package HD\Modules\WooCommerce\Gallery\Frontend
 */

namespace HD\Modules\WooCommerce\Gallery\Frontend;

use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class VideoHelper {

	// ── Detection ───────────────────────────────────

	/**
	 * Detect video type from URL.
	 *
	 * @param string $url Video URL.
	 *
	 * @return string 'youtube'|'vimeo'|'mp4'|'iframe'
	 */
	public static function detectType( string $url ): string {
		if ( preg_match( '/youtube|youtu\.be/i', $url ) ) {
			return 'youtube';
		}

		if ( preg_match( '/vimeo/i', $url ) ) {
			return 'vimeo';
		}

		if ( preg_match( '/\.(mp4|webm)(\?|$)/i', $url ) ) {
			return 'mp4';
		}

		return 'iframe';
	}

	// ── Thumbnail ───────────────────────────────────

	/**
	 * Get video thumbnail URL (auto-extract for YouTube, admin field fallback).
	 *
	 * @param string $videoUrl  Video URL.
	 * @param int    $productId Product ID (for admin poster meta fallback).
	 *
	 * @return string Thumbnail URL or empty string.
	 */
	public static function getThumbnailUrl( string $videoUrl, int $productId ): string {
		// 1. Admin-provided poster takes priority
		$poster = get_post_meta( $productId, GalleryDataProvider::PRODUCT_VIDEO_POSTER, true );
		if ( $poster ) {
			return $poster;
		}

		// 2. Auto-extract for YouTube (hqdefault = resolution key 1)
		$thumbnail = Helper::youtubeImage( $videoUrl, 1 );
		if ( $thumbnail && Helper::pixelImg() !== $thumbnail ) {
			return $thumbnail;
		}

		return '';
	}

	// ── Slide Building ──────────────────────────────

	/**
	 * Build a video slide data array for per-product video.
	 *
	 * @param string $videoUrl   The video URL.
	 * @param array  $images     Current images array (fallback poster from first image).
	 * @param int    $productId  Product ID.
	 *
	 * @return array Video slide data.
	 */
	public static function buildSlide( string $videoUrl, array $images, int $productId ): array {
		$posterUrl = self::getThumbnailUrl( $videoUrl, $productId );
		$usePoster = ! empty( $posterUrl );

		return [
			'src'              => $usePoster ? $posterUrl : ( $images[0]['src'] ?? '' ),
			'width'            => $usePoster ? 0 : ( $images[0]['width'] ?? 0 ),
			'height'           => $usePoster ? 0 : ( $images[0]['height'] ?? 0 ),
			'thumb'            => $usePoster ? $posterUrl : ( $images[0]['thumb'] ?? '' ),
			'full'             => $usePoster ? $posterUrl : ( $images[0]['full'] ?? '' ),
			'srcset'           => '',
			'sizes'            => '',
			'alt'              => get_the_title( $productId ),
			'video'            => $videoUrl,
			'video_type'       => self::detectType( $videoUrl ),
			'is_product_video' => true,
		];
	}

	/**
	 * Inject per-product video slide into images array.
	 *
	 * @param array  $images     Image data array (modified by reference).
	 * @param string $videoUrl   Video URL.
	 * @param string $position   'first_slide'|'last_slide'|'overlay'.
	 * @param int    $productId  Product ID.
	 */
	public static function injectVideo( array &$images, string $videoUrl, string $position, int $productId ): void {
		if ( 'overlay' === $position ) {
			return; // Overlay handled in HTML, not as a slide
		}

		$videoSlide = self::buildSlide( $videoUrl, $images, $productId );

		match ( $position ) {
			'first_slide' => array_unshift( $images, $videoSlide ),
			'last_slide'  => $images[] = $videoSlide,
			default       => null,
		};
	}
}
