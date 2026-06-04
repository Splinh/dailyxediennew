<?php
/**
 * Access Control — IP address and country blocking.
 *
 * Generates server-level rules (htaccess/nginx) for IP/country blocking.
 * Also provides PHP-level runtime blocking as fallback when the Firewall module is OFF.
 *
 * @package HDAddons\Modules\Security
 */

namespace HDAddons\Modules\Security;

use HDAddons\Modules\Security\Firewall\Firewall;
use HDAddons\Modules\Security\Firewall\ResponseHandler;
use HDAddons\Helper;
use HDAddons\Modules\Security\ServerConfig\ServerConfig;
use GeoIp2\Database\Reader;

\defined( 'ABSPATH' ) || exit;

final class AccessControl {

	public const SUB_KEY               = 'waf';
	public const KEY_BLOCKED_COUNTRIES = 'blocked_countries';
	public const KEY_COUNTRY_MODE      = 'country_mode';          // 'block_selected' | 'allow_selected'
	public const KEY_BLOCK_UNKNOWN     = 'block_unknown_countries'; // Block when GeoIP can't determine country
	public const KEY_BLOCKED_IPS       = 'blocked_ips';
	public const MARKER_IP             = 'HDA-IPBLOCK';

	/**
	 * Cached module options.
	 *
	 * @var array|null
	 */
	private static ?array $options = null;

	/**
	 * Initialize the module.
	 */
	public function __construct() {
		// Only run on frontend and if not admin/ajax/cron/CLI.
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		$options = self::getOptions();
		$ip      = Helper::ipAddress();

		if ( ! $ip || $ip === '127.0.0.1' || $ip === '::1' ) {
			return;
		}

		// ── When Firewall module is enabled, it handles runtime blocking ──
		// AccessControl still generates server config (htaccess/nginx) via updateIpBlockConfig(),
		// but the runtime checks here are redundant.
		$firewallOptions = SecurityModule::getSubOptions( Firewall::SUB_KEY );
		if ( ! empty( $firewallOptions[ Firewall::KEY_ENABLED ] ) ) {
			return; // Firewall module handles runtime IP/country blocking.
		}

		// ── Fallback: runtime blocking when Firewall is NOT enabled ──

		// Check blocked IPs (exact, CIDR, and dash range).
		$blockedEntries = (array) ( $options[ self::KEY_BLOCKED_IPS ] ?? [] );

		if ( ! empty( $blockedEntries ) && Helper::ipMatchesAny( $ip, $blockedEntries ) ) {
			ResponseHandler::blockSimple( 'IP/Range: ' . $ip, $ip );
		}

		// Check blocked countries (GeoIP).
		$this->checkBlockedCountries( $ip, $options );
	}

	/**
	 * Provide GeoIP data to the theme via filter hook.
	 *
	 * This bridges HDA's GeoIP capability to the HD theme's Form module
	 * without creating a direct dependency. Always registered regardless
	 * of admin/frontend context.
	 */
	public static function registerGeoIPProvider(): void {
		add_filter(
			'hd_form_geoip_resolve',
			static function ( $geo, string $ip ) {
				if ( null !== $geo ) {
					return $geo; // Another provider already resolved.
				}

				$instance    = new self();
				$countryCode = $instance->resolveCountry( $ip );
				if ( ! $countryCode ) {
					return null;
				}

				return [
					'country'      => $countryCode,
					'country_name' => \Locale::getDisplayRegion( '-' . $countryCode, 'en' ) ?: $countryCode,
				];
			},
			10,
			2
		);
	}

	/**
	 * Get cached module options.
	 *
	 * @return array
	 */
	public static function getOptions(): array {
		if ( null === self::$options ) {
			self::$options = SecurityModule::getSubOptions( self::SUB_KEY );
		}

		return self::$options;
	}

	// --------------------------------------------------
	// Access Checks
	// --------------------------------------------------

	/**
	 * Check country blocking using PHP GeoIP.
	 *
	 * Supports two modes:
	 * - `block_selected` (default): block IPs from selected countries, allow everything else.
	 * - `allow_selected`: allow ONLY selected countries, block everything else.
	 *
	 * @param string $ip      Visitor IP.
	 * @param array  $options Full module options.
	 *
	 * @return void
	 */
	private function checkBlockedCountries( string $ip, array $options ): void {
		$countries = $options[ self::KEY_BLOCKED_COUNTRIES ] ?? [];
		$mode      = $options[ self::KEY_COUNTRY_MODE ] ?? 'block_selected';
		$blockUnk  = ! empty( $options[ self::KEY_BLOCK_UNKNOWN ] );

		// Nothing configured → nothing to do.
		if ( empty( $countries ) && 'block_selected' === $mode && ! $blockUnk ) {
			return;
		}

		// Resolve visitor country.
		$isoCode = $this->resolveCountry( $ip );

		// ── Country could not be determined ──────────
		if ( ! $isoCode ) {
			if ( $blockUnk ) {
				ResponseHandler::blockSimple( 'Country: unknown', $ip );
			}

			return; // Can't decide → allow.
		}

		// ── No countries selected ─────────────────────
		if ( empty( $countries ) ) {
			if ( 'allow_selected' === $mode ) {
				// Allow-only mode with empty list = block everyone.
				ResponseHandler::blockSimple( 'Country: ' . $isoCode, $ip );
			}

			return; // block_selected with empty list = allow everyone.
		}

		$isInList = in_array( $isoCode, $countries, true );

		if ( 'allow_selected' === $mode ) {
			// Allow-only: block if NOT in the list.
			if ( ! $isInList ) {
				ResponseHandler::blockSimple( 'Country: ' . $isoCode, $ip );
			}
		} elseif ( $isInList ) {
			// Block-selected: block if in the list.
			ResponseHandler::blockSimple( 'Country: ' . $isoCode, $ip );
		}
	}

	/**
	 * Cached GeoIP Reader instance.
	 *
	 * @var Reader|null|false Null = not loaded yet, false = unavailable.
	 */
	private static Reader|null|false $geoReader = null;

	/**
	 * Resolve country code for an IP address via GeoIP2.
	 *
	 * @param string $ip IP address.
	 *
	 * @return string|null ISO country code (e.g. 'VN') or null on failure.
	 */
	private function resolveCountry( string $ip ): ?string {
		// Initialize Reader once per request.
		if ( null === self::$geoReader ) {
			if ( ! class_exists( Reader::class ) ) {
				self::$geoReader = false;

				return null;
			}

			$dbPath = HDA_PATH . 'resources/geoip/GeoLite2-Country.mmdb';

			$upload_dir = wp_upload_dir();
			$userDbPath = $upload_dir['basedir'] . '/hda/GeoLite2-Country.mmdb';

			if ( file_exists( $userDbPath ) ) {
				$dbPath = $userDbPath;
			}

			if ( ! file_exists( $dbPath ) ) {
				self::$geoReader = false;

				return null;
			}

			try {
				self::$geoReader = new Reader( $dbPath );
			} catch ( \Exception $e ) {
				self::$geoReader = false;

				return null;
			}
		}

		if ( false === self::$geoReader ) {
			return null;
		}

		try {
			$record = self::$geoReader->country( $ip );

			return $record->country->isoCode ?: null;
		} catch ( \Exception $e ) {
			return null; // Silent fail — don't block if DB is corrupt.
		}
	}


	// --------------------------------------------------
	// Settings Save
	// --------------------------------------------------

	/**
	 * Process and persist Access Control settings.
	 *
	 * Extracted from SecurityModule::saveSettings() for SRP.
	 * Handles blocked IPs, blocked countries, country mode, and server config.
	 *
	 * @param array $data Sanitized form data.
	 */
	public static function handleSave( array $data ): void {
		$blocked = [];
		if ( ! empty( $data['blocked_countries'] ) && is_array( $data['blocked_countries'] ) ) {
			$blocked = array_map( 'sanitize_text_field', $data['blocked_countries'] );
		}

		$blocked_ips = [];
		if ( ! empty( $data['waf_blocked_ips'] ) && is_array( $data['waf_blocked_ips'] ) ) {
			$blocked_ips = array_map( 'sanitize_text_field', $data['waf_blocked_ips'] );
			$blocked_ips = array_values( array_unique( array_filter( $blocked_ips ) ) );
		}

		$country_mode = sanitize_text_field( $data['country_mode'] ?? 'block_selected' );
		if ( ! in_array( $country_mode, [ 'block_selected', 'allow_selected' ], true ) ) {
			$country_mode = 'block_selected';
		}

		$waf_options = [
			self::KEY_BLOCKED_COUNTRIES => $blocked,
			self::KEY_COUNTRY_MODE      => $country_mode,
			self::KEY_BLOCK_UNKNOWN     => ! empty( $data['block_unknown_countries'] ),
			self::KEY_BLOCKED_IPS       => $blocked_ips,
		];

		SecurityModule::setSubOptions( self::SUB_KEY, $waf_options );

		self::updateIpBlockConfig( $blocked_ips );
	}

	// --------------------------------------------------
	// Server Configuration
	// --------------------------------------------------

	/**
	 * Update server config for IP/range blocking (native Deny from / deny).
	 *
	 * @param array $entries Blocked IPs, CIDRs, and dash ranges.
	 *
	 * @return void
	 */
	public static function updateIpBlockConfig( array $entries ): void {
		if ( empty( $entries ) ) {
			ServerConfig::removeBlock( self::MARKER_IP );
			return;
		}

		$htaccess = self::generateIpHtaccessRules( $entries );
		$nginx    = self::generateIpNginxRules( $entries );

		ServerConfig::addBlockContent( self::MARKER_IP, $htaccess, $nginx );
	}

	// --------------------------------------------------
	// Rule Generators
	// --------------------------------------------------

	/**
	 * Generate Apache htaccess rules for IP/range blocking (native).
	 * Works on ALL Apache servers without any extra module.
	 *
	 * @param array $entries IPs and CIDR ranges.
	 *
	 * @return string
	 */
	private static function generateIpHtaccessRules( array $entries ): string {
		$output  = "<RequireAll>\n";
		$output .= "  Require all granted\n";

		foreach ( $entries as $entry ) {
			$entry = self::sanitizeIpEntry( $entry );
			if ( $entry ) {
				$output .= "  Require not ip {$entry}\n";
			}
		}

		$output .= '</RequireAll>';

		return $output;
	}

	/**
	 * Generate Nginx rules for IP/range blocking (native).
	 *
	 * @param array $entries IPs and CIDR ranges.
	 *
	 * @return string
	 */
	private static function generateIpNginxRules( array $entries ): string {
		$output = "# IP/range blocking\n";

		foreach ( $entries as $entry ) {
			$entry = self::sanitizeIpEntry( $entry );
			if ( $entry ) {
				$output .= "deny {$entry};\n";
			}
		}

		return rtrim( $output );
	}

	/**
	 * Sanitize an IP or CIDR entry for safe use in server config.
	 *
	 * @param string $entry IP address, CIDR, or dash range.
	 *
	 * @return string Sanitized entry or empty string if invalid.
	 */
	private static function sanitizeIpEntry( string $entry ): string {
		$entry = trim( $entry );

		// Single IP (IPv4 or IPv6).
		if ( filter_var( $entry, FILTER_VALIDATE_IP ) ) {
			return $entry;
		}

		// IPv4 CIDR: 1.2.3.0/24
		if ( preg_match( '/^(\d{1,3}\.){3}\d{1,3}\/([0-9]|[1-2]\d|3[0-2])$/', $entry ) ) {
			return $entry;
		}

		// IPv6 CIDR: 2001:db8::/32
		if ( preg_match( '/^([0-9a-fA-F:]+)\/(\d{1,3})$/', $entry, $m ) &&
			filter_var( $m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) &&
			(int) $m[2] >= 1 && (int) $m[2] <= 128
		) {
			return $entry;
		}

		// Dash range: 1.2.3.1-100 → not supported by Apache/Nginx natively.
		// Handled by PHP-level blocking (ipMatchesAny).

		return '';
	}

	// --------------------------------------------------
	// Cloudflare Detection
	// --------------------------------------------------

	/**
	 * Check if the site is served through Cloudflare.
	 *
	 * @return bool
	 */
	public static function isCloudflare(): bool {
		return ! empty( $_SERVER['HTTP_CF_RAY'] ) || ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] );
	}
}
