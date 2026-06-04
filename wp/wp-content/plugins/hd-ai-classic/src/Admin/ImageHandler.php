<?php
/**
 * Image handling and sideloading helper.
 *
 * @package HDAC\Admin
 */

namespace HDAC\Admin;

defined( 'ABSPATH' ) || exit;

final class ImageHandler {

	/**
	 * Sideload an image from a URL and attach it to a post as the featured image.
	 *
	 * @param string $url    Remote image URL.
	 * @param int    $postId Target post ID.
	 *
	 * @return int|\WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public static function sideload( string $url, int $postId ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error(
				'hdac_image_permission_denied',
				__( 'You do not have permission to upload files.', 'hd-ai-classic' )
			);
		}

		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Download and insert into Media Library.
		$attachmentId = media_sideload_image( $url, $postId, null, 'id' );

		if ( is_wp_error( $attachmentId ) ) {
			return $attachmentId;
		}

		if ( ! $attachmentId ) {
			return new \WP_Error(
				'hdac_image_sideload_failed',
				__( 'Failed to download and save image to media library.', 'hd-ai-classic' )
			);
		}

		// Set as featured image.
		$result = set_post_thumbnail( $postId, $attachmentId );

		if ( ! $result ) {
			return new \WP_Error(
				'hdac_image_set_thumbnail_failed',
				__( 'Failed to set the downloaded image as the featured image.', 'hd-ai-classic' )
			);
		}

		return (int) $attachmentId;
	}

	/**
	 * Save a base64 image payload to the Media Library and set it as featured image.
	 *
	 * @param string $base64Image Base64 image payload, optionally as a data URI.
	 * @param int    $postId      Target post ID.
	 *
	 * @return int|\WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public static function sideloadBase64( string $base64Image, int $postId ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error(
				'hdac_image_permission_denied',
				__( 'You do not have permission to upload files.', 'hd-ai-classic' )
			);
		}

		$payload = trim( $base64Image );
		if ( '' === $payload ) {
			return new \WP_Error(
				'hdac_image_empty_base64',
				__( 'HDAT returned an empty base64 image payload.', 'hd-ai-classic' )
			);
		}

		if ( preg_match( '/^data:image\/[a-z0-9.+-]+;base64,(.+)$/is', $payload, $matches ) ) {
			$payload = $matches[1];
		}

		$payload = preg_replace( '/\s+/', '', $payload );
		if ( ! is_string( $payload ) || '' === $payload ) {
			return new \WP_Error(
				'hdac_image_invalid_base64',
				__( 'HDAT returned an invalid base64 image payload.', 'hd-ai-classic' )
			);
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Benign use for decoding AI generated image payload.
		$binary = base64_decode( $payload, true );
		if ( false === $binary || '' === $binary ) {
			return new \WP_Error(
				'hdac_image_invalid_base64',
				__( 'HDAT returned an invalid base64 image payload.', 'hd-ai-classic' )
			);
		}

		$maxUploadSize = wp_max_upload_size();
		if ( $maxUploadSize > 0 && strlen( $binary ) > $maxUploadSize ) {
			return new \WP_Error(
				'hdac_image_too_large',
				__( 'The generated image exceeds the maximum upload size.', 'hd-ai-classic' )
			);
		}

		if ( ! function_exists( 'finfo_open' ) || ! defined( 'FILEINFO_MIME_TYPE' ) ) {
			return new \WP_Error(
				'hdac_image_invalid_type',
				__( 'The server cannot validate generated image data.', 'hd-ai-classic' )
			);
		}

		$fileInfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime     = $fileInfo ? finfo_buffer( $fileInfo, $binary ) : false;
		if ( $fileInfo ) {
			finfo_close( $fileInfo );
		}

		$extensions = [
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/webp' => 'webp',
			'image/gif'  => 'gif',
		];
		if ( ! is_string( $mime ) || ! isset( $extensions[ $mime ] ) ) {
			return new \WP_Error(
				'hdac_image_unsupported_type',
				__( 'HDAT returned an unsupported image type.', 'hd-ai-classic' )
			);
		}

		if ( ! function_exists( 'wp_upload_bits' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$filename = sprintf( 'hdac-ai-image-%s.%s', gmdate( 'Ymd-His' ), $extensions[ $mime ] );
		$upload   = wp_upload_bits( $filename, null, $binary );
		if ( ! empty( $upload['error'] ) ) {
			return new \WP_Error(
				'hdac_image_upload_failed',
				(string) $upload['error']
			);
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$attachmentId = wp_insert_attachment(
			[
				'post_mime_type' => $mime,
				'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			],
			$upload['file'],
			$postId
		);

		if ( is_wp_error( $attachmentId ) ) {
			return $attachmentId;
		}

		$metadata = wp_generate_attachment_metadata( (int) $attachmentId, $upload['file'] );
		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( (int) $attachmentId, $metadata );
		}

		$result = set_post_thumbnail( $postId, (int) $attachmentId );
		if ( ! $result ) {
			return new \WP_Error(
				'hdac_image_set_thumbnail_failed',
				__( 'Failed to set the generated image as the featured image.', 'hd-ai-classic' )
			);
		}

		return (int) $attachmentId;
	}
}
