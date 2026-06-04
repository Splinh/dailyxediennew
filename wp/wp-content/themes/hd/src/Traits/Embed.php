<?php
/**
 * Embed Trait
 *
 * Provides static methods for YouTube embeds, safe mailto links,
 * SVG sanitization, and FAQ schema generation.
 *
 * @package HD\Traits
 * @author  HD
 */

namespace HD\Traits;

defined( 'ABSPATH' ) || exit;

trait Embed {

	/**
	 * @return array
	 */
	public static function ksesSVG(): array {
		return [
			'svg'            => [
				'xmlns'               => true,
				'viewbox'             => true,
				'width'               => true,
				'height'              => true,
				'fill'                => true,
				'stroke'              => true,
				'stroke-width'        => true,
				'stroke-linecap'      => true,
				'stroke-linejoin'     => true,
				'stroke-miterlimit'   => true,
				'stroke-dasharray'    => true,
				'stroke-dashoffset'   => true,
				'fill-rule'           => true,
				'clip-rule'           => true,
				'preserveaspectratio' => true,
				'aria-hidden'         => true,
				'role'                => true,
				'focusable'           => true,
				'id'                  => true,
				'class'               => true,
				'style'               => true,
			],
			'g'              => [
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'clip-path'    => true,
				'transform'    => true,
				'id'           => true,
				'class'        => true,
				'style'        => true,
			],
			'path'           => [
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'fill-rule'       => true,
				'clip-rule'       => true,
				'vector-effect'   => true,
				'transform'       => true,
				'opacity'         => true,
				'id'              => true,
				'class'           => true,
				'style'           => true,
			],
			'circle'         => [
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'ellipse'        => [
				'cx'           => true,
				'cy'           => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'rect'           => [
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'line'           => [
				'x1'           => true,
				'y1'           => true,
				'x2'           => true,
				'y2'           => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'polyline'       => [
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'polygon'        => [
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'opacity'      => true,
				'id'           => true,
				'class'        => true,
			],
			'defs'           => [],
			'symbol'         => [
				'id'                  => true,
				'viewbox'             => true,
				'preserveaspectratio' => true,
			],
			'use'            => [
				'href'       => true,
				'xlink:href' => true,
				'x'          => true,
				'y'          => true,
				'width'      => true,
				'height'     => true,
				'id'         => true,
				'class'      => true,
			],
			'clippath'       => [ 'id' => true ],
			'mask'           => [
				'id'               => true,
				'x'                => true,
				'y'                => true,
				'width'            => true,
				'height'           => true,
				'maskunits'        => true,
				'maskcontentunits' => true,
			],
			'lineargradient' => [
				'id'                => true,
				'x1'                => true,
				'y1'                => true,
				'x2'                => true,
				'y2'                => true,
				'gradientunits'     => true,
				'gradienttransform' => true,
			],
			'radialgradient' => [
				'id'                => true,
				'cx'                => true,
				'cy'                => true,
				'r'                 => true,
				'fx'                => true,
				'fy'                => true,
				'gradientunits'     => true,
				'gradienttransform' => true,
			],
			'stop'           => [
				'offset'       => true,
				'stop-color'   => true,
				'stop-opacity' => true,
			],
			'title'          => [],
			'desc'           => [],
		];
	}

	// --------------------------------------------------

	/**
	 * Extract YouTube video ID from URL.
	 *
	 * Handles: ?v=, youtu.be/, /embed/, /shorts/, /v/ with 11-char validation.
	 *
	 * @param string $url YouTube URL.
	 *
	 * @return string|null Video ID or null.
	 */
	public static function youtubeId( string $url ): ?string {
		if ( ! $url ) {
			return null;
		}

		$parsed = wp_parse_url( $url );
		$host   = strtolower( $parsed['host'] ?? '' );

		if ( ! preg_match( '/(?:^|\.)youtube\.com$|^youtu\.be$/', $host ) ) {
			return null;
		}

		// 1. Standard watch URL: parse query params (handles ?v=ID, ?reload=9&v=ID, etc.)
		if ( ! empty( $parsed['query'] ) ) {
			parse_str( $parsed['query'], $params );
			if ( ! empty( $params['v'] ) && preg_match( '/^[a-zA-Z0-9_-]{11}$/', $params['v'] ) ) {
				return $params['v'];
			}
		}

		// 2. youtu.be, /embed/ID, /shorts/ID, /v/ID
		if ( preg_match( '/(?:youtu\.be\/|\/(?:embed|shorts)\/|\/v\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
			return $m[1];
		}

		return null;
	}

	// --------------------------------------------------

	/**
	 * @param string $url
	 * @param int $resolutionKey
	 *
	 * @return string
	 */
	public static function youtubeImage( string $url, int $resolutionKey = 0 ): string {
		$videoId = self::youtubeId( $url );
		if ( ! $videoId ) {
			return self::pixelImg();
		}

		$resolutions = [ 'sddefault', 'hqdefault', 'mqdefault', 'default', 'maxresdefault' ];
		$resKey      = $resolutions[ max( 0, min( $resolutionKey, count( $resolutions ) - 1 ) ) ];

		return 'https://img.youtube.com/vi/' . $videoId . '/' . $resKey . '.jpg';
	}

	// --------------------------------------------------

	/**
	 * @param string $url
	 * @param int $autoplay
	 * @param bool $lazyload
	 * @param bool $control
	 *
	 * @return string|null
	 */
	public static function youtubeIframe( string $url, int $autoplay = 0, bool $lazyload = true, bool $control = true ): ?string {
		$videoId = self::youtubeId( $url );
		if ( ! $videoId ) {
			return null;
		}

		$home            = esc_url( trailingslashit( network_home_url() ) );
		$allowAttributes = 'accelerometer; encrypted-media; gyroscope; picture-in-picture';
		$src             = "https://www.youtube.com/embed/{$videoId}?origin={$home}";

		if ( $autoplay ) {
			$allowAttributes .= '; autoplay';
			$src             .= '&autoplay=1';
		}

		if ( ! $control ) {
			$src .= '&modestbranding=1&controls=0&rel=0&version=3&loop=1&enablejsapi=1&iv_load_policy=3&playlist=' . $videoId;
		}

		$src .= '&html5=1';

		return sprintf(
			'<iframe title="YouTube Video Player" width="800" height="450" allow="%1$s" allowfullscreen%2$s src="%3$s" style="border:0"></iframe>',
			$allowAttributes,
			$lazyload ? ' loading="lazy"' : '',
			esc_url( $src )
		);
	}

	// --------------------------------------------------

	/**
	 * @param string $email
	 * @param string $title
	 * @param array|string $attributes
	 *
	 * @return string|null
	 */
	public static function safeMailTo( string $email, string $title = '', array|string $attributes = '' ): ?string {
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return null;
		}

		$title        = $title ?: $email;
		$encodedEmail = self::encodeChars( $email );
		$encodedTitle = self::encodeChars( $title );

		// Handle attributes
		$attrString = '';
		if ( is_array( $attributes ) ) {
			foreach ( $attributes as $key => $val ) {
				$key = strtolower( trim( $key ) );
				if ( ! self::isSafeAttrName( $key ) ) {
					continue;
				}
				$attrString .= ' ' . $key . '="' . esc_attr( $val ) . '"';
			}
		} elseif ( is_string( $attributes ) && $attributes !== '' ) {
			// Parse "class=\"x\" rel=\"nofollow\"" into proper key-value pairs
			if ( preg_match_all( '/([\w-]+)\s*=\s*["\']([^"\']*)["\']/', $attributes, $attrMatches, PREG_SET_ORDER ) ) {
				foreach ( $attrMatches as $m ) {
					$key = strtolower( trim( $m[1] ) );
					if ( ! self::isSafeAttrName( $key ) ) {
						continue;
					}
					$attrString .= ' ' . $key . '="' . esc_attr( $m[2] ) . '"';
				}
			}
		}

		return '<a href="mailto:' . $encodedEmail . '"' . $attrString . '>' . $encodedTitle . '</a>';
	}

	// --------------------------------------------------

	/**
	 * Check if an HTML attribute name is safe.
	 *
	 * Rejects event handlers (on*) and URL-bearing attributes.
	 *
	 * @param string $name Lowercased attribute name.
	 *
	 * @return bool
	 */
	private static function isSafeAttrName( string $name ): bool {
		if ( preg_match( '/^on/', $name ) ) {
			return false;
		}

		if ( in_array( $name, [ 'href', 'src', 'action', 'formaction' ], true ) ) {
			return false;
		}

		return (bool) preg_match( '/^[a-z][a-z0-9-]*$/', $name );
	}

	// --------------------------------------------------

	private static function encodeChars( string $str ): string {
		$encoded = '';
		$len     = mb_strlen( $str, 'UTF-8' );

		for ( $i = 0; $i < $len; $i++ ) {
			$char     = mb_substr( $str, $i, 1, 'UTF-8' );
			$encoded .= '&#' . mb_ord( $char, 'UTF-8' ) . ';';
		}

		return $encoded;
	}
}
