<?php
/**
 * Content generation and resizing prompt builder.
 *
 * @package HDAC\Prompts
 */

namespace HDAC\Prompts;

defined( 'ABSPATH' ) || exit;

use HDAC\Settings;

final class ContentPrompt {

	/**
	 * Build chat messages for content generation or editing.
	 *
	 * @param array<string, mixed> $context     Post context (content, title, etc.).
	 * @param string               $action      The action to perform: generate, rephrase, shorten, expand.
	 * @param string               $customPrompt Custom user instructions.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function build( array $context, string $action, string $customPrompt ): array {
		// Define action description.
		$actionDesc = match ( $action ) {
			'shorten'  => 'Condense the content to roughly half its current length. Preserve the core meaning, key facts, and tone. Remove redundancy and filler. Do not add new information.',
			'expand'   => 'Expand the content to roughly 1.5 to 2 times its current length. Add supporting detail, elaboration, or examples that are consistent with the original meaning and tone. Do not introduce contradictory information.',
			'generate' => 'Generate a well-structured, detailed, and engaging content text based on the provided title, outline, or prompt. Maintain a professional, clear tone.',
			default    => 'Rephrase the content using different wording and sentence structure while preserving the exact same meaning, tone, and level of detail. The output should be approximately the same length as the input.', // rephrase
		};

		// Determine format instruction.
		$format     = Settings::get( 'content_format', 'html' );
		$formatDesc = ( 'plain' === $format )
			? 'Return content as plain text. Do not include any HTML tags. Strip all HTML tags if present in the input.'
			: 'Return content with appropriate HTML tags. Preserve and reuse any inline HTML tags (such as strong, em, a, code, p, ul, li) from the input, or create appropriate HTML elements if writing new content.';

		$system = "You are an editorial assistant that transforms or generates text content (denoted by <content> tags) while preserving meaning and intent.

Goal: {$actionDesc}

Requirements:
- Return only the transformed/generated text, nothing else.
- Do not include any preamble, explanation, introduction like \"Here is the rewritten text:\", or commentary.
- {$formatDesc}
- Match the original language of the content. If the content is in Vietnamese, generate/rewrite in Vietnamese. If English, respond in English.
- Maintain the original perspective, voice, and tone.";

		// Add custom instructions if provided.
		if ( '' !== $customPrompt ) {
			$system .= "\n\nAdditional instructions: " . $customPrompt;
		}

		// Gather user content.
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
				$postContent = $post->post_content;
				if ( $postContent ) {
					$parts[] = '<content>' . $postContent . '</content>';
				}

				$postTitle = $post->post_title;
				if ( $postTitle ) {
					$parts[] = '<title>' . $postTitle . '</title>';
				}

				// Include categories/tags as context.
				$terms = wp_get_post_terms( $postId, [ 'category', 'post_tag' ], [ 'fields' => 'names' ] );
				if ( ! is_wp_error( $terms ) && $terms ) {
					$parts[] = '<categories>' . implode( ', ', $terms ) . '</categories>';
				}
			}
		}

		// Fallback/Inline content or selected text.
		$inlineContent = isset( $context['content'] ) ? (string) $context['content'] : '';
		if ( $inlineContent ) {
			// If we already have content tags, don't overwrite if they are the same
			$hasContentInParts = false;
			foreach ( $parts as $part ) {
				if ( str_starts_with( $part, '<content>' ) ) {
					$hasContentInParts = true;
					break;
				}
			}
			if ( ! $hasContentInParts ) {
				$parts[] = '<content>' . $inlineContent . '</content>';
			}
		}

		$postTitleAttr = isset( $context['post_title'] ) ? sanitize_text_field( $context['post_title'] ) : '';
		if ( $postTitleAttr ) {
			$hasTitleInParts = false;
			foreach ( $parts as $part ) {
				if ( str_starts_with( $part, '<title>' ) ) {
					$hasTitleInParts = true;
					break;
				}
			}
			if ( ! $hasTitleInParts ) {
				$parts[] = '<title>' . $postTitleAttr . '</title>';
			}
		}

		return implode( "\n\n", $parts ) ?: 'No content provided.';
	}
}
