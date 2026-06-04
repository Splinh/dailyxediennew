<?php
/**
 * WordPress Options utility trait.
 *
 * Thin wrappers around WP's option API with consistent interface.
 * Does NOT add extra caching — WordPress already caches options
 * via wp_object_cache / alloptions.
 *
 * @author HD
 */

namespace HDAddons\Traits;

use HDAddons\DB;

\defined( 'ABSPATH' ) || exit;

trait Options {

	/**
	 * Hidden post type for storing long content options.
	 */
	private static string $optionStoragePostType = 'hda_storage';

	// --------------------------------------------------
	// OPTION WRAPPERS
	// --------------------------------------------------

	/**
	 * Get option value.
	 *
	 * WordPress core `get_option()` already caches values in
	 * `wp_object_cache` (and autoloaded options in `alloptions`).
	 * No additional caching layer is needed.
	 *
	 * @param string $option       Option name.
	 * @param mixed  $defaultValue Default value if option doesn't exist.
	 *
	 * @return mixed
	 */
	public static function getOption( string $option, mixed $defaultValue = false ): mixed {
		$option = trim( $option );
		if ( ! $option ) {
			return $defaultValue;
		}

		return get_option( $option, $defaultValue );
	}

	// --------------------------------------------------

	/**
	 * Update option value.
	 *
	 * @param string    $option   Option name.
	 * @param mixed     $newValue New value.
	 * @param int       $_deprecated  @deprecated Unused since 2.x. Kept for backward compatibility.
	 * @param bool|null $autoload Whether to autoload option.
	 *
	 * @return bool
	 */
	public static function updateOption( string $option, mixed $newValue, int $_deprecated = 0, ?bool $autoload = false ): bool {
		$option = trim( $option );
		if ( ! $option ) {
			return false;
		}

		return update_option( $option, $newValue, $autoload );
	}

	// --------------------------------------------------

	/**
	 * Remove option.
	 *
	 * @param string $option Option name.
	 *
	 * @return bool
	 */
	public static function removeOption( string $option ): bool {
		$option = trim( $option );
		if ( ! $option ) {
			return false;
		}

		return delete_option( $option );
	}

	// --------------------------------------------------

	/**
	 * Atomic increment for a numeric option counter.
	 * Uses direct SQL UPDATE to prevent race conditions in high-concurrency scenarios.
	 *
	 * @param string $optionName Option name to increment.
	 * @param int    $amount     Amount to increment by (default 1).
	 *
	 * @return bool True if incremented successfully.
	 */
	public static function incrementCounter( string $optionName, int $amount = 1 ): bool {
		$optionName = trim( $optionName );
		if ( ! $optionName ) {
			return false;
		}

		$db = DB::db();

		// Use atomic SQL UPDATE to prevent race conditions.
		$updated = $db->query(
			$db->prepare(
				"UPDATE {$db->options} SET option_value = option_value + %d WHERE option_name = %s",
				$amount,
				$optionName
			)
		);

		// Query failed — bail out.
		if ( false === $updated ) {
			self::errorLog( '[incrementCounter] SQL query failed for: ' . $optionName );

			return false;
		}

		// If no rows updated, option doesn't exist — create it.
		if ( 0 === $updated ) {
			self::updateOption( $optionName, $amount );
		}

		// Bust WP's internal option cache so next get_option() returns fresh value.
		wp_cache_delete( $optionName, 'options' );

		return true;
	}

	// --------------------------------------------------

	/**
	 * Get theme mod value.
	 *
	 * WordPress `get_theme_mod()` is already cached via `theme_mods_{slug}`
	 * option (autoloaded). No additional caching layer needed.
	 *
	 * @param string|null $modName      Mod name.
	 * @param mixed       $defaultValue Default value.
	 *
	 * @return mixed
	 */
	public static function getThemeMod( ?string $modName, mixed $defaultValue = false ): mixed {
		if ( ! $modName ) {
			return $defaultValue;
		}

		$mod = get_theme_mod( $modName, $defaultValue );

		// Force HTTPS on URLs when site uses SSL.
		if ( is_ssl() && is_string( $mod ) && str_contains( $mod, 'http://' ) ) {
			return str_replace( 'http://', 'https://', $mod );
		}

		return $mod;
	}

	// --------------------------------------------------

	/**
	 * Set theme mod value.
	 *
	 * @param string $modName Mod name.
	 * @param mixed  $value   Value.
	 *
	 * @return void
	 */
	public static function setThemeMod( string $modName, mixed $value ): void {
		if ( ! $modName ) {
			return;
		}

		set_theme_mod( $modName, $value );
	}

	// --------------------------------------------------
	// CUSTOM POST STORAGE (for long content)
	// --------------------------------------------------

	/**
	 * Register the hidden post type for option storage.
	 * Should be called on 'init' hook.
	 *
	 * @return void
	 */
	public static function registerStoragePostType(): void {
		if ( post_type_exists( self::$optionStoragePostType ) ) {
			return;
		}

		register_post_type(
			self::$optionStoragePostType,
			[
				'labels'              => [ 'name' => 'HDA Storage' ],
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => false,
				'delete_with_user'    => false,
				'supports'            => [ 'title', 'editor' ],
			]
		);
	}

	// --------------------------------------------------

	/**
	 * Get stored option content from custom post.
	 *
	 * Uses a lightweight lookup: option stores post ID for O(1) access.
	 * Falls back to WP_Query by slug if ID is missing.
	 *
	 * @param string $optionKey Unique key identifier for the option.
	 *
	 * @return array|null Array with 'ID', 'post_content', 'post_title' or null if not found.
	 */
	public static function getStoredOption( string $optionKey ): ?array {
		if ( ! $optionKey ) {
			return null;
		}

		$optionKey = sanitize_key( $optionKey );

		// Try to get post ID from option first (fast lookup).
		$postId = self::getOption( "hda_so_id_{$optionKey}", 0 );

		if ( $postId > 0 ) {
			$post = get_post( $postId );
			if ( $post && $post->post_type === self::$optionStoragePostType ) {
				return self::formatStoredOptionData( $post );
			}
		}

		// ID is stale or missing — fall back to slug query.
		$post = self::queryStoredOptionPost( $optionKey );

		if ( ! $post ) {
			// Mark as not found to avoid repeated queries.
			self::updateOption( "hda_so_id_{$optionKey}", -1 );

			return null;
		}

		// Cache the post ID for fast future lookups.
		self::updateOption( "hda_so_id_{$optionKey}", $post->ID );

		return self::formatStoredOptionData( $post );
	}

	// --------------------------------------------------

	/**
	 * Update or create stored option content.
	 *
	 * @param string $optionKey   Unique key identifier for the option.
	 * @param string $content     Content to store.
	 * @param string $contentType Content type hint ('css', 'js', 'html', 'text').
	 *
	 * @return int|\WP_Error Post ID on success, WP_Error on failure.
	 */
	public static function updateStoredOption( string $optionKey, string $content, string $contentType = 'text' ): int|\WP_Error {
		if ( ! $optionKey ) {
			return new \WP_Error( 'invalid_key', __( 'Option key is required.', 'hda' ) );
		}

		$optionKey = sanitize_key( $optionKey );

		// Ensure post type is registered.
		self::registerStoragePostType();

		if ( self::isRawHtmlContentType( $contentType ) && ! current_user_can( 'unfiltered_html' ) ) {
			return new \WP_Error(
				'raw_html_forbidden',
				__( 'You do not have permission to save raw HTML or scripts.', 'hda' )
			);
		}

		// Sanitize content based on type.
		$content = self::sanitizeStoredContent( $content, $contentType );

		// Check content size limit (1MB).
		if ( strlen( $content ) > 1048576 ) {
			return new \WP_Error(
				'content_too_large',
				__( 'Content is too large. Maximum size is 1MB.', 'hda' )
			);
		}

		$existingPost = self::getStoredOption( $optionKey );

		$postData = [
			'post_type'    => self::$optionStoragePostType,
			'post_status'  => 'publish',
			'post_content' => $content,
			'post_excerpt' => $contentType,
		];

		if ( $existingPost && isset( $existingPost['ID'] ) ) {
			$postData['ID'] = $existingPost['ID'];
			$result         = wp_update_post( wp_slash( $postData ), true );
		} else {
			$postData['post_title'] = $optionKey;
			$postData['post_name']  = $optionKey;

			$result = wp_insert_post( wp_slash( $postData ), true );

			if ( ! is_wp_error( $result ) ) {
				self::updateOption( "hda_so_id_{$optionKey}", $result, 0, false );
			}
		}

		return $result;
	}

	// --------------------------------------------------

	/**
	 * Get stored option content only.
	 *
	 * @param string $optionKey Unique key identifier.
	 *
	 * @return string Content or empty string.
	 */
	public static function getStoredOptionContent( string $optionKey ): string {
		$data = self::getStoredOption( $optionKey );

		return $data['post_content'] ?? '';
	}

	// --------------------------------------------------

	/**
	 * Delete stored option.
	 *
	 * @param string $optionKey Unique key identifier.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function deleteStoredOption( string $optionKey ): bool {
		if ( ! $optionKey ) {
			return false;
		}

		$optionKey = sanitize_key( $optionKey );
		$data      = self::getStoredOption( $optionKey );

		if ( ! $data || ! isset( $data['ID'] ) ) {
			return false;
		}

		$deleted = wp_delete_post( $data['ID'], true );

		if ( $deleted ) {
			self::removeOption( "hda_so_id_{$optionKey}" );
		}

		return (bool) $deleted;
	}

	// --------------------------------------------------
	// PRIVATE HELPERS
	// --------------------------------------------------

	/**
	 * Query stored option post by slug.
	 *
	 * @param string $optionKey
	 *
	 * @return \WP_Post|null
	 */
	private static function queryStoredOptionPost( string $optionKey ): ?\WP_Post {
		$query = new \WP_Query(
			[
				'post_type'              => self::$optionStoragePostType,
				'post_status'            => 'any',
				'name'                   => $optionKey,
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'cache_results'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'lazy_load_term_meta'    => false,
			]
		);

		return $query->post ?? null;
	}

	// --------------------------------------------------

	/**
	 * Format post data for stored option.
	 *
	 * @param \WP_Post|null $post
	 *
	 * @return array
	 */
	private static function formatStoredOptionData( ?\WP_Post $post ): array {
		if ( ! $post ) {
			return [];
		}

		return [
			'ID'           => $post->ID,
			'post_title'   => $post->post_title,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
		];
	}

	// --------------------------------------------------

	/**
	 * Sanitize content based on type.
	 *
	 * @param string $content
	 * @param string $contentType
	 *
	 * @return string
	 */
	private static function sanitizeStoredContent( string $content, string $contentType ): string {
		return match ( $contentType ) {
			'css', 'text/css' => self::extractCss( $content ),
			'js', 'javascript', 'text/javascript' => self::extractJS( $content ),
			'hda_raw_html' => $content,
			'html', 'text/html' => wp_kses_post( $content ),
			default => $content,
		};
	}

	// --------------------------------------------------

	/**
	 * Check if content type is raw html.
	 *
	 * @param string $contentType

	 * @return bool
	 */
	private static function isRawHtmlContentType( string $contentType ): bool {
		return 'hda_raw_html' === $contentType;
	}
}
