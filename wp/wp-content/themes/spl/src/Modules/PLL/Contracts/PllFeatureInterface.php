<?php
/**
 * PllFeature Interface — required contract for PLL sub-features.
 *
 * Mirrors WooFeatureInterface pattern:
 * - slug() is STATIC → check settings WITHOUT instantiation.
 * - Disabled features = zero footprint (no new, no register).
 *
 * @package SPL\Modules\PLL\Contracts
 */

namespace SPL\Modules\PLL\Contracts;

defined( 'ABSPATH' ) || exit;

interface PllFeatureInterface {
	/**
	 * Unique slug used as key in settings.
	 * STATIC — check settings BEFORE instantiation.
	 * Disabled features = zero footprint.
	 */
	public static function slug(): string;

	/** Register hooks, filters, actions. Called only when feature is enabled. */
	public function register(): void;
}
