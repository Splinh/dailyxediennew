<?php
/**
 * @package HDAT\Domain\Provider
 */

declare(strict_types=1);

namespace HDAT\Domain\Provider;

defined( 'ABSPATH' ) || exit;

final class ModelMeta {

	/**
	 * @param string[] $modalities Input modalities (text, image, audio).
	 * @param array<string, mixed>|null $pricing Optional pricing info.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $name,
		public readonly int $contextWindow = 0,
		public readonly array $modalities = [ 'text' ],
		public readonly ?array $pricing = null,
	) {}
}
