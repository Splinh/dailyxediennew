<?php
/**
 * Cloudflare Integration — Cache purge and status detection.
 *
 * Provides Cloudflare API v4 integration for the ImageConverter module:
 * - Cache purge after batch/auto conversion (so visitors see next-gen images immediately)
 * - Cloudflare presence detection (is the site behind CF?)
 * - Cloudflare Polish detection (warns about potential conflicts)
 *
 * @package HDAddons\Modules\ImageConverter
 * @author  HD
 */

namespace HDAddons\Modules\ImageConverter;

use HDAddons\Helper;
use HDAddons\Modules\Security\AccessControl;

\defined( 'ABSPATH' ) || exit;

final class CloudflareIntegration {

	/**
	 * Cloudflare API v4 base URL.
	 */
	private const API_BASE = 'https://api.cloudflare.com/client/v4';

	/**
	 * Transient key for caching CF status check.
	 */
	private const STATUS_CACHE_KEY = 'hda_imgconv_cf_status';

	/**
	 * Cache TTL for CF status check (1 hour).
	 */
	private const STATUS_CACHE_TTL = HOUR_IN_SECONDS;

	// ─── API Methods ────────────────────────────────────

	/**
	 * Purge entire Cloudflare cache for the zone.
	 *
	 * Best used after batch conversion completes — ensures visitors
	 * immediately see next-gen format images via rewrite rules.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function purgeAll(): array {
		$credentials = self::getCredentials();

		if ( ! $credentials ) {
			return [
				'success' => false,
				'message' => 'Cloudflare credentials not configured.',
			];
		}

		$response = self::apiRequest(
			"zones/{$credentials['zone_id']}/purge_cache",
			[ 'purge_everything' => true ]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
			];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['success'] ) ) {
			Helper::errorLog( '[HDA ImageConverter] Cloudflare cache purged successfully.' );

			return [
				'success' => true,
				'message' => 'Cloudflare cache purged successfully.',
			];
		}

		$error = $body['errors'][0]['message'] ?? 'Unknown Cloudflare API error.';
		Helper::errorLog( '[HDA ImageConverter] Cloudflare purge failed: ' . $error );

		return [
			'success' => false,
			'message' => $error,
		];
	}

	/**
	 * Purge specific URLs from Cloudflare cache.
	 *
	 * Used after auto-convert to purge only the converted image URLs.
	 * CF API limit: max 30 URLs per request.
	 *
	 * @param array<string> $urls Absolute URLs to purge (max 30).
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function purgeUrls( array $urls ): array {
		if ( empty( $urls ) ) {
			return [
				'success' => true,
				'message' => 'No URLs to purge.',
			];
		}

		$credentials = self::getCredentials();

		if ( ! $credentials ) {
			return [
				'success' => false,
				'message' => 'Cloudflare credentials not configured.',
			];
		}

		// CF API limit: 30 URLs per request
		$chunks = array_chunk( $urls, 30 );

		foreach ( $chunks as $chunk ) {
			$response = self::apiRequest(
				"zones/{$credentials['zone_id']}/purge_cache",
				[ 'files' => $chunk ]
			);

			if ( is_wp_error( $response ) ) {
				return [
					'success' => false,
					'message' => $response->get_error_message(),
				];
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $body['success'] ) ) {
				$error = $body['errors'][0]['message'] ?? 'Unknown Cloudflare API error.';

				return [
					'success' => false,
					'message' => $error,
				];
			}
		}

		return [
			'success' => true,
			'message' => sprintf( 'Purged %d URL(s) from Cloudflare cache.', count( $urls ) ),
		];
	}

	/**
	 * Verify Cloudflare API credentials are valid.
	 *
	 * Makes a lightweight call to verify the token and zone.
	 *
	 * @return array{success: bool, message: string, zone_name?: string}
	 */
	public static function verifyCredentials(): array {
		$credentials = self::getCredentials();

		if ( ! $credentials ) {
			return [
				'success' => false,
				'message' => 'Cloudflare Zone ID or API Token not configured.',
			];
		}

		$response = self::apiRequest( "zones/{$credentials['zone_id']}", method: 'GET' );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
			];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['success'] ) && ! empty( $body['result']['name'] ) ) {
			return [
				'success'   => true,
				'message'   => 'Credentials valid.',
				'zone_name' => $body['result']['name'],
			];
		}

		$error = $body['errors'][0]['message'] ?? 'Invalid Zone ID or API Token.';

		return [
			'success' => false,
			'message' => $error,
		];
	}

	// ─── Status Detection ───────────────────────────────

	/**
	 * Get comprehensive Cloudflare status for the admin UI.
	 *
	 * @return array{
	 *     is_cloudflare: bool,
	 *     has_credentials: bool,
	 *     credentials_valid: bool|null,
	 *     zone_name: string|null,
	 *     auto_purge: bool
	 * }
	 */
	public static function getStatus(): array {
		$options = ImageConverter::getOptions();

		$status = [
			'is_cloudflare'     => AccessControl::isCloudflare(),
			'has_credentials'   => self::hasCredentials(),
			'credentials_valid' => null,
			'zone_name'         => null,
			'auto_purge'        => ! empty( $options[ ImageConverter::KEY_CF_AUTO_PURGE ] ),
		];

		// Only verify credentials if they exist (cached to avoid API spam)
		if ( $status['has_credentials'] ) {
			$cached = get_transient( self::STATUS_CACHE_KEY );

			if ( $cached !== false ) {
				$status['credentials_valid'] = $cached['valid'] ?? false;
				$status['zone_name']         = $cached['zone_name'] ?? null;
			} else {
				$verify = self::verifyCredentials();

				$status['credentials_valid'] = $verify['success'];
				$status['zone_name']         = $verify['zone_name'] ?? null;

				set_transient(
					self::STATUS_CACHE_KEY,
					[
						'valid'     => $verify['success'],
						'zone_name' => $verify['zone_name'] ?? null,
					],
					self::STATUS_CACHE_TTL
				);
			}
		}

		return $status;
	}

	/**
	 * Clear the cached CF status (e.g., after saving new credentials).
	 *
	 * @return void
	 */
	public static function clearStatusCache(): void {
		delete_transient( self::STATUS_CACHE_KEY );
	}

	// ─── Hook: Purge After Events ───────────────────────

	/**
	 * Purge Cloudflare cache after batch conversion completes.
	 *
	 * Called from BatchProcessor when all items are processed.
	 * Only fires if auto-purge is enabled and credentials are configured.
	 *
	 * @return void
	 */
	public static function onBatchComplete(): void {
		if ( ! self::shouldAutoPurge() ) {
			return;
		}

		$result = self::purgeAll();

		if ( $result['success'] ) {
			Helper::errorLog( '[HDA ImageConverter] Cloudflare cache purged after batch completion.' );
		}
	}

	/**
	 * Purge specific image URLs after auto-conversion.
	 *
	 * Resolves file paths to public URLs and purges them from CF cache.
	 *
	 * @param array<string> $filePaths Relative file paths (e.g., '2026/03/photo.jpg').
	 *
	 * @return void
	 */
	public static function onAutoConvertComplete( array $filePaths ): void {
		if ( ! self::shouldAutoPurge() || empty( $filePaths ) ) {
			return;
		}

		$uploadDir = wp_upload_dir();
		$baseUrl   = rtrim( $uploadDir['baseurl'], '/' );

		$urls = [];
		foreach ( $filePaths as $file ) {
			// Purge the original image URL (CF may have it cached)
			$urls[] = $baseUrl . '/' . ltrim( $file, '/' );
		}

		if ( ! empty( $urls ) ) {
			self::purgeUrls( $urls );
		}
	}

	// ─── Private Helpers ────────────────────────────────

	/**
	 * Check if auto-purge should fire.
	 *
	 * @return bool
	 */
	private static function shouldAutoPurge(): bool {
		$options = ImageConverter::getOptions();

		return ! empty( $options[ ImageConverter::KEY_CF_AUTO_PURGE ] )
			&& self::hasCredentials();
	}

	/**
	 * Check if CF credentials exist (non-empty).
	 *
	 * @return bool
	 */
	private static function hasCredentials(): bool {
		$credentials = self::getCredentials();

		return $credentials !== null;
	}

	/**
	 * Get Cloudflare credentials from options.
	 *
	 * @return array{zone_id: string, api_token: string}|null
	 */
	private static function getCredentials(): ?array {
		$options = ImageConverter::getOptions();

		$zoneId   = trim( $options[ ImageConverter::KEY_CF_ZONE_ID ] ?? '' );
		$apiToken = trim( $options[ ImageConverter::KEY_CF_API_TOKEN ] ?? '' );

		if ( empty( $zoneId ) || empty( $apiToken ) ) {
			return null;
		}

		return [
			'zone_id'   => $zoneId,
			'api_token' => $apiToken,
		];
	}

	/**
	 * Make a request to the Cloudflare API v4.
	 *
	 * @param string      $endpoint API endpoint (after /client/v4/).
	 * @param array|null  $body     Request body (for POST).
	 * @param string      $method   HTTP method.
	 *
	 * @return array|\WP_Error
	 */
	private static function apiRequest( string $endpoint, ?array $body = null, string $method = 'POST' ): array|\WP_Error {
		$credentials = self::getCredentials();

		if ( ! $credentials ) {
			return new \WP_Error( 'cf_no_credentials', 'Cloudflare credentials not configured.' );
		}

		$args = [
			'method'  => $method,
			'timeout' => 15,
			'headers' => [
				'Authorization' => 'Bearer ' . $credentials['api_token'],
				'Content-Type'  => 'application/json',
			],
		];

		if ( $body !== null && $method === 'POST' ) {
			$args['body'] = wp_json_encode( $body );
		}

		return wp_remote_request( self::API_BASE . "/{$endpoint}", $args );
	}
}
