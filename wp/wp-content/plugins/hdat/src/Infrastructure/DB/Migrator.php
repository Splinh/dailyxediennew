<?php
/**
 * @package HDAT\Infrastructure\DB
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Idempotent schema migrator.
 *
 * Mirrors the structure of the legacy hd-ai-toolkit `Migration` class — one
 * `DB::createTable()` call per table, with the column block written as a
 * nowdoc heredoc. dbDelta is additive only; we keep schemas lean here so
 * fresh installs don't carry the legacy clutter that earlier hdat versions
 * accumulated.
 *
 * Bump VERSION when SQL below changes; the option gate prevents needless
 * dbDelta() calls on every plugins_loaded.
 */
final class Migrator {

	private const VERSION_OPTION = 'hdat_db_version';

	public const VERSION = 7;

	public function run(): void {
		$installedVersion = (int) get_option( self::VERSION_OPTION, 0 );
		$tablesExist      = $this->allTablesExist();

		if ( $installedVersion >= self::VERSION && $tablesExist ) {
			return;
		}

		$this->createTables();

		update_option( self::VERSION_OPTION, self::VERSION );
	}

	public function forceRun(): void {
		delete_option( self::VERSION_OPTION );
		$this->run();
	}

	private function allTablesExist(): bool {
		foreach ( Schema::all() as $table ) {
			if ( ! DB::tableExists( $table ) ) {
				return false;
			}
		}
		return true;
	}

	private function createTables(): void {
		DB::createTable(
			Schema::AI_KEYS,
			<<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			provider varchar(50) NOT NULL,
			label varchar(100) NOT NULL DEFAULT '',
			api_key_hash varchar(8) NOT NULL DEFAULT '',
			api_key_enc text NOT NULL,
			base_url varchar(255) NOT NULL DEFAULT '',
			tier varchar(10) NOT NULL DEFAULT 'free',
			priority smallint unsigned NOT NULL DEFAULT 10,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			capabilities_json longtext NULL,
			custom_provider_meta longtext NULL,
			expires_at datetime NULL,
			daily_token_limit bigint unsigned NULL,
			monthly_token_limit bigint unsigned NULL,
			default_rpm int unsigned NULL,
			default_rpd int unsigned NULL,
			default_tpm int unsigned NULL,
			default_tpd int unsigned NULL,
			last_used_at datetime NULL,
			cooldown_until datetime NULL,
			preferred_model varchar(191) NULL,
			model_status varchar(20) NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_pool_lookup (is_active, priority, provider),
			KEY idx_provider (provider)
			SQL
		);

		DB::createTable(
			Schema::CONSUMER_TOKENS,
			<<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			name varchar(120) NOT NULL DEFAULT '',
			token_hash char(64) NOT NULL,
			token_prefix varchar(64) NOT NULL DEFAULT '',
			allowed_providers_json longtext NULL,
			allowed_models_json longtext NULL,
			internal_only tinyint(1) NOT NULL DEFAULT 0,
			daily_token_limit bigint unsigned NULL,
			monthly_token_limit bigint unsigned NULL,
			rpm_limit int unsigned NULL,
			rpd_limit int unsigned NULL,
			tpm_limit bigint unsigned NULL,
			tpd_limit bigint unsigned NULL,
			expires_at datetime NULL,
			revoked_at datetime NULL,
			last_used_at datetime NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_token_hash (token_hash),
			KEY idx_token_prefix (token_prefix),
			KEY idx_token_status (revoked_at, expires_at)
			SQL
		);

		DB::createTable(
			Schema::USAGE_LEDGER,
			<<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			consumer_token_id bigint unsigned NULL,
			credential_id bigint unsigned NULL,
			provider varchar(50) NOT NULL DEFAULT '',
			model varchar(191) NOT NULL DEFAULT '',
			route_hash char(64) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'success',
			prompt_tokens int unsigned NOT NULL DEFAULT 0,
			completion_tokens int unsigned NOT NULL DEFAULT 0,
			total_tokens int unsigned NOT NULL DEFAULT 0,
			cost decimal(12,6) NULL,
			latency_ms int unsigned NULL,
			error_code varchar(64) NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_consumer (consumer_token_id, created_at),
			KEY idx_credential (credential_id, created_at),
			KEY idx_provider_model (provider, model, created_at),
			KEY idx_created (created_at),
			KEY idx_route (route_hash)
			SQL
		);

		DB::createTable(
			Schema::RESPONSE_CACHE,
			<<<'SQL'
			hash_key char(64) NOT NULL,
			provider varchar(50) NOT NULL DEFAULT '',
			model varchar(191) NOT NULL DEFAULT '',
			response_json longtext NOT NULL,
			tokens_used int unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (hash_key),
			KEY idx_expires (expires_at)
			SQL
		);

		DB::createTable(
			Schema::ROUTE_STATE,
			<<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			route_hash char(64) NOT NULL,
			scope varchar(50) NOT NULL DEFAULT 'global',
			provider varchar(50) NOT NULL DEFAULT '',
			model varchar(191) NOT NULL DEFAULT '',
			credential_id bigint unsigned NULL,
			consecutive_failures int unsigned NOT NULL DEFAULT 0,
			avg_latency_ms int unsigned NOT NULL DEFAULT 0,
			last_success_at datetime NULL,
			last_failure_at datetime NULL,
			last_failure_category varchar(50) NOT NULL DEFAULT '',
			circuit_open_until datetime NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_route_hash_scope (route_hash, scope),
			KEY idx_credential (credential_id),
			KEY idx_circuit (circuit_open_until)
			SQL
		);

		DB::createTable(
			Schema::QUOTA_WINDOWS,
			<<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			route_hash char(64) NOT NULL DEFAULT '',
			consumer_token_id bigint unsigned NULL,
			credential_id bigint unsigned NULL,
			window_type varchar(20) NOT NULL DEFAULT '',
			window_start datetime NOT NULL,
			window_end datetime NOT NULL,
			request_count int unsigned NOT NULL DEFAULT 0,
			used_tokens bigint unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_route_window (route_hash, window_type, window_start),
			KEY idx_consumer_window (consumer_token_id, window_type, window_start),
			KEY idx_credential_window (credential_id, window_type, window_start),
			KEY idx_window_end (window_end)
			SQL
		);

		DB::createTable(
			Schema::STICKY_ROUTES,
			<<<'SQL'
			sticky_key char(64) NOT NULL,
			consumer_token_id bigint unsigned NULL,
			route_hash char(64) NOT NULL DEFAULT '',
			credential_id bigint unsigned NULL,
			provider varchar(50) NOT NULL DEFAULT '',
			model varchar(191) NOT NULL DEFAULT '',
			last_used_at datetime NULL,
			expires_at datetime NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (sticky_key),
			KEY idx_consumer_route (consumer_token_id, route_hash),
			KEY idx_expires_at (expires_at)
			SQL
		);

		DB::clearSchemaCache();
	}
}
