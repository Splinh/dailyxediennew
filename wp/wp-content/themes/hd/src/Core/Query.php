<?php
/**
 * WordPress Query Utilities
 *
 * Static utility class for all WordPress post/term data retrieval,
 * display helpers, and query building with caching.
 *
 * Mirrors the Helper pattern: thin class in Core/ with logic in Traits/.
 *
 * @package HD\Core
 * @author  HD
 */

namespace HD\Core;

use HD\Traits\WpPost;
use HD\Traits\WpQuery;

defined( 'ABSPATH' ) || exit;

final class Query {
	use WpPost;
	use WpQuery;
}
