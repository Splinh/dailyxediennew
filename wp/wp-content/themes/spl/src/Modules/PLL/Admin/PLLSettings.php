<?php
/**
 * PLL Module Settings — Admin tab under Polylang settings.
 *
 * Adds a "HD Polylang" tab to Languages > Settings with:
 * - Pro feature toggles (TranslateSlugs, DuplicateContent, ShareSlugs, LocaleFallback).
 * - Translation scanner settings (theme/plugin/domain selection).
 * - Translation Import/Export (CSV, PO, XLIFF 2.1).
 *
 * @package SPL\Modules\PLL\Admin
 */

namespace SPL\Modules\PLL\Admin;

use SPL\Core\Helper;
use SPL\Modules\PLL\AI\AiClient;
use SPL\Modules\PLL\PLLModule;
use SPL\Modules\PLL\ImportExport\ExportHandler;
use SPL\Modules\PLL\ImportExport\FileFormatFactory;
use SPL\Modules\PLL\ImportExport\ImportHandler;
use SPL\Modules\PLL\Translation\Scanner;
use SPL\Modules\PLL\Translation\Settings as TranslationSettings;

defined( 'ABSPATH' ) || exit;

final class PLLSettings {

	private const NONCE_ACTION = 'hd_pll_settings_save';
	private const NONCE_FIELD  = '_hd_pll_nonce';
	private const TAB_SLUG     = 'hd_pll';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_filter( 'pll_settings_tabs', [ self::class, 'addTab' ] );
		add_action( 'pll_settings_active_tab_' . self::TAB_SLUG, [ self::class, 'renderTab' ] );
		add_action( 'admin_init', [ self::class, 'handleFormSubmission' ] );

		// Override Polylang Free's preview modules when HD Pro features are active.
		add_filter( 'pll_settings_modules', [ self::class, 'overridePreviewModules' ], 20 );
	}

	/**
	 * Replace Polylang Free's "preview" settings modules with activated
	 * versions when HD PLL Pro features are enabled.
	 *
	 * Polylang Free registers `PLL_Settings_Preview_Share_Slug` and
	 * `PLL_Settings_Preview_Translate_Slugs` with `active_option = 'preview'`,
	 * which renders them as "Deactivated" with an upgrade notice.
	 * When HD provides the equivalent feature, we swap the class for a
	 * thin wrapper that sets `active_option = 'none'` → "Activated" status.
	 *
	 * @param string[] $modules Settings module class names.
	 *
	 * @return string[]
	 */
	public static function overridePreviewModules( array $modules ): array {
		if ( PLLModule::isProActive() ) {
			return $modules; // Polylang Pro handles its own modules.
		}

		$settings     = PLLModule::getCachedOptions();
		$replacements = [];

		if ( ! empty( $settings['share_slugs'] ) ) {
			$replacements['PLL_Settings_Preview_Share_Slug'] = HD_PLL_Settings_Share_Slug::class;
		}

		if ( ! empty( $settings['translate_slugs'] ) ) {
			$replacements['PLL_Settings_Preview_Translate_Slugs'] = HD_PLL_Settings_Translate_Slugs::class;
		}

		if ( empty( $replacements ) ) {
			return $modules;
		}

		foreach ( $modules as &$class ) {
			if ( isset( $replacements[ $class ] ) ) {
				$class = $replacements[ $class ];
			}
		}

		return $modules;
	}

	/**
	 * Add "HD Polylang" tab to Polylang Settings.
	 *
	 * @param array<string, string> $tabs Existing tabs.
	 *
	 * @return array<string, string>
	 */
	public static function addTab( array $tabs ): array {
		$tabs[ self::TAB_SLUG ] = __( 'HD Polylang', 'SPL' );

		return $tabs;
	}

	/**
	 * Render the settings tab content.
	 */
	public static function renderTab(): void {
		$pll_settings   = PLLModule::getCachedOptions();
		$trans_settings = TranslationSettings::get();
		$pro_features   = self::getProFeatureLabels();
		$themes         = self::getAvailableThemes();
		$plugins        = self::getAvailablePlugins();
		$show_pro       = ! PLLModule::isProActive();
		$show_ttfp      = ! PLLModule::isTTfPActive();
		$show_wc        = Helper::isWoocommerceActive() && ! PLLModule::isWCActive();

		// Flash messages.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = sanitize_key( $_GET['hd_pll_msg'] ?? '' );
		if ( $msg ) {
			$messages = [
				'saved'        => __( 'Settings saved.', 'SPL' ),
				'imported'     => sprintf(
					/* translators: %d: number of imported items */
					__( 'Translations imported: %d items.', 'SPL' ),
					absint( $_GET['hd_pll_count'] ?? 0 ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				),
				'export_error' => sanitize_text_field( $_GET['hd_pll_error'] ?? '' ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			];
			if ( isset( $messages[ $msg ] ) && '' !== $messages[ $msg ] ) {
				$noticeType = ( 'export_error' === $msg ) ? 'error' : 'success';
				printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $noticeType ), esc_html( $messages[ $msg ] ) );
			}
		}

		?>
		<div class="form-wrap">
			<?php if ( $show_wc ) : ?>
			<div class="notice notice-success inline" style="margin: 0 0 20px 0;">
				<p><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <strong><?php esc_html_e( 'WooCommerce Integration Active', 'SPL' ); ?>:</strong> <?php esc_html_e( 'Native translation support for products and emails is running automatically.', 'SPL' ); ?></p>
			</div>
			<?php endif; ?>

			<form method="post" enctype="multipart/form-data" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
				<input type="hidden" name="hd_pll_save" value="1">

				<?php if ( $show_pro ) : ?>
				<!-- Pro Features -->
				<h3><?php esc_html_e( 'Pro Features', 'SPL' ); ?></h3>
				<p class="description"><?php esc_html_e( 'These features replace Polylang Pro. Disable this section by activating the Polylang Pro plugin.', 'SPL' ); ?></p>
				<table class="form-table" role="presentation">
					<?php foreach ( $pro_features as $slug => $label ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="hd_pll_features[<?php echo esc_attr( $slug ); ?>]" value="1"
									<?php checked( ! empty( $pll_settings[ $slug ] ) ); ?>>
								<?php esc_html_e( 'Enable', 'SPL' ); ?>
							</label>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
				<?php endif; ?>

				<hr>

				<!-- Admin Force Locale (T-B1) -->
				<h3><?php esc_html_e( 'Admin Dashboard Language', 'SPL' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Control the language used for the WordPress admin dashboard.', 'SPL' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Admin Language', 'SPL' ); ?></th>
						<td>
							<?php $force_locale = $pll_settings['admin_force_locale'] ?? 'content'; ?>
							<select name="hd_pll_admin_force_locale">
								<option value="content" <?php selected( $force_locale, 'content' ); ?>><?php esc_html_e( 'Content language (default)', 'SPL' ); ?></option>
								<option value="default" <?php selected( $force_locale, 'default' ); ?>><?php esc_html_e( 'Always use default language', 'SPL' ); ?></option>
								<option value="profile" <?php selected( $force_locale, 'profile' ); ?>><?php esc_html_e( 'Use user profile language', 'SPL' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Choose how the admin dashboard language is determined. "Content language" follows the content you are editing.', 'SPL' ); ?></p>
						</td>
					</tr>
				</table>

				<?php self::renderAiSettings( $pll_settings ); ?>



				<?php if ( $show_ttfp ) : ?>
				<hr>

				<!-- Translation Scanner Settings -->
				<h3><?php esc_html_e( 'Theme & Plugin Translation', 'SPL' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Select themes and plugins to scan for translatable strings. Strings will appear in Languages > String translations.', 'SPL' ); ?></p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Themes', 'SPL' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( $themes as $name => $display ) : ?>
								<label style="display: block; margin-bottom: 4px;">
									<input type="checkbox" name="hd_pll_translation[themes][]"
										value="<?php echo esc_attr( $name ); ?>"
										<?php checked( in_array( $name, $trans_settings['themes'], true ) ); ?>>
									<?php echo esc_html( $display ); ?>
								</label>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Plugins', 'SPL' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( $plugins as $name => $display ) : ?>
								<label style="display: block; margin-bottom: 4px;">
									<input type="checkbox" name="hd_pll_translation[plugins][]"
										value="<?php echo esc_attr( $name ); ?>"
										<?php checked( in_array( $name, $trans_settings['plugins'], true ) ); ?>>
									<?php echo esc_html( $display ); ?>
								</label>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
				</table>

				<hr>

				<!-- Translation Import/Export -->
				<h3><?php esc_html_e( 'Translation Import/Export', 'SPL' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Export string translations in CSV, PO, or XLIFF format. Import translated files back.', 'SPL' ); ?></p>
					<?php

					$languages     = \PLL()->model->get_languages_list();
					$defaultLang   = \PLL()->model->get_default_language();
					$strings       = class_exists( 'PLL_Admin_Strings' ) ? \PLL_Admin_Strings::get_strings() : [];
					$groups        = array_unique( wp_list_pluck( $strings, 'context' ) );
					$formatFactory = new FileFormatFactory();
					$exportFormats = $formatFactory->getSupportedFormats( 'strings' );

					?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Export Strings', 'SPL' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Target languages', 'SPL' ); ?></legend>
								<p><strong><?php esc_html_e( 'Target languages:', 'SPL' ); ?></strong></p>
								<?php foreach ( $languages as $language ) : ?>
									<?php if ( $defaultLang && $defaultLang->slug !== $language->slug ) : ?>
									<label style="display:inline-block;margin-right:12px;">
										<input type="checkbox" name="hd_pll_export_langs[]" value="<?php echo esc_attr( $language->slug ); ?>" checked>
										<?php echo esc_html( $language->name ); ?>
									</label>
									<?php endif; ?>
								<?php endforeach; ?>
							</fieldset>

							<?php if ( ! empty( $groups ) ) : ?>
							<p style="margin-top:8px;">
								<label for="hd-pll-export-group"><?php esc_html_e( 'Filter group:', 'SPL' ); ?></label>
								<select name="hd_pll_export_group" id="hd-pll-export-group">
									<option value=""><?php esc_html_e( 'All groups', 'SPL' ); ?></option>
									<?php foreach ( $groups as $group ) : ?>
									<option value="<?php echo esc_attr( $group ); ?>"><?php echo esc_html( $group ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
							<?php endif; ?>

							<p style="margin-top:8px;">
								<strong><?php esc_html_e( 'File format:', 'SPL' ); ?></strong><br>
								<?php foreach ( $exportFormats as $key => $fmt ) : ?>
								<label style="display:inline-block; margin-right:12px;">
									<input type="radio" name="hd_pll_export_format" value="<?php echo esc_attr( $key ); ?>"
										<?php checked( 'csv', $key ); ?>>
									<?php echo esc_html( $fmt['label'] ); ?>
								</label>
								<?php endforeach; ?>
							</p>

							<p>
								<button type="submit" name="hd_pll_export" value="1" class="button button-secondary">
									<?php esc_html_e( 'Download', 'SPL' ); ?>
								</button>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Import Translations', 'SPL' ); ?></th>
						<td>
							<input type="file" name="hd_pll_import_file" accept=".csv,.po,.xliff,.xlf">
							<p class="description"><?php esc_html_e( 'Upload a CSV, PO, or XLIFF file to import string translations.', 'SPL' ); ?></p>
						</td>
					</tr>
				</table>
				<?php endif; ?>

				<?php submit_button( __( 'Save Settings', 'SPL' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle form submission.
	 */
	public static function handleFormSubmission(): void {
		$isExport = ! empty( $_POST['hd_pll_export'] );
		if ( ( empty( $_POST['hd_pll_save'] ) && ! $isExport ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! wp_verify_nonce( $_POST[ self::NONCE_FIELD ] ?? '', self::NONCE_ACTION ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
		$redirect_url = admin_url( 'admin.php?page=' . sanitize_key( $_GET['page'] ?? 'mlang' ) );

		// ── Handle export (skip settings save) ──
		if ( $isExport ) {
			$exportFormat = sanitize_key( $_POST['hd_pll_export_format'] ?? 'csv' );
			$exportLangs  = array_map( 'sanitize_key', $_POST['hd_pll_export_langs'] ?? [] );
			$exportGroup  = sanitize_text_field( $_POST['hd_pll_export_group'] ?? '' );

			if ( ! empty( $exportLangs ) ) {
				$result = ExportHandler::handle( $exportFormat, $exportLangs, $exportGroup );

				if ( \is_wp_error( $result ) && $result->has_errors() ) {
					$redirect_url = add_query_arg(
						[
							'hd_pll_msg'   => 'export_error',
							'hd_pll_error' => $result->get_error_message(),
						],
						$redirect_url
					);
					wp_safe_redirect( $redirect_url );
					exit;
				}
				// On success, ExportHandler sends file and exits.
			}

			// No languages selected — redirect with error.
			$redirect_url = add_query_arg( 'hd_pll_msg', 'export_error', $redirect_url );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// ── Save Pro features ──
		$features   = $_POST['hd_pll_features'] ?? [];
		$pro_slugs  = array_keys( self::getProFeatureLabels() );
		$pll_option = [];

		foreach ( $pro_slugs as $slug ) {
			$pll_option[ $slug ] = ! empty( $features[ $slug ] );
		}

		// T-B1: Admin force locale.
		$pll_option['admin_force_locale']          = sanitize_key( $_POST['hd_pll_admin_force_locale'] ?? '' );
		$pll_option['ai_translation_enabled']      = ! empty( $_POST['hd_pll_ai_enabled'] );
		$pll_option['ai_consumer_token']           = sanitize_text_field( wp_unslash( $_POST['hd_pll_ai_consumer_token'] ?? '' ) );
		$pll_option['ai_default_target_languages'] = array_map( 'sanitize_key', (array) ( $_POST['hd_pll_ai_target_languages'] ?? [] ) );
		$pll_option['ai_default_commit_mode']      = 'draft';
		$pll_option['ai_default_post_status']      = sanitize_key( $_POST['hd_pll_ai_post_status'] ?? 'draft' );
		$pll_option['ai_content_types']            = array_map( 'sanitize_key', (array) ( $_POST['hd_pll_ai_content_types'] ?? [] ) );
		$pll_option['ai_translate_title']          = ! empty( $_POST['hd_pll_ai_translate_title'] );
		$pll_option['ai_translate_content']        = ! empty( $_POST['hd_pll_ai_translate_content'] );
		$pll_option['ai_translate_excerpt']        = ! empty( $_POST['hd_pll_ai_translate_excerpt'] );
		$pll_option['ai_translate_slug']           = ! empty( $_POST['hd_pll_ai_translate_slug'] );
		$pll_option['ai_translate_meta_keys']      = array_values( array_filter( array_map( 'sanitize_key', preg_split( '/\r\n|\r|\n/', (string) ( $_POST['hd_pll_ai_meta_keys'] ?? '' ) ) ?: [] ) ) );
		$pll_option['ai_glossary_terms']           = array_values( array_filter( array_map( 'trim', array_map( 'sanitize_text_field', preg_split( '/\r\n|\r|\n/', (string) ( $_POST['hd_pll_ai_glossary_terms'] ?? '' ) ) ?: [] ) ) ) );
		$pll_option['ai_max_units_per_request']    = max( 1, absint( $_POST['hd_pll_ai_max_units'] ?? 25 ) );
		$pll_option['ai_max_chars_per_request']    = max( 1000, absint( $_POST['hd_pll_ai_max_chars'] ?? 12000 ) );
		$pll_option['ai_editor_assist_enabled']    = ! empty( $_POST['hd_pll_ai_editor_assist'] );

		Helper::updateOption( PLLModule::optionKey(), $pll_option );
		PLLModule::resetCache();

		// ── Save Translation settings ──
		$translation = $_POST['hd_pll_translation'] ?? [];
		$save_data   = [
			'themes'             => array_map( 'sanitize_text_field', $translation['themes'] ?? [] ),
			'plugins'            => array_map( 'sanitize_text_field', $translation['plugins'] ?? [] ),
			'domains'            => [ 'default' ],
			'additional_domains' => [],
		];

		// Auto-detect text domains for selected themes.
		foreach ( $save_data['themes'] as $theme_name ) {
			$theme = wp_get_theme( $theme_name );
			if ( $theme->exists() ) {
				$textdomain = $theme->get( 'TextDomain' );
				if ( $textdomain && $textdomain !== $theme_name ) {
					$save_data['additional_domains'][] = sanitize_text_field( $textdomain );
				}
			}
		}

		// Auto-detect text domains for selected plugins.
		$all_plugins = function_exists( 'get_plugins' ) ? get_plugins() : [];
		foreach ( $save_data['plugins'] as $plugin_name ) {
			foreach ( $all_plugins as $key => $info ) {
				if ( pathinfo( $key, PATHINFO_FILENAME ) === $plugin_name ) {
					$textdomain = $info['TextDomain'] ?? '';
					if ( $textdomain && $textdomain !== $plugin_name ) {
						$save_data['additional_domains'][] = sanitize_text_field( $textdomain );
					}
					break;
				}
			}
		}

		$save_data['additional_domains'] = array_unique( $save_data['additional_domains'] );
		TranslationSettings::save( $save_data );

		// Clear scanner transients.
		Scanner::clearCache();

		// ── Handle import ──
		if ( ! empty( $_FILES['hd_pll_import_file']['tmp_name'] ) && ! empty( $_FILES['hd_pll_import_file']['size'] ) ) {
			$result = ImportHandler::handle( $_FILES['hd_pll_import_file'] );

			if ( \is_wp_error( $result ) ) {
				$redirect_url = add_query_arg(
					[
						'hd_pll_msg'   => 'export_error',
						'hd_pll_error' => $result->get_error_message(),
					],
					$redirect_url
				);
			} else {
				$redirect_url = add_query_arg(
					[
						'hd_pll_msg'   => 'imported',
						'hd_pll_count' => $result['imported'] ?? 0,
					],
					$redirect_url
				);
			}
		} else {
			$redirect_url = add_query_arg( 'hd_pll_msg', 'saved', $redirect_url );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render AI translation settings.
	 *
	 * @param array<string, mixed> $settings PLL settings.
	 */
	private static function renderAiSettings( array $settings ): void {
		$languages    = function_exists( 'PLL' ) ? \PLL()->model->get_languages_list() : [];
		$post_types   = get_post_types( [ 'public' => true ], 'objects' );
		$target_langs = (array) ( $settings['ai_default_target_languages'] ?? [] );
		$contentTypes = (array) ( $settings['ai_content_types'] ?? [] );

		?>
		<hr>

		<h3>
			<?php esc_html_e( 'AI Translation', 'SPL' ); ?>
			<b style="font-size:14px;color:<?php echo AiClient::isAvailable() ? '#00a32a' : '#d63638'; ?>">
				<?php echo AiClient::isAvailable() ? esc_html__( 'HDAT route available', 'SPL' ) : esc_html__( 'HDAT route missing', 'SPL' ); ?>
			</b>
		</h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable AI translation', 'SPL' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="hd_pll_ai_enabled" value="1" <?php checked( ! empty( $settings['ai_translation_enabled'] ) ); ?>>
						<?php esc_html_e( 'Enable', 'SPL' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'HDAT consumer token', 'SPL' ); ?></th>
				<td>
					<?php $token = (string) ( $settings['ai_consumer_token'] ?? '' ); ?>
					<input type="text" class="regular-text" name="hd_pll_ai_consumer_token" value="<?php echo esc_attr( $token ); ?>" autocomplete="off">
					<p class="description"><?php esc_html_e( 'Model and provider routing are managed centrally by HDAT Credentials associated with this token.', 'SPL' ); ?></p>
					<?php
					if ( $token ) {
						$auth = apply_filters( 'hdat_validate_consumer_token', null, $token );
						if ( null !== $auth ) {
							if ( is_wp_error( $auth ) ) {
								printf( '<p class="description" style="color: #d63638;"><strong>%s</strong></p>', esc_html( $auth->get_error_message() ) );
							} elseif ( ! $auth ) {
								printf( '<p class="description" style="color: #d63638;"><strong>%s</strong></p>', esc_html__( 'Token not found or invalid.', 'SPL' ) );
							} else {
								printf( '<p class="description" style="color: #00a32a;"><strong>✓ %s</strong></p>', esc_html__( 'Active', 'SPL' ) );
							}
						}
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Target languages', 'SPL' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Target languages', 'SPL' ); ?></span></legend>
						<?php foreach ( (array) $languages as $language ) : ?>
							<?php if ( is_object( $language ) ) : ?>
							<label style="display:inline-block;margin-right:12px;margin-bottom:8px;">
								<input type="checkbox" name="hd_pll_ai_target_languages[]" value="<?php echo esc_attr( $language->slug ); ?>" <?php checked( in_array( $language->slug, $target_langs, true ) ); ?>>
								<?php echo esc_html( $language->name ); ?>
							</label>
							<?php endif; ?>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Select the languages to generate translations for when performing bulk actions.', 'SPL' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Bulk draft status', 'SPL' ); ?></th>
				<td>
					<select name="hd_pll_ai_post_status">
						<option value="draft" <?php selected( $settings['ai_default_post_status'] ?? 'draft', 'draft' ); ?>><?php esc_html_e( 'Draft', 'SPL' ); ?></option>
						<option value="pending" <?php selected( $settings['ai_default_post_status'] ?? 'draft', 'pending' ); ?>><?php esc_html_e( 'Pending review', 'SPL' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Bulk AI translation always creates translated posts in the selected status. Editor assist remains preview-only inside the editor.', 'SPL' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Content types', 'SPL' ); ?></th>
				<td>
					<?php foreach ( $post_types as $type => $object ) : ?>
						<label style="display:inline-block;margin-right:12px;">
							<input type="checkbox" name="hd_pll_ai_content_types[]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $contentTypes, true ) ); ?>>
							<?php echo esc_html( $object->labels->name ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Fields', 'SPL' ); ?></th>
				<td>
					<label><input type="checkbox" name="hd_pll_ai_translate_title" value="1" <?php checked( ! empty( $settings['ai_translate_title'] ) ); ?>> <?php esc_html_e( 'Title', 'SPL' ); ?></label>
					<label><input type="checkbox" name="hd_pll_ai_translate_content" value="1" <?php checked( ! empty( $settings['ai_translate_content'] ) ); ?>> <?php esc_html_e( 'Content', 'SPL' ); ?></label>
					<label><input type="checkbox" name="hd_pll_ai_translate_excerpt" value="1" <?php checked( ! empty( $settings['ai_translate_excerpt'] ) ); ?>> <?php esc_html_e( 'Excerpt', 'SPL' ); ?></label>
					<label><input type="checkbox" name="hd_pll_ai_translate_slug" value="1" <?php checked( ! empty( $settings['ai_translate_slug'] ) ); ?>> <?php esc_html_e( 'Slug', 'SPL' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Meta keys', 'SPL' ); ?></th>
				<td>
					<textarea name="hd_pll_ai_meta_keys" rows="3" class="large-text"><?php echo esc_textarea( implode( "\n", (array) ( $settings['ai_translate_meta_keys'] ?? [] ) ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One key per line. List of custom field keys to translate (e.g., _yoast_wpseo_title, rank_math_description). Do not add fields storing IDs, media, or arrays.', 'SPL' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Limits', 'SPL' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Limits', 'SPL' ); ?></span></legend>
						<label style="display: block; margin-bottom: 8px;">
							<input type="number" min="1" step="1" name="hd_pll_ai_max_units" value="<?php echo esc_attr( (string) ( $settings['ai_max_units_per_request'] ?? 25 ) ); ?>" class="small-text">
							<?php esc_html_e( 'Maximum translation units per API request', 'SPL' ); ?>
						</label>
						<label style="display: block; margin-bottom: 8px;">
							<input type="number" min="1000" step="100" name="hd_pll_ai_max_chars" value="<?php echo esc_attr( (string) ( $settings['ai_max_chars_per_request'] ?? 12000 ) ); ?>" style="width: 80px;">
							<?php esc_html_e( 'Maximum characters per API request', 'SPL' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Controls chunking. Long posts will be split into smaller API requests to prevent timeouts or token limits.', 'SPL' ); ?></p>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Glossary', 'SPL' ); ?></th>
				<td>
					<textarea name="hd_pll_ai_glossary_terms" rows="4" class="large-text"><?php echo esc_textarea( implode( "\n", (array) ( $settings['ai_glossary_terms'] ?? [] ) ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One term per line. AI will be forced to preserve these terms (e.g. brand names, SKUs) without translating them.', 'SPL' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Editor assist', 'SPL' ); ?></th>
				<td>
					<label><input type="checkbox" name="hd_pll_ai_editor_assist" value="1" <?php checked( ! empty( $settings['ai_editor_assist_enabled'] ) ); ?>> <?php esc_html_e( 'Enable editor-time assist', 'SPL' ); ?></label>
					<p class="description"><?php esc_html_e( 'When creating a new translation in the Editor, automatically run AI in the background to pre-fill the title and content. You can review before publishing.', 'SPL' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/* ---------- Helpers ---------- */

	/**
	 * Get Pro feature slugs and labels.
	 *
	 * @return array<string, string>
	 */
	private static function getProFeatureLabels(): array {
		return [
			'translate_slugs'   => __( 'Translate URL Slugs', 'SPL' ),
			'duplicate_content' => __( 'Duplicate Content on Translation', 'SPL' ),
			'share_slugs'       => __( 'Share Slugs Across Languages', 'SPL' ),
			'locale_fallback'   => __( 'Locale Fallback', 'SPL' ),
		];
	}

	/**
	 * Get available themes for scanning.
	 *
	 * @return array<string, string> name => display label
	 */
	private static function getAvailableThemes(): array {
		$result = [];

		foreach ( wp_get_themes() as $name => $theme ) {
			$textdomain = $theme->get( 'TextDomain' );
			$label      = $name;

			if ( $textdomain && $textdomain !== $name ) {
				$label .= sprintf( ' (TextDomain: %s)', $textdomain );
			}

			$result[ $name ] = $label;
		}

		return $result;
	}

	/**
	 * Get available plugins for scanning (excludes Polylang-related).
	 *
	 * @return array<string, string> name => display label
	 */
	private static function getAvailablePlugins(): array {
		$result  = [];
		$exclude = [ 'polylang', 'polylang-pro', 'theme-translation-for-polylang', 'polylang-theme-translation' ];
		$plugins = wp_get_active_and_valid_plugins();

		if ( \is_multisite() ) {
			$plugins = array_merge( $plugins, wp_get_active_network_plugins() );
		}

		$all_plugin_data = function_exists( 'get_plugins' ) ? get_plugins() : [];

		foreach ( $plugins as $plugin ) {
			$plugin_dir  = dirname( $plugin );
			$plugin_name = pathinfo( $plugin, PATHINFO_FILENAME );

			if ( in_array( $plugin_name, $exclude, true ) || $plugin_dir === WP_PLUGIN_DIR ) {
				continue;
			}

			$label = $plugin_name;

			// Try to get plugin full name.
			foreach ( $all_plugin_data as $key => $info ) {
				if ( pathinfo( $key, PATHINFO_FILENAME ) === $plugin_name ) {
					$full_name  = $info['Name'] ?? '';
					$textdomain = $info['TextDomain'] ?? '';

					if ( $full_name ) {
						$label = $full_name;
					}
					if ( $textdomain && $textdomain !== $plugin_name ) {
						$label .= sprintf( ' (TextDomain: %s)', $textdomain );
					}
					break;
				}
			}

			$result[ $plugin_name ] = $label;
		}

		return $result;
	}
}
