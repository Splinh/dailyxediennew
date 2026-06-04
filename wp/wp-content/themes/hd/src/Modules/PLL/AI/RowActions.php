<?php
/**
 * AI translation row actions.
 *
 * @package HD\Modules\PLL\AI
 */

namespace HD\Modules\PLL\AI;

defined( 'ABSPATH' ) || exit;

final class RowActions {

	public function register(): void {
		add_filter( 'post_row_actions', [ $this, 'postActions' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'postActions' ], 10, 2 );
		add_filter( 'tag_row_actions', [ $this, 'termActions' ], 10, 2 );
	}

	/**
	 * @param array<string, string> $actions Row actions.
	 *
	 * @return array<string, string>
	 */
	public function postActions( array $actions, \WP_Post $post ): array {
		if ( ! AiClient::isAvailable() || ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		if ( ! function_exists( 'pll_is_translated_post_type' ) || ! pll_is_translated_post_type( $post->post_type ) ) {
			return $actions;
		}

		if ( 'trash' === $post->post_status ) {
			return $actions;
		}

		foreach ( $this->missingPostLanguages( $post->ID ) as $lang ) {
			$actions[ 'hd_pll_ai_' . $lang ] = sprintf(
				'<a href="#" class="hd-pll-ai-action" data-type="%s" data-source-id="%d" data-target-lang="%s">%s</a>',
				esc_attr( $post->post_type ),
				$post->ID,
				esc_attr( $lang ),
				esc_html( strtoupper( $lang ) )
			);
		}

		return $actions;
	}

	/**
	 * @param array<string, string> $actions Row actions.
	 *
	 * @return array<string, string>
	 */
	public function termActions( array $actions, \WP_Term $term ): array {
		if ( ! AiClient::isAvailable() || ! current_user_can( 'manage_options' ) || ! current_user_can( 'manage_categories' ) ) {
			return $actions;
		}

		foreach ( $this->missingTermLanguages( $term->term_id ) as $lang ) {
			$actions[ 'hd_pll_ai_' . $lang ] = sprintf(
				'<a href="#" class="hd-pll-ai-action" data-type="term" data-source-id="%d" data-target-lang="%s">%s</a>',
				$term->term_id,
				esc_attr( $lang ),
				esc_html( strtoupper( $lang ) )
			);
		}

		return $actions;
	}

	/**
	 * @return string[]
	 */
	private function missingPostLanguages( int $postId ): array {
		$languages = function_exists( 'pll_languages_list' ) ? pll_languages_list( [ 'fields' => 'slug' ] ) : [];

		return array_values(
			array_filter(
				(array) $languages,
				static fn( string $lang ): bool => ! \pll_get_post( $postId, $lang )
			)
		);
	}

	/**
	 * @return string[]
	 */
	private function missingTermLanguages( int $termId ): array {
		$languages = function_exists( 'pll_languages_list' ) ? pll_languages_list( [ 'fields' => 'slug' ] ) : [];

		return array_values(
			array_filter(
				(array) $languages,
				static fn( string $lang ): bool => ! \pll_get_term( $termId, $lang )
			)
		);
	}
}
