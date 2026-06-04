<?php
/**
 * Polylang integration for WooCommerce Filter Presets.
 *
 * Keeps the filter preset CPT private while exposing it to Polylang settings
 * and translating preset-owned metadata when presets are duplicated.
 *
 * @package SPL\Modules\WooCommerce\Filter\Integrations
 */

namespace SPL\Modules\WooCommerce\Filter\Integrations;

use SPL\Core\Helper;
use SPL\Modules\WooCommerce\Filter\FilterMeta;

defined( 'ABSPATH' ) || exit;

final class PolylangIntegration {

	/**
	 * Register Polylang hooks.
	 */
	public static function register(): void {
		add_filter( 'pll_get_post_types', [ self::class, 'postTypes' ], 10, 2 );
		add_filter( 'pll_copy_post_metas', [ self::class, 'copyPostMetas' ], 10, 3 );
		add_filter( 'pll_translate_post_meta', [ self::class, 'translatePostMeta' ], 10, 4 );
	}

	/**
	 * Expose Filter Presets to Polylang without making the CPT public.
	 *
	 * Runtime translation remains controlled by Polylang's saved `post_types`
	 * option. The settings screen always receives the CPT so admins can enable it.
	 *
	 * @param string[] $postTypes  Polylang-managed post types.
	 * @param bool     $isSettings True while rendering Polylang settings.
	 *
	 * @return string[]
	 */
	public static function postTypes( array $postTypes, bool $isSettings ): array {
		if ( $isSettings || self::isPresetTranslationEnabled() ) {
			$postTypes[ FilterMeta::POST_TYPE ] = FilterMeta::POST_TYPE;
		}

		return $postTypes;
	}

	/**
	 * Copy preset meta when creating a preset translation.
	 *
	 * @param string[]    $metas Meta keys to copy.
	 * @param bool        $sync  True when synchronizing existing translations.
	 * @param int         $from  Source post ID.
	 *
	 * @return string[]
	 */
	public static function copyPostMetas( array $metas, bool $sync, int $from ): array {
		if ( FilterMeta::POST_TYPE !== get_post_type( $from ) ) {
			return $metas;
		}

		$keys = $sync
			? [ FilterMeta::LAYOUT, FilterMeta::TRIGGER, FilterMeta::ENABLED ]
			: FilterMeta::allKeys();

		return array_values( array_unique( array_merge( $metas, $keys ) ) );
	}

	/**
	 * Translate term slugs inside `_hd_filter_config` while duplicating a preset.
	 *
	 * @param mixed    $value Meta value.
	 * @param string   $key   Meta key.
	 * @param string   $lang  Target language slug.
	 * @param int      $from  Source post ID.
	 *
	 * @return mixed
	 */
	public static function translatePostMeta( mixed $value, string $key, string $lang, int $from ): mixed {
		if ( FilterMeta::POST_TYPE !== get_post_type( $from ) || FilterMeta::CONFIG !== $key ) {
			return $value;
		}

		return self::translateConfigMetaValue( $value, $lang );
	}

	/**
	 * Translate a preset ID to the current Polylang language when available.
	 *
	 * @param int         $presetId Preset post ID.
	 * @param string|null $lang     Optional target language slug.
	 *
	 * @return int
	 */
	public static function translatePresetId( int $presetId, ?string $lang = null ): int {
		if ( $presetId <= 0 || ! function_exists( 'pll_get_post' ) ) {
			return $presetId;
		}

		if ( function_exists( 'pll_is_translated_post_type' ) && ! \pll_is_translated_post_type( FilterMeta::POST_TYPE ) ) {
			return $presetId;
		}

		$translatedId = $lang
			? \pll_get_post( $presetId, $lang )
			: \pll_get_post( $presetId );

		return $translatedId ? (int) $translatedId : $presetId;
	}

	/**
	 * Check whether Filter Presets are enabled in Polylang settings.
	 */
	private static function isPresetTranslationEnabled(): bool {
		$options = Helper::getOption( 'polylang', [] );
		if ( ! is_array( $options ) ) {
			return false;
		}

		$postTypes = $options['post_types'] ?? [];

		return is_array( $postTypes ) && in_array( FilterMeta::POST_TYPE, $postTypes, true );
	}

	/**
	 * Translate term slug references inside stored preset config.
	 *
	 * @param mixed  $value Stored meta value.
	 * @param string $lang  Target language slug.
	 *
	 * @return mixed
	 */
	private static function translateConfigMetaValue( mixed $value, string $lang ): mixed {
		$returnJson = false;

		if ( is_array( $value ) ) {
			$items = $value;
		} else {
			$items      = json_decode( (string) $value, true );
			$returnJson = true;
		}

		if ( ! is_array( $items ) || empty( $items ) ) {
			return $value;
		}

		foreach ( $items as $index => $item ) {
			if ( is_array( $item ) ) {
				$items[ $index ] = self::translateConfigItemTermSlugs( $item, $lang );
			}
		}

		if ( ! $returnJson ) {
			return $items;
		}

		$encoded = wp_json_encode( $items );

		return false !== $encoded ? $encoded : $value;
	}

	/**
	 * Translate one filter item's excluded term slugs to the target language.
	 *
	 * @param array<string, mixed> $item Preset config item.
	 * @param string               $lang Target language slug.
	 *
	 * @return array<string, mixed>
	 */
	private static function translateConfigItemTermSlugs( array $item, string $lang ): array {
		$taxonomy = sanitize_key( $item['taxonomy'] ?? '' );
		if ( '' === $taxonomy || empty( $item['exclude_terms'] ) ) {
			return $item;
		}

		$terms = $item['exclude_terms'];
		if ( is_string( $terms ) ) {
			$terms = array_filter( array_map( 'trim', explode( ',', $terms ) ) );
		}

		if ( ! is_array( $terms ) ) {
			return $item;
		}

		$item['exclude_terms'] = array_values(
			array_map(
				static fn( string $slug ): string => self::translateTermSlug( $slug, $taxonomy, $lang ),
				array_map( 'strval', $terms )
			)
		);

		return $item;
	}

	/**
	 * Translate a term slug inside the same taxonomy.
	 */
	private static function translateTermSlug( string $slug, string $taxonomy, string $lang ): string {
		if ( '' === $slug || ! taxonomy_exists( $taxonomy ) || ! function_exists( 'pll_get_term' ) ) {
			return $slug;
		}

		if ( function_exists( 'pll_is_translated_taxonomy' ) && ! \pll_is_translated_taxonomy( $taxonomy ) ) {
			return $slug;
		}

		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term instanceof \WP_Term ) {
			return $slug;
		}

		$translatedId = \pll_get_term( $term->term_id, $lang );
		if ( ! $translatedId ) {
			return $slug;
		}

		$translated = get_term( $translatedId, $taxonomy );

		return $translated instanceof \WP_Term ? $translated->slug : $slug;
	}
}
