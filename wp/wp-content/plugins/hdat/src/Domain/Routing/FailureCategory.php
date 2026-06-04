<?php
/**
 * @package HDAT\Domain\Routing
 */

declare(strict_types=1);

namespace HDAT\Domain\Routing;

defined( 'ABSPATH' ) || exit;

enum FailureCategory: string {
	case RateLimit = 'rate_limit';
	case Auth      = 'auth';
	case Timeout   = 'timeout';
	case Server    = 'server';
	case Unknown   = 'unknown';
}
