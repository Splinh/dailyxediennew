<?php
/**
 * ACF Entity — Term fields.
 *
 * Handles ACF fields attached to translated taxonomies.
 *
 * @package HD\Modules\PLL\ACF\Entity
 */

namespace HD\Modules\PLL\ACF\Entity;

defined( 'ABSPATH' ) || exit;

final class TermEntity extends AbstractEntity {

	protected static function acfId( int $id ): string {
		return 'term_' . $id;
	}

	public function getType(): string {
		return 'term';
	}

	protected function getFromIdInRequest(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['taxonomy'], $_GET['from_tag'] ) ) {
			return absint( $_GET['from_tag'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return 0;
	}
}
