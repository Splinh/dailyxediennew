<?php
/**
 * IP Reputation — lightweight IP abuse checking.
 *
 * Checks IPs against publicly available blocklists (no API key required).
 * Uses a local cache synced daily from free threat feeds:
 * - Spamhaus DROP/EDROP (known hijacked/spam netblocks)
 * - Emerging Threats compromised IPs
 *
 * @package HDAddons\Modules\Security\Firewall
 * @author  HD
 */

namespace HDAddons\Modules\Security\Firewall;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class IpReputation {

	/**
	 * Transient keys for cached blocklists.
	 */
	private const CACHE_DROP  = 'hda_fw_blocklist_drop';
	private const CACHE_EDROP = 'hda_fw_blocklist_edrop';
	private const CACHE_ET    = 'hda_fw_blocklist_et';

	/**
	 * Cache TTL (24 hours).
	 */
	private const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Blocklist sources (free, no API key).
	 */
	private const SOURCES = [
		'drop'           => [
			'url'       => 'https://www.spamhaus.org/drop/drop.txt',
			'cache_key' => self::CACHE_DROP,
			'type'      => 'cidr',
		],
		'edrop'          => [
			'url'       => 'https://www.spamhaus.org/drop/edrop.txt',
			'cache_key' => self::CACHE_EDROP,
			'type'      => 'cidr',
		],
		'et_compromised' => [
			'url'       => 'https://rules.emergingthreats.net/blockrules/compromised-ips.txt',
			'cache_key' => self::CACHE_ET,
			'type'      => 'ip',
		],
	];

	/**
	 * Per-request cache of loaded blocklists.
	 *
	 * @var array<string, string[]>|null
	 */
	private static ?array $listCache = null;

	// ══════════════════════════════════════════════════
	// Checking API
	// ══════════════════════════════════════════════════

	/**
	 * Check if an IP is on a known abuse list.
	 *
	 * @param string $ip Client IP address.
	 *
	 * @return array|null Null if clean, or ['source' => string, 'type' => string] if found.
	 */
	public function check( string $ip ): ?array {
		if ( '' === $ip ) {
			return null;
		}

		// Skip private/reserved IPs.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return null;
		}

		// ── Check CIDR-based lists (DROP, EDROP) ────
		foreach ( [ 'drop', 'edrop' ] as $name ) {
			$ranges = $this->getList( $name );
			if ( ! empty( $ranges ) && Helper::ipMatchesAny( $ip, $ranges ) ) {
				return [
					'source' => $name,
					'type'   => 'known_abuser',
				];
			}
		}

		// ── Check IP-based lists (ET compromised) ───
		$etList = $this->getList( 'et_compromised' );
		if ( ! empty( $etList ) && in_array( $ip, $etList, true ) ) {
			return [
				'source' => 'et_compromised',
				'type'   => 'compromised',
			];
		}

		return null;
	}

	/**
	 * Quick boolean check.
	 *
	 * @param string $ip Client IP.
	 *
	 * @return bool True if the IP is on a known blocklist.
	 */
	public function isKnownAbuser( string $ip ): bool {
		return null !== $this->check( $ip );
	}

	// ══════════════════════════════════════════════════
	// List management
	// ══════════════════════════════════════════════════

	/**
	 * Get a specific blocklist (cached).
	 *
	 * @param string $name List name (drop, edrop, et_compromised).
	 *
	 * @return string[] CIDR ranges or IP addresses.
	 */
	private function getList( string $name ): array {
		// In-memory cache.
		if ( isset( self::$listCache[ $name ] ) ) {
			return self::$listCache[ $name ];
		}

		$source = self::SOURCES[ $name ] ?? null;
		if ( ! $source ) {
			return [];
		}

		// Transient cache.
		$cached = get_transient( $source['cache_key'] );
		if ( is_array( $cached ) ) {
			self::$listCache[ $name ] = $cached;

			return $cached;
		}

		// Fetch from source.
		$list = $this->fetchList( $source['url'], $source['type'] );

		if ( ! empty( $list ) ) {
			set_transient( $source['cache_key'], $list, self::CACHE_TTL );
		}

		self::$listCache[ $name ] = $list;

		return $list;
	}

	/**
	 * Fetch a blocklist from a remote URL.
	 *
	 * @param string $url  Remote URL.
	 * @param string $type 'cidr' or 'ip' — determines parsing strategy.
	 *
	 * @return string[]
	 */
	private function fetchList( string $url, string $type ): array {
		$response = wp_remote_get(
			$url,
			[
				'timeout'   => 15,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			Helper::errorLog( "[HDA IpReputation] Failed to fetch: {$url} — " . $response->get_error_message() );

			return [];
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			Helper::errorLog( "[HDA IpReputation] HTTP {$code} from: {$url}" );

			return [];
		}

		$body  = wp_remote_retrieve_body( $response );
		$lines = explode( "\n", $body );
		$list  = [];

		foreach ( $lines as $line ) {
			$line = trim( $line );

			// Skip comments and empty lines.
			if ( '' === $line || str_starts_with( $line, '#' ) || str_starts_with( $line, ';' ) ) {
				continue;
			}

			if ( 'cidr' === $type ) {
				// Spamhaus DROP format: "x.x.x.x/y ; SBLxxxxxxx"
				$parts = explode( ';', $line );
				$cidr  = trim( $parts[0] );
				if ( str_contains( $cidr, '/' ) ) {
					$list[] = $cidr;
				}
			} else {
				// Plain IP list.
				$ip = $line;
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					$list[] = $ip;
				}
			}
		}

		return $list;
	}

	// ══════════════════════════════════════════════════
	// Sync (Cron)
	// ══════════════════════════════════════════════════

	/**
	 * Refresh all cached blocklists (called via cron).
	 *
	 * @return void
	 */
	public static function syncAll(): void {
		// Clear caches to force re-fetch.
		foreach ( self::SOURCES as $source ) {
			delete_transient( $source['cache_key'] );
		}
		self::$listCache = null;

		$instance = new self();

		foreach ( array_keys( self::SOURCES ) as $name ) {
			$list = $instance->getList( $name );
			if ( ! empty( $list ) ) {
				Helper::errorLog(
					sprintf(
						'[HDA IpReputation] Synced %s: %d entries',
						$name,
						count( $list )
					)
				);
			}
		}
	}

	/**
	 * Clear all cached blocklists.
	 *
	 * @return void
	 */
	public static function clearCache(): void {
		foreach ( self::SOURCES as $source ) {
			delete_transient( $source['cache_key'] );
		}
		self::$listCache = null;
	}
}
