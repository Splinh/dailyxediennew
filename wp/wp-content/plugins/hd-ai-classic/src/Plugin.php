<?php
/**
 * Plugin bootstrap.
 *
 * @package HDAC
 */

namespace HDAC;

defined( 'ABSPATH' ) || exit;

use HDAC\Admin\SettingsPage;
use HDAC\API\GenerateAPI;
use HDAC\API\SettingsAPI;
use HDAC\Settings;

final class Plugin {

	/**
	 * Boot the plugin.
	 */
	public static function boot(): void {
		try {
			new self();
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				add_action(
					'admin_notices',
					static fn() => printf(
						'<div class="notice notice-error"><p><strong>HD AI Classic Error:</strong> %s</p></div>',
						esc_html( $e->getMessage() )
					)
				);
			}
		}
	}

	public function __construct() {
		// Init settings page.
		if ( is_admin() ) {
			new SettingsPage();
		}

		// Register REST API routes.
		add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );

		// Register features on Classic Editor screens.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );

		// Script tag attribute injection.
		add_filter( 'script_loader_tag', [ $this, 'scriptLoaderTag' ], 11, 2 );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . HDAC_PLUGIN_BASENAME, [ $this, 'actionLinks' ] );
	}

	/**
	 * Register REST API routes.
	 */
	public function registerRoutes(): void {
		( new GenerateAPI() )->registerRoutes();
		( new SettingsAPI() )->registerRoutes();
	}

	/**
	 * Enqueue admin assets on Classic Editor screens.
	 */
	public function enqueueAssets( string $hook ): void {
		// Post editor screens.
		$isPostScreen = in_array( $hook, [ 'post.php', 'post-new.php' ], true );

		// Term editor screens.
		$isTermScreen = in_array( $hook, [ 'edit-tags.php', 'term.php' ], true );

		if ( ! $isPostScreen && ! $isTermScreen ) {
			return;
		}

		// Prevent loading if token is empty or no features are enabled (zero footprint on editor screens).
		$token           = Settings::consumerToken();
		$featuresEnabled = Settings::get( 'features_enabled', [] );
		if ( empty( $token ) || empty( $featuresEnabled ) ) {
			return;
		}

		// Only load on Classic Editor (not Block Editor).
		if ( $isPostScreen && $this->isBlockEditor() ) {
			return;
		}

		Asset::enqueueJS( 'editor-ai.js', [ 'jquery-core', 'wp-api-fetch' ], null, true, [ 'module', 'defer' ] );

		$features = [];
		$presets  = [];

		if ( $isPostScreen ) {
			$screen = get_current_screen();

			if ( $screen && post_type_supports( $screen->post_type, 'title' ) && Settings::isFeatureEnabled( 'title' ) ) {
				$features[]       = 'title';
				$presets['title'] = PromptRegistry::presets( 'title' );
			}

			if ( $screen && post_type_supports( $screen->post_type, 'excerpt' ) && Settings::isFeatureEnabled( 'excerpt' ) ) {
				$features[]         = 'excerpt';
				$presets['excerpt'] = PromptRegistry::presets( 'excerpt' );
			}

			if ( $screen && post_type_supports( $screen->post_type, 'editor' ) && Settings::isFeatureEnabled( 'content' ) ) {
				$features[]         = 'content';
				$presets['content'] = PromptRegistry::presets( 'content' );
			}

			if ( $screen && post_type_supports( $screen->post_type, 'editor' ) && Settings::isFeatureEnabled( 'long-content' ) ) {
				$features[]              = 'long-content';
				$presets['long-content'] = PromptRegistry::presets( 'long-content' );
			}

			if ( $screen && post_type_supports( $screen->post_type, 'thumbnail' ) && Settings::isFeatureEnabled( 'image-prompt' ) ) {
				$features[]       = 'image';
				$presets['image'] = PromptRegistry::presets( 'image' );
			}
		}

		if ( $isTermScreen && Settings::isFeatureEnabled( 'term-description' ) ) {
			$features[]                  = 'term-description';
			$presets['term-description'] = PromptRegistry::presets( 'term-description' );
		}

		$handle = Asset::handle( 'editor-ai.js' );
		if ( $handle && $features ) {
			Asset::localize(
				$handle,
				'hdacData',
				[
					'features' => $features,
					'presets'  => $presets,
					'i18n'     => [
						'generate'          => __( 'Generate with AI', 'hd-ai-classic' ),
						'regenerate'        => __( 'Regenerate', 'hd-ai-classic' ),
						'apply'             => __( 'Apply', 'hd-ai-classic' ),
						'close'             => __( 'Close', 'hd-ai-classic' ),
						'generating'        => __( 'Generating...', 'hd-ai-classic' ),
						'error'             => __( 'An error occurred. Please try again.', 'hd-ai-classic' ),
						'noContent'         => __( 'Please add some content first.', 'hd-ai-classic' ),
						'promptLabel'       => __( 'Prompt', 'hd-ai-classic' ),
						'presetLabel'       => __( 'Style', 'hd-ai-classic' ),
						'customLabel'       => __( 'Custom instructions (optional)', 'hd-ai-classic' ),
						'customPlaceholder' => __( 'e.g. Make it professional, translate to English, or rewrite in bullet points...', 'hd-ai-classic' ),
						'resultLabel'       => __( 'Result', 'hd-ai-classic' ),
						'replaceContent'    => __( 'Replace Content', 'hd-ai-classic' ),
						'insertContent'     => __( 'Insert at Cursor', 'hd-ai-classic' ),
						'generateImage'     => __( 'Generate Image', 'hd-ai-classic' ),
						'generatingImage'   => __( 'Generating Image...', 'hd-ai-classic' ),
						'imageSuccess'      => __( 'Featured Image generated and updated successfully!', 'hd-ai-classic' ),
						'imageError'        => __( 'Failed to generate image.', 'hd-ai-classic' ),
						'copyPrompt'        => __( 'Copy Image Prompt', 'hd-ai-classic' ),
						'directImageLabel'  => __( 'Generate Direct Image', 'hd-ai-classic' ),
						'promptOnlyLabel'   => __( 'Generate Prompt Only', 'hd-ai-classic' ),
						'copied'            => __( 'Copied!', 'hd-ai-classic' ),
						'longContent'       => __( 'Long Content', 'hd-ai-classic' ),
						'generateLong'      => __( 'Generate Long Content', 'hd-ai-classic' ),
						'includeImage'      => __( 'Generate featured image after article text', 'hd-ai-classic' ),
						'cancel'            => __( 'Cancel', 'hd-ai-classic' ),
						'cancelled'         => __( 'Generation cancelled.', 'hd-ai-classic' ),
						'outlineProgress'   => __( 'Creating outline...', 'hd-ai-classic' ),
						'outlineReady'      => __( 'Outline ready: %d sections.', 'hd-ai-classic' ),
						'sectionProgress'   => __( 'Writing section %1$d of %2$d...', 'hd-ai-classic' ),
						'sectionComplete'   => __( 'Section %1$d of %2$d complete.', 'hd-ai-classic' ),
						'longContentDone'   => __( 'Long content generated successfully.', 'hd-ai-classic' ),
						'imageWarning'      => __( 'Article generated, but featured image generation failed.', 'hd-ai-classic' ),
					],
				]
			);
		}
	}

	/**
	 * Check if Block Editor is active for the current post.
	 */
	private function isBlockEditor(): bool {
		if ( ! function_exists( 'use_block_editor_for_post' ) ) {
			return false;
		}

		global $post;

		return $post instanceof \WP_Post && use_block_editor_for_post( $post );
	}

	public function actionLinks( array $links ): array {
		$settingsLink = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=hdac-settings' ) ),
			esc_html__( 'Settings', 'hd-ai-classic' )
		);
		array_unshift( $links, $settingsLink );

		return $links;
	}

	/**
	 * Inject extra attributes (defer, module) to hdac script tags.
	 */
	public function scriptLoaderTag( string $tag, string $handle ): string {
		$scripts = wp_scripts();
		$reg     = $scripts->registered[ $handle ] ?? null;

		if ( ! $reg || empty( $reg->extra['hdac'] ) ) {
			return $tag;
		}

		$extras = is_array( $reg->extra['hdac'] )
			? $reg->extra['hdac']
			: explode( ' ', (string) $reg->extra['hdac'] );

		foreach ( $extras as $attr ) {
			$attr = trim( $attr );
			if ( empty( $attr ) ) {
				continue;
			}

			if ( 'module' === $attr ) {
				if ( ! str_contains( $tag, 'type=' ) ) {
					$tag = str_replace( ' src=', ' type="module" src=', $tag );
				}
			} elseif ( ! preg_match( "#\\s{$attr}(=|>|\\s|$)#", $tag ) ) {
				$tag = str_replace( ' src=', " {$attr} src=", $tag );
			}
		}

		return $tag;
	}
}
