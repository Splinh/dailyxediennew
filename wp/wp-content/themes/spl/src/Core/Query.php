<?php
/**
 * WordPress Query Utilities
 *
 * Static utility class for all WordPress post/term data retrieval,
 * display helpers, and query building with caching.
 *
 * Mirrors the Helper pattern: thin class in Core/ with logic in Traits/.
 *
 * @package SPL\Core
 * @author  HD
 */

namespace SPL\Core;

use SPL\Traits\WpPost;
use SPL\Traits\WpQuery;

defined( 'ABSPATH' ) || exit;

final class Query {
	use WpPost;
	use WpQuery;
}
