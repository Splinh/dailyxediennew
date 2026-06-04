<?php
/**
 * @package HDAT\Domain\Image
 */

declare(strict_types=1);

namespace HDAT\Domain\Image;

use HDAT\Domain\Consumer\ConsumerTokenId;

defined( 'ABSPATH' ) || exit;

final class ImageRequest {

	/**
	 * @param array<string, mixed> $extra
	 */
	public function __construct(
		public readonly string $prompt,
		public readonly ?string $model = null,
		public readonly ?string $provider = null,
		public readonly string $size = '1024x1024',
		public readonly int $count = 1,
		public readonly ?ConsumerTokenId $consumerId = null,
		public readonly array $extra = [],
	) {}
}
