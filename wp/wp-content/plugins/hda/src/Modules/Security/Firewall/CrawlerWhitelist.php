<?php
/**
 * Crawler Whitelist — auto-whitelists legitimate search engine bots and CDN IPs.
 *
 * Fetches and caches official IP ranges from Google, Bing, and Cloudflare.
 * Verified crawlers bypass the Firewall pipeline entirely (no false positives).
 *
 * Verification relies on official published IP range lists rather than reverse DNS
 * (which is slower and unreliable under high load).
 *
 * @package HDAddons\Modules\Security\Firewall
 * @author  HD
 */

namespace HDAddons\Modules\Security\Firewall;

use HDAddons\Core\RateLimitStorage;
use HDAddons\DB;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class CrawlerWhitelist {

	/**
	 * Transient cache keys.
	 */
	private const CACHE_GOOGLE     = 'hda_fw_googlebot_ips';
	private const CACHE_BING       = 'hda_fw_bingbot_ips';
	private const CACHE_CLOUDFLARE = 'hda_fw_cloudflare_ips';

	/**
	 * Cache TTL (24 hours).
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Official API endpoints for IP ranges.
	 */
	private const SOURCES = [
		'google' => [
			'url'        => 'https://developers.google.com/static/search/apis/ipranges/googlebot.json',
			'cache_key'  => self::CACHE_GOOGLE,
			'ua_pattern' => 'googlebot|apis-google|adsbot-google|mediapartners-google|google-inspectiontool',
		],
		'bing'   => [
			'url'        => 'https://www.bing.com/toolbox/bingbot.json',
			'cache_key'  => self::CACHE_BING,
			'ua_pattern' => 'bingbot|msnbot|bingpreview|adidxbot',
		],
	];

	/**
	 * Cloudflare IP list URLs (plain text, one CIDR per line).
	 */
	private const CLOUDFLARE_URLS = [
		'https://www.cloudflare.com/ips-v4/',
		'https://www.cloudflare.com/ips-v6/',
	];

	/**
	 * Per-request cache of loaded IP ranges.
	 *
	 * @var array<string, string[]>|null
	 */
	private static ?array $ipRangesCache = null;

	// ══════════════════════════════════════════════════
	// Verification API
	// ══════════════════════════════════════════════════

	/**
	 * Check if an IP + User Agent belongs to a verified crawler.
	 *
	 * Returns the crawler name if verified, null otherwise.
	 *
	 * @param string $ip        Client IP address.
	 * @param string $userAgent User-Agent string.
	 *
	 * @return string|null Crawler name ('google', 'bing', 'cloudflare') or null.
	 */
	public function isVerifiedCrawler( string $ip, string $userAgent ): ?string {
		if ( '' === $ip ) {
			return null;
		}

		// ── Per-request micro-cache (avoid N checks per request) ──
		$cached = RateLimitStorage::getString( $ip, 'crawler' );
		if ( null !== $cached ) {
			return '' !== $cached ? $cached : null; // Empty string = negative cache.
		}

		$userAgentLower = strtolower( $userAgent );

		// ── Check Google/Bing by UA pattern + IP range ──
		foreach ( self::SOURCES as $name => $source ) {
			if ( ! preg_match( '/' . $source['ua_pattern'] . '/i', $userAgentLower ) ) {
				continue;
			}

			$ranges = $this->getIpRanges( $name );
			if ( ! empty( $ranges ) && Helper::ipMatchesAny( $ip, $ranges ) ) {
				RateLimitStorage::setString( $ip, 'crawler', $name, HOUR_IN_SECONDS );
				return $name;
			}
		}

		// ── Check Cloudflare (by IP only, no UA) ──
		$cfRanges = $this->getCloudflareRanges();
		if ( ! empty( $cfRanges ) && Helper::ipMatchesAny( $ip, $cfRanges ) ) {
			RateLimitStorage::setString( $ip, 'crawler', 'cloudflare', HOUR_IN_SECONDS );
			return 'cloudflare';
		}

		// Not a verified crawler — cache negative result to avoid re-checking.
		RateLimitStorage::setString( $ip, 'crawler', '', 10 * MINUTE_IN_SECONDS );

		return null;
	}

	/**
	 * Check if an IP belongs to Cloudflare.
	 *
	 * Useful for trusting CF-Connecting-IP header.
	 *
	 * @param string $ip Client IP address.
	 *
	 * @return bool
	 */
	public function isCloudflareIp( string $ip ): bool {
		$ranges = $this->getCloudflareRanges();

		return ! empty( $ranges ) && Helper::ipMatchesAny( $ip, $ranges );
	}

	// ══════════════════════════════════════════════════
	// IP range fetching
	// ══════════════════════════════════════════════════

	/**
	 * Get IP ranges for a specific crawler (cached).
	 *
	 * @param string $name Crawler name ('google', 'bing').
	 *
	 * @return string[] CIDR ranges.
	 */
	private function getIpRanges( string $name ): array {
		// In-memory cache.
		if ( isset( self::$ipRangesCache[ $name ] ) ) {
			return self::$ipRangesCache[ $name ];
		}

		$source = self::SOURCES[ $name ] ?? null;
		if ( ! $source ) {
			return [];
		}

		// Transient cache.
		$cached = get_transient( $source['cache_key'] );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			self::$ipRangesCache[ $name ] = $cached;

			return $cached;
		}

		// Fetch from API.
		$ranges = $this->fetchJsonIpRanges( $source['url'] );

		if ( ! empty( $ranges ) ) {
			set_transient( $source['cache_key'], $ranges, self::CACHE_TTL );
			self::$ipRangesCache[ $name ] = $ranges;
		}

		return $ranges;
	}

	/**
	 * Get Cloudflare IP ranges (cached).
	 *
	 * @return string[] CIDR ranges.
	 */
	private function getCloudflareRanges(): array {
		if ( isset( self::$ipRangesCache['cloudflare'] ) ) {
			return self::$ipRangesCache['cloudflare'];
		}

		$cached = get_transient( self::CACHE_CLOUDFLARE );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			self::$ipRangesCache['cloudflare'] = $cached;

			return $cached;
		}

		$ranges = $this->fetchCloudflareRanges();

		if ( ! empty( $ranges ) ) {
			set_transient( self::CACHE_CLOUDFLARE, $ranges, self::CACHE_TTL );
			self::$ipRangesCache['cloudflare'] = $ranges;
		}

		return $ranges;
	}

	// ══════════════════════════════════════════════════
	// HTTP fetchers
	// ══════════════════════════════════════════════════

	/**
	 * Fetch IP ranges from a JSON API (Google/Bing format).
	 *
	 * Both Google and Bing publish ranges in format:
	 * { "prefixes": [{ "ipv4Prefix": "x.x.x.x/y" }, { "ipv6Prefix": "::1/128" }] }
	 *
	 * @param string $url API endpoint.
	 *
	 * @return string[] CIDR ranges.
	 */
	private function fetchJsonIpRanges( string $url ): array {
		$response = wp_remote_get(
			$url,
			[
				'timeout'   => 15,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			Helper::errorLog( "[HDA CrawlerWhitelist] Failed to fetch: {$url} — " . $response->get_error_message() );

			return [];
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			Helper::errorLog( "[HDA CrawlerWhitelist] HTTP {$code} from: {$url}" );

			return [];
		}

		$body = wp_remote_retrieve_body( $response );

		try {
			$data = json_decode( $body, true, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $e ) {
			Helper::errorLog( "[HDA CrawlerWhitelist] Invalid JSON from: {$url}" );

			return [];
		}

		$ranges   = [];
		$prefixes = $data['prefixes'] ?? [];

		foreach ( $prefixes as $entry ) {
			if ( ! empty( $entry['ipv4Prefix'] ) ) {
				$ranges[] = $entry['ipv4Prefix'];
			}
			if ( ! empty( $entry['ipv6Prefix'] ) ) {
				$ranges[] = $entry['ipv6Prefix'];
			}
		}

		return $ranges;
	}

	/**
	 * Fetch Cloudflare IP ranges from plain text endpoints.
	 *
	 * @return string[] CIDR ranges.
	 */
	private function fetchCloudflareRanges(): array {
		$ranges = [];

		foreach ( self::CLOUDFLARE_URLS as $url ) {
			$response = wp_remote_get(
				$url,
				[
					'timeout'   => 10,
					'sslverify' => true,
				]
			);

			if ( is_wp_error( $response ) ) {
				Helper::errorLog( '[HDA CrawlerWhitelist] Failed to fetch CF IPs: ' . $response->get_error_message() );
				continue;
			}

			$body  = wp_remote_retrieve_body( $response );
			$lines = array_filter( array_map( 'trim', explode( "\n", $body ) ) );

			foreach ( $lines as $line ) {
				// Basic validation: must contain a slash (CIDR).
				if ( str_contains( $line, '/' ) ) {
					$ranges[] = $line;
				}
			}
		}

		return $ranges;
	}

	// ══════════════════════════════════════════════════
	// Sync (Cron)
	// ══════════════════════════════════════════════════

	/**
	 * Refresh all cached IP ranges (called via cron).
	 *
	 * @return void
	 */
	public static function syncAll(): void {
		$instance = new self();

		// Clear caches to force re-fetch.
		delete_transient( self::CACHE_GOOGLE );
		delete_transient( self::CACHE_BING );
		delete_transient( self::CACHE_CLOUDFLARE );
		self::$ipRangesCache = null;

		// Re-fetch all.
		foreach ( array_keys( self::SOURCES ) as $name ) {
			$ranges = $instance->getIpRanges( $name );
			if ( ! empty( $ranges ) ) {
				Helper::errorLog(
					sprintf(
						'[HDA CrawlerWhitelist] Synced %s: %d IP ranges',
						$name,
						count( $ranges )
					)
				);
			}
		}

		$cfRanges = $instance->getCloudflareRanges();
		if ( ! empty( $cfRanges ) ) {
			Helper::errorLog(
				sprintf(
					'[HDA CrawlerWhitelist] Synced cloudflare: %d IP ranges',
					count( $cfRanges )
				)
			);
		}
	}

	/**
	 * Clear all cached IP ranges and verification results.
	 *
	 * IP range transients (Google, Bing, Cloudflare) are deleted directly.
	 * Per-IP crawler verification results stored in `hda_rate_limits` (MySQL fallback)
	 * are purged by action key. Sites using object cache will expire via TTL.
	 *
	 * @return void
	 */
	public static function clearCache(): void {
		delete_transient( self::CACHE_GOOGLE );
		delete_transient( self::CACHE_BING );
		delete_transient( self::CACHE_CLOUDFLARE );
		self::$ipRangesCache = null;

		// Clear verified IP caches from RateLimitStorage custom table.
		if ( ! wp_using_ext_object_cache() && DB::tableExists( RateLimitStorage::TABLE_NAME ) ) {
			$db    = DB::db();
			$table = DB::tableNameFull( RateLimitStorage::TABLE_NAME );
			$db->delete( $table, [ 'action' => 'crawler' ], [ '%s' ] );
		}
	}
}
