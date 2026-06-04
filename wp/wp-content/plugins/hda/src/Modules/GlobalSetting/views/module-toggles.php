<?php
/**
 * Module Toggles — Enable/disable plugin modules.
 *
 * Included by the main settings.php within the Global Setting panel.
 *
 * @var array  $grouped_config
 * @var array  $group_labels
 * @var array  $global_setting_options
 * @var array  $hda_config
 * @var string $current_slug
 *
 * @package HDAddons\Modules\GlobalSetting
 */

use HDAddons\Helper;
use HDAddons\Modules\GlobalSetting\GlobalSetting;

\defined( 'ABSPATH' ) || exit;

?>
<div class="hda-bulk-actions">
	<button type="button" class="button button-primary" id="hda-enable-all">
		<span class="dashicons dashicons-yes-alt"></span>
		<?php esc_html_e( 'Enable All', 'hda' ); ?>
	</button>
	<button type="button" class="button hda-btn-danger" id="hda-disable-all">
		<span class="dashicons dashicons-dismiss"></span>
		<?php esc_html_e( 'Disable All', 'hda' ); ?>
	</button>
</div>

<div class="hda-notice hda-notice--info">
	<p>
		<span class="dashicons dashicons-admin-plugins"></span>
		<?php esc_html_e( 'Enable modules to activate features. Enabled modules appear in the left menu. Disable unused ones to save resources.', 'hda' ); ?>
	</p>
</div>

<?php
foreach ( $grouped_config as $group_key => $modules ) :

	// Skip the 'general' group (that's global_setting itself)
	$filterable_modules = array_filter(
		$modules,
		static fn( $slug ) => $slug !== $current_slug,
		ARRAY_FILTER_USE_KEY
	);

	if ( empty( $filterable_modules ) ) {
		continue;
	}

	$group_info  = $group_labels[ $group_key ] ?? [];
	$group_label = $group_info['label'] ?? ucfirst( $group_key );
	$group_icon  = $group_info['icon'] ?? 'dashicons-admin-generic';
	?>
	<div class="hda-module-group">
		<h3 class="hda-module-group__title">
			<span class="dashicons <?php echo esc_attr( $group_icon ); ?>"></span>
			<?php echo esc_html( $group_label ); ?>
		</h3>
		<div class="container grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-6">
			<?php
			foreach ( $filterable_modules as $slug => $value ) :
				$menu_title  = ! empty( $value['title'] ) ? $value['title'] : '';
				$description = ! empty( $value['description'] ) ? $value['description'] : '';
				?>
				<div class="hda-module-card" id="section_<?php echo esc_attr( $slug ); ?>">
					<label class="hda-module-card__label" for="<?php echo esc_attr( $slug ); ?>">
						<div class="hda-module-card__toggle">
							<input type="checkbox" class="checkbox" name="<?php echo esc_attr( $slug ); ?>" id="<?php echo esc_attr( $slug ); ?>" <?php checked( $global_setting_options[ $slug ] ?? false, 1 ); ?> value="1">
						</div>
						<div class="hda-module-card__info">
							<span class="hda-module-card__title"><?php echo esc_html( $menu_title ); ?></span>
							<?php if ( ! empty( $description ) ) : ?>
								<span class="hda-module-card__desc"><?php echo esc_html( $description ); ?></span>
							<?php endif; ?>
						</div>
					</label>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endforeach; ?>

<?php
// ─── Data Cleanup Settings ─────────────────────────
$clean_uninstall = $hda_config[ GlobalSetting::KEY_CLEAN_UNINSTALL ] ?? false;
?>
<div class="hda-data-cleanup">
	<h3 class="hda-data-cleanup__title">
		<span class="dashicons dashicons-database-remove"></span>
		<span class="uppercase"><?php esc_html_e( 'Data Cleanup', 'hda' ); ?></span>
	</h3>
	<div class="section section-checkbox hda-data-cleanup__option">
		<div class="option">
			<div class="controls">
				<label>
					<input type="checkbox" class="checkbox" name="<?php echo esc_attr( GlobalSetting::KEY_CLEAN_UNINSTALL ); ?>" id="<?php echo esc_attr( GlobalSetting::KEY_CLEAN_UNINSTALL ); ?>" <?php checked( $clean_uninstall, 1 ); ?> value="1">
					<span class="font-medium text-sm"><?php esc_html_e( 'Delete all plugin data when uninstalling', 'hda' ); ?></span>
				</label>
			</div>
		</div>
		<p class="hda-data-cleanup__hint">
			<?php esc_html_e( 'All data will be permanently deleted on uninstall. Uncheck to preserve.', 'hda' ); ?>
		</p>
	</div>

	<div class="hda-data-cleanup__note">
		<span class="dashicons dashicons-info-outline"></span>
		<?php esc_html_e( 'Disabling a module preserves its settings. Data is only removed when the module is deleted from the codebase.', 'hda' ); ?>
	</div>
</div>

<?php
// ─── GitHub Auto-Update Token ─────────────────────────
$stored_token     = Helper::getOption( GlobalSetting::KEY_GITHUB_TOKEN, '' );
$has_token        = ! empty( $stored_token );
$token_field_name = 'hda_global_setting[' . GlobalSetting::KEY_GITHUB_TOKEN . ']';
?>
<div class="hda-data-cleanup" style="margin-top:16px;">
	<h3 class="hda-data-cleanup__title">
		<span class="dashicons dashicons-update-alt"></span>
		<span class="uppercase"><?php esc_html_e( 'Plugin Auto-Update', 'hda' ); ?></span>
	</h3>
	<div class="section section-input hda-data-cleanup__option">
		<div class="option">
			<div class="controls" style="max-width:480px;">
				<input
					type="password"
					id="hda_github_token"
					name="<?php echo esc_attr( $token_field_name ); ?>"
					value="<?php echo $has_token ? '***' . substr( $stored_token, -4 ) : ''; ?>"
					placeholder="<?php esc_attr_e( 'Enter your access token', 'hda' ); ?>"
					class="regular-text"
					autocomplete="new-password"
					spellcheck="false"
				>
				<?php if ( $has_token ) : ?>
					<span style="display:inline-flex;align-items:center;gap:4px;color:#46b450;margin-top:6px;font-size:12px;">
						<span class="dashicons dashicons-yes-alt" style="font-size:16px;height:16px;width:16px;"></span>
						<?php esc_html_e( 'Token is configured', 'hda' ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
		<p class="hda-data-cleanup__hint">
			<?php esc_html_e( 'Access token for automatic plugin updates. Leave blank to clear. Token is encrypted before storage.', 'hda' ); ?>
		</p>
	</div>
	<div class="hda-data-cleanup__note">
		<span class="dashicons dashicons-info-outline"></span>
		<?php esc_html_e( 'Contact SPLWorks Agency to obtain your access token. Leave blank if you do not have one.', 'hda' ); ?>
	</div>
</div>

<script>
(function () {
	const panel = document.getElementById('global_setting_settings');
	if (!panel) return;

	const enableBtn = document.getElementById('hda-enable-all');
	const disableBtn = document.getElementById('hda-disable-all');

	function toggleAll(checked) {
		const boxes = panel.querySelectorAll('.hda-module-card .checkbox');
		boxes.forEach(function (cb) {
			// Don't toggle the uninstall cleanup checkbox
			if (cb.name === '<?php echo esc_js( GlobalSetting::KEY_CLEAN_UNINSTALL ); ?>') return;
			cb.checked = checked;
		});
	}

	enableBtn?.addEventListener('click', function () { toggleAll(true); });
	disableBtn?.addEventListener('click', function () { toggleAll(false); });
})();
</script>
