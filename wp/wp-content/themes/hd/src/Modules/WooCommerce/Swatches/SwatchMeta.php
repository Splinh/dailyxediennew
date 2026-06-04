<?php
/**
 * Swatch Meta — centralized term meta constants and read helpers.
 *
 * Single source of truth for all swatch-related term meta keys.
 * All swatch classes (Admin, SingleSwatches, ArchiveSwatches) reference
 * these constants instead of maintaining private duplicates.
 *
 * @package HD\Modules\WooCommerce\Swatches
 */

namespace HD\Modules\WooCommerce\Swatches;

defined( 'ABSPATH' ) || exit;

final class SwatchMeta {

	// ── Core ─────────────────────────────────────────
	public const TYPE  = '_hd_swatch_type';
	public const COLOR = '_hd_swatch_color';
	public const IMAGE = '_hd_swatch_image';

	// ── Dual Color ───────────────────────────────────
	public const IS_DUAL         = '_hd_swatch_is_dual';
	public const SECONDARY_COLOR = '_hd_swatch_secondary_color';

	// ── Tooltip ──────────────────────────────────────
	public const TOOLTIP_TYPE  = '_hd_swatch_tooltip_type';
	public const TOOLTIP_TEXT  = '_hd_swatch_tooltip_text';
	public const TOOLTIP_IMAGE = '_hd_swatch_tooltip_image';

	/**
	 * All swatch meta keys (for bulk sync/copy operations).
	 *
	 * @return string[]
	 */
	public static function allKeys(): array {
		return [
			self::TYPE,
			self::COLOR,
			self::IMAGE,
			self::IS_DUAL,
			self::SECONDARY_COLOR,
			self::TOOLTIP_TYPE,
			self::TOOLTIP_TEXT,
			self::TOOLTIP_IMAGE,
		];
	}

	/**
	 * Read all swatch data for a term.
	 *
	 * Works best after `update_meta_cache('term', $ids)` — zero extra queries.
	 *
	 * @param int $termId Term ID.
	 *
	 * @return array{type: string, color: string, image: int, is_dual: bool, secondary_color: string, tooltip_type: string, tooltip_text: string, tooltip_image: int}
	 */
	public static function getData( int $termId ): array {
		return [
			'type'            => get_term_meta( $termId, self::TYPE, true ) ?: '',
			'color'           => get_term_meta( $termId, self::COLOR, true ) ?: '',
			'image'           => absint( get_term_meta( $termId, self::IMAGE, true ) ),
			'is_dual'         => (bool) get_term_meta( $termId, self::IS_DUAL, true ),
			'secondary_color' => get_term_meta( $termId, self::SECONDARY_COLOR, true ) ?: '',
			'tooltip_type'    => get_term_meta( $termId, self::TOOLTIP_TYPE, true ) ?: 'text',
			'tooltip_text'    => get_term_meta( $termId, self::TOOLTIP_TEXT, true ) ?: '',
			'tooltip_image'   => absint( get_term_meta( $termId, self::TOOLTIP_IMAGE, true ) ),
		];
	}

	/**
	 * Check if a term has swatch data configured.
	 */
	public static function hasSwatch( int $termId ): bool {
		return (bool) get_term_meta( $termId, self::TYPE, true );
	}
}
