<?php
/**
 * ACF Nav Menu Field Type.
 *
 * Provides a custom ACF field for selecting WordPress navigation menus.
 *
 * @author Galaxy Weblinks
 * @link   https://wordpress.org/plugins/acf-nav-menu/
 *
 * Modified by HD
 */

namespace HD\Modules\ACF\FieldTypes;

defined( 'ABSPATH' ) || exit;

class NavMenu extends \acf_field {

	/**
	 * Cached navigation menus.
	 *
	 * @var array<int|string, string>|null
	 */
	private ?array $cachedMenus = null;

	// ----------------------------------------------

	/**
	 * Initialize the Nav Menu field type.
	 */
	public function __construct() {
		$this->name     = 'nav_menu';
		$this->label    = esc_html__( 'Nav Menu', 'hd' );
		$this->category = 'choice';
		$this->defaults = [
			'save_format' => 'menu',
			'allow_null'  => 0,
			'container'   => 'div',
		];

		parent::__construct();
	}

	// ----------------------------------------------

	/**
	 * Render field settings in ACF admin.
	 *
	 * @param array $field Field configuration.
	 */
	public function render_field_settings( $field ) {
		// Register the Return Value format setting
		acf_render_field_setting(
			$field,
			[
				'label'        => esc_html__( 'Return Value', 'hd' ),
				'instructions' => esc_html__( 'Specify the returned value on front end', 'hd' ),
				'type'         => 'radio',
				'name'         => 'save_format',
				'layout'       => 'horizontal',
				'choices'      => [
					'menu'   => esc_html__( 'Nav Menu HTML', 'hd' ),
					'object' => esc_html__( 'Nav Menu Object', 'hd' ),
					'id'     => esc_html__( 'Nav Menu ID', 'hd' ),
				],
			]
		);

		// Register the Menu Container setting
		acf_render_field_setting(
			$field,
			[
				'label'        => esc_html__( 'Menu Container', 'hd' ),
				'instructions' => esc_html__( "What to wrap the Menu's ul with (when returning HTML only)", 'hd' ),
				'type'         => 'select',
				'name'         => 'container',
				'choices'      => $this->getAllowedContainerTags(),
			]
		);

		// Register the Allow Null setting
		acf_render_field_setting(
			$field,
			[
				'label'   => esc_html__( 'Allow Null?', 'hd' ),
				'type'    => 'radio',
				'name'    => 'allow_null',
				'layout'  => 'horizontal',
				'choices' => [
					1 => esc_html__( 'Yes', 'hd' ),
					0 => esc_html__( 'No', 'hd' ),
				],
			]
		);
	}

	// ----------------------------------------------

	/**
	 * Get allowed HTML container tags for nav menu.
	 *
	 * @return array<string, string> Tag => Label pairs.
	 */
	private function getAllowedContainerTags(): array {
		$tags = $this->normalizeContainerTags( apply_filters( 'wp_nav_menu_container_allowedtags', [ 'div', 'nav' ] ) );
		$tags = $tags ?: [ 'div', 'nav' ];

		$choices = [ '0' => esc_html__( 'None', 'hd' ) ];
		foreach ( $tags as $tag ) {
			$choices[ $tag ] = ucfirst( $tag );
		}

		return $choices;
	}

	// ----------------------------------------------

	/**
	 * Render the field input in admin.
	 *
	 * @param array $field Field configuration.
	 */
	public function render_field( $field ) {
		$allowNull = (bool) $field['allow_null'];
		$navMenus  = $this->getNavMenus( $allowNull );

		if ( ! $navMenus ) {
			echo '<p class="description acf-nav-menu__empty">' . esc_html__( 'No navigation menus found. Create a menu in Appearance > Menus, then return to this field.', 'hd' ) . '</p>';
			return;
		}

		?>
		<div class="custom-acf-nav-menu">
			<select title="" id="<?php echo esc_attr( $field['id'] ); ?>" class="<?php echo esc_attr( $field['class'] ); ?>"
					name="<?php echo esc_attr( $field['name'] ); ?>">
				<?php foreach ( $navMenus as $navMenuId => $navMenuName ) : ?>
					<option value="<?php echo esc_attr( $navMenuId ); ?>" <?php selected( $field['value'], $navMenuId ); ?>>
						<?php echo esc_html( $navMenuName ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	// ----------------------------------------------

	/**
	 * Get available navigation menus with caching.
	 *
	 * @param bool $allowNull Whether to include empty option.
	 *
	 * @return array<int|string, string> Menu ID => Menu Name pairs.
	 */
	private function getNavMenus( bool $allowNull = false ): array {
		// Build menus cache if not already cached
		if ( null === $this->cachedMenus ) {
			$this->cachedMenus = [];

			$navs = get_terms(
				[
					'taxonomy'   => 'nav_menu',
					'hide_empty' => false,
				]
			);

			// Check for errors or empty result
			if ( $navs && ! is_wp_error( $navs ) ) {
				foreach ( $navs as $nav ) {
					$this->cachedMenus[ $nav->term_id ] = $nav->name;
				}
			}
		}

		// Prepend empty option if allowed
		if ( $allowNull ) {
			return [ '' => esc_html__( '- Select -', 'hd' ) ] + $this->cachedMenus;
		}

		return $this->cachedMenus;
	}

	// ----------------------------------------------

	/**
	 * Format the field value for frontend output.
	 *
	 * ACF can pass $postId as string for options pages, terms, users, etc.
	 * Examples: "options", "term_5", "user_1", "widget_123"
	 *
	 * @param mixed $value The field value.
	 * @param int|string $postId The post ID or location identifier.
	 * @param array $field The field configuration.
	 *
	 * @return \stdClass|string|int|false Nav Menu Object, HTML, ID, or false if empty.
	 */
	public function format_value( $value, $postId, $field ) {
		// Bail early if no value
		if ( empty( $value ) ) {
			return false;
		}

		return match ( $field['save_format'] ?? 'menu' ) {
			'object' => $this->formatAsObject( $value ),
			'menu'   => $this->formatAsHtml( $value, $field ),
			'id'     => (int) $value,
			default  => false,
		};
	}

	// ----------------------------------------------

	/**
	 * Format value as menu object.
	 *
	 * @param mixed $value Menu ID or slug.
	 *
	 * @return \stdClass|false Menu object or false if not found.
	 */
	private function formatAsObject( mixed $value ): \stdClass|false {
		$wpMenuObject = wp_get_nav_menu_object( $value );
		if ( ! $wpMenuObject ) {
			return false;
		}

		$menuObject        = new \stdClass();
		$menuObject->ID    = $wpMenuObject->term_id;
		$menuObject->name  = $wpMenuObject->name;
		$menuObject->slug  = $wpMenuObject->slug;
		$menuObject->count = $wpMenuObject->count;

		return $menuObject;
	}

	// ----------------------------------------------

	/**
	 * Format value as HTML.
	 *
	 * @param mixed $value Menu ID or slug.
	 * @param array $field Field configuration.
	 *
	 * @return string|false Menu HTML or false if menu not found.
	 */
	private function formatAsHtml( mixed $value, array $field ): string|false {
		$container = $this->normalizeContainerValue( $field['container'] ?? 'div' );

		return wp_nav_menu(
			[
				'echo'            => false,
				'menu'            => $value,
				'container_class' => 'acf-nav-menu',
				'container'       => $container,
				'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				'fallback_cb'     => '__return_false', // Return false instead of default menu fallback
			]
		);
	}

	/**
	 * @return string[]
	 */
	private function normalizeContainerTags( mixed $tags ): array {
		if ( ! is_array( $tags ) ) {
			return [];
		}

		$normalized = [];
		foreach ( $tags as $tag ) {
			$tag = strtolower( trim( (string) $tag ) );
			if ( preg_match( '/^[a-z][a-z0-9-]*$/', $tag ) ) {
				$normalized[] = $tag;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	private function normalizeContainerValue( mixed $container ): string|false {
		$container = strtolower( trim( (string) $container ) );
		if ( '' === $container || '0' === $container ) {
			return false;
		}

		$allowed = array_keys( $this->getAllowedContainerTags() );

		return in_array( $container, $allowed, true ) ? $container : 'div';
	}
}
