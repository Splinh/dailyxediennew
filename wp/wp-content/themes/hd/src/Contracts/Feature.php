<?php
/**
 * Abstract Feature — Base class for native theme features.
 *
 * Features are THEME INFRASTRUCTURE:
 * - Do NOT depend on any external plugin.
 * - Always loaded — cannot be toggled off.
 * - Theme degrades or breaks without them.
 *
 * Examples: optimization, customizer, admin UI, shortcodes, template hooks.
 *
 * Every class in `src/Features/` MUST extend this class.
 * Features are booted explicitly via Bootstrap::FEATURES (order matters).
 * Unlike modules, features are not auto-discovered and do not expose REST API
 * controller classes through ModuleRegistry.
 *
 * @see \HD\Contracts\ModuleInterface for plugin integrations / optional features.
 *
 * @package HD\Contracts
 * @author  HD
 */

namespace HD\Contracts;

defined( 'ABSPATH' ) || exit;

abstract class Feature implements Bootable {

	/**
	 * Register WordPress hooks for this feature.
	 *
	 * Called once during Bootstrap for every feature in the FEATURES array.
	 * This is the ONLY entry point — all hook registration must happen here.
	 *
	 * @return void
	 */
	abstract public function boot(): void;
}
