<?php
/**
 * Optimizer Feature
 *
 * Main coordinator for performance optimizations and WordPress cleanup.
 * Delegates specific tasks to sub-modules:
 * - ImageSize: Configure image sizes
 * - ScriptLoader: Handle script/style tag modifications
 * - CssClass: Manage body/post/menu CSS classes
 *
 * @package HD\Features
 * @author  HD
 */

namespace HD\Features;

use HD\Contracts\Feature;
use HD\Features\Optimizer\CssClass;
use HD\Features\Optimizer\ImageSize;
use HD\Features\Optimizer\ScriptLoader;
use HD\Core\DB;
use HD\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class Optimizer extends Feature {

	/** ---------------------------------------- */

	public function boot(): void {
		// Run sub-modules
		ImageSize::register();
		ScriptLoader::register();
		CssClass::register();

		// Permalink — only on theme activation (flush_rules is expensive).
		add_action( 'after_switch_theme', self::configurePermalink( ... ) );

		$this->registerOptimizations();
	}

	/** ---------------------------------------- */

	/**
	 * Configure permalink structure (one-time, on theme activation).
	 *
	 * @return void
	 */
	private static function configurePermalink(): void {
		if ( Helper::getOption( 'hd_permalink_configured' ) ) {
			return;
		}

		Helper::updateOption( 'hd_permalink_configured', true );

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules();
	}

	/** ---------------------------------------- */

	/**
	 * Register additional optimization hooks.
	 *
	 * @return void
	 */
	private function registerOptimizations(): void {
		// Shortcode support in widgets
		add_filter( 'widget_text', 'do_shortcode' );
		add_filter( 'widget_text', 'shortcode_unautop' );

		// Remove inline image styles
		add_filter( 'post_thumbnail_html', $this->removeInlineImgStyles( ... ) );
		add_filter( 'the_content', $this->removeInlineImgStyles( ... ) );

		// Front-end only (excluding login page)
		if ( ! is_admin() && ! Helper::isLogin() ) {
			add_action( 'wp_print_footer_scripts', $this->printFooterScripts( ... ), 20 );
		}

		// Custom hooks
		add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
		add_filter( 'excerpt_more', $this->excerptMore( ... ) );
		add_filter( 'sanitize_file_name', $this->sanitizeFileName( ... ) );
		add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
		add_filter( 'query_vars', $this->queryVars( ... ), 99 );
		add_filter( 'posts_search', $this->searchByTitle( ... ), 500, 2 );
		add_action( 'wp_default_scripts', $this->wpDefaultScripts( ... ) );
	}

	/** ---------------------------------------- */
	/* PUBLIC CALLBACKS                          */
	/** ---------------------------------------- */

	/**
	 * Remove inline style attribute from image tags.
	 *
	 * @param string|null $html HTML content.
	 *
	 * @return string|null
	 */
	public function removeInlineImgStyles( ?string $html ): string|null {
		if ( ! $html ) {
			return $html;
		}

		return preg_replace(
			'/(<img[^>]+)(style=\"[^\"]+\")([^>]*)(>)/',
			'${1}${3}${4}',
			$html
		) ?? $html;
	}

	/** ---------------------------------------- */

	/**
	 * Custom excerpt more text.
	 *
	 * @return string
	 */
	public function excerptMore(): string {
		return ' &hellip;';
	}

	/** ---------------------------------------- */

	/**
	 * Sanitize filename by removing accents.
	 *
	 * @param mixed $filename File name.
	 *
	 * @return string
	 */
	public function sanitizeFileName( mixed $filename ): string {
		return remove_accents( (string) $filename );
	}

	/** ---------------------------------------- */

	/**
	 * Add custom query vars.
	 *
	 * @param array $vars Query vars.
	 *
	 * @return array
	 */
	public function queryVars( array $vars ): array {
		return [ ...$vars, 'page', 'paged' ];
	}

	/** ---------------------------------------- */

	/**
	 * Remove jquery-migrate from jQuery dependencies.
	 *
	 * @param \WP_Scripts $scripts Scripts object.
	 *
	 * @return void
	 */
	public function wpDefaultScripts( \WP_Scripts $scripts ): void {
		if ( is_admin() ) {
			return;
		}

		$jquery = $scripts->registered['jquery'] ?? null;
		if ( $jquery && is_array( $jquery->deps ) && $jquery->deps ) {
			$jquery->deps = array_diff( $jquery->deps, [ 'jquery-migrate' ] );
		}
	}

	/** ---------------------------------------- */

	/**
	 * Search only in post-title or excerpt.
	 *
	 * @param string    $search  Search SQL.
	 * @param \WP_Query $wpQuery Query object.
	 *
	 * @return string
	 */
	public function searchByTitle( string $search, \WP_Query $wpQuery ): string {
		if ( ! $search ) {
			return $search;
		}

		$db        = DB::db();
		$postTable = $db->posts;
		$q         = $wpQuery->query_vars;
		$n         = empty( $q['exact'] ) ? '%' : '';

		$searchParts = [];
		foreach ( (array) ( $q['search_terms'] ?? [] ) as $term ) {
			$escapedTerm   = $db->esc_like( mb_strtolower( $term ) );
			$like          = $db->prepare( 'LIKE CONCAT(%s, CONVERT(%s, BINARY), %s)', $n, $escapedTerm, $n );
			$searchParts[] = "(LOWER({$postTable}.post_title) {$like} OR LOWER({$postTable}.post_excerpt) {$like})";
		}

		if ( ! $searchParts ) {
			return $search;
		}

		$search = ' AND (' . implode( ' AND ', $searchParts ) . ') ';

		if ( ! is_user_logged_in() ) {
			$search .= " AND ({$postTable}.post_password = '') ";
		}

		return $search;
	}

	/** ---------------------------------------- */

	/**
	 * Print footer script to remove no-js class.
	 *
	 * @return void
	 */
	public function printFooterScripts(): void {
		wp_print_inline_script_tag( "document.documentElement.classList.remove('no-js');" );
	}
}
