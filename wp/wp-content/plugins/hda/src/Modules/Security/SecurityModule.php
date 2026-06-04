<?php
/**
 * Security module - implements various security enhancements for WordPress.
 *
 * Simplified option keys:
 * - comments_off: disables comments system entirely (kept separate — major UX impact)
 * - wp_hardening: combines XMLRPC, WP version, wp-links-opml, RSS, readme, App Passwords
 * - server_config: server-level security rules
 * - lock_files: critical file permissions
 *
 * @package HDAddons\Modules\Security
 */

namespace HDAddons\Modules\Security;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\AbstractModule;
use HDAddons\Modules\Security\AccessControl;
use HDAddons\Modules\Security\Comment;
use HDAddons\Modules\Security\Firewall\Firewall;
use HDAddons\Modules\Security\Readme;
use HDAddons\Modules\Security\ServerConfig\ServerConfig;
use HDAddons\Modules\Security\Xmlrpc;

defined( 'ABSPATH' ) || exit;

final class SecurityModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'security';
	}

	public static function title(): string {
		return 'Security';
	}

	public static function description(): string {
		return 'WAF, firewall, traffic logging, and rate limiting.';
	}

	public static function group(): string {
		return 'security';
	}

	public static function cronHooks(): array {
		return [ 'hda_threat_intel_sync' ];
	}

	// ── Constants ───────────────────────────────────

	public const KEY_COMMENTS_OFF  = 'comments_off';
	public const KEY_WP_HARDENING  = 'wp_hardening';
	public const KEY_SERVER_CONFIG = 'server_config';
	public const KEY_LOCK_FILES    = 'lock_files';

	/**
	 * Security settings from theme config (lazy-loaded).
	 */
	private ?array $settings = null;

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		// Register GeoIP provider for the theme (always, regardless of context).
		AccessControl::registerGeoIPProvider();

		$options = self::getCachedOptions();

		if ( ! empty( $options[ self::KEY_COMMENTS_OFF ] ) ) {
			( new Comment() )->disable();
		}

		if ( ! empty( $options[ self::KEY_WP_HARDENING ] ) ) {
			$this->initHardening();
		}

		add_filter( 'all_plugins', $this->hidePluginInstall( ... ), 10 );
		add_filter( 'user_has_cap', $this->restrictPluginInstall( ... ), 10, 4 );
		add_filter( 'user_has_cap', $this->preventDeletionAccounts( ... ), 11, 3 );
		add_action( 'delete_user', $this->preventDeletionUser( ... ), 10 );
		add_action( 'pre_get_users', $this->hideUsers( ... ), 20 );
		add_filter( 'views_users', $this->updateUserViewsCounts( ... ) );

		// Sub-modules (Firewall, Traffic Monitor, Access Control).
		$this->initSecuritySubModules();
	}

	// ── Hardening ───────────────────────────────────

	/**
	 * WP Security Hardening: disables XMLRPC, hides WP version,
	 * blocks wp-links-opml, disables RSS feeds, removes readme.html,
	 * and disables Application Passwords.
	 */
	private function initHardening(): void {
		// Disable XML-RPC.
		( new Xmlrpc() )->disable();

		// Hide WP version from meta tags and admin footer.
		add_filter( 'update_footer', '__return_empty_string', 11 );
		add_filter( 'the_generator', '__return_empty_string' );

		// Block wp-links-opml.php.
		add_action(
			'init',
			static function (): void {
				$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

				if ( str_contains( $uri, 'wp-links-opml.php' ) ) {
					status_header( 403 );
					exit;
				}
			}
		);

		// Disable RSS/Atom feeds.
		$feedActions = [ 'do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom', 'do_feed_rss2_comments', 'do_feed_atom_comments' ];
		foreach ( $feedActions as $action ) {
			add_action( $action, $this->disableFeed( ... ), 1 );
		}
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'feed_links', 2 );

		// Auto-delete readme.html.
		new Readme();

		// Disable Application Passwords (WP 5.6+).
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
		add_action(
			'admin_init',
			static function (): void {
				remove_action( 'show_user_profile', 'wp_user_application_passwords_list' );
				remove_action( 'edit_user_profile', 'wp_user_application_passwords_list' );
			}
		);
	}

	/**
	 * Load WAF Firewall and Security Log sub-modules.
	 */
	private function initSecuritySubModules(): void {
		// WAF Firewall.
		$firewallOptions = self::getSubOptions( Firewall::SUB_KEY );
		if ( ! empty( $firewallOptions[ Firewall::KEY_ENABLED ] ) ) {
			new Firewall();
		}

		// AccessControl (IP/Country blocking) — always load.
		new AccessControl();
	}

	// ── Feed / Plugins / Users ──────────────────────

	/**
	 * Get security settings (lazy-loaded after theme init).
	 */
	private function getSecuritySettings(): array {
		return $this->settings ??= Helper::filterSettingOptions( 'security', [] );
	}

	/**
	 * Get user IDs protected from deletion/editing.
	 */
	private function getProtectedUserIds(): array {
		return array_map( 'absint', (array) ( $this->getSecuritySettings()['disallowed_users_ids_delete_account'] ?? [] ) );
	}

	/**
	 * Get user IDs allowed to see all plugins.
	 */
	private function getPluginVisibleUserIds(): array {
		return (array) ( $this->getSecuritySettings()['allowed_users_ids_show_plugins'] ?? [] );
	}

	/**
	 * Get user IDs allowed to install plugins/themes.
	 */
	private function getPluginInstallUserIds(): array {
		return (array) ( $this->getSecuritySettings()['allowed_users_ids_install_plugins'] ?? [] );
	}

	/**
	 * Redirect feed requests to homepage.
	 */
	public function disableFeed(): void {
		Helper::redirect( trailingslashit( esc_url( network_home_url() ) ) );
	}

	/**
	 * Hide specific plugins from the plugin list for non-authorized users.
	 */
	public function hidePluginInstall( array $plugins ): array {
		$allowed_ids = $this->getPluginVisibleUserIds();
		$user_id     = get_current_user_id();

		if ( ! in_array( $user_id, $allowed_ids, true ) ) {
			unset( $plugins[ HDA_PLUGIN_BASENAME ] );
		}

		return $plugins;
	}

	/**
	 * Hide protected accounts from the Users screen.
	 */
	public function hideUsers( \WP_User_Query $query ): void {
		if ( 'users.php' !== ( $GLOBALS['pagenow'] ?? '' ) || ! is_admin() ) {
			return;
		}

		$hidden_ids = $this->getProtectedUserIds();
		if ( empty( $hidden_ids ) ) {
			return;
		}

		$user_id    = get_current_user_id();
		$hidden_ids = array_map( 'absint', $hidden_ids );

		if ( ! in_array( $user_id, $hidden_ids, true ) ) {
			$existing_exclude = $query->get( 'exclude' ) ?: [];
			$query->set( 'exclude', array_merge( (array) $existing_exclude, $hidden_ids ) );
		}
	}

	/**
	 * Update user counts in the table views (All, Administrator, etc.)
	 */
	public function updateUserViewsCounts( array $views ): array {
		$hidden_ids = $this->getProtectedUserIds();
		if ( empty( $hidden_ids ) ) {
			return $views;
		}

		$user_id    = get_current_user_id();
		$hidden_ids = array_map( 'absint', $hidden_ids );

		if ( in_array( $user_id, $hidden_ids, true ) ) {
			return $views;
		}

		// Count how many hidden users there are per role (batch query).
		$hidden_counts = [ 'all' => count( $hidden_ids ) ];
		$hidden_users  = get_users(
			[
				'include' => $hidden_ids,
				'fields'  => [ 'ID' ],
			]
		);

		foreach ( $hidden_users as $u ) {
			$user_data = get_userdata( $u->ID ); // Already primed by get_users().
			if ( $user_data && ! empty( $user_data->roles ) ) {
				foreach ( $user_data->roles as $role ) {
					$hidden_counts[ $role ] = ( $hidden_counts[ $role ] ?? 0 ) + 1;
				}
			}
		}

		// Subtract from the views HTML.
		// WP format: <span class="count">(2)</span> — parens inside the span.
		foreach ( $views as $key => $html ) {
			if ( empty( $hidden_counts[ $key ] ) ) {
				continue;
			}

			if ( preg_match( '/<span class="count">\(([0-9,]+)\)<\/span>/', $html, $matches ) ) {
				$current_count = (int) str_replace( ',', '', $matches[1] );
				$new_count     = max( 0, $current_count - $hidden_counts[ $key ] );
				$views[ $key ] = str_replace(
					'<span class="count">(' . $matches[1] . ')</span>',
					'<span class="count">(' . number_format_i18n( $new_count ) . ')</span>',
					$html
				);
			}
		}

		return $views;
	}

	/**
	 * Prevent deletion of protected user accounts.
	 */
	public function preventDeletionUser( int $user_id ): void {
		$hidden_ids = $this->getProtectedUserIds();

		if ( in_array( $user_id, $hidden_ids, true ) ) {
			wp_die(
				__( 'You cannot delete this admin account.', 'hda' ),
				__( 'Error', 'hda' ),
				[ 'response' => 403 ]
			);
		}
	}

	/**
	 * Remove caps for editing/deleting protected accounts.
	 */
	public function preventDeletionAccounts( array $allcaps, array $cap, array $args ): array {
		$hidden_ids = $this->getProtectedUserIds();

		if ( isset( $cap[0] ) && in_array( $cap[0], [ 'delete_users', 'edit_users' ], true ) ) {
			$user_id_to_delete = $args[2] ?? 0;
			if ( $user_id_to_delete && in_array( $user_id_to_delete, $hidden_ids, true ) ) {
				unset( $allcaps['delete_users'], $allcaps['edit_users'] );
			}
		}

		return $allcaps;
	}

	/**
	 * Restrict plugin installation for non-authorized users.
	 */
	public function restrictPluginInstall( array $allcaps, array $caps, array $args, \WP_User $user ): array {
		$allowed_ids = $this->getPluginInstallUserIds();

		if ( in_array( $user->ID, $allowed_ids, true ) ) {
			return $allcaps;
		}

		if ( isset( $allcaps['activate_plugins'] ) ) {
			unset( $allcaps['install_plugins'], $allcaps['delete_plugins'] );
		}

		unset(
			$allcaps['install_themes'],
			$allcaps['edit_plugins'],
			$allcaps['edit_themes']
		);

		return $allcaps;
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		// ── Core security options ────────────────────
		$options = self::extractFields(
			$data,
			[
				self::KEY_COMMENTS_OFF,
				self::KEY_WP_HARDENING,
				self::KEY_SERVER_CONFIG,
				self::KEY_LOCK_FILES,
			]
		);

		self::saveOrRemove( self::optionKey(), $options );

		$isHardening = ! empty( $options[ self::KEY_WP_HARDENING ] );

		// Set default_ping_status to closed when hardening is on (XMLRPC disabled).
		if ( $isHardening && 'closed' !== Helper::getOption( 'default_ping_status' ) ) {
			Helper::updateOption( 'default_ping_status', 'closed' );
		}

		// Remove readme.html when hardening is on.
		if ( $isHardening ) {
			try {
				( new Readme() )->deleteReadme();
			} catch ( \Exception $e ) {
				Helper::errorLog( 'HDA: Failed to delete readme.html - ' . $e->getMessage() );
			}
		}

		// ── Server config & file lock ────────────────
		self::handleServerConfigBlock( $data, $isHardening );
		self::handleFileLock( $data );

		// ── Delegate to sub-module save handlers ─────
		if ( isset( $data[ AccessControl::SUB_KEY ] ) ) {
			AccessControl::handleSave( (array) $data[ AccessControl::SUB_KEY ] );
		}

		if ( isset( $data[ Firewall::SUB_KEY ] ) ) {
			Firewall::saveSettings( (array) $data[ Firewall::SUB_KEY ] );
		}
	}

	// ── Private helpers for save ─────────────────────

	/**
	 * Handle server config blocks add/remove.
	 */
	private static function handleServerConfigBlock( array $data, bool $isHardening ): void {
		$blocks = [
			[ ! empty( $data[ self::KEY_SERVER_CONFIG ] ), ServerConfig::MARKER, 'htaccess.tpl', 'nginx.conf' ],
			[ $isHardening, ServerConfig::XMLRPC_MARKER, 'xmlrpc-htaccess.tpl', 'xmlrpc-nginx.conf' ],
			[ $isHardening, ServerConfig::OPML_MARKER, 'opml-htaccess.tpl', 'opml-nginx.conf' ],
		];

		foreach ( $blocks as $args ) {
			self::toggleServerBlock( ...$args );
		}
	}

	/**
	 * Toggle a single server config block on or off.
	 */
	private static function toggleServerBlock( bool $enabled, string $marker, string $htaccessTpl, string $nginxTpl ): void {
		try {
			$result = $enabled
				? ServerConfig::addBlock( $marker, $htaccessTpl, $nginxTpl )
				: ServerConfig::removeBlock( $marker );

			if ( is_string( $result ) ) {
				Helper::errorLog( "[HDA] ServerConfig [{$marker}]: " . $result );
			}
		} catch ( \Exception $e ) {
			Helper::errorLog( "[HDA] ServerConfig [{$marker}] error: " . $e->getMessage() );
		}
	}

	/**
	 * Handle file permission lock/unlock.
	 */
	private static function handleFileLock( array $data ): void {
		$enabled = ! empty( $data[ self::KEY_LOCK_FILES ] );

		try {
			$results = $enabled ? ServerConfig::lockFiles() : ServerConfig::unlockFiles();

			foreach ( $results as $label => $result ) {
				if ( is_string( $result ) ) {
					Helper::errorLog( "[HDA] FileLock [{$label}]: " . $result );
				}
			}
		} catch ( \Exception $e ) {
			Helper::errorLog( '[HDA] FileLock error: ' . $e->getMessage() );
		}
	}
}
