<?php
/**
 * WooFeature Interface — required contract for all WooCommerce sub-features.
 *
 * Each sub-feature is a self-contained mini-module.
 * slug() is STATIC — allows checking settings WITHOUT instantiation.
 * Disabled features = zero footprint (no new, no register).
 *
 * @package SPL\Modules\WooCommerce\Contracts
 */

namespace SPL\Modules\WooCommerce\Contracts;

defined( 'ABSPATH' ) || exit;

interface WooFeatureInterface {
	/**
	 * Unique slug used as key in settings.
	 * STATIC — check settings BEFORE instantiation.
	 * Disabled features = zero footprint.
	 */
	public static function slug(): string;

	/** Register hooks, filters, actions. Called only when feature is enabled. */
	public function register(): void;
}
