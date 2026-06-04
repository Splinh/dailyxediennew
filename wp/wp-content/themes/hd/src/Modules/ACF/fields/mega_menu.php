<?php

use HD\Core\Helper;
use HD\Modules\ACF\ACFModule;

defined( 'ABSPATH' ) || exit;

add_action(
	'acf/include_fields',
	static function (): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$acfMenu   = Helper::filterSettingOptions( 'acf_menu', [] );
		$location  = ACFModule::navMenuItemLocationRules( (array) ( $acfMenu['acf_mega_menu_locations'] ?? [] ) );

		if ( empty( $location ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'                   => 'group_64c8be6be97d0',
				'title'                 => __( 'Attributes of Menu', 'hd' ),
				'fields'                => [
					[
						'key'               => 'field_64c8be6c6147a',
						'label'             => __( 'Mega menu (optional)', 'hd' ),
						'name'              => 'menu_mega',
						'aria-label'        => '',
						'type'              => 'true_false',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => 'checkbox',
							'id'    => '',
						],
						'message'           => 'Mega menu',
						'default_value'     => 0,
						'ui'                => 0,
						'ui_on_text'        => '',
						'ui_off_text'       => '',
					],
				],
				'location'              => $location,
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => '',
				'show_in_rest'          => 0,
			]
		);
	}
);
