<?php
/**
 * Swatches Manager — sub-feature entry point.
 *
 * Orchestrates all swatch sub-components:
 * - Admin: term meta form, column preview, assets
 * - Frontend: single product swatches, archive compact swatches
 *
 * Implements WooFeatureInterface + HasSettings for zero-footprint
 * conditional loading and auto-generated settings tab.
 *
 * Settings definitions delegated to SwatchSettings (SRP).
 *
 * @package SPL\Modules\WooCommerce\Swatches
 */

namespace SPL\Modules\WooCommerce\Swatches;

use SPL\Modules\WooCommerce\Contracts\HasSettings;
use SPL\Modules\WooCommerce\Contracts\WooFeatureInterface;

defined( 'ABSPATH' ) || exit;

final class SwatchesManager implements WooFeatureInterface, HasSettings {

	public static function slug(): string {
		return 'swatches';
	}

	public function register(): void {
		// Conflict guard: skip all hooks if legacy plugin is active (prevent double-render).
		// Settings tab in WCSettings still renders (reads HasSettings directly).
		// SwatchesAdmin (term meta form) intentionally skipped — plugin manages its own.
		if ( class_exists( 'Woo_Variation_Swatches', false ) ) {
			return;
		}

		if ( is_admin() ) {
			( new Admin\SwatchesAdmin() )->register();
			( new Admin\ProductSwatchesTab() )->register();
		}

		( new Frontend\SingleSwatches() )->register();
		( new Frontend\ArchiveSwatches() )->register();

		// Register swatch meta keys for Polylang term translation sync.
		// Standard PLL filter — no import needed. If Polylang is inactive, this filter never fires.
		add_filter( 'pll_copy_term_metas', [ self::class, 'addPllTermMetas' ] );
	}

	/**
	 * Add swatch meta keys to Polylang term duplication list.
	 *
	 * @param array<string> $keys Meta keys to copy.
	 *
	 * @return array<string>
	 */
	public static function addPllTermMetas( array $keys ): array {
		return array_merge( $keys, SwatchMeta::allKeys() );
	}

	/* ---------- HasSettings (delegates to SwatchSettings) ---------- */

	public static function settingsFields(): array {
		return SwatchSettings::settingsFields();
	}

	public static function defaults(): array {
		return SwatchSettings::defaults();
	}
}
