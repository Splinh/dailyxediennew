<?php

use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

add_action(
	'acf/include_fields',
	static function (): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$adminListTable = Helper::filterSettingOptions( 'admin_list_table', [] );
		$termColumns    = (array) ( $adminListTable['term_thumb_columns'] ?? [] );

		$location = array_values(
			array_filter(
				array_map(
					static fn( $termColumn ) => $termColumn
					? [
						[
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => Helper::toString( $termColumn ),
						],
					]
					: null,
					$termColumns
				)
			)
		);

		if ( empty( $location ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'                   => 'group_64b3b263d91cc',
				'title'                 => __( 'Taxonomy', 'hd' ),
				'fields'                => [
					[
						'key'               => 'field_64b3b26480fb4',
						'label'             => __( 'Thumbnail', 'hd' ),
						'name'              => 'term_thumb',
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
						'preview_size'      => 'small-300',
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
	},
	11
);
