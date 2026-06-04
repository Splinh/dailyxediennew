<?php
/**
 * @package HDAT\Domain\Credential
 */

declare(strict_types=1);

namespace HDAT\Domain\Credential;

defined( 'ABSPATH' ) || exit;

enum CredentialTier: string {
	case Free = 'free';
	case Paid = 'paid';
}
