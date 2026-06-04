<?php
/**
 * Term/category description prompt builder.
 *
 * @package HDAC\Prompts
 */

namespace HDAC\Prompts;

defined( 'ABSPATH' ) || exit;

final class TermDescriptionPrompt {

	private const SYSTEM = <<<'INSTRUCTION'
You are an editorial assistant that generates descriptions for taxonomy terms (categories, tags, etc.) on a WordPress website.

Goal: Generate a concise, informative description for the given taxonomy term that helps readers and search engines understand what content belongs in this category.

Requirements:
- Plain text only; no markdown, bullets, numbering, or HTML formatting
- Must be relevant to the term name and its context within the taxonomy
- Match the language of the term name and site context
- Output ONLY the description text. No preamble, no quotes, no code fences, no follow-up
INSTRUCTION;

	private const STYLE_INSTRUCTIONS = [
		'seo_category' => 'Write an SEO-optimized category description (100-200 characters). Include the category name and relevant keywords naturally.',
		'brief'        => 'Write a brief 1-2 sentence summary of what this category covers.',
		'detailed'     => 'Write a detailed overview (3-4 sentences) explaining the scope, topics, and value of content in this category.',
	];

	/**
	 * Build chat messages for term description generation.
	 *
	 * @param array<string, mixed> $context     Term context (term_id, taxonomy, term_name, etc.).
	 * @param string               $presetId    Preset style ID.
	 * @param string               $customPrompt Custom instructions.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function build( array $context, string $presetId, string $customPrompt ): array {
		$system = self::SYSTEM;

		if ( isset( self::STYLE_INSTRUCTIONS[ $presetId ] ) ) {
			$system .= "\n\nStyle: " . self::STYLE_INSTRUCTIONS[ $presetId ];
		}

		if ( '' !== $customPrompt ) {
			$system .= "\n\nAdditional instructions: " . $customPrompt;
		}

		$content = self::gatherContent( $context );

		return [
			[
				'role'    => 'system',
				'content' => $system,
			],
			[
				'role'    => 'user',
				'content' => $content,
			],
		];
	}

	private static function gatherContent( array $context ): string {
		$parts = [];

		$termName = sanitize_text_field( $context['term_name'] ?? '' );
		$taxonomy = sanitize_text_field( $context['taxonomy'] ?? 'category' );
		$termId   = absint( $context['term_id'] ?? 0 );

		if ( $termName ) {
			$parts[] = '<term-name>' . $termName . '</term-name>';
		}

		// Get taxonomy label.
		$taxObject = get_taxonomy( $taxonomy );
		if ( $taxObject ) {
			$parts[] = '<taxonomy>' . $taxObject->labels->singular_name . '</taxonomy>';
		}

		// Get parent term if exists.
		if ( $termId > 0 ) {
			$term = get_term( $termId, $taxonomy );
			if ( $term && ! is_wp_error( $term ) && $term->parent > 0 ) {
				$parent = get_term( $term->parent, $taxonomy );
				if ( $parent && ! is_wp_error( $parent ) ) {
					$parts[] = '<parent-term>' . $parent->name . '</parent-term>';
				}
			}

			// Get sample post titles in this term (up to 5).
			$posts = get_posts(
				[
					'tax_query'      => [
						[
							'taxonomy' => $taxonomy,
							'terms'    => $termId,
						],
					],
					'posts_per_page' => 5,
					'fields'         => 'ids',
					'post_status'    => 'publish',
				]
			);

			if ( $posts ) {
				$titles  = array_map( static fn( int $id ) => get_the_title( $id ), $posts );
				$parts[] = '<sample-posts>' . implode( ', ', array_filter( $titles ) ) . '</sample-posts>';
			}
		}

		// Site name for context.
		$siteName = get_bloginfo( 'name' );
		if ( $siteName ) {
			$parts[] = '<site-name>' . $siteName . '</site-name>';
		}

		return implode( "\n", $parts ) ?: 'No term information provided.';
	}
}
