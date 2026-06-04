=== HDAddons ===
Contributors: HD
Requires at least: 6.0
Requires PHP: 8.3
Tested up to: 6.9.1
Stable tag: 2.3.9
License: MIT
License URI: https://opensource.org/licenses/MIT

Extra blocks and helpers for WordPress.

== Description ==

HDAddons is a WordPress plugin providing extra blocks, security features, and helper utilities for HD themes.

**Features:**

* Global Setting – Toggle plugin modules on or off from a single dashboard
* Aspect Ratio – Fixed aspect ratios for featured images and media embeds
* Editor – Switch between Classic and Block editors with configurable defaults
* Logs – Unified logging dashboard: Activity Log, Traffic Monitor, and 404 Monitor with tabbed admin UI
* Security – Hardening, WAF Firewall (SQLi/XSS/RCE/LFI), traffic logging, rate limiting, and threat intelligence
* Login Security – Custom login URL, IP restrictions, 2FA (OTP / TOTP / Magic Link), brute-force protection
* File – Upload limits, SVG support, core integrity verification, malware signature scanning
* Optimize – Heartbeat, embeds, wp_head cleanup, database optimization, and cache plugin integration
* Social Link – Social media link management
* Contact Link – Floating contact buttons with popup and click-to-action links
* Custom Sorting – Drag-and-drop ordering for posts, pages, and taxonomies
* Post Type Archive – Assign a static page as the archive for any custom post type
* CAPTCHA – Google reCAPTCHA v2 and Cloudflare Turnstile integration
* Redirect – 301/302 URL redirects with pagination, CSV/XLSX import and export
* Cron Manager – View, run, and delete WP-Cron events with overdue detection
* Maintenance – Maintenance mode to restrict site access during updates
* Custom Code – Inject custom CSS and tracking scripts / JS into head or body
* GitHub auto-update support

**Requirements:**

* WordPress 6.0 or higher
* PHP 8.3 or higher
* Advanced Custom Fields Pro or Secure Custom Fields

== Installation ==

1. Upload `hda` directory to `/wp-content/plugins/`
2. Run `composer install` in the plugin directory
3. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Does this plugin require ACF Pro? =

Yes, Advanced Custom Fields Pro or Secure Custom Fields is required for this plugin to function.

= How do I enable auto-updates from GitHub? =

The GitHub token is managed from the plugin's Global Settings page in wp-admin. No manual configuration in wp-config.php is required.

== Changelog ==

= 2.3.9 - 2026-06-01 =
* **Security:** Hardened rate-limit fixed-window behavior, encrypted TOTP secrets at rest, and reduced Activity Log IP retention precision.

= 2.3.8 - 2026-04-09 =
* **Module Consolidation:** Merged ActivityLog, Monitor404, and TrafficMonitor into new unified `Logs` module with tabbed admin UI
* **Removed Modules:** Removed `SEO`, `CookieConsent`, and `ScheduledContent` modules (dead code cleanup)
* **Login Security:** Extracted OTP sending logic into dedicated `OtpSender` class; refactored `LoginOtpVerification` (~400 lines reduced)
* **Login Security:** Refactored `LoginUrl` custom login URL handling for clarity and maintainability
* **Security:** Enhanced Firewall detection patterns and settings UI
* **Editor:** Refactored module settings with improved toggle controls
* **File:** Improved FileIntegrity admin UI and settings layout
* **Global Setting:** Simplified settings page — extracted module toggles into dedicated partial, removed legacy server-info and menu views
* **Core:** Refactored `ModuleRegistry`, `AbstractModule`, `SettingsManager`, and `HasSettings` contract for cleaner module lifecycle
* **Frontend:** Rebuilt Vite assets; removed unused cookie.js entry point
* **Clean Code:** Removed standalone admin classes (ActivityLogAdmin, Monitor404Admin, TrafficMonitorAdmin) — consolidated into `LogsAdmin`

= 2.3.7 - 2026-04-03 =
* **UI/UX:** Complete modernization of plugin settings interfaces (Security, SEO, SocialLink, Recaptcha, Optimize, Redirect, ScheduledContent, Monitor404, Maintenance, PostTypeArchive) adopting Tailwind CSS v4 and native WordPress BEM standards.
* **UI/UX:** Introduced new `hda-tip`, `hda-details`, and `hda-notice` components for cleaner settings layout, reducing cognitive load and improving accessibility.

= 2.3.6 - 2026-04-02 =
* **Login Security:** Removed recovery constant instructions from UI

= 2.3.5 - 2026-03-20 =
* **Core:** Refactored architecture for Security, LoginSecurity, CAPTCHA, SEO, and Redirect modules (Phase 2 Migration).

= 2.3.4 - 2026-03-11 =
* **Optimize:** Consolidated Heartbeat (frequency + location → preset dropdown) and Core Cleanup (embeds + cleanup → single toggle)
* **Login Security:** Merged "Disable Common Usernames" + "Enable Activity Log" into single "Basic Protection" toggle
* **CAPTCHA:** Merged 4 form checkboxes into single "Protect All WP Forms" toggle; removed "Use recaptcha.net Domain" option
* **Clean Code:** Consolidated option keys across Optimize, Login Security, and CAPTCHA modules

= 2.3.3 - 2026-03-11 =
* **Security:** Consolidated "Attack Detection" controls into single toggle with advanced details
* **Firewall:** Simplified Threat Intelligence settings

= 2.3.2 - 2026-03-10 =
* **Fix:** SVG upload fatal TypeError — `checkFiletypeAndExt()` now accepts nullable `$mimes` parameter matching WordPress core signature

= 2.3.1 - 2026-03-04 =
* **Fix:** IconRenderer trait no longer references host class `Helper::` directly — uses `self::` for proper trait portability
* **Fix:** `getCurrentUrl()` no longer crashes when `$_GET` contains array values (e.g. `?filter[]=value`)
* **Fix:** Removed stale deprecated parameter value in `Options::updateStoredOption()` call
* **Clean Code:** Extracted duplicated magic number `5242880` into `Minify::MAX_EXTRACT_SIZE` constant
* **Clean Code:** Removed trivial `Str::normalizePath()` wrapper — inlined `wp_normalize_path()` in Vite trait
* **Clean Code:** Removed unused `Misc::random()` method — inlined `wp_generate_password()` in `CSRFToken()`
* **Clean Code:** Replaced `md5()` cache key in `Vite::manifestResolve()` with simple string concatenation
* **Clean Code:** Added `@deprecated` annotation to `Options::updateOption()` unused parameter

= 2.3.0 - 2026-03-03 =
* **Redirect:** Redesigned UI from repeater to table layout (widefat striped) with inline editing
* **Redirect:** Added server-side pagination (20 rules per page) with paginated save logic
* **Redirect:** Added CSV and XLSX import via OpenSpout (append or replace modes)
* **Redirect:** Added CSV and XLSX export with one-click download
* **Redirect:** Added import toolbar with mode selector and status feedback
* **Cookie Consent:** Reorganized options into logical fieldsets with informational notices
* **Cookie Consent:** Added JS toggle — disable banner hides all dependent fields; empty privacy URL hides privacy link text
* **Maintenance:** Added informational notices explaining 503 behavior and access bypass hierarchy
* **Cron Manager:** Improved spacing, padding, and visual hierarchy for consistency with design system
* **Cron Manager:** Fixed row deletion animation — rows now properly collapse and are removed from DOM (table rows ignore max-height)
* **UI/UX:** Added hda-notice blocks across Cookie Consent, Maintenance, and Redirect modules
* **UI/UX:** Redirect action button now matches input height (34px) with proper hover states
* **Dependencies:** Added openspout/openspout for spreadsheet import/export

= 2.2.0 - 2026-02-27 =
* Added new modules: SEO, Security (WAF Firewall), Editor, File Manager, Optimize, Cookie Consent, Custom Sorting, Scheduled Content, Post Type Archive, Redirect, 404 Monitor, Cron Manager, Maintenance, Custom Code
* Improved existing modules: Login Security, CAPTCHA, Contact Link, Social Link
* Various bug fixes and performance improvements

= 2.1.8 - 2026-02-13 =
* **Cron Manager:** Fixed one-time events not unscheduled after "Run Now" (prevented double execution)
* **Cron Manager:** Added button loading state to prevent double-click on Run/Delete actions
* **Database Optimizer:** Fixed critical bug where settings were not saved (missing handler)
* **Database Optimizer:** Fixed performance issue — schedule sync now only runs on settings save, not on every admin_init
* **Database Optimizer:** Unified checkbox styles and improved "Run Selected Now" button prominence
* **Login Security:** Added profile page links to SMS/Messaging and TOTP descriptions for quick setup navigation
* **Activity Log:** Fixed "undefined array key" warnings for otp_failed, totp_setup, totp_reset action types
* **Resources:** Reorganized scripts into modules/admin/ and modules/settings/ subdirectories
* **Resources:** Renamed utils.js to utils/jquery-plugins.js for clarity
* **Resources:** Extracted inline JS from Database Optimizer and Cron Manager into dedicated modules
* **Resources:** Localized nonces via wp_localize_script (removed inline PHP-generated scripts)

= 2.1.7 - 2026-02-12 =
* Fixed DB.php bugs: getOne() empty params, updateOneRow/deleteOneRow PK format detection
* Removed 16 unused methods from Traits (Str, Minify, Plugin, Cache, Misc)
* Removed 7 unused methods from Asset.php (enqueueStyles, enqueueScripts, inlineScript, dequeue*, has*)
* Code optimization and dead code cleanup (~390 lines removed)

= 2.1.6 =
* Refactored all module option keys to use class constants (single source of truth)
* Modules updated: LoginSecurity, AspectRatio, ContactLink, CookieConsent, CustomCss, CustomEmailTo, CustomScript, CustomSorting, Editor, File, GlobalSetting, Maintenance, Monitor404, Optimize, PostTypeArchive, Recaptcha, Redirect, ScheduledContent, Security, Seo, SocialLink
* Code maintainability and consistency improvements

= 2.1.5 =
* Refactored SCSS into modular partials (settings/, admin/)
* Refactored JS into ES modules (scripts/modules/)
* Migrated redirect rules storage from wp_options to custom post type (JSON)
* Replaced deprecated jQuery $.trim() with native String.trim()
* Code organization and maintainability improvements

= 2.1.4 =
* Fixed timing issue with remove_action for embeds (called too early)
* Fixed hasPersistentCache returning null on early load
* Improved OTP validation with proper error feedback
* Added lazy loading for LoginSecurity sub-modules
* Added memoization for BaseProvider option parsing
* Improved caching with persistent cache detection
* Fixed transient orphaning in module config loading
* Added rate limiting for cache cleanup
* Removed duplicate version removal filters

= 2.1.3 =
* Added emergency OTP/login security bypass via .env
* Admin warning notice when bypass is active

= 2.1.2 =
* Refactored OTP Profile UI - separated inline CSS/JS into dedicated files
* Added otp-profile.js and otp-profile.scss entry points
* Improved code organization and maintainability

= 2.1.1 =
* Added GitHub auto-update feature
* Enhanced login security with OTP IP binding
* Improved IP restriction validation
* Code refactoring and bug fixes

= 2.0.0 =
* Major refactoring
* PHP 8.3 requirement
* New module architecture

= 1.6.0 =
* Tested on WP 6.7.1

== Upgrade Notice ==

= 2.3.8 =
Module consolidation: ActivityLog + Monitor404 + TrafficMonitor merged into Logs module. Removed SEO, CookieConsent, ScheduledContent. LoginSecurity OTP refactor. No breaking changes.

= 2.3.7 =
UI Modernization: Brand new card-based layouts replacing legacy tables across all modules. Enhanced tooltips and documentation sections. No breaking changes.

= 2.3.6 =
Login Security: Cleaned up settings UI text. No breaking changes.

= 2.3.5 =
Code architecture refactored for Phase 2 Migration. No breaking changes.

= 2.3.4 =
UI simplification: consolidated controls in Optimize, Login Security, and CAPTCHA modules. Fewer option keys, cleaner settings pages. No breaking changes.

= 2.3.3 =
Security & Firewall UI simplification. No breaking changes.

= 2.3.2 =
Fix: SVG upload fatal TypeError when WordPress passes null mimes array. No breaking changes.

= 2.3.1 =
Bug fixes: IconRenderer trait coupling, getCurrentUrl array crash, stale deprecated param. Clean code: extracted constants, removed trivial wrappers, optimized cache keys. No breaking changes.

= 2.3.0 =
Redirect module redesign: table UI, pagination, CSV/XLSX import & export. Cookie Consent & Maintenance UI clarity improvements. Cron Manager row delete fix. New dependency: openspout/openspout. No breaking changes.

= 2.2.0 =
New modules: SEO, Security, Editor, File, Optimize, Cookie Consent, Custom Sorting, Scheduled Content, Post Type Archive, Redirect, 404 Monitor, Cron Manager, Maintenance, Custom Code. Bug fixes and improvements. No breaking changes.
