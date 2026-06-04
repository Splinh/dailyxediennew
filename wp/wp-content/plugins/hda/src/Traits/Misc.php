<?php
/**
 * Miscellaneous utility trait.
 *
 * Includes: environment, logging, URLs, CSRF, shortcuts,
 * string utilities, plugin detection, and theme settings.
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Misc {

	// ── Environment ────────────────────────────────

	/**
	 * Check if in development mode.
	 */
	public static function development(): bool {
		return wp_get_environment_type() === 'development'
				|| ( defined( 'WP_DEBUG' ) && \WP_DEBUG === true );
	}

	// ── Logging ────────────────────────────────────

	/**
	 * Throttled error logging.
	 *
	 * Logs errors in all environments, but throttles duplicate messages
	 * via object cache to prevent log flooding.
	 */
	public static function errorLog( string $message, int $type = 0, ?string $dest = null, ?string $headers = null ): void {
		$key = 'hda_err_' . md5( $message );

		if ( ! wp_cache_get( $key, 'hda_error_log_cache' ) ) {
			wp_cache_set( $key, 1, 'hda_error_log_cache', MINUTE_IN_SECONDS );
			error_log( $message, $type, $dest, $headers );
		}
	}

	// ── URL / Navigation ───────────────────────────

	/**
	 * Remove version query string from asset URLs.
	 */
	public static function removeVersionQuery( string $src ): string {
		if ( str_contains( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}

		return $src;
	}

	/**
	 * Redirect with fallback for headers sent.
	 */
	public static function redirect( string $uri = '', int $status = 301 ): bool {
		$uri = esc_url_raw( $uri );
		if ( ! $uri ) {
			return false;
		}

		if ( ! headers_sent() ) {
			wp_safe_redirect( $uri, $status );
			exit;
		}

		printf( '<script>window.location.href=%s;</script>', wp_json_encode( $uri ) );
		printf( '<noscript><meta http-equiv="refresh" content="0;url=%s" /></noscript>', esc_attr( $uri ) );

		return true;
	}

	/**
	 * Get current page URL (canonical, without pagination params).
	 */
	public static function getCurrentUrl( bool $stripPagination = false ): string {
		if ( ! function_exists( 'home_url' ) ) {
			return '';
		}

		if ( ! $stripPagination ) {
			return home_url( add_query_arg( null, null ) );
		}

		global $wp;

		$baseUrl     = home_url( $wp->request ?? '' );
		$queryParams = array_filter(
			array_map(
				static fn( $v ) => is_string( $v ) ? sanitize_text_field( $v ) : null,
				wp_unslash( $_GET )
			),
			static fn( $v ) => $v !== null
		);

		unset( $queryParams['paged'], $queryParams['page'], $queryParams['pg'] );

		if ( ! empty( $queryParams ) ) {
			return add_query_arg( $queryParams, trailingslashit( $baseUrl ) );
		}

		return trailingslashit( $baseUrl );
	}

	/**
	 * Validate URL.
	 */
	public static function isUrl( ?string $url ): bool {
		if ( ! $url || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return false;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		return (bool) filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME );
	}

	/**
	 * Check if Lighthouse/PageSpeed is running.
	 */
	public static function lightHouse(): bool {
		$ua = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) );

		return (bool) preg_match( '/lighthouse|headlesschrome|chrome-lighthouse|pagespeed/', $ua );
	}

	// ── CSRF / Forms ───────────────────────────────

	/**
	 * Generate CSRF token field.
	 */
	public static function CSRFToken( string|int $action = -1, string $name = '_csrf_token', bool $referer = false, bool $display = false ): ?string {
		$name       = esc_attr( $name );
		$token      = wp_create_nonce( $action );
		$nonceField = '<input type="hidden" id="' . wp_generate_password( 10, false ) . '" name="' . $name . '" value="' . esc_attr( $token ) . '" />';

		if ( $referer ) {
			$nonceField .= wp_referer_field( false );
		}

		if ( $display ) {
			echo $nonceField;

			return null;
		}

		return $nonceField;
	}

	/**
	 * Check if value is in array and output checked attribute.
	 */
	public static function inArrayChecked( array $checkedArr, mixed $current, bool $display = true, string $type = 'checked' ): ?string {
		$type   = preg_match( '/^[a-zA-Z0-9\-]+$/', $type ) ? $type : 'checked';
		$result = in_array( $current, $checkedArr, true ) ? " {$type}='{$type}'" : '';

		if ( $display ) {
			echo $result;

			return null;
		}

		return $result;
	}

	/**
	 * Execute shortcode directly.
	 */
	public static function doShortcode( string $tag, array $atts = [], ?string $content = null ): mixed {
		global $shortcode_tags;

		if ( ! isset( $shortcode_tags[ $tag ] ) ) {
			return false;
		}

		try {
			return call_user_func( $shortcode_tags[ $tag ], $atts, $content, $tag );
		} catch ( \Throwable $e ) {
			self::errorLog( '[Shortcode error] ' . $e->getMessage() );

			return false;
		}
	}

	/**
	 * Convert size string to MB.
	 */
	public static function convertToMB( string $size ): int {
		$multipliers = [
			'M' => 1,
			'G' => 1024,
			'T' => 1024 * 1024,
		];
		$size        = strtoupper( trim( $size ) );

		if ( preg_match( '/^(\d+(?:\.\d+)?)(M|MB|G|GB|T|TB)?$/', $size, $m ) ) {
			$value = (float) $m[1];
			$unit  = rtrim( $m[2] ?? 'M', 'B' );

			return (int) round( $value * ( $multipliers[ $unit ] ?? 1 ) );
		}

		return 0;
	}

	// ── AJAX Response ──────────────────────────────

	/**
	 * Send toast success JSON response.
	 */
	public static function toastSuccess( string $msg = '', bool $autoHide = true ): void {
		$text = $msg ?: esc_html__( 'Values saved', 'hda' );

		wp_send_json_success(
			[
				'type'     => 'success',
				'message'  => $text,
				'autoHide' => $autoHide,
			]
		);
	}

	/**
	 * Send toast error JSON response.
	 */
	public static function toastError( string $msg = '', bool $autoHide = false ): void {
		$text = $msg ?: esc_html__( 'An error occurred', 'hda' );

		wp_send_json_error(
			[
				'type'     => 'error',
				'message'  => $text,
				'autoHide' => $autoHide,
			]
		);
	}

	// ── String Utilities ───────────────────────────

	/**
	 * Strip all HTML tags with optional JS removal.
	 */
	public static function stripAllTags( ?string $text, bool $removeJs = true, bool $flatten = true, string|array|null $allowedTags = null ): string {
		if ( ! $text ) {
			return '';
		}

		if ( is_array( $allowedTags ) ) {
			$allowedTags = implode( '', array_map( static fn( $tag ) => "<{$tag}>", $allowedTags ) );
		}

		if ( $removeJs ) {
			$text = preg_replace( '/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $text ) ?? '';
		}

		$text = strip_tags( $text, $allowedTags );

		if ( $flatten ) {
			$text = preg_replace( '/\s+/u', ' ', $text ) ?? '';
		}

		return trim( $text );
	}

	/**
	 * Escape attribute safely.
	 */
	public static function escAttr( mixed $text ): string {
		return esc_attr( self::stripAllTags( (string) $text ) );
	}

	/**
	 * Convert slug to capitalized format (PascalCase or Original_Case).
	 */
	public static function capitalizedSlug( string $slug, bool $removeSymbols = true ): string {
		$words = preg_split( '/[_\-]/', $slug );
		$words = array_map( 'ucfirst', $words );

		if ( $removeSymbols ) {
			return implode( '', $words );
		}

		return str_contains( $slug, '_' ) ? implode( '_', $words ) : implode( '-', $words );
	}

	// ── Plugin Detection ───────────────────────────

	/**
	 * Cache flag for plugin functions loaded.
	 */
	private static bool $pluginFunctionsLoaded = false;

	/**
	 * Ensure plugin functions are loaded (cached).
	 */
	private static function ensurePluginFunctions(): void {
		if ( self::$pluginFunctionsLoaded ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		self::$pluginFunctionsLoaded = true;
	}

	/**
	 * Check if plugin is active.
	 */
	public static function checkPluginActive( string $pluginFile ): bool {
		self::ensurePluginFunctions();

		if ( is_multisite() && is_plugin_active_for_network( $pluginFile ) ) {
			return true;
		}

		return is_plugin_active( $pluginFile );
	}

	/**
	 * Check if Classic Editor is active.
	 */
	public static function isClassicEditorActive(): bool {
		return class_exists( 'Classic_Editor' )
				|| self::checkPluginActive( 'classic-editor/classic-editor.php' );
	}

	/**
	 * Check if ACF Pro or Secure Custom Fields (SCF) is active.
	 */
	public static function isAcfProActive(): bool {
		if ( defined( 'ACF_PRO' ) || class_exists( 'acf_pro' ) ) {
			return true;
		}

		return self::checkPluginActive( 'advanced-custom-fields-pro/acf.php' )
			|| self::checkPluginActive( 'secure-custom-fields/secure-custom-fields.php' );
	}

	// ── Settings (Theme integration) ───────────────

	/**
	 * Get filtered setting options.
	 */
	public static function filterSettingOptions( string $name, mixed $fallback = [] ): mixed {
		$filters = apply_filters( 'hd_settings_filter', self::themeSettingDefault() );

		return ( $filters[ $name ] ?? null ) ?: $fallback;
	}

	/**
	 * Get default theme settings.
	 */
	public static function themeSettingDefault(): array {
		return apply_filters(
			'hd_settings_defaults',
			[
				'aspect_ratio' => [
					'post_type_term' => [ 'post' ],
				],

				'admin_menu'   => [
					'admin_hide_menu_ignore_user' => [ 1 ],
				],

				'security'     => [
					'privileged_user_ids'                 => [ 1 ],
					'allowed_users_ids_show_plugins'      => [ 1 ],
					'allowed_users_ids_install_plugins'   => [ 1 ],
					'disallowed_users_ids_delete_account' => [ 1 ],
				],
			]
		);
	}
}
