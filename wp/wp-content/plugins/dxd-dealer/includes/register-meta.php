<?php
/**
 * Register & render metabox for local_store CPT.
 *
 * Fields (matching devvn-local-stores-pro):
 *   localstore_address     – Địa chỉ
 *   localstore_phone       – Số điện thoại
 *   localstore_hotline     – Hotline
 *   localstore_email       – Email
 *   localstore_open        – Giờ mở cửa
 *   localstore_brand       – Thương hiệu cửa hàng
 *   localstore_website     – Website
 *   localstore_code        – Mã cửa hàng
 *   localstore_maps_lat    – Latitude
 *   localstore_maps_lng    – Longitude
 *   localstore_marker_icon – Marker icon (attachment ID)
 *   localstore_gallery     – Gallery (comma-separated attachment IDs)
 *
 * @package DXD_Dealer
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', 'dxd_dealer_add_metaboxes' );
add_action( 'save_post_local_store', 'dxd_dealer_save_meta', 10, 2 );

/** All meta keys and their labels. */
function dxd_dealer_meta_fields(): array {
	return [
		'localstore_code'        => __( 'Mã cửa hàng', 'dxd-dealer' ),
		'localstore_address'     => __( 'Địa chỉ', 'dxd-dealer' ),
		'localstore_phone'       => __( 'Số điện thoại', 'dxd-dealer' ),
		'localstore_hotline'     => __( 'Hotline', 'dxd-dealer' ),
		'localstore_email'       => __( 'Email', 'dxd-dealer' ),
		'localstore_open'        => __( 'Giờ mở cửa', 'dxd-dealer' ),
		'localstore_brand'       => __( 'Thương hiệu cửa hàng', 'dxd-dealer' ),
		'localstore_website'     => __( 'Website', 'dxd-dealer' ),
		'localstore_established' => __( 'Hoạt động từ (VD: 2020)', 'dxd-dealer' ),
		'localstore_exhibit'     => __( 'Xe đang trưng bày (VD: 50+ mẫu)', 'dxd-dealer' ),
		'localstore_maps_lat'    => __( 'Latitude', 'dxd-dealer' ),
		'localstore_maps_lng'    => __( 'Longitude', 'dxd-dealer' ),
	];
}

/**
 * Add metaboxes.
 */
function dxd_dealer_add_metaboxes(): void {
	add_meta_box(
		'dxd_dealer_info',
		__( 'Thông tin cửa hàng', 'dxd-dealer' ),
		'dxd_dealer_render_info_metabox',
		'local_store',
		'normal',
		'high'
	);

	add_meta_box(
		'dxd_dealer_gallery',
		__( 'Gallery ảnh', 'dxd-dealer' ),
		'dxd_dealer_render_gallery_metabox',
		'local_store',
		'normal',
		'default'
	);
}

/**
 * Render store info metabox.
 */
function dxd_dealer_render_info_metabox( WP_Post $post ): void {
	wp_nonce_field( 'dxd_dealer_save', 'dxd_dealer_nonce' );

	$fields = dxd_dealer_meta_fields();
	$values = [];
	foreach ( $fields as $key => $label ) {
		$values[ $key ] = get_post_meta( $post->ID, $key, true );
	}
	?>
	<style>
		.dxd-meta-table { width: 100%; border-collapse: collapse; }
		.dxd-meta-table td { padding: 8px 6px; vertical-align: top; }
		.dxd-meta-table .dxd-label { width: 160px; font-weight: 600; color: #1d2327; white-space: nowrap; }
		.dxd-meta-table input[type="text"],
		.dxd-meta-table input[type="email"],
		.dxd-meta-table input[type="url"] { width: 100%; padding: 6px 10px; }
		.dxd-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 24px; }
		.dxd-map-preview { margin-top: 10px; border: 1px solid #c3c4c7; border-radius: 4px; overflow: hidden; }
	</style>

	<div class="dxd-meta-grid">
		<div>
			<table class="dxd-meta-table">
				<?php foreach ( [ 'localstore_code', 'localstore_address', 'localstore_phone', 'localstore_hotline', 'localstore_email', 'localstore_open', 'localstore_brand', 'localstore_website', 'localstore_established', 'localstore_exhibit' ] as $key ) : ?>
				<tr>
					<td class="dxd-label"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $fields[ $key ] ); ?></label></td>
					<td>
						<?php
						$type = 'text';
						if ( str_contains( $key, 'email' ) ) {
							$type = 'email';
						}
						if ( str_contains( $key, 'website' ) ) {
							$type = 'url';
						}
						?>
						<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $values[ $key ] ?? '' ); ?>" />
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<div>
			<table class="dxd-meta-table">
				<tr>
					<td class="dxd-label"><label for="localstore_maps_lat"><?php echo esc_html( $fields['localstore_maps_lat'] ); ?></label></td>
					<td><input type="text" id="localstore_maps_lat" name="localstore_maps_lat" value="<?php echo esc_attr( $values['localstore_maps_lat'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<td class="dxd-label"><label for="localstore_maps_lng"><?php echo esc_html( $fields['localstore_maps_lng'] ); ?></label></td>
					<td><input type="text" id="localstore_maps_lng" name="localstore_maps_lng" value="<?php echo esc_attr( $values['localstore_maps_lng'] ?? '' ); ?>" /></td>
				</tr>
			</table>
			<?php
			$lat = (float) ( $values['localstore_maps_lat'] ?? 0 );
			$lng = (float) ( $values['localstore_maps_lng'] ?? 0 );
			if ( $lat && $lng ) :
				$map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.5!2d' . $lng . '!3d' . $lat . '!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2z!5e0!3m2!1svi!2s!4v1';
				?>
				<div class="dxd-map-preview">
					<iframe src="<?php echo esc_url( $map_url ); ?>" width="100%" height="250" style="border:0;" loading="lazy"></iframe>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Render gallery metabox.
 */
function dxd_dealer_render_gallery_metabox( WP_Post $post ): void {
	$gallery_ids = get_post_meta( $post->ID, 'localstore_gallery', true );
	$ids_array   = $gallery_ids ? array_filter( array_map( 'intval', explode( ',', $gallery_ids ) ) ) : [];
	?>
	<style>
		.dxd-gallery-list { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
		.dxd-gallery-list .dxd-gallery-thumb { width: 120px; height: 80px; border-radius: 6px; overflow: hidden; position: relative; border: 1px solid #ddd; }
		.dxd-gallery-list .dxd-gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }
		.dxd-gallery-list .dxd-gallery-thumb .dxd-remove { position: absolute; top: 2px; right: 2px; background: #d63638; color: #fff; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 11px; line-height: 20px; text-align: center; }
	</style>

	<div class="dxd-gallery-list" id="dxd-gallery-list">
		<?php foreach ( $ids_array as $aid ) :
			$img = wp_get_attachment_image_url( $aid, 'thumbnail' );
			if ( ! $img ) continue;
			?>
			<div class="dxd-gallery-thumb" data-id="<?php echo (int) $aid; ?>">
				<img src="<?php echo esc_url( $img ); ?>" alt="" />
				<button type="button" class="dxd-remove" onclick="this.parentNode.remove(); dxdGallerySync();">&times;</button>
			</div>
		<?php endforeach; ?>
	</div>
	<input type="hidden" name="localstore_gallery" id="localstore_gallery" value="<?php echo esc_attr( $gallery_ids ); ?>" />
	<button type="button" class="button" id="dxd-gallery-add"><?php esc_html_e( 'Add to gallery', 'dxd-dealer' ); ?></button>

	<script>
	(function(){
		function dxdGallerySync() {
			var ids = [];
			document.querySelectorAll('#dxd-gallery-list .dxd-gallery-thumb').forEach(function(el) {
				ids.push(el.dataset.id);
			});
			document.getElementById('localstore_gallery').value = ids.join(',');
		}
		window.dxdGallerySync = dxdGallerySync;

		document.getElementById('dxd-gallery-add').addEventListener('click', function() {
			var frame = wp.media({ multiple: true, library: { type: 'image' } });
			frame.on('select', function() {
				var attachments = frame.state().get('selection').toJSON();
				var list = document.getElementById('dxd-gallery-list');
				attachments.forEach(function(att) {
					var thumb = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
					var div = document.createElement('div');
					div.className = 'dxd-gallery-thumb';
					div.dataset.id = att.id;
					div.innerHTML = '<img src="' + thumb + '" alt="" /><button type="button" class="dxd-remove" onclick="this.parentNode.remove(); dxdGallerySync();">&times;</button>';
					list.appendChild(div);
				});
				dxdGallerySync();
			});
			frame.open();
		});
	})();
	</script>
	<?php
}

/**
 * Save meta on post save.
 */
function dxd_dealer_save_meta( int $post_id, WP_Post $post ): void {
	if ( ! isset( $_POST['dxd_dealer_nonce'] ) || ! wp_verify_nonce( $_POST['dxd_dealer_nonce'], 'dxd_dealer_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$text_fields = [
		'localstore_code',
		'localstore_address',
		'localstore_phone',
		'localstore_hotline',
		'localstore_open',
		'localstore_brand',
		'localstore_website',
		'localstore_established',
		'localstore_exhibit',
		'localstore_maps_lat',
		'localstore_maps_lng',
		'localstore_gallery',
	];

	foreach ( $text_fields as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}

	// Email field — sanitize as email.
	if ( isset( $_POST['localstore_email'] ) ) {
		update_post_meta( $post_id, 'localstore_email', sanitize_email( wp_unslash( $_POST['localstore_email'] ) ) );
	}
}
