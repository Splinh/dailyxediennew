<?php
/**
 * Excerpt generation prompt builder.
 *
 * @package HDAC\Prompts
 */

namespace HDAC\Prompts;

defined( 'ABSPATH' ) || exit;

final class ExcerptPrompt {

	private const SYSTEM = <<<'INSTRUCTION'
You are an editorial assistant that generates excerpt/summary for online articles and pages.

Goal: Generate a concise excerpt from the provided content and title. The excerpt should accurately summarize the main points of the content.

Requirements:
- Plain text only; no markdown, bullets, numbering, or formatting
- Must accurately reflect the content
- Match the language of the content (e.g., if content is in Vietnamese, excerpt must be in Vietnamese)
- Output ONLY the excerpt text. No preamble, no quotes, no code fences, no follow-up
INSTRUCTION;

	private const STYLE_INSTRUCTIONS = [
		'summary'  => 'Write a 2-3 sentence summary that captures the main points of the content.',
		'seo_meta' => 'Write a compelling meta description optimized for search engines. Must be 120-160 characters. Include relevant keywords naturally.',
		'teaser'   => 'Write an engaging hook/teaser that makes readers want to read more. Create curiosity without revealing everything.',
	];

	/**
	 * Build chat messages for excerpt generation.
	 *
	 * @param array<string, mixed> $context     Post context.
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

		$postId = absint( $context['post_id'] ?? 0 );
		if ( $postId > 0 ) {
			$post = get_post( $postId );
			if ( $post ) {
				if ( $post->post_title ) {
					$parts[] = '<title>' . sanitize_text_field( $post->post_title ) . '</title>';
				}

				$postContent = wp_strip_all_tags( $post->post_content );
				if ( $postContent ) {
					$parts[] = '<content>' . $postContent . '</content>';
				}
			}
		}

		// Fallback: inline content.
		if ( ! $parts ) {
			$title   = sanitize_text_field( $context['title'] ?? '' );
			$content = sanitize_textarea_field( $context['content'] ?? '' );

			if ( $title ) {
				$parts[] = '<title>' . $title . '</title>';
			}
			if ( $content ) {
				$parts[] = '<content>' . wp_strip_all_tags( $content ) . '</content>';
			}
		}

		return implode( "\n\n", $parts ) ?: 'No content provided.';
	}
}
