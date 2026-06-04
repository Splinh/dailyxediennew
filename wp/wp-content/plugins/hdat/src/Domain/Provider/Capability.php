<?php
/**
 * @package HDAT\Domain\Provider
 */

declare(strict_types=1);

namespace HDAT\Domain\Provider;

defined( 'ABSPATH' ) || exit;

enum Capability: string {
	case Chat         = 'chat';
	case Image        = 'image';
	case Vision       = 'vision';
	case Embedding    = 'embedding';
	case FunctionCall = 'function_call';
	case ToolUse      = 'tool_use';
}
