<?php
/**
 * Configuration overrides for WP_ENV === 'production'
 *
 * This file documents the production baseline.
 * Most values are already set in config/application.php.
 * Only add overrides here if production needs differ from the defaults.
 *
 * @package HD
 */

use Roots\WPConfig\Config;

/** Ensure debug is disabled in production */
Config::define( 'WP_DEBUG', false );
Config::define( 'WP_DEBUG_DISPLAY', false );
Config::define( 'WP_DEBUG_LOG', false );
Config::define( 'SCRIPT_DEBUG', false );
Config::define( 'SAVEQUERIES', false );

/** Security hardening */
Config::define( 'DISALLOW_FILE_EDIT', true );
Config::define( 'DISALLOW_FILE_MODS', true );
Config::define( 'DISALLOW_INDEXING', false );
