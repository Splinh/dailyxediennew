<?php
/**
 * WordPress Performance - Heartbeat, Embeds & Core Cleanup.
 *
 * Reads consolidated keys from Optimize:
 * - KEY_HEARTBEAT (preset: default|reduced|disabled)
 * - KEY_CORE_CLEANUP (bool: embeds + wp_head cleanup)
 *
 * @package HDAddons\Modules\Optimize
 */

namespace HDAddons\Modules\Optimize;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class Performance {

	/**
	 * Module options.
	 */
	private array $options;

	// ------------------------------------------------------

	public function __construct() {
		$this->options = Helper::getOption( OptimizeModule::optionKey(), [] );

		// Heartbeat control
		$this->initHeartbeat();

		// Core cleanup (embeds + wp_head)
		$this->initCoreCleanup();
	}

	// ------------------------------------------------------
	// HEARTBEAT CONTROL
	// ------------------------------------------------------

	/**
	 * Initialize Heartbeat modifications based on preset.
	 *
	 * @return void
	 */
	private function initHeartbeat(): void {
		$preset = $this->options[ OptimizeModule::KEY_HEARTBEAT ] ?? 'default';

		if ( 'default' === $preset ) {
			return; // No modifications
		}

		if ( 'disabled' === $preset ) {
			add_action( 'init', $this->disableHeartbeat( ... ), 1 );
			return;
		}

		// 'reduced' preset: 30s interval, post-edit screen only
		add_action( 'init', $this->limitHeartbeatToPostEdit( ... ), 1 );
		add_filter( 'heartbeat_settings', $this->setReducedFrequency( ... ) );
	}

	/**
	 * Disable Heartbeat completely.
	 *
	 * @return void
	 */
	public function disableHeartbeat(): void {
		wp_deregister_script( 'heartbeat' );
		wp_deregister_script( 'wp-auth-check' );
	}

	/**
	 * Limit Heartbeat to post-edit screens only.
	 *
	 * @return void
	 */
	public function limitHeartbeatToPostEdit(): void {
		$is_admin     = is_admin();
		$is_post_edit = $is_admin && isset( $GLOBALS['pagenow'] ) &&
						in_array( $GLOBALS['pagenow'], [ 'post.php', 'post-new.php' ], true );

		if ( ! $is_post_edit ) {
			wp_deregister_script( 'heartbeat' );
			wp_deregister_script( 'wp-auth-check' );
		}
	}

	/**
	 * Set reduced Heartbeat frequency (30s).
	 *
	 * @param array $settings Heartbeat settings.
	 *
	 * @return array
	 */
	public function setReducedFrequency( array $settings ): array {
		$settings['interval'] = 30;

		return $settings;
	}

	// ------------------------------------------------------
	// CORE CLEANUP (Embeds + wp_head)
	// ------------------------------------------------------

	/**
	 * Initialize Core Cleanup — handles both embeds and wp_head cleanup.
	 *
	 * @return void
	 */
	private function initCoreCleanup(): void {
		if ( empty( $this->options[ OptimizeModule::KEY_CORE_CLEANUP ] ) ) {
			return;
		}

		// ── Embeds ──
		add_action( 'wp_enqueue_scripts', $this->dequeueEmbedScripts( ... ), 9999 );
		add_action( 'init', $this->removeEmbedActions( ... ), 9998 );
		add_action( 'init', $this->disableEmbeds( ... ), 9999 );

		// ── wp_head cleanup ──
		add_action( 'init', $this->runCleanup( ... ), 1 );
	}

	/**
	 * Remove oEmbed head actions.
	 * Called on 'init' hook to ensure actions are registered before removal.
	 *
	 * @return void
	 */
	public function removeEmbedActions(): void {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	}

	/**
	 * Dequeue embed scripts.
	 * Called on 'wp_enqueue_scripts' with high priority.
	 *
	 * @return void
	 */
	public function dequeueEmbedScripts(): void {
		wp_dequeue_script( 'wp-embed' );
	}

	/**
	 * Disable WordPress Embeds.
	 *
	 * @return void
	 */
	public function disableEmbeds(): void {
		// Remove the REST API endpoint
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );

		// Turn off oEmbed auto discovery
		add_filter( 'embed_oembed_discover', '__return_false' );

		// Don't filter oEmbed results
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );

		// Remove all embeds rewrite rules
		add_filter( 'rewrite_rules_array', $this->disableEmbedsRewrites( ... ) );

		// Remove filter of the oEmbed result before any HTTP requests are made
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
	}

	/**
	 * Remove embed rewrites.
	 *
	 * @param array $rules Rewrite rules.
	 *
	 * @return array
	 */
	public function disableEmbedsRewrites( array $rules ): array {
		foreach ( $rules as $rule => $rewrite ) {
			if ( str_contains( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}

		return $rules;
	}

	/**
	 * Run WordPress cleanup operations.
	 * Removes unnecessary features and actions.
	 *
	 * @return void
	 */
	public function runCleanup(): void {
		// wp_head cleanup
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );

		// All actions related to emojis
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

		// Staticize emoji
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

		// Remove the wp-json header from WordPress
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );

		// Remove id li navigation
		add_filter( 'nav_menu_item_id', '__return_empty_string', 10, 3 );

		// Remove DNS prefetch for s.w.org (emoji CDN)
		add_filter( 'emoji_svg_url', '__return_false' );

		// Remove WP version from scripts and styles
		if ( ! is_admin() ) {
			add_filter( 'style_loader_src', Helper::removeVersionQuery( ... ), 10 );
			add_filter( 'script_loader_src', Helper::removeVersionQuery( ... ), 10 );
		}
	}
}
