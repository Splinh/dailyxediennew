<?php
/**
 * File Integrity Module — entry point.
 *
 * Manages settings, schedules automated scans via cron,
 * and registers the admin page for manual scans.
 *
 * @package HDAddons\Modules\File\FileIntegrity
 */

namespace HDAddons\Modules\File\FileIntegrity;

use HDAddons\Contracts\HasSettings;
use HDAddons\Helper;
use HDAddons\Modules\File\FileModule;

\defined( 'ABSPATH' ) || exit;

final class FileIntegrity implements HasSettings {


	// ─── Option Keys (single source of truth) ───────────

	public const SUB_KEY          = 'file_integrity';
	public const KEY_ENABLED      = 'fi_enabled';
	public const KEY_CORE_SCAN    = 'fi_core_scan';       // Auto core scan
	public const KEY_MALWARE_SCAN = 'fi_malware_scan';    // Auto malware scan
	public const KEY_VULN_SCAN    = 'fi_vuln_scan';       // Auto vulnerability scan
	public const KEY_EMAIL_ALERTS = 'fi_email_alerts';    // Email admin on findings
	public const KEY_SCHEDULE     = 'fi_schedule';        // daily | weekly | monthly

	/**
	 * Cron hook name.
	 */
	private const CRON_HOOK = 'hda_file_integrity_scan';

	/**
	 * Cached options.
	 *
	 * @var array
	 */
	private array $options;

	// --------------------------------------------------

	public function __construct() {
		$this->options = FileModule::getSubOptions( self::SUB_KEY );

		if ( empty( $this->options[ self::KEY_ENABLED ] ) ) {
			// Unschedule any existing cron when module is disabled.
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::CRON_HOOK );
			}

			return;
		}

		// ── Cron scheduled scan ─────────────────────
		add_action( self::CRON_HOOK, self::runScheduledScan( ... ) );

		$schedule = $this->options[ self::KEY_SCHEDULE ] ?? 'weekly';
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), $schedule, self::CRON_HOOK );
		}
	}

	// ══════════════════════════════════════════════════
	// Scheduled scan
	// ══════════════════════════════════════════════════

	/**
	 * Run automated scan via cron.
	 *
	 * @return void
	 */
	public static function runScheduledScan(): void {
		$options = FileModule::getSubOptions( self::SUB_KEY );

		$coreFindings    = [];
		$malwareFindings = [];

		// ── Core scan ────────────────────────────────
		if ( ! empty( $options[ self::KEY_CORE_SCAN ] ) ) {
			$checker      = new CoreChecker();
			$coreResults  = $checker->runScan();
			$coreFindings = array_merge(
				$coreResults['modified'] ?? [],
				$coreResults['unknown'] ?? [],
			);

			// Cache results for admin page.
			set_transient( 'hda_fi_core_results', $coreResults, 24 * HOUR_IN_SECONDS );
		}

		// ── Malware scan ─────────────────────────────
		if ( ! empty( $options[ self::KEY_MALWARE_SCAN ] ) ) {
			$scanner        = new MalwareScanner();
			$malwareResults = $scanner->runScan();

			$malwareFindings = $malwareResults['findings'] ?? [];

			// Cache results for admin page.
			set_transient( 'hda_fi_malware_results', $malwareResults, 24 * HOUR_IN_SECONDS );
		}

		// ── Vulnerability scan ────────────────────────
		$vulnFindings = 0;
		if ( ! empty( $options[ self::KEY_VULN_SCAN ] ) ) {
			$vulnScanner  = new VulnerabilityScanner();
			$vulnResults  = $vulnScanner->runScan();
			$vulnSummary  = $vulnResults['summary'] ?? [];
			$vulnFindings = ( $vulnSummary['vulnerable'] ?? 0 ) + ( $vulnSummary['closed'] ?? 0 );

			VulnerabilityScanner::cacheResults( $vulnResults );
		}

		// ── Email alerts ─────────────────────────────
		$totalIssues = count( $coreFindings ) + count( $malwareFindings ) + $vulnFindings;

		if ( $totalIssues > 0 && ! empty( $options[ self::KEY_EMAIL_ALERTS ] ) ) {
			self::sendAlertEmail( $totalIssues, count( $coreFindings ), count( $malwareFindings ), $vulnFindings );
		}
	}

	// ══════════════════════════════════════════════════
	// Email
	// ══════════════════════════════════════════════════

	/**
	 * Send alert email to site admin.
	 *
	 * @param int $total         Total issues.
	 * @param int $coreIssues    Core integrity issues.
	 * @param int $malwareHits   Malware scan findings.
	 * @param int $vulnFindings  Vulnerable plugins/themes.
	 *
	 * @return void
	 */
	private static function sendAlertEmail( int $total, int $coreIssues, int $malwareHits, int $vulnFindings = 0 ): void {
		$adminEmail = Helper::getOption( 'admin_email' );
		$siteName   = get_bloginfo( 'name' );
		$siteUrl    = get_bloginfo( 'url' );
		$adminUrl   = admin_url( 'admin.php?page=' . FileIntegrityAdmin::MENU_SLUG );

		$subject = sprintf(
			'[%s] File Integrity Alert: %d issues found',
			$siteName,
			$total
		);

		$message = sprintf(
			"File Integrity Scan Report\n\n" .
			"Site: %s (%s)\n" .
			"Time: %s\n\n" .
			"Core integrity issues: %d\n" .
			"Malware scan findings: %d\n" .
			"Vulnerable plugins/themes: %d\n" .
			"Total issues: %d\n\n" .
			"Please review the scan results:\n%s\n\n" .
			'— HDA Security',
			$siteName,
			$siteUrl,
			current_time( 'mysql' ),
			$coreIssues,
			$malwareHits,
			$vulnFindings,
			$total,
			$adminUrl
		);

		wp_mail( $adminEmail, $subject, $message );
	}

	// ── HasSettings ──────────────────────────────────────


	/**
	 * Sanitize and persist file integrity settings.
	 *
	 * Called by FileModule::saveSettings().
	 *
	 * @param array $data Form data.
	 */
	public static function saveSettings( array $data ): void {
		$checkboxFields = [
			self::KEY_ENABLED,
			self::KEY_CORE_SCAN,
			self::KEY_MALWARE_SCAN,
			self::KEY_VULN_SCAN,
			self::KEY_EMAIL_ALERTS,
		];

		$options = [];
		foreach ( $checkboxFields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$options[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}

		// Schedule validation.
		$schedule = $data[ self::KEY_SCHEDULE ] ?? 'weekly';
		if ( ! in_array( $schedule, [ 'daily', 'weekly', 'monthly' ], true ) ) {
			$schedule = 'weekly';
		}
		$options[ self::KEY_SCHEDULE ] = $schedule;

		// Reschedule cron if schedule changed.
		$oldOptions  = FileModule::getSubOptions( self::SUB_KEY );
		$oldSchedule = $oldOptions[ self::KEY_SCHEDULE ] ?? 'weekly';

		if ( $schedule !== $oldSchedule ) {
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::CRON_HOOK );
			}
		}

		FileModule::setSubOptions( self::SUB_KEY, $options );

		// ── API keys (stored as separate options, not in the main array) ──
		$apiKeys = [
			'hda_virustotal_api_key' => sanitize_text_field( $data['hda_virustotal_api_key'] ?? '' ),
		];

		foreach ( $apiKeys as $key => $value ) {
			if ( '' !== $value ) {
				Helper::updateOption( $key, $value );
			} else {
				Helper::removeOption( $key );
			}
		}
	}
}
