<?php
/**
 * Swatch Config — read-only accessor for swatch settings.
 *
 * Thin facade over WooCommerceModule::getCachedOptions() — returns
 * typed values with fallback to defaults. Avoids scattering
 * raw option lookups across Frontend/Admin classes.
 *
 * All keys are prefixed with `swatch_` and stored in the shared
 * `hd_woocommerce` wp_option (alongside gallery_*, quick_view, etc.).
 *
 * @package HD\Modules\WooCommerce\Swatches
 */

namespace HD\Modules\WooCommerce\Swatches;

use HD\Modules\WooCommerce\WooCommerceModule;

defined( 'ABSPATH' ) || exit;

final class SwatchConfig {

	/** @var array<string, mixed>|null Cached options for current request. */
	private static ?array $cache = null;

	/** @var array<string, mixed>|null Cached defaults. */
	private static ?array $defaults = null;

	/**
	 * Get a swatch setting value.
	 *
	 * @param string $key      Setting key WITHOUT prefix (e.g. 'shape_style').
	 * @param mixed  $fallback Fallback if not set.
	 *
	 * @return mixed
	 */
	public static function get( string $key, mixed $fallback = null ): mixed {
		$options  = self::all();
		$fullKey  = 'swatch_' . $key;
		$defaults = self::$defaults ??= SwatchSettings::defaults();

		return $options[ $fullKey ] ?? $fallback ?? $defaults[ $fullKey ] ?? null;
	}

	/**
	 * Get all cached WooCommerce options.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		return self::$cache ??= WooCommerceModule::getCachedOptions();
	}

	/**
	 * Typed getters for common settings.
	 */

	public static function shapeStyle(): string {
		return (string) self::get( 'shape_style', 'squared' );
	}

	public static function disabledStyle(): string {
		return (string) self::get( 'disabled_style', 'blur' );
	}

	public static function defaultToButton(): bool {
		return (bool) self::get( 'default_to_button' );
	}

	public static function defaultToImage(): bool {
		return (bool) self::get( 'default_to_image' );
	}

	public static function clearOnReselect(): bool {
		return (bool) self::get( 'clear_on_reselect' );
	}

	public static function showSelectedLabel(): bool {
		return (bool) self::get( 'show_selected_label' );
	}

	public static function labelSeparator(): string {
		return (string) self::get( 'label_separator', ':' );
	}

	public static function displayLimit(): int {
		return absint( self::get( 'display_limit', 0 ) );
	}

	public static function tooltipEnabled(): bool {
		return (bool) self::get( 'tooltip' );
	}

	public static function archivePosition(): string {
		return (string) self::get( 'archive_position', 'after' );
	}

	public static function archiveLimit(): int {
		return absint( self::get( 'archive_limit', 5 ) );
	}

	public static function showStockInfo(): bool {
		return (bool) self::get( 'show_stock_info' );
	}

	public static function stockThreshold(): int {
		return absint( self::get( 'stock_threshold', 5 ) );
	}

	public static function linkableUrl(): bool {
		return (bool) self::get( 'linkable_url' );
	}

	public static function imagePreview(): bool {
		return (bool) self::get( 'image_preview' );
	}

	/**
	 * Reset internal cache (useful after settings save).
	 */
	public static function reset(): void {
		self::$cache    = null;
		self::$defaults = null;
	}
}
