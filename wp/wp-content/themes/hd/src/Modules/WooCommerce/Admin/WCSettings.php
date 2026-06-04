<?php
/**
 * WooCommerce Module Settings — Admin page under WooCommerce menu.
 *
 * Tab layout:
 *   - Settings: feature toggles (always visible).
 *   - Per-feature tab: visible only when feature is enabled, shows settingsFields().
 *
 * Tab state is persisted via URL hash (#tab-settings, #tab-gallery_thumbs, …).
 *
 * @package HD\Modules\WooCommerce\Admin
 */

namespace HD\Modules\WooCommerce\Admin;

use HD\Core\Helper;
use HD\Modules\WooCommerce\Contracts\HasSettings;
use HD\Modules\WooCommerce\WooCommerceModule;

defined( 'ABSPATH' ) || exit;

final class WCSettings {

	private const NONCE_ACTION = 'hd_wc_settings_save';
	private const NONCE_FIELD  = '_hd_wc_nonce';
	private const MENU_SLUG    = 'hd-woocommerce';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'addMenu' ], 99 );
		add_action( 'admin_init', [ self::class, 'handleSave' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueueAssets' ] );
	}

	/**
	 * Add submenu under WooCommerce.
	 */
	public static function addMenu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'HD WooCommerce', 'hd' ),
			__( 'HD WooCommerce', 'hd' ),
			'manage_options', // phpcs:ignore WordPress.Roles.Capabilities.Unknown
			self::MENU_SLUG,
			[ self::class, 'renderPage' ]
		);
	}

	/**
	 * Enqueue inline JS/CSS only on our page.
	 *
	 * @param string $hook Current admin hook.
	 */
	public static function enqueueAssets( string $hook ): void {
		if ( ! str_contains( $hook, self::MENU_SLUG ) ) {
			return;
		}

		wp_add_inline_style( 'wp-admin', self::inlineCss() );
		wp_add_inline_script( 'jquery', self::inlineJs(), 'after' );
	}

	/**
	 * Render settings page.
	 */
	public static function renderPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options  = WooCommerceModule::getCachedOptions();
		$features = WooCommerceModule::getFeatures();

		// Flash message.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = sanitize_key( $_GET['hd_wc_msg'] ?? '' );
		if ( 'saved' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'hd' ) . '</p></div>';
		}

		// Build list of enabled feature slugs (for conditional tab visibility).
		$enabledSlugs = [];
		foreach ( $features as $featureClass ) {
			if ( ! empty( $options[ $featureClass::slug() ] ) ) {
				$enabledSlugs[] = $featureClass::slug();
			}
		}
		?>
		<div class="wrap hd-wc-wrap">
			<h1><?php esc_html_e( 'HD WooCommerce', 'hd' ); ?></h1>

			<?php /* ── Tab navigation ── */ ?>
			<nav class="nav-tab-wrapper hd-wc-tabs" id="hd-wc-tab-nav">
				<a href="#tab-settings" class="nav-tab" data-tab="tab-settings">
					<?php esc_html_e( 'Settings', 'hd' ); ?>
				</a>
				<?php
				foreach ( $features as $featureClass ) :
					$slug = $featureClass::slug();
					if ( ! is_subclass_of( $featureClass, HasSettings::class ) ) {
						continue;
					}
					if ( empty( $featureClass::settingsFields() ) ) {
						continue;
					}
					$label   = self::getFeatureLabel( $slug );
					$visible = in_array( $slug, $enabledSlugs, true ) ? '' : ' style="display:none;"';
					?>
					<a href="#tab-<?php echo esc_attr( $slug ); ?>"
						class="nav-tab hd-wc-feature-tab"
						data-tab="tab-<?php echo esc_attr( $slug ); ?>"
						data-feature="<?php echo esc_attr( $slug ); ?>"
						<?php echo $visible; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
				<input type="hidden" name="hd_wc_save" value="1">

				<?php /* ── Settings Tab ── */ ?>
				<div class="hd-wc-tab-content" id="tab-settings">
					<h2><?php esc_html_e( 'Features', 'hd' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Enable or disable WooCommerce enhancement features. Sub-feature settings tabs appear automatically when enabled.', 'hd' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						foreach ( $features as $featureClass ) :
							$slug  = $featureClass::slug();
							$label = self::getFeatureLabel( $slug );
							?>
							<tr>
								<th scope="row"><?php echo esc_html( $label ); ?></th>
								<td>
									<label>
										<input type="checkbox"
												name="hd_wc_features[<?php echo esc_attr( $slug ); ?>]"
												value="1"
												class="hd-wc-feature-toggle"
												data-feature="<?php echo esc_attr( $slug ); ?>"
												<?php checked( ! empty( $options[ $slug ] ) ); ?>>
										<?php esc_html_e( 'Enable', 'hd' ); ?>
									</label>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
					<?php submit_button( __( 'Save Settings', 'hd' ) ); ?>
				</div>

				<?php /* ── Per-feature Settings Tabs ── */ ?>
				<?php
				foreach ( $features as $featureClass ) :
					if ( ! is_subclass_of( $featureClass, HasSettings::class ) ) {
						continue;
					}

					/** @var class-string<\HD\Modules\WooCommerce\Contracts\WooFeatureInterface&\HD\Modules\WooCommerce\Contracts\HasSettings> $featureClass */
					$slug   = $featureClass::slug();
					$label  = self::getFeatureLabel( $slug );
					$fields = $featureClass::settingsFields();
					if ( empty( $fields ) ) {
						continue;
					}
					?>
					<div class="hd-wc-tab-content" id="tab-<?php echo esc_attr( $slug ); ?>" style="display:none;">
						<h2><?php echo esc_html( $label . ' — ' . __( 'Settings', 'hd' ) ); ?></h2>
						<table class="form-table" role="presentation">
							<?php foreach ( $fields as $key => $field ) : ?>
								<tr>
									<th scope="row"><label for="hd_wc_field_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( self::fieldLabel( $key ) ); ?></label></th>
									<td><?php self::renderField( $key, $field, $options[ $key ] ?? ( $field['default'] ?? '' ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</table>
						<?php submit_button( __( 'Save Settings', 'hd' ) ); ?>
					</div>
				<?php endforeach; ?>

			</form>
		</div>
		<?php
	}

	/**
	 * Handle form submission.
	 */
	public static function handleSave(): void {
		if ( empty( $_POST['hd_wc_save'] ) || ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! wp_verify_nonce( $_POST[ self::NONCE_FIELD ] ?? '', self::NONCE_ACTION ) ) {
			return;
		}

		$features     = WooCommerceModule::getFeatures();
		$postFeatures = $_POST['hd_wc_features'] ?? [];
		$postSettings = $_POST['hd_wc_settings'] ?? [];
		$save         = [];

		// Feature toggles.
		foreach ( $features as $featureClass ) {
			$slug          = $featureClass::slug();
			$save[ $slug ] = ! empty( $postFeatures[ $slug ] );
		}

		// Feature-specific settings.
		foreach ( $features as $featureClass ) {
			if ( ! is_subclass_of( $featureClass, HasSettings::class ) ) {
				continue;
			}

			$fields   = $featureClass::settingsFields();
			$defaults = $featureClass::defaults();

			foreach ( $fields as $key => $field ) {
				// Toggle/checkbox: unchecked → not in POST → must be false (not default).
				$fallback     = 'toggle' === ( $field['type'] ?? 'text' ) ? '' : ( $defaults[ $key ] ?? '' );
				$raw          = $postSettings[ $key ] ?? $fallback;
				$save[ $key ] = self::sanitizeField( $field, $raw );
			}
		}

		Helper::updateOption( WooCommerceModule::optionKey(), $save );
		WooCommerceModule::resetCache();

		$redirect = add_query_arg( 'hd_wc_msg', 'saved', admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	// ── Helpers ──────────────────────────────────────

	/**
	 * Human-readable feature labels.
	 *
	 * @param string $slug Feature slug.
	 *
	 * @return string Label.
	 */
	private static function getFeatureLabel( string $slug ): string {
		$labels = [
			'swatches'       => __( 'Variation Swatches', 'hd' ),
			'gallery_thumbs' => __( 'Product Gallery', 'hd' ),
			'quick_view'     => __( 'Quick View', 'hd' ),
			'ajax_filter'    => __( 'AJAX Product Filter', 'hd' ),
		];

		return $labels[ $slug ] ?? ucwords( str_replace( '_', ' ', $slug ) );
	}

	/**
	 * Convert setting key to label: gallery_layout → Gallery Layout.
	 *
	 * @param string $key Setting key.
	 *
	 * @return string Label.
	 */
	private static function fieldLabel( string $key ): string {
		// Strip common feature prefixes for cleaner display
		$clean = preg_replace( '/^(swatch|gallery|quick_view|ajax_filter)_/', '', $key );

		return ucwords( str_replace( '_', ' ', $clean ) );
	}

	/**
	 * Render a single settings field.
	 *
	 * @param string $key   Setting key.
	 * @param array  $field Field definition from settingsFields().
	 * @param mixed  $value Current value.
	 */
	private static function renderField( string $key, array $field, mixed $value ): void {
		$type = $field['type'] ?? 'text';
		$name = 'hd_wc_settings[' . esc_attr( $key ) . ']';
		$id   = 'hd_wc_field_' . esc_attr( $key );

		match ( $type ) {
			'select' => self::renderSelect( $name, $id, $field['options'] ?? [], $value ),
			'toggle' => self::renderToggle( $name, $id, $field, $value ),
			'number' => self::renderNumber( $name, $id, $field, $value ),
			default  => printf( '<input type="text" id="%s" name="%s" value="%s" class="regular-text">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) ),
		};
	}

	/**
	 * Render select dropdown.
	 *
	 * @param string        $name    Input name.
	 * @param string        $id      Input id.
	 * @param array<string> $options Option values.
	 * @param mixed         $value   Current value.
	 */
	private static function renderSelect( string $name, string $id, array $options, mixed $value ): void {
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
		foreach ( $options as $optKey => $optLabel ) {
			// Backwards compatibility for flat arrays: ['a', 'b']
			if ( is_int( $optKey ) ) {
				$optKey   = $optLabel;
				$optLabel = ucfirst( $optLabel );
			}
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $optKey ),
				selected( $value, $optKey, false ),
				esc_html( $optLabel )
			);
		}
		echo '</select>';
	}

	/**
	 * Render toggle checkbox.
	 *
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param mixed  $value Current value.
	 */
	private static function renderToggle( string $name, string $id, array $field, mixed $value ): void {
		$help = $field['help'] ?? '';

		printf(
			'<label><input type="checkbox" id="%s" name="%s" value="1" %s> %s</label>',
			esc_attr( $id ),
			esc_attr( $name ),
			checked( $value, true, false ),
			esc_html__( 'Enable', 'hd' )
		);

		if ( $help ) {
			printf( '<p class="description">%s</p>', esc_html( $help ) );
		}
	}

	/**
	 * Render number input with min/max.
	 *
	 * @param string $name  Input name.
	 * @param string $id    Input id.
	 * @param array  $field Field definition.
	 * @param mixed  $value Current value.
	 */
	private static function renderNumber( string $name, string $id, array $field, mixed $value ): void {
		$min  = isset( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '';
		$max  = isset( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '';
		$step = isset( $field['step'] ) ? ' step="' . esc_attr( $field['step'] ) . '"' : '';
		$help = $field['help'] ?? '';

		printf(
			'<input type="number" id="%s" name="%s" value="%s" class="small-text"%s%s%s>',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			$min,
			$max,
			$step
		);

		if ( $help ) {
			printf( '<p class="description">%s</p>', esc_html( $help ) );
		}
	}

	/**
	 * Sanitize a field value based on its type.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value from POST.
	 *
	 * @return mixed Sanitized value.
	 */
	private static function sanitizeField( array $field, mixed $raw ): mixed {
		$type    = $field['type'] ?? 'text';
		$options = $field['options'] ?? [];

		return match ( $type ) {
			'select' => array_key_exists( $raw, $options ) ? $raw : ( $field['default'] ?? '' ),
			'toggle' => ! empty( $raw ),
			'number' => self::sanitizeNumber( $field, $raw ),
			default  => sanitize_text_field( $raw ),
		};
	}

	/**
	 * Sanitize number field value — clamp to min/max if defined.
	 * Allows 0 (means disabled / auto mode in frontend).
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw POST value.
	 *
	 * @return int|float Sanitized number.
	 */
	private static function sanitizeNumber( array $field, mixed $raw ): int|float {
		$value = (float) $raw;
		$min   = isset( $field['min'] ) ? (float) $field['min'] : 0.0;
		$max   = isset( $field['max'] ) ? (float) $field['max'] : (float) PHP_INT_MAX;

		if ( 0.0 === $value ) {
			return 0; // Explicitly allow 0 = auto
		}

		$clamped = max( $min, min( $max, $value ) );

		// Return int if it's a whole number, else float
		return (float) (int) $clamped === $clamped ? (int) $clamped : $clamped;
	}

	// ── Inline assets ────────────────────────────────

	/**
	 * Inline CSS for the tab UI.
	 */
	private static function inlineCss(): string {
		return <<<'CSS'
.hd-wc-wrap .nav-tab-wrapper { margin-bottom: 0; }
.hd-wc-wrap .hd-wc-tab-content { background: #fff; border: 1px solid #c3c4c7; border-top: none; padding: 16px 20px 8px; }
CSS;
	}

	/**
	 * Inline JS: hash-based tab persistence + toggle visibility of feature tabs.
	 */
	private static function inlineJs(): string {
		return <<<'JS'
(function($){
	var STORAGE_KEY = 'hd_wc_active_tab';

	function activateTab(tabId) {
		var $nav  = $('#hd-wc-tab-nav');
		var $tabs = $('.hd-wc-tab-content');
		var $link = $nav.find('[data-tab="' + tabId + '"]');

		// Fallback to settings if target not found / hidden
		if ( ! $link.length || $link.css('display') === 'none' ) {
			tabId = 'tab-settings';
			$link = $nav.find('[data-tab="tab-settings"]');
		}

		$nav.find('.nav-tab').removeClass('nav-tab-active');
		$link.addClass('nav-tab-active');

		$tabs.hide();
		$('#' + tabId).show();

		// Persist: hash + sessionStorage (hash survives refresh, storage is backup)
		try { sessionStorage.setItem(STORAGE_KEY, tabId); } catch(e){}
		if (window.location.hash !== '#' + tabId) {
			history.replaceState(null, '', window.location.pathname + window.location.search + '#' + tabId);
		}
	}

	function resolveInitialTab() {
		// 1. URL hash
		var hash = (window.location.hash || '').replace('#', '');
		if (hash) { return hash; }
		// 2. sessionStorage
		try { var s = sessionStorage.getItem(STORAGE_KEY); if (s) { return s; } } catch(e){}
		return 'tab-settings';
	}

	$(function(){
		// Tab click
		$('#hd-wc-tab-nav').on('click', '.nav-tab', function(e){
			e.preventDefault();
			activateTab($(this).data('tab'));
		});

		// Feature checkbox toggle → show/hide corresponding tab
		$('.hd-wc-feature-toggle').on('change', function(){
			var slug   = $(this).data('feature');
			var $tab   = $('[data-tab="tab-' + slug + '"]');
			if ( ! $tab.length ) { return; }

			if ($(this).is(':checked')) {
				$tab.show();
			} else {
				$tab.hide();
				// If currently active, revert to settings
				if ($tab.hasClass('nav-tab-active')) {
					activateTab('tab-settings');
				}
			}
		});

		// Init
		activateTab(resolveInitialTab());
	});
}(jQuery));
JS;
	}
}
