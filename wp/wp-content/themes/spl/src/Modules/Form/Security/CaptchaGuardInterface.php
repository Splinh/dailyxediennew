<?php
/**
 * CAPTCHA Guard Interface — Strategy Pattern
 *
 * @package SPL\Modules\Form\Security
 */

namespace SPL\Modules\Form\Security;

defined( 'ABSPATH' ) || exit;

interface CaptchaGuardInterface {

	/**
	 * Verify a CAPTCHA token.
	 *
	 * @param string $token Token from the frontend widget.
	 * @param string $ip    Client IP address.
	 *
	 * @return bool True if verification passed.
	 */
	public function verify( string $token, string $ip ): bool;

	/**
	 * Render the CAPTCHA widget HTML.
	 *
	 * @return string HTML output.
	 */
	public function renderField(): string;

	/**
	 * Get the external script URL for this provider.
	 *
	 * @return string URL or empty string for NullGuard.
	 */
	public function getScriptUrl(): string;
}
