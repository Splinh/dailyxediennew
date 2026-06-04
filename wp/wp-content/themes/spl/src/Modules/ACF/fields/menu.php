<?php

use SPL\Core\Helper;
use SPL\Modules\ACF\ACFModule;

defined( 'ABSPATH' ) || exit;

add_action(
	'acf/include_fields',
	static function (): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$acfMenu   = Helper::filterSettingOptions( 'acf_menu', [] );
		$location  = ACFModule::navMenuItemLocationRules( (array) ( $acfMenu['acf_menu_items_locations'] ?? [] ) );

		if ( empty( $location ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'                   => 'group_64bd0aafbaa3a',
				'title'                 => __( 'Attributes of Menu Items', 'SPL' ),
				'fields'                => [
					[
						'key'               => 'field_64bd131c6bca9',
						'label'             => __( 'Link CSS', 'SPL' ),
						'name'              => 'menu_link_class',
						'aria-label'        => '',
						'type'              => 'text',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'maxlength'         => '',
						'placeholder'       => '',
						'prepend'           => '',
						'append'            => '',
					],
					[
						'key'               => 'field_68cb6d4233853',
						'label'             => __( 'Span (optional)', 'SPL' ),
						'name'              => 'menu_span',
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
						'message'           => 'Wrap the title with a `span` tag',
						'default_value'     => 0,
						'ui'                => 0,
						'ui_on_text'        => '',
						'ui_off_text'       => '',
					],
					[
						'key'               => 'field_68cb6d5633854',
						'label'             => __( 'Span CSS', 'SPL' ),
						'name'              => 'menu_span_css',
						'aria-label'        => '',
						'type'              => 'text',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => [
							[
								[
									'field'    => 'field_68cb6d4233853',
									'operator' => '==',
									'value'    => '1',
								],
							],
						],
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'maxlength'         => '',
						'allow_in_bindings' => 0,
						'placeholder'       => '',
						'prepend'           => '',
						'append'            => '',
					],
					[
						'key'               => 'field_68cb6919920d6',
						'label'             => __( 'Svg (optional)', 'SPL' ),
						'name'              => 'menu_svg',
						'aria-label'        => '',
						'type'              => 'textarea',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'maxlength'         => '',
						'allow_in_bindings' => 0,
						'rows'              => 6,
						'placeholder'       => '',
						'new_lines'         => '',
					],
					[
						'key'               => 'field_64bd0ab0ea1d7',
						'label'             => __( 'Thumbnail', 'SPL' ),
						'name'              => 'menu_image',
						'aria-label'        => '',
						'type'              => 'image',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'return_format'     => 'id',
						'library'           => 'all',
						'min_width'         => '',
						'min_height'        => '',
						'min_size'          => '',
						'max_width'         => '',
						'max_height'        => '',
						'max_size'          => '',
						'mime_types'        => 'png,svg,jpg,jpeg,gif,webp',
						'preview_size'      => 'small-100',
					],
					[
						'key'               => 'field_64bd139df7dfd',
						'label'             => __( 'Label', 'SPL' ),
						'name'              => 'menu_label_text',
						'aria-label'        => '',
						'type'              => 'text',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'maxlength'         => '',
						'placeholder'       => '"New", "Hot", "Featured" ...',
						'prepend'           => '',
						'append'            => '',
					],
					[
						'key'               => 'field_64bd13ccf7dfe',
						'label'             => __( 'Label Color', 'SPL' ),
						'name'              => 'menu_label_color',
						'aria-label'        => '',
						'type'              => 'color_picker',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => [
							[
								[
									'field'    => 'field_64bd139df7dfd',
									'operator' => '!=empty',
								],
							],
						],
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'enable_opacity'    => 1,
						'return_format'     => 'string',
					],
					[
						'key'               => 'field_64bd1488092dc',
						'label'             => __( 'Label Background', 'SPL' ),
						'name'              => 'menu_label_background',
						'aria-label'        => '',
						'type'              => 'color_picker',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => [
							[
								[
									'field'    => 'field_64bd139df7dfd',
									'operator' => '!=empty',
								],
							],
						],
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'enable_opacity'    => 1,
						'return_format'     => 'string',
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
				'show_in_rest'          => 1,
			]
		);
	}
);
