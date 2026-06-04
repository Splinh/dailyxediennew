<?php
/**
 * Form Module — Entry Point
 *
 * Auto-discovered by ModuleRegistry. Coordinates all Form subsystem
 * initialization: Cron hooks, admin pages, REST API.
 *
 * @package SPL\Modules\Form
 */

namespace SPL\Modules\Form;

use SPL\Contracts\HasAdminContext;
use SPL\Contracts\HasDatabaseSchema;
use SPL\Modules\AbstractModule;
use SPL\Modules\Form\Admin\FormEntriesPage;
use SPL\Modules\Form\Admin\FormExporter;
use SPL\Modules\Form\Admin\FormLogsPage;
use SPL\Modules\Form\Admin\FormSettingsPage;
use SPL\Modules\Form\API\FormAPI;
use SPL\Modules\Form\API\DynamicFieldAPI;
use SPL\Core\Helper;
use SPL\Modules\Form\Cron\AsyncFormProcessor;
use SPL\Modules\Form\Cron\FormEntryCleaner;
use SPL\Modules\Form\Cron\MailQueueProcessor;
use SPL\Modules\Form\Cron\WeeklyDigestCron;

defined( 'ABSPATH' ) || exit;

final class FormModule extends AbstractModule implements HasAdminContext, HasDatabaseSchema {

	/* ---------- ModuleInterface --------------------------------- */

	public static function slug(): string {
		return 'form';
	}

	/** Controlled by 'enabled' key in _hd_build_form_config(). */
	public static function isActive(): bool {
		$config = Helper::filterSettingOptions( 'form_config' );

		return ! empty( $config['enabled'] );
	}

	/**
	 * REST API controllers owned by this module.
	 *
	 * @return array<class-string<\WP_REST_Controller>>
	 */
	public static function apiClasses(): array {
		return [
			FormAPI::class,
			DynamicFieldAPI::class,
		];
	}

	/* ---------- HasDatabaseSchema ------------------------------- */

	/** @inheritDoc */
	public static function databaseSchemas(): array {
		return [
			'hd_form_entries' => <<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			form_type varchar(50) NOT NULL DEFAULT '' COMMENT 'contact|quote|service|registration',
			form_id varchar(100) NOT NULL DEFAULT '' COMMENT 'Instance slug',
			status varchar(20) NOT NULL DEFAULT 'new' COMMENT 'new|read|starred|spam|trash',
			submission_hash varchar(64) DEFAULT NULL COMMENT 'Duplicate submission idempotency key',
			name varchar(255) NOT NULL DEFAULT '',
			email varchar(255) NOT NULL DEFAULT '',
			phone varchar(30) NOT NULL DEFAULT '',
			phone_country varchar(5) NOT NULL DEFAULT '',
			phone_national varchar(30) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(500) NOT NULL DEFAULT '',
			referer_url varchar(500) NOT NULL DEFAULT '',
			page_url varchar(500) NOT NULL DEFAULT '',
			utm_source varchar(200) NOT NULL DEFAULT '',
			utm_medium varchar(200) NOT NULL DEFAULT '',
			utm_campaign varchar(200) NOT NULL DEFAULT '',
			utm_term varchar(200) NOT NULL DEFAULT '',
			utm_content varchar(200) NOT NULL DEFAULT '',
			data longtext NOT NULL COMMENT 'JSON fields',
			notes text NOT NULL,
			is_spam tinyint(1) NOT NULL DEFAULT 0,
			user_id bigint unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_form_type (form_type),
			KEY idx_form_id (form_id),
			KEY idx_status (status),
			KEY idx_email (email),
			KEY idx_phone (phone),
			KEY idx_ip (ip_address),
			KEY idx_is_spam (is_spam),
			KEY idx_created_at (created_at),
			KEY idx_type_status (form_type, status),
			KEY idx_type_created (form_type, created_at),
			KEY idx_utm_source (utm_source),
			KEY idx_utm_campaign (utm_campaign),
			UNIQUE KEY uniq_form_submission (form_type, submission_hash)
			SQL,

			'hd_mail_queue'   => <<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			entry_id bigint unsigned NOT NULL DEFAULT 0,
			channel varchar(50) NOT NULL DEFAULT 'email',
			to_email varchar(255) NOT NULL DEFAULT '',
			subject varchar(500) NOT NULL DEFAULT '',
			body longtext NOT NULL,
			headers text NOT NULL,
			attachments text NOT NULL,
			payload longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending|processing|sent|failed|dead',
			worker_token varchar(64) DEFAULT NULL,
			attempts tinyint unsigned NOT NULL DEFAULT 0,
			max_attempts tinyint unsigned NOT NULL DEFAULT 3,
			last_error text NOT NULL,
			scheduled_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			sent_at datetime NULL DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_status (status),
			KEY idx_channel_status (channel, status),
			KEY idx_entry_id (entry_id),
			KEY idx_scheduled_status (scheduled_at, status)
			SQL,

			'hd_form_logs'    => <<<'SQL'
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			entry_id bigint unsigned NOT NULL DEFAULT 0,
			event varchar(50) NOT NULL DEFAULT '',
			message text NOT NULL,
			context text NOT NULL,
			actor varchar(100) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_entry_id (entry_id),
			KEY idx_event (event),
			KEY idx_created_at (created_at)
			SQL,
		];
	}

	/* ---------- Boot -------------------------------------------- */

	public function boot(): void {
		FormConfig::register();

		// Register cron hooks.
		MailQueueProcessor::init();
		FormEntryCleaner::init();
		WeeklyDigestCron::init();
		AsyncFormProcessor::init();

		// Export handler (admin-post action — must be registered early).
		FormExporter::register();

		// Inject honeypot payload into splConfig on frontend (after Theme localize at priority 10).
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', [ self::class, 'injectFrontendConfig' ], 20 );
		}
	}

	/**
	 * Merge form-specific config into the global splConfig JS object.
	 */
	public static function injectFrontendConfig(): void {
		if ( ! wp_script_is( 'jquery-core', 'registered' ) && ! wp_script_is( 'jquery-core' ) ) {
			return;
		}

		$payload = Security\HoneypotGuard::payload();
		$json    = wp_json_encode( $payload );

		if ( false !== $json ) {
			wp_add_inline_script(
				'jquery-core',
				sprintf( 'window.splConfig=window.splConfig||{};window.splConfig.form={honeypot:%s};', $json ),
				'before'
			);
		}
	}

	/* ---------- HasAdminContext ---------------------------------- */

	public function adminBoot(): void {
		FormEntriesPage::register();
		FormLogsPage::register();
		FormSettingsPage::register();
	}
}
