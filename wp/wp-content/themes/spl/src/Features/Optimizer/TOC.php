<?php
/**
 * Table of Contents Optimizer Module
 *
 * Automatically parses H2 headings in single posts to generate anchors
 * and prepends a responsive, collapsible Table of Contents box.
 *
 * @package SPL\Features\Optimizer
 * @author  HD
 */

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

final class TOC {

	/**
	 * Headings array list.
	 *
	 * @var array
	 */
	private static array $headings = [];

	/**
	 * Track if content has been processed.
	 *
	 * @var bool
	 */
	private static bool $processed = false;

	/**
	 * Register feature.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'the_content', [ self::class, 'processContent' ], 15 );
	}

	/**
	 * Process post and product content.
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	public static function processContent( string $content ): string {
		if ( ! is_singular( [ 'post', 'product' ] ) ) {
			return $content;
		}

		if ( self::$processed ) {
			return $content;
		}

		self::$headings = [];
		$slugs          = [];

		// Match h2 tags.
		$pattern = '/<h2([^>]*)>(.*?)<\/h2>/i';

		$content = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( &$slugs ) {
				$attrs = $matches[1];
				$title = wp_strip_all_tags( $matches[2] );
				$slug  = sanitize_title( $title );

				if ( isset( $slugs[ $slug ] ) ) {
					$slugs[ $slug ]++;
					$slug = $slug . '-' . $slugs[ $slug ];
				} else {
					$slugs[ $slug ] = 1;
				}

				self::$headings[] = [
					'title' => $title,
					'slug'  => $slug,
				];

				// Do not overwrite existing id attribute if present.
				if ( preg_match( '/\bid\s*=\s*"/i', $attrs ) ) {
					return $matches[0];
				}

				return sprintf( '<h2 id="%s"%s>%s</h2>', esc_attr( $slug ), $attrs, $matches[2] );
			},
			$content
		) ?? $content;

		self::$processed = true;

		// Prepend TOC to content.
		if ( ! empty( self::$headings ) ) {
			$content = self::generateTocHtml() . $content;
		}

		return $content;
	}

	/**
	 * Generate Table of Contents HTML (default collapsed/hidden).
	 *
	 * @return string
	 */
	private static function generateTocHtml(): string {
		if ( empty( self::$headings ) ) {
			return '';
		}

		$title_text = is_singular( 'product' )
			? esc_html__( 'Mục lục nội dung', 'spl' )
			: esc_html__( 'Mục lục bài viết', 'spl' );

		$toc_id = 'toc-' . wp_generate_password( 8, false, false );

		$html  = '<div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 md:p-5 mb-6 toc-box" id="' . esc_attr( $toc_id ) . '">';
		$html .= '  <div class="flex items-center justify-between cursor-pointer select-none toc-trigger">';
		$html .= '    <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2 mb-0">';
		$html .= '      <svg class="w-4 h-4 text-[#1e73be]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>';
		$html .= '      ' . $title_text;
		$html .= '    </h3>';
		$html .= '    <span class="text-xs text-[#1e73be] font-semibold hover:underline toc-toggle-btn">' . esc_html__( '[Hiện]', 'spl' ) . '</span>';
		$html .= '  </div>';
		$html .= '  <ul class="space-y-2 text-sm text-slate-600 pl-4 list-decimal hidden mt-3 pt-3 border-t border-slate-200/60 toc-list">';

		foreach ( self::$headings as $heading ) {
			$html .= sprintf(
				'    <li><a href="#%s" class="hover:text-[#1e73be] transition-colors font-medium">%s</a></li>',
				esc_attr( $heading['slug'] ),
				esc_html( $heading['title'] )
			);
		}

		$html .= '  </ul>';
		$html .= '</div>';

		// Add dynamic toggle script.
		$html .= '
		<script>
		document.addEventListener("DOMContentLoaded", function() {
			var box = document.getElementById("' . esc_js( $toc_id ) . '");
			if (box) {
				var trigger = box.querySelector(".toc-trigger");
				var list = box.querySelector(".toc-list");
				var btn = box.querySelector(".toc-toggle-btn");
				if (trigger && list && btn) {
					trigger.addEventListener("click", function() {
						if (list.classList.contains("hidden")) {
							list.classList.remove("hidden");
							btn.textContent = "' . esc_js( __( '[Ẩn]', 'spl' ) ) . '";
						} else {
							list.classList.add("hidden");
							btn.textContent = "' . esc_js( __( '[Hiện]', 'spl' ) ) . '";
						}
					});
				}
			}
		});
		</script>';

		return $html;
	}
}
