<?php
/**
 * Preset prompts registry.
 *
 * @package HDAC
 */

namespace HDAC;

defined( 'ABSPATH' ) || exit;

final class PromptRegistry {

	/**
	 * Get preset prompts for a feature.
	 *
	 * @param string $feature Feature ID: 'title', 'excerpt', 'term-description'.
	 *
	 * @return array<int, array{id: string, label: string}>
	 */
	public static function presets( string $feature ): array {
		$presets = match ( $feature ) {
			'title' => [
				[
					'id'    => 'seo_friendly',
					'label' => __( 'SEO-friendly', 'hd-ai-classic' ),
				],
				[
					'id'    => 'engaging',
					'label' => __( 'Engaging / Clickbait', 'hd-ai-classic' ),
				],
				[
					'id'    => 'question',
					'label' => __( 'Question-based', 'hd-ai-classic' ),
				],
				[
					'id'    => 'formal',
					'label' => __( 'Formal / Professional', 'hd-ai-classic' ),
				],
			],
			'excerpt' => [
				[
					'id'    => 'summary',
					'label' => __( 'Summary (2-3 sentences)', 'hd-ai-classic' ),
				],
				[
					'id'    => 'seo_meta',
					'label' => __( 'SEO meta description (<=160 chars)', 'hd-ai-classic' ),
				],
				[
					'id'    => 'teaser',
					'label' => __( 'Hook / Teaser', 'hd-ai-classic' ),
				],
			],
			'term-description' => [
				[
					'id'    => 'seo_category',
					'label' => __( 'SEO category description', 'hd-ai-classic' ),
				],
				[
					'id'    => 'brief',
					'label' => __( 'Brief summary', 'hd-ai-classic' ),
				],
				[
					'id'    => 'detailed',
					'label' => __( 'Detailed overview', 'hd-ai-classic' ),
				],
			],
			'content' => [
				[
					'id'    => 'rephrase',
					'label' => __( 'Rephrase', 'hd-ai-classic' ),
				],
				[
					'id'    => 'shorten',
					'label' => __( 'Shorten', 'hd-ai-classic' ),
				],
				[
					'id'    => 'expand',
					'label' => __( 'Expand', 'hd-ai-classic' ),
				],
				[
					'id'    => 'generate',
					'label' => __( 'Generate Content', 'hd-ai-classic' ),
				],
			],
			'long-content' => [
				[
					'id'    => 'comprehensive',
					'label' => __( 'Comprehensive Article', 'hd-ai-classic' ),
				],
				[
					'id'    => 'seo_guide',
					'label' => __( 'SEO Guide', 'hd-ai-classic' ),
				],
				[
					'id'    => 'editorial',
					'label' => __( 'Editorial Feature', 'hd-ai-classic' ),
				],
			],
			'image', 'image-prompt' => [
				[
					'id'    => 'editorial',
					'label' => __( 'Editorial Photography', 'hd-ai-classic' ),
				],
				[
					'id'    => 'minimalist',
					'label' => __( 'Minimalist Art', 'hd-ai-classic' ),
				],
				[
					'id'    => 'vibrant',
					'label' => __( 'Vibrant Illustration', 'hd-ai-classic' ),
				],
				[
					'id'    => 'photorealistic',
					'label' => __( 'Photorealistic', 'hd-ai-classic' ),
				],
			],
			default => [],
		};

		/**
		 * Filter preset prompts for a feature.
		 *
		 * @param array  $presets Preset prompts array.
		 * @param string $feature Feature ID.
		 */
		return (array) apply_filters( "hdac_prompt_presets_{$feature}", $presets, $feature );
	}
}
