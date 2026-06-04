<?php
/**
 * Logs module - Centralizes activity, 404, and traffic logging.
 *
 * @package HDAddons\Modules\Logs
 */

namespace HDAddons\Modules\Logs;

use HDAddons\Contracts\HasDatabaseSchema;
use HDAddons\Contracts\HasSettings;
use HDAddons\Modules\AbstractModule;
use HDAddons\Modules\Logs\ActivityLog\ActivityLog;
use HDAddons\Modules\Logs\Monitor404\Monitor404;
use HDAddons\Modules\Logs\TrafficMonitor\TrafficLogger;
use HDAddons\Modules\Logs\TrafficMonitor\TrafficMonitor;

defined( 'ABSPATH' ) || exit;

final class LogsModule extends AbstractModule implements HasSettings, HasDatabaseSchema {

	// ── ModuleInterface ─────────────────────────────

	public static function slug(): string {
		return 'logs';
	}

	public static function title(): string {
		return 'Logs';
	}

	public static function description(): string {
		return 'Centralized logging for activity, 404 errors, and traffic.';
	}

	public static function group(): string {
		return 'tools';
	}

	public static function optionKeys(): array {
		return [ self::optionKey() ];
	}

	public static function cronHooks(): array {
		return [
			'hda_activity_log_cleanup',
			'hda_404_log_cleanup',
			'hda_traffic_log_cleanup',
		];
	}

	// ── HasDatabaseSchema ──────────────────────────

	/** @inheritDoc */
	public static function databaseSchemas(): array {
		return [
			ActivityLog::TABLE_NAME   => <<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint unsigned DEFAULT 0,
			username varchar(60) NOT NULL DEFAULT '',
			action varchar(20) NOT NULL DEFAULT 'login',
			ip_address varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at),
			KEY ip_address (ip_address)
			SQL,

			Monitor404::TABLE_NAME    => <<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			url_hash char(32) NOT NULL DEFAULT '',
			url varchar(2048) NOT NULL DEFAULT '',
			referer varchar(2048) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			hit_count int unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY url_hash (url_hash),
			KEY url (url(191)),
			KEY hit_count (hit_count),
			KEY updated_at (updated_at)
			SQL,

			TrafficLogger::TABLE_NAME => <<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			ip varchar(45) NOT NULL DEFAULT '',
			country varchar(2) DEFAULT NULL,
			uri varchar(2048) NOT NULL DEFAULT '',
			method varchar(10) NOT NULL DEFAULT 'GET',
			user_agent varchar(512) DEFAULT NULL,
			action varchar(20) NOT NULL DEFAULT 'allowed',
			attack_type varchar(30) DEFAULT NULL,
			rule_id varchar(50) DEFAULT NULL,
			severity varchar(10) DEFAULT NULL,
			matched text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_ip (ip),
			KEY idx_action (action),
			KEY idx_created (created_at),
			KEY idx_attack_type (attack_type)
			SQL,
		];
	}

	// ── Boot ────────────────────────────────────────

	public function boot(): void {
		// Boot sub-components — they read their options from hda_logs[sub_key].
		new ActivityLog();
		new TrafficMonitor();

		$logger = new Monitor404();
		add_action( 'template_redirect', $logger->log404( ... ), 999 );

		// Cron cleanup for 404 — scheduling handled by cronHooks() + Activator.
		add_action( 'hda_404_log_cleanup', Monitor404::cleanup( ... ) );

		if ( is_admin() ) {
			new LogsAdmin();
		}
	}

	// ── HasSettings ─────────────────────────────────

	public static function saveSettings( array $data ): void {
		if ( isset( $data['activity_log'] ) ) {
			ActivityLog::saveSettings( (array) $data['activity_log'] );
		}
		if ( isset( $data['monitor_404'] ) ) {
			Monitor404::saveSettings( (array) $data['monitor_404'] );
		}
		if ( isset( $data['traffic_monitor'] ) ) {
			TrafficMonitor::saveSettings( (array) $data['traffic_monitor'] );
		}
	}
}
