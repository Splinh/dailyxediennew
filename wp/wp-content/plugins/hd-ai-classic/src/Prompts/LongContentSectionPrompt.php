<?php
/**
 * Long content section prompt builder.
 *
 * @package HDAC\Prompts
 */

namespace HDAC\Prompts;

defined( 'ABSPATH' ) || exit;

use HDAC\Settings;

final class LongContentSectionPrompt {

	/**
	 * Build chat messages for one long-content section.
	 *
	 * @param array<string, mixed> $context      Section and article context.
	 * @param string               $action       Preset/action id.
	 * @param string               $customPrompt Custom user instructions.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function build( array $context, string $action, string $customPrompt ): array {
		$style = match ( $action ) {
			'seo_guide' => 'Write as a practical SEO guide with clear explanations, useful examples, and natural keyword coverage.',
			'editorial' => 'Write in an editorial style with polished prose, smooth transitions, and strong reader engagement.',
			default     => 'Write as a comprehensive article section with useful detail and clear structure.',
		};

		$format     = Settings::get( 'content_format', 'html' );
		$formatDesc = ( 'plain' === $format )
			? 'Return plain text only. Do not include HTML tags.'
			: 'Return safe Classic Editor HTML only. Use semantic tags such as h2, h3, p, ul, ol, li, strong, em, blockquote, and a when useful.';

		$system = "You are an expert long-form article writer.

Goal: {$style}

Requirements:
- Write only the requested section.
- Do not include a preamble, explanation, or Markdown fences.
- {$formatDesc}
- Match the language of the article title or outline.
- Avoid repeating previous sections.
- Keep the section self-contained but consistent with the full article outline.";

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
	 * Gather section context.
	 *
	 * @param array<string, mixed> $context Section and article context.
	 */
	private static function gatherContent( array $context ): string {
		$parts = [];

		$title = isset( $context['title'] ) ? sanitize_text_field( (string) $context['title'] ) : '';
		if ( '' !== $title ) {
			$parts[] = '<article_title>' . $title . '</article_title>';
		}

		$section = is_array( $context['section'] ?? null ) ? $context['section'] : [];
		$heading = isset( $section['heading'] ) ? sanitize_text_field( (string) $section['heading'] ) : '';
		$intent  = isset( $section['intent'] ) ? sanitize_textarea_field( (string) $section['intent'] ) : '';

		if ( '' !== $heading ) {
			$parts[] = '<current_heading>' . $heading . '</current_heading>';
		}

		if ( '' !== $intent ) {
			$parts[] = '<current_intent>' . $intent . '</current_intent>';
		}

		$outline = is_array( $context['outline'] ?? null ) ? $context['outline'] : [];
		if ( $outline ) {
			$items = array_map(
				static function ( mixed $item ): string {
					if ( ! is_array( $item ) ) {
						return '';
					}

					$itemHeading = isset( $item['heading'] ) ? sanitize_text_field( (string) $item['heading'] ) : '';
					$itemIntent  = isset( $item['intent'] ) ? sanitize_textarea_field( (string) $item['intent'] ) : '';

					return trim( $itemHeading . ': ' . $itemIntent );
				},
				$outline
			);

			$parts[] = '<full_outline>' . implode( "\n", array_filter( $items ) ) . '</full_outline>';
		}

		$previousHeadings = is_array( $context['previous_headings'] ?? null ) ? $context['previous_headings'] : [];
		if ( $previousHeadings ) {
			$parts[] = '<previous_headings>' . implode( ', ', array_map( 'sanitize_text_field', $previousHeadings ) ) . '</previous_headings>';
		}

		$currentContent = isset( $context['content'] ) ? (string) $context['content'] : '';
		if ( '' !== trim( $currentContent ) ) {
			$parts[] = '<existing_article_context>' . wp_strip_all_tags( $currentContent ) . '</existing_article_context>';
		}

		return implode( "\n\n", $parts ) ?: 'Write the requested long-form article section.';
	}
}
