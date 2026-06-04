<?php
/**
 * Form entry persisted statuses.
 *
 * @package SPL\Modules\Form\Enum
 */

namespace SPL\Modules\Form\Enum;

defined( 'ABSPATH' ) || exit;

enum FormEntryStatus: string {
	case New     = 'new';
	case Read    = 'read';
	case Starred = 'starred';
	case Spam    = 'spam';
	case Trash   = 'trash';

	public static function fromRaw( mixed $status ): ?self {
		return self::tryFrom( sanitize_key( (string) $status ) );
	}

	/**
	 * @return array<int, string>
	 */
	public static function values(): array {
		return array_map( static fn( self $status ): string => $status->value, self::cases() );
	}
}
