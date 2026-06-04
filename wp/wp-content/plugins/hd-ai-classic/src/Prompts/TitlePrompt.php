<?php
/**
 * Title generation prompt builder.
 *
 * @package HDAC\Prompts
 */

namespace HDAC\Prompts;

defined( 'ABSPATH' ) || exit;

final class TitlePrompt {

	private const SYSTEM = <<<'INSTRUCTION'
You are an editorial assistant that generates title suggestions for online articles and pages.

Goal: Generate a concise, engaging, and accurate title for the provided content. The title should be optimized for clarity, engagement, and SEO while maintaining an appropriate tone.

Requirements:
- Be no more than 80 characters
- Plain text only; no markdown, bullets, numbering, or formatting
- Must reflect the actual content, not generic clickbait
- Match the language of the content (e.g., if content is in Vietnamese, title must be in Vietnamese)
- Output ONLY the title text. No preamble, no quotes, no code fences, no follow-up
INSTRUCTION;

	private const STYLE_INSTRUCTIONS = [
		'seo_friendly' => 'Optimize the title for search engines. Include relevant keywords naturally.',
		'engaging'     => 'Make the title attention-grabbing and curiosity-inducing. Use power words.',
		'question'     => 'Frame the title as a compelling question that the content answers.',
		'formal'       => 'Use a professional, authoritative tone suitable for business or academic contexts.',
	];

	/**
	 * Build chat messages for title generation.
	 *
	 * @param array<string, mixed> $context     Post context (post_id, content, etc.).
	 * @param string               $presetId    Preset style ID.
	 * @param string               $customPrompt Custom user instructions.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function build( array $context, string $presetId, string $customPrompt ): array {
		$system = self::SYSTEM;

		// Add style instruction.
		if ( isset( self::STYLE_INSTRUCTIONS[ $presetId ] ) ) {
			$system .= "\n\nStyle: " . self::STYLE_INSTRUCTIONS[ $presetId ];
		}

		// Add custom instructions.
		if ( '' !== $customPrompt ) {
			$system .= "\n\nAdditional instructions: " . $customPrompt;
		}

		// Build user content.
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

	/**
	 * Gather content from context.
	 */
	private static function gatherContent( array $context ): string {
		$parts = [];

		$postId = absint( $context['post_id'] ?? 0 );
		if ( $postId > 0 ) {
			$post = get_post( $postId );
			if ( $post ) {
				$postContent = wp_strip_all_tags( $post->post_content );
				if ( $postContent ) {
					$parts[] = '<content>' . $postContent . '</content>';
				}

				// Include categories/tags as context.
				$terms = wp_get_post_terms( $postId, [ 'category', 'post_tag' ], [ 'fields' => 'names' ] );
				if ( ! is_wp_error( $terms ) && $terms ) {
					$parts[] = '<categories>' . implode( ', ', $terms ) . '</categories>';
				}
			}
		}

		// Fallback: use inline content if provided.
		$inlineContent = sanitize_textarea_field( $context['content'] ?? '' );
		if ( ! $parts && $inlineContent ) {
			$parts[] = '<content>' . wp_strip_all_tags( $inlineContent ) . '</content>';
		}

		return implode( "\n\n", $parts ) ?: 'No content provided.';
	}
}
