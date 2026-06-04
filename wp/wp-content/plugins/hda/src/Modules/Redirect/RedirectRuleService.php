<?php
/**
 * Redirect Rule Service – CRUD, caching and AJAX for 301/302 redirect rules.
 *
 * Extracted from RedirectModule to keep single-responsibility.
 *
 * @package HDAddons\Modules\Redirect
 */

namespace HDAddons\Modules\Redirect;

use HDAddons\Helper;
use HDAddons\Plugin;

defined( 'ABSPATH' ) || exit;

final class RedirectRuleService {

	/**
	 * Cache key for redirect rules (object cache).
	 */
	private const CACHE_KEY   = 'hda_redirect_rules';
	private const CACHE_GROUP = 'hda';

	/**
	 * Items per page for the admin table.
	 */
	public const PER_PAGE = 20;

	/**
	 * Storage key for redirect rules (stored option / CPT).
	 */
	public const OPTION_NAME = 'redirect_rules';

	// ── Rules CRUD ──────────────────────────────────

	/**
	 * Get stored redirect rules (with object caching).
	 *
	 * @return array<int, array{from: string, to: string, type: int}>
	 */
	public static function getRules(): array {
		$cached = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$json = Helper::getStoredOptionContent( self::OPTION_NAME );

		if ( empty( $json ) ) {
			wp_cache_set( self::CACHE_KEY, [], self::CACHE_GROUP );

			return [];
		}

		try {
			$rules = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $e ) {
			Helper::errorLog( '[HDA Redirect] Invalid JSON in stored rules: ' . $e->getMessage() );

			return [];
		}

		$rules = is_array( $rules ) ? $rules : [];

		wp_cache_set( self::CACHE_KEY, $rules, self::CACHE_GROUP );

		return $rules;
	}

	/**
	 * Get paginated redirect rules for the admin table.
	 *
	 * @return array{rules: array, total: int, total_pages: int, page: int}
	 */
	public static function getPaginated( int $page = 1 ): array {
		$rules      = self::getRules();
		$total      = count( $rules );
		$totalPages = max( 1, (int) ceil( $total / self::PER_PAGE ) );
		$page       = max( 1, min( $page, $totalPages ) );
		$offset     = ( $page - 1 ) * self::PER_PAGE;

		return [
			'rules'       => array_slice( $rules, $offset, self::PER_PAGE ),
			'total'       => $total,
			'total_pages' => $totalPages,
			'page'        => $page,
			'offset'      => $offset,
		];
	}

	/**
	 * Save redirect rules.
	 */
	public static function saveRules( array $rules ): void {
		$sanitized = [];

		foreach ( $rules as $rule ) {
			$from = trim( sanitize_text_field( $rule['from'] ?? '' ) );
			$to   = trim( esc_url_raw( $rule['to'] ?? '' ) );
			$type = (int) ( $rule['type'] ?? 301 );

			if ( empty( $from ) || empty( $to ) ) {
				continue;
			}

			// Normalize: ensure "from" starts with /.
			if ( ! str_starts_with( $from, '/' ) ) {
				$from = '/' . $from;
			}

			// Only allow 301 or 302.
			if ( ! in_array( $type, [ 301, 302 ], true ) ) {
				$type = 301;
			}

			// De-duplicate: use normalized path as key.
			$normalizedKey               = strtolower( rtrim( $from, '/' ) );
			$sanitized[ $normalizedKey ] = [
				'from' => $from,
				'to'   => $to,
				'type' => $type,
			];
		}

		$sanitized = array_values( $sanitized );

		if ( empty( $sanitized ) ) {
			Helper::deleteStoredOption( self::OPTION_NAME );
		} else {
			$json = wp_json_encode( $sanitized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			Helper::updateStoredOption( self::OPTION_NAME, $json, 'application/json' );
		}

		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
		wp_cache_delete( self::CACHE_KEY . '_map', self::CACHE_GROUP );
	}

	/**
	 * Get rules as a hashmap, cached for frontend O(1) lookup.
	 *
	 * @return array<string, array>
	 */
	public static function getRulesHashMap(): array {
		$cached = wp_cache_get( self::CACHE_KEY . '_map', self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$rules  = self::getRules();
		$lookup = [];

		foreach ( $rules as $rule ) {
			$key            = strtolower( rtrim( $rule['from'], '/' ) );
			$lookup[ $key ] = $rule;
		}

		wp_cache_set( self::CACHE_KEY . '_map', $lookup, self::CACHE_GROUP );

		return $lookup;
	}

	// ── AJAX: Check Duplicate ───────────────────────

	public static function ajaxCheckDupe(): void {
		check_ajax_referer( 'hda_redirect_manage', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'hda' ) ] );
		}

		$from = trim( sanitize_text_field( wp_unslash( $_POST['from'] ?? '' ) ) );

		if ( empty( $from ) ) {
			wp_send_json_success( [ 'exists' => false ] );
		}

		if ( ! str_starts_with( $from, '/' ) ) {
			$from = '/' . $from;
		}

		$key   = strtolower( rtrim( $from, '/' ) );
		$rules = self::getRules();

		foreach ( $rules as $rule ) {
			if ( strtolower( rtrim( $rule['from'], '/' ) ) === $key ) {
				wp_send_json_success(
					[
						'exists'      => true,
						'existing_to' => $rule['to'],
					]
				);
			}
		}

		wp_send_json_success( [ 'exists' => false ] );
	}

	// ── AJAX: Delete ────────────────────────────────

	public static function ajaxDelete(): void {
		check_ajax_referer( 'hda_redirect_manage', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'hda' ) ] );
		}

		$indices = isset( $_POST['indices'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['indices'] ) ) : [];

		if ( empty( $indices ) ) {
			wp_send_json_error( [ 'message' => __( 'No rules specified.', 'hda' ) ] );
		}

		$rules = self::getRules();

		rsort( $indices );
		foreach ( $indices as $idx ) {
			if ( isset( $rules[ $idx ] ) ) {
				array_splice( $rules, $idx, 1 );
			}
		}

		self::saveRules( $rules );

		wp_send_json_success(
			[
				'message' => sprintf(
					__( 'Deleted %1$d rule(s). Remaining: %2$d.', 'hda' ),
					count( $indices ),
					count( $rules )
				),
				'total'   => count( $rules ),
			]
		);
	}

	/**
	 * Delete all redirect rules.
	 */
	public static function ajaxDeleteAll(): void {
		check_ajax_referer( 'hda_redirect_manage', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'hda' ) ] );
		}

		self::saveRules( [] );

		wp_send_json_success(
			[
				'message' => __( 'All redirect rules have been deleted.', 'hda' ),
				'total'   => 0,
			]
		);
	}

	// ── AJAX: Save Row ──────────────────────────────

	/**
	 * Save a single redirect rule via AJAX (add or edit).
	 *
	 * Accepts: from, to, type, old_from (for edits).
	 */
	public static function ajaxSaveRow(): void {
		check_ajax_referer( 'hda_redirect_manage', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'hda' ) ] );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
		$from    = trim( sanitize_text_field( wp_unslash( $_POST['from'] ?? '' ) ) );
		$to      = trim( esc_url_raw( wp_unslash( $_POST['to'] ?? '' ) ) );
		$type    = absint( $_POST['type'] ?? 301 );
		$oldFrom = trim( sanitize_text_field( wp_unslash( $_POST['old_from'] ?? '' ) ) );

		if ( empty( $from ) || empty( $to ) ) {
			wp_send_json_error( [ 'message' => __( 'From and To fields are required.', 'hda' ) ] );
		}

		$rules = self::getRules();

		// If editing an existing rule, remove the old entry.
		if ( '' !== $oldFrom ) {
			$normalizedOld = strtolower( rtrim( $oldFrom, '/' ) );
			$rules         = array_values(
				array_filter(
					$rules,
					fn( array $r ) => strtolower( rtrim( $r['from'], '/' ) ) !== $normalizedOld,
				)
			);
		}

		// Append new/edited rule — saveRules handles sanitization + dedup.
		$rules[] = [
			'from' => $from,
			'to'   => $to,
			'type' => $type,
		];

		self::saveRules( $rules );

		wp_send_json_success(
			[
				'message' => __( 'Rule saved.', 'hda' ),
				'total'   => count( self::getRules() ),
			]
		);
	}

	// ── AJAX: Import ────────────────────────────────

	public static function ajaxImport(): void {
		check_ajax_referer( 'hda_redirect_manage', '_nonce' );

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'hda' ) ] );
		}

		if ( empty( $_FILES['import_file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'hda' ) ] );
		}

		$file     = $_FILES['import_file'];
		$mimeType = $file['type'] ?? '';
		$tmpName  = $file['tmp_name'] ?? '';
		$mode     = sanitize_key( wp_unslash( $_POST['import_mode'] ?? 'append' ) );

		if ( empty( $tmpName ) || ! is_uploaded_file( $tmpName ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid file upload.', 'hda' ) ] );
		}

		// Validate extension.
		$ext = strtolower( pathinfo( $file['name'] ?? '', PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, [ 'csv', 'xlsx' ], true ) ) {
			wp_send_json_error( [ 'message' => __( 'Only CSV and XLSX files are accepted.', 'hda' ) ] );
		}

		try {
			$result = RedirectImportExport::parseFile( $tmpName, $mimeType, sanitize_file_name( (string) ( $file['name'] ?? '' ) ) );
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA Redirect] Import parse error: ' . $e->getMessage() );
			wp_send_json_error( [ 'message' => __( 'Failed to parse file.', 'hda' ) . ' ' . $e->getMessage() ] );
		}

		$importedRules = $result['rules'] ?? [];

		if ( empty( $importedRules ) ) {
			wp_send_json_error(
				[
					'message' => __( 'No valid rules found in the file.', 'hda' ),
					'errors'  => $result['errors'] ?? [],
				]
			);
		}

		// Merge or replace — with duplicate detection.
		$existing = self::getRules();
		$skipped  = 0;

		if ( 'replace' === $mode ) {
			$finalRules = $importedRules;
		} else {
			$existingPaths = [];
			foreach ( $existing as $rule ) {
				$key                   = strtolower( rtrim( $rule['from'], '/' ) );
				$existingPaths[ $key ] = true;
			}

			$uniqueImported = [];
			foreach ( $importedRules as $rule ) {
				$key = strtolower( rtrim( $rule['from'], '/' ) );

				if ( isset( $existingPaths[ $key ] ) ) {
					++$skipped;
					continue;
				}

				$existingPaths[ $key ] = true;
				$uniqueImported[]      = $rule;
			}

			$finalRules = array_merge( $existing, $uniqueImported );
		}

		self::saveRules( $finalRules );

		$savedCount  = count( self::getRules() );
		$addedCount  = 'replace' === $mode ? count( $importedRules ) : count( $uniqueImported ?? $importedRules );
		$parseErrors = $result['errors'] ?? [];

		if ( $skipped > 0 ) {
			$parseErrors[] = sprintf(
				__( '%d rule(s) skipped — "from" path already exists.', 'hda' ),
				$skipped
			);
		}

		wp_send_json_success(
			[
				'message'  => sprintf(
					__( 'Added %1$d rules. Total rules: %2$d.', 'hda' ),
					$addedCount,
					$savedCount
				),
				'imported' => $addedCount,
				'skipped'  => $skipped,
				'total'    => $savedCount,
				'errors'   => $parseErrors,
			]
		);
	}

	// ── AJAX: Export ────────────────────────────────

	public static function ajaxExport(): void {
		$nonce = sanitize_text_field( wp_unslash( $_GET['_nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'hda_redirect_manage' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'hda' ), 403 );
		}

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'hda' ), 403 );
		}

		$format = sanitize_key( $_GET['format'] ?? 'csv' );

		if ( ! in_array( $format, [ 'csv', 'xlsx' ], true ) ) {
			$format = 'csv';
		}

		$rules = self::getRules();

		if ( empty( $rules ) ) {
			wp_die( esc_html__( 'No redirect rules to export.', 'hda' ) );
		}

		try {
			$filePath = RedirectImportExport::exportToFile( $rules, $format );
		} catch ( \Throwable $e ) {
			Helper::errorLog( '[HDA Redirect] Export error: ' . $e->getMessage() );
			wp_die( esc_html__( 'Export failed.', 'hda' ) );
		}

		$filename    = 'hda-redirects-' . gmdate( 'Y-m-d' ) . '.' . $format;
		$contentType = 'xlsx' === $format
			? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
			: 'text/csv';

		header( 'Content-Type: ' . $contentType );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $filePath ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $filePath );

		wp_delete_file( $filePath );
		exit;
	}

	// ── Bulk Save (from saveSettings form) ──────────

	/**
	 * Process batch save from the settings form.
	 */
	public static function saveBatch( array $data ): void {
		$existingRules = self::getRules();
		$lookup        = [];

		foreach ( $existingRules as $rule ) {
			$key            = strtolower( rtrim( $rule['from'], '/' ) );
			$lookup[ $key ] = $rule;
		}

		if ( ! empty( $data['redirect_from'] ) ) {
			$fromArr    = (array) $data['redirect_from'];
			$toArr      = (array) ( $data['redirect_to'] ?? [] );
			$typeArr    = (array) ( $data['redirect_type'] ?? [] );
			$oldFromArr = (array) ( $data['redirect_old_from'] ?? [] );

			foreach ( $fromArr as $i => $from ) {
				$newRule = [
					'from' => $from,
					'to'   => $toArr[ $i ] ?? '',
					'type' => (int) ( $typeArr[ $i ] ?? 301 ),
				];

				$oldFrom = $oldFromArr[ $i ] ?? '';

				if ( ! empty( $oldFrom ) ) {
					$oldKey = strtolower( rtrim( $oldFrom, '/' ) );
					if ( isset( $lookup[ $oldKey ] ) ) {
						unset( $lookup[ $oldKey ] );
					}
				}

				if ( ! empty( $from ) ) {
					$newKey            = strtolower( rtrim( $from, '/' ) );
					$lookup[ $newKey ] = $newRule;
				}
			}
		}

		self::saveRules( array_values( $lookup ) );
	}
}
