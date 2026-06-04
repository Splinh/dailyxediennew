=== HD Theme ===

Contributors: HD
Requires at least: WordPress 6.5
Tested up to: WordPress 6.9
Version: 2.1.2
Requires PHP: 8.3
License: MIT

== Description ==
== Installation ==

1. In your admin panel, go to Appearance > Themes and click the 'Add New' button.
2. Click 'Upload theme' and upload the zipped child
3. Click on the 'Activate' button to use your new theme right away.

== Customizations ==
== Frequently Asked Questions ==
== Changelog ==

= 2.1.2 - 2026-04-13 =
* Refactored Service Page Templates for consistent UI/UX and modular architecture
* Enforced singleton pattern in ModuleRegistry

= 2.1.1 - 2026-02-12 =
* Fixed DB.php bugs: getOne() empty params, updateOneRow/deleteOneRow PK format detection
* Removed unused methods from Traits (Str, Minify, Plugin, Cache, Misc) and Asset.php
* Code optimization and dead code cleanup

= 2.1.0 =
* Major version bump

= 1.13.0 - 2025-12-28 =
* Refactor: Restructured core/ directory
  - Removed Services/ - moved Modules/ to root level
  - Removed Integration/ - merged into new Plugins/
  - Moved Libraries/ into Utilities/Libraries/ (for third-party code)
  - Updated namespaces: HD\Modules\*, HD\Plugins\*, HD\Utilities\Libraries\*
* Refactor: Reorganized resources/components/
  - Added plugins/ folder for plugin-specific assets
  - Added modules/ folder for module-specific assets
  - Moved WooCommerce assets to plugins/woocommerce/

= 1.12.0 =
* Previous release

= 1.8.0 - 2025-08-01 =
* Initial Public Release

