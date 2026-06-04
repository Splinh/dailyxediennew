<?php
/**
 * SVG sanitization + Icon rendering utility trait.
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Svg {

	/**
	 * Sanitize SVG content.
	 *
	 * @param string|null $svg
	 *
	 * @return string
	 */
	public static function ksesSvg( ?string $svg ): string {
		if ( ! $svg ) {
			return '';
		}

		return wp_kses( $svg, self::svgAllowedTags() );
	}

	/**
	 * Get allowed SVG tags and attributes.
	 *
	 * @return array
	 */
	public static function svgAllowedTags(): array {
		$commonAttrs = [
			'class'        => true,
			'id'           => true,
			'style'        => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
			'transform'    => true,
		];

		return [
			'svg'      => [
				...$commonAttrs,
				'xmlns'           => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'aria-hidden'     => true,
				'role'            => true,
				'focusable'       => true,
			],
			'g'        => [
				...$commonAttrs,
				'clip-path' => true,
			],
			'path'     => [
				...$commonAttrs,
				'd'               => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'fill-rule'       => true,
				'clip-rule'       => true,
			],
			'circle'   => [
				...$commonAttrs,
				'cx' => true,
				'cy' => true,
				'r'  => true,
			],
			'ellipse'  => [
				...$commonAttrs,
				'cx' => true,
				'cy' => true,
				'rx' => true,
				'ry' => true,
			],
			'rect'     => [
				...$commonAttrs,
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'ry'     => true,
			],
			'line'     => [
				...$commonAttrs,
				'x1' => true,
				'y1' => true,
				'x2' => true,
				'y2' => true,
			],
			'polyline' => [
				...$commonAttrs,
				'points' => true,
			],
			'polygon'  => [
				...$commonAttrs,
				'points' => true,
			],
			'defs'     => [],
			'clipPath' => [ 'id' => true ],
			'use'      => [
				'href'       => true,
				'xlink:href' => true,
				'x'          => true,
				'y'          => true,
				'width'      => true,
				'height'     => true,
				'class'      => true,
				'id'         => true,
				'style'      => true,
			],
			'symbol'   => [
				'id'      => true,
				'viewbox' => true,
				'class'   => true,
			],
			'title'    => [],
			'desc'     => [],
			'i'        => [
				'class' => true,
				'id'    => true,
				'style' => true,
			],
		];
	}

	// ── Icon Rendering ────────────────────────────

	/**
	 * Render icon from various formats.
	 *
	 * Supports: Attachment ID, URL, SVG string, icon class, data URI.
	 *
	 * @param string|int $icon     Icon value.
	 * @param string     $name     Name for alt text.
	 * @param string     $cssClass Optional CSS class.
	 * @param int        $size     Optional size in pixels.
	 *
	 * @return string Rendered HTML.
	 */
	public static function renderIcon( string|int $icon, string $name = '', string $cssClass = '', int $size = 32 ): string {
		if ( empty( $icon ) ) {
			return '';
		}

		// Attachment ID — get image from media library.
		if ( is_numeric( $icon ) ) {
			$attachmentId = absint( $icon );
			$imageUrl     = wp_get_attachment_image_url( $attachmentId, 'thumbnail' );

			if ( $imageUrl ) {
				// For SVG attachments, load file content instead of using URL.
				$mimeType = get_post_mime_type( $attachmentId );
				if ( 'image/svg+xml' === $mimeType ) {
					$svgPath = get_attached_file( $attachmentId );

					if ( $svgPath && is_file( $svgPath ) && filesize( $svgPath ) < 50000 ) {
						$svgContent = self::readFile( $svgPath );
						if ( $svgContent ) {
							return self::addSvgClass( self::ksesSvg( $svgContent ), $cssClass );
						}
					}
				}

				// Regular image or large SVG — use URL.
				return sprintf(
					'<img width="%d" height="%d" src="%s" alt="%s"%s>',
					$size,
					$size,
					esc_url( $imageUrl ),
					esc_attr( $name ? "{$name}-icon" : 'icon' ),
					! empty( $cssClass ) ? ' class="' . esc_attr( $cssClass ) . '"' : ''
				);
			}

			return '';
		}

		// URL or data URI — render as image.
		if ( self::isUrl( $icon ) || str_starts_with( $icon, 'data:' ) ) {
			return sprintf(
				'<img width="%d" height="%d" src="%s" alt="%s"%s>',
				$size,
				$size,
				esc_url( $icon ),
				esc_attr( $name ? "{$name}-icon" : 'icon' ),
				! empty( $cssClass ) ? ' class="' . esc_attr( $cssClass ) . '"' : ''
			);
		}

		// Inline SVG — sanitize and add optional class.
		if ( str_starts_with( $icon, '<svg' ) ) {
			return self::addSvgClass( self::ksesSvg( $icon ), $cssClass );
		}

		// <i> tag (already rendered icon class) — sanitize.
		if ( str_starts_with( $icon, '<i' ) ) {
			return self::ksesSvg( $icon );
		}

		// Icon class (e.g., FontAwesome) — render as <i> element.
		$classes = trim( $icon . ' ' . $cssClass );

		return sprintf( '<i class="%s"></i>', esc_attr( $classes ) );
	}

	/**
	 * Add CSS class to sanitized SVG markup.
	 *
	 * @param string $svg      Sanitized SVG string.
	 * @param string $cssClass CSS class to add.
	 *
	 * @return string SVG with class attribute.
	 */
	private static function addSvgClass( string $svg, string $cssClass ): string {
		if ( ! empty( $cssClass ) && ! str_contains( $svg, 'class=' ) ) {
			return str_replace( '<svg', '<svg class="' . esc_attr( $cssClass ) . '"', $svg );
		}

		return $svg;
	}
}
