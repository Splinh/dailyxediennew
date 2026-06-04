<?php
/**
 * Traffic Monitor Module — entry point.
 *
 * Manages settings, schedules cleanup cron, and wires Firewall events to TrafficLogger.
 * When enabled alongside the Firewall module, automatically logs all threat events.
 *
 * @package HDAddons\Modules\Logs\TrafficMonitor
 */

namespace HDAddons\Modules\Logs\TrafficMonitor;

use HDAddons\Modules\Logs\LogsModule;
use HDAddons\Modules\Security\Firewall\ThreatResult;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class TrafficMonitor {

	public const SUB_KEY = 'traffic_monitor';

	// ─── Option Keys (single source of truth) ───────────
	public const KEY_ENABLED        = 'tm_enabled';
	public const KEY_RETENTION_DAYS = 'tm_retention_days';

	/**
	 * Default log retention in days.
	 */
	private const DEFAULT_RETENTION_DAYS = 30;

	/**
	 * Cached options.
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Shared logger instance (lazy-loaded).
	 *
	 * @var TrafficLogger|null
	 */
	private ?TrafficLogger $logger = null;

	// --------------------------------------------------

	public function __construct() {
		$this->options = LogsModule::getSubOptions( self::SUB_KEY );

		if ( empty( $this->options[ self::KEY_ENABLED ] ) ) {
			return;
		}

		// ── Cron cleanup — scheduling handled by LogsModule::cronHooks() + Activator.
		add_action( 'hda_traffic_log_cleanup', self::runCleanup( ... ) );

		// ── Wire Firewall events → TrafficLogger ────
		add_action( 'hda_firewall_threat_detected', $this->onThreatDetected( ... ), 10, 4 );

		// ── Wire LoginSecurity blocked events ────────
		add_action( 'hda_login_blocked', $this->onLoginBlocked( ... ), 10, 2 );

		// ── Admin page ──────────────────────────────
		// Setup and rendering is now delegated to LogsAdmin.
	}

	// ══════════════════════════════════════════════════
	// Firewall integration
	// ══════════════════════════════════════════════════

	/**
	 * Handle a threat detected by the Firewall module.
	 *
	 * Fires on the `hda_firewall_threat_detected` action hook.
	 *
	 * @param ThreatResult $threat      Detected threat.
	 * @param string       $ip          Client IP.
	 * @param array        $requestData Analyzed request data.
	 * @param string       $mode        Firewall mode (learning/protecting).
	 *
	 * @return void
	 */
	public function onThreatDetected( ThreatResult $threat, string $ip, array $requestData, string $mode ): void {
		$this->getLogger()->log(
			[
				'ip'          => $ip,
				'uri'         => $requestData['uri'] ?? '',
				'method'      => $requestData['method'] ?? 'GET',
				'user_agent'  => $requestData['user_agent'] ?? '',
				'action'      => 'protecting' === $mode ? 'blocked' : 'logged',
				'attack_type' => $threat->attackType,
				'rule_id'     => $threat->ruleId,
				'severity'    => $threat->severity,
				'matched'     => $threat->matchedValue,
			]
		);
	}


	/**
	 * Handle login blocked event from LoginAttempts module.
	 *
	 * @param string $ip       Client IP.
	 * @param int    $attempts Number of failed attempts.
	 *
	 * @return void
	 */
	public function onLoginBlocked( string $ip, int $attempts ): void {
		$this->getLogger()->log(
			[
				'ip'          => $ip,
				'uri'         => '/wp-login.php',
				'method'      => 'POST',
				'action'      => 'blocked',
				'attack_type' => 'brute_force',
				'severity'    => 'high',
				'matched'     => sprintf( '%d failed login attempts', $attempts ),
			]
		);
	}

	/**
	 * Get the shared logger instance (lazy-loaded).
	 *
	 * @return TrafficLogger
	 */
	private function getLogger(): TrafficLogger {
		return $this->logger ??= new TrafficLogger();
	}

	// ══════════════════════════════════════════════════
	// Cleanup
	// ══════════════════════════════════════════════════

	/**
	 * Run cleanup via cron.
	 *
	 * @return void
	 */
	public static function runCleanup(): void {
		$options       = LogsModule::getSubOptions( self::SUB_KEY );
		$retentionDays = ! empty( $options[ self::KEY_RETENTION_DAYS ] )
			? max( 7, (int) $options[ self::KEY_RETENTION_DAYS ] )
			: self::DEFAULT_RETENTION_DAYS;

		TrafficLogger::cleanup( $retentionDays );
	}

	// ── HasSettings ──────────────────────────────────────


	public static function saveSettings( array $data ): void {
		$options = [
			self::KEY_ENABLED        => ! empty( $data[ self::KEY_ENABLED ] ),
			self::KEY_RETENTION_DAYS => isset( $data[ self::KEY_RETENTION_DAYS ] )
				? max( 7, min( 365, absint( $data[ self::KEY_RETENTION_DAYS ] ) ) )
				: self::DEFAULT_RETENTION_DAYS,
		];

		LogsModule::setSubOptions( self::SUB_KEY, $options );
	}

	public static function defaults(): array {
		return [
			self::KEY_ENABLED        => false,
			self::KEY_RETENTION_DAYS => self::DEFAULT_RETENTION_DAYS,
		];
	}

	public static function clearAll(): bool {
		return TrafficLogger::clearAll();
	}
}
