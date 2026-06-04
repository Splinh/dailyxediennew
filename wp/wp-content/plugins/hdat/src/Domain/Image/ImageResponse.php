<?php
/**
 * @package HDAT\Domain\Image
 */

declare(strict_types=1);

namespace HDAT\Domain\Image;

defined( 'ABSPATH' ) || exit;

final class ImageResponse {

	/**
	 * @param array<int, array{url?: string, b64_json?: string}> $items
	 */
	public function __construct(
		public readonly array $items,
		public readonly string $model,
		public readonly string $provider,
	) {}
}
