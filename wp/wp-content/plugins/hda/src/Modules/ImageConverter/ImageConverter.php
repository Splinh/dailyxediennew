<?php
/**
 * Image Converter Module — Main class.
 *
 * Manages module settings, auto-convert hooks, and coordinates sub-components.
 * Settings tab is rendered as part of GlobalSetting's tabbed interface.
 *
 * @package HDAddons\Modules\ImageConverter
 */

namespace HDAddons\Modules\ImageConverter;

use HDAddons\Asset;
use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class ImageConverter implements HasSettings {


	// ─── Option Keys ────────────────────────────────────


	public const KEY_FORMAT           = 'imgconv_format';
	public const KEY_QUALITY_JPG      = 'imgconv_quality_jpg';
	public const KEY_QUALITY_PNG      = 'imgconv_quality_png';
	public const KEY_AUTO_CONVERT     = 'imgconv_auto_convert';
	public const KEY_SERVER_RULES     = 'imgconv_server_rules';
	public const KEY_EXCLUDE_KEYWORDS = 'imgconv_exclude_keywords';
	public const KEY_CF_ZONE_ID       = 'imgconv_cf_zone_id';
	public const KEY_CF_API_TOKEN     = 'imgconv_cf_api_token';
	public const KEY_CF_AUTO_PURGE    = 'imgconv_cf_auto_purge';

	// --------------------------------------------------

	public function __construct() {
		// Auto-convert on upload (if enabled AND format is not 'none')
		$options = self::getOptions();
		$format  = self::getFormat();

		if ( ! empty( $options[ self::KEY_AUTO_CONVERT ] ) && $format !== Converter::FORMAT_NONE ) {
			AutoConverter::init();
		}

		// Exclude converted images from backup plugins (regenerable)
		self::excludeFromBackups();

		if ( ! is_admin() ) {
			return;
		}

		// Register batch processor AJAX + cron hooks
		BatchProcessor::init();

		// Enqueue page-specific assets on HDA settings page
		add_action(
			'admin_enqueue_scripts',
			static function ( string $hookSuffix ): void {
				if ( 'toplevel_page_hda-settings' !== $hookSuffix ) {
					return;
				}

				self::enqueueAssets();
			}
		);
	}

	// ─── Backup Exclusion ───────────────────────────────

	/**
	 * Exclude output directories from backup plugins.
	 *
	 * Converted files are regenerable — no need to back them up.
	 *
	 * @return void
	 */
	private static function excludeFromBackups(): void {
		// All-in-One WP Migration
		add_filter(
			'ai1wm_exclude_content_from_export',
			static function ( array $dirs ): array {
				$dirs[] = 'uploads_webp';
				$dirs[] = 'uploads_avif';

				return $dirs;
			}
		);

		// UpdraftPlus
		add_filter(
			'updraftplus_exclude_directory',
			static function ( bool $status, string $dir ): bool {
				if ( in_array( $dir, [ 'uploads_webp', 'uploads_avif' ], true ) ) {
					return true;
				}

				return $status;
			},
			10,
			2
		);

		// BackWPup
		add_filter(
			'backwpup_content_exclude_dirs',
			static function ( array $dirs ): array {
				$dirs[] = 'uploads_webp';
				$dirs[] = 'uploads_avif';

				return $dirs;
			}
		);
	}

	// ─── Options ────────────────────────────────────────

	/**
	 * Get module options.
	 *
	 * @return array
	 */
	public static function getOptions(): array {
		return Helper::getOption( ImageConverterModule::optionKey(), [] );
	}

	/**
	 * Get the currently configured format.
	 *
	 * @return string 'none', 'webp' or 'avif'
	 */
	public static function getFormat(): string {
		$options = self::getOptions();

		$format = $options[ self::KEY_FORMAT ] ?? Converter::FORMAT_AVIF;

		return \in_array( $format, [ Converter::FORMAT_NONE, Converter::FORMAT_WEBP, Converter::FORMAT_AVIF ], true )
			? $format
			: Converter::FORMAT_AVIF;
	}

	// ─── Assets ─────────────────────────────────────────

	/**
	 * Enqueue page-specific assets via Vite manifest.
	 *
	 * @return void
	 */
	private static function enqueueAssets(): void {
		Asset::enqueueJS( 'converter.js' );

		$handle = Asset::handle( 'converter.js' );

		if ( $handle ) {
			Asset::localize(
				$handle,
				'hdaImgConv',
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'hda_imgconv_nonce' ),
					'i18n'    => [
						'confirm_start'  => __( 'Start batch conversion? This may take a while for large directories.', 'hda' ),
						'confirm_cancel' => __( 'Cancel the running conversion?', 'hda' ),
						'converting'     => __( 'Converting...', 'hda' ),
						'complete'       => __( 'Conversion complete!', 'hda' ),
						'cancelled'      => __( 'Conversion cancelled.', 'hda' ),
						'error'          => __( 'An error occurred.', 'hda' ),
						'saved'          => __( 'Settings saved.', 'hda' ),
						'start'          => __( 'Start Conversion', 'hda' ),
					],
				]
			);
		}
	}

	// ── Exclude Keywords ─────────────────────────────────

	/**
	 * Get parsed exclusion keywords array.
	 *
	 * @return array<string> Trimmed, non-empty keywords.
	 */
	public static function getExcludeKeywords(): array {
		$options = self::getOptions();
		$raw     = $options[ self::KEY_EXCLUDE_KEYWORDS ] ?? '';

		if ( empty( $raw ) ) {
			return [];
		}

		return array_filter(
			array_map( 'trim', explode( ',', $raw ) ),
			fn( string $kw ) => $kw !== ''
		);
	}

	// ── HasSettings ──────────────────────────────────────


	public static function saveSettings( array $data ): void {
		$options = [
			self::KEY_FORMAT           => isset( $data[ self::KEY_FORMAT ] ) && \in_array(
				$data[ self::KEY_FORMAT ],
				[ Converter::FORMAT_NONE, Converter::FORMAT_WEBP, Converter::FORMAT_AVIF ],
				true,
			) ? $data[ self::KEY_FORMAT ] : Converter::FORMAT_AVIF,

			self::KEY_QUALITY_JPG      => isset( $data[ self::KEY_QUALITY_JPG ] )
				? max( 30, min( 100, absint( $data[ self::KEY_QUALITY_JPG ] ) ) )
				: 65,

			self::KEY_QUALITY_PNG      => isset( $data[ self::KEY_QUALITY_PNG ] )
				? max( 30, min( 100, absint( $data[ self::KEY_QUALITY_PNG ] ) ) )
				: 70,

			self::KEY_AUTO_CONVERT     => ! empty( $data[ self::KEY_AUTO_CONVERT ] ),
			self::KEY_SERVER_RULES     => ! empty( $data[ self::KEY_SERVER_RULES ] ),

			// Exclusion keywords (comma-separated)
			self::KEY_EXCLUDE_KEYWORDS => isset( $data[ self::KEY_EXCLUDE_KEYWORDS ] )
				? sanitize_text_field( wp_unslash( $data[ self::KEY_EXCLUDE_KEYWORDS ] ) )
				: '',

			// Cloudflare integration
			self::KEY_CF_ZONE_ID       => isset( $data[ self::KEY_CF_ZONE_ID ] )
				? sanitize_text_field( wp_unslash( $data[ self::KEY_CF_ZONE_ID ] ) )
				: '',
			self::KEY_CF_API_TOKEN     => isset( $data[ self::KEY_CF_API_TOKEN ] )
				? sanitize_text_field( wp_unslash( $data[ self::KEY_CF_API_TOKEN ] ) )
				: '',
			self::KEY_CF_AUTO_PURGE    => ! empty( $data[ self::KEY_CF_AUTO_PURGE ] ),
		];

		if ( ! empty( $options ) ) {
			Helper::updateOption( ImageConverterModule::optionKey(), $options, 12, true );
		} else {
			Helper::removeOption( ImageConverterModule::optionKey() );
		}

		// Clear CF status cache so the UI re-verifies with new credentials
		CloudflareIntegration::clearStatusCache();

		// Apply or remove server rewrite rules (.htaccess / Nginx)
		// Rules are always removed when format is 'none'
		$rulesEnabled = ! empty( $options[ self::KEY_SERVER_RULES ] )
			&& $options[ self::KEY_FORMAT ] !== Converter::FORMAT_NONE;
		ServerRules::apply( $rulesEnabled );
	}
}
