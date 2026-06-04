<?php
/**
 * Maintenance module - Restrict frontend access during maintenance.
 *
 * @package HDAddons\Modules\Maintenance
 */

namespace HDAddons\Modules\Maintenance;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\AbstractModule;
use HDAddons\Plugin;

defined( 'ABSPATH' ) || exit;

final class MaintenanceModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'maintenance';
	}

	public static function title(): string {
		return 'Maintenance';
	}

	public static function description(): string {
		return 'Maintenance mode for site access.';
	}

	public static function group(): string {
		return 'tools';
	}


	// ── Constants ───────────────────────────────────

	public const KEY_ENABLED         = 'enabled';
	public const KEY_TITLE           = 'title';
	public const KEY_MESSAGE         = 'message';
	public const KEY_ALLOWLIST_IPS   = 'allowlist_ips';
	public const KEY_ALLOWLIST_ROLES = 'allowlist_roles';

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		$options = self::getCachedOptions();

		if ( empty( $options[ self::KEY_ENABLED ] ) ) {
			return;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			add_action( 'admin_notices', self::adminNotice( ... ) );

			return;
		}

		if (
			wp_doing_cron()
			|| wp_doing_ajax()
			|| ( defined( 'REST_REQUEST' ) && \REST_REQUEST )
			|| ( defined( 'WP_CLI' ) && \WP_CLI )
		) {
			return;
		}

		add_action( 'template_redirect', $this->maybeShowMaintenancePage( ... ), 0 );
	}

	// ── Admin Notice ────────────────────────────────

	public static function adminNotice(): void {
		echo '<div class="notice notice-warning" style="border-left-color:#f0b849;">';
		echo '<p><strong>🚧 ' . esc_html__( 'Maintenance mode is active.', 'hda' ) . '</strong> ';
		echo esc_html__( 'The frontend is only accessible to administrators and allowlisted IPs/roles.', 'hda' );
		echo '</p></div>';
	}

	// ── Maintenance Page ────────────────────────────

	public function maybeShowMaintenancePage(): void {
		if ( $this->isVisitorAllowed() ) {
			return;
		}

		$options = self::getCachedOptions();
		$message = $options[ self::KEY_MESSAGE ] ?? '';
		$title   = $options[ self::KEY_TITLE ] ?? __( 'Under Maintenance', 'hda' );

		if ( empty( $message ) ) {
			$message = __( 'We are currently performing scheduled maintenance. We\'ll be back soon. Thank you for your patience.', 'hda' );
		}

		status_header( 503 );
		header( 'Retry-After: 3600' );
		nocache_headers();

		$this->renderMaintenancePage( $title, $message );
		exit;
	}

	private function isVisitorAllowed(): bool {
		$requestPath = isset( $_SERVER['REQUEST_URI'] )
			? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH )
			: '';

		if ( $requestPath && ( str_ends_with( $requestPath, 'wp-login.php' ) || str_contains( $requestPath, '/wp-admin/' ) ) ) {
			return true;
		}

		if ( is_user_logged_in() && current_user_can( Plugin::CAPABILITY ) ) {
			return true;
		}

		$options = self::getCachedOptions();

		$allowlistIps = $options[ self::KEY_ALLOWLIST_IPS ] ?? [];
		if ( ! empty( $allowlistIps ) ) {
			$ip = Helper::ipAddress();
			if ( $ip && Helper::ipMatchesAny( $ip, $allowlistIps ) ) {
				return true;
			}
		}

		$allowlistRoles = $options[ self::KEY_ALLOWLIST_ROLES ] ?? [];
		if ( ! empty( $allowlistRoles ) && is_user_logged_in() ) {
			$roles = wp_get_current_user()->roles;

			if ( ! empty( array_intersect( $roles, $allowlistRoles ) ) ) {
				return true;
			}
		}

		return false;
	}

	private function renderMaintenancePage( string $title, string $message ): void {
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $title ); ?> — <?php bloginfo( 'name' ); ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen,Ubuntu,sans-serif;background:#1a1a2e;color:#e0e0e0}
.maintenance-wrap{max-width:580px;padding:48px 32px;text-align:center}
.maintenance-icon{font-size:64px;margin-bottom:24px;display:block}
h1{font-size:28px;font-weight:700;margin-bottom:16px;color:#fff}
p{font-size:16px;line-height:1.7;color:#b0b0c0}
</style>
</head>
<body>
<div class="maintenance-wrap">
	<span class="maintenance-icon">🚧</span>
	<h1><?php echo esc_html( $title ); ?></h1>
	<p><?php echo wp_kses_post( $message ); ?></p>
</div>
</body>
</html>
		<?php
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		$allowlistIps = [];
		if ( ! empty( $data['mt_allowlist_ips'] ) ) {
			$allowlistIps = array_filter( array_map( 'sanitize_text_field', (array) $data['mt_allowlist_ips'] ) );
		}

		$allowlistRoles = [];
		if ( ! empty( $data['mt_allowlist_roles'] ) ) {
			$allowlistRoles = array_map( 'sanitize_key', (array) $data['mt_allowlist_roles'] );
		}

		$options = [
			self::KEY_ENABLED         => ! empty( $data['mt_enabled'] ),
			self::KEY_TITLE           => sanitize_text_field( $data['mt_title'] ?? '' ),
			self::KEY_MESSAGE         => wp_kses_post( $data['mt_message'] ?? '' ),
			self::KEY_ALLOWLIST_IPS   => $allowlistIps,
			self::KEY_ALLOWLIST_ROLES => $allowlistRoles,
		];

		self::saveOrRemove( self::optionKey(), $options, true );
	}
}
