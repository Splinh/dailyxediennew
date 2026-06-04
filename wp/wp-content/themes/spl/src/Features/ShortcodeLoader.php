<?php
/**
 * Theme Shortcodes Loader
 *
 * Registers and manages all custom shortcodes used in the theme.
 *
 * @package SPL\Features
 * @author  HD
 */

namespace SPL\Features;

use SPL\Contracts\Feature;
use SPL\Core\Helper;
use SPL\Core\Query;

defined( 'ABSPATH' ) || exit;

final class ShortcodeLoader extends Feature {

	/* ---------- Feature ---------------------------------------- */

	public function boot(): void {
		$shortcodes = [
			'safe_mail'         => [ $this, 'safeMail' ],
			'site_logo'         => [ $this, 'siteLogo' ],
			'menu_logo'         => [ $this, 'menuLogo' ],
			'inline_search'     => [ $this, 'inlineSearch' ],
			'dropdown_search'   => [ $this, 'dropdownSearch' ],
			'off_canvas_button' => [ $this, 'offCanvasButton' ],
			'horizontal_menu'   => [ $this, 'horizontalMenu' ],
			'vertical_menu'     => [ $this, 'verticalMenu' ],
			'posts'             => [ $this, 'posts' ],
		];

		foreach ( $shortcodes as $tag => $callback ) {
			add_shortcode( $tag, $callback );
		}
	}

	/* ---------- PRIVATE --------------------------------------- */

	/**
	 * Generate unique ID for form elements.
	 */
	private static function generateUniqueId( string $prefix = 'id' ): string {
		static $counters = [];

		$counters[ $prefix ] = ( $counters[ $prefix ] ?? 0 ) + 1;

		return $prefix . '-' . substr( md5( $prefix . $counters[ $prefix ] ), 0, 10 );
	}

	/* ---------- PUBLIC ---------------------------------------- */

	/**
	 * Render the site logo area used inside navigation menus.
	 *
	 * This shortcode intentionally delegates to Helper::siteTitleOrLogo(): it
	 * renders the custom logo when configured and falls back to the site title.
	 * The supported controls are limited to heading semantics and wrapper class.
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function safeMail( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'title' => '',
				'email' => '',
				'class' => '',
			],
			$atts,
			'safe_mail'
		);

		$attributes = [
			'title' => Helper::escAttr( $atts['title'] ?: $atts['email'] ),
		];

		if ( $atts['class'] ) {
			$attributes['class'] = Helper::escAttr( $atts['class'] );
		}

		return Helper::safeMailTo( $atts['email'], $atts['title'], $attributes ) ?? '';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function siteLogo( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'theme' => 'default',
				'class' => '',
			],
			$atts,
			'site_logo'
		);

		return Helper::siteLogo( $atts['theme'], $atts['class'] );
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function menuLogo( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'heading' => false,
				'class'   => 'logo',
			],
			$atts,
			'menu_logo'
		);

		return Helper::siteTitleOrLogo( false, $atts['heading'], $atts['class'] ) ?? '';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function inlineSearch( array $atts = [] ): string {
		$defaultId = self::generateUniqueId( 'search' );

		$atts = shortcode_atts(
			[
				'title'       => '',
				'placeholder' => '',
				'class'       => '',
				'id'          => $defaultId,
			],
			$atts,
			'inline_search'
		);

		$title       = esc_html( $atts['title'] );
		$titleFor    = esc_attr__( 'Tìm kiếm', 'SPL' );
		$placeholder = esc_attr( $atts['placeholder'] ?: __( 'Tìm kiếm...', 'SPL' ) );
		$id          = Helper::escAttr( $atts['id'] ?: $defaultId );
		$class       = $atts['class'] ? ' ' . Helper::escAttr( $atts['class'] ) : '';

		ob_start();
		?>
		<form action="<?php echo Helper::home(); ?>" class="frm-search" method="get" accept-charset="UTF-8">
			<label for="<?php echo $id; ?>" class="sr-only"><?php echo $titleFor; ?></label>
			<input id="<?php echo $id; ?>" required pattern="^(.*\S+.*)$" type="search" autocomplete="off" name="s" value="<?php echo get_search_query(); ?>"
					placeholder="<?php echo $placeholder; ?>">
			<button type="submit" aria-label="<?php echo esc_attr__( 'Tìm kiếm', 'SPL' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
					<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21l-3.5-3.5M17 10a7 7 0 1 1-14 0a7 7 0 0 1 14 0Z"/>
				</svg>
				<?php echo $title ? '<span>' . $title . '</span>' : ''; ?>
			</button>
			<input type="hidden" name="post_type" value="<?php echo Helper::isWoocommerceActive() ? 'product' : 'post'; ?>">
		</form>
		<?php

		return '<div class="inline-search' . $class . '">' . ob_get_clean() . '</div>';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function dropdownSearch( array $atts = [] ): string {
		$defaultId = self::generateUniqueId( 'search' );

		$atts = shortcode_atts(
			[
				'title'         => '',
				'class'         => '',
				'id'            => $defaultId,
				'align'         => '',
				'trigger_class' => '',
				'pane_class'    => '',
				'show_title'    => true,
				'icon_class'    => 'size-6',
			],
			$atts,
			'dropdown_search'
		);

		$titleRaw     = $atts['title'] ?: __( 'Tìm kiếm', 'SPL' );
		$title        = esc_html( $titleRaw );
		$titleAttr    = esc_attr( $titleRaw );
		$titleFor     = esc_attr__( 'Tìm kiếm cho', 'SPL' );
		$placeholder  = esc_attr__( 'Tìm kiếm...', 'SPL' );
		$class        = $atts['class'] ? ' ' . Helper::escAttr( $atts['class'] ) : '';
		$align        = $atts['align'] ? ' alignment-' . Helper::escAttr( $atts['align'] ) : '';
		$id           = Helper::escAttr( $atts['id'] ?: $defaultId );
		$triggerClass = $atts['trigger_class'] ? ' ' . Helper::escAttr( $atts['trigger_class'] ) : '';
		$paneClass    = $atts['pane_class'] ? ' ' . Helper::escAttr( $atts['pane_class'] ) : '';
		$showTitle    = Helper::toBool( $atts['show_title'] );
		$iconClass    = Helper::escAttr( $atts['icon_class'] ?: 'size-6' );

		ob_start();
		?>
		<button type="button" class="dropdown-trigger<?php echo $triggerClass; ?>" title="<?php echo $titleAttr; ?>" aria-label="<?php echo $titleAttr; ?>" data-fx-dropdown-toggle aria-expanded="false" aria-haspopup="true">
			<svg class="<?php echo $iconClass; ?> svg-search" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
				<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21l-3.5-3.5M17 10a7 7 0 1 1-14 0a7 7 0 0 1 14 0Z"/>
			</svg>
			<svg class="<?php echo $iconClass; ?> svg-close" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
				<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L17.94 6M18 18L6.06 6"/>
			</svg>
			<span<?php echo $showTitle ? '' : ' class="sr-only"'; ?>><?php echo $title; ?></span>
		</button>
		<div role="search" class="dropdown-pane<?php echo $align; ?><?php echo $paneClass; ?>" data-fx-dropdown data-auto-focus="true">
			<form action="<?php echo Helper::home(); ?>" class="frm-search" method="get" accept-charset="UTF-8">
				<div class="frm-container">
					<label for="<?php echo $id; ?>" class="sr-only"><?php echo $titleFor; ?></label>
					<input id="<?php echo $id; ?>" required pattern="^(.*\S+.*)$" type="search" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php echo $placeholder; ?>">
					<button class="btn-s" type="submit" aria-label="<?php echo esc_attr__( 'Tìm kiếm', 'SPL' ); ?>">
						<svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
							<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21l-3.5-3.5M17 10a7 7 0 1 1-14 0a7 7 0 0 1 14 0Z"/>
						</svg>
						<span><?php echo $title; ?></span>
					</button>
				</div>
				<?php
				Helper::blockTemplate( 'template-parts/blocks/search-hint' );
				echo '<input type="hidden" name="post_type" value="' . ( Helper::isWoocommerceActive() ? 'product' : 'post' ) . '">';
				?>
			</form>
		</div>
		<?php

		return '<div class="dropdown-search' . $class . '">' . ob_get_clean() . '</div>';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function offCanvasButton( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'title'           => '',
				'hide_if_desktop' => true,
				'class'           => '',
			],
			$atts,
			'off_canvas_button'
		);

		$title  = esc_html( $atts['title'] ?: __( 'Menu', 'SPL' ) );
		$class  = Helper::toBool( $atts['hide_if_desktop'] ) ? ' lg:hidden!' : '';
		$class .= $atts['class'] ? ' ' . Helper::escAttr( $atts['class'] ) : '';

		ob_start();
		?>
		<button class="menu-lines flex items-center gap-3" type="button" data-open="offCanvasMenu" aria-label="<?php echo esc_attr__( 'Menu', 'SPL' ); ?>">
			<span class="line w-7 h-5 flex flex-col flex-nowrap justify-between">
				<span class="line-1 relative w-full"></span>
				<span class="line-2 relative w-full"></span>
				<span class="line-3 relative w-full"></span>
			</span>
			<span class="menu-txt text-[15px] font-light order-1 hidden"><?php echo $title; ?></span>
		</button>
		<?php

		return '<div class="off-canvas-content' . $class . '" data-fx-off-canvas-content>' . ob_get_clean() . '</div>';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string|false
	 */
	public function horizontalMenu( array $atts = [] ): string|false {
		$defaultId = self::generateUniqueId( 'menu' );

		$atts = shortcode_atts(
			[
				'location'         => 'main-nav',
				'class'            => 'dropdown menu horizontal-menu',
				'extra_class'      => '',
				'id'               => $defaultId,
				'depth'            => 4,
				'li_class'         => '',
				'li_depth_class'   => '',
				'link_class'       => '',
				'link_depth_class' => '',
				'submenu_class'    => '',
				'attr'             => '',
				'data_autohide'    => false,
				'data_hover'       => true,
			],
			$atts,
			'horizontal_menu'
		);

		$args                  = $this->buildNavArgs( $atts, $defaultId );
		$args['data_hover']    = Helper::toBool( $atts['data_hover'] );
		$args['data_autohide'] = Helper::toBool( $atts['data_autohide'] );

		return Helper::horizontalNav( $args );
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string|false
	 */
	public function verticalMenu( array $atts = [] ): string|false {
		$defaultId = self::generateUniqueId( 'menu' );

		$atts = shortcode_atts(
			[
				'location'         => 'mobile-nav',
				'class'            => 'menu vertical vertical-menu mobile-menu',
				'extra_class'      => '',
				'id'               => $defaultId,
				'depth'            => 4,
				'li_class'         => '',
				'li_depth_class'   => '',
				'link_class'       => '',
				'link_depth_class' => '',
				'submenu_class'    => '',
			],
			$atts,
			'vertical_menu'
		);

		return Helper::verticalNav( $this->buildNavArgs( $atts, $defaultId ) );
	}

	// ------------------------------------------------------

	/**
	 * Build shared nav arguments from shortcode attributes.
	 *
	 * @param array  $atts      Parsed shortcode attributes.
	 * @param string $defaultId Fallback menu ID.
	 *
	 * @return array
	 */
	private function buildNavArgs( array $atts, string $defaultId ): array {
		$location   = Helper::escAttr( $atts['location'] ?: 'main-nav' );
		$class      = $atts['class'] ? Helper::escAttr( $atts['class'] ) . ' ' . $location : $location;
		$extraClass = $atts['extra_class'] ? Helper::escAttr( $atts['extra_class'] ) : '';

		return [
			'menu_id'          => Helper::escAttr( $atts['id'] ?: $defaultId ),
			'menu_class'       => $extraClass ? $class . ' ' . $extraClass : $class,
			'theme_location'   => $location,
			'depth'            => $atts['depth'] ? absint( $atts['depth'] ) : 1,
			'li_class'         => $atts['li_class'] ? Helper::escAttr( $atts['li_class'] ) : '',
			'li_depth_class'   => $atts['li_depth_class'] ? Helper::escAttr( $atts['li_depth_class'] ) : '',
			'link_class'       => $atts['link_class'] ? Helper::escAttr( $atts['link_class'] ) : '',
			'link_depth_class' => $atts['link_depth_class'] ? Helper::escAttr( $atts['link_depth_class'] ) : '',
			'submenu_class'    => $atts['submenu_class'] ? Helper::escAttr( $atts['submenu_class'] ) : '',
			'echo'             => false,
		];
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string|null
	 */
	public function posts( array $atts = [] ): ?string {
		// Allowed wrapper tags (whitelist for security)
		$allowedTags = [ 'div', 'article', 'section', 'li', 'span', '' ];

		$atts = shortcode_atts(
			[
				'post_type'        => 'post',
				'taxonomy'         => 'category',
				'term_ids'         => [],
				'exclude_ids'      => [],
				'include_children' => false,
				'limit'            => 12,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'wrapper_tag'      => '',
				'wrapper_class'    => '',
				'show'             => [
					'title_tag'      => 'p',
					'thumbnail'      => true,
					'thumbnail_size' => 'medium',
					'scale'          => false,
					'time'           => true,
					'term'           => true,
					'desc'           => true,
					'view_more'      => true,
				],
			],
			$atts,
			'posts'
		);

		// Deep merge 'show' defaults (shortcode_atts only shallow-merges and treats user input as strings)
		$showDefaults = [
			'title_tag'      => 'p',
			'thumbnail'      => true,
			'thumbnail_size' => 'medium',
			'scale'          => false,
			'time'           => true,
			'term'           => true,
			'desc'           => true,
			'view_more'      => true,
		];

		// Decode JSON if 'show' is a string, then fallback to empty array if invalid
		$userShow     = is_string( $atts['show'] ) ? json_decode( html_entity_decode( $atts['show'] ), true ) : $atts['show'];
		$atts['show'] = wp_parse_args( is_array( $userShow ) ? $userShow : [], $showDefaults );

		$termIds         = is_string( $atts['term_ids'] ) ? array_map( 'intval', array_filter( explode( ',', $atts['term_ids'] ) ) ) : ( $atts['term_ids'] ?: [] );
		$excludeIds      = is_string( $atts['exclude_ids'] ) ? array_map( 'intval', explode( ',', $atts['exclude_ids'] ) ) : ( $atts['exclude_ids'] ?: [] );
		$limit           = $atts['limit'] ? absint( $atts['limit'] ) : Helper::getOption( 'posts_per_page' );
		$includeChildren = Helper::toBool( $atts['include_children'] );

		$r = Query::queryByTerms(
			[
				'terms'            => $termIds,
				'post_type'        => $atts['post_type'],
				'taxonomy'         => $atts['taxonomy'],
				'limit'            => $limit,
				'return_query'     => true,
				'include_children' => $includeChildren,
				'exclude_ids'      => $excludeIds,
				'orderby'          => $atts['orderby'],
				'order'            => $atts['order'],
			]
		);

		if ( ! $r ) {
			return null;
		}

		// Sanitize wrapper tag with whitelist
		$wrapperTag = strtolower( trim( $atts['wrapper_tag'] ) );
		if ( ! in_array( $wrapperTag, $allowedTags, true ) ) {
			$wrapperTag = '';
		}

		$wrapperClass = $wrapperTag ? Helper::escAttr( $atts['wrapper_class'] ) : '';
		$wrapperOpen  = $wrapperTag ? '<' . $wrapperTag . ' class="' . $wrapperClass . '">' : '';
		$wrapperClose = $wrapperTag ? '</' . $wrapperTag . '>' : '';

		ob_start();

		while ( $r->have_posts() ) :
			$r->the_post();

			echo $wrapperOpen;
			get_template_part( 'template-parts/post/loop', null, $atts['show'] );
			echo $wrapperClose;
		endwhile;

		wp_reset_postdata();

		return ob_get_clean();
	}
}
