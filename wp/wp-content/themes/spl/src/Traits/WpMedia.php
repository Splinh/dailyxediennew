<?php
/**
 * Image and attachment helper methods.
 *
 * @author HD
 */

namespace SPL\Traits;

use SPL\Core\Cache;

defined( 'ABSPATH' ) || exit;

trait WpMedia {
	private static function defaultImageAttr(): array {
		return [
			'loading' => 'lazy',
		];
	}

	// -------------------------------------------------------------

	/**
	 * @param int|\WP_Post|null $postId
	 * @param string|int[] $size
	 *
	 * @return string|false
	 */
	public static function postImageSrc( int|\WP_Post|null $postId = null, string|array $size = 'thumbnail' ): string|false {
		return get_the_post_thumbnail_url( $postId ?: null, $size );
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $attachmentId
	 * @param string|int[] $size
	 *
	 * @return string|false
	 */
	public static function attachmentImageSrc( mixed $attachmentId, string|array $size = 'thumbnail' ): string|false {
		return $attachmentId ? wp_get_attachment_image_url( (int) $attachmentId, $size ) : false;
	}

	// -------------------------------------------------------------

	/**
	 * @param int|\WP_Post|null $postId
	 * @param string|int[] $size
	 * @param string|array $attr
	 * @param bool $filter
	 *
	 * @return string
	 */
	public static function postImageHTML( int|\WP_Post|null $postId = null, string|array $size = 'post-thumbnail', string|array $attr = '', bool $filter = true ): string {
		$attr = $attr ?: self::defaultImageAttr();
		$html = get_the_post_thumbnail( $postId ?: null, $size, $attr );

		return $filter ? apply_filters( 'hd_post_image_html_filter', $html, $postId, $size, $attr ) : $html;
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $attachmentId
	 * @param string|int[] $size
	 * @param string|array $attr
	 * @param bool $filter
	 *
	 * @return string
	 */
	public static function attachmentImageHTML( mixed $attachmentId, string|array $size = 'thumbnail', string|array $attr = '', bool $filter = true ): string {
		if ( ! $attachmentId ) {
			return '';
		}

		$attr         = $attr ?: self::defaultImageAttr();
		$attachmentId = (int) $attachmentId;
		$html         = wp_get_attachment_image( $attachmentId, $size, false, $attr );

		return $filter ? apply_filters( 'hd_attachment_image_html_filter', $html, $attachmentId, $size, $attr ) : $html;
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $attachmentId
	 * @param string|int[] $size
	 * @param string|array $attr
	 * @param bool $filter
	 *
	 * @return string
	 */
	public static function iconImageHTML( mixed $attachmentId, string|array $size = 'thumbnail', string|array $attr = '', bool $filter = false ): string {
		if ( ! $attachmentId ) {
			return '';
		}

		$attachmentId = (int) $attachmentId;
		$image        = wp_get_attachment_image_src( $attachmentId, $size, true );

		if ( ! $image ) {
			return '';
		}

		[ $src, $width, $height ] = $image;

		$attachment = get_post( $attachmentId );
		$hwstring   = image_hwstring( $width, $height );

		$defaultAttr = [
			'src'     => $src,
			'alt'     => trim( wp_strip_all_tags( get_post_meta( $attachmentId, '_wp_attachment_image_alt', true ) ) ),
			'loading' => 'lazy',
		];

		$context = apply_filters( 'wp_get_attachment_image_context', 'wp_get_attachment_image' );
		$attr    = wp_parse_args( $attr, $defaultAttr );

		$loadingAttr         = [
			...$attr,
			'width'  => $width,
			'height' => $height,
		];
		$loadingOptimization = wp_get_loading_optimization_attributes( 'img', $loadingAttr, $context );
		$attr                = [ ...$attr, ...$loadingOptimization ];

		// Omit invalid decoding values
		if ( empty( $attr['decoding'] ) || ! in_array( $attr['decoding'], [ 'async', 'sync', 'auto' ], true ) ) {
			unset( $attr['decoding'] );
		}

		// Remove empty loading/fetchpriority
		if ( isset( $attr['loading'] ) && ! $attr['loading'] ) {
			unset( $attr['loading'] );
		}
		if ( isset( $attr['fetchpriority'] ) && ! $attr['fetchpriority'] ) {
			unset( $attr['fetchpriority'] );
		}

		$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment, $size );
		$attr = array_map( 'esc_attr', $attr );

		$attrString = '';
		foreach ( $attr as $name => $value ) {
			$attrString .= ' ' . esc_attr( $name ) . '="' . $value . '"';
		}

		$html = "<img $hwstring$attrString />";

		return $filter ? apply_filters( 'hd_icon_image_html_filter', $html, $attachmentId, $size, $attr ) : $html;
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $cssClass
	 * @param int|null $attachmentId
	 * @param int|null $attachmentMobileId
	 * @param string $sizeSet
	 * @param bool $filter
	 *
	 * @return string
	 */
	public static function pictureHTML(
		?int $attachmentId = 0,
		?int $attachmentMobileId = 0,
		string $sizeSet = 'thumbnail',
		?string $cssClass = null,
		?string $cssImgClass = null,
		bool $filter = true
	): string {
		if ( ! $attachmentId ) {
			return '';
		}

		$html               = $cssClass ? '<picture class="' . esc_attr( $cssClass ) . '">' : '<picture>';
		$attachmentMobileId = (int) $attachmentMobileId;
		$mobileId           = $attachmentMobileId ?: $attachmentId;

		$sources = match ( $sizeSet ) {
			'full'     => [
				[ 'full', 1920, $attachmentId ],
				[ 'widescreen', 1280, $attachmentId ],
				[ 'post-thumbnail', 1024, $attachmentId ],
				[ 'large', 768, $attachmentId ],
				[ 'medium', 640, $mobileId ],
			],
			'widescreen'     => [
				[ 'widescreen', 1280, $attachmentId ],
				[ 'post-thumbnail', 1024, $attachmentId ],
				[ 'large', 768, $attachmentId ],
				[ 'medium', 640, $mobileId ],
			],
			'post-thumbnail' => [
				[ 'post-thumbnail', 1024, $attachmentId ],
				[ 'large', 768, $attachmentId ],
				[ 'medium', 640, $mobileId ],
			],
			'large'          => [
				[ 'large', 768, $attachmentId ],
				[ 'medium', 640, $mobileId ],
			],
			'medium'         => [
				[ 'medium', 640, $mobileId ],
			],
			default          => [],
		};

		foreach ( $sources as [$size, $minWidth, $id] ) {
			$src = self::attachmentImageSrc( $id, $size );
			if ( $src ) {
				$html .= '<source srcset="' . esc_url( $src ) . '" media="(min-width: ' . $minWidth . 'px)">';
			}
		}

		$html .= self::iconImageHTML(
			$mobileId,
			'thumbnail',
			[
				'class'   => $cssImgClass ? 'lazy ' . $cssImgClass : 'lazy',
				'loading' => 'lazy',
			]
		);
		$html .= '</picture>';

		return $filter ? apply_filters( 'hd_picture_html_filter', $html, $cssClass, $attachmentId, $attachmentMobileId ) : $html;
	}

	// -------------------------------------------------------------

	/**
	 * Custom logo image — replaces WordPress get_custom_logo().
	 *
	 * Key difference from WP core:
	 * When `unlink-homepage-logo` is enabled, this method ALWAYS returns
	 * the image WITHOUT a link (<span> wrapper), regardless of page context.
	 * Link wrapping is delegated to the caller (siteTitleOrLogo / siteLogo).
	 *
	 * @param mixed $blogId
	 *
	 * @return string
	 */
	public static function customSiteLogo( mixed $blogId = 0 ): string {
		$blogId       = (int) ( $blogId ?: get_current_blog_id() );
		$html         = '';
		$switchedBlog = false;

		if ( is_multisite() && get_current_blog_id() !== $blogId ) {
			switch_to_blog( $blogId );
			$switchedBlog = true;
		}

		$customLogoId = self::getThemeMod( 'custom_logo' );

		if ( $customLogoId ) {
			$customLogoAttr = [
				'class'         => 'custom-logo',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			];

			$unlinkLogo = (bool) get_theme_support( 'custom-logo', 'unlink-homepage-logo' );
			$isHomepage = \SPL_Query::isHomeOrFrontPage() && ! is_paged();

			if ( $unlinkLogo ) {
				// When unlink is enabled, image is decorative (link handled by caller).
				$customLogoAttr['alt'] = '';
			} else {
				$imageAlt = get_post_meta( $customLogoId, '_wp_attachment_image_alt', true );
				if ( ! $imageAlt ) {
					$customLogoAttr['alt'] = get_bloginfo( 'name', 'display' );
				}
			}

			$customLogoAttr = apply_filters( 'get_custom_logo_image_attributes', $customLogoAttr, $customLogoId, $blogId );
			$image          = self::attachmentImageHTML( $customLogoId, 'full', $customLogoAttr );

			if ( $unlinkLogo ) {
				// Return unlinked image — caller (siteTitleOrLogo) handles <a> wrapper.
				$html = sprintf( '<span class="custom-logo-link">%s</span>', $image );
			} else {
				$ariaCurrent = $isHomepage ? ' aria-current="page"' : '';
				$html        = sprintf(
					'<a href="%1$s" class="custom-logo-link" rel="home"%2$s>%3$s</a>',
					self::home(),
					$ariaCurrent,
					$image
				);
			}
		} elseif ( is_customize_preview() ) {
			$html = sprintf(
				'<a href="%1$s" class="custom-logo-link" style="display:none;">%2$s</a>',
				self::home(),
				esc_html( get_bloginfo( 'name' ) )
			);
		}

		if ( $switchedBlog ) {
			restore_current_blog();
		}

		return apply_filters( 'get_custom_logo', $html, $blogId );
	}

	// -------------------------------------------------------------

	/**
	 * @param bool $output
	 * @param string|null $homeHeading
	 * @param string|null $cssClass
	 *
	 * @return string|null
	 */
	public static function siteTitleOrLogo( bool $output = true, ?string $homeHeading = 'h1', ?string $cssClass = 'logo' ): ?string {

		$logoClass = $cssClass ? ' class="' . esc_attr( $cssClass ) . '"' : '';
		$homeLink  = function_exists( 'pll_home_url' ) ? \pll_home_url() : self::home( '/' );
		$siteName  = get_bloginfo( 'name' );

		if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
			$logo = self::customSiteLogo();

			// customSiteLogo() returns <a> (when unlink disabled) or <span> (when unlink enabled).
			// Only add outer link wrapper if logo is NOT already linked — prevents nested <a>.
			if ( str_contains( $logo, '<a ' ) ) {
				$html = $logo;
			} else {
				$html = '<a' . $logoClass . ' title="' . esc_attr( $siteName ) . '" href="' . esc_url( $homeLink ) . '" rel="home">' . $logo . '</a>';
			}
		} else {
			$html        = '<a' . $logoClass . ' title="' . esc_attr( $siteName ) . '" href="' . esc_url( $homeLink ) . '" rel="home">' . esc_html( $siteName ) . '</a>';
			$description = get_bloginfo( 'description' );
			if ( $description ) {
				$html .= '<p class="site-description">' . esc_html( $description ) . '</p>';
			}
		}

		if ( $homeHeading ) {
			$isHomeOrFrontPage = \SPL_Query::isHomeOrFrontPage();
			$tag               = $isHomeOrFrontPage ? $homeHeading : 'div';
			$logoHeading       = self::getThemeMod( 'home_heading_setting' );

			if ( $logoHeading && $isHomeOrFrontPage ) {
				$html .= '<' . tag_escape( $tag ) . ' class="sr-only">' . esc_html( $logoHeading ) . '</' . tag_escape( $tag ) . '>';
			}
		}

		$html = '<div class="site-logo">' . $html . '</div>';

		if ( $output ) {
			echo wp_kses_post( $html );

			return null;
		}

		return $html;
	}

	// -------------------------------------------------------------

	/**
	 * @param string $theme - default|light|dark
	 * @param string|null $cssClass
	 *
	 * @return string
	 */
	public static function siteLogo( string $theme = 'default', ?string $cssClass = '' ): string {
		$customLogoId = null;
		$homeLink     = function_exists( 'pll_home_url' ) ? \pll_home_url() : self::home( '/' );

		if ( $theme !== 'default' ) {
			$themeLogo = self::getThemeMod( $theme . '_logo' );
			if ( $themeLogo ) {
				$cacheKey     = 'logo_id_' . md5( $themeLogo );
				$customLogoId = Cache::remember(
					$cacheKey,
					static fn() => attachment_url_to_postid( $themeLogo ) ?: 0,
					'theme_posts',
					DAY_IN_SECONDS
				);
				$customLogoId = (int) $customLogoId ?: null;
			}
		}

		if ( ! $customLogoId && has_custom_logo() ) {
			$customLogoId = self::getThemeMod( 'custom_logo' );
		}

		if ( ! $customLogoId ) {
			return '';
		}

		$imageAlt = get_post_meta( $customLogoId, '_wp_attachment_image_alt', true ) ?: get_bloginfo( 'name', 'display' );

		$customLogoAttr = [
			'class'   => $theme . '-logo',
			'loading' => 'lazy',
			'alt'     => $imageAlt,
		];

		$logo = self::attachmentImageHTML( $customLogoId, 'full', $customLogoAttr );

		if ( $cssClass ) {
			return '<div class="' . esc_attr( $cssClass ) . '"><a title="' . esc_attr( $imageAlt ) . '" href="' . esc_url( $homeLink ) . '">' . $logo . '</a></div>';
		}

		return '<a title="' . esc_attr( $imageAlt ) . '" href="' . esc_url( $homeLink ) . '">' . $logo . '</a>';
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $file
	 *
	 * @return string
	 */
	public static function sanitizeImage( mixed $file ): string {
		$mimes = [
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
			'webp'         => 'image/webp',
			'avif'         => 'image/avif',
			'tif|tiff'     => 'image/tiff',
			'ico'          => 'image/x-icon',
			'svg'          => 'image/svg+xml',
		];

		$fileExt = wp_check_filetype( $file, $mimes );

		return $fileExt['ext'] ? $file : '';
	}

	// -------------------------------------------------------------

	/**
	 * @param string $img
	 *
	 * @return string
	 */
	public static function pixelImg( string $img = '' ): string {
		return is_file( $img ) ? $img : 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
	}

	// -------------------------------------------------------------

	/**
	 * Generate placeholder image.
	 *
	 * @param string $cssClass Additional CSS class.
	 * @param bool $imgWrap Whether to wrap in img tag.
	 *
	 * @return string
	 */
	public static function placeholderSrc( string $cssClass = '', bool $imgWrap = true ): string {
		// Base64 encoded SVG placeholder (320x320 gray rectangle)
		$src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMjAgMzIwIj48cmVjdCBmaWxsPSIjZjBmMGYwIiB3aWR0aD0iMzIwIiBoZWlnaHQ9IjMyMCIvPjwvc3ZnPg==';

		if ( ! $imgWrap ) {
			return $src;
		}

		$class = 'wp-placeholder' . ( $cssClass ? ' ' . $cssClass : '' );

		return '<img width="320" height="320" loading="lazy" src="' . $src . '" alt="placeholder" class="' . esc_attr( $class ) . '">';
	}

	// -------------------------------------------------------------
}
