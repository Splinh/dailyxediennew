<?php
/**
 * @package HDAT\Infrastructure\Routing
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Routing;

use HDAT\Domain\Gateway\GatewayRequest;
use HDAT\Kernel\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Trims bloated request payloads before sending to the provider.
 *
 * Opt-in via `Settings::get('request_shaper')`:
 *   - 'off'  (default) — no-op, return input unchanged.
 *   - 'safe' — trim long tool descriptions and metadata values.
 *
 * Called once in GatewayService before estimateMessages() so both token
 * estimate and the actual HTTP payload benefit from the trimming.
 */
final class RequestShaper {

	private const MAX_TOOL_DESC_LENGTH = 1200;
	private const MAX_METADATA_LENGTH  = 2000;

	/**
	 * Shape a request by trimming bloated tools/metadata in `extra`.
	 *
	 * Returns the same instance when no changes are needed (zero-alloc
	 * short-circuit).
	 */
	public function shape( GatewayRequest $req ): GatewayRequest {
		$mode = (string) Settings::get( 'request_shaper', 'off' );

		if ( 'safe' !== $mode ) {
			return $req;
		}

		$extra   = $req->extra;
		$changed = false;

		// Trim tool function descriptions.
		if ( ! empty( $extra['tools'] ) && is_array( $extra['tools'] ) ) {
			foreach ( $extra['tools'] as $i => $tool ) {
				$desc = $tool['function']['description'] ?? null;
				if ( is_string( $desc ) && mb_strlen( $desc ) > self::MAX_TOOL_DESC_LENGTH ) {
					$extra['tools'][ $i ]['function']['description'] = mb_substr( $desc, 0, self::MAX_TOOL_DESC_LENGTH ) . '…';
					$changed = true;
				}
			}
		}

		// Trim oversized metadata entries.
		if ( ! empty( $extra['metadata'] ) && is_array( $extra['metadata'] ) ) {
			foreach ( $extra['metadata'] as $key => $value ) {
				if ( is_string( $value ) && mb_strlen( $value ) > self::MAX_METADATA_LENGTH ) {
					$extra['metadata'][ $key ] = mb_substr( $value, 0, self::MAX_METADATA_LENGTH ) . '…';
					$changed                   = true;
				}
			}
		}

		return $changed ? $req->withExtra( $extra ) : $req;
	}
}
