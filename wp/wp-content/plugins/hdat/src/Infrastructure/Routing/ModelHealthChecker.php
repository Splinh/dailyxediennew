<?php
/**
 * @package HDAT\Infrastructure\Routing
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Routing;

use HDAT\Domain\Credential\CredentialId;
use HDAT\Infrastructure\Persistence\CredentialRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Daily health check: flags credentials whose preferredModel has been
 * removed from the provider's live model list.
 *
 * Runs as a WP-Cron callback. Each invocation:
 *   1. Iterates active credentials with a preferredModel set.
 *   2. Calls ModelCache to get the live model list.
 *   3. If preferredModel is absent → marks `model_status = 'deprecated'`.
 *   4. If preferredModel is present → clears any existing deprecated flag.
 *   5. Fires `hdat_model_deprecated` for newly deprecated credentials.
 */
final class ModelHealthChecker {

	public function __construct(
		private readonly CredentialRepository $credentials,
		private readonly ModelCache $modelCache,
	) {}

	/**
	 * Run the health check.
	 *
	 * @return array<int, array{credential_id: int, provider: string, preferred_model: string, status: string}>
	 */
	public function check(): array {
		$report = [];

		foreach ( $this->credentials->findActive() as $cred ) {
			if ( null === $cred->preferredModel || '' === $cred->preferredModel ) {
				continue;
			}

			$models   = $this->modelCache->getModels( $cred );
			$modelIds = array_map( static fn( $m ) => $m->id, $models );

			// If model list is empty (fetch failed), skip — don't false-positive.
			if ( empty( $modelIds ) ) {
				continue;
			}

			$found = in_array( $cred->preferredModel, $modelIds, true );

			if ( ! $found ) {
				$this->credentials->setModelStatus( $cred->id, 'deprecated' );

				/** @see do_action('hdat_model_deprecated') */
				do_action( 'hdat_model_deprecated', $cred->id, $cred->provider, $cred->preferredModel );

				$report[] = [
					'credential_id'   => $cred->id->value,
					'provider'        => $cred->provider,
					'preferred_model' => $cred->preferredModel,
					'status'          => 'deprecated',
				];
			} else {
				// Model is back / still valid — clear any previous deprecated flag.
				$this->credentials->setModelStatus( $cred->id, null );

				$report[] = [
					'credential_id'   => $cred->id->value,
					'provider'        => $cred->provider,
					'preferred_model' => $cred->preferredModel,
					'status'          => 'ok',
				];
			}
		}

		return $report;
	}
}
