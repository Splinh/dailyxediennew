<?php
/**
 * Meta translation helper.
 *
 * @package HD\Modules\PLL\AI\Translator
 */

namespace HD\Modules\PLL\AI\Translator;

use HD\Modules\PLL\AI\TranslationUnit;
use HD\Modules\PLL\AI\TranslationEngine;

defined( 'ABSPATH' ) || exit;

final class MetaTranslator {

	public const DEFAULT_KEYS = [
		'rank_math_title',
		'rank_math_description',
		'rank_math_focus_keyword',
		'_yoast_wpseo_title',
		'_yoast_wpseo_metadesc',
		'_yoast_wpseo_focuskw',
	];

	public function __construct( private readonly TranslationEngine $engine = new TranslationEngine() ) {}

	/**
	 * @return array<string, string>|\WP_Error
	 */
	public function previewPostMeta( int $postId, string $sourceLang, string $targetLang, array $keys = [] ): array|\WP_Error {
		$keys  = ! empty( $keys ) ? array_map( 'sanitize_key', $keys ) : self::DEFAULT_KEYS;
		$units = [];

		foreach ( $keys as $key ) {
			$value = get_post_meta( $postId, $key, true );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$units[] = new TranslationUnit( 'meta_' . sanitize_key( $key ), $value, 'post meta ' . $key, 'seo', [], [ 'post_meta', $key ] );
			}
		}

		$results = $this->engine->translateUnits( $units, $sourceLang, $targetLang );
		if ( is_wp_error( $results ) ) {
			return $results;
		}

		$meta = [];
		foreach ( $results as $result ) {
			$meta[ substr( $result->unit_id, 5 ) ] = $result->translated;
		}

		return $meta;
	}
}
