<?php
/**
 * CAPTCHA Provider Interface.
 *
 * Unified contract for all CAPTCHA providers (reCAPTCHA v2, Turnstile, etc.).
 * Consumers call render/verify without knowing which provider is active.
 *
 * @package HDAddons\Modules\Recaptcha\Provider
 */

namespace HDAddons\Modules\Recaptcha\Provider;

\defined( 'ABSPATH' ) || exit;

interface CaptchaProviderInterface {

	/**
	 * Human-readable provider name.
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Enqueue the provider's JavaScript SDK.
	 *
	 * @return void
	 */
	public function enqueueAssets(): void;

	/**
	 * Output the CAPTCHA widget HTML.
	 *
	 * @return void
	 */
	public function renderWidget(): void;

	/**
	 * Read the CAPTCHA response token from $_POST.
	 *
	 * @return string Token value or empty string.
	 */
	public function getResponseToken(): string;

	/**
	 * Verify a CAPTCHA response token server-side.
	 *
	 * @param string $token The CAPTCHA response token.
	 *
	 * @return bool True if verification passed.
	 */
	public function verifyToken( string $token ): bool;
}
