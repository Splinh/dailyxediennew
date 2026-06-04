<?php
/**
 * @package HDAT\Domain\Gateway
 */

declare(strict_types=1);

namespace HDAT\Domain\Gateway;

use HDAT\Domain\Consumer\ConsumerTokenId;

defined( 'ABSPATH' ) || exit;

final class GatewayRequest {

	/**
	 * @param array<int, array<string, mixed>> $messages
	 * @param array<string, mixed> $extra Pass-through (tools, tool_choice, response_format, ...).
	 */
	public function __construct(
		public readonly array $messages,
		public readonly ?string $model = null,
		public readonly ?string $provider = null,
		public readonly float $temperature = 0.7,
		public readonly int $maxTokens = 2048,
		public readonly bool $stream = false,
		public readonly ?ConsumerTokenId $consumerId = null,
		public readonly array $extra = [],
	) {}

	public function withStream( bool $stream ): self {
		return new self(
			messages:    $this->messages,
			model:       $this->model,
			provider:    $this->provider,
			temperature: $this->temperature,
			maxTokens:   $this->maxTokens,
			stream:      $stream,
			consumerId:  $this->consumerId,
			extra:       $this->extra,
		);
	}

	/**
	 * @param array<string, mixed> $extra
	 */
	public function withExtra( array $extra ): self {
		return new self(
			messages:    $this->messages,
			model:       $this->model,
			provider:    $this->provider,
			temperature: $this->temperature,
			maxTokens:   $this->maxTokens,
			stream:      $this->stream,
			consumerId:  $this->consumerId,
			extra:       $extra,
		);
	}
}
