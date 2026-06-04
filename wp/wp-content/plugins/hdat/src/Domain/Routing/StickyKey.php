<?php
/**
 * @package HDAT\Domain\Routing
 */

declare(strict_types=1);

namespace HDAT\Domain\Routing;

use HDAT\Domain\Consumer\ConsumerTokenId;
use HDAT\Domain\Gateway\GatewayRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Derives the affinity key that pins a multi-turn conversation to one route.
 *
 * Stickiness is OPT-IN: the caller must supply a conversation/thread/session
 * id in the request `extra`. Without one we return null and routing stays
 * stateless — we deliberately do NOT key on message content, because that
 * would make every distinct prompt its own "conversation" and defeat the
 * purpose (and balloon the table).
 *
 * The key is scoped to the consumer so two tenants sharing a conversation id
 * never collide.
 */
final class StickyKey {

	private const ID_FIELDS = [ 'conversation_id', 'thread_id', 'session_id' ];

	public static function derive( GatewayRequest $req, ?ConsumerTokenId $consumerId ): ?string {
		$id = self::extractId( $req );
		if ( null === $id ) {
			return null;
		}

		$scope = null !== $consumerId ? (string) $consumerId->value : 'anon';

		return hash( 'sha256', "{$scope}|{$id}" );
	}

	private static function extractId( GatewayRequest $req ): ?string {
		foreach ( self::ID_FIELDS as $field ) {
			$value = $req->extra[ $field ] ?? null;
			if ( is_string( $value ) && '' !== $value ) {
				return $field . ':' . $value;
			}
			if ( is_int( $value ) ) {
				return $field . ':' . $value;
			}
		}

		return null;
	}
}
