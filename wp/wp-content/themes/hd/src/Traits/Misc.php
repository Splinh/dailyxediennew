<?php
/**
 * Miscellaneous utility trait.
 *
 * Provides environment checks, plugin detection, admin notices,
 * file operations, navigation menus, microdata, and other utilities.
 *
 * Merged from: Base, File, WpMisc, WpNavigation
 *
 * @package HD\Traits
 */

namespace HD\Traits;

use HD\Support\Libraries\CSS;
use HD\Support\NavWalker\HorizontalNavWalker;
use HD\Support\NavWalker\VerticalNavWalker;

defined( 'ABSPATH' ) || exit;

trait Misc {

	// --------------------------------------------------
	// ENVIRONMENT & PLUGIN CHECKS (from Base)
	// --------------------------------------------------

	/**
	 * Check if running in development mode.
	 *
	 * @return bool
	 */
	public static function development(): bool {
		return wp_get_environment_type() === 'development' || ( defined( 'WP_DEBUG' ) && \WP_DEBUG === true );
	}

	// --------------------------------------------------

	/**
	 * Render admin notice.
	 *
	 * @param string $msg Notice message.
	 * @param string $cssClass CSS class for notice.
	 *
	 * @return void
	 */
	private static function renderNotice( string $msg, string $cssClass ): void {
		printf(
			'<div class="%1$s"><p><strong>%2$s</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%3$s</span></button></div>',
			esc_attr( $cssClass ),
			esc_html( $msg ),
			esc_html__( 'Dismiss this notice.', 'hd' )
		);
	}

	// --------------------------------------------------

	/**
	 * Display success notice.
	 *
	 * @param string $msg Message text.
	 * @param bool $autoHide Whether to auto-hide.
	 *
	 * @return void
	 */
	public static function messageSuccess( string $msg = 'Values saved', bool $autoHide = false ): void {
		$class = 'notice notice-success is-dismissible' . ( $autoHide ? ' dismissible-auto' : '' );
		self::renderNotice( $msg, $class );
	}

	// --------------------------------------------------

	/**
	 * Display error notice.
	 *
	 * @param string $msg Message text.
	 * @param bool $autoHide Whether to auto-hide.
	 *
	 * @return void
	 */
	public static function messageError( string $msg = 'Values error', bool $autoHide = false ): void {
		$class = 'notice notice-error is-dismissible' . ( $autoHide ? ' dismissible-auto' : '' );
		self::renderNotice( $msg, $class );
	}

	// --------------------------------------------------

	/**
	 * Throttled error logging with a 1-minute throttle per unique message.
	 *
	 * @param string $message Error message.
	 * @param int $type Error type.
	 * @param string|null $dest Destination.
	 * @param string|null $headers Headers.
	 *
	 * @return void
	 */
	public static function errorLog( string $message, int $type = 0, ?string $dest = null, ?string $headers = null ): void {
		$key = 'hdf_err_' . md5( $message );

		// Throttle: skip if same message was logged within the last minute.
		if ( wp_cache_get( $key, 'hdf_error_log_cache' ) ) {
			return;
		}

		wp_cache_set( $key, 1, 'hdf_error_log_cache', MINUTE_IN_SECONDS );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging utility.
		error_log( $message, $type, $dest, $headers );
	}

	// --------------------------------------------------

	/**
	 * Check if value is empty (handles strings properly).
	 *
	 * @param mixed $value Value to check.
	 *
	 * @return bool
	 */
	public static function isEmpty( mixed $value ): bool {
		if ( is_string( $value ) ) {
			return trim( $value ) === '';
		}

		return ! is_numeric( $value ) && ! is_bool( $value ) && empty( $value );
	}

	// --------------------------------------------------

	/**
	 * Check if the current page is using a specific page template.
	 *
	 * @param string|null $template Template file path or regex pattern.
	 *                              If null, returns true if any custom template is used.
	 *                              If starts with '/', treated as regex pattern.
	 *
	 * @return bool
	 */
	public static function isPageTemplate( ?string $template = null ): bool {
		$templateSlug = get_page_template_slug();
		if ( ! $templateSlug ) {
			return false;
		}

		// No template specified - just check if any custom template is used
		if ( $template === null ) {
			return true;
		}

		// If template starts with '/', treat as regex pattern
		if ( str_starts_with( $template, '/' ) ) {
			return (bool) preg_match( $template, $templateSlug );
		}

		// Exact match
		return $templateSlug === $template;
	}

	// --------------------------------------------------

	/**
	 * Check if on login page.
	 *
	 * @return bool
	 */
	public static function isLogin(): bool {
		$pagenow = $GLOBALS['pagenow'] ?? null;

		return $pagenow && in_array( $pagenow, [ 'wp-login.php', 'wp-register.php' ], true );
	}

	// --------------------------------------------------

	/**
	 * Ensure plugin functions are loaded.
	 *
	 * @return void
	 */
	private static function ensurePluginFunctions(): void {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	// --------------------------------------------------

	/**
	 * Check if a plugin is active.
	 *
	 * @param string $pluginFile Plugin file path.
	 *
	 * @return bool
	 */
	public static function checkPluginActive( string $pluginFile ): bool {
		// Ensure plugin functions are loaded first
		self::ensurePluginFunctions();

		if ( is_multisite() && is_plugin_active_for_network( $pluginFile ) ) {
			return true;
		}

		return is_plugin_active( $pluginFile );
	}

	// --------------------------------------------------

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function isWoocommerceActive(): bool {
		static $active = null;

		return $active ??= function_exists( 'WC' )
				|| class_exists( 'WooCommerce' )
				|| self::checkPluginActive( 'woocommerce/woocommerce.php' );
	}

	// --------------------------------------------------

	/**
	 * Check if ACF Pro or Secure Custom Fields (SCF) is active.
	 *
	 * @return bool
	 */
	public static function isAcfActive(): bool {
		static $active = null;

		return $active ??= defined( 'ACF_PRO' )
				|| class_exists( 'acf_pro' )
				|| function_exists( 'get_field' )
				|| self::checkPluginActive( 'advanced-custom-fields-pro/acf.php' )
				|| self::checkPluginActive( 'advanced-custom-fields/acf.php' )
				|| self::checkPluginActive( 'secure-custom-fields/secure-custom-fields.php' );
	}

	// --------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isRankMathActive(): bool {
		static $active = null;

		return $active ??= class_exists( 'RankMath' )
				|| self::checkPluginActive( 'seo-by-rank-math/rank-math.php' );
	}

	// --------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isCf7Active(): bool {
		static $active = null;

		return $active ??= defined( 'WPCF7_PLUGIN_BASENAME' )
				|| class_exists( 'WPCF7' )
				|| self::checkPluginActive( 'contact-form-7/wp-contact-form-7.php' );
	}

	// --------------------------------------------------
	// FILE SYSTEM UTILITIES (from File)
	// --------------------------------------------------

	/**
	 * @return \WP_Filesystem_Base|null
	 */
	private static function wpFileSystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 *
	 * @return string|null
	 */
	public static function fileRead( string $path ): ?string {
		$fs = self::wpFileSystem();

		if ( ! $fs ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file fallback when WP_Filesystem unavailable.
			return is_file( $path ) ? file_get_contents( $path ) : null;
		}

		return $fs->is_file( $path ) ? $fs->get_contents( $path ) : null;
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 * @param string $content
	 * @param bool $lock
	 *
	 * @return bool
	 */
	public static function fileWrite( string $path, string $content, bool $lock = false ): bool {
		$fs = self::wpFileSystem();
		if ( $fs ) {
			return $fs->put_contents( $path, $content, FS_CHMOD_FILE );
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions
		if ( ! $lock ) {
			return (bool) file_put_contents( $path, $content );
		}

		$fp = fopen( $path, 'cb' );
		if ( ! $fp ) {
			return false;
		}

		flock( $fp, LOCK_EX );
		ftruncate( $fp, 0 ); // Clear existing content to avoid leftover data
		fwrite( $fp, $content );
		fflush( $fp );
		flock( $fp, LOCK_UN );
		fclose( $fp );

		return true;
		// phpcs:enable
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	public static function deleteFile( string $path ): bool {
		$fs = self::wpFileSystem();
		if ( $fs ) {
			return $fs->exists( $path ) && $fs->delete( $path, false, 'f' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Local file fallback when WP_Filesystem unavailable.
		return is_file( $path ) && unlink( $path );
	}

	// --------------------------------------------------
	// WordPress Misc Utilities (from WpMisc)
	// --------------------------------------------------

	/**
	 * @param string|int $action
	 * @param string $name
	 * @param bool $referer
	 * @param bool $display
	 *
	 * @return string|null
	 */
	public static function csrfToken( string|int $action = - 1, string $name = '_csrf_token', bool $referer = false, bool $display = false ): ?string {
		$name       = esc_attr( $name );
		$token      = wp_create_nonce( $action );
		$nonceField = '<input type="hidden" id="' . wp_generate_password( 10, false ) . '" name="' . $name . '" value="' . esc_attr( $token ) . '" />';

		if ( $referer ) {
			$nonceField .= wp_referer_field( false );
		}

		if ( $display ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe: composed from wp_create_nonce + esc_attr.
			echo $nonceField;

			return null;
		}

		return $nonceField;
	}

	// --------------------------------------------------

	/**
	 * @param string $tag
	 * @param array $atts
	 * @param string|null $content
	 *
	 * @return mixed
	 */
	public static function doShortcode( string $tag, array $atts = [], ?string $content = null ): mixed {
		global $shortcode_tags;

		$callback = $shortcode_tags[ $tag ] ?? null;

		if ( ! $callback ) {
			return false;
		}

		try {
			return $callback( $atts, $content, $tag );
		} catch ( \Throwable $e ) {
			self::errorLog( '[Shortcode error] ' . $e->getMessage() );

			return false;
		}
	}

	// --------------------------------------------------

	/**
	 * @param array|null $arrParsed
	 * @param string $tag
	 * @param string $handle
	 *
	 * @return string
	 */
	public static function lazyScriptTag( ?array $arrParsed, string $tag, string $handle ): string {
		if ( $arrParsed === null ) {
			return $tag;
		}

		foreach ( $arrParsed as $str => $value ) {
			if ( ! str_contains( $handle, $str ) ) {
				continue;
			}

			if ( $value === 'defer' ) {
				return preg_replace( [ '/\s+defer\s+/', '/\s+src=/' ], [ ' ', ' defer src=' ], $tag );
			}

			if ( $value === 'delay' && ! is_admin() ) {
				return preg_replace( [ '/\s+defer\s+/', '/\s+src=/' ], [ ' ', ' defer data-type="lazy" data-src=' ], $tag );
			}
		}

		return $tag;
	}

	// --------------------------------------------------

	/**
	 * @param array|null $arrStyles
	 * @param string $html
	 * @param string $handle
	 *
	 * @return string
	 */
	public static function lazyStyleTag( ?array $arrStyles, string $html, string $handle ): string {
		if ( $arrStyles === null ) {
			return $html;
		}

		foreach ( $arrStyles as $style ) {
			if ( ! str_contains( $handle, $style ) ) {
				continue;
			}

			$attrs = [
				'id'    => '',
				'href'  => '',
				'type'  => 'text/css',
				'media' => 'all',
			];

			foreach ( array_keys( $attrs ) as $key ) {
				if ( preg_match( '/' . $key . '=[\'"]([^\'"]+)[\'"]/', $html, $m ) ) {
					$attrs[ $key ] = esc_attr( $m[1] );
				}
			}

			return sprintf(
				"<link rel='preload' id='%s' href='%s' as='style' type='%s' onload=\"this.rel='stylesheet'\">",
				$attrs['id'],
				$attrs['href'],
				$attrs['type']
			);
		}

		return $html;
	}

	// --------------------------------------------------

	/**
	 * @param string $postType
	 * @param string|null $option
	 *
	 * @return array
	 */
	public static function getAspectRatioOption( string $postType = '', ?string $option = '' ): array {
		return self::parseAspectRatio( $postType, $option );
	}

	// --------------------------------------------------

	/**
	 * @param string $postType
	 * @param string $defaultValue
	 *
	 * @return string
	 */
	public static function aspectRatioClass( string $postType = 'post', string $defaultValue = 'as-3-2' ): string {
		$ratio  = self::parseAspectRatio( $postType );
		$ratioX = $ratio[0] ?? '';
		$ratioY = $ratio[1] ?? '';

		return ( $ratioX && $ratioY ) ? "as-{$ratioX}-{$ratioY}" : $defaultValue;
	}

	// --------------------------------------------------

	/**
	 * @param string $postType
	 * @param string|null $option
	 * @param string $defaultValue
	 *
	 * @return object
	 */
	public static function getAspectRatio( string $postType = 'post', ?string $option = '', string $defaultValue = 'as-3-2' ): object {
		$ratio  = self::parseAspectRatio( $postType, $option );
		$ratioX = $ratio[0] ?? '';
		$ratioY = $ratio[1] ?? '';

		$ratioStyle = '';
		if ( ! $ratioX || ! $ratioY ) {
			$ratioClass = $defaultValue;
		} else {
			$ratioClass           = "as-{$ratioX}-{$ratioY}";
			$arSettings           = self::filterSettingOptions( 'aspect_ratio' );
			$arAspectRatioDefault = $arSettings['aspect_ratio_default'] ?? [];

			if ( is_array( $arAspectRatioDefault ) && ! in_array( "{$ratioX}-{$ratioY}", $arAspectRatioDefault, true ) ) {
				$css = new CSS();
				$css->setSelector( '.' . $ratioClass );
				$css->addProperty( 'aspect-ratio', "{$ratioX}/{$ratioY}" );

				$ratioStyle = $css->cssOutput();
			}
		}

		return (object) [
			'class' => $ratioClass,
			'style' => $ratioStyle,
		];
	}

	/**
	 * @return array{0:string,1:string}|array{}
	 */
	private static function parseAspectRatio( string $postType = '', ?string $option = '' ): array {
		$postType = $postType ?: 'post';
		$option   = $option ?: 'aspect_ratio__options';

		$options = self::getOption( $option );
		if ( ! is_array( $options ) ) {
			return [];
		}

		$width  = (string) ( $options[ 'as-' . $postType . '-width' ] ?? '' );
		$height = (string) ( $options[ 'as-' . $postType . '-height' ] ?? '' );

		return ( '' !== $width && '' !== $height ) ? [ $width, $height ] : [];
	}

	// --------------------------------------------------

	/**
	 * Get any necessary microdata.
	 *
	 * @param string|null $context The element to target.
	 *
	 * @return string Our final attribute to add to the element.
	 */
	public static function microdata( ?string $context ): string {
		$data = match ( $context ) {
			'body'                          => self::getBodyMicrodata(),
			'header'                        => 'itemtype="https://schema.org/WPHeader" itemscope',
			'navigation'                    => 'itemtype="https://schema.org/SiteNavigationElement" itemscope',
			'article'                       => 'itemtype="https://schema.org/CreativeWork" itemscope',
			'product'                       => 'itemtype="https://schema.org/Product" itemscope',
			'post-author', 'comment-author' => 'itemprop="author" itemtype="https://schema.org/Person" itemscope',
			'comment-body'                  => 'itemtype="https://schema.org/Comment" itemscope',
			'sidebar'                       => 'itemtype="https://schema.org/WPSideBar" itemscope',
			'footer'                        => 'itemtype="https://schema.org/WPFooter" itemscope',
			'headline'                      => 'itemprop="headline"',
			'url'                           => 'itemprop="url"',
			'name'                          => 'itemprop="name"',
			'review'                        => 'itemtype="https://schema.org/Review" itemscope',
			'publisher'                     => 'itemtype="https://schema.org/Organization" itemscope',
			'date-published'                => 'itemprop="datePublished"',
			'date-modified'                 => 'itemprop="dateModified"',
			'rating'                        => 'itemtype="https://schema.org/Rating" itemscope',
			'faq'                           => 'itemtype="https://schema.org/FAQPage" itemscope',
			'question'                      => 'itemtype="https://schema.org/Question" itemscope',
			'answer'                        => 'itemtype="https://schema.org/Answer" itemscope',
			default                         => '',
		};

		return apply_filters( 'hd_' . ( $context ?? '' ) . '_microdata_filter', $data );
	}

	// --------------------------------------------------

	/**
	 * @return string
	 */
	private static function getBodyMicrodata(): string {
		// Priority-based type detection (most specific first)
		if ( function_exists( 'is_product_category' ) && \is_product_category() ) {
			$type = 'Collection';
		} elseif ( function_exists( 'is_shop' ) && \is_shop() ) {
			$type = 'Collection';
		} elseif ( is_search() ) {
			$type = 'SearchResultsPage';
		} elseif ( is_home() || is_archive() || is_tax() || is_single() ) {
			$type = 'Blog';
		} else {
			$type = 'WebPage';
		}

		return sprintf( 'itemtype="https://schema.org/%s" itemscope', esc_attr( $type ) );
	}

	// --------------------------------------------------
	// NAVIGATION MENU UTILITIES (from WpNavigation)
	// --------------------------------------------------

	/**
	 * @param array $args
	 *
	 * @return false|string|null
	 */
	public static function verticalNav( array $args = [] ): false|string|null {
		$args = wp_parse_args(
			$args,
			[
				'container'      => false,
				'menu_id'        => '',
				'menu_class'     => 'menu vertical',
				'theme_location' => '',
				'depth'          => 4,
				'fallback_cb'    => false,
				'walker'         => new VerticalNavWalker(),
				'items_wrap'     => '<ul id="%1$s" class="%2$s" data-fx-accordion-menu data-submenu-toggle="true" data-multi-selectable="true">%3$s</ul>',
				'echo'           => false,
			]
		);

		return wp_nav_menu( $args );
	}

	// --------------------------------------------------

	/**
	 * @param array $args
	 *
	 * @return false|string|null
	 */
	public static function horizontalNav( array $args = [] ): false|string|null {
		$dataHover    = (bool) ( $args['data_hover'] ?? true );
		$dataAutohide = (bool) ( $args['data_autohide'] ?? false );

		$dataAttrs = ( $dataHover ? ' data-hover="true"' : '' ) . ( $dataAutohide ? ' data-autohide="true" data-more-label="' . esc_attr__( 'More', 'hd' ) . '"' : '' );

		$args = wp_parse_args(
			$args,
			[
				'container'      => false,
				'menu_id'        => '',
				'menu_class'     => 'dropdown menu horizontal',
				'theme_location' => '',
				'depth'          => 4,
				'fallback_cb'    => false,
				'walker'         => new HorizontalNavWalker(),
				'items_wrap'     => '<ul id="%1$s" class="%2$s" data-fx-dropdown-menu' . $dataAttrs . '>%3$s</ul>',
				'echo'           => false,
			]
		);

		return wp_nav_menu( $args );
	}
}
