<?php
/**
 * Import timeline milestone images to WordPress Media Library & ACF.
 *
 * @package SPL
 */

require_once __DIR__ . '/../../../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$p = get_page_by_path( 'gioi-thieu', OBJECT, 'page' );
if ( ! $p ) {
	echo "Page gioi-thieu not found\n";
	exit;
}

$map = [
	0 => 'timeline_2013_founding_1786175168990.png',
	1 => 'timeline_2015_factory_1786175183412.png',
	2 => 'timeline_2018_scale_1786175198872.png',
	3 => 'timeline_2021_digital_1786175211739.png',
	4 => 'timeline_2023_ai_ebike_1786175227144.png',
	5 => 'timeline_2024_network_1786175240864.png',
	6 => 'timeline_2026_showroom3s_1786175252670.png',
];

$dir_url = get_template_directory_uri() . '/resources/img/';

$sections = get_post_meta( $p->ID, 'about_sections', true );
if ( is_array( $sections ) ) {
	foreach ( $sections as $sec_idx => &$s ) {
		if ( ( $s['acf_fc_layout'] ?? '' ) === 'about_timeline' ) {
			echo "Found timeline layout at index: {$sec_idx}\n";
			foreach ( $map as $item_idx => $filename ) {
				$img_url = $dir_url . $filename;
				$attach_id = media_sideload_image( $img_url, $p->ID, "Cột mốc {$filename}", 'id' );
				if ( is_wp_error( $attach_id ) ) {
					echo "Sideload error for {$filename}: " . $attach_id->get_error_message() . "\n";
					$val = $img_url;
				} else {
					echo "Uploaded {$filename} -> attachment ID {$attach_id}\n";
					$val = $attach_id;
				}

				$s['items'][ $item_idx ]['image'] = $val;

				$meta_key = "about_sections_{$sec_idx}_items_{$item_idx}_image";
				update_post_meta( $p->ID, $meta_key, $val );
				update_post_meta( $p->ID, '_' . $meta_key, 'field_about_timeline_item_image' );
				echo "Updated {$meta_key} = {$val}\n";
			}
		}
	}
	update_post_meta( $p->ID, 'about_sections', $sections );
	echo "SUCCESSFULLY_SAVED_ALL_ACF_FIELDS\n";
}
