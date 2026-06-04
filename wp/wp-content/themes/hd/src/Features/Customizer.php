<?php
/**
 * Customizer and Admin Bar Integration
 *
 * Extends the WordPress Customizer with theme-specific settings.
 * Uses a data-driven approach: sections and fields are defined as
 * configuration arrays and registered via generic helper methods.
 *
 * @package HD\Features
 * @author  HD
 */

namespace HD\Features;

use HD\Contracts\Feature;
use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class Customizer extends Feature {

	/** ---------------------------------------- */

	public function boot(): void {
		add_action( 'wp_before_admin_bar_render', $this->beforeAdminBarRender( ... ) );
		add_action( 'customize_register', $this->customizeRegister( ... ), 30 );
	}

	/** ---------------------------------------- */

	public function beforeAdminBarRender(): void {
		global $wp_admin_bar;

		$wp_admin_bar->remove_menu( 'wp-logo' );
		$wp_admin_bar->remove_menu( 'updates' );

		// Clear cache button
		$currentUrl = wp_nonce_url( add_query_arg( 'clear_cache', '1', sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ), 'hd_clear_cache' );
		$wp_admin_bar->add_menu(
			[
				'id'    => 'clear_cache_button',
				'title' => '<div class="custom-admin-button"><span class="custom-icon">⚡</span><span class="custom-text">' . __( 'Clear cache', 'hd' ) . '</span></div>',
				'href'  => $currentUrl,
			]
		);
	}

	/** ---------------------------------------- */

	/**
	 * Register customizer options.
	 *
	 * @param \WP_Customize_Manager $wpCustomize
	 *
	 * @return void
	 */
	public function customizeRegister( \WP_Customize_Manager $wpCustomize ): void {
		// Hide 'Additional CSS' tab.
		$wpCustomize->remove_section( 'custom_css' );

		// Logo + title — uses existing WP section 'title_tagline'.
		$this->registerFields( $wpCustomize, 'title_tagline', $this->logoFields() );

		// Addon panel.
		$wpCustomize->add_panel(
			'addon_menu_panel',
			[
				'priority'       => 140,
				'theme_supports' => '',
				'title'          => __( 'Addons', 'hd' ),
				'description'    => __( 'Controls the add-on menu', 'hd' ),
			]
		);

		// Register addon sections from config.
		foreach ( $this->addonSections() as $sectionId => $section ) {
			$wpCustomize->add_section(
				$sectionId,
				[
					'title'    => $section['title'],
					'panel'    => 'addon_menu_panel',
					'priority' => $section['priority'],
				]
			);

			$this->registerFields( $wpCustomize, $sectionId, $section['fields'] );
		}
	}

	/* ========== FIELD CONFIGURATION ========== */

	/**
	 * Fields for the built-in "Site Identity" section.
	 *
	 * @return array<string, array>
	 */
	private function logoFields(): array {
		return [
			'alt_logo'             => [
				'type'     => 'image',
				'label'    => __( 'Alternative Logo', 'hd' ),
				'priority' => 8,
			],
			'logo_title_setting'   => [
				'type'     => 'text',
				'label'    => __( 'Logo title', 'hd' ),
				'priority' => 9,
			],
			'home_heading_setting' => [
				'type'     => 'text',
				'label'    => __( 'H1 on the homepage', 'hd' ),
				'priority' => 9,
			],
		];
	}

	/**
	 * Addon panel sections and their fields.
	 * Each key is a section ID; value contains title, priority and fields.
	 *
	 * @return array<string, array{title: string, priority: int, fields: array}>
	 */
	private function addonSections(): array {
		return [

			// ----- Login page -----
			'login_page_section'     => [
				'title'    => __( 'Trang đăng nhập', 'hd' ),
				'priority' => 999,
				'fields'   => [
					'login_page_bgcolor_setting'    => [
						'type'     => 'color',
						'label'    => __( 'Màu nền', 'hd' ),
						'priority' => 8,
					],
					'login_page_bgimage_setting'    => [
						'type'     => 'image',
						'label'    => __( 'Ảnh nền', 'hd' ),
						'priority' => 9,
					],
					'login_page_logo_setting'       => [
						'type'     => 'image',
						'label'    => __( 'Logo', 'hd' ),
						'priority' => 10,
					],
					'login_page_headertext_setting' => [
						'type'        => 'text',
						'label'       => __( 'Văn bản tiêu đề', 'hd' ),
						'priority'    => 11,
						'description' => __( 'Thay đổi văn bản thay thế (alt)', 'hd' ),
					],
					'login_page_headerurl_setting'  => [
						'type'        => 'url',
						'label'       => __( 'URL của tiêu đề', 'hd' ),
						'priority'    => 12,
						'description' => __( 'Thay đổi đường dẫn của logo', 'hd' ),
					],
				],
			],

			// ----- OffCanvas -----
			'offcanvas_menu_section' => [
				'title'    => __( 'OffCanvas', 'hd' ),
				'priority' => 1000,
				'fields'   => [
					'offcanvas_menu_setting' => [
						'type'    => 'radio',
						'label'   => __( 'offCanvas position', 'hd' ),
						'choices' => [
							'left'    => __( 'Left', 'hd' ),
							'right'   => __( 'Right', 'hd' ),
							'top'     => __( 'Top', 'hd' ),
							'bottom'  => __( 'Bottom', 'hd' ),
							'default' => __( 'Default (Left)', 'hd' ),
						],
					],
				],
			],

			// ----- Breadcrumb -----
			'breadcrumb_section'     => [
				'title'    => __( 'Breadcrumb', 'hd' ),
				'priority' => 1007,
				'fields'   => [
					'breadcrumb_bg_setting'         => [
						'type'     => 'image',
						'label'    => __( 'Background image', 'hd' ),
						'priority' => 9,
					],
					'breadcrumb_bgcolor_setting'    => [
						'type'     => 'color',
						'label'    => __( 'Background color', 'hd' ),
						'priority' => 9,
					],
					'breadcrumb_color_setting'      => [
						'type'     => 'color',
						'label'    => __( 'Text color', 'hd' ),
						'priority' => 9,
					],
					'breadcrumb_min_height_setting' => [
						'type'        => 'number',
						'label'       => __( 'Breadcrumb min-height', 'hd' ),
						'description' => __( 'Min-height of breadcrumb section', 'hd' ),
					],
					'breadcrumb_max_height_setting' => [
						'type'        => 'number',
						'label'       => __( 'Breadcrumb max-height', 'hd' ),
						'description' => __( 'Max-height of breadcrumb section', 'hd' ),
					],
				],
			],

			// ----- Footer -----
			'footer_section'         => [
				'title'    => __( 'Footer', 'hd' ),
				'priority' => 1010,
				'fields'   => [
					'footer_credit_setting' => [
						'type'     => 'text',
						'label'    => __( 'Footer copyright', 'hd' ),
						'priority' => 10,
					],
				],
			],

			// ----- Other -----
			'other_section'          => [
				'title'    => __( 'Other', 'hd' ),
				'priority' => 1011,
				'fields'   => [
					'theme_color_setting' => [
						'type'  => 'color',
						'label' => __( 'Theme Color', 'hd' ),
					],
					'remove_menu_setting' => [
						'type'        => 'textarea',
						'label'       => __( 'Remove Menu', 'hd' ),
						'description' => __( 'The menu list will be hidden', 'hd' ),
					],
				],
			],
		];
	}

	/* ========== REGISTRATION HELPERS ========== */

	/**
	 * Register an array of fields (setting + control) for a section.
	 *
	 * @param \WP_Customize_Manager $wpCustomize
	 * @param string                $section
	 * @param array<string, array>  $fields  keyed by setting ID
	 *
	 * @return void
	 */
	private function registerFields( \WP_Customize_Manager $wpCustomize, string $section, array $fields ): void {
		foreach ( $fields as $settingId => $field ) {
			$type = $field['type'] ?? 'text';

			// Register setting.
			$wpCustomize->add_setting(
				$settingId,
				[
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => $this->sanitizeCallbackFor( $type ),
				]
			);

			// Register control.
			$controlId   = str_ends_with( $settingId, '_setting' ) ? str_replace( '_setting', '_control', $settingId ) : $settingId;
			$controlArgs = array_filter(
				[
					'label'       => $field['label'] ?? '',
					'section'     => $section,
					'settings'    => $settingId,
					'priority'    => $field['priority'] ?? null,
					'description' => $field['description'] ?? null,
				],
				static fn( $v ) => $v !== null
			);

				match ( $type ) {
					'image' => $wpCustomize->add_control( new \WP_Customize_Image_Control( $wpCustomize, $controlId, $controlArgs ) ),
					'color' => $wpCustomize->add_control( new \WP_Customize_Color_Control( $wpCustomize, $controlId, $controlArgs ) ),
					default => $wpCustomize->add_control(
						$controlId,
						array_merge(
							$controlArgs,
							[ 'type' => $type ],
							isset( $field['choices'] ) ? [ 'choices' => $field['choices'] ] : []
						)
					),
				};
		}
	}

	/**
	 * Return the sanitize callback for a given field type.
	 *
	 * @param string $type
	 *
	 * @return callable|string
	 */
	private function sanitizeCallbackFor( string $type ): callable|string {
		return match ( $type ) {
			'color'    => 'sanitize_hex_color',
			'image'    => Helper::sanitizeImage( ... ),
			'textarea' => 'sanitize_textarea_field',
			'url'      => 'esc_url_raw',
			default    => 'sanitize_text_field',
		};
	}
}
