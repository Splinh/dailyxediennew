<?php
/**
 * PLL Module Settings — Admin tab under Polylang settings.
 *
 * Adds a "HD Polylang" tab to Languages > Settings with:
 * - Pro feature toggles (TranslateSlugs, DuplicateContent, ShareSlugs, LocaleFallback).
 * - Translation scanner settings (theme/plugin/domain selection).
 * - Translation Import/Export (CSV, PO, XLIFF 2.1).
 * - Modern HD Extended dashboard UI layout.
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
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueueAssets' ] );

		// Override Polylang Free's preview modules when HD Pro features are active.
		add_filter( 'pll_settings_modules', [ self::class, 'overridePreviewModules' ], 20 );
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook Page hook.
	 */
	public static function enqueueAssets( string $hook ): void {
		if ( false === strpos( $hook, 'mlang' ) ) {
			return;
		}

		wp_enqueue_style(
			'hd-pll-admin-css',
			get_template_directory_uri() . '/src/Modules/PLL/Admin/assets/admin-pll.css',
			[],
			'1.0.0'
		);
	}

	/**
	 * Replace Polylang Free's "preview" settings modules with activated
	 * versions when HD PLL Pro features are enabled.
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
		$tabs[ self::TAB_SLUG ] = __( 'SPL Polylang', 'SPL' );

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

		$feature_descriptions = [
			'translate_slugs'   => __( 'Translate URL slugs for custom post types and taxonomies per language.', 'SPL' ),
			'duplicate_content' => __( 'Automatically copy title, content, media, and meta fields when creating a new translation.', 'SPL' ),
			'share_slugs'       => __( 'Allow posts of different languages to share the identical URL slug across post types.', 'SPL' ),
			'locale_fallback'   => __( 'Fall back to default language content when a translation does not exist for a requested locale.', 'SPL' ),
		];

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
				printf( '<div class="notice notice-%s is-dismissible" style="margin-top:15px;"><p>%s</p></div>', esc_attr( $noticeType ), esc_html( $messages[ $msg ] ) );
			}
		}

		?>
		<div class="hde-wrap">
			<!-- Top Header Subnav Toolbar -->
			<div class="hde-top-bar">
				<div class="hde-brand-badge">
					<span class="hde-brand-logo">SPL</span>
					<span>Extended</span>
				</div>
				<nav class="hde-nav-tabs">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlang' ) ); ?>" class="hde-tab-item">
						<span class="dashicons dashicons-dashboard"></span>
						<span>Dashboard</span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlang_hd_pll' ) ); ?>" class="hde-tab-item active">
						<span class="dashicons dashicons-translation"></span>
						<span>Polylang</span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hd-form-entries' ) ); ?>" class="hde-tab-item">
						<span class="dashicons dashicons-feedback"></span>
						<span>Form</span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="hde-tab-item">
						<span class="dashicons dashicons-visibility"></span>
						<span>Post Views</span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hd-form-settings' ) ); ?>" class="hde-tab-item">
						<span class="dashicons dashicons-admin-generic"></span>
						<span>Settings</span>
					</a>
				</nav>
			</div>

			<form method="post" enctype="multipart/form-data" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
				<input type="hidden" name="hd_pll_save" value="1">

				<!-- Page Header -->
				<div class="hde-page-header">
					<div class="hde-header-info">
						<h1><?php esc_html_e( 'Polylang Multilingual Management', 'SPL' ); ?></h1>
						<p><?php esc_html_e( 'Orchestrate Pro features, AI translation engine, WooCommerce, ACF, and string translation.', 'SPL' ); ?></p>
					</div>
					<button type="submit" class="button hde-save-btn">
						<?php esc_html_e( 'Save Settings', 'SPL' ); ?>
					</button>
				</div>

				<?php if ( $show_wc ) : ?>
				<div class="notice notice-success inline" style="margin: 0 0 20px 0; border-radius: 8px;">
					<p><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <strong><?php esc_html_e( 'WooCommerce Integration Active', 'SPL' ); ?>:</strong> <?php esc_html_e( 'Native translation support for products and emails is running automatically.', 'SPL' ); ?></p>
				</div>
				<?php endif; ?>

				<!-- Section 1: Pro Features & Sub-System Integration -->
				<div class="hde-section">
					<div class="hde-section-header">
						<h2 class="hde-section-title"><?php esc_html_e( 'Pro Features & Sub-System Integration', 'SPL' ); ?></h2>
						<p class="hde-section-desc"><?php esc_html_e( 'Configure Polylang Pro feature emulation, URL slug translation, duplicate content settings, and automatic native integrations.', 'SPL' ); ?></p>
					</div>

					<div class="hde-grid-3">
						<!-- Card 1: Translate Slugs -->
						<div class="hde-card">
							<div>
								<div class="hde-card-top">
									<h3 class="hde-card-title"><?php esc_html_e( 'Translate Slugs', 'SPL' ); ?></h3>
									<label class="hde-switch">
										<input type="checkbox" name="hd_pll_features[translate_slugs]" value="1" <?php checked( ! empty( $pll_settings['translate_slugs'] ) ); ?>>
										<span class="hde-slider"></span>
									</label>
								</div>
								<p class="hde-card-desc"><?php echo esc_html( $feature_descriptions['translate_slugs'] ); ?></p>
							</div>
							<div class="hde-card-bottom">
								<span class="hde-badge hde-badge-pro">SPL PRO FEATURE</span>
							</div>
						</div>

						<!-- Card 2: Duplicate Content -->
						<div class="hde-card">
							<div>
								<div class="hde-card-top">
									<h3 class="hde-card-title"><?php esc_html_e( 'Duplicate Content', 'SPL' ); ?></h3>
									<label class="hde-switch">
										<input type="checkbox" name="hd_pll_features[duplicate_content]" value="1" <?php checked( ! empty( $pll_settings['duplicate_content'] ) ); ?>>
										<span class="hde-slider"></span>
									</label>
								</div>
								<p class="hde-card-desc"><?php echo esc_html( $feature_descriptions['duplicate_content'] ); ?></p>
							</div>
							<div class="hde-card-bottom">
								<span class="hde-badge hde-badge-pro">SPL PRO FEATURE</span>
							</div>
						</div>

						<!-- Card 3: Share Slugs -->
						<div class="hde-card">
							<div>
								<div class="hde-card-top">
									<h3 class="hde-card-title"><?php esc_html_e( 'Share Slugs', 'SPL' ); ?></h3>
									<label class="hde-switch">
										<input type="checkbox" name="hd_pll_features[share_slugs]" value="1" <?php checked( ! empty( $pll_settings['share_slugs'] ) ); ?>>
										<span class="hde-slider"></span>
									</label>
								</div>
								<p class="hde-card-desc"><?php echo esc_html( $feature_descriptions['share_slugs'] ); ?></p>
							</div>
							<div class="hde-card-bottom">
								<span class="hde-badge hde-badge-pro">SPL PRO FEATURE</span>
							</div>
						</div>

						<!-- Card 4: Locale Fallback -->
						<div class="hde-card">
							<div>
								<div class="hde-card-top">
									<h3 class="hde-card-title"><?php esc_html_e( 'Locale Fallback', 'SPL' ); ?></h3>
									<label class="hde-switch">
										<input type="checkbox" name="hd_pll_features[locale_fallback]" value="1" <?php checked( ! empty( $pll_settings['locale_fallback'] ) ); ?>>
										<span class="hde-slider"></span>
									</label>
								</div>
								<p class="hde-card-desc"><?php echo esc_html( $feature_descriptions['locale_fallback'] ); ?></p>
							</div>
							<div class="hde-card-bottom">
								<span class="hde-badge hde-badge-pro">SPL PRO FEATURE</span>
							</div>
						</div>

						<!-- Card 5: WooCommerce Integration -->
						<div class="hde-card">
							<div>
								<div class="hde-card-top">
									<h3 class="hde-card-title"><?php esc_html_e( 'WooCommerce Integration', 'SPL' ); ?></h3>
									<span class="hde-badge hde-badge-warning">BYPASSED — POLYLANG WC ACTIVE</span>
								</div>
								<p class="hde-card-desc"><?php esc_html_e( 'Dual-sync order language, HPOS-safe order meta, product translation, and cart hash stability.', 'SPL' ); ?></p>
							</div>
							<div class="hde-card-bottom">
								<span class="hde-badge hde-badge-native">NATIVE INTEGRATION</span>
							</div>
						</div>

						<!-- Card 6: ACF / SCF Integration -->
						<div class="hde-card">
							<div>
								<div class="hde-card-top">
									<h3 class="hde-card-title"><?php esc_html_e( 'ACF / SCF Integration', 'SPL' ); ?></h3>
									<span class="hde-badge hde-badge-success">ACF LOADED</span>
								</div>
								<p class="hde-card-desc"><?php esc_html_e( 'Transparent post_id rewriting, options page slide-over language switcher, and field sync.', 'SPL' ); ?></p>
							</div>
							<div class="hde-card-bottom">
								<span class="hde-badge hde-badge-native">NATIVE INTEGRATION</span>
							</div>
						</div>
					</div>
				</div>

				<!-- Section 2: Admin Dashboard Language -->
				<div class="hde-section">
					<div class="hde-section-header">
						<h2 class="hde-section-title"><?php esc_html_e( 'Admin Dashboard Language', 'SPL' ); ?></h2>
						<p class="hde-section-desc"><?php esc_html_e( 'Control the language used for the WordPress admin dashboard.', 'SPL' ); ?></p>
					</div>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Admin Language', 'SPL' ); ?></th>
							<td>
								<?php $force_locale = $pll_settings['admin_force_locale'] ?? 'content'; ?>
								<select name="hd_pll_admin_force_locale" class="regular-text">
									<option value="content" <?php selected( $force_locale, 'content' ); ?>><?php esc_html_e( 'Content language (default)', 'SPL' ); ?></option>
									<option value="default" <?php selected( $force_locale, 'default' ); ?>><?php esc_html_e( 'Always use default language', 'SPL' ); ?></option>
									<option value="profile" <?php selected( $force_locale, 'profile' ); ?>><?php esc_html_e( 'Use user profile language', 'SPL' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Choose how the admin dashboard language is determined. "Content language" follows the content you are editing.', 'SPL' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Section 3: AI Settings Section -->
				<div class="hde-section">
					<?php self::renderAiSettings( $pll_settings ); ?>
				</div>

				<?php if ( $show_ttfp ) : ?>
				<!-- Section 4: Theme & Plugin String Scanner + Import/Export -->
				<div class="hde-section">
					<div class="hde-section-header">
						<h2 class="hde-section-title"><?php esc_html_e( 'Theme & Plugin Translation', 'SPL' ); ?></h2>
						<p class="hde-section-desc"><?php esc_html_e( 'Select themes and plugins to scan for translatable strings. Strings will appear in Languages > String translations.', 'SPL' ); ?></p>
					</div>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Themes', 'SPL' ); ?></th>
							<td>
								<fieldset>
									<?php foreach ( $themes as $name => $display ) : ?>
									<label style="display: block; margin-bottom: 6px;">
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
									<label style="display: block; margin-bottom: 6px;">
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

					<hr style="margin: 20px 0; border: 0; border-top: 1px solid #e2e8f0;">

					<div class="hde-section-header">
						<h2 class="hde-section-title"><?php esc_html_e( 'Translation Import/Export', 'SPL' ); ?></h2>
						<p class="hde-section-desc"><?php esc_html_e( 'Export string translations in CSV, PO, or XLIFF format. Import translated files back.', 'SPL' ); ?></p>
					</div>

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
								<p style="margin-top:12px;">
									<label for="hd-pll-export-group"><?php esc_html_e( 'Filter group:', 'SPL' ); ?></label>
									<select name="hd_pll_export_group" id="hd-pll-export-group" class="regular-text">
										<option value=""><?php esc_html_e( 'All groups', 'SPL' ); ?></option>
										<?php foreach ( $groups as $group ) : ?>
										<option value="<?php echo esc_attr( $group ); ?>"><?php echo esc_html( $group ); ?></option>
										<?php endforeach; ?>
									</select>
								</p>
								<?php endif; ?>

								<p style="margin-top:12px;">
									<strong><?php esc_html_e( 'File format:', 'SPL' ); ?></strong><br>
									<?php foreach ( $exportFormats as $key => $fmt ) : ?>
									<label style="display:inline-block; margin-right:12px; margin-top: 6px;">
										<input type="radio" name="hd_pll_export_format" value="<?php echo esc_attr( $key ); ?>"
											<?php checked( 'csv', $key ); ?>>
										<?php echo esc_html( $fmt['label'] ); ?>
									</label>
									<?php endforeach; ?>
								</p>

								<p style="margin-top:12px;">
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
				</div>
				<?php endif; ?>
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
			}

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

		// Admin force locale.
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
		<div class="hde-section-header">
			<h2 class="hde-section-title">
				<?php esc_html_e( 'AI Translation Engine', 'SPL' ); ?>
				<b style="font-size:13px;margin-left:8px;color:<?php echo AiClient::isAvailable() ? '#00a32a' : '#d63638'; ?>">
					<?php echo AiClient::isAvailable() ? esc_html__( '✓ HDAT route available', 'SPL' ) : esc_html__( '⚠ HDAT route missing', 'SPL' ); ?>
				</b>
			</h2>
			<p class="hde-section-desc"><?php esc_html_e( 'Configure automated AI translation routing, target languages, and field options.', 'SPL' ); ?></p>
		</div>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable AI translation', 'SPL' ); ?></th>
				<td>
					<label class="hde-switch">
						<input type="checkbox" name="hd_pll_ai_enabled" value="1" <?php checked( ! empty( $settings['ai_translation_enabled'] ) ); ?>>
						<span class="hde-slider"></span>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'HDAT consumer token', 'SPL' ); ?></th>
				<td>
					<?php $token = (string) ( $settings['ai_consumer_token'] ?? '' ); ?>
					<input type="text" class="regular-text" name="hd_pll_ai_consumer_token" value="<?php echo esc_attr( $token ); ?>" autocomplete="off">
					<p class="description"><?php esc_html_e( 'Model and provider routing are managed centrally by HDAT Credentials associated with this token.', 'SPL' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Target languages', 'SPL' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( (array) $languages as $language ) : ?>
							<?php if ( is_object( $language ) ) : ?>
							<label style="display:inline-block;margin-right:14px;margin-bottom:8px;">
								<input type="checkbox" name="hd_pll_ai_target_languages[]" value="<?php echo esc_attr( $language->slug ); ?>" <?php checked( in_array( $language->slug, $target_langs, true ) ); ?>>
								<?php echo esc_html( $language->name ); ?>
							</label>
							<?php endif; ?>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Bulk draft status', 'SPL' ); ?></th>
				<td>
					<select name="hd_pll_ai_post_status" class="regular-text">
						<option value="draft" <?php selected( $settings['ai_default_post_status'] ?? 'draft', 'draft' ); ?>><?php esc_html_e( 'Draft', 'SPL' ); ?></option>
						<option value="pending" <?php selected( $settings['ai_default_post_status'] ?? 'draft', 'pending' ); ?>><?php esc_html_e( 'Pending review', 'SPL' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Content types', 'SPL' ); ?></th>
				<td>
					<?php foreach ( $post_types as $type => $object ) : ?>
						<label style="display:inline-block;margin-right:14px;margin-bottom:6px;">
							<input type="checkbox" name="hd_pll_ai_content_types[]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $contentTypes, true ) ); ?>>
							<?php echo esc_html( $object->labels->name ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>
		<?php
	}

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
