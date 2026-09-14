<?php
/**
 * RankMath SEO Integration
 *
 * Customizations for RankMath SEO plugin:
 * - Custom breadcrumb markup
 * - Remove RankMath from admin bar
 * - TOC plugin support
 *
 * @package SPL\Modules\RankMath
 * @author  HD
 */

namespace SPL\Modules\RankMath;

use SPL\Modules\AbstractModule;
use SPL\Core\Helper;

defined( 'ABSPATH' ) || exit;

final class RankMathModule extends AbstractModule {

	/* ---------- ModuleInterface --------------------------------- */

	public static function slug(): string {
		return 'rankmath';
	}

	public static function isActive(): bool {
		return Helper::isRankMathActive();
	}

	/* ---------- Boot -------------------------------------------- */

	public function boot(): void {
		// Custom breadcrumb markup
		add_filter( 'rank_math/frontend/breadcrumb/args', $this->breadcrumbArgs( ... ) );

		// Remove RankMath from admin bar
		add_action( 'wp_before_admin_bar_render', $this->removeAdminBarMenu( ... ) );

		// Add TOC plugin support
		add_filter( 'rank_math/researches/toc_plugins', $this->tocPlugins( ... ), PHP_INT_MAX );

		// Ensure schema integrity on PHP 8.4
		add_action( 'init', $this->ensureSchemaIntegrity( ... ) );
	}

	/* ---------- PUBLIC ------------------------------------------- */

	/**
	 * Remove RankMath menu from admin bar.
	 *
	 * @return void
	 */
	public function removeAdminBarMenu(): void {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'rank-math' );
	}

	/**
	 * Filter TOC plugins to prioritize specific plugins.
	 *
	 * @param array $tocPlugins Default TOC plugins.
	 *
	 * @return array Filtered TOC plugins.
	 */
	public function tocPlugins( array $tocPlugins ): array {
		$preferred = [
			'table-of-contents-plus/toc.php' => 'Table of Contents Plus',
			'easy-table-of-contents/easy-table-of-contents.php' => 'Easy Table of Contents',
			'tocer/tocer.php'                => 'Tocer',
			'fixed-toc/fixed-toc.php'        => 'Fixed TOC',
		];

		foreach ( $preferred as $file => $label ) {
			if ( Helper::checkPluginActive( $file ) ) {
				return [ $file => $label ];
			}
		}

		return $tocPlugins;
	}

	/**
	 * Customize breadcrumb HTML structure.
	 *
	 * @param array $args Original breadcrumb arguments.
	 *
	 * @return array Modified breadcrumb arguments.
	 */
	public function breadcrumbArgs( array $args ): array {
		return [
			...$args,
			'delimiter'   => '',
			'wrap_before' => '<ul id="breadcrumbs" class="breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumbs', 'SPL' ) . '">',
			'wrap_after'  => '</ul>',
			'before'      => '<li><span property="itemListElement" typeof="ListItem">',
			'after'       => '</span></li>',
		];
	}

	/**
	 * Self-healing guard: sanitize corrupted rank_math_schema_% postmeta on PHP 8.4.
	 * Runs once daily via transient lock.
	 */
	public function ensureSchemaIntegrity(): void {
		if ( get_transient( 'spl_rank_math_schema_integrity_checked' ) ) {
			return;
		}

		set_transient( 'spl_rank_math_schema_integrity_checked', 1, DAY_IN_SECONDS );

		global $wpdb;

		if ( ! $wpdb ) {
			return;
		}

		$schemas = $wpdb->get_results(
			"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE 'rank_math_schema_%'",
			ARRAY_A
		);

		if ( empty( $schemas ) ) {
			return;
		}

		foreach ( $schemas as $row ) {
			$val = maybe_unserialize( $row['meta_value'] );
			if ( is_string( $val ) && is_serialized( $val ) ) {
				$val = maybe_unserialize( $val );
				if ( is_array( $val ) && ! empty( $val['@type'] ) ) {
					$wpdb->update(
						$wpdb->postmeta,
						[ 'meta_value' => maybe_serialize( $val ) ],
						[ 'meta_id' => $row['meta_id'] ]
					);
					continue;
				}
			}

			if ( ! is_array( $val ) || empty( $val['@type'] ) ) {
				delete_post_meta_by_mid( (int) $row['meta_id'] );
			}
		}
	}
}
