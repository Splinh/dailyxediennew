<?php
/**
 * Email Template Engine
 *
 * Renders HTML email templates with placeholder replacement.
 * Uses pure HTML templates — no Markdown dependency.
 *
 * @package SPL\Modules\Form\Notification
 */

namespace SPL\Modules\Form\Notification;

use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

class TemplateEngine {

	/**
	 * Placeholder names that intentionally carry sanitized HTML.
	 *
	 * @var string[]
	 */
	private const RAW_PLACEHOLDERS = [
		'content',
		'fields_table',
	];

	/**
	 * Render email template.
	 *
	 * @param string $templateName Template filename without extension.
	 * @param array  $data         Variables for template.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render( string $templateName, array $data ): string {
		$contentHtml = self::resolveTemplate( $templateName, $data );

		$baseHtmlPath = dirname( __DIR__ ) . '/email-templates/_base.html';
		$baseHtml     = Helper::fileRead( $baseHtmlPath ) ?: '{{content}}';

		// Inject rendered content.
		$data['content'] = $contentHtml;

		// Standard site variables.
		$data['site_name'] = $data['site_name'] ?? Helper::getOption( 'blogname', '' );
		$data['site_url']  = $data['site_url'] ?? Helper::home();

		$siteLogoId        = Helper::getThemeMod( 'custom_logo' );
		$logoUrl           = Helper::attachmentImageSrc( $siteLogoId, 'full' ) ?: '';
		$data['site_logo'] = $data['site_logo'] ?? $logoUrl;
		$data['year']      = $data['year'] ?? gmdate( 'Y' );

		return self::replacePlaceholders( $baseHtml, $data );
	}

	/**
	 * Resolve and render template content.
	 *
	 * Priority: wp_options → file → fallback.
	 *
	 * @param string $name Template filename without extension.
	 * @param array  $data Variables for template.
	 *
	 * @return string Rendered HTML content block.
	 */
	protected static function resolveTemplate( string $name, array $data ): string {
		// 1. wp_options (future admin editor).
		$optionKey = 'hd_email_tpl_' . $name;
		$optionTpl = Helper::getOption( $optionKey, '' );
		if ( ! empty( $optionTpl ) ) {
			return self::replacePlaceholders( $optionTpl, $data );
		}

		// 2. File in Modules/Form/email-templates/.
		$path    = dirname( __DIR__ ) . '/email-templates/' . $name . '.html';
		$content = Helper::fileRead( $path );
		if ( $content ) {
			return self::replacePlaceholders( $content, $data );
		}

		// 3. Fallback → default.html.
		$fallback = dirname( __DIR__ ) . '/email-templates/default.html';
		$content  = Helper::fileRead( $fallback );
		if ( $content ) {
			return self::replacePlaceholders( $content, $data );
		}

		// 4. Emergency inline fallback.
		return self::emergencyFallback( $data );
	}

	/**
	 * Build fields table HTML from entry data.
	 *
	 * @param array $data Template data (must contain 'fields' key).
	 *
	 * @return string HTML table rows.
	 */
	protected static function buildFieldsTable( array $data ): string {
		$fields = $data['fields'] ?? [];
		$labels = $fields['__labels'] ?? [];
		unset( $fields['__labels'], $fields['__files'], $fields['__geo'] );

		if ( empty( $fields ) ) {
			return '';
		}

		$rows = '';
		foreach ( $fields as $key => $value ) {
			$label = $labels[ $key ] ?? ucwords( str_replace( [ '_', '-' ], ' ', $key ) );

			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			$rows .= sprintf(
				'<tr><td style="border:1px solid #e2e8f0;padding:8px;font-weight:600;color:#334155;width:35%%;background:#f8fafc;vertical-align:top;">%s</td><td style="border:1px solid #e2e8f0;padding:8px;color:#475569;vertical-align:top;">%s</td></tr>',
				esc_html( $label ),
				esc_html( (string) $value )
			);
		}

		if ( '' === $rows ) {
			return '';
		}

		return '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;width:100%;margin-bottom:24px;font-size:14px;">' . $rows . '</table>';
	}

	/**
	 * Replace {{placeholders}} in template string.
	 *
	 * @param string $template Template string.
	 * @param array  $data     Data to replace.
	 *
	 * @return string Replaced string.
	 */
	protected static function replacePlaceholders( string $template, array $data ): string {
		// Auto-generate fields_table if needed.
		if ( ! isset( $data['fields_table'] ) && isset( $data['fields'] ) ) {
			$data['fields_table'] = self::buildFieldsTable( $data );
		}

		return preg_replace_callback(
			'/{{\s*([a-zA-Z0-9_\-]+)\s*}}/',
			static function ( array $matches ) use ( $template, $data ): string {
				$key = (string) ( $matches[1][0] ?? '' );
				if ( '' === $key || ! array_key_exists( $key, $data ) || ! is_scalar( $data[ $key ] ) ) {
					return '';
				}

				$value   = (string) $data[ $key ];
				$context = self::placeholderContext( $template, (int) ( $matches[0][1] ?? 0 ), $key );

				return match ( $context ) {
					'raw'  => wp_kses_post( $value ),
					'url'  => esc_url( $value ),
					'attr' => esc_attr( $value ),
					default => esc_html( $value ),
				};
			},
			$template,
			-1,
			$count,
			PREG_OFFSET_CAPTURE
		) ?? '';
	}

	/**
	 * Resolve the escaping context for a placeholder occurrence.
	 */
	private static function placeholderContext( string $template, int $offset, string $key ): string {
		if ( self::isRawPlaceholder( $key ) ) {
			return 'raw';
		}

		$before    = substr( $template, max( 0, $offset - 200 ), min( 200, $offset ) );
		$lastLt    = strrpos( $before, '<' );
		$lastGt    = strrpos( $before, '>' );
		$insideTag = false !== $lastLt && ( false === $lastGt || $lastLt > $lastGt );

		if ( $insideTag ) {
			if ( preg_match( '/(?:href|src|action)\s*=\s*["\'][^"\']*$/i', $before ) ) {
				return 'url';
			}

			if ( preg_match( '/[a-z0-9:_-]+\s*=\s*["\'][^"\']*$/i', $before ) ) {
				return 'attr';
			}
		}

		return self::isUrlPlaceholder( $key ) ? 'url' : 'html';
	}

	/**
	 * Whether a placeholder is explicitly allowed to render sanitized HTML.
	 */
	private static function isRawPlaceholder( string $key ): bool {
		return in_array( $key, self::RAW_PLACEHOLDERS, true )
			|| str_starts_with( $key, 'raw_' )
			|| str_ends_with( $key, '_html' );
	}

	/**
	 * Whether a placeholder should be treated as a URL when no attribute context exists.
	 */
	private static function isUrlPlaceholder( string $key ): bool {
		return str_contains( $key, 'url' )
			|| str_ends_with( $key, '_link' )
			|| str_ends_with( $key, '_href' )
			|| str_ends_with( $key, '_src' );
	}

	/**
	 * Emergency fallback when no template files exist.
	 *
	 * @param array $data Template data.
	 *
	 * @return string Minimal HTML.
	 */
	private static function emergencyFallback( array $data ): string {
		$html  = '<h2 style="font-size:20px;font-weight:600;color:#1e293b;margin:0 0 16px;">New Form Submission</h2>';
		$html .= self::buildFieldsTable( $data );

		return $html;
	}
}
