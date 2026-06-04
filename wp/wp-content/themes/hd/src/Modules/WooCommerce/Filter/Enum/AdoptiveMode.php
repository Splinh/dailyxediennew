<?php
/**
 * WooCommerce filter adoptive display modes.
 *
 * @package HD\Modules\WooCommerce\Filter\Enum
 */

namespace HD\Modules\WooCommerce\Filter\Enum;

defined( 'ABSPATH' ) || exit;

enum AdoptiveMode: string {
	case Show    = 'show';
	case Hide    = 'hide';
	case Disable = 'disable';

	public static function fromConfig( mixed $value ): self {
		return self::tryFrom( sanitize_key( (string) $value ) ) ?? self::Show;
	}

	public function hidesEmpty(): bool {
		return self::Hide === $this;
	}

	public function disablesEmpty(): bool {
		return self::Disable === $this;
	}
}
