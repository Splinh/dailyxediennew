<?php
/**
 * SVG support in WordPress.
 *
 * Provides safe SVG upload capability with optional sanitization.
 *
 * @author ShortPixel
 * @link   https://github.com/shortpixel-ai
 *
 * Modified by HD
 *
 * @package HDAddons\Modules\File
 */

namespace HDAddons\Modules\File;

use HDAddons\Helper;
use enshrined\svgSanitize\data\AllowedAttributes;
use enshrined\svgSanitize\data\AllowedTags;
use enshrined\svgSanitize\Sanitizer;

\defined( 'ABSPATH' ) || exit;

final class SVG {

	/**
	 * SVG sanitizer instance.
	 */
	private Sanitizer $sanitizer;

	/**
	 * SVG handling mode: 'disable', 'sanitized', or 'unrestricted'.
	 */
	private string $svgOption;

	// ------------------------------------------------------

	/**
	 * Initialize SVG support based on settings.
	 */
	public function __construct() {
		$file_options    = Helper::getOption( FileModule::optionKey(), [] );
		$this->svgOption = $file_options[ FileModule::KEY_SVGS ] ?? 'disable';

		if ( 'disable' !== $this->svgOption ) {
			$this->initSvgSupport();
		}
	}

	// ------------------------------------------------------

	/**
	 * Initialize SVG handling hooks and filters.
	 *
	 * @return void
	 */
	private function initSvgSupport(): void {
		$this->sanitizer = new Sanitizer();
		$this->sanitizer->removeXMLTag( true );
		$this->sanitizer->minify( true );

		add_action( 'admin_footer', $this->fixSvgThumbnailSize( ... ) );
		add_action( 'print_media_templates', $this->printSvgMediaTemplates( ... ) );
		add_filter( 'wp_prepare_attachment_for_js', $this->prepareAttachmentForJs( ... ), 10, 3 );

		add_filter( 'wp_handle_upload_prefilter', $this->handleUploadPrefilter( ... ) );
		add_filter( 'wp_check_filetype_and_ext', $this->checkFiletypeAndExt( ... ), 100, 4 );
		add_filter( 'wp_generate_attachment_metadata', $this->generateAttachmentMetadata( ... ), 10, 2 );

		add_filter( 'upload_mimes', $this->addSvgMime( ... ) );
		add_filter( 'fl_module_upload_regex', $this->filterBeaverBuilderRegex( ... ), 10, 4 );
		add_filter( 'render_block', $this->fixMissingImageDimensions( ... ), 10, 2 );
		add_filter( 'intermediate_image_sizes_advanced', $this->disableUploadSizes( ... ), 101, 3 );
	}

	// ------------------------------------------------------

	/**
	 * Disable intermediate image sizes for SVG uploads.
	 *
	 * @param array $sizes Available image sizes.
	 * @param array $metadata Attachment metadata.
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array Filtered sizes (empty for SVG).
	 */
	public function disableUploadSizes( array $sizes, array $metadata, int $attachment_id ): array {
		if ( 'image/svg+xml' === get_post_mime_type( $attachment_id ) ) {
			return [];
		}

		return $sizes;
	}

	// ------------------------------------------------------

	/**
	 * Add missing width/height attributes to SVG image blocks.
	 *
	 * @param string $block_content Block HTML content.
	 * @param array $block Block configuration.
	 *
	 * @return string Modified block content.
	 */
	public function fixMissingImageDimensions( string $block_content, array $block ): string {
		if (
			! isset( $block['attrs']['id'] ) ||
			str_contains( $block_content, 'width=' ) ||
			str_contains( $block_content, 'height=' ) ||
			'core/image' !== $block['blockName'] ||
			'image/svg+xml' !== get_post_mime_type( $block['attrs']['id'] )
		) {
			return $block_content;
		}

		$svg_path = get_attached_file( $block['attrs']['id'] );

		if ( ! $svg_path || ! file_exists( $svg_path ) ) {
			return $block_content;
		}

		$dimensions = $this->getSvgDimensions( $svg_path );

		return preg_replace(
			'/<img\s/',
			sprintf( '<img width="%d" height="%d" ', $dimensions->width, $dimensions->height ),
			$block_content,
			1
		);
	}

	// ------------------------------------------------------

	/**
	 * Add SVG support for Beaver Builder module uploads.
	 *
	 * @param array $regex Regex patterns.
	 * @param string $type Upload type.
	 * @param string $ext File extension.
	 * @param string $file File path.
	 *
	 * @return array Modified regex patterns.
	 */
	public function filterBeaverBuilderRegex( array $regex, string $type, string $ext, string $file ): array {
		if ( 'svg' === $ext || 'svgz' === $ext ) {
			$regex['photo'] = str_replace( '|png|', '|png|svgz?|', $regex['photo'] );
		}

		return $regex;
	}

	// ------------------------------------------------------

	/**
	 * Generate attachment metadata with SVG dimensions.
	 *
	 * @param array $metadata Attachment metadata.
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array Modified metadata.
	 */
	public function generateAttachmentMetadata( array $metadata, int $attachment_id ): array {
		if ( 'image/svg+xml' !== get_post_mime_type( $attachment_id ) ) {
			return $metadata;
		}

		$svg_path = get_attached_file( $attachment_id );

		if ( ! $svg_path || ! file_exists( $svg_path ) ) {
			return $metadata;
		}

		$dimensions         = $this->getSvgDimensions( $svg_path );
		$metadata['width']  = $dimensions->width;
		$metadata['height'] = $dimensions->height;

		return $metadata;
	}

	// ------------------------------------------------------

	/**
	 * Validate SVG file type and extension.
	 *
	 * @param array $filetype_ext_data File type data.
	 * @param string $file Full path to file.
	 * @param string $filename File name.
	 * @param ?array $mimes Allowed mime types (null when not provided).
	 *
	 * @return array Modified file type data.
	 */
	public function checkFiletypeAndExt( array $filetype_ext_data, string $file, string $filename, ?array $mimes ): array {
		if ( 'disable' === $this->svgOption || ! current_user_can( 'upload_files' ) ) {
			return $filetype_ext_data;
		}

		if ( str_ends_with( $filename, '.svg' ) ) {
			$filetype_ext_data['ext']  = 'svg';
			$filetype_ext_data['type'] = 'image/svg+xml';
		} elseif ( str_ends_with( $filename, '.svgz' ) ) {
			$filetype_ext_data['ext']  = 'svgz';
			$filetype_ext_data['type'] = 'image/svg+xml';
		}

		return $filetype_ext_data;
	}

	// ------------------------------------------------------

	/**
	 * Add SVG mime types to allowed uploads.
	 *
	 * @param array $mimes Allowed mime types.
	 *
	 * @return array Modified mime types.
	 */
	public function addSvgMime( array $mimes = [] ): array {
		if ( 'disable' !== $this->svgOption && current_user_can( 'upload_files' ) ) {
			$mimes['svg']  = 'image/svg+xml';
			$mimes['svgz'] = 'image/svg+xml';
		}

		return $mimes;
	}

	// ------------------------------------------------------

	/**
	 * Output CSS to fix SVG thumbnail display in admin.
	 *
	 * @return void
	 */
	public function fixSvgThumbnailSize(): void {
		echo '<style>.attachment-info .thumbnail img[src$=".svg"],#postimagediv .inside img[src$=".svg"]{width:100%;height:auto}</style>';
	}

	// ------------------------------------------------------

	/**
	 * Print inline JS to patch media library templates for SVG preview.
	 *
	 * Uses the `print_media_templates` action which fires only when
	 * the media library is loaded — avoids buffering every admin page.
	 *
	 * @return void
	 */
	public function printSvgMediaTemplates(): void {
		?>
		<script>
		(function () {
			function patchTemplate(id, needle, replacement) {
				var el = document.getElementById(id);
				if (el && el.innerHTML.indexOf(needle) !== -1) {
					el.innerHTML = el.innerHTML.replace(needle, replacement);
				}
			}

			// Attachment details (single view).
			patchTemplate(
				'tmpl-attachment-details-two-column',
				"<# } else if ( 'image' === data.type && data.sizes && data.sizes.full ) { #>",
				"<# } else if ( 'svg+xml' === data.subtype ) { #>" +
				'<img class="details-image" src="{{ data.url }}" draggable="false" />' +
				"<# } else if ( 'image' === data.type && data.sizes && data.sizes.full ) { #>"
			);

			// Attachment grid thumbnail.
			patchTemplate(
				'tmpl-attachment',
				"<# } else if ( 'image' === data.type && data.sizes ) { #>",
				"<# } else if ( 'svg+xml' === data.subtype ) { #>" +
				'<div class="centered"><img src="{{ data.url }}" class="thumbnail" draggable="false" /></div>' +
				"<# } else if ( 'image' === data.type && data.sizes ) { #>"
			);
		})();
		</script>
		<?php
	}

	// ------------------------------------------------------

	/**
	 * Prepare SVG attachment data for JavaScript.
	 *
	 * @param array $response Attachment response.
	 * @param \WP_Post $attachment Attachment post object.
	 * @param array $meta Attachment metadata.
	 *
	 * @return array Modified response.
	 */
	public function prepareAttachmentForJs( array $response, \WP_Post $attachment, array $meta ): array {
		if ( 'image/svg+xml' !== (string) $response['mime'] || ! empty( $response['sizes'] ) ) {
			return $response;
		}

		$svg_path = get_attached_file( $attachment->ID );

		if ( ! $svg_path || ! file_exists( $svg_path ) ) {
			return $response;
		}

		$dimensions        = $this->getSvgDimensions( $svg_path );
		$response['sizes'] = [
			'full' => [
				'url'         => $response['url'],
				'width'       => $dimensions->width,
				'height'      => $dimensions->height,
				'orientation' => $dimensions->width > $dimensions->height ? 'landscape' : 'portrait',
			],
		];

		return $response;
	}

	// ------------------------------------------------------

	/**
	 * Extract dimensions from SVG file.
	 *
	 * @param string $svg_path Path to SVG file or URL.
	 *
	 * @return object Object with width and height properties.
	 */
	public function getSvgDimensions( string $svg_path ): object {
		$width  = 0.0;
		$height = 0.0;

		// Try to get file contents.
		$svg_content = Helper::readFile( $svg_path );

		if ( empty( $svg_content ) ) {
			return (object) [
				'width'  => $width,
				'height' => $height,
			];
		}

		$svg = @simplexml_load_string( $svg_content );

		if ( ! $svg ) {
			return (object) [
				'width'  => $width,
				'height' => $height,
			];
		}

		$attributes = $svg->attributes();

		if ( isset( $attributes, $attributes->width, $attributes->height ) ) {
			if ( ! str_ends_with( trim( (string) $attributes->width ), '%' ) ) {
				$width = (float) $attributes->width;
			}
			if ( ! str_ends_with( trim( (string) $attributes->height ), '%' ) ) {
				$height = (float) $attributes->height;
			}
		}

		// Fallback to viewBox if width/height not available
		if ( ( ! $width || ! $height ) && isset( $attributes->viewBox ) ) {
			$sizes = explode( ' ', (string) $attributes->viewBox );
			if ( isset( $sizes[2], $sizes[3] ) ) {
				$width  = (float) $sizes[2];
				$height = (float) $sizes[3];
			}
		}

		return (object) [
			'width'  => $width,
			'height' => $height,
		];
	}

	// ------------------------------------------------------

	/**
	 * Pre-filter SVG uploads for sanitization.
	 *
	 * @param array $file Upload file data.
	 *
	 * @return array Modified file data with potential error.
	 */
	public function handleUploadPrefilter( array $file ): array {
		if (
			'image/svg+xml' !== (string) $file['type'] ||
			'sanitized' !== $this->svgOption ||
			! current_user_can( 'upload_files' )
		) {
			return $file;
		}

		if ( ! $this->sanitize( $file['tmp_name'] ) ) {
			$file['error'] = __( 'This SVG can not be sanitized.', 'hda' );
		}

		return $file;
	}

	// ------------------------------------------------------

	/**
	 * Sanitize SVG file content.
	 *
	 * @param string $file Path to SVG file.
	 *
	 * @return bool True if sanitization was successful.
	 */
	public function sanitize( string $file ): bool {
		$svg_code = Helper::readFile( $file );

		if ( empty( $svg_code ) ) {
			return false;
		}

		$is_zipped = $this->isGzipped( $svg_code );
		if ( $is_zipped ) {
			$svg_code = @gzdecode( $svg_code );

			if ( false === $svg_code ) {
				return false;
			}
		}

		$this->sanitizer->setAllowedTags( new AllowedTags() );
		$this->sanitizer->setAllowedAttrs( new AllowedAttributes() );

		$clean_svg_code = $this->sanitizer->sanitize( $svg_code );

		if ( ! $clean_svg_code ) {
			return false;
		}

		if ( $is_zipped ) {
			$clean_svg_code = gzencode( $clean_svg_code );
		}

		return Helper::writeFile( $file, $clean_svg_code );
	}

	// ------------------------------------------------------

	/**
	 * Check if SVG content is gzipped.
	 *
	 * @param string $svg_code SVG content.
	 *
	 * @return bool True if content is gzipped.
	 */
	public function isGzipped( string $svg_code ): bool {
		return str_starts_with( $svg_code, "\x1f\x8b\x08" );
	}
}
