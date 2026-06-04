<?php
/**
 * Static utility facade.
 *
 * Aggregates all Trait utilities into one class for convenient access.
 * Theme and modules call Helper:: for options, network, SVG, minify, etc.
 *
 * Traits (8 files):
 *  Options    — WP option wrappers + stored options (CPT)
 *  Cache      — HDA transient cleanup + cache plugin notification
 *  Filesystem — WP_Filesystem wrappers
 *  Minify     — JS/CSS extraction + minification
 *  Misc       — Environment, logging, strings, plugin detection, forms, settings
 *  Network    — IP detection + matching (exact, CIDR, dash range, IPv6)
 *  Svg        — SVG sanitization + icon rendering
 *  Vite       — Vite manifest parsing + resolution
 *
 * @package HDAddons
 */

namespace HDAddons;

\defined( 'ABSPATH' ) || exit;

final class Helper {
	use Traits\Options;
	use Traits\Cache;
	use Traits\Crypto;
	use Traits\Filesystem;
	use Traits\Minify;
	use Traits\Misc;
	use Traits\Network;
	use Traits\Svg;
	use Traits\Vite;
}
