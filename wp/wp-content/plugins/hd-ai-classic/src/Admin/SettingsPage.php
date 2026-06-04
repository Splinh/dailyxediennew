<?php
/**
 * Admin settings page.
 *
 * @package HDAC\Admin
 */

namespace HDAC\Admin;

defined( 'ABSPATH' ) || exit;

use HDAC\Asset;
use HDAC\Settings;

final class SettingsPage {

	private const MENU_SLUG = 'hdac-settings';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'registerMenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
	}

	/**
	 * Register menu item.
	 */
	public function registerMenu(): void {
		add_options_page(
			__( 'HD AI Classic', 'hd-ai-classic' ),
			__( 'HD AI Classic', 'hd-ai-classic' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render' ]
		);
	}

	/**
	 * Enqueue assets only on the settings page.
	 */
	public function enqueueAssets( string $hook ): void {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		Asset::enqueueJS( 'admin.js', [ 'jquery-core', 'wp-api-fetch' ], null, true, [ 'module', 'defer' ] );

		$handle = Asset::handle( 'admin.js' );
		if ( $handle ) {
			$settings = Settings::all();
			if ( ! empty( $settings['consumer_token'] ) ) {
				$settings['consumer_token'] = '****************';
			}

			Asset::localize(
				$handle,
				'hdacAdminData',
				[
					'nonce'    => wp_create_nonce( 'wp_rest' ),
					'settings' => $settings,
					'i18n'     => [
						'saveSuccess' => __( 'Settings saved successfully.', 'hd-ai-classic' ),
						'saveError'   => __( 'Failed to save settings. Please try again.', 'hd-ai-classic' ),
						'connecting'  => __( 'Testing connection...', 'hd-ai-classic' ),
						'connected'   => __( 'Connected successfully!', 'hd-ai-classic' ),
						'connError'   => __( 'Connection failed. Please check your token.', 'hd-ai-classic' ),
					],
				]
			);
		}
	}

	/**
	 * Render settings page wrapper.
	 */
	public function render(): void {
		?>
		<div class="wrap hdac-settings-wrap">
			<h1><?php esc_html_e( 'HD AI Classic Settings', 'hd-ai-classic' ); ?></h1>
			<p><?php esc_html_e( 'Configure Classic Editor AI generation through HD AI Toolkit.', 'hd-ai-classic' ); ?></p>
			<hr class="wp-header-end">

			<div id="hdac-settings-app" class="hdac-settings-container">
				<nav class="nav-tab-wrapper hdac-tabs" aria-label="<?php esc_attr_e( 'HD AI Classic settings sections', 'hd-ai-classic' ); ?>">
					<a href="#connection" class="nav-tab nav-tab-active" data-tab="connection"><?php esc_html_e( 'Connection', 'hd-ai-classic' ); ?></a>
					<a href="#generation" class="nav-tab" data-tab="generation"><?php esc_html_e( 'Generation', 'hd-ai-classic' ); ?></a>
					<a href="#features" class="nav-tab" data-tab="features"><?php esc_html_e( 'Features', 'hd-ai-classic' ); ?></a>
				</nav>

				<form id="hdac-settings-form" class="hdac-form">
					<div id="tab-connection" class="tab-content">
						<div class="hdac-section-header">
							<h2><?php esc_html_e( 'HDAT Connection', 'hd-ai-classic' ); ?></h2>
							<p><?php esc_html_e( 'Use an HDAT consumer token to route Classic Editor requests through the toolkit key pool.', 'hd-ai-classic' ); ?></p>
						</div>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><label for="hdac_consumer_token"><?php esc_html_e( 'HDAT Consumer Token', 'hd-ai-classic' ); ?></label></th>
									<td>
										<input type="password" id="hdac_consumer_token" name="consumer_token" class="regular-text" autocomplete="new-password" spellcheck="false">
										<p class="description"><?php esc_html_e( 'Enter your HDAT consumer token for authentication. Leave as-is to keep the configured token.', 'hd-ai-classic' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Connection Status', 'hd-ai-classic' ); ?></th>
									<td>
										<div class="hdac-inline-actions">
											<div id="hdac-connection-status" class="hdac-status-badge is-neutral">
												<span class="dashicons dashicons-marker"></span>
												<span class="status-text"><?php esc_html_e( 'Not Tested', 'hd-ai-classic' ); ?></span>
											</div>
											<button type="button" id="hdac-test-connection" class="button button-secondary"><?php esc_html_e( 'Test Connection', 'hd-ai-classic' ); ?></button>
										</div>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'HDAT Endpoint', 'hd-ai-classic' ); ?></th>
									<td>
										<code>/hdat/v1/models</code>
										<p class="description"><?php esc_html_e( 'Connection tests use the same internal REST dispatch path as generation requests.', 'hd-ai-classic' ); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div id="tab-generation" class="tab-content" hidden>
						<div class="hdac-section-header">
							<h2><?php esc_html_e( 'Generation Defaults', 'hd-ai-classic' ); ?></h2>
							<p><?php esc_html_e( 'Set conservative defaults for Classic Editor fields while HDAT handles provider rotation.', 'hd-ai-classic' ); ?></p>
						</div>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><label for="hdac_temperature"><?php esc_html_e( 'Default Temperature', 'hd-ai-classic' ); ?></label></th>
									<td>
										<input type="number" id="hdac_temperature" name="temperature" step="0.1" min="0.0" max="2.0" class="small-text">
										<p class="description"><?php esc_html_e( '0 is analytical, 1+ is creative. Range 0-2.', 'hd-ai-classic' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="hdac_content_format"><?php esc_html_e( 'Content Output Format', 'hd-ai-classic' ); ?></label></th>
									<td>
										<select id="hdac_content_format" name="content_format">
											<option value="html"><?php esc_html_e( 'HTML', 'hd-ai-classic' ); ?></option>
											<option value="plain"><?php esc_html_e( 'Plain text', 'hd-ai-classic' ); ?></option>
										</select>
										<p class="description"><?php esc_html_e( 'HTML preserves safe editor markup.', 'hd-ai-classic' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Token Limits', 'hd-ai-classic' ); ?></th>
									<td>
										<div class="hdac-token-fields">
											<label for="hdac_max_tokens_title"><span><?php esc_html_e( 'Title', 'hd-ai-classic' ); ?></span><input type="number" id="hdac_max_tokens_title" name="max_tokens_title" min="1" class="small-text"></label>
											<label for="hdac_max_tokens_excerpt"><span><?php esc_html_e( 'Excerpt', 'hd-ai-classic' ); ?></span><input type="number" id="hdac_max_tokens_excerpt" name="max_tokens_excerpt" min="1" class="small-text"></label>
											<label for="hdac_max_tokens_content"><span><?php esc_html_e( 'Content', 'hd-ai-classic' ); ?></span><input type="number" id="hdac_max_tokens_content" name="max_tokens_content" min="1" class="small-text"></label>
											<label for="hdac_max_tokens_image"><span><?php esc_html_e( 'Image Prompt', 'hd-ai-classic' ); ?></span><input type="number" id="hdac_max_tokens_image" name="max_tokens_image" min="1" class="small-text"></label>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div id="tab-features" class="tab-content" hidden>
						<div class="hdac-section-header">
							<h2><?php esc_html_e( 'Classic Editor Features', 'hd-ai-classic' ); ?></h2>
							<p><?php esc_html_e( 'Choose where HDAC injects AI actions inside Classic Editor screens.', 'hd-ai-classic' ); ?></p>
						</div>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><?php esc_html_e( 'Enable Features', 'hd-ai-classic' ); ?></th>
									<td>
										<fieldset class="hdac-feature-list">
											<legend class="screen-reader-text"><?php esc_html_e( 'Enable Features', 'hd-ai-classic' ); ?></legend>
											<label><input type="checkbox" name="features_enabled[]" value="title"><span><strong><?php esc_html_e( 'Post Title', 'hd-ai-classic' ); ?></strong><?php esc_html_e( 'Generate optimized Classic Editor titles.', 'hd-ai-classic' ); ?></span></label>
											<label><input type="checkbox" name="features_enabled[]" value="excerpt"><span><strong><?php esc_html_e( 'Post Excerpt', 'hd-ai-classic' ); ?></strong><?php esc_html_e( 'Create short summaries for excerpt fields.', 'hd-ai-classic' ); ?></span></label>
											<label><input type="checkbox" name="features_enabled[]" value="term-description"><span><strong><?php esc_html_e( 'Term Description', 'hd-ai-classic' ); ?></strong><?php esc_html_e( 'Draft taxonomy descriptions from term context.', 'hd-ai-classic' ); ?></span></label>
											<label><input type="checkbox" name="features_enabled[]" value="content"><span><strong><?php esc_html_e( 'Content', 'hd-ai-classic' ); ?></strong><?php esc_html_e( 'Generate, replace, or insert editor content.', 'hd-ai-classic' ); ?></span></label>
											<label><input type="checkbox" name="features_enabled[]" value="long-content"><span><strong><?php esc_html_e( 'Long Content', 'hd-ai-classic' ); ?></strong><?php esc_html_e( 'Generate long articles in smaller sequential requests.', 'hd-ai-classic' ); ?></span></label>
											<label><input type="checkbox" name="features_enabled[]" value="image-prompt"><span><strong><?php esc_html_e( 'Featured Image', 'hd-ai-classic' ); ?></strong><?php esc_html_e( 'Generate direct featured images or reusable image prompts.', 'hd-ai-classic' ); ?></span></label>
										</fieldset>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="hdac-action-bar">
						<button type="submit" id="hdac-save-settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'hd-ai-classic' ); ?></button>
						<span id="hdac-save-status" class="hdac-save-status" aria-live="polite"></span>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}
