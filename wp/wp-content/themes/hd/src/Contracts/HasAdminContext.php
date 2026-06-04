<?php
/**
 * HasAdminContext — optional interface for Features or Modules with admin-only hooks.
 *
 * When a Bootable/ModuleInterface class also implements this interface,
 * Bootstrap/ModuleRegistry will call adminBoot() only when is_admin() is true.
 *
 * @package HD\Contracts
 */

namespace HD\Contracts;

defined( 'ABSPATH' ) || exit;

interface HasAdminContext {
	/** Admin-only hooks. Called only when is_admin() is true. */
	public function adminBoot(): void;
}
