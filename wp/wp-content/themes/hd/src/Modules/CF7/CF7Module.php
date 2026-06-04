<?php
/**
 * Contact Form 7 Optimization
 *
 * Optimizes CF7 performance without breaking AJAX submission.
 * - Conditionally loads assets only on pages with forms
 * - Removes autop for cleaner HTML
 *
 * @package HD\Modules\CF7
 * @author  HD
 */

namespace HD\Modules\CF7;

use HD\Modules\AbstractModule;
use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class CF7Module extends AbstractModule {
	private ?bool $cf7DetectionCache = null;

	/* ---------- ModuleInterface --------------------------------- */

	public static function slug(): string {
		return 'cf7';
	}

	public static function isActive(): bool {
		return Helper::isCf7Active();
	}

	/* ---------- Boot -------------------------------------------- */

	public function boot(): void {
		// Remove auto <p> and <br> tags from form output
		add_filter( 'wpcf7_autop_or_not', '__return_false' );

		// Bypass nonce when page caching is active.
		// WHY: Full-page caching (WP Super Cache, WP Rocket, etc.) serves stale
		// HTML containing expired nonces. CF7 AJAX submission then fails with a
		// "nonce verification failed" error. This is a well-known CF7 trade-off.
		// MITIGATION: CF7 still validates the HTTP Referer header and applies its
		// own spam filters (Akismet, reCAPTCHA). The form endpoint is public by design.
		if ( self::hasPageCacheIntegration() ) {
			add_filter( 'wpcf7_verify_nonce', [ self::class, 'maybeBypassNonce' ] );
		}

		// Dynamic taxonomy select support
		add_filter( 'wpcf7_form_tag', $this->dynamicSelectTerms( ... ), 10, 1 );

		// Add inputmode attributes for better mobile UX
		add_filter( 'wpcf7_form_elements', $this->addInputMode( ... ), 10, 1 );

		// Only load CF7 assets on pages that have the shortcode
		add_action( 'wp', $this->conditionalAssets( ... ) );

		// CF7 configuration validation is controlled by WPCF7_VALIDATE_CONFIGURATION,
		// not by the unsupported wpcf7_skip_mail_validation hook.
	}

	/* ---------- PUBLIC ------------------------------------------- */

	/**
	 * Bypass stale CF7 nonces only for anonymous page-cache submissions.
	 */
	public static function maybeBypassNonce( mixed $verified = false ): bool {
		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			return (bool) $verified;
		}

		return self::hasPageCacheIntegration() ? true : (bool) $verified;
	}

	/**
	 * Dynamic Select Terms for Contact Form 7.
	 *
	 * @usage [select name taxonomy:{taxonomy_name} parent:{term_id}]
	 *
	 * @param array $tag Form tag configuration.
	 *
	 * @return array Modified tag with dynamic term values.
	 */
	public function dynamicSelectTerms( array $tag ): array {
		if ( ! in_array( $tag['type'], [ 'select', 'select*' ], true ) ) {
			return $tag;
		}

		if ( empty( $tag['options'] ) ) {
			return $tag;
		}

		$termArgs = [];

		foreach ( $tag['options'] as $option ) {
			[ $key, $value ] = array_pad( explode( ':', (string) $option, 2 ), 2, null );
			if ( null === $value ) {
				continue;
			}

			if ( 'taxonomy' === $key ) {
				$termArgs['taxonomy'] = sanitize_key( $value );
			} elseif ( 'parent' === $key ) {
				$termArgs['parent'] = absint( $value );
			}
		}

		if ( empty( $termArgs['taxonomy'] ) ) {
			return $tag;
		}

		$termArgs = [
			...$termArgs,
			'hide_empty'       => false,
			'hierarchical'     => true,
			'suppress_filters' => false,
		];

		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language( 'slug' );
			if ( $lang ) {
				$termArgs['lang'] = $lang;
			}
		}

		$terms = get_terms( $termArgs );

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$tag['values'][] = $term->slug ?: (string) $term->term_id;
				$tag['labels'][] = $term->name;
			}
		}

		return $tag;
	}

	/**
	 * Add inputmode attributes to form inputs for better mobile keyboard.
	 *
	 * @param string $content Form HTML content.
	 *
	 * @return string Modified content with inputmode attributes.
	 */
	public function addInputMode( string $content ): string {
		return preg_replace_callback(
			'/<input\b(?![^>]*\binputmode\s*=)(?=[^>]*\btype\s*=\s*([\'"])(tel|email|url)\1)[^>]*\/?>/i',
			static function ( array $matches ): string {
				$input   = $matches[0];
				$type    = strtolower( $matches[2] );
				$closing = str_ends_with( rtrim( $input ), '/>' ) ? ' />' : '>';
				$input   = preg_replace( '/\s*\/?>$/', '', $input );

				return ( is_string( $input ) ? $input : $matches[0] ) . ' inputmode="' . $type . '"' . $closing;
			},
			$content
		) ?? $content;
	}

	/**
	 * Only load CF7 assets on pages that need them.
	 *
	 * @return void
	 */
	public function conditionalAssets(): void {
		if ( is_admin() ) {
			return;
		}

		if ( ! $this->detectCF7() ) {
			add_action( 'wp_enqueue_scripts', $this->dequeueAssets( ... ), 100 );
		}
	}

	/* ---------- PRIVATE ------------------------------------------ */

	private static function hasPageCacheIntegration(): bool {
		if ( defined( 'WP_CACHE' ) && \WP_CACHE ) {
			return true;
		}

		$cacheConstants = [
			'WP_ROCKET_VERSION',
			'WPCACHEHOME',
			'LSCWP_V',
			'SWCFPC_VERSION',
			'W3TC_VERSION',
		];

		foreach ( $cacheConstants as $constant ) {
			if ( defined( $constant ) ) {
				return true;
			}
		}

		$cacheClasses = [
			'LiteSpeed_Cache',
			'W3_Plugin_TotalCache',
			'WP_Rocket',
		];

		foreach ( $cacheClasses as $className ) {
			if ( class_exists( $className, false ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect if CF7 is used anywhere on the current page.
	 *
	 * @return bool
	 */
	private function detectCF7(): bool {
		if ( null !== $this->cf7DetectionCache ) {
			return $this->cf7DetectionCache;
		}

		global $post;

		if ( $post instanceof \WP_Post ) {
			if ( has_shortcode( $post->post_content, 'contact-form-7' ) ) {
				$this->cf7DetectionCache = true;

				return true;
			}

			if ( has_block( 'contact-form-7/contact-form-selector', $post ) ) {
				$this->cf7DetectionCache = true;

				return true;
			}
		}

		if ( $this->isForcedLoadPage() ) {
			$this->cf7DetectionCache = true;

			return true;
		}

		$contactForm = Helper::getField( 'contact_form', 'option' );
		if ( ! empty( $contactForm['form'] ) ) {
			$this->cf7DetectionCache = true;

			return true;
		}

		if ( $this->checkWidgetsForCF7() ) {
			$this->cf7DetectionCache = true;

			return true;
		}

		$this->cf7DetectionCache = false;

		return false;
	}

	private function isForcedLoadPage(): bool {
		$pages = apply_filters(
			'hd_cf7_force_load_pages',
			[
				'contact',
				'contact-us',
				'lien-he',
				'lien-he-chung-toi',
			]
		);

		return is_page( $pages );
	}

	/**
	 * Check if any active widget contains CF7 shortcode.
	 *
	 * @return bool
	 */
	private function checkWidgetsForCF7(): bool {
		$sidebars = wp_get_sidebars_widgets();

		if ( ! $sidebars ) {
			return false;
		}

		$widgetOptions = [
			'text-'        => [
				'items' => get_option( 'widget_text', [] ),
				'field' => 'text',
			],
			'custom_html-' => [
				'items' => get_option( 'widget_custom_html', [] ),
				'field' => 'content',
			],
			'block-'       => [
				'items' => get_option( 'widget_block', [] ),
				'field' => 'content',
			],
		];

		foreach ( $sidebars as $sidebarId => $widgets ) {
			if ( $sidebarId === 'wp_inactive_widgets' || ! $widgets ) {
				continue;
			}

			foreach ( $widgets as $widgetId ) {
				if ( $this->widgetContainsCF7( $widgetId, $widgetOptions ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if a widget contains CF7 shortcode.
	 *
	 * @param string $widgetId
	 * @param array<string, array{items: mixed, field: string}> $widgetOptions
	 *
	 * @return bool
	 */
	private function widgetContainsCF7( string $widgetId, array $widgetOptions ): bool {
		foreach ( $widgetOptions as $prefix => $option ) {
			if ( ! str_starts_with( $widgetId, $prefix ) ) {
				continue;
			}

			$widgetNumber = (int) str_replace( $prefix, '', $widgetId );
			$widgets      = is_array( $option['items'] ) ? $option['items'] : [];
			$field        = $option['field'];
			$content      = $widgets[ $widgetNumber ][ $field ] ?? '';

			if ( $content && ( has_shortcode( $content, 'contact-form-7' ) || str_contains( $content, 'wpcf7' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Dequeue CF7 assets on pages without forms.
	 *
	 * @return void
	 */
	public function dequeueAssets(): void {
		$scriptHandles = apply_filters(
			'hd_cf7_dequeue_script_handles',
			[
				'contact-form-7',
				'swv',
				'wpcf7-recaptcha',
				'google-recaptcha',
			]
		);
		$styleHandles  = apply_filters(
			'hd_cf7_dequeue_style_handles',
			[
				'contact-form-7',
				'swv',
			]
		);

		foreach ( $this->normalizeAssetHandles( $scriptHandles ) as $handle ) {
			wp_dequeue_script( $handle );
		}

		foreach ( $this->normalizeAssetHandles( $styleHandles ) as $handle ) {
			wp_dequeue_style( $handle );
		}
	}

	/**
	 * @return string[]
	 */
	private function normalizeAssetHandles( mixed $handles ): array {
		if ( ! is_array( $handles ) ) {
			return [];
		}

		return array_values(
			array_unique(
				array_filter(
					array_map( 'strval', $handles ),
					static fn( string $handle ): bool => '' !== $handle
				)
			)
		);
	}
}
