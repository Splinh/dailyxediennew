<?php
/**
 * Extract translatable content units.
 *
 * @package HD\Modules\PLL\AI\Content
 */

namespace HD\Modules\PLL\AI\Content;

use HD\Modules\PLL\AI\TranslationUnit;
use HD\Modules\PLL\PLLModule;

defined( 'ABSPATH' ) || exit;

final class ContentExtractor {

	private const BLOCK_ATTRIBUTES = [ 'title', 'text', 'content', 'caption', 'alt', 'ariaLabel', 'label', 'description' ];

	public function __construct( private readonly HtmlStructure $html = new HtmlStructure() ) {}

	/**
	 * @return TranslationUnit[]
	 */
	public function extractPost( \WP_Post $post, array $options = [] ): array {
		$settings         = PLLModule::getCachedOptions();
		$translateTitle   = $this->enabled( $options, $settings, 'translate_title', 'ai_translate_title', true );
		$translateContent = $this->enabled( $options, $settings, 'translate_content', 'ai_translate_content', true );
		$translateExcerpt = $this->enabled( $options, $settings, 'translate_excerpt', 'ai_translate_excerpt', true );
		$units            = [];

		if ( $translateTitle && '' !== trim( $post->post_title ) && ! $this->html->isShortcodeOnly( $post->post_title ) ) {
			$units[] = new TranslationUnit( 'post_title', $post->post_title, 'post title', 'text', $this->protectedTokens( $post->post_title ), [ 'post_title' ] );
		}

		if ( $translateExcerpt && '' !== trim( $post->post_excerpt ) && ! $this->html->isShortcodeOnly( $post->post_excerpt ) ) {
			$units[] = new TranslationUnit( 'post_excerpt', $post->post_excerpt, 'post excerpt', 'html', $this->protectedTokens( $post->post_excerpt ), [ 'post_excerpt' ] );
		}

		if ( ! $translateContent ) {
			return $units;
		}

		if ( ! has_blocks( $post->post_content ) ) {
			foreach ( $this->extractHtmlUnits( $post->post_content, [ 'post_content' ], 'post content' ) as $unit ) {
				$units[] = $unit;
			}
		}

		foreach ( $this->extractBlockAttributes( $post->post_content ) as $unit ) {
			$units[] = $unit;
		}

		return $units;
	}

	/**
	 * @return TranslationUnit[]
	 */
	private function extractBlockAttributes( string $content ): array {
		if ( ! has_blocks( $content ) ) {
			return [];
		}

		$units = [];
		$this->walkBlocks( parse_blocks( $content ), $units );

		return $units;
	}

	/**
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @param TranslationUnit[]               $units  Output units.
	 */
	private function walkBlocks( array $blocks, array &$units, array $path = [] ): void {
		foreach ( $blocks as $index => $block ) {
			$blockPath = [ ...$path, (string) $index ];
			$blockName = (string) ( $block['blockName'] ?? 'freeform' );
			$attrs     = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];

			foreach ( self::BLOCK_ATTRIBUTES as $attr ) {
				if ( empty( $attrs[ $attr ] ) || ! is_string( $attrs[ $attr ] ) || $this->html->isShortcodeOnly( $attrs[ $attr ] ) ) {
					continue;
				}

				$id      = sanitize_key( 'block_' . implode( '_', $blockPath ) . '_attr_' . $attr );
				$source  = $attrs[ $attr ];
				$units[] = new TranslationUnit( $id, $source, $blockName . ' attribute', 'block_attribute', $this->protectedTokens( $source ), [ 'blocks', ...$blockPath, 'attrs', $attr ] );
			}

			$innerBlocks = is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : [];
			$innerHtml   = is_string( $block['innerHTML'] ?? null ) ? $block['innerHTML'] : '';
			if ( empty( $innerBlocks ) ) {
				foreach ( $this->extractHtmlUnits( $innerHtml, [ 'blocks', ...$blockPath, 'innerHTML' ], $blockName . ' inner HTML' ) as $unit ) {
					$units[] = $unit;
				}
			}

			if ( ! empty( $innerBlocks ) ) {
				$this->walkBlocks( $innerBlocks, $units, [ ...$blockPath, 'innerBlocks' ] );
			}
		}
	}

	/**
	 * @return string[]
	 */
	private function protectedTokens( string $text ): array {
		$patterns = [
			'/%(?:\d+\$)?[bcdeEfFgGosuxX]/',
			'/\{[A-Za-z0-9_.-]+\}/',
			'/\[[A-Za-z0-9_-]+(?:\s[^\]]*)?\]/',
			'~https?://[^\s<>"\']+~',
		];

		$tokens = [];
		foreach ( $patterns as $pattern ) {
			preg_match_all( $pattern, $text, $matches );
			$tokens = array_merge( $tokens, $matches[0] ?? [] );
		}

		return array_values( array_unique( $tokens ) );
	}

	/**
	 * @param string[] $basePath Unit path prefix.
	 *
	 * @return TranslationUnit[]
	 */
	private function extractHtmlUnits( string $html, array $basePath, string $context ): array {
		if ( '' === trim( wp_strip_all_tags( $html ) ) || $this->html->isShortcodeOnly( $html ) ) {
			return [];
		}

		$units      = [];
		$rawTextTag = '';
		foreach ( $this->html->split( $html ) as $index => $part ) {
			if ( $this->html->isTag( $part ) ) {
				$this->updateRawTextTag( $part, $rawTextTag );

				foreach ( $this->html->translatableAttributes( $part ) as $attribute => $value ) {
					$path    = [ ...$basePath, 'tag_attrs', (string) $index, $attribute ];
					$units[] = new TranslationUnit( $this->unitId( $path ), $value, $context . ' attribute', 'text', $this->protectedTokens( $value ), $path );
				}

				continue;
			}

			if ( '' !== $rawTextTag || $this->html->isShortcodeToken( $part ) || '' === trim( $part ) ) {
				continue;
			}

			if ( '' === trim( wp_strip_all_tags( $part ) ) || $this->html->isShortcodeOnly( $part ) ) {
				continue;
			}

			$path    = [ ...$basePath, 'segments', (string) $index ];
			$units[] = new TranslationUnit( $this->unitId( $path ), $part, $context . ' text', 'text', $this->protectedTokens( $part ), $path );
		}

		return $units;
	}

	private function unitId( array $path ): string {
		return sanitize_key( implode( '_', $path ) );
	}

	private function updateRawTextTag( string $tag, string &$rawTextTag ): void {
		$trimmed = strtolower( trim( $tag ) );

		if ( '' !== $rawTextTag ) {
			if ( str_starts_with( $trimmed, '</' . $rawTextTag ) ) {
				$rawTextTag = '';
			}

			return;
		}

		foreach ( [ 'script', 'style', 'textarea' ] as $name ) {
			if ( str_starts_with( $trimmed, '<' . $name ) ) {
				$rawTextTag = $name;
				return;
			}
		}
	}

	/**
	 * @param array<string, mixed> $options  Runtime options.
	 * @param array<string, mixed> $settings Saved settings.
	 */
	private function enabled( array $options, array $settings, string $optionKey, string $settingKey, bool $defaultValue ): bool {
		if ( array_key_exists( $optionKey, $options ) ) {
			return (bool) $options[ $optionKey ];
		}

		if ( array_key_exists( $settingKey, $settings ) ) {
			return (bool) $settings[ $settingKey ];
		}

		return $defaultValue;
	}
}
