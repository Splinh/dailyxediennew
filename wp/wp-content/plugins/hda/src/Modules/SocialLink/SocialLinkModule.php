<?php
/**
 * Social Link — social media profile links with shortcode.
 *
 * @package HDAddons\Modules\SocialLink
 */

namespace HDAddons\Modules\SocialLink;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class SocialLinkModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'social_link';
	}

	public static function title(): string {
		return 'Social Link';
	}

	public static function description(): string {
		return 'Social media profile links.';
	}

	public static function group(): string {
		return 'tools';
	}


	// ── Constants ───────────────────────────────────



	/**
	 * Cached social follows links configuration.
	 */
	private static ?array $socialFollowsLinks = null;

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		add_shortcode( 'social_menu', $this->socialMenu( ... ) );
	}

	// ── Public Accessors ────────────────────────────

	/**
	 * Get cached social follows links configuration.
	 */
	public static function getFollowsLinks(): array {
		if ( null === self::$socialFollowsLinks ) {
			self::$socialFollowsLinks = Helper::filterSettingOptions( 'social_follows_links' );
		}

		return self::$socialFollowsLinks;
	}

	// ── Shortcode ───────────────────────────────────

	/**
	 * Render social menu shortcode.
	 *
	 * Usage: [social_menu] or [social_menu class="custom-class"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 *
	 * @return string Rendered HTML.
	 */
	private function socialMenu( array|string $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'class' => 'social-menu',
			],
			$atts,
			'social_menu'
		);

		$class         = Helper::escAttr( $atts['class'] );
		$socialOptions = self::getCachedOptions();
		$socialLinks   = self::getFollowsLinks();
		$items         = [];

		if ( empty( $socialLinks ) ) {
			return '';
		}

		foreach ( $socialLinks as $key => $linkData ) {
			$url = $socialOptions[ $key ]['url'] ?? ( $linkData['url'] ?? '' );

			if ( empty( $url ) ) {
				continue;
			}

			$name  = $linkData['name'] ?? '';
			$icon  = $linkData['icon'] ?? '';
			$thumb = Helper::renderIcon( $icon, $name );

			if ( empty( $thumb ) ) {
				continue;
			}

			$items[] = sprintf(
				'<li><a class="%s" href="%s" title="%s" target="_blank" rel="noopener noreferrer">%s<span class="sr-only">%s</span></a></li>',
				esc_attr( $key ),
				esc_url( $url ),
				esc_attr( $name ),
				$thumb,
				esc_html( $name )
			);
		}

		if ( empty( $items ) ) {
			return '';
		}

		return sprintf(
			'<ul class="menu %s">%s</ul>',
			$class,
			implode( '', $items )
		);
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		$options = [];

		foreach ( Helper::filterSettingOptions( 'social_follows_links', [] ) as $i => $item ) {
			$url = ! empty( $data[ $i . '-url' ] ) ? sanitize_url( $data[ $i . '-url' ] ) : '';
			if ( $url ) {
				$options[ $i ] = [ 'url' => $url ];
			}
		}

		self::saveOrRemove( self::optionKey(), $options );
	}
}
