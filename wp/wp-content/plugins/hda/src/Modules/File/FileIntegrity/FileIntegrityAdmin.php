<?php
/**
 * File Integrity Admin Page — scan triggers and result display.
 *
 * @package HDAddons\Modules\File\FileIntegrity
 * @author  HD
 */

namespace HDAddons\Modules\File\FileIntegrity;

use HDAddons\Asset;
use HDAddons\Helper;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class FileIntegrityAdmin {

	/**
	 * Menu slug.
	 */
	public const MENU_SLUG = 'hda-file-integrity';

	/**
	 * Nonce actions.
	 */
	private const SCAN_NONCE      = 'hda_file_integrity_scan';
	private const ALLOWLIST_NONCE = 'hda_malware_allowlist';

	/**
	 * Transient keys for cached scan results.
	 */
	private const CORE_RESULTS_KEY    = 'hda_fi_core_results';
	private const MALWARE_RESULTS_KEY = 'hda_fi_malware_results';

	// --------------------------------------------------

	public function __construct() {
		add_action( 'admin_menu', $this->addMenuPage( ... ), 20 );
		add_action( 'admin_init', $this->handleAllowlistAction( ... ) );

		// AJAX scan handlers.
		add_action( 'wp_ajax_hda_fi_core_scan', self::ajaxCoreScan( ... ) );
		add_action( 'wp_ajax_hda_fi_malware_scan', self::ajaxMalwareScan( ... ) );
		add_action( 'wp_ajax_hda_fi_vuln_scan', self::ajaxVulnScan( ... ) );

		// Localize nonce for JS.
		add_action(
			'admin_enqueue_scripts',
			static function (): void {
				$handle = Asset::handle( 'settings.js' );
				if ( $handle ) {
					Asset::localize(
						$handle,
						'hdaFileIntegrity',
						[
							'nonce' => wp_create_nonce( self::SCAN_NONCE ),
							'i18n'  => [
								'scanning'  => __( 'Scanning…', 'hda' ),
								'completed' => __( 'Scan completed', 'hda' ),
								'error'     => __( 'Scan failed. Please try again.', 'hda' ),
							],
						]
					);
				}
			},
			50
		);
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function addMenuPage(): void {
		add_submenu_page(
			'hda-settings',
			__( 'File Integrity', 'hda' ),
			__( 'File Integrity', 'hda' ),
			Plugin::CAPABILITY,
			self::MENU_SLUG,
			$this->renderPage( ... )
		);
	}

	// ══════════════════════════════════════════════════
	// AJAX scan handlers
	// ══════════════════════════════════════════════════

	/**
	 * Validate AJAX scan request.
	 *
	 * @return void Dies on failure.
	 */
	private static function validateScanRequest(): void {
		if ( ! check_ajax_referer( self::SCAN_NONCE, '_nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid or expired nonce. Please reload the page.' ], 403 );
		}

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
		}
	}

	/**
	 * AJAX: Run core integrity scan.
	 */
	public static function ajaxCoreScan(): void {
		self::validateScanRequest();

		@set_time_limit( 120 );

		$checker = new CoreChecker();
		$results = $checker->runScan();
		set_transient( self::CORE_RESULTS_KEY, $results, 12 * HOUR_IN_SECONDS );

		wp_send_json_success(
			[
				'message' => __( 'Core integrity scan completed.', 'hda' ),
				'results' => $results,
			]
		);
	}

	/**
	 * AJAX: Run malware scan.
	 */
	public static function ajaxMalwareScan(): void {
		self::validateScanRequest();

		@set_time_limit( 120 );

		$scanner = new MalwareScanner();
		$results = $scanner->runScan();
		set_transient( self::MALWARE_RESULTS_KEY, $results, 12 * HOUR_IN_SECONDS );

		wp_send_json_success(
			[
				'message' => __( 'Malware scan completed.', 'hda' ),
				'results' => $results,
			]
		);
	}

	/**
	 * AJAX: Run vulnerability scan.
	 */
	public static function ajaxVulnScan(): void {
		self::validateScanRequest();

		// WP.org API batch queries can be slow on large sites.
		@set_time_limit( 120 );

		$scanner = new VulnerabilityScanner();
		$results = $scanner->runScan();
		VulnerabilityScanner::cacheResults( $results );

		wp_send_json_success(
			[
				'message' => __( 'Vulnerability scan completed.', 'hda' ),
				'results' => $results,
			]
		);
	}

	/**
	 * Handle allowlist actions (mark safe / remove from allowlist).
	 *
	 * @return void
	 */
	public function handleAllowlistAction(): void {
		$page = sanitize_key( $_REQUEST['page'] ?? '' );
		if ( $page !== self::MENU_SLUG ) {
			return;
		}

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, self::ALLOWLIST_NONCE ) ) {
			return;
		}

		// ── Mark as safe ─────────────────────────────
		if ( isset( $_POST['allowlist_add'] ) ) {
			$filePath = sanitize_text_field( wp_unslash( $_POST['allowlist_file'] ?? '' ) );

			if ( ! empty( $filePath ) ) {
				MalwareScanner::addToAllowlist( $filePath );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&allowlisted=1' ) );
			exit;
		}

		// ── Remove from allowlist ────────────────────
		if ( isset( $_POST['allowlist_remove'] ) ) {
			$filePath = sanitize_text_field( wp_unslash( $_POST['allowlist_file'] ?? '' ) );

			if ( ! empty( $filePath ) ) {
				MalwareScanner::removeFromAllowlist( $filePath );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&allowlist_removed=1' ) );
			exit;
		}
	}

	// --------------------------------------------------

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function renderPage(): void {
		$coreResults    = get_transient( self::CORE_RESULTS_KEY ) ?: null;
		$malwareResults = get_transient( self::MALWARE_RESULTS_KEY ) ?: null;
		$vulnResults    = VulnerabilityScanner::getCachedResults();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'File Integrity', 'hda' ); ?></h1>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['allowlisted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'File marked as safe (allowlisted). It will be skipped in future scans.', 'hda' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['allowlist_removed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-info is-dismissible">
					<p><?php esc_html_e( 'File removed from allowlist. It will be scanned again.', 'hda' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Toast container for AJAX messages -->
			<div id="hda-fi-toast"></div>

			<!-- ═══════════ Core Integrity Scan ═══════════ -->
			<div class="hda-fi-section">
				<div class="card hda-fi-card">
					<div class="hda-fi-card__header">
						<div>
							<h2 class="hda-fi-card__title">🔍 <?php esc_html_e( 'WordPress Core Integrity', 'hda' ); ?></h2>
							<p class="description"><?php esc_html_e( 'Checks WordPress core files against official checksums from WordPress.org.', 'hda' ); ?></p>
						</div>
						<div class="hda-fi-scan-action">
							<div class="hda-fi-progress" data-progress-for="hda_fi_core_scan"><div class="hda-fi-progress__bar"></div></div>
							<button type="button" class="button button-primary hda-fi-scan-btn" data-scan="hda_fi_core_scan">
								<?php esc_html_e( 'Run Core Scan', 'hda' ); ?>
							</button>
						</div>
					</div>
					<div class="hda-fi-results" data-results-for="hda_fi_core_scan">
						<?php $this->renderCoreResults( $coreResults ); ?>
					</div>
				</div>
			</div>

			<!-- ═══════════ Malware Scanner ═══════════ -->
			<div class="hda-fi-section">
				<div class="card hda-fi-card">
					<div class="hda-fi-card__header">
						<div>
							<h2 class="hda-fi-card__title">🛡️ <?php esc_html_e( 'Malware Scanner', 'hda' ); ?></h2>
							<p class="description"><?php esc_html_e( 'Scans plugins, themes, and uploads for known malware signatures and suspicious patterns.', 'hda' ); ?></p>
						</div>
						<div class="hda-fi-scan-action">
							<div class="hda-fi-progress" data-progress-for="hda_fi_malware_scan"><div class="hda-fi-progress__bar"></div></div>
							<button type="button" class="button button-primary hda-fi-scan-btn" data-scan="hda_fi_malware_scan">
								<?php esc_html_e( 'Run Malware Scan', 'hda' ); ?>
							</button>
						</div>
					</div>
					<div class="hda-fi-results" data-results-for="hda_fi_malware_scan">
						<?php $this->renderMalwareResults( $malwareResults ); ?>
					</div>
				</div>
			</div>

			<!-- ═══════════ Vulnerability Scanner ═══════════ -->
			<div class="hda-fi-section">
				<div class="card hda-fi-card">
					<div class="hda-fi-card__header">
						<div>
							<h2 class="hda-fi-card__title">🔓 <?php esc_html_e( 'Vulnerability Scanner', 'hda' ); ?></h2>
							<p class="description"><?php esc_html_e( 'Checks installed plugins and themes for known security issues via WordPress.org (outdated, closed, abandoned).', 'hda' ); ?></p>
						</div>
						<div class="hda-fi-scan-action">
							<div class="hda-fi-progress" data-progress-for="hda_fi_vuln_scan"><div class="hda-fi-progress__bar"></div></div>
							<button type="button" class="button button-primary hda-fi-scan-btn" data-scan="hda_fi_vuln_scan">
								<?php esc_html_e( 'Run Vulnerability Scan', 'hda' ); ?>
							</button>
						</div>
					</div>
					<div class="hda-fi-results" data-results-for="hda_fi_vuln_scan">
						<?php $this->renderVulnResults( $vulnResults ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	// ══════════════════════════════════════════════════
	// Result renderers
	// ══════════════════════════════════════════════════

	/**
	 * Render core scan results.
	 *
	 * @param array|null $results Scan results.
	 *
	 * @return void
	 */
	private function renderCoreResults( ?array $results ): void {
		if ( ! $results ) {
			echo '<p class="hda-fi-empty"><em>' . esc_html__( 'No scan results yet. Click "Run Core Scan" to check.', 'hda' ) . '</em></p>';

			return;
		}

		if ( ! empty( $results['error'] ) ) {
			echo '<div class="notice notice-error below-h2"><p>' . esc_html( $results['error'] ) . '</p></div>';

			return;
		}

		$modifiedCount = count( $results['modified'] );
		$unknownCount  = count( $results['unknown'] );
		$missingCount  = count( $results['missing'] );
		$totalIssues   = $modifiedCount + $unknownCount + $missingCount;

		// Summary.
		$statusClass = $totalIssues > 0 ? 'notice-warning' : 'notice-success';
		$statusIcon  = $totalIssues > 0 ? '⚠️' : '✅';

		printf(
			'<div class="notice %s below-h2 hda-fi-summary"><p>%s <strong>WP %s</strong> — %s files checked — <strong>%d issues</strong> — %s</p></div>',
			esc_attr( $statusClass ),
			$statusIcon,
			esc_html( $results['wp_version'] ),
			esc_html( number_format_i18n( $results['checked'] ) ),
			$totalIssues,
			esc_html( $results['scanned_at'] )
		);

		if ( $totalIssues === 0 ) {
			return;
		}

		// Modified files.
		if ( $modifiedCount > 0 ) {
			echo '<h3 class="hda-fi-heading hda-fi-heading--danger">🔴 ' . esc_html( sprintf( __( 'Modified Files (%d)', 'hda' ), $modifiedCount ) ) . '</h3>';
			echo '<table class="widefat striped hda-fi-table"><thead><tr><th>File</th></tr></thead><tbody>';
			foreach ( $results['modified'] as $file => $hashes ) {
				printf( '<tr><td><code>%s</code></td></tr>', esc_html( $file ) );
			}
			echo '</tbody></table>';
		}

		// Unknown files.
		if ( $unknownCount > 0 ) {
			echo '<h3 class="hda-fi-heading hda-fi-heading--warning">🟡 ' . esc_html( sprintf( __( 'Unknown Files (%d)', 'hda' ), $unknownCount ) ) . '</h3>';
			echo '<table class="widefat striped hda-fi-table"><thead><tr><th>File</th></tr></thead><tbody>';
			foreach ( array_slice( $results['unknown'], 0, 50 ) as $file ) {
				printf( '<tr><td><code>%s</code></td></tr>', esc_html( $file ) );
			}
			if ( $unknownCount > 50 ) {
				printf( '<tr><td><em>... and %d more</em></td></tr>', $unknownCount - 50 );
			}
			echo '</tbody></table>';
		}

		// Missing files.
		if ( $missingCount > 0 ) {
			echo '<h3 class="hda-fi-heading hda-fi-heading--info">🔵 ' . esc_html( sprintf( __( 'Missing Files (%d)', 'hda' ), $missingCount ) ) . '</h3>';
			echo '<table class="widefat striped hda-fi-table"><thead><tr><th>File</th></tr></thead><tbody>';
			foreach ( array_slice( $results['missing'], 0, 30 ) as $file ) {
				printf( '<tr><td><code>%s</code></td></tr>', esc_html( $file ) );
			}
			echo '</tbody></table>';
		}
	}

	/**
	 * Render malware scan results.
	 *
	 * @param array|null $results Scan results.
	 *
	 * @return void
	 */
	private function renderMalwareResults( ?array $results ): void {
		if ( ! $results ) {
			echo '<p class="hda-fi-empty"><em>' . esc_html__( 'No scan results yet. Click "Run Malware Scan" to check.', 'hda' ) . '</em></p>';

			return;
		}

		$findingsCount    = count( $results['findings'] );
		$skippedAllowlist = $results['skipped_allowlist'] ?? 0;
		$skippedChecksum  = $results['skipped_checksum'] ?? 0;
		$statusClass      = $findingsCount > 0 ? 'notice-warning' : 'notice-success';
		$statusIcon       = $findingsCount > 0 ? '⚠️' : '✅';

		// Build skip info string.
		$skipParts = [];
		if ( $skippedAllowlist > 0 ) {
			$skipParts[] = sprintf( '%d allowlisted', $skippedAllowlist );
		}
		if ( $skippedChecksum > 0 ) {
			$skipParts[] = sprintf( '%d verified (WP.org)', $skippedChecksum );
		}
		$skipInfo = ! empty( $skipParts ) ? ' — Skipped: ' . implode( ', ', $skipParts ) : '';

		$vtChecked = $results['vt_checked'] ?? 0;
		$vtInfo    = $vtChecked > 0 ? sprintf( ' — VirusTotal: %d files checked', $vtChecked ) : '';

		printf(
			'<div class="notice %s below-h2 hda-fi-summary"><p>%s Scanned <strong>%s files</strong> in <strong>%ss</strong> — <strong>%d suspicious files</strong>%s%s%s — %s</p></div>',
			esc_attr( $statusClass ),
			$statusIcon,
			esc_html( number_format_i18n( $results['scanned_files'] ) ),
			esc_html( $results['scan_time'] ),
			$findingsCount,
			$results['truncated'] ? wp_kses_post( ' <em>(scan truncated)</em>' ) : '',
			esc_html( $skipInfo ),
			esc_html( $vtInfo ),
			esc_html( $results['scanned_at'] )
		);

		if ( $findingsCount === 0 ) {
			return;
		}

		$allowlist  = MalwareScanner::getAllowlist();
		$nonceField = wp_nonce_field( self::ALLOWLIST_NONCE, '_wpnonce', true, false );
		$pageInput  = '<input type="hidden" name="page" value="' . esc_attr( self::MENU_SLUG ) . '">';

		echo '<table class="widefat striped hda-fi-table">';
		echo '<thead><tr><th>File</th><th>Match</th><th>Severity</th><th>Confidence</th><th>Line</th><th>Actions</th></tr></thead><tbody>';

		foreach ( $results['findings'] as $finding ) {
			$file          = esc_html( $finding['file'] );
			$isAllowlisted = isset( $allowlist[ $finding['file'] ] );
			$matchCount    = count( $finding['matches'] );
			$rowClass      = $isAllowlisted ? ' class="hda-fi-row--safe"' : '';

			foreach ( $finding['matches'] as $idx => $match ) {
				$severityClass = match ( $match['severity'] ) {
					'critical' => 'hda-fi-severity--critical',
					'high'     => 'hda-fi-severity--high',
					'medium'   => 'hda-fi-severity--medium',
					default    => 'hda-fi-severity--low',
				};

				$confidence      = $match['confidence'] ?? 0;
				$confidenceClass = $confidence >= 70 ? 'hda-fi-confidence--high' : ( $confidence >= 40 ? 'hda-fi-confidence--medium' : 'hda-fi-confidence--low' );

				// Allowlist button only on first match row per file.
				$actionCell = '';
				if ( 0 === $idx ) {
					if ( $isAllowlisted ) {
						$actionCell = sprintf(
							'<form method="post" style="display:inline">%s%s<input type="hidden" name="allowlist_file" value="%s"><button type="submit" name="allowlist_remove" class="button button-small">%s</button></form>',
							$nonceField, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$pageInput,
							esc_attr( $finding['file'] ),
							esc_html__( 'Remove from allowlist', 'hda' )
						);
					} else {
						$actionCell = sprintf(
							'<form method="post" style="display:inline">%s%s<input type="hidden" name="allowlist_file" value="%s"><button type="submit" name="allowlist_add" class="button button-small">%s</button></form>',
							$nonceField, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$pageInput,
							esc_attr( $finding['file'] ),
							esc_html__( 'Mark as safe', 'hda' )
						);
					}

					if ( $matchCount > 1 ) {
						$actionCell = sprintf(
							'<td rowspan="%d">%s</td>',
							$matchCount,
							$actionCell
						);
					} else {
						$actionCell = '<td>' . $actionCell . '</td>';
					}
				}

				// Build file label with badges.
				$fileLabel = '<code>' . $file . '</code>';

				if ( $isAllowlisted ) {
					$fileLabel .= ' <span class="hda-fi-badge--safe">✅ ' . esc_html__( 'Safe', 'hda' ) . '</span>';
				}

				// VirusTotal verdict badge (only on first match row).
				if ( 0 === $idx && isset( $finding['virustotal'] ) ) {
					$vt = $finding['virustotal'];
					if ( 'clean' === $vt['status'] ) {
						$fileLabel .= ' <span class="hda-fi-badge--safe" title="VirusTotal: 0 detections">🛡️ VT Clean</span>';
					} elseif ( 'malicious' === $vt['status'] ) {
						$fileLabel .= sprintf(
							' <span class="hda-fi-severity hda-fi-severity--critical" title="VirusTotal: %d/%d detections">🔴 VT %d/%d</span>',
							(int) $vt['positives'],
							(int) $vt['total'],
							(int) $vt['positives'],
							(int) $vt['total']
						);
					} elseif ( 'suspicious' === $vt['status'] ) {
						$fileLabel .= sprintf(
							' <span class="hda-fi-severity hda-fi-severity--medium" title="VirusTotal: %d/%d detections">⚠️ VT %d/%d</span>',
							(int) $vt['positives'],
							(int) $vt['total'],
							(int) $vt['positives'],
							(int) $vt['total']
						);
					}
				}

				printf(
					'<tr%s><td>%s</td><td>%s</td><td><span class="hda-fi-severity %s">%s</span></td><td><span class="hda-fi-confidence %s">%d%%</span></td><td>%s</td>%s</tr>',
					$rowClass,
					$fileLabel,
					esc_html( $match['name'] ),
					esc_attr( $severityClass ),
					esc_html( ucfirst( $match['severity'] ) ),
					esc_attr( $confidenceClass ),
					$confidence,
					$match['line'] > 0 ? esc_html( "L{$match['line']}" ) : '—',
					$actionCell
				);
			}
		}

		echo '</tbody></table>';
	}

	// ══════════════════════════════════════════════════
	// Vulnerability results
	// ══════════════════════════════════════════════════

	/**
	 * Render vulnerability scan results.
	 *
	 * @param array|null $results Vulnerability scan results.
	 */
	private function renderVulnResults( ?array $results ): void {
		if ( ! $results ) {
			echo '<p class="hda-fi-empty"><em>' . esc_html__( 'No scan results yet. Click "Run Vulnerability Scan" to check.', 'hda' ) . '</em></p>';

			return;
		}

		$summary     = $results['summary'] ?? [];
		$vulnCount   = ( $summary['vulnerable'] ?? 0 ) + ( $summary['closed'] ?? 0 );
		$statusClass = $vulnCount > 0 ? 'notice-error' : ( ( $summary['outdated'] ?? 0 ) > 0 ? 'notice-warning' : 'notice-success' );
		$statusIcon  = $vulnCount > 0 ? '🚨' : ( ( $summary['outdated'] ?? 0 ) > 0 ? '⚠️' : '✅' );

		$dataSources = implode( ', ', $results['data_sources'] ?? [] );

		printf(
			'<div class="notice %s below-h2 hda-fi-summary"><p>%s <strong>%d plugins</strong>, <strong>%d themes</strong> — ' .
			'<span class="hda-fi-badge hda-fi-badge--critical">%d vulnerable</span> ' .
			'<span class="hda-fi-badge hda-fi-badge--high">%d closed</span> ' .
			'<span class="hda-fi-badge hda-fi-badge--medium">%d abandoned</span> ' .
			'<span class="hda-fi-badge hda-fi-badge--low">%d outdated</span> ' .
			'<span class="hda-fi-badge hda-fi-badge--ok">%d up to date</span> — ' .
			'Sources: %s — %s</p></div>',
			esc_attr( $statusClass ),
			$statusIcon,
			$summary['total_plugins'] ?? 0,
			$summary['total_themes'] ?? 0,
			$summary['vulnerable'] ?? 0,
			$summary['closed'] ?? 0,
			$summary['abandoned'] ?? 0,
			$summary['outdated'] ?? 0,
			$summary['up_to_date'] ?? 0,
			esc_html( $dataSources ),
			esc_html( $results['scanned_at'] ?? '' )
		);

		// Merge plugins + themes for display.
		$allItems = [];
		foreach ( ( $results['plugins'] ?? [] ) as $item ) {
			$item['_type'] = 'Plugin';
			$allItems[]    = $item;
		}
		foreach ( ( $results['themes'] ?? [] ) as $item ) {
			$item['_type'] = 'Theme';
			$allItems[]    = $item;
		}

		// Sort: vulnerable first, then closed, abandoned, outdated, then up_to_date.
		$statusOrder = [
			'vulnerable'   => 0,
			'closed'       => 1,
			'abandoned'    => 2,
			'outdated'     => 3,
			'not_in_wporg' => 4,
			'unknown'      => 5,
			'up_to_date'   => 6,
		];
		usort( $allItems, static fn( $a, $b ) => ( $statusOrder[ $a['status'] ] ?? 9 ) <=> ( $statusOrder[ $b['status'] ] ?? 9 ) );

		echo '<table class="widefat striped hda-fi-table">';
		echo '<thead><tr><th>Name</th><th>Type</th><th>Installed</th><th>Latest</th><th>Status</th><th>Vulnerabilities</th></tr></thead><tbody>';

		foreach ( $allItems as $item ) {
			$statusBadge = match ( $item['status'] ) {
				'vulnerable'   => '<span class="hda-fi-severity hda-fi-severity--critical">Vulnerable</span>',
				'closed'       => '<span class="hda-fi-severity hda-fi-severity--high">Closed</span>',
				'abandoned'    => '<span class="hda-fi-severity hda-fi-severity--medium">Abandoned</span>',
				'outdated'     => '<span class="hda-fi-severity hda-fi-severity--low">Outdated</span>',
				'up_to_date'   => '<span class="hda-fi-severity" style="background:#2e7d32;color:#fff">Up to date</span>',
				'not_in_wporg' => '<span class="hda-fi-severity" style="background:#666;color:#fff">Not in WP.org</span>',
				default        => '<span class="hda-fi-severity" style="background:#999;color:#fff">Unknown</span>',
			};

			$vulns    = $item['vulnerabilities'] ?? [];
			$vulnCell = '—';

			if ( ! empty( $vulns ) ) {
				$vulnLines = [];
				foreach ( $vulns as $vuln ) {
					$cve   = ! empty( $vuln['cve'] ) ? esc_html( $vuln['cve'] ) . ': ' : '';
					$title = esc_html( $vuln['title'] );
					$fixed = ! empty( $vuln['fixed_in'] ) ? ' (fix: ' . esc_html( $vuln['fixed_in'] ) . ')' : '';
					$score = ( $vuln['cvss_score'] ?? 0 ) > 0 ? sprintf( ' [CVSS %.1f]', $vuln['cvss_score'] ) : '';

					$vulnLines[] = sprintf( '<div>%s%s%s%s</div>', $cve, $title, $fixed, $score );
				}

				$vulnCell = implode( '', $vulnLines );
			}

			printf(
				'<tr><td><strong>%s</strong></td><td>%s</td><td><code>%s</code></td><td><code>%s</code></td><td>%s</td><td class="hda-fi-vulns">%s</td></tr>',
				esc_html( $item['name'] ),
				esc_html( $item['_type'] ),
				esc_html( $item['installed_version'] ?? '?' ),
				esc_html( $item['latest_version'] ?? '?' ),
				$statusBadge, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$vulnCell // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		echo '</tbody></table>';
	}
}
