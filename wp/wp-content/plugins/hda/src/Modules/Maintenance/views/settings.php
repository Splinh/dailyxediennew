<?php
/**
 * Maintenance module options panel.
 *
 * @package HDAddons\Modules\Maintenance
 */

use HDAddons\Helper;
use HDAddons\Modules\Maintenance\MaintenanceModule;

\defined( 'ABSPATH' ) || exit;

$mt_options      = MaintenanceModule::getOptions();
$enabled         = $mt_options[ MaintenanceModule::KEY_ENABLED ] ?? false;
$title           = $mt_options[ MaintenanceModule::KEY_TITLE ] ?? '';
$message         = $mt_options[ MaintenanceModule::KEY_MESSAGE ] ?? '';
$allowlist_ips   = $mt_options[ MaintenanceModule::KEY_ALLOWLIST_IPS ] ?? [];
$allowlist_roles = $mt_options[ MaintenanceModule::KEY_ALLOWLIST_ROLES ] ?? [];

// Get editable roles for selection.
$wp_roles = wp_roles()->get_names();
unset( $wp_roles['administrator'] ); // Admins always bypass.

?>
<div class="container">

	<?php if ( $enabled ) : ?>
		<div class="hda-notice hda-notice--warning">
			<p>
				<strong>🚧 <?php esc_html_e( 'Maintenance mode is currently ACTIVE.', 'hda' ); ?></strong>
				<?php esc_html_e( 'Frontend returns 503 to non-privileged visitors. Admins always have access.', 'hda' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<fieldset class="container-fieldset mt-6">
		<legend class="section-legend"><?php esc_html_e( 'Maintenance Settings', 'hda' ); ?></legend>

		<div class="hda-notice hda-notice--info">
			<p>
				<span class="dashicons dashicons-info"></span>
				<?php echo wp_kses_post( __( 'Non-privileged visitors get <code>503 Service Unavailable</code> with <code>Retry-After</code> header. Search engines won\'t de-index your pages.', 'hda' ) ); ?>
			</p>
		</div>

		<div class="section section-checkbox">
			<label class="heading" for="mt_enabled">
				<?php esc_html_e( 'Enable Maintenance Mode', 'hda' ); ?>
				<span class="hda-tip">
					<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
					<span class="hda-tip__body"><?php echo wp_kses( __( 'Returns <code>503 Service Unavailable</code> to all non-privileged visitors. Admins always bypass.', 'hda' ), [ 'code' => [] ] ); ?></span>
				</span>
			</label>
			<div class="option">
				<div class="controls">
					<label class="flex items-center gap-2 cursor-pointer">
						<input type="checkbox" class="checkbox" name="hda_maintenance[mt_enabled]" id="mt_enabled" <?php checked( $enabled, 1 ); ?> value="1">
						<span class="font-medium text-sm"><?php esc_html_e( 'Activate maintenance mode', 'hda' ); ?></span>
					</label>
				</div>
			</div>
		</div>

		<div id="hda-maintenance-fields" class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6 mt-6" <?php echo $enabled ? '' : 'style="display:none;"'; ?>>
			<div class="section">
				<label class="heading" for="mt_title">
					<?php esc_html_e( 'Page Title', 'hda' ); ?>
				</label>
				<div class="option">
					<input type="text" class="input" name="hda_maintenance[mt_title]" id="mt_title" value="<?php echo esc_attr( $title ); ?>" placeholder="Under Maintenance">
				</div>
			</div>
			<div class="section col-span-full">
				<label class="heading" for="mt_message">
					<?php esc_html_e( 'Message', 'hda' ); ?>
					<span class="hda-tip">
						<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
						<span class="hda-tip__body"><?php esc_html_e( 'Supports basic HTML tags.', 'hda' ); ?></span>
					</span>
				</label>
				<div class="option">
					<textarea class="textarea" name="hda_maintenance[mt_message]" id="mt_message" rows="3" placeholder="<?php esc_attr_e( 'We are currently performing scheduled maintenance...', 'hda' ); ?>"><?php echo esc_textarea( $message ); ?></textarea>
				</div>
			</div>
		</div>
	</fieldset>

		<fieldset id="hda-maintenance-access" class="container-fieldset" <?php echo $enabled ? '' : 'style="display:none;"'; ?>>
			<legend class="section-legend"><?php esc_html_e( 'Access Control', 'hda' ); ?></legend>
			<div class="hda-notice hda-notice--info">
				<p>
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'Administrators always have access. You can grant access to additional IP addresses or user roles below.', 'hda' ); ?>
				</p>
			</div>

			<div class="container grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6 mt-4">
				<div class="section">
					<label class="heading flex items-center" for="mt_allowlist_ips">
						<?php esc_html_e( 'Allowlist IP Addresses', 'hda' ); ?>
						<span class="hda-tip">
							<button type="button" class="hda-tip__icon" aria-label="<?php esc_attr_e( 'More info', 'hda' ); ?>">i</button>
							<span class="hda-tip__body"><?php esc_html_e( 'These IPs will bypass maintenance mode.', 'hda' ); ?></span>
						</span>
					</label>
					<div class="option">
						<div class="controls">
							<select multiple placeholder="<?php esc_attr_e( 'Enter IP addresses', 'hda' ); ?>" class="select select2-ips !w[100%]" name="hda_maintenance[mt_allowlist_ips][]" id="mt_allowlist_ips">
								<?php foreach ( $allowlist_ips as $ip ) : ?>
									<option value="<?php echo esc_attr( $ip ); ?>" selected="selected"><?php echo esc_html( $ip ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<details class="hda-details mt-2">
							<summary class="hda-details__summary">
								<span class="dashicons dashicons-editor-help"></span>
								<?php esc_html_e( 'Accepted formats', 'hda' ); ?>
							</summary>
							<div class="hda-details__content">
								<p class="mb-2"><?php esc_html_e( 'These IPs bypass maintenance:', 'hda' ); ?></p>
								<ul class="pl-6 list-disc text-sm text-slate-600">
									<li><?php echo wp_kses_post( __( 'Single IPv4: <code>192.168.1.1</code>', 'hda' ) ); ?></li>
									<li><?php echo wp_kses_post( __( 'Single IPv6: <code>2001:db8::1</code>', 'hda' ) ); ?></li>
									<li><?php echo wp_kses_post( __( 'CIDR: <code>192.168.1.0/24</code>, <code>2001:db8::/32</code>', 'hda' ) ); ?></li>
									<li><?php echo wp_kses_post( __( 'Dash range: <code>192.168.1.1-100</code>', 'hda' ) ); ?></li>
								</ul>
								<?php
								$current_ip = Helper::ipAddress();
								if ( $current_ip ) {
									printf(
										'<p class="mt-2 text-wp-primary">' . wp_kses_post( __( 'Your current IP: <code>%s</code>', 'hda' ) ) . '</p>',
										esc_html( $current_ip )
									);
								}
								?>
							</div>
						</details>
					</div>
			</div>
			<div class="section section-radio mt-6 md:mt-0">
				<span class="heading border-b-none"><?php esc_html_e( 'Allowlist User Roles', 'hda' ); ?></span>
				<div class="option inline-option">
					<div class="controls">
						<div class="inline-group">
							<?php foreach ( $wp_roles as $role_slug => $role_name ) : ?>
							<label class="flex items-center gap-2 cursor-pointer">
								<input type="checkbox" class="checkbox" name="hda_maintenance[mt_allowlist_roles][]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, $allowlist_roles, true ) ); ?>>
								<span class="font-medium text-sm"><?php echo esc_html( translate_user_role( $role_name ) ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<details class="hda-details mt-2">
					<summary class="hda-details__summary">
						<span class="dashicons dashicons-editor-help"></span>
						<?php esc_html_e( 'Role Bypass Rules', 'hda' ); ?>
					</summary>
					<div class="hda-details__content">
						<p><?php echo wp_kses( __( 'Checked roles can browse normally during maintenance. <strong>Administrator</strong> always bypasses and cannot be unchecked.', 'hda' ), [ 'strong' => [] ] ); ?></p>
					</div>
				</details>
			</div>
		</div>
	</fieldset>
</div>

<!-- Toggle maintenance sub-sections visibility -->
<script>
(function () {
	const toggle = document.getElementById('mt_enabled');
	const targets = [
		document.getElementById('hda-maintenance-fields'),
		document.getElementById('hda-maintenance-access'),
	];
	if (!toggle) return;

	toggle.addEventListener('change', function () {
		const show = this.checked ? '' : 'none';
		targets.forEach(el => { if (el) el.style.display = show; });
	});
})();
</script>
