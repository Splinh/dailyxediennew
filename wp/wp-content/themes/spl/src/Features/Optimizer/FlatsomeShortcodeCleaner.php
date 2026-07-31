<?php

declare( strict_types=1 );

namespace SPL\Features\Optimizer;

defined( 'ABSPATH' ) || exit;

/**
 * Handle and clean up Flatsome shortcodes in product descriptions/content.
 *
 * Maps layout shortcodes (row, col, section, ux_text) to clean Tailwind equivalents
 * and converts media shortcodes (ux_image, ux_slider) to responsive grids and images.
 *
 * @package SPL
 */
final class FlatsomeShortcodeCleaner {

	public static function register(): void {
		// Register shortcode handlers.
		add_shortcode( 'section', [ self::class, 'renderSection' ] );
		add_shortcode( 'row', [ self::class, 'renderRow' ] );
		add_shortcode( 'col', [ self::class, 'renderCol' ] );
		add_shortcode( 'ux_text', [ self::class, 'renderUxText' ] );
		add_shortcode( 'featured_box', [ self::class, 'renderFeaturedBox' ] );
		add_shortcode( 'ux_image', [ self::class, 'renderUxImage' ] );
		add_shortcode( 'ux_slider', [ self::class, 'renderUxSlider' ] );

		// Hook into the_content to clean up stray tags or ensure correct execution.
		add_filter( 'the_content', [ self::class, 'cleanContentTags' ], 5 );
	}

	/**
	 * Strip shortcode tags (preserving inner content) from a string.
	 * Used for short descriptions.
	 */
	public static function cleanShortcodes( string $text ): string {
		if ( empty( $text ) ) {
			return '';
		}
		// Strip shortcode tags like [section ...] or [/section]
		return preg_replace( '/\[\/?[^\]]+\]/', '', $text );
	}

	public static function renderSection( array|string|null $atts, string $content = '' ): string {
		return '<section class="py-6">' . do_shortcode( $content ) . '</section>';
	}

	public static function renderRow( array|string|null $atts, string $content = '' ): string {
		return '<div class="grid grid-cols-1 md:grid-cols-12 gap-6 my-6">' . do_shortcode( $content ) . '</div>';
	}

	public static function renderCol( array|string|null $atts, string $content = '' ): string {
		$atts = shortcode_atts( [
			'span'     => '12',
			'span__sm' => '',
			'span__md' => '',
		], is_array( $atts ) ? $atts : [] );

		$classes = [];
		$span    = (int) $atts['span'];
		$classes[] = "md:col-span-{$span}";

		if ( ! empty( $atts['span__sm'] ) ) {
			$sm        = (int) $atts['span__sm'];
			$classes[] = "col-span-{$sm}";
		} else {
			$classes[] = "col-span-12";
		}

		if ( ! empty( $atts['span__md'] ) ) {
			$md        = (int) $atts['span__md'];
			$classes[] = "md:col-span-{$md}";
		}

		return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . do_shortcode( $content ) . '</div>';
	}

	public static function renderUxText( array|string|null $atts, string $content = '' ): string {
		return '<div class="prose max-w-none text-slate-700 leading-relaxed">' . do_shortcode( $content ) . '</div>';
	}

	public static function renderFeaturedBox( array|string|null $atts, string $content = '' ): string {
		$atts = shortcode_atts( [
			'img'       => '',
			'img_width' => '46',
			'pos'       => 'center',
			'link'      => '',
		], is_array( $atts ) ? $atts : [] );

		$img_html = '';
		if ( ! empty( $atts['img'] ) ) {
			$img_url = wp_get_attachment_image_url( (int) $atts['img'], 'thumbnail' );
			if ( $img_url ) {
				$img_html = '<div class="flex justify-center mb-2"><img loading="lazy" decoding="async" src="' . esc_url( $img_url ) . '" class="h-10 w-auto object-contain" style="width:' . esc_attr( $atts['img_width'] ) . 'px" /></div>';
			}
		}

		$inner = $img_html . '<div class="text-center font-bold text-slate-800">' . do_shortcode( $content ) . '</div>';

		if ( ! empty( $atts['link'] ) && '#' !== $atts['link'] ) {
			return '<a href="' . esc_url( $atts['link'] ) . '" class="block p-4 border border-slate-100 rounded-xl hover:shadow-md transition-shadow bg-white">' . $inner . '</a>';
		}

		return '<div class="p-4 border border-slate-100 rounded-xl bg-slate-50/50">' . $inner . '</div>';
	}

	public static function renderUxImage( array|string|null $atts ): string {
		$atts = shortcode_atts( [
			'id'     => '',
			'width'  => '',
			'height' => '',
			'class'  => '',
		], is_array( $atts ) ? $atts : [] );

		if ( empty( $atts['id'] ) ) {
			return '';
		}

		$img_url = wp_get_attachment_image_url( (int) $atts['id'], 'large' );
		if ( ! $img_url ) {
			return '';
		}

		$style = '';
		if ( ! empty( $atts['width'] ) ) {
			$style .= 'width:' . esc_attr( $atts['width'] ) . ';';
		}

		return '<div class="my-4 flex justify-center"><img loading="lazy" decoding="async" src="' . esc_url( $img_url ) . '" class="max-w-full h-auto rounded-xl shadow-sm ' . esc_attr( $atts['class'] ) . '" style="' . esc_attr( $style ) . '" /></div>';
	}

	public static function renderUxSlider( array|string|null $atts, string $content = '' ): string {
		return '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 my-6">' . do_shortcode( $content ) . '</div>';
	}

	/**
	 * Pre-process content to strip empty paragraph wrappers around shortcodes.
	 */
	public static function cleanContentTags( string $content ): string {
		$content = preg_replace( '/<p>\s*\[([^\]]+)\]\s*<\/p>/', '[$1]', $content );
		return $content;
	}
}
