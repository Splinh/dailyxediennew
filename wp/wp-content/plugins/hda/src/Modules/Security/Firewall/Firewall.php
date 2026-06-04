<?php
/**
 * Firewall Module — Web Application Firewall orchestrator.
 *
 * Pipeline: Analyze → Detect → Rate Limit → Respond.
 * Supports 'learning' (log only) and 'protecting' (block) modes.
 *
 * Simplified keys:
 * - attack_detection: enables all 5 detectors (SQLi, XSS, RCE, LFI, Bad Bots)
 * - threat_intel: enables Crawler Whitelist + IP Reputation
 *
 * @package HDAddons\Modules\Security\Firewall
 */

namespace HDAddons\Modules\Security\Firewall;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\Security\SecurityModule;
use HDAddons\Core\RateLimitStorage;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class Firewall implements HasSettings {


	// ─── Option Keys ─────────────────────────────────────

	public const SUB_KEY           = 'firewall';
	public const KEY_ENABLED       = 'firewall_enabled';
	public const KEY_MODE          = 'firewall_mode';            // 'learning' | 'protecting'
	public const KEY_ATTACK_DETECT = 'firewall_attack_detection'; // All 5 detectors
	public const KEY_THREAT_INTEL  = 'firewall_threat_intel';     // Crawler WL + IP Reputation
	public const KEY_RATE_LIMIT    = 'firewall_rate_limit';
	public const KEY_RATE_GLOBAL   = 'firewall_rate_global';     // requests/min
	public const KEY_ALLOWLIST_IPS = 'firewall_allowlist_ips';   // IP whitelist array
	public const KEY_404_FLOOD     = 'firewall_404_flood';       // 404 flood protection

	/**
	 * All attack types enabled by KEY_ATTACK_DETECT.
	 */
	private const ATTACK_TYPES = [ 'sqli', 'xss', 'rce', 'lfi', 'bad_bot' ];

	/**
	 * Firewall options (cached).
	 */
	private array $options;

	// --------------------------------------------------

	/**
	 * Initialize the firewall.
	 */
	public function __construct() {
		$this->options = SecurityModule::getSubOptions( self::SUB_KEY );

		// Emergency bypass via .env constant.
		if ( defined( 'HDA_DISABLE_FIREWALL' ) && \HDA_DISABLE_FIREWALL ) {
			return;
		}

		// Must be explicitly enabled.
		if ( empty( $this->options[ self::KEY_ENABLED ] ) ) {
			return;
		}

		// Threat Intel cron sync (all contexts).
		// Scheduling is handled by SecurityModule::cronHooks() + Activator.
		add_action( 'hda_threat_intel_sync', self::runThreatIntelSync( ... ) );

		// Skip CLI and WP Cron.
		if ( 'cli' === PHP_SAPI || wp_doing_cron() ) {
			return;
		}

		// Handle 404 Flood protection (requires RateLimiter storage).
		if ( ! empty( $this->options[ self::KEY_404_FLOOD ] ) ) {
			add_action( 'hda_404_error_event', $this->handle404Flood( ... ), 10, 2 );
		}

		// Defer WAF pipeline to plugins_loaded to avoid constructor side-effects.
		add_action( 'plugins_loaded', $this->run( ... ), 1 );
	}

	// ══════════════════════════════════════════════════
	// WAF Pipeline
	// ══════════════════════════════════════════════════

	/**
	 * Execute the WAF pipeline.
	 */
	private function run(): void {
		$ip = Helper::ipAddress();

		// 1. Skip localhost.
		if ( in_array( $ip, [ '127.0.0.1', '::1', '' ], true ) ) {
			return;
		}

		// 2. IP Allowlist (always pass).
		$allowlist = (array) ( $this->options[ self::KEY_ALLOWLIST_IPS ] ?? [] );
		if ( ! empty( $allowlist ) && Helper::ipMatchesAny( $ip, $allowlist ) ) {
			return;
		}

		// 3. Check temporary 404 flood ban (before any bypass).
		if ( ! empty( $this->options[ self::KEY_404_FLOOD ] ) ) {
			if ( RateLimitStorage::get( $ip, 'banned_404' ) > 0 ) {
				$threat = new ThreatResult(
					ruleId: '404_flood_ban',
					attackType: 'rate_limit',
					severity: 'high',
					matchedValue: 'IP temporarily banned for 404 flood',
					context: 'global',
					description: 'IP banned due to excessive 404 errors',
				);
				$this->handleThreat( $threat, $ip, [ 'uri' => sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ] );

				return;
			}
		}

		// 4. Analyze request.
		$analyzer    = new RequestAnalyzer();
		$requestData = $analyzer->analyze();

		// 5. Skip static files.
		if ( $requestData['is_static'] ) {
			return;
		}

		// 6. Skip admin for logged-in admins.
		$uri = $requestData['uri'];
		if ( str_contains( $uri, '/wp-admin/' ) && is_user_logged_in() && current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		$hasThreatIntel = ! empty( $this->options[ self::KEY_THREAT_INTEL ] );

		// 7. Crawler whitelist (bypass scanning).
		if ( $hasThreatIntel ) {
			$crawler = ( new CrawlerWhitelist() )->isVerifiedCrawler( $ip, $requestData['user_agent'] ?? '' );
			if ( $crawler ) {
				return;
			}
		}

		// 8. IP Reputation check.
		if ( $hasThreatIntel ) {
			$reputation = ( new IpReputation() )->check( $ip );
			if ( $reputation ) {
				$threat = new ThreatResult(
					ruleId: 'ip_rep_' . $reputation['source'],
					attackType: 'ip_reputation',
					severity: 'high',
					matchedValue: $ip . ' (' . $reputation['source'] . ')',
					context: 'global',
					description: 'IP on known abuse list: ' . $reputation['source'],
				);
				$this->handleThreat( $threat, $ip, $requestData );

				return;
			}
		}

		// 9. Threat detection (all 5 types at once).
		if ( ! empty( $this->options[ self::KEY_ATTACK_DETECT ] ) ) {
			$threat = ( new ThreatDetector() )->detect( $requestData, self::ATTACK_TYPES );
			if ( $threat ) {
				$this->handleThreat( $threat, $ip, $requestData );

				return;
			}
		}

		// 10. Rate limiting.
		if ( ! empty( $this->options[ self::KEY_RATE_LIMIT ] ) ) {
			$rateThreat = $this->checkRateLimit( $ip, $requestData );
			if ( $rateThreat ) {
				$this->handleThreat( $rateThreat, $ip, $requestData );
			}
		}
	}

	// ══════════════════════════════════════════════════
	// Rate Limiting
	// ══════════════════════════════════════════════════

	/**
	 * Check rate limits for the current request.
	 */
	private function checkRateLimit( string $ip, array $requestData ): ?ThreatResult {
		$customLimits = [];

		if ( ! empty( $this->options[ self::KEY_RATE_GLOBAL ] ) ) {
			$customLimits['global'] = (int) $this->options[ self::KEY_RATE_GLOBAL ];
		}

		return ( new RateLimiter( $customLimits ) )->check( $ip, $requestData );
	}

	// ══════════════════════════════════════════════════
	// Response
	// ══════════════════════════════════════════════════

	/**
	 * Handle a detected threat based on current mode.
	 */
	private function handleThreat( ThreatResult $threat, string $ip, array $requestData ): void {
		$mode = $this->options[ self::KEY_MODE ] ?? 'learning';

		// Log the threat (always).
		$this->logThreat( $threat, $ip, $requestData, $mode );

		// Fire action for external integrations.
		do_action( 'hda_firewall_threat_detected', $threat, $ip, $requestData, $mode );

		// In protecting mode, block the request.
		if ( 'protecting' === $mode ) {
			ResponseHandler::block( $threat, $ip );
		}
	}

	/**
	 * Handle 404 flood event triggered from Monitor404.
	 */
	private function handle404Flood( string $ip, string $requestUri ): void {
		$limit = defined( 'HDA_404_FLOOD_LIMIT' ) ? (int) \HDA_404_FLOOD_LIMIT : 10;

		// Increment 404 count via RateLimitStorage.
		$count = \HDAddons\Core\RateLimitStorage::increment( $ip, 'rl_404', 60 ); // 60s window

		if ( $count > $limit ) {
			// Lock the IP site-wide for 1 hour.
			\HDAddons\Core\RateLimitStorage::increment( $ip, 'banned_404', HOUR_IN_SECONDS );

			$threat = new ThreatResult(
				ruleId: '404_flood',
				attackType: 'rate_limit',
				severity: 'high',
				matchedValue: "{$count}/{$limit} 404 requests in 60s",
				context: 'global',
				description: 'Rate limit exceeded for 404 errors',
			);

			$requestData = [
				'uri' => $requestUri,
			];

			$this->handleThreat( $threat, $ip, $requestData );
		}
	}

	/**
	 * Log a threat.
	 */
	private function logThreat( ThreatResult $threat, string $ip, array $requestData, string $mode ): void {
		$action = 'protecting' === $mode ? 'blocked' : 'logged';

		Helper::errorLog(
			sprintf(
				'[HDA Firewall] %s | %s | %s | %s | %s | %s | %s',
				strtoupper( $action ),
				$threat->attackType,
				$threat->severity,
				$threat->ruleId,
				$ip,
				$requestData['uri'] ?? '',
				$threat->matchedValue,
			)
		);
	}

	// ══════════════════════════════════════════════════
	// Threat Intel Sync (Cron)
	// ══════════════════════════════════════════════════

	/**
	 * Sync threat intelligence data via daily cron.
	 */
	public static function runThreatIntelSync(): void {
		CrawlerWhitelist::syncAll();
		IpReputation::syncAll();
	}

	// ── HasSettings ──────────────────────────────────────


	public static function saveSettings( array $data ): void {
		$fields = [
			self::KEY_ENABLED,
			self::KEY_MODE,
			self::KEY_ATTACK_DETECT,
			self::KEY_THREAT_INTEL,
			self::KEY_RATE_LIMIT,
			self::KEY_RATE_GLOBAL,
			self::KEY_404_FLOOD,
		];

		$options = [];
		foreach ( $fields as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$options[ $key ] = is_string( $data[ $key ] ) ? sanitize_text_field( $data[ $key ] ) : $data[ $key ];
			}
		}

		// Handle allowlist IPs (select2 → array).
		if ( ! empty( $data[ self::KEY_ALLOWLIST_IPS ] ) && is_array( $data[ self::KEY_ALLOWLIST_IPS ] ) ) {
			$options[ self::KEY_ALLOWLIST_IPS ] = array_map( 'sanitize_text_field', $data[ self::KEY_ALLOWLIST_IPS ] );
		}

		// Validate mode.
		if ( isset( $options[ self::KEY_MODE ] ) && ! in_array( $options[ self::KEY_MODE ], [ 'learning', 'protecting' ], true ) ) {
			$options[ self::KEY_MODE ] = 'learning';
		}

		SecurityModule::setSubOptions( self::SUB_KEY, $options );
	}
}
