<?php
/**
 * @package HDAT\Infrastructure\Routing
 */

declare(strict_types=1);

namespace HDAT\Infrastructure\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown when a consumer or credential exceeds an RPM/RPD/TPM/TPD limit.
 *
 * `$dimension` is one of rpm|rpd|tpm|tpd so the API layer can map it to a
 * 429 with a precise reason.
 */
final class QuotaExceededException extends \RuntimeException {

	public function __construct(
		public readonly string $dimension,
		string $message = '',
	) {
		parent::__construct( '' === $message ? "Quota exceeded: {$dimension}" : $message );
	}
}
