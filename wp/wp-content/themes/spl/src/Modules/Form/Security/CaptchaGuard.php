<?php
/**
 * CAPTCHA Guard Factory
 *
 * Resolves the correct CaptchaGuardInterface implementation
 * based on form_type config or data-* fallback from HTML attributes.
 *
 * @package SPL\Modules\Form\Security
 */

namespace SPL\Modules\Form\Security;

use SPL\Modules\Form\FormConfig;

defined( 'ABSPATH' ) || exit;

final class CaptchaGuard {

	/**
	 * Build a CaptchaGuard from the config registry.
	 *
	 * Falls back to data-* attributes when the form_type is NOT
	 * registered in form_config → form_types.
	 *
	 * @param string      $formType Form type slug.
	 * @param string|null $provider Override provider (from data-captcha attribute).
	 *
	 * @return CaptchaGuardInterface
	 */
	public static function make( string $formType, ?string $provider = null ): CaptchaGuardInterface {
		$provider ??= FormConfig::getCaptchaProvider( $formType );
		$config     = FormConfig::all()['captcha'] ?? [];
		$failOpen   = ! empty( $config['fail_open_on_network_error'] );

		return match ( $provider ) {
			'recaptcha_v2' => new RecaptchaV2Guard(
				$config['recaptcha_v2']['site_key'] ?? '',
				$config['recaptcha_v2']['secret_key'] ?? '',
				$failOpen,
			),
			'recaptcha_v3' => new RecaptchaV3Guard(
				$config['recaptcha_v3']['site_key'] ?? '',
				$config['recaptcha_v3']['secret_key'] ?? '',
				(float) ( $config['recaptcha_v3']['score_threshold'] ?? 0.5 ),
				(string) ( $config['recaptcha_v3']['action'] ?? 'form_submit' ),
				$failOpen,
			),
			'turnstile' => new TurnstileGuard(
				$config['turnstile']['site_key'] ?? '',
				$config['turnstile']['secret_key'] ?? '',
				$failOpen,
			),
			default => new NullGuard(),
		};
	}
}
