<?php
/**
 * @package HDAT\Interface\Rest
 */

declare(strict_types=1);

namespace HDAT\Interface\Rest;

use HDAT\Auth\WpNonceAuthenticator;
use HDAT\Domain\Consumer\ConsumerToken;
use HDAT\Domain\Consumer\ConsumerTokenId;
use HDAT\Infrastructure\Persistence\ConsumerTokenRepository;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for consumer tokens management.
 */
final class TokenAdminController extends AbstractApiController {

	public function __construct(
		private readonly WpNonceAuthenticator $auth,
		private readonly ConsumerTokenRepository $tokens,
	) {}

	public function register(): void {
		$perm = [ $this->auth, 'check' ];
		$ns   = 'hdat/v1/admin';

		$this->route( $ns, '/tokens', 'GET', 'listTokens', $perm );
		$this->route( $ns, '/tokens', 'POST', 'createToken', $perm );
		$this->route( $ns, '/tokens/(?P<id>\d+)', 'PUT', 'updateToken', $perm );
		$this->route( $ns, '/tokens/(?P<id>\d+)', 'DELETE', 'revokeToken', $perm );
	}

	public function listTokens(): \WP_REST_Response {
		return new \WP_REST_Response(
			array_map( [ $this, 'serializeToken' ], $this->tokens->findAll() ),
			200
		);
	}

	public function createToken( \WP_REST_Request $r ): \WP_REST_Response {
		$name             = sanitize_text_field( (string) $r->get_param( 'name' ) );
		$allowedProviders = $this->sanitizeStringArray( $r->get_param( 'allowed_providers' ) );
		$allowedModels    = $this->sanitizeStringArray( $r->get_param( 'allowed_models' ) );

		$result = $this->tokens->create(
			name:             $name,
			rpmLimit:         $this->nullableInt( $r, 'rpm_limit' ),
			rpdLimit:         $this->nullableInt( $r, 'rpd_limit' ),
			tpmLimit:         $this->nullableInt( $r, 'tpm_limit' ),
			tpdLimit:         $this->nullableInt( $r, 'tpd_limit' ),
			allowedProviders: $allowedProviders,
			allowedModels:    $allowedModels,
			internalOnly:     (bool) $r->get_param( 'internal_only' ),
			expiresAt:        $this->nullableDate( $r, 'expires_at' ),
		);

		$data        = $this->serializeToken( $result['token'] );
		$data['raw'] = $result['raw']; // Shown once.

		return new \WP_REST_Response( $data, 201 );
	}

	public function updateToken( \WP_REST_Request $r ): \WP_REST_Response {
		$id    = new ConsumerTokenId( (int) $r->get_param( 'id' ) );
		$token = $this->tokens->findById( $id );

		$this->tokens->update(
			$id,
			name:             sanitize_text_field( (string) ( $r->get_param( 'name' ) ?? $token->name ) ),
			rpmLimit:         $this->nullableInt( $r, 'rpm_limit' ) ?? $token->rpmLimit,
			rpdLimit:         $this->nullableInt( $r, 'rpd_limit' ) ?? $token->rpdLimit,
			tpmLimit:         $this->nullableInt( $r, 'tpm_limit' ) ?? $token->tpmLimit,
			tpdLimit:         $this->nullableInt( $r, 'tpd_limit' ) ?? $token->tpdLimit,
			allowedProviders: $r->has_param( 'allowed_providers' ) ? $this->sanitizeStringArray( $r->get_param( 'allowed_providers' ) ) : $token->allowedProviders,
			allowedModels:    $r->has_param( 'allowed_models' ) ? $this->sanitizeStringArray( $r->get_param( 'allowed_models' ) ) : $token->allowedModels,
			internalOnly:     $r->has_param( 'internal_only' ) ? (bool) $r->get_param( 'internal_only' ) : $token->internalOnly,
			expiresAt:        $r->has_param( 'expires_at' ) ? $this->nullableDate( $r, 'expires_at' ) : $token->expiresAt,
		);

		return new \WP_REST_Response( $this->serializeToken( $this->tokens->findById( $id ) ), 200 );
	}

	public function revokeToken( \WP_REST_Request $r ): \WP_REST_Response {
		$this->tokens->revoke( new ConsumerTokenId( (int) $r->get_param( 'id' ) ) );

		return new \WP_REST_Response( [ 'revoked' => true ], 200 );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function serializeToken( ConsumerToken $t ): array {
		return [
			'id'                => $t->id->value,
			'name'              => $t->name,
			'token_prefix'      => $t->tokenPrefix,
			'rpm_limit'         => $t->rpmLimit,
			'rpd_limit'         => $t->rpdLimit,
			'tpm_limit'         => $t->tpmLimit,
			'tpd_limit'         => $t->tpdLimit,
			'allowed_providers' => $t->allowedProviders,
			'allowed_models'    => $t->allowedModels,
			'internal_only'     => $t->internalOnly,
			'expires_at'        => $t->expiresAt?->format( 'c' ),
			'revoked_at'        => $t->revokedAt?->format( 'c' ),
		];
	}
}
