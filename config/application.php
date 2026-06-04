<?php
/**
 * Base production configuration. Environment-specific overrides go in config/environments/{{WP_ENV}}.php
 *
 * @package HD
 */

use Roots\WPConfig\Config;
use function Env\env;

/** USE_ENV_ARRAY + CONVERT_* + STRIP_QUOTES */
\Env\Env::$options = 31;


/* ==========================================================================
	Environment Setup
	========================================================================== */

$root_dir = dirname( __DIR__ );

/** Load .env file (.env.local overrides .env if exists) */
if ( file_exists( $root_dir . '/.env' ) ) {
	$env_files  = file_exists( $root_dir . '/.env.local' ) ? [ '.env', '.env.local' ] : [ '.env' ];
	$repository = \Dotenv\Repository\RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();
	$dotenv     = \Dotenv\Dotenv::create( $repository, $root_dir, $env_files, false );
	$dotenv->load();

	$dotenv->required( [ 'WP_HOME', 'WP_SITEURL' ] );
	env( 'DATABASE_URL' ) || $dotenv->required( [ 'DB_NAME', 'DB_USER', 'DB_PASSWORD' ] );
}

/** WP Environment (default: production) */
define( 'WP_ENV', env( 'WP_ENV' ) ?? 'production' );

! env( 'WP_ENVIRONMENT_TYPE' )
	&& in_array( WP_ENV, [ 'production', 'staging', 'development', 'local' ], true )
	&& Config::define( 'WP_ENVIRONMENT_TYPE', WP_ENV );


/* ==========================================================================
	URLs
	========================================================================== */

Config::define( 'WP_HOME', env( 'WP_HOME' ) );
Config::define( 'WP_SITEURL', env( 'WP_SITEURL' ) );

defined( 'WP_HOME' ) || define( 'WP_HOME', env( 'WP_HOME' ) );
defined( 'WP_SITEURL' ) || define( 'WP_SITEURL', env( 'WP_SITEURL' ) );


/* ==========================================================================
	Database
	========================================================================== */

env( 'DB_SSL' ) && Config::define( 'MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL );

Config::define( 'DB_NAME', env( 'DB_NAME' ) );
Config::define( 'DB_USER', env( 'DB_USER' ) );
Config::define( 'DB_PASSWORD', env( 'DB_PASSWORD' ) );
Config::define( 'DB_HOST', env( 'DB_HOST' ) ?? 'localhost' );
Config::define( 'DB_CHARSET', env( 'DB_CHARSET' ) ?? 'utf8mb4' );
Config::define( 'DB_COLLATE', env( 'DB_COLLATE' ) ?? 'utf8mb4_unicode_520_ci' );

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$table_prefix = env( 'DB_PREFIX' ) ?? 'wp_';

/** DATABASE_URL overrides individual DB settings */
if ( env( 'DATABASE_URL' ) ) {
	$dsn = (object) wp_parse_url( env( 'DATABASE_URL' ) );
	Config::define( 'DB_NAME', substr( $dsn->path, 1 ) );
	Config::define( 'DB_USER', $dsn->user );
	Config::define( 'DB_PASSWORD', $dsn->pass ?? null );
	Config::define( 'DB_HOST', isset( $dsn->port ) ? "{$dsn->host}:{$dsn->port}" : $dsn->host );
}


/* ==========================================================================
	Authentication Keys & Salts
	========================================================================== */

Config::define( 'SECRET_KEY', env( 'SECRET_KEY' ) );
Config::define( 'AUTH_KEY', env( 'AUTH_KEY' ) );
Config::define( 'SECURE_AUTH_KEY', env( 'SECURE_AUTH_KEY' ) );
Config::define( 'LOGGED_IN_KEY', env( 'LOGGED_IN_KEY' ) );
Config::define( 'NONCE_KEY', env( 'NONCE_KEY' ) );
Config::define( 'AUTH_SALT', env( 'AUTH_SALT' ) );
Config::define( 'SECURE_AUTH_SALT', env( 'SECURE_AUTH_SALT' ) );
Config::define( 'LOGGED_IN_SALT', env( 'LOGGED_IN_SALT' ) );
Config::define( 'NONCE_SALT', env( 'NONCE_SALT' ) );


/* ==========================================================================
	WordPress Settings
	========================================================================== */

/** Security */
Config::define( 'DISALLOW_FILE_EDIT', true );
Config::define( 'DISALLOW_FILE_MODS', false );
Config::define( 'FORCE_SSL_ADMIN', env( 'FORCE_SSL_ADMIN' ) ?? true );
Config::define( 'DISALLOW_INDEXING', env( 'DISALLOW_INDEXING' ) ?? false );

/** Debug (disabled in production - override in environments/*.php) */
Config::define( 'WP_DEBUG', false );
Config::define( 'WP_DEBUG_DISPLAY', false );
Config::define( 'WP_DEBUG_LOG', false );

/** Performance */
Config::define( 'WP_MEMORY_LIMIT', env( 'WP_MEMORY_LIMIT' ) ?? '512M' );
Config::define( 'WP_MAX_MEMORY_LIMIT', env( 'WP_MAX_MEMORY_LIMIT' ) ?? '512M' );
Config::define( 'CONCATENATE_SCRIPTS', false );

/** Content Management */
Config::define( 'WP_POST_REVISIONS', env( 'WP_POST_REVISIONS' ) ?? true );
Config::define( 'EMPTY_TRASH_DAYS', env( 'EMPTY_TRASH_DAYS' ) ?? 30 );
Config::define( 'AUTOSAVE_INTERVAL', env( 'AUTOSAVE_INTERVAL' ) ?? 180 );

/** Updates & Cron */
Config::define( 'AUTOMATIC_UPDATER_DISABLED', env( 'AUTOMATIC_UPDATER_DISABLED' ) ?? false );
Config::define( 'WP_AUTO_UPDATE_CORE', env( 'WP_AUTO_UPDATE_CORE' ) ?? true );
Config::define( 'DISABLE_WP_CRON', env( 'DISABLE_WP_CRON' ) ?? false );

/** Custom */
Config::define( 'FORCE_VERSION', env( 'FORCE_VERSION' ) ?? false );


/* ==========================================================================
	Plugins Configuration
	========================================================================== */

/**
 * FluentSMTP - Gmail OAuth or SMTP credentials
 */
if ( env( 'FLUENTMAIL_GMAIL_CLIENT_ID' ) && env( 'FLUENTMAIL_GMAIL_CLIENT_SECRET' ) ) {
	Config::define( 'FLUENTMAIL_GMAIL_CLIENT_ID', env( 'FLUENTMAIL_GMAIL_CLIENT_ID' ) );
	Config::define( 'FLUENTMAIL_GMAIL_CLIENT_SECRET', env( 'FLUENTMAIL_GMAIL_CLIENT_SECRET' ) );
}

if ( env( 'FLUENTMAIL_SMTP_USERNAME' ) && env( 'FLUENTMAIL_SMTP_PASSWORD' ) ) {
	Config::define( 'FLUENTMAIL_SMTP_USERNAME', env( 'FLUENTMAIL_SMTP_USERNAME' ) );
	Config::define( 'FLUENTMAIL_SMTP_PASSWORD', env( 'FLUENTMAIL_SMTP_PASSWORD' ) );
}

/**
 * HDA Plugin — Emergency Login Security Bypass
 *
 * Use these ONLY when locked out due to OTP/email issues!
 * Set in .env, remove after recovery.
 */
env( 'HDA_DISABLE_OTP' ) && Config::define( 'HDA_DISABLE_OTP', true );
env( 'HDA_DISABLE_LOGIN_SECURITY' ) && Config::define( 'HDA_DISABLE_LOGIN_SECURITY', true );
env( 'HDA_DISABLE_LOGIN_CAPTCHA' ) && Config::define( 'HDA_DISABLE_LOGIN_CAPTCHA', true );

/** GitHub Auto-Update Tokens */
env( 'HDA_GITHUB_TOKEN' ) && Config::define( 'HDA_GITHUB_TOKEN', env( 'HDA_GITHUB_TOKEN' ) );
env( 'HDAT_GITHUB_TOKEN' ) && Config::define( 'HDAT_GITHUB_TOKEN', env( 'HDAT_GITHUB_TOKEN' ) );


/* ==========================================================================
	Server & Final Setup
	========================================================================== */

/** Detect HTTPS behind reverse proxy/load balancer */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}

/** Load environment-specific config */
$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';
is_file( $env_config ) && require $env_config;

/** Apply all Config::define() calls */
Config::apply();

/** Absolute path to the WordPress directory. */
defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( __DIR__ ) . '/wp/' );
