<?php
/**
 * Single Layout — HDA Settings Page.
 *
 * Combines sidebar navigation and content panels into one file.
 * Replaces the old menu.php + content.php split.
 *
 * @package HDAddons\Modules\GlobalSetting
 */

use HDAddons\Modules\GlobalSetting\GlobalSetting;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$grouped_config         = GlobalSetting::getGroupedConfig();
$group_labels           = GlobalSetting::getGroupLabels();
$hda_config             = Helper::getOption( GlobalSetting::OPTION_NAME, [] );
$global_setting_options = $hda_config[ GlobalSetting::KEY_MODULES ] ?? [];

?>
<div class="wrap" id="_container">
	<form role="form" id="_settings_form" method="post" accept-charset="UTF-8" enctype="multipart/form-data">

		<?php wp_nonce_field( '_wpnonce_settings_form_' . get_current_user_id() ); ?>

		<div id="main" class="filter-tabs clearfix">

			<!-- ═══ Sidebar Navigation ═══ -->
			<div id="_nav" class="tabs-nav">
				<div class="logo-title">
					<h3>
						<?php esc_html_e( 'HDA Settings', 'hda' ); ?>
						<span>Version: <?php echo esc_html( HDA_VERSION ); ?></span>
					</h3>
				</div>
				<div class="save-bar">
					<button type="submit" name="_submit_settings" class="button button-primary"><?php esc_html_e( 'Save Changes', 'hda' ); ?></button>
				</div>
				<ul class="ul-menu-list">
					<?php
					// Global Setting tab (always visible — excluded from getGroupedConfig because alwaysActive).
					$general_info = $group_labels['general'] ?? [];
					$general_icon = $general_info['icon'] ?? 'dashicons-admin-generic';
					?>
					<li class="menu-group-header" data-group="general">
						<span class="dashicons <?php echo esc_attr( $general_icon ); ?>"></span>
						<?php echo esc_html( $general_info['label'] ?? __( 'General', 'hda' ) ); ?>
					</li>
					<li class="global_setting-settings menu-group-item">
						<button type="button" class="current" data-tab="global_setting_settings" title="<?php esc_attr_e( 'Global Setting', 'hda' ); ?>"><?php esc_html_e( 'Global Setting', 'hda' ); ?></button>
					</li>
					<?php

					foreach ( $grouped_config as $group_key => $modules ) :

						// Collect visible modules in this group.
						$visible_modules = [];
						foreach ( $modules as $slug => $value ) {
							if ( ! empty( $global_setting_options[ $slug ] ) || 'global_setting' === $slug ) {
								$visible_modules[ $slug ] = $value;
							}
						}

						// Skip group if no visible modules.
						if ( empty( $visible_modules ) ) {
							continue;
						}

						$group_info  = $group_labels[ $group_key ] ?? [];
						$group_label = $group_info['label'] ?? ucfirst( $group_key );
						$group_icon  = $group_info['icon'] ?? 'dashicons-admin-generic';
						?>
						<li class="menu-group-header" data-group="<?php echo esc_attr( $group_key ); ?>">
							<span class="dashicons <?php echo esc_attr( $group_icon ); ?>"></span>
							<?php echo esc_html( $group_label ); ?>
						</li>
						<?php

						foreach ( $visible_modules as $slug => $value ) :
							$menu_title = ! empty( $value['title'] ) ? $value['title'] : '';
							?>
							<li class="<?php echo esc_attr( $slug ); ?>-settings menu-group-item">
								<button type="button" data-tab="<?php echo esc_attr( $slug ); ?>_settings" title="<?php echo esc_attr( $menu_title ); ?>"><?php echo esc_html( $menu_title ); ?></button>
							</li>
						<?php endforeach; ?>

					<?php endforeach; ?>
				</ul>
			</div>

			<!-- ═══ Content Panels ═══ -->
			<div id="_content" class="tabs-content">
				<h2 class="hidden-text"></h2>

				<!-- Global Setting panel (always visible) -->
				<div id="global_setting_settings" class="group tabs-panel">
					<div class="section-heading">
						<h2><?php esc_html_e( 'Global Setting', 'hda' ); ?></h2>
						<div class="desc"><?php esc_html_e( 'Enable or disable plugin modules.', 'hda' ); ?></div>
					</div>
					<?php
					$current_slug = 'global_setting';
					require __DIR__ . '/module-toggles.php';
					?>
				</div>

				<?php
				// Module panels — iterate grouped config to match sidebar order.
				foreach ( $grouped_config as $group_key => $modules ) :
					foreach ( $modules as $current_slug => $value ) :

						// Check module active.
						if ( empty( $global_setting_options[ $current_slug ] ) && 'global_setting' !== $current_slug ) {
							continue;
						}

						$current_title       = ! empty( $value['title'] ) ? $value['title'] : '';
						$current_description = ! empty( $value['description'] ) ? $value['description'] : '';
						?>
					<div class="group tabs-panel">
						<div class="section-heading">
							<h2><?php echo esc_html( $current_title ); ?></h2>
							<div class="desc"><?php echo esc_html( $current_description ); ?></div>
						</div>
						<?php
						$option_file = HDA_PATH . 'src/Modules/' . Helper::capitalizedSlug( $current_slug, true ) . '/views/settings.php';
						if ( file_exists( $option_file ) ) {
							include $option_file;
						}
						?>
					</div>
					<?php endforeach; ?>
				<?php endforeach; ?>

				<div class="save-bar">
					<button type="submit" name="_submit_settings" class="button button-primary"><?php esc_html_e( 'Save Changes', 'hda' ); ?></button>
				</div>

				<script>
				// Pre-activate tab from URL hash (sync, before Vite bundle).
				(function(){
					var h=location.hash.slice(1);if(!h)return;
					var w=document.getElementById('_content');if(!w)return;
					var p=w.parentElement;if(!p)return;
					var tabs=p.querySelectorAll('.tabs-nav .menu-group-item>[data-tab]');
					var panels=w.querySelectorAll(':scope>.tabs-panel');
					for(var i=0;i<tabs.length;i++){
						if(tabs[i].getAttribute('data-tab')===h){
							for(var j=0;j<tabs.length;j++){tabs[j].classList.remove('current');panels[j]&&panels[j].classList.remove('show');}
							tabs[i].classList.add('current');panels[i]&&panels[i].classList.add('show');
							break;
						}
					}
				})();
				</script>
			</div>
		</div>
	</form>
</div>
