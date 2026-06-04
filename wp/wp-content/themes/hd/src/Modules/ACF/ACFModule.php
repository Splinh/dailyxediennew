<?php
/**
 * Advanced Custom Fields (ACF) / Secure Custom Fields (SCF) integration.
 *
 * Manages theme-level ACF/SCF integrations:
 * - Hide ACF admin UI in production
 * - Custom field types (NavMenu, CodeEditor)
 * - Extended WYSIWYG/TinyMCE toolbars
 * - Navigation menu item ACF properties (icons, labels, mega menu)
 *
 * @package HD\Modules\ACF
 * @author  HD
 */

namespace HD\Modules\ACF;

use HD\Modules\AbstractModule;
use HD\Modules\ACF\FieldTypes\CodeEditor;
use HD\Modules\ACF\FieldTypes\NavMenu;
use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class ACFModule extends AbstractModule {
	/**
	 * Field definition load order is intentionally stable for deterministic
	 * local field group registration and predictable override behavior.
	 */
	private const FIELD_DEFINITION_FILES = [
		'author_meta.php',
		'mega_menu.php',
		'menu.php',
		'suggestion_page.php',
		'suggestion_post.php',
		'taxonomy.php',
	];

	/* ---------- ModuleInterface --------------------------------- */

	public static function slug(): string {
		return 'acf';
	}

	public static function isActive(): bool {
		return Helper::isAcfActive();
	}

	/* ---------- Boot -------------------------------------------- */

	public function boot(): void {
		// Hide the ACF Admin UI in production
		if ( ! Helper::development() ) {
			add_filter( 'acf/settings/show_admin', '__return_false' );
		}

		add_filter( 'wp_kses_allowed_html', $this->ksesAllowedHtml( ... ), 11, 2 );

		if ( self::isAdminOrAcfHookContext() ) {
			// Register custom ACF field types
			add_action( 'acf/include_field_types', $this->registerFieldTypes( ... ) );
			add_filter( 'teeny_mce_buttons', $this->teenyMceButtons( ... ), 99, 2 );
			$this->loadFieldDefinitions();
		}

		add_filter( 'wp_nav_menu_objects', $this->navMenuObjects( ... ), 998, 2 );
		add_filter( 'nav_menu_item_title', $this->navMenuItemTitle( ... ), 999, 4 );
		add_filter( 'nav_menu_link_attributes', $this->navMenuLinkAttributes( ... ), 999, 4 );
	}

	private static function isAdminOrAcfHookContext(): bool {
		if ( is_admin() ) {
			return true;
		}

		if ( ! function_exists( 'doing_action' ) ) {
			return false;
		}

		return doing_action( 'acf/init' )
			|| doing_action( 'acf/include_fields' )
			|| doing_action( 'acf/include_field_types' );
	}

	private function loadFieldDefinitions(): void {
		// Load custom field definitions from fields/ directory.
		foreach ( self::FIELD_DEFINITION_FILES as $file ) {
			$path = __DIR__ . '/fields/' . $file;
			if ( is_file( $path ) ) {
				require_once $path;
			}
		}
	}

	/* ---------- FIELD TYPES ---------------------------------------- */

	/**
	 * Register custom ACF field types.
	 *
	 * @return void
	 */
	public function registerFieldTypes(): void {
		// Register Code Editor field
		new CodeEditor();

		// Skip Nav Menu if the standalone plugin is already active
		if (
			Helper::checkPluginActive( 'acf-nav-menu-field/advanced-custom-nav-menu-field.php' )
			|| Helper::checkPluginActive( 'advanced-custom-nav-menu-field/advanced-custom-nav-menu-field.php' )
		) {
			return;
		}

		new NavMenu();
	}

	/* ---------- PUBLIC ------------------------------------------- */

	/**
	 * @param array  $tags
	 * @param string $context
	 *
	 * @return array
	 */
	public function ksesAllowedHtml( array $tags, string $context ): array {
		if ( $context !== 'acf' ) {
			return $tags;
		}

		foreach ( Helper::ksesSVG() as $tag => $attrs ) {
			$tags[ $tag ] = isset( $tags[ $tag ] ) ? [ ...$tags[ $tag ], ...$attrs ] : $attrs;
		}

		return $tags;
	}

	// -------------------------------------------------------------

	/**
	 * @param array  $teenyMceButtons
	 * @param string $editorId
	 *
	 * @return array
	 */
	public function teenyMceButtons( array $teenyMceButtons, string $editorId ): array {
		if ( ! str_starts_with( $editorId, 'acf-editor' ) ) {
			return $teenyMceButtons;
		}

		return [
			'formatselect',
			'bold',
			'underline',
			'bullist',
			'numlist',
			'link',
			'unlink',
			'forecolor',
			'blockquote',
			'table',
			'codesample',
			'subscript',
			'superscript',
			'fullscreen',
		];
	}

	// -------------------------------------------------------------

	/**
	 * @param array  $items
	 * @param object $args
	 *
	 * @return array
	 */
	public function navMenuObjects( array $items, object $args ): array {
		$itemIds = array_values(
			array_unique(
				array_filter(
					array_map( static fn( object $item ): int => absint( $item->ID ?? 0 ), $items )
				)
			)
		);

		if ( $itemIds ) {
			update_meta_cache( 'post', $itemIds );
		}

		foreach ( $items as $item ) {
			$itemId = absint( $item->ID ?? 0 );
			if ( ! $itemId ) {
				continue;
			}

			$item->menu_mega             = (bool) get_post_meta( $itemId, 'menu_mega', true );
			$item->menu_link_class       = (string) get_post_meta( $itemId, 'menu_link_class', true );
			$item->menu_span             = (string) get_post_meta( $itemId, 'menu_span', true );
			$item->menu_span_css         = (string) get_post_meta( $itemId, 'menu_span_css', true );
			$item->menu_svg              = (string) get_post_meta( $itemId, 'menu_svg', true );
			$item->menu_image            = absint( get_post_meta( $itemId, 'menu_image', true ) );
			$item->menu_label_text       = (string) get_post_meta( $itemId, 'menu_label_text', true );
			$item->menu_label_color      = (string) get_post_meta( $itemId, 'menu_label_color', true );
			$item->menu_label_background = (string) get_post_meta( $itemId, 'menu_label_background', true );

			if ( $item->menu_mega ) {
				$item->classes[] = 'menu-mega';
			}
			if ( $item->menu_svg ) {
				$item->classes[] = 'menu-svg';
			}
			if ( $item->menu_image ) {
				$item->classes[] = 'menu-thumb';
			}
			if ( $item->menu_label_text ) {
				$item->classes[] = 'menu-label';
			}
		}

		return $items;
	}

	// -------------------------------------------------------------

	/**
	 * Build ACF location rules for selected menu item locations.
	 *
	 * @param array<int, mixed> $locations Menu location identifiers.
	 *
	 * @return array<int, array<int, array{param: string, operator: string, value: string}>>
	 */
	public static function navMenuItemLocationRules( array $locations ): array {
		return array_values(
			array_filter(
				array_map(
					static fn( mixed $location ): ?array => $location
						? [
							[
								'param'    => 'nav_menu_item',
								'operator' => '==',
								'value'    => 'location/' . Helper::toString( $location ),
							],
						]
						: null,
					$locations
				)
			)
		);
	}

	// -------------------------------------------------------------

	/**
	 * @param string   $title
	 * @param \WP_Post $item
	 * @param object   $args
	 * @param int      $depth
	 *
	 * @return string
	 */
	public function navMenuItemTitle( string $title, \WP_Post $item, object $args, int $depth ): string {
		// Label <sup>
		if ( ! empty( $item->menu_label_text ) ) {
			$css = '';
			if ( ! empty( $item->menu_label_color ) ) {
				$css .= 'color:' . esc_attr( $item->menu_label_color ) . ';';
			}
			if ( ! empty( $item->menu_label_background ) ) {
				$css .= 'background-color:' . esc_attr( $item->menu_label_background ) . ';';
			}

			$style  = $css ? ' style="' . $css . '"' : '';
			$title .= '<sup' . $style . '>' . esc_html( $item->menu_label_text ) . '</sup>';
		}

		// span + span css
		if ( ! empty( $item->menu_span ) ) {
			$spanOpen = ! empty( $item->menu_span_css )
				? '<span class="' . esc_attr( $item->menu_span_css ) . '">'
				: '<span>';
			$title    = $spanOpen . $title . '</span>';
		}

		// SVG inline
		if ( ! empty( $item->menu_svg ) ) {
			$title = wp_kses( $item->menu_svg, Helper::ksesSVG() ) . $title;
		}

		// IMG
		if ( ! empty( $item->menu_image ) ) {
			$img   = Helper::attachmentImageHTML(
				$item->menu_image,
				'thumbnail',
				[
					'loading' => 'lazy',
					'alt'     => wp_strip_all_tags( $item->title ?? '' ),
				]
			);
			$title = $img . $title;
		}

		return $title;
	}

	// -------------------------------------------------------------

	/**
	 * @param array    $atts
	 * @param \WP_Post $menuItem
	 * @param object   $args
	 * @param int      $depth
	 *
	 * @return array
	 */
	public function navMenuLinkAttributes( array $atts, \WP_Post $menuItem, object $args, int $depth ): array {
		if ( empty( $menuItem->menu_link_class ) ) {
			return $atts;
		}

		$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' ' . esc_attr( $menuItem->menu_link_class ) );

		return $atts;
	}
}
