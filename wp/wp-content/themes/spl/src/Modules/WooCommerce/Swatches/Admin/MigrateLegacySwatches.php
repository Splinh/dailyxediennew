<?php
/**
 * Migrate Legacy Swatches — one-time migration from plugin meta to HD theme meta.
 *
 * Converts woo-variation-swatches plugin term meta keys to HD theme format:
 *   product_attribute_color  → _hd_swatch_type + _hd_swatch_color
 *   product_attribute_image  → _hd_swatch_type + _hd_swatch_image
 *   is_dual_color            → _hd_swatch_is_dual
 *   secondary_color          → _hd_swatch_secondary_color
 *
 * Usage:
 *   vendor/bin/wp eval "HD\Modules\WooCommerce\Swatches\Admin\MigrateLegacySwatches::run();"
 *
 * Safe to run multiple times — skips terms that already have HD swatch data.
 *
 * @package SPL\Modules\WooCommerce\Swatches\Admin
 */

namespace SPL\Modules\WooCommerce\Swatches\Admin;

use SPL\Core\DB;
use SPL\Modules\WooCommerce\Swatches\SwatchMeta;

defined( 'ABSPATH' ) || exit;

final class MigrateLegacySwatches {

	/** Plugin meta keys to migrate. */
	private const LEGACY_KEYS = [
		'product_attribute_color',
		'product_attribute_image',
		'is_dual_color',
		'secondary_color',
	];

	/**
	 * Run the migration.
	 *
	 * @return void
	 */
	public static function run(): void {
		$db = DB::db();

		// 1. Fetch all plugin term meta in one query
		$placeholders = implode( ',', array_fill( 0, count( self::LEGACY_KEYS ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $db->get_results(
			$db->prepare(
				"SELECT term_id, meta_key, meta_value FROM {$db->termmeta} WHERE meta_key IN ({$placeholders})",
				...self::LEGACY_KEYS
			)
		);

		if ( empty( $rows ) ) {
			echo "No legacy swatch data found.\n";
			return;
		}

		// 2. Group by term_id
		$grouped = [];
		foreach ( $rows as $row ) {
			$grouped[ $row->term_id ][ $row->meta_key ] = $row->meta_value;
		}

		$migrated = 0;
		$skipped  = 0;
		$diagnostics = [];

		foreach ( $grouped as $termId => $meta ) {
			// Skip if HD meta already exists
			if ( SwatchMeta::hasSwatch( (int) $termId ) ) {
				++$skipped;
				continue;
			}

			// Determine type
			$type = '';
			if ( ! empty( $meta['product_attribute_color'] ) ) {
				$type = 'color';
			} elseif ( ! empty( $meta['product_attribute_image'] ) ) {
				$type = 'image';
			}

			if ( ! $type ) {
				++$skipped;
				$diagnostics[] = "Term {$termId}: no legacy color/image value.";
				continue;
			}

			if ( 'color' === $type ) {
				$color = sanitize_hex_color( $meta['product_attribute_color'] ?? '' );
				if ( ! $color ) {
					++$skipped;
					$diagnostics[] = "Term {$termId}: invalid primary color; dual-color migration skipped.";
					continue;
				}

				update_term_meta( $termId, SwatchMeta::TYPE, $type );
				update_term_meta( $termId, SwatchMeta::COLOR, $color );

				if ( ! empty( $meta['is_dual_color'] ) ) {
					update_term_meta( $termId, SwatchMeta::IS_DUAL, '1' );
					$secondary = sanitize_hex_color( $meta['secondary_color'] ?? '' );
					if ( $secondary ) {
						update_term_meta( $termId, SwatchMeta::SECONDARY_COLOR, $secondary );
					}
				}
			}

			if ( 'image' === $type ) {
				$imageId = absint( $meta['product_attribute_image'] ?? 0 );
				if ( ! $imageId ) {
					++$skipped;
					$diagnostics[] = "Term {$termId}: invalid legacy image ID.";
					continue;
				}

				update_term_meta( $termId, SwatchMeta::TYPE, $type );
				update_term_meta( $termId, SwatchMeta::IMAGE, $imageId );
			}

			++$migrated;
		}

		echo "Migration complete: {$migrated} terms migrated, {$skipped} skipped.\n";
		foreach ( $diagnostics as $message ) {
			echo "Skipped: {$message}\n";
		}
	}
}
