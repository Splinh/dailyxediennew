<?php
/**
 * Translation Settings — manages which themes/plugins/domains to scan.
 *
 * @package SPL\Modules\PLL\Translation
 */

namespace SPL\Modules\PLL\Translation;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class Settings {

	private const OPTION_KEY = 'hd_pll_translation_settings';

	/** @var array|null In-memory cache. */
	private static ?array $cache = null;

	/**
	 * Get settings with defaults.
	 *
	 * @return array{themes: string[], plugins: string[], domains: string[], additional_domains: string[]}
	 */
	public static function get(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$settings = Helper::getOption( self::OPTION_KEY, [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		self::$cache = wp_parse_args(
			$settings,
			[
				'themes'             => [],
				'plugins'            => [],
				'domains'            => [ 'default' ],
				'additional_domains' => [],
			]
		);

		return self::$cache;
	}

	/**
	 * Save settings.
	 *
	 * @param array $data Raw settings data.
	 */
	public static function save( array $data ): void {
		$settings = [
			'themes'             => array_map( 'sanitize_text_field', $data['themes'] ?? [] ),
			'plugins'            => array_map( 'sanitize_text_field', $data['plugins'] ?? [] ),
			'domains'            => array_map( 'sanitize_text_field', $data['domains'] ?? [ 'default' ] ),
			'additional_domains' => array_map( 'sanitize_text_field', $data['additional_domains'] ?? [] ),
		];

		Helper::updateOption( self::OPTION_KEY, $settings );
		self::$cache = null;

		// Bust scanner transient cache so next admin load re-scans with new selections.
		Scanner::clearCache();
	}

	/**
	 * Get option key.
	 */
	public static function optionKey(): string {
		return self::OPTION_KEY;
	}
}
