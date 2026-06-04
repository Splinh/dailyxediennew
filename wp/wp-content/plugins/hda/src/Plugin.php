<?php
/**
 * Plugin Class - Main orchestrator for loading modules and third-party integrations.
 *
 * Uses ModuleRegistry for auto-discovery and lifecycle management.
 * Module OFF → boot() NOT called → zero footprint.
 *
 * @author HD
 */

namespace HDAddons;

use HDAddons\Core\ModuleRegistry;
use HDAddons\Core\RateLimitStorage;
use HDAddons\Core\SettingsManager;
use HDAddons\Modules\CustomCode\CustomCss;
use HDAddons\Modules\CustomCode\CustomScript;

use HDAddons\Updater\GitHubUpdater;

\defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** Custom capability for managing HDA settings. */
	public const CAPABILITY = 'hda_manage_options';

	/** Option key to track capability version. */
	public const KEY_CAP_VERSION = 'hda_capability_version';

	// -------------------------------------------------------------

	/**
	 * Boot the plugin - Replaces inline closure in hda.php
	 */
	public static function boot(): void {
		load_plugin_textdomain( 'hda', false, dirname( HDA_PLUGIN_BASENAME ) . '/languages' );

		// ACF requirement check.
		if ( ! Helper::isAcfProActive() ) {
			if ( is_admin() ) {
				add_action(
					'admin_notices',
					static fn() => printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( 'SPL Toolkit requires Advanced Custom Fields Pro plugin. Please install and activate it.', 'hda' )
					)
				);
			}

			return;
		}

		try {
			new self();
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA] ' . $e->getMessage() );

			// Only show detailed error in development mode.
			if ( Helper::development() ) {
				add_action(
					'admin_notices',
					static fn() => printf(
						'<div class="notice notice-error"><p><strong>HDA Error:</strong> %s</p></div>',
						esc_html( $e->getMessage() )
					)
				);
			}
		}
	}

	/**
	 * Initialize the plugin.
	 */
	public function __construct() {
		// Run DB migration (version-gated, skips if already current).
		Migration::init();

		// Register hidden post type for option storage.
		add_action( 'init', Helper::registerStoragePostType( ... ), 0 );

		// Register custom cron schedules (only if not already provided by theme).
		add_filter(
			'cron_schedules',
			static function ( array $schedules ): array {
				$schedules['weekly'] ??= [
					'interval' => 7 * DAY_IN_SECONDS,
					'display'  => __( 'Once Weekly', 'hda' ),
				];

				$schedules['monthly'] ??= [
					'interval' => 30 * DAY_IN_SECONDS,
					'display'  => __( 'Once Monthly', 'hda' ),
				];

				return $schedules;
			}
		);

		add_action( 'hda_daily_cleanup', RateLimitStorage::cleanupDb( ... ) );

		// ── ModuleRegistry: discover + boot enabled modules ──
		$this->bootModules();

		// Classic Editor: prevent duplicate settings registration.
		if ( class_exists( 'Classic_Editor' ) && Helper::isClassicEditorActive() ) {
			remove_action( 'admin_init', [ 'Classic_Editor', 'register_settings' ] );
		}

		if ( is_admin() && ! wp_doing_cron() ) {
			// GitHub auto-update (admin context, including AJAX for update process).
			new GitHubUpdater();

			// Sync capability once per plugin version.
			add_action( 'admin_init', self::maybeAddCapability( ... ) );

			// Emergency bypass warning.
			$this->maybeShowEmergencyBypassNotice();
		}

		// Admin assets.
		add_action( 'admin_enqueue_scripts', $this->adminEnqueueAssets( ... ), 39 );

		// Script tag attribute injection.
		add_filter( 'script_loader_tag', $this->scriptLoaderTag( ... ), 11, 3 );

		// Hook into theme's cache clearing to handle cache plugins.
		add_action( 'hd_clear_all_cache', Helper::clearCachePlugins( ... ) );
	}

	// -------------------------------------------------------------
	// Module Boot (ModuleRegistry pattern)
	// -------------------------------------------------------------

	/**
	 * Discover and boot all enabled modules via ModuleRegistry.
	 *
	 * Replaces the old loadModules() flow:
	 * - No more config.php dependency for boot
	 * - No more manual class resolution
	 * - Auto-discovery from composer classmap
	 * - Module OFF → boot() NOT called → zero footprint
	 */
	private function bootModules(): void {
		$registry = ModuleRegistry::getInstance();
		$registry->discover();
		$registry->bootEnabled();

		// Register AJAX settings handler.
		$settingsManager = new SettingsManager(
			[
				CustomScript::KEY_HEADER,
				CustomScript::KEY_FOOTER,
				CustomScript::KEY_BODY_TOP,
				CustomScript::KEY_BODY_BOTTOM,
				CustomCss::KEY_FORM_CSS,
			]
		);
		$settingsManager->register();
	}

	// -------------------------------------------------------------
	// Capability Management
	// -------------------------------------------------------------

	/**
	 * Add capability to roles.
	 *
	 * Assigns `hda_manage_options` to administrator and editor roles.
	 * Called on activation and when plugin version changes.
	 */
	public static function addCapability(): void {
		$roles = apply_filters( 'hda_manage_roles', [ 'administrator', 'editor' ] );

		foreach ( $roles as $roleName ) {
			$role = get_role( $roleName );
			$role?->add_cap( self::CAPABILITY );
		}
	}

	/**
	 * Remove capability from all roles.
	 *
	 * Called on plugin uninstall.
	 */
	public static function removeCapability(): void {
		$wpRoles = wp_roles();
		$roles   = array_keys( $wpRoles->roles );

		foreach ( $roles as $roleName ) {
			$role = get_role( $roleName );
			if ( $role?->has_cap( self::CAPABILITY ) ) {
				$role->remove_cap( self::CAPABILITY );
			}
		}
	}

	/**
	 * Add capability once per plugin version (not every admin_init).
	 */
	private static function maybeAddCapability(): void {
		$storedVersion = Helper::getOption( self::KEY_CAP_VERSION, '' );

		if ( $storedVersion === HDA_VERSION ) {
			return;
		}

		self::addCapability();
		Helper::updateOption( self::KEY_CAP_VERSION, HDA_VERSION );
	}

	// -------------------------------------------------------------

	/**
	 * Show admin notice when emergency login security bypass is active.
	 */
	private function maybeShowEmergencyBypassNotice(): void {
		$bypassOtp      = defined( 'HDA_DISABLE_OTP' ) && \HDA_DISABLE_OTP;
		$bypassSecurity = defined( 'HDA_DISABLE_LOGIN_SECURITY' ) && \HDA_DISABLE_LOGIN_SECURITY;
		$bypassCaptcha  = defined( 'HDA_DISABLE_LOGIN_CAPTCHA' ) && \HDA_DISABLE_LOGIN_CAPTCHA;
		$bypassFirewall = defined( 'HDA_DISABLE_FIREWALL' ) && \HDA_DISABLE_FIREWALL;

		if ( ! $bypassOtp && ! $bypassSecurity && ! $bypassCaptcha && ! $bypassFirewall ) {
			return;
		}

		add_action(
			'admin_notices',
			static function () use ( $bypassOtp, $bypassSecurity, $bypassCaptcha, $bypassFirewall ) {
				$messages = [];

				if ( $bypassSecurity ) {
					$messages[] = __( '<code>HDA_DISABLE_LOGIN_SECURITY</code> is active - ALL login security features are bypassed!', 'hda' );
				} elseif ( $bypassOtp ) {
					$messages[] = __( '<code>HDA_DISABLE_OTP</code> is active - OTP verification is bypassed!', 'hda' );
				}

				if ( $bypassCaptcha ) {
					$messages[] = __( '<code>HDA_DISABLE_LOGIN_CAPTCHA</code> is active - Login CAPTCHA is bypassed!', 'hda' );
				}

				if ( $bypassFirewall ) {
					$messages[] = __( '<code>HDA_DISABLE_FIREWALL</code> is active - Firewall is bypassed!', 'hda' );
				}

				echo '<div class="notice notice-error" style="border-left-color:#dc3232;">';
				echo '<p><strong>⚠️ ' . esc_html__( 'HDA Security Warning:', 'hda' ) . '</strong></p>';
				foreach ( $messages as $msg ) {
					echo '<p>' . wp_kses( $msg, [ 'code' => [] ] ) . '</p>';
				}
				echo '<p>' . esc_html__( 'Remember to remove these settings from your .env file after recovery!', 'hda' ) . '</p>';
				echo '</div>';
			}
		);
	}

	// -------------------------------------------------------------

	/**
	 * Inject extra attributes (defer, module, etc.) to script tags.
	 *
	 * @param string $tag    The script tag HTML.
	 * @param string $handle The script handle.
	 * @param string $src    The script source URL.
	 *
	 * @return string Modified script tag.
	 */
	public function scriptLoaderTag( string $tag, string $handle, string $src ): string {
		$scripts = wp_scripts();
		$reg     = $scripts->registered[ $handle ] ?? null;

		if ( ! $reg || empty( $reg->extra['hda'] ) ) {
			return $tag;
		}

		$extras = is_array( $reg->extra['hda'] )
			? $reg->extra['hda']
			: explode( ' ', (string) $reg->extra['hda'] );

		foreach ( $extras as $attr ) {
			$attr = trim( $attr );
			if ( empty( $attr ) ) {
				continue;
			}

			if ( 'module' === $attr ) {
				if ( ! str_contains( $tag, 'type=' ) ) {
					$tag = str_replace( ' src=', ' type="module" src=', $tag );
				}
			} elseif ( ! preg_match( "#\\s{$attr}(=|>|\\s|$)#", $tag ) ) {
				$tag = str_replace( ' src=', " {$attr} src=", $tag );
			}
		}

		return $tag;
	}

	// -------------------------------------------------------------

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function adminEnqueueAssets( string $hook ): void {
		// Global admin assets (CSS extracted from JS by Vite).
		Asset::enqueueJS( 'admin-core.js', [ 'jquery-core' ], null, true, [ 'module', 'defer' ] );

		// Addon settings pages only.
		$allowed_pages = [
			'toplevel_page_hda-settings',
			'hda_page_hda-file-integrity',
		];

		if ( ! in_array( $hook, $allowed_pages, true ) ) {
			return;
		}

		// Color picker.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		Asset::enqueueJS( 'settings.js', [ 'wp-color-picker', 'jquery-ui-sortable' ], null, true, [ 'module', 'defer' ] );

		// CodeMirror.
		wp_enqueue_style( 'wp-codemirror' );

		$hdaHandle = Asset::handle( 'settings.js' );
		if ( $hdaHandle ) {
			$l10n = [
				'codemirror_css'  => wp_enqueue_code_editor( [ 'type' => 'text/css' ] ),
				'codemirror_html' => wp_enqueue_code_editor( [ 'type' => 'text/html' ] ),
			];
			Asset::localize( $hdaHandle, 'codemirror_settings', $l10n );
		}
	}
}
