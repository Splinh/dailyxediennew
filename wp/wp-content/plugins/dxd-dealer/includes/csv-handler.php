<?php
/**
 * CSV Import / Export handler for local_store CPT.
 *
 * CSV columns (matching cua-hang-2026-06-06.csv):
 *   ma_cua_hang, ten_cua_hang, noi_dung, dia_chi, dien_thoai, hotline,
 *   email, gio_mo_cua, website, lat, lng, tinh_thanh, quan_huyen,
 *   loai_cua_hang, featured_image_url, gallery_urls
 *
 * Import logic:
 *   - If ma_cua_hang matches existing post meta → update
 *   - If ten_cua_hang matches existing post title → update
 *   - Otherwise → create new
 *   - tinh_thanh → local_store_state taxonomy term (create if needed)
 *   - loai_cua_hang → store_type taxonomy term (match by slug)
 *   - featured_image_url → sideload as featured image
 *   - gallery_urls (pipe-separated) → sideload as gallery
 *
 * Export logic:
 *   - Query all local_store posts → write CSV with same columns
 *
 * @package DXD_Dealer
 */

defined( 'ABSPATH' ) || exit;

/**
 * CSV column headers.
 */
function dxd_dealer_csv_headers(): array {
	return [
		'ma_cua_hang',
		'ten_cua_hang',
		'noi_dung',
		'dia_chi',
		'dien_thoai',
		'hotline',
		'email',
		'gio_mo_cua',
		'website',
		'lat',
		'lng',
		'tinh_thanh',
		'quan_huyen',
		'loai_cua_hang',
		'featured_image_url',
		'gallery_urls',
	];
}

// ════════════════════════════════════════════════════════════════
//  EXPORT
// ════════════════════════════════════════════════════════════════

/**
 * Export all stores as CSV download.
 */
function dxd_dealer_export_csv(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Bạn không có quyền thực hiện thao tác này.', 'dxd-dealer' ) );
	}

	$stores = get_posts( [
		'post_type'      => 'local_store',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	$filename = 'cua-hang-' . gmdate( 'Y-m-d' ) . '.csv';

	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$out = fopen( 'php://output', 'w' );

	// BOM for Excel UTF-8.
	fwrite( $out, "\xEF\xBB\xBF" );

	// Header row.
	fputcsv( $out, dxd_dealer_csv_headers() );

	foreach ( $stores as $store ) {
		$sid = $store->ID;

		// Taxonomies.
		$state_terms = get_the_terms( $sid, 'local_store_state' );
		$type_terms  = get_the_terms( $sid, 'store_type' );

		$tinh_thanh    = ( $state_terms && ! is_wp_error( $state_terms ) ) ? $state_terms[0]->name : '';
		$loai_cua_hang = ( $type_terms && ! is_wp_error( $type_terms ) ) ? $type_terms[0]->slug : '';

		// Featured image URL.
		$feat_url = get_the_post_thumbnail_url( $sid, 'full' ) ?: '';

		// Gallery URLs (pipe-separated).
		$gallery_ids = get_post_meta( $sid, 'localstore_gallery', true );
		$gallery_urls = '';
		if ( $gallery_ids ) {
			$urls = [];
			foreach ( array_filter( array_map( 'intval', explode( ',', $gallery_ids ) ) ) as $aid ) {
				$url = wp_get_attachment_url( $aid );
				if ( $url ) {
					$urls[] = $url;
				}
			}
			$gallery_urls = implode( '|', $urls );
		}

		$row = [
			get_post_meta( $sid, 'localstore_code', true ),    // ma_cua_hang
			$store->post_title,                                 // ten_cua_hang
			$store->post_content,                               // noi_dung
			get_post_meta( $sid, 'localstore_address', true ),  // dia_chi
			get_post_meta( $sid, 'localstore_phone', true ),    // dien_thoai
			get_post_meta( $sid, 'localstore_hotline', true ),  // hotline
			get_post_meta( $sid, 'localstore_email', true ),    // email
			get_post_meta( $sid, 'localstore_open', true ),     // gio_mo_cua
			get_post_meta( $sid, 'localstore_website', true ),  // website
			get_post_meta( $sid, 'localstore_maps_lat', true ), // lat
			get_post_meta( $sid, 'localstore_maps_lng', true ), // lng
			$tinh_thanh,                                        // tinh_thanh
			'',                                                 // quan_huyen (reserved)
			$loai_cua_hang,                                     // loai_cua_hang
			$feat_url,                                          // featured_image_url
			$gallery_urls,                                      // gallery_urls
		];

		fputcsv( $out, $row );
	}

	fclose( $out );
	exit;
}

// ════════════════════════════════════════════════════════════════
//  IMPORT
// ════════════════════════════════════════════════════════════════

/**
 * Import stores from uploaded CSV.
 *
 * @return array{ imported: int, updated: int, skipped: int, errors: list<string> }
 */
function dxd_dealer_import_csv( string $file_path ): array {
	$result = [ 'imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [] ];

	$handle = fopen( $file_path, 'r' );
	if ( ! $handle ) {
		$result['errors'][] = __( 'Không thể mở file CSV.', 'dxd-dealer' );
		return $result;
	}

	// Read header row.
	$header = fgetcsv( $handle );
	if ( ! $header ) {
		fclose( $handle );
		$result['errors'][] = __( 'File CSV trống hoặc không hợp lệ.', 'dxd-dealer' );
		return $result;
	}

	// Strip BOM.
	$header[0] = preg_replace( '/^\x{FEFF}/u', '', $header[0] );

	// Map header to indices.
	$expected = dxd_dealer_csv_headers();
	$col_map  = [];
	foreach ( $expected as $col ) {
		$idx = array_search( $col, $header, true );
		$col_map[ $col ] = ( $idx !== false ) ? $idx : -1;
	}

	// Validate required columns.
	if ( $col_map['ten_cua_hang'] === -1 ) {
		fclose( $handle );
		$result['errors'][] = __( 'CSV thiếu cột "ten_cua_hang".', 'dxd-dealer' );
		return $result;
	}

	$row_num = 1;
	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$row_num++;

		$get = function ( string $col ) use ( $row, $col_map ): string {
			$idx = $col_map[ $col ] ?? -1;
			return ( $idx >= 0 && isset( $row[ $idx ] ) ) ? trim( $row[ $idx ] ) : '';
		};

		$title = $get( 'ten_cua_hang' );
		if ( ! $title ) {
			$result['skipped']++;
			continue;
		}

		$code = $get( 'ma_cua_hang' );

		// ── Find existing post ──
		$existing_id = 0;

		// 1. Match by code.
		if ( $code ) {
			$by_code = get_posts( [
				'post_type'      => 'local_store',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => 'localstore_code',
				'meta_value'     => $code,
				'fields'         => 'ids',
			] );
			if ( $by_code ) {
				$existing_id = $by_code[0];
			}
		}

		// 2. Match by title.
		if ( ! $existing_id ) {
			$by_title = get_page_by_title( $title, OBJECT, 'local_store' );
			if ( $by_title ) {
				$existing_id = $by_title->ID;
			}
		}

		// ── Prepare post data ──
		$post_data = [
			'post_type'    => 'local_store',
			'post_title'   => $title,
			'post_content' => $get( 'noi_dung' ),
			'post_status'  => 'publish',
		];

		if ( $existing_id ) {
			$post_data['ID'] = $existing_id;
			$pid = wp_update_post( $post_data, true );
			if ( is_wp_error( $pid ) ) {
				$result['errors'][] = sprintf( 'Row %d: %s', $row_num, $pid->get_error_message() );
				continue;
			}
			$pid = $existing_id;
			$result['updated']++;
		} else {
			$pid = wp_insert_post( $post_data, true );
			if ( is_wp_error( $pid ) ) {
				$result['errors'][] = sprintf( 'Row %d: %s', $row_num, $pid->get_error_message() );
				continue;
			}
			$result['imported']++;
		}

		// ── Meta fields ──
		$meta_map = [
			'ma_cua_hang' => 'localstore_code',
			'dia_chi'     => 'localstore_address',
			'dien_thoai'  => 'localstore_phone',
			'hotline'     => 'localstore_hotline',
			'email'       => 'localstore_email',
			'gio_mo_cua'  => 'localstore_open',
			'website'     => 'localstore_website',
			'lat'         => 'localstore_maps_lat',
			'lng'         => 'localstore_maps_lng',
		];

		foreach ( $meta_map as $csv_col => $meta_key ) {
			$val = $get( $csv_col );
			if ( $val !== '' ) {
				update_post_meta( $pid, $meta_key, sanitize_text_field( $val ) );
			}
		}

		// ── Taxonomy: tinh_thanh → local_store_state ──
		$tinh = $get( 'tinh_thanh' );
		if ( $tinh ) {
			$term = get_term_by( 'name', $tinh, 'local_store_state' );
			if ( ! $term ) {
				$inserted = wp_insert_term( $tinh, 'local_store_state' );
				if ( ! is_wp_error( $inserted ) ) {
					$term_id = $inserted['term_id'];
				}
			} else {
				$term_id = $term->term_id;
			}
			if ( ! empty( $term_id ) ) {
				wp_set_object_terms( $pid, [ (int) $term_id ], 'local_store_state' );
			}
		}

		// ── Taxonomy: loai_cua_hang → store_type ──
		$loai = $get( 'loai_cua_hang' );
		if ( $loai ) {
			$term = get_term_by( 'slug', $loai, 'store_type' );
			if ( ! $term ) {
				// Try by name.
				$term = get_term_by( 'name', $loai, 'store_type' );
			}
			if ( $term ) {
				wp_set_object_terms( $pid, [ (int) $term->term_id ], 'store_type' );
			}
		}

		// ── Featured image (sideload from URL) ──
		$feat_url = $get( 'featured_image_url' );
		if ( $feat_url && ! has_post_thumbnail( $pid ) ) {
			$att_id = dxd_dealer_sideload_image( $feat_url, $pid );
			if ( $att_id && ! is_wp_error( $att_id ) ) {
				set_post_thumbnail( $pid, $att_id );
			}
		}

		// ── Gallery images (pipe-separated URLs) ──
		$gallery_raw = $get( 'gallery_urls' );
		if ( $gallery_raw ) {
			$existing_gallery = get_post_meta( $pid, 'localstore_gallery', true );
			if ( ! $existing_gallery ) {
				$urls    = array_filter( array_map( 'trim', explode( '|', $gallery_raw ) ) );
				$att_ids = [];
				foreach ( $urls as $gurl ) {
					$gid = dxd_dealer_sideload_image( $gurl, $pid );
					if ( $gid && ! is_wp_error( $gid ) ) {
						$att_ids[] = $gid;
					}
				}
				if ( $att_ids ) {
					update_post_meta( $pid, 'localstore_gallery', implode( ',', $att_ids ) );
				}
			}
		}
	}

	fclose( $handle );
	return $result;
}

/**
 * Sideload an image from URL into the media library.
 *
 * @return int|WP_Error Attachment ID or error.
 */
function dxd_dealer_sideload_image( string $url, int $post_id ): int|WP_Error {
	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	// Check if URL already exists in media library to avoid duplicates.
	$existing = get_posts( [
		'post_type'      => 'attachment',
		'posts_per_page' => 1,
		'meta_key'       => '_dxd_source_url',
		'meta_value'     => $url,
		'fields'         => 'ids',
	] );

	if ( $existing ) {
		return $existing[0];
	}

	// Extract filename for description.
	$filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );

	$att_id = media_sideload_image( $url, $post_id, $filename, 'id' );

	if ( ! is_wp_error( $att_id ) ) {
		// Store source URL to prevent re-importing.
		update_post_meta( $att_id, '_dxd_source_url', $url );
	}

	return $att_id;
}
