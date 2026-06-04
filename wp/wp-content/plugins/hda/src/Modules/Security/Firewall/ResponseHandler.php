<?php
/**
 * Response Handler — renders block pages for blocked requests.
 *
 * Sends a clean 403 page with minimal HTML/CSS (no external dependencies).
 * Designed to work even if WordPress is only partially loaded.
 *
 * @package HDAddons\Modules\Security\Firewall
 * @author  HD
 */

namespace HDAddons\Modules\Security\Firewall;

\defined( 'ABSPATH' ) || exit;

final class ResponseHandler {

	/**
	 * Block the current request and terminate.
	 *
	 * @param ThreatResult $threat    The detected threat.
	 * @param string       $clientIp  Client IP for display.
	 *
	 * @return never
	 */
	public static function block( ThreatResult $threat, string $clientIp = '' ): never {
		// Prevent caching of 403 responses.
		if ( ! headers_sent() ) {
			status_header( 403 );
			nocache_headers();
			header( 'Content-Type: text/html; charset=utf-8' );
			header( 'X-HDA-Blocked: ' . $threat->attackType );
		}

		echo self::renderBlockPage( $threat, $clientIp );
		exit;
	}

	// --------------------------------------------------

	/**
	 * Block the current request with a simple reason string (no ThreatResult needed).
	 *
	 * Convenience method for Access Control (IP/Country blocking) and other
	 * contexts that don't have a full ThreatResult pipeline.
	 *
	 * @param string $reason   Human-readable reason (e.g. "Country: CN", "IP/Range: 1.2.3.4").
	 * @param string $clientIp Client IP for display.
	 *
	 * @return never
	 */
	public static function blockSimple( string $reason, string $clientIp = '' ): never {
		$threat = new ThreatResult(
			ruleId: 'access_control',
			attackType: 'access_denied',
			severity: 'high',
			matchedValue: $reason,
			context: 'global',
			description: 'Blocked by access control: ' . $reason,
		);

		self::block( $threat, $clientIp );
	}

	// --------------------------------------------------

	/**
	 * Render the 403 block page HTML.
	 *
	 * Self-contained: no external CSS/JS, no WordPress template dependencies.
	 *
	 * @param ThreatResult $threat   The detected threat.
	 * @param string       $clientIp Client IP.
	 *
	 * @return string Full HTML page.
	 */
	private static function renderBlockPage( ThreatResult $threat, string $clientIp ): string {
		$siteName  = function_exists( 'get_bloginfo' ) ? esc_html( get_bloginfo( 'name' ) ) : 'Website';
		$timestamp = gmdate( 'Y-m-d H:i:s' ) . ' UTC';
		$ruleId    = esc_html( $threat->ruleId );
		$type      = esc_html( strtoupper( $threat->attackType ) );
		$severity  = esc_html( ucfirst( $threat->severity ) );
		$ip        = esc_html( $clientIp );

		$severityColor = match ( $threat->severity ) {
			'critical' => '#ef4444',
			'high'     => '#f97316',
			'medium'   => '#eab308',
			default    => '#6b7280',
		};

		return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>403 — Access Denied | {$siteName}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0}
.wrap{max-width:520px;width:90%;text-align:center;padding:2.5rem}
.shield{font-size:4rem;margin-bottom:1rem;opacity:.9}
h1{font-size:1.5rem;font-weight:700;margin-bottom:.5rem;color:#f8fafc}
.sub{color:#94a3b8;font-size:.95rem;margin-bottom:2rem;line-height:1.6}
.card{background:rgba(30,41,59,.7);border:1px solid rgba(148,163,184,.15);border-radius:12px;padding:1.25rem;text-align:left;font-size:.8rem;color:#94a3b8;backdrop-filter:blur(12px)}
.card .row{display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid rgba(148,163,184,.08)}
.card .row:last-child{border:0}
.card .label{color:#64748b}
.card .value{color:#cbd5e1;font-family:'SF Mono',Consolas,monospace;font-size:.75rem}
.severity{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:600;color:#fff;background:{$severityColor}}
.footer{margin-top:2rem;font-size:.75rem;color:#475569}
</style>
</head>
<body>
<div class="wrap">
  <div class="shield">🛡️</div>
  <h1>Access Denied</h1>
  <p class="sub">This request has been blocked by the security firewall.<br>If you believe this is an error, please contact the site administrator.</p>
  <div class="card">
    <div class="row"><span class="label">Type</span><span class="value">{$type}</span></div>
    <div class="row"><span class="label">Severity</span><span class="severity">{$severity}</span></div>
    <div class="row"><span class="label">Rule</span><span class="value">{$ruleId}</span></div>
    <div class="row"><span class="label">Your IP</span><span class="value">{$ip}</span></div>
    <div class="row"><span class="label">Time</span><span class="value">{$timestamp}</span></div>
  </div>
  <p class="footer">Protected by HDA Security Firewall</p>
</div>
</body>
</html>
HTML;
	}
}
