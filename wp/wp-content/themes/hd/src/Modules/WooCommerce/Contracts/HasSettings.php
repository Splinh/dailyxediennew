<?php
/**
 * HasSettings — optional contract for features with their own settings.
 *
 * defaults() values are auto-merged into WooCommerceModule::defaults().
 * settingsFields() defines admin UI schema (Phase 4+).
 *
 * @package HD\Modules\WooCommerce\Contracts
 */

namespace HD\Modules\WooCommerce\Contracts;

defined( 'ABSPATH' ) || exit;

interface HasSettings {
	/** @return array<string, array> Settings field definitions for admin UI. */
	public static function settingsFields(): array;

	/**
	 * Default values merged into WooCommerceModule::defaults() automatically.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array;
}
