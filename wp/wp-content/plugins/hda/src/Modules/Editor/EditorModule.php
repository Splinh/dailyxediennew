<?php
/**
 * Editor module - manages Gutenberg and Classic Editor settings.
 *
 * @package HDAddons\Modules\Editor
 */

namespace HDAddons\Modules\Editor;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class EditorModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'editor';
	}

	public static function title(): string {
		return 'Editor';
	}

	public static function description(): string {
		return 'Classic / Block editor toggle and defaults.';
	}

	public static function group(): string {
		return 'tools';
	}


	// ── Constants ───────────────────────────────────

	public const KEY_CLASSIC          = 'classic_editor';
	public const KEY_EXTRAS_OFF       = 'block_extras_off';
	public const KEY_WOO_BLOCK_STYLES = 'remove_woo_block_styles';
	public const KEY_WOO_ALL_STYLES   = 'remove_woo_all_styles';


	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		$options = self::getCachedOptions();

		// TinyMCE enhancements (extra toolbar buttons and plugins).
		new TinyMCE();

		$isClassic = ! empty( $options[ self::KEY_CLASSIC ] );

		if ( $isClassic ) {
			$this->initClassicMode();
		} elseif ( ! empty( $options[ self::KEY_EXTRAS_OFF ] ) ) {
			$this->disableBlockExtras();
		}

		// WooCommerce optimizations
		if ( class_exists( 'WooCommerce' ) ) {
			if ( ! empty( $options[ self::KEY_WOO_ALL_STYLES ] ) ) {
				add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
				add_action( 'wp_enqueue_scripts', $this->dequeueWooBlockStyles( ... ), 100 );
			} elseif ( ! empty( $options[ self::KEY_WOO_BLOCK_STYLES ] ) ) {
				add_action( 'wp_enqueue_scripts', $this->dequeueWooBlockStyles( ... ), 100 );
			}
		}
	}

	// ── Classic Mode ────────────────────────────────

	private function initClassicMode(): void {
		add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
		add_filter( 'use_widgets_block_editor', '__return_false', 100 );
		add_filter( 'gutenberg_use_widgets_block_editor', '__return_false', 100 );

		add_action( 'wp_enqueue_scripts', $this->dequeueBlockStyles( ... ), 20 );
		add_action( 'wp_loaded', $this->removeDuotoneStyles( ... ) );

		add_action( 'admin_menu', static fn() => remove_submenu_page( 'themes.php', 'site-editor.php' ), 999 );
	}

	private function disableBlockExtras(): void {
		add_filter(
			'block_editor_settings_all',
			static function ( array $settings ): array {
				$settings['fontLibraryEnabled']           = false;
				$settings['enableOpenverseMediaCategory'] = false;

				return $settings;
			},
			100
		);

		add_filter( 'should_load_remote_block_patterns', '__return_false' );
	}

	// ── Dequeue ─────────────────────────────────────

	public function removeDuotoneStyles(): void {
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_stored_styles', 1 );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	}

	public function dequeueBlockStyles(): void {
		$handles = [
			'wp-block-library',
			'wp-block-library-theme',
			'classic-theme-styles',
			'global-styles',
			'wp-emoji-styles',
		];

		foreach ( $handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	public function dequeueWooBlockStyles(): void {
		$handles = [
			'wc-blocks-style',
			'wc-blocks-vendors-style',
			'wc-blocks-packages-style',
		];

		foreach ( $handles as $handle ) {
			wp_dequeue_style( $handle );
		}
	}

	// ── HasSettings ─────────────────────────────────

	public static function saveSettings( array $data ): void {
		$options = self::extractFields(
			$data,
			[
				self::KEY_CLASSIC,
				self::KEY_EXTRAS_OFF,
				self::KEY_WOO_BLOCK_STYLES,
				self::KEY_WOO_ALL_STYLES,
			]
		);

		if ( ! empty( $options[ self::KEY_CLASSIC ] ) ) {
			unset( $options[ self::KEY_EXTRAS_OFF ] );
		}

		self::saveOrRemove( self::optionKey(), $options );
	}
}
