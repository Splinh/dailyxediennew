<?php
/**
 * Group display order for settings sidebar.
 *
 * Modules self-declare their group() — this file only defines
 * the display order, labels, and icons for the sidebar UI.
 *
 * To add a new group: add an entry here and use its key in module group().
 * Order here controls the sidebar rendering order.
 *
 * @package HDAddons
 */

return [
	'general'     => [
		'label' => __( 'General', 'hda' ),
		'icon'  => 'dashicons-admin-generic',
	],
	'security'    => [
		'label' => __( 'Security & Access', 'hda' ),
		'icon'  => 'dashicons-shield',
	],
	'performance' => [
		'label' => __( 'Performance', 'hda' ),
		'icon'  => 'dashicons-performance',
	],
	'tools'       => [
		'label' => __( 'Tools', 'hda' ),
		'icon'  => 'dashicons-admin-tools',
	],
];
