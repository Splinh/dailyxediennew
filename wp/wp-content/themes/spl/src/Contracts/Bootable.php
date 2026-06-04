<?php
/**
 * Bootable contract — used by Features (native theme services).
 *
 * Any class implementing this interface will have its boot() method called
 * during theme initialization to register WordPress hooks.
 *
 * @package SPL\Contracts
 */

namespace SPL\Contracts;

defined( 'ABSPATH' ) || exit;

interface Bootable {
	/** Register WordPress hooks. */
	public function boot(): void;
}
