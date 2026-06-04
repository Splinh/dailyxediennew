<?php
/**
 * Redirect module - Manage 301/302 URL redirects and HTTP status code rules.
 *
 * Stores redirect rules and status code rules as JSON in custom posts
 * (hda_storage CPT) and hooks into `template_redirect` to perform
 * server-side redirects or return appropriate HTTP status codes.
 *
 * @package HDAddons\Modules\Redirect
 */

namespace HDAddons\Modules\Redirect;

use HDAddons\Asset;
use HDAddons\Contracts\HasSettings;
use HDAddons\Modules\AbstractModule;

defined( 'ABSPATH' ) || exit;

final class RedirectModule extends AbstractModule implements HasSettings {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'redirect';
	}

	public static function title(): string {
		return 'Redirect';
	}

	public static function description(): string {
		return '301/302 URL redirects and HTTP status code rules.';
	}

	public static function group(): string {
		return 'tools';
	}

	public static function optionKeys(): array {
		return [];
	}

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		// Allow external redirects for whitelisted domains.
		add_filter( 'allowed_redirect_hosts', [ $this, 'allowedRedirectHosts' ] );

		// Frontend: handle redirects.
		if ( ! is_admin() ) {
			add_action( 'template_redirect', $this->handleRedirects( ... ), 1 );

			return;
		}

		// Admin: AJAX endpoints — Redirect rules.
		add_action( 'wp_ajax_hda_redirect_import', RedirectRuleService::ajaxImport( ... ) );
		add_action( 'wp_ajax_hda_redirect_export', RedirectRuleService::ajaxExport( ... ) );
		add_action( 'wp_ajax_hda_redirect_delete', RedirectRuleService::ajaxDelete( ... ) );
		add_action( 'wp_ajax_hda_redirect_delete_all', RedirectRuleService::ajaxDeleteAll( ... ) );
		add_action( 'wp_ajax_hda_redirect_check_dupe', RedirectRuleService::ajaxCheckDupe( ... ) );
		add_action( 'wp_ajax_hda_redirect_save_row', RedirectRuleService::ajaxSaveRow( ... ) );

		// Admin: AJAX endpoints — Status code rules.
		add_action( 'wp_ajax_hda_status_code_import', StatusCodeRuleService::ajaxImport( ... ) );
		add_action( 'wp_ajax_hda_status_code_export', StatusCodeRuleService::ajaxExport( ... ) );
		add_action( 'wp_ajax_hda_status_code_delete', StatusCodeRuleService::ajaxDelete( ... ) );
		add_action( 'wp_ajax_hda_status_code_delete_all', StatusCodeRuleService::ajaxDeleteAll( ... ) );
		add_action( 'wp_ajax_hda_status_code_save_row', StatusCodeRuleService::ajaxSaveRow( ... ) );

		// Localize nonce for JS module.
		add_action(
			'admin_enqueue_scripts',
			static function (): void {
				$handle = Asset::handle( 'settings.js' );
				if ( $handle ) {
					Asset::localize(
						$handle,
						'hdaRedirect',
						[
							'nonce' => wp_create_nonce( 'hda_redirect_manage' ),
							'i18n'  => [
								'importing'       => __( 'Importing...', 'hda' ),
								'import_done'     => __( 'Import complete!', 'hda' ),
								'import_error'    => __( 'Import failed.', 'hda' ),
								'confirm_replace' => __( 'This will replace ALL existing rules with the imported ones. Continue?', 'hda' ),
							],
						]
					);
				}
			},
			50
		);
	}

	// ── Frontend Redirect ───────────────────────────

	/**
	 * Perform redirects and status code responses based on stored rules.
	 */
	public function handleRedirects(): void {
		$requestUri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

		if ( empty( $requestUri ) ) {
			return;
		}

		$requestPath  = wp_parse_url( $requestUri, PHP_URL_PATH );
		$requestPath  = $requestPath ? strtolower( rtrim( $requestPath, '/' ) ) : '';
		$requestQuery = wp_parse_url( $requestUri, PHP_URL_QUERY );

		if ( empty( $requestPath ) ) {
			return;
		}

		// 1. Check status code rules first (401, 410, etc.).
		$scMatch = StatusCodeRuleService::getRulesHashMap()[ $requestPath ] ?? null;

		if ( $scMatch ) {
			$code    = $scMatch['code'];
			$message = match ( $code ) {
				401     => __( 'Unauthorized', 'hda' ),
				410     => __( 'Gone', 'hda' ),
				default => __( 'Error', 'hda' ),
			};

			status_header( $code );
			nocache_headers();
			wp_die(
				esc_html( $message ),
				esc_html( $code . ' ' . $message ),
				[ 'response' => $code ]
			);
		}

		// 2. Check redirect rules (301/302).
		$lookup = RedirectRuleService::getRulesHashMap();

		if ( empty( $lookup ) ) {
			return;
		}

		$match = $lookup[ $requestPath ] ?? null;

		if ( ! $match ) {
			return;
		}

		$destination = $match['to'];
		$statusCode  = $match['type'];

		// Prevent redirect loops.
		$destHost = wp_parse_url( $destination, PHP_URL_HOST );
		$destPath = wp_parse_url( $destination, PHP_URL_PATH );
		$sameHost = ! $destHost || strcasecmp( $destHost, wp_parse_url( home_url(), PHP_URL_HOST ) ) === 0;

		if ( $sameHost && $destPath && strtolower( rtrim( $destPath, '/' ) ) === $requestPath ) {
			return;
		}

		// Preserve original query string if the destination has none.
		if ( $requestQuery && ! wp_parse_url( $destination, PHP_URL_QUERY ) ) {
			$destination .= '?' . $requestQuery;
		}

		// Use wp_safe_redirect() to prevent open redirect vulnerabilities.
		// External redirects are only allowed if the domain is whitelisted.
		wp_safe_redirect( $destination, $statusCode );
		exit;
	}

	// ── Allowed Redirect Hosts ──────────────────────

	/**
	 * Add whitelisted domains to allowed redirect hosts.
	 *
	 * @param array<string> $hosts Allowed hosts.
	 * @return array<string>
	 */
	public function allowedRedirectHosts( array $hosts ): array {
		$allowed = get_option( 'hda_redirect_allowed_domains', [] );

		if ( is_array( $allowed ) && ! empty( $allowed ) ) {
			$hosts = array_merge( $hosts, $allowed );
		}

		return $hosts;
	}

	// ── HasSettings ─────────────────────────────────

	public static function saveSettings( array $data ): void {
		RedirectRuleService::saveBatch( $data );
		StatusCodeRuleService::saveBatch( $data );
	}
}
