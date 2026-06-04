<?php
/**
 * @package HDAT\Modules
 */

declare(strict_types=1);

namespace HDAT\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Zero-footprint module contract.
 *
 * boot() is the ONLY entry point. Module disabled → boot() not called →
 * no hooks, no memory, no behavior change.
 */
interface ModuleInterface {

	public static function slug(): string;

	public static function title(): string;

	public static function description(): string;

	public static function alwaysActive(): bool;

	public function boot(): void;
}
