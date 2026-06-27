<?php
/**
 * Seasonal Module — toggle Tet/holiday decorations via ACF Options.
 *
 * Adds a body class (e.g. `is-tet-season`, `is-summer-sale`) that CSS can
 * target for seasonal backgrounds, side banners, and color overrides.
 * Also injects an optional top announcement bar for promotions.
 *
 * Admin controls via ACF Options > Seasonal tab:
 *   - Season preset (none / tet / summer / christmas / custom)
 *   - Custom body class (when preset = custom)
 *   - Announcement bar text + link + background color
 *   - Enable/disable toggle
 *
 * @package SPL\Features\Optimizer
 */

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class SeasonalModule {

	/** Cached values. */
	private static string $season      = '';
	private static string $bodyClass   = '';
	private static string $barText     = '';
	private static string $barLink     = '';
	private static string $barBgColor  = '';

	/** Season preset → body class map. */
	private const PRESETS = [
		'tet'       => 'is-tet-season',
		'summer'    => 'is-summer-sale',
		'christmas' => 'is-christmas',
		'mid_autumn'=> 'is-mid-autumn',
	];

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		add_action( 'wp', [ self::class, 'init' ], 5 );
	}

	/**
	 * Read options and attach output hooks.
	 */
	public static function init(): void {
		if ( Helper::isLogin() ) {
			return;
		}

		$enabled = Helper::getField( 'seasonal_enabled', 'option' );
		if ( ! $enabled ) {
			return;
		}

		self::$season = trim( (string) Helper::getField( 'seasonal_preset', 'option' ) );
		if ( self::$season === '' || self::$season === 'none' ) {
			return;
		}

		// Resolve body class.
		if ( self::$season === 'custom' ) {
			self::$bodyClass = sanitize_html_class( (string) Helper::getField( 'seasonal_custom_class', 'option' ) );
		} else {
			self::$bodyClass = self::PRESETS[ self::$season ] ?? '';
		}

		// Announcement bar.
		self::$barText    = trim( (string) Helper::getField( 'seasonal_bar_text', 'option' ) );
		self::$barLink    = trim( (string) Helper::getField( 'seasonal_bar_link', 'option' ) );
		self::$barBgColor = trim( (string) Helper::getField( 'seasonal_bar_color', 'option' ) );

		// Hook body class.
		if ( self::$bodyClass !== '' ) {
			add_filter( 'body_class', [ self::class, 'addBodyClass' ] );
		}

		// Hook announcement bar.
		if ( self::$barText !== '' ) {
			add_action( 'wp_body_open', [ self::class, 'renderAnnouncementBar' ], 1 );
		}
	}

	/**
	 * Add seasonal body class.
	 *
	 * @param string[] $classes Existing body classes.
	 *
	 * @return string[]
	 */
	public static function addBodyClass( array $classes ): array {
		$classes[] = self::$bodyClass;

		return $classes;
	}

	/**
	 * Render a top announcement bar for seasonal promotions.
	 */
	public static function renderAnnouncementBar(): void {
		$bg    = self::$barBgColor ?: '#dc2626';
		$tag   = self::$barLink !== '' ? 'a' : 'div';
		$href  = self::$barLink !== '' ? ' href="' . esc_url( self::$barLink ) . '"' : '';
		?>
		<<?= $tag ?><?= $href ?>
			class="dxd-seasonal-bar block w-full text-center text-white text-xs md:text-sm font-semibold py-2 px-4 relative z-[60]"
			style="background-color:<?= esc_attr( $bg ) ?>"
		>
			<span class="inline-flex items-center gap-2">
				<?= wp_kses_post( self::$barText ) ?>
				<?php if ( self::$barLink !== '' ) : ?>
					<svg class="w-3.5 h-3.5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
						<path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
					</svg>
				<?php endif; ?>
			</span>
		</<?= $tag ?>>
		<?php
	}
}
