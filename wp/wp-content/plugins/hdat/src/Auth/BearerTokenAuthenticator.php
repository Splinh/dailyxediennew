<?php
/**
 * @package HDAT\Auth
 */

declare(strict_types=1);

namespace HDAT\Auth;

use HDAT\Infrastructure\Persistence\ConsumerTokenRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Permission callback for the public REST namespace.
 *
 * Reads `Authorization: Bearer hdat_…` (or the WP-friendly `HTTP_AUTHORIZATION`
 * fallback for Apache + FastCGI), looks the token up by sha256, and validates
 * `isValid()` (revoked / expired). On success the resolved ConsumerToken is
 * attached to the request as `consumer_token` for the controller to read.
 *
 * Returns a WP_Error on failure — REST core turns that into a JSON 401/403
 * response automatically.
 */
final class BearerTokenAuthenticator {

	public function __construct(
		private readonly ConsumerTokenRepository $tokens,
	) {}

	public function authenticate( \WP_REST_Request $request ): bool|\WP_Error {
		$header = $this->extractAuthHeader( $request );
		if ( '' === $header ) {
			return new \WP_Error( 'hdat_missing_token', 'Missing Authorization header.', [ 'status' => 401 ] );
		}

		if ( ! preg_match( '/^Bearer\s+(.+)$/i', $header, $m ) ) {
			return new \WP_Error( 'hdat_malformed_token', 'Authorization header must use the Bearer scheme.', [ 'status' => 401 ] );
		}

		$raw = trim( $m[1] );
		if ( '' === $raw ) {
			return new \WP_Error( 'hdat_empty_token', 'Empty bearer token.', [ 'status' => 401 ] );
		}

		$token = $this->tokens->findByRawToken( $raw );
		if ( null === $token ) {
			return new \WP_Error( 'hdat_invalid_token', 'Token not recognised.', [ 'status' => 401 ] );
		}

		if ( ! $token->isValid() ) {
			return new \WP_Error( 'hdat_token_inactive', 'Token revoked or expired.', [ 'status' => 403 ] );
		}

		// REST request attributes survive into the route handler.
		$request->set_attributes( array_merge( $request->get_attributes(), [ 'consumer_token' => $token ] ) );

		return true;
	}

	private function extractAuthHeader( \WP_REST_Request $request ): string {
		$h = (string) $request->get_header( 'authorization' );
		if ( '' !== $h ) {
			return $h;
		}

		// Apache + FastCGI strip Authorization unless mod_rewrite restores it;
		// some hosts expose it under HTTP_AUTHORIZATION or REDIRECT_HTTP_AUTHORIZATION.
		foreach ( [ 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ] as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				return (string) $_SERVER[ $key ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}
		}

		return '';
	}
}
