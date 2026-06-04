<?php
/**
 * Contact Link — floating contact buttons (Hotline, Zalo, Messenger…).
 *
 * @package HDAddons\Modules\ContactLink
 */

namespace HDAddons\Modules\ContactLink;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Asset;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class ContactLinkModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'contact_link';
	}

	public static function title(): string {
		return 'Contact Link';
	}

	public static function description(): string {
		return 'Floating contact buttons with popup.';
	}

	public static function group(): string {
		return 'tools';
	}


	// ── Constants ───────────────────────────────────

	public const KEY_FORM_ITEMS = 'contact_items';

	/**
	 * Cached contact items for performance.
	 */
	private static ?array $contactItems = null;

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		add_shortcode( 'contact_link', $this->contactLink( ... ) );
		add_action( 'wp_footer', $this->addThisContactLink( ... ), 30 );
		add_filter( 'hd_footer_class_filter', $this->modifyFooterClass( ... ) );

		// Admin: Enqueue media uploader scripts.
		add_action( 'admin_enqueue_scripts', $this->enqueueAdminScripts( ... ) );
	}

	// ── Admin Scripts ───────────────────────────────

	/**
	 * Enqueue admin scripts for media uploader and localize i18n strings.
	 *
	 * @param string $hook The current admin page.
	 */
	private function enqueueAdminScripts( string $hook ): void {
		if ( 'toplevel_page_hda-settings' !== $hook ) {
			return;
		}

		wp_enqueue_media();

		$adminHandle = Asset::handle( 'admin-core.js' );

		if ( $adminHandle ) {
			wp_localize_script(
				$adminHandle,
				'hdaContactLinkI18n',
				[
					'newContact'       => __( 'New Contact', 'hda' ),
					'remove'           => __( 'Remove', 'hda' ),
					'selectIcon'       => __( 'Select Icon', 'hda' ),
					'useThisIcon'      => __( 'Use this icon', 'hda' ),
					'atLeastOne'       => __( 'You must have at least one contact link.', 'hda' ),
					'icon'             => __( 'Icon', 'hda' ),
					'iconDesc'         => __( 'Select an image or SVG from the media library.', 'hda' ),
					'name'             => __( 'Name', 'hda' ),
					'namePlaceholder'  => __( 'e.g., Hotline, Zalo, Facebook', 'hda' ),
					'linkValue'        => __( 'Link/Value', 'hda' ),
					'valuePlaceholder' => __( 'e.g., tel:+84123456789, https://zalo.me/...', 'hda' ),
					'target'           => __( 'Target', 'hda' ),
					'targetBlank'      => __( 'New Tab (_blank)', 'hda' ),
					'targetSelf'       => __( 'Same Tab (_self)', 'hda' ),
					'cssClass'         => __( 'CSS Class', 'hda' ),
					'classPlaceholder' => __( 'e.g., hotline', 'hda' ),
					'color'            => __( 'Color', 'hda' ),
				]
			);
		}
	}

	// ── Public Accessors ────────────────────────────

	/**
	 * Get cached contact items.
	 */
	public static function getItems(): array {
		if ( null === self::$contactItems ) {
			$raw = Helper::getOption( self::optionKey(), null );

			if ( null === $raw ) {
				$items = self::buildItemsFromTheme();
			} else {
				$items = is_array( $raw ) ? $raw : [];
			}

			if ( ! empty( $items ) ) {
				usort( $items, static fn( $a, $b ) => ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 ) );
			}

			self::$contactItems = $items;
		}

		return self::$contactItems;
	}

	/**
	 * Build contact items from theme's contact_links configuration.
	 */
	private static function buildItemsFromTheme(): array {
		$themeLinks = Helper::filterSettingOptions( 'contact_links' );

		if ( empty( $themeLinks ) ) {
			return [];
		}

		$items = [];
		$order = 0;

		foreach ( $themeLinks as $key => $link ) {
			$items[] = [
				'id'     => $key,
				'name'   => $link['name'] ?? '',
				'icon'   => $link['icon'] ?? '',
				'value'  => $link['value'] ?? '',
				'target' => $link['target'] ?? '_blank',
				'class'  => $link['class'] ?? '',
				'color'  => '',
				'order'  => $order++,
			];
		}

		return $items;
	}

	/**
	 * Clear cached items (call after saving).
	 */
	public static function clearCache(): void {
		self::$contactItems = null;
	}

	/**
	 * Get active contact items (items with value).
	 */
	public static function getActiveItems(): array {
		return array_filter(
			self::getItems(),
			static fn( $item ) => ! empty( $item['value'] )
		);
	}

	/**
	 * Check if any contact link is active.
	 */
	public static function hasActiveLinks(): bool {
		return ! empty( self::getActiveItems() );
	}

	/**
	 * Get default empty item structure.
	 */
	public static function getDefaultItem(): array {
		return [
			'id'     => '',
			'name'   => '',
			'icon'   => '',
			'value'  => '',
			'target' => '_blank',
			'class'  => '',
			'color'  => '',
			'order'  => 0,
		];
	}

	// ── Footer ──────────────────────────────────────

	/**
	 * Modify footer class when contact links are present.
	 */
	private function modifyFooterClass( mixed $default_class ): mixed {
		if ( self::hasActiveLinks() ) {
			return $default_class . ' has-contact-link';
		}

		return $default_class;
	}

	/**
	 * Output contact link shortcode in footer.
	 */
	private function addThisContactLink(): void {
		echo $this->contactLink();
	}

	// ── Shortcode ───────────────────────────────────

	/**
	 * Render contact link shortcode.
	 *
	 * Usage: [contact_link] or [contact_link class="custom-class"]
	 */
	private function contactLink( array|string $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'class' => 'contact-link',
			],
			$atts,
			'contact_link'
		);

		$class  = Helper::escAttr( $atts['class'] );
		$items  = self::getActiveItems();
		$output = [];

		if ( empty( $items ) ) {
			return '';
		}

		foreach ( $items as $item ) {
			$name   = $item['name'] ?? '';
			$icon   = $item['icon'] ?? '';
			$value  = $item['value'] ?? '';
			$target = $item['target'] ?? '_blank';
			$class_ = $item['class'] ?? '';
			$thumb  = Helper::renderIcon( $icon, $name );

			if ( empty( $value ) || empty( $thumb ) ) {
				continue;
			}

			$targetAttr = '';
			if ( ! empty( $target ) ) {
				$targetAttr = sprintf( ' target="%s"', esc_attr( $target ) );
				if ( '_blank' === $target ) {
					$targetAttr .= ' rel="noopener noreferrer"';
				}
			}

			$output[] = sprintf(
				'<li><a%s class="%s" href="%s" title="%s">%s<span>%s</span></a></li>',
				$targetAttr,
				esc_attr( $class_ ),
				esc_url( $value ),
				esc_attr( $name ),
				$thumb,
				esc_html( $name )
			);
		}

		if ( empty( $output ) ) {
			return '';
		}

		return sprintf(
			'<ul class="add-this %s">%s</ul>',
			$class,
			implode( '', $output )
		);
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		$items          = $data[ self::KEY_FORM_ITEMS ] ?? [];
		$sanitizedItems = [];

		if ( ! is_array( $items ) ) {
			$items = [];
		}

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sanitizedItem = [
				'id'     => ! empty( $item['id'] ) ? sanitize_text_field( $item['id'] ) : wp_generate_uuid4(),
				'name'   => ! empty( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '',
				'icon'   => self::sanitizeIconValue( $item['icon'] ?? '' ),
				'value'  => ! empty( $item['value'] ) ? esc_url_raw( $item['value'] ) : '',
				'target' => in_array( $item['target'] ?? '', [ '_blank', '_self' ], true ) ? $item['target'] : '_blank',
				'class'  => ! empty( $item['class'] ) ? sanitize_html_class( $item['class'] ) : '',
				'color'  => ! empty( $item['color'] ) ? sanitize_hex_color( $item['color'] ) : '',
				'order'  => absint( $item['order'] ?? $index ),
			];

			if ( ! empty( $sanitizedItem['name'] ) || ! empty( $sanitizedItem['value'] ) ) {
				$sanitizedItems[] = $sanitizedItem;
			}
		}

		usort( $sanitizedItems, static fn( $a, $b ) => $a['order'] <=> $b['order'] );
		self::clearCache();

		self::saveOrRemove( self::optionKey(), $sanitizedItems, false );
	}

	/**
	 * Sanitize icon value (attachment ID, URL, or SVG string).
	 *
	 * @param mixed $value The icon value.
	 *
	 * @return int|string Sanitized value.
	 */
	private static function sanitizeIconValue( mixed $value ): int|string {
		if ( empty( $value ) ) {
			return '';
		}

		if ( is_string( $value ) && str_starts_with( $value, 'base64:' ) ) {
			$value = base64_decode( substr( $value, 7 ), true );
			if ( false === $value ) {
				return '';
			}
		}

		if ( is_numeric( $value ) ) {
			$attachmentId = absint( $value );
			if ( wp_attachment_is_image( $attachmentId ) || 'image/svg+xml' === get_post_mime_type( $attachmentId ) ) {
				return $attachmentId;
			}

			return '';
		}

		if ( Helper::isUrl( $value ) ) {
			return esc_url_raw( $value );
		}

		if ( str_starts_with( $value, '<svg' ) ) {
			return Helper::ksesSvg( $value );
		}

		return sanitize_text_field( $value );
	}
}
