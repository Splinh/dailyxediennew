<?php
/**
 * Persisted WooCommerce swatch type values.
 *
 * @package HD\Modules\WooCommerce\Swatches\Enum
 */

namespace HD\Modules\WooCommerce\Swatches\Enum;

defined( 'ABSPATH' ) || exit;

enum SwatchType: string {
	case None  = '';
	case Color = 'color';
	case Image = 'image';
	case Label = 'label';
	case Radio = 'radio';

	public static function fromRaw( mixed $type ): self {
		return self::tryFrom( sanitize_key( (string) $type ) ) ?? self::None;
	}

	public function isConfigured(): bool {
		return self::None !== $this;
	}

	/**
	 * @return array<string, string>
	 */
	public static function labelOptions(): array {
		return [
			self::None->value  => __( 'None (default dropdown)', 'hd' ),
			self::Color->value => __( 'Color', 'hd' ),
			self::Image->value => __( 'Image', 'hd' ),
			self::Label->value => __( 'Label', 'hd' ),
			self::Radio->value => __( 'Radio', 'hd' ),
		];
	}
}
