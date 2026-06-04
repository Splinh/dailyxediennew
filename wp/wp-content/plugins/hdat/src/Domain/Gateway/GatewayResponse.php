<?php
/**
 * @package HDAT\Domain\Gateway
 */

declare(strict_types=1);

namespace HDAT\Domain\Gateway;

defined( 'ABSPATH' ) || exit;

final class GatewayResponse {

	public function __construct(
		public readonly string $content,
		public readonly string $model,
		public readonly string $provider,
		public readonly GatewayUsage $usage,
		public readonly bool $cached = false,
		public readonly ?string $finishReason = null,
	) {}
}
