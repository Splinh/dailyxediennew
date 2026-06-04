<?php
/**
 * Translation unit DTO.
 *
 * @package SPL\Modules\PLL\AI
 */

namespace SPL\Modules\PLL\AI;

defined( 'ABSPATH' ) || exit;

final class TranslationUnit {

	/**
	 * @param string[] $protected_tokens Tokens that must remain unchanged.
	 * @param string[] $path             Object/content path metadata.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $source,
		public readonly string $context = '',
		public readonly string $format = 'text',
		public readonly array $protected_tokens = [],
		public readonly array $path = []
	) {}

	/**
	 * @param array<string, mixed> $data Raw unit data.
	 */
	public static function fromArray( array $data ): self {
		return new self(
			sanitize_key( (string) ( $data['id'] ?? '' ) ),
			(string) ( $data['source'] ?? '' ),
			(string) ( $data['context'] ?? '' ),
			sanitize_key( (string) ( $data['format'] ?? 'text' ) ),
			array_values( array_map( 'strval', (array) ( $data['protected_tokens'] ?? [] ) ) ),
			array_values( array_map( 'strval', (array) ( $data['path'] ?? [] ) ) )
		);
	}
}
