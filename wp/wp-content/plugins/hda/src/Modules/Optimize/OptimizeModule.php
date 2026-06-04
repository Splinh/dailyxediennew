<?php
/**
 * Optimize Module — Heartbeat, embeds, wp_head cleanup, and database optimization.
 *
 * @package HDAddons\Modules\Optimize
 */

namespace HDAddons\Modules\Optimize;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class OptimizeModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'optimize';
	}

	public static function title(): string {
		return 'Optimize';
	}

	public static function description(): string {
		return 'Heartbeat, embeds, wp_head, and DB cleanup.';
	}

	public static function group(): string {
		return 'performance';
	}

	// ── Option Keys ─────────────────────────────────

	public const KEY_HEARTBEAT    = 'heartbeat_preset';
	public const KEY_CORE_CLEANUP = 'core_cleanup';

	public static function defaults(): array {
		return [
			self::KEY_HEARTBEAT    => 'default',
			self::KEY_CORE_CLEANUP => false,
		];
	}

	public static function cronHooks(): array {
		return [ 'hda_db_optimizer_cleanup' ];
	}

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		new Performance();
		new DatabaseOptimizer();
	}

	// ── HasSettings ─────────────────────────────────


	public static function saveSettings( array $data ): void {
		$options = [];

		$options[ self::KEY_HEARTBEAT ] = isset( $data[ self::KEY_HEARTBEAT ] )
			? sanitize_key( $data[ self::KEY_HEARTBEAT ] )
			: 'default';

		$options[ self::KEY_CORE_CLEANUP ] = ! empty( $data[ self::KEY_CORE_CLEANUP ] );

		self::saveOrRemove( self::optionKey(), $options );

		// DatabaseOptimizer sub-module.
		if ( isset( $data[ DatabaseOptimizer::SUB_KEY ] ) ) {
			DatabaseOptimizer::saveSettings( (array) $data[ DatabaseOptimizer::SUB_KEY ] );
		}
	}
}
