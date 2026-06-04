<?php

declare( strict_types=1 );

namespace HDMU;

/**
 * Disable plugins based on DISABLED_PLUGINS constant
 *
 * Usage in wp-config.php:
 * define('DISABLED_PLUGINS', ['plugin-folder/plugin-file.php']);
 */
final class PluginDisabler {

	/** @var string[] */
	private static array $disabled = [];

	public static function init(): void {
		self::$disabled = self::getDisabledPlugins();

		if ( empty( self::$disabled ) ) {
			return;
		}

		// Filter active plugins
		add_filter( 'option_active_plugins', self::filterPlugins( ... ) );
		add_filter( 'site_option_active_sitewide_plugins', self::filterNetworkPlugins( ... ) );

		// Show disabled notice in admin
		add_action( 'pre_current_active_plugins', self::markDisabledPlugins( ... ) );
	}

	/**
	 * @return string[]
	 */
	private static function getDisabledPlugins(): array {
		if ( ! defined( 'DISABLED_PLUGINS' ) || empty( \DISABLED_PLUGINS ) ) {
			return [];
		}

		$plugins = \DISABLED_PLUGINS;

		// Support JSON string (legacy)
		if ( is_string( $plugins ) ) {
			try {
				$plugins = json_decode( $plugins, true, 512, JSON_THROW_ON_ERROR );
			} catch ( \JsonException ) {
				return [];
			}
		}

		return is_array( $plugins ) ? array_filter( $plugins, 'is_string' ) : [];
	}

	/**
	 * @param string[] $plugins
	 *
	 * @return string[]
	 */
	private static function filterPlugins( array $plugins ): array {
		return array_values( array_diff( $plugins, self::$disabled ) );
	}

	/**
	 * @param array<string, mixed> $plugins
	 *
	 * @return array<string, mixed>
	 */
	private static function filterNetworkPlugins( array $plugins ): array {
		foreach ( self::$disabled as $plugin ) {
			unset( $plugins[ $plugin ] );
		}

		return $plugins;
	}

	private static function markDisabledPlugins(): void {
		global $wp_list_table;

		if ( ! $wp_list_table?->items ) {
			return;
		}

		foreach ( self::$disabled as $plugin ) {
			if ( ! isset( $wp_list_table->items[ $plugin ] ) ) {
				continue;
			}

			$item                 = &$wp_list_table->items[ $plugin ];
			$item['Name']         = '[Disabled] ' . $item['Name'];
			$item['Description'] .= sprintf(
				'<br><strong style="color:#dc2626">%s</strong>',
				esc_html__( 'Disabled in this environment.', 'hdmu' )
			);
		}
	}
}
