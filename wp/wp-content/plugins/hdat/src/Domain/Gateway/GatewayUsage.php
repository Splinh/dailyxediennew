<?php
/**
 * @package HDAT\Domain\Gateway
 */

declare(strict_types=1);

namespace HDAT\Domain\Gateway;

defined( 'ABSPATH' ) || exit;

final class GatewayUsage {

	public function __construct(
		public readonly int $promptTokens = 0,
		public readonly int $completionTokens = 0,
		public readonly int $totalTokens = 0,
	) {}
}
