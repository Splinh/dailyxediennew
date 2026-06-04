<?php
/**
 * Long content outline prompt builder.
 *
 * @package HDAC\Prompts
 */

namespace HDAC\Prompts;

defined( 'ABSPATH' ) || exit;

final class LongContentOutlinePrompt {

	/**
	 * Build chat messages for a long-content outline.
	 *
	 * @param array<string, mixed> $context      Post context.
	 * @param string               $action       Preset/action id.
	 * @param string               $customPrompt Custom user instructions.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function build( array $context, string $action, string $customPrompt ): array {
		$style = match ( $action ) {
			'seo_guide'      => 'Plan an SEO-focused guide with search intent coverage, clear topical hierarchy, and practical subtopics.',
			'editorial'      => 'Plan an editorial feature with a strong narrative flow, useful context, and polished section transitions.',
			default          => 'Plan a comprehensive long-form article with complete coverage and a logical reading flow.',
		};

		$system = "You are an expert editor planning long-form Classic Editor articles.

Goal: {$style}

Requirements:
- Return only valid JSON, with no Markdown fences or commentary.
- Use this exact top-level shape: {\"sections\":[{\"heading\":\"...\",\"intent\":\"...\"}]}.
- Create 4 to 8 sections.
- Each heading must be concise and useful as an article subheading.
- Each intent must explain what that section should cover in one or two sentences.
- Match the language of the provided title, content, or instructions.";

		if ( '' !== $customPrompt ) {
			$system .= "\n\nAdditional instructions: " . $customPrompt;
		}

		return [
			[
				'role'    => 'system',
				'content' => $system,
			],
			[
				'role'    => 'user',
				'content' => self::gatherContent( $context ),
			],
		];
	}

	/**
	 * Gather planning context.
	 *
	 * @param array<string, mixed> $context Post context.
	 */
	private static function gatherContent( array $context ): string {
		$parts = [];

		$title = isset( $context['title'] ) ? sanitize_text_field( (string) $context['title'] ) : '';
		if ( '' !== $title ) {
			$parts[] = '<title>' . $title . '</title>';
		}

		$content = isset( $context['content'] ) ? (string) $context['content'] : '';
		if ( '' !== trim( $content ) ) {
			$parts[] = '<current_content>' . wp_strip_all_tags( $content ) . '</current_content>';
		}

		$postId = absint( $context['post_id'] ?? 0 );
		if ( $postId > 0 ) {
			$post = get_post( $postId );
			if ( $post instanceof \WP_Post && '' !== $post->post_title && '' === $title ) {
				$parts[] = '<title>' . sanitize_text_field( $post->post_title ) . '</title>';
			}

			$terms = wp_get_post_terms( $postId, [ 'category', 'post_tag' ], [ 'fields' => 'names' ] );
			if ( ! is_wp_error( $terms ) && $terms ) {
				$parts[] = '<topics>' . implode( ', ', array_map( 'sanitize_text_field', $terms ) ) . '</topics>';
			}
		}

		return implode( "\n\n", $parts ) ?: 'Plan a long article from the user instructions.';
	}
}
