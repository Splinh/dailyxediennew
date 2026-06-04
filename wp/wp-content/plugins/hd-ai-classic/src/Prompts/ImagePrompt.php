<?php
/**
 * Image prompt generation helper.
 *
 * @package HDAC\Prompts
 */

namespace HDAC\Prompts;

defined( 'ABSPATH' ) || exit;

final class ImagePrompt {

	private const SYSTEM = <<<'INSTRUCTION'
You are a helpful assistant that generates a single, self-contained image generation prompt suitable for use with an image generation LLM.

Goal: Synthesize the provided content and context into a single, complete image generation prompt that can be passed directly to another LLM to immediately generate an image.

Requirements:
- Incorporate relevant context faithfully and accurately.
- Do not reference the existence or structure of the input context.
- Do not include explanations, headings, or commentary.
- Output only the final image generation prompt text. No preamble, no quotes, no markdown, no follow-up.
- The generated prompt should describe an image that visually represents the content's core topic and tone.
- Do NOT include text, captions, logos, branding, or watermarks in the image.
- Describe the subject, setting, and visual style directly and concisely.
INSTRUCTION;

	private const STYLE_INSTRUCTIONS = [
		'editorial'      => 'Style: Professional editorial photography, clean composition, natural lighting, high-end editorial feel.',
		'minimalist'     => 'Style: Minimalist art, clean lines, simple shapes, generous negative space, subtle color palette.',
		'vibrant'        => 'Style: Bold and vibrant digital illustration, high contrast, saturated colors, modern aesthetic.',
		'photorealistic' => 'Style: Photorealistic representation, lifelike details, realistic textures, natural depth of field, high resolution.',
	];

	/**
	 * Build chat messages for image prompt generation.
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
			$system .= "\n\n" . self::STYLE_INSTRUCTIONS[ $presetId ];
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
		$parts         = [];
		$inlineContent = sanitize_textarea_field( $context['content'] ?? '' );
		if ( $inlineContent ) {
			$parts[] = '<content>' . wp_strip_all_tags( $inlineContent ) . '</content>';
		}

		$postId = absint( $context['post_id'] ?? 0 );
		if ( $postId > 0 ) {
			$post = get_post( $postId );
			if ( $post ) {
				$postContent = wp_strip_all_tags( $post->post_content );
				if ( ! $parts && $postContent ) {
					$parts[] = '<content>' . $postContent . '</content>';
				}

				// Include categories/tags as context.
				$terms = wp_get_post_terms( $postId, [ 'category', 'post_tag' ], [ 'fields' => 'names' ] );
				if ( ! is_wp_error( $terms ) && $terms ) {
					$parts[] = '<categories>' . implode( ', ', $terms ) . '</categories>';
				}
			}
		}

		return implode( "\n\n", $parts ) ?: 'No content provided.';
	}
}
